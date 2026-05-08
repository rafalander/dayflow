<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\VacationRequest;
use Illuminate\Support\Collection;

/**
 * Próximas ausências aprovadas num horizonte configurável.
 *
 * Centraliza a regra de negócio para reutilização em HTTP, jobs (ex.: notificação Slack)
 * e outros pontos de integração.
 */
class UpcomingAbsencesService
{
    public const SETTING_KEY = 'dashboard_upcoming_absences_days';

    /**
     * Dias efetivos do horizonte (1–366), a partir da setting ou do default em config.
     */
    public function resolveHorizonDays(?int $overrideDays = null): int
    {
        if ($overrideDays !== null) {
            return max(1, min($overrideDays, 366));
        }

        $rawDays = Setting::where('key', self::SETTING_KEY)->value('value');
        $days = is_numeric($rawDays)
            ? (int) $rawDays
            : (int) config('dayflow.dashboard_upcoming_absences_days', 30);

        return max(1, min($days, 366));
    }

    /**
     * @return array{days: int, vacations: Collection<int, VacationRequest>}
     */
    public function upcomingApprovedVacations(?int $horizonDaysOverride = null): array
    {
        $days = $this->resolveHorizonDays($horizonDaysOverride);

        $today = now()->startOfDay();
        $until = (clone $today)->addDays($days)->endOfDay();

        $vacations = VacationRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '>=', $today->toDateString())
            ->whereDate('start_date', '<=', $until->toDateString())
            ->with(['user:id,name,email'])
            ->orderBy('start_date')
            ->orderBy('user_id')
            ->get();

        return [
            'days' => $days,
            'vacations' => $vacations,
        ];
    }
}
