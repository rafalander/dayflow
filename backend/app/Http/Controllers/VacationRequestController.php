<?php

namespace App\Http\Controllers;

use App\Models\VacationRequest;
use App\Models\User;
use App\Services\VacationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VacationRequestController extends Controller
{
    public function __construct(private VacationService $vacationService) {}

    /**
     * List vacation requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = VacationRequest::with('user', 'approver');

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        // Get only requests that user can see
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

    /**
     * Get single vacation request
     */
    public function show(VacationRequest $vacation_request): JsonResponse
    {
        $vacation_request->load('user', 'approver');

        return response()->json([
            'data' => $vacation_request,
            'status' => 'success',
        ]);
    }

    /**
     * Create vacation request
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'comments' => 'nullable|string',
        ]);

        // Check for conflicts
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

        // Create vacation request
        $vacation = VacationRequest::create([
            'user_id' => $request->user()->id,
            'approver_id' => $request->user()->manager_id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Send notifications
        $this->vacationService->notifyApprover($vacation);

        return response()->json([
            'data' => $vacation,
            'message' => 'Vacation request created successfully',
            'status' => 'success',
        ], 201);
    }

    /**
     * Update vacation request (before approval)
     */
    public function update(Request $request, VacationRequest $vacation_request): JsonResponse
    {
        $this->authorize('update', $vacation_request);

        // Can only update pending requests
        if ($vacation_request->status !== 'pending') {
            return response()->json([
                'message' => 'Cannot update a vacation request that has been already processed',
                'status' => 'error',
            ], 422);
        }

        $validated = $request->validate([
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'sometimes|nullable|string|max:500',
        ]);

        $vacation_request->update($validated);

        return response()->json([
            'data' => $vacation_request,
            'message' => 'Vacation request updated successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Delete vacation request (before approval)
     */
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

        return response()->json([
            'message' => 'Vacation request deleted successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Calendário corporativo: todas as férias aprovadas que interceptam o período [start_date, end_date].
     * Visível para qualquer usuário autenticado (planejamento da equipe).
     */
    public function calendar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Sobreposição: intervalo da solicitação [start_date, end_date] ∩ período pedido ≠ ∅
        $vacations = VacationRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->with(['user:id,name,email'])
            ->orderBy('start_date')
            ->get();

        return response()->json([
            'data' => $vacations,
            'status' => 'success',
        ]);
    }
}
