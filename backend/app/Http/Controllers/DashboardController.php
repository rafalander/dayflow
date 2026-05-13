<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VacationRequest;
use App\Support\ApiQueryCacheGens;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function monthlyOverview(): JsonResponse
    {
        $today = now();
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();
        $monthNumber = $today->month;
        $monthKey = $today->format('Y-m');
        $ttl = (int) config('dayflow.api_read_cache.dashboard_overview', 180);
        $userGen = ApiQueryCacheGens::userDirectory();
        $vacationGen = ApiQueryCacheGens::vacation();

        $data = Cache::remember(
            "api.dashboard.overview.{$monthKey}.u{$userGen}.v{$vacationGen}",
            $ttl,
            function () use ($monthNumber, $monthStart, $monthEnd, $monthKey) {
                $birthdays = User::query()
                    ->active()
                    ->whereNotNull('birth_date')
                    ->whereMonth('birth_date', $monthNumber)
                    ->orderByRaw('DAY(birth_date)')
                    ->orderBy('name')
                    ->get(['id', 'name', 'birth_date'])
                    ->map(fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'birth_date' => $user->birth_date?->format('Y-m-d'),
                        'day' => (int) $user->birth_date?->format('d'),
                    ])
                    ->values();

                $possibleAbsencesCount = VacationRequest::query()
                    ->whereIn('status', ['approved', 'pending'])
                    ->forPeriod($monthStart, $monthEnd)
                    ->whereHas('user', fn ($query) => $query->active())
                    ->count();

                return [
                    'month' => $monthKey,
                    'birthdays' => $birthdays,
                    'possible_absences_count' => $possibleAbsencesCount,
                ];
            }
        );

        return response()->json([
            'data' => $data,
            'status' => 'success',
        ]);
    }
}
