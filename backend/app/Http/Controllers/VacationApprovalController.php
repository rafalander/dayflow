<?php

namespace App\Http\Controllers;

use App\Models\VacationApproval;
use App\Models\VacationRequest;
use App\Services\VacationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacationApprovalController extends Controller
{
    public function __construct(private VacationService $vacationService) {}

    public function approve(Request $request, VacationRequest $vacation): JsonResponse
    {
        $this->authorize('approve', $vacation);

        if ($vacation->status !== 'pending') {
            return response()->json([
                'message' => 'Vacation request has already been processed',
                'status' => 'error',
            ], 422);
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        VacationApproval::create([
            'vacation_request_id' => $vacation->id,
            'approver_id' => $request->user()->id,
            'action' => 'approved',
            'comment' => $validated['comments'] ?? null,
        ]);

        $vacation->update([
            'status' => 'approved',
            'approver_id' => $request->user()->id,
        ]);

        $this->vacationService->notifyRequester($vacation, 'approved');

        $this->vacationService->logAudit($request->user(), 'vacation_approved', $vacation);

        return response()->json([
            'data' => $vacation->load('approver'),
            'message' => 'Vacation request approved successfully',
            'status' => 'success',
        ]);
    }

    public function reject(Request $request, VacationRequest $vacation): JsonResponse
    {
        $this->authorize('approve', $vacation);

        if ($vacation->status !== 'pending') {
            return response()->json([
                'message' => 'Vacation request has already been processed',
                'status' => 'error',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        VacationApproval::create([
            'vacation_request_id' => $vacation->id,
            'approver_id' => $request->user()->id,
            'action' => 'rejected',
            'comment' => $validated['reason'],
        ]);

        $vacation->update([
            'status' => 'rejected',
            'approver_id' => $request->user()->id,
        ]);

        $this->vacationService->notifyRequester($vacation, 'rejected');

        $this->vacationService->logAudit($request->user(), 'vacation_rejected', $vacation);

        return response()->json([
            'data' => $vacation->load('approver'),
            'message' => 'Vacation request rejected successfully',
            'status' => 'success',
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $query = VacationRequest::query()
            ->where('status', 'pending')
            ->with('user', 'approver')
            ->orderBy('created_at', 'desc');

        if (! $request->user()->isAdmin()) {
            $query->where('approver_id', $request->user()->id);
        }

        return response()->json([
            'data' => $query->paginate($request->input('per_page', 15)),
            'status' => 'success',
        ]);
    }
}
