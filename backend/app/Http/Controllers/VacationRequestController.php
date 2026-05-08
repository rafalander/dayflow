<?php

namespace App\Http\Controllers;

use App\Models\VacationRequest;
use App\Models\User;
use App\Services\UpcomingAbsencesService;
use App\Services\VacationService;
use App\Support\AbsenceTypes;
use App\Support\ApiQueryCacheGens;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class VacationRequestController extends Controller
{
    public function __construct(
        private VacationService $vacationService,
        private UpcomingAbsencesService $upcomingAbsencesService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = VacationRequest::with('user', 'approver');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        if ($request->filled('absence_type')) {
            $query->where('absence_type', $request->absence_type);
        }

        if (!$request->user()->isAdmin()) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                  ->orWhere('approver_id', $request->user()->id);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => $requests,
            'status' => 'success',
        ]);
    }

    public function show(VacationRequest $vacation_request): JsonResponse
    {
        $vacation_request->load('user', 'approver');

        return response()->json([
            'data' => $vacation_request,
            'status' => 'success',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'absence_type' => ['required', 'string', 'max:64', Rule::in(AbsenceTypes::slugs())],
            'reason' => 'nullable|string|max:500',
            'comments' => 'nullable|string',
        ]);

        $conflicts = $this->vacationService->checkConflicts(
            $request->user()->id,
            $validated['start_date'],
            $validated['end_date']
        );

        if ($conflicts['hasConflict']) {
            return response()->json([
                'message' => 'Vacation request conflicts with existing approvals or exceeds team limit',
                'conflicts' => $conflicts,
                'status' => 'error',
            ], 422);
        }

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $businessDays = VacationRequest::calculateBusinessDays($start, $end);

        $vacation = VacationRequest::create([
            'user_id' => $request->user()->id,
            'absence_type' => $validated['absence_type'],
            'approver_id' => $request->user()->manager_id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => blank($validated['reason'] ?? null) ? null : $validated['reason'],
            'status' => 'pending',
            'business_days' => $businessDays,
        ]);

        $this->vacationService->notifyApprover($vacation);

        ApiQueryCacheGens::bumpVacation();

        return response()->json([
            'data' => $vacation,
            'message' => 'Vacation request created successfully',
            'status' => 'success',
        ], 201);
    }

    public function update(Request $request, VacationRequest $vacation_request): JsonResponse
    {
        $this->authorize('update', $vacation_request);

        if ($vacation_request->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update a vacation request that has been already processed',
                'status' => 'error',
            ], 422);
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'absence_type' => ['sometimes', 'string', 'max:64', Rule::in(AbsenceTypes::slugs())],
            'reason' => 'sometimes|nullable|string|max:500',
        ]);

        $vacation_request->fill($validated);

        if ($vacation_request->isDirty(['start_date', 'end_date'])) {
            $vacation_request->business_days = VacationRequest::calculateBusinessDays(
                Carbon::parse($vacation_request->start_date),
                Carbon::parse($vacation_request->end_date)
            );
        }

        $vacation_request->save();

        ApiQueryCacheGens::bumpVacation();

        return response()->json([
            'data' => $vacation_request,
            'message' => 'Vacation request updated successfully',
            'status' => 'success',
        ]);
    }

    public function destroy(VacationRequest $vacation_request): JsonResponse
    {
        $this->authorize('delete', $vacation_request);

        if ($vacation_request->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot delete a vacation request that has been already processed',
                'status' => 'error',
            ], 422);
        }

        $vacation_request->delete();

        ApiQueryCacheGens::bumpVacation();

        return response()->json([
            'message' => 'Vacation request deleted successfully',
            'status' => 'success',
        ]);
    }

    public function teamStats(Request $request): JsonResponse
    {
        $auth = $request->user();

        if (! $auth->canViewTeamVacationStats()) {
            return response()->json([
                'message' => 'Acesso negado.',
                'status' => 'error',
            ], 403);
        }

        $gen = ApiQueryCacheGens::vacation();
        $ttl = (int) config('dayflow.api_read_cache.team_stats', 120);
        $userId = $auth->id;

        $data = Cache::remember(
            "api.team_stats.{$gen}.{$userId}",
            $ttl,
            function () use ($auth) {
                if ($auth->isAdmin()) {
                    $userIds = User::query()->active()->pluck('id');
                } else {
                    $userIds = $auth->subordinates()->pluck('id');
                }

                $base = VacationRequest::query()->whereIn('user_id', $userIds);

                return [
                    'approved' => (clone $base)->where('status', 'approved')->count(),
                    'pending' => (clone $base)->where('status', 'pending')->count(),
                    'rejected' => (clone $base)->where('status', 'rejected')->count(),
                    'total' => (clone $base)->count(),
                ];
            }
        );

        return response()->json([
            'data' => $data,
            'status' => 'success',
        ]);
    }

    public function upcomingAbsences(Request $request): JsonResponse
    {
        $days = $this->upcomingAbsencesService->resolveHorizonDays();
        $gen = ApiQueryCacheGens::vacation();
        $ttl = (int) config('dayflow.api_read_cache.upcoming_absences', 180);

        $payload = Cache::remember(
            "api.upcoming.{$gen}.d{$days}",
            $ttl,
            function () {
                $result = $this->upcomingAbsencesService->upcomingApprovedVacations();

                return [
                    'data' => $result['vacations'],
                    'meta' => [
                        'days' => $result['days'],
                    ],
                    'status' => 'success',
                ];
            }
        );

        return response()->json($payload);
    }

    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];
        $gen = ApiQueryCacheGens::vacation();
        $ttl = (int) config('dayflow.api_read_cache.vacation_calendar', 300);

        $approved = Cache::remember(
            "api.calendar.{$gen}.{$startDate}.{$endDate}",
            $ttl,
            function () use ($startDate, $endDate) {
                return VacationRequest::query()
                    ->where('status', 'approved')
                    ->whereDate('start_date', '<=', $endDate)
                    ->whereDate('end_date', '>=', $startDate)
                    ->with(['user:id,name,email'])
                    ->orderBy('start_date')
                    ->get();
            }
        );

        $pendingMine = VacationRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->with(['user:id,name,email'])
            ->orderBy('start_date')
            ->get();

        $vacations = $approved
            ->concat($pendingMine)
            ->unique('id')
            ->sortBy('start_date')
            ->values();

        return response()->json([
            'data' => $vacations,
            'status' => 'success',
        ]);
    }
}
