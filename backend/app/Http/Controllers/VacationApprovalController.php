<?php

namespace App\Http\Controllers;

use App\Models\VacationRequest;
use App\Models\VacationApproval;
use App\Services\VacationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VacationApprovalController extends Controller
{
    public function __construct(private VacationService $vacationService) {}

    /**
     * Approve vacation request
     */
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

        // Create approval record
        $approval = VacationApproval::create([
            'vacation_request_id' => $vacation->id,
            'approver_id' => $request->user()->id,
            'action' => 'approved',
            'comments' => $validated['comments'] ?? null,
        ]);

        // Update vacation request
        $vacation->update([
            'status' => 'approved',
            'approver_id' => $request->user()->id,
        ]);

        // Send notifications
        $this->vacationService->notifyRequester($vacation, 'approved');

        // Log audit
        $this->vacationService->logAudit($request->user(), 'vacation_approved', $vacation);

        return response()->json([
            'data' => $vacation->load('approver'),
            'message' => 'Vacation request approved successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Reject vacation request
     */
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

        // Create approval record
        $approval = VacationApproval::create([
            'vacation_request_id' => $vacation->id,
            'approver_id' => $request->user()->id,
            'action' => 'rejected',
            'comments' => $validated['reason'],
        ]);

        // Update vacation request
        $vacation->update([
            'status' => 'rejected',
            'approver_id' => $request->user()->id,
        ]);

        // Send notifications
        $this->vacationService->notifyRequester($vacation, 'rejected');

        // Log audit
        $this->vacationService->logAudit($request->user(), 'vacation_rejected', $vacation);

        return response()->json([
            'data' => $vacation->load('approver'),
            'message' => 'Vacation request rejected successfully',
            'status' => 'success',
        ]);
    }

    /**
     * Get pending approvals for current user
     */
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
