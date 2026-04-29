<?php

namespace App\Http\Controllers;

use App\Models\VacationRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Get vacation report
     */
    public function vacations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'manager_id' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:users,id',
        ]);

        $query = $this->approvedVacationsInRangeQuery($validated, $request);

        $vacations = $query->get();

        $report = $vacations->groupBy('user_id')->map(function ($userVacations) {
            return [
                'user' => $userVacations->first()->user,
                'vacations' => $userVacations,
                'total_days' => $userVacations->sum(function ($v) {
                    return $v->start_date->diffInDays($v->end_date) + 1;
                }),
            ];
        });

        return response()->json([
            'data' => $report,
            'status' => 'success',
        ]);
    }

    /**
     * Export report as CSV (útil para testes / Excel).
     */
    public function exportExcel(Request $request): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'manager_id' => 'nullable|exists:users,id',
            'team_id' => 'nullable|exists:users,id',
        ]);

        $query = $this->approvedVacationsInRangeQuery($validated, $request);

        $vacations = $query->orderBy('start_date')->get();

        $filename = 'relatorio-ferias-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($vacations) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nome', 'E-mail', 'Início', 'Fim', 'Status', 'Dias']);

            foreach ($vacations as $v) {
                $days = $v->start_date->diffInDays($v->end_date) + 1;
                fputcsv($out, [
                    $v->user?->name ?? '',
                    $v->user?->email ?? '',
                    $v->start_date->format('Y-m-d'),
                    $v->end_date->format('Y-m-d'),
                    $v->status,
                    $days,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export report as PDF
     */
    public function exportPdf(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'PDF export not yet implemented',
            'status' => 'pending',
        ]);
    }

    /**
     * Get audit logs
     */
    public function auditLogs(Request $request): JsonResponse
    {
        $logs = \App\Models\AuditLog::orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => $logs,
            'status' => 'success',
        ]);
    }

    /**
     * @param  array{start_date: string, end_date: string, manager_id?: int, team_id?: int}  $validated
     */
    private function approvedVacationsInRangeQuery(array $validated, Request $request): Builder
    {
        $query = VacationRequest::query()
            ->where('status', 'approved')
            ->where(function ($q) use ($validated) {
                $q->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                    ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']]);
            })
            ->with('user');

        if ($request->filled('team_id')) {
            $team = User::findOrFail($request->team_id);
            $subordinateIds = $this->getSubordinateIds($team);
            $query->whereIn('user_id', $subordinateIds);
        }

        if ($request->filled('manager_id')) {
            $manager = User::findOrFail($request->manager_id);
            $subordinateIds = $this->getSubordinateIds($manager);
            $query->whereIn('user_id', $subordinateIds);
        }

        return $query;
    }

    private function getSubordinateIds(User $user): array
    {
        $ids = [$user->id];

        foreach ($user->subordinates as $subordinate) {
            $ids = array_merge($ids, $this->getSubordinateIds($subordinate));
        }

        return $ids;
    }
}
