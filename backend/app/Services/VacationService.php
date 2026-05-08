<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VacationRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VacationService
{
    /**
     * @return array{
     *     hasConflict: bool,
     *     reason: string,
     *     teamSize?: int,
     *     currentAbsent?: int,
     *     maxAbsent?: int
     * }
     */
    public function checkConflicts(int $userId, string $startDate, string $endDate): array
    {
        $user = User::findOrFail($userId);
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $teamMemberIds = $this->getTeamMemberIds($user);

        $userConflict = VacationRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->exists();

        if ($userConflict) {
            return [
                'hasConflict' => true,
                'reason' => 'User already has an approved vacation in this period',
            ];
        }

        $teamVacationCount = VacationRequest::whereIn('user_id', $teamMemberIds)
            ->where('status', 'approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->count();

        $teamSize = count($teamMemberIds);

        $maxAbsent = $this->getTeamVacationLimit($user);
        $totalAbsent = $teamVacationCount + 1;

        if ($totalAbsent > $maxAbsent) {
            return [
                'hasConflict' => true,
                'reason' => "Team vacation limit exceeded. Maximum {$maxAbsent} members can be absent, but {$totalAbsent} would be.",
                'teamSize' => $teamSize,
                'currentAbsent' => $teamVacationCount,
                'maxAbsent' => $maxAbsent,
            ];
        }

        return [
            'hasConflict' => false,
            'reason' => 'No conflicts found',
            'teamSize' => $teamSize,
            'currentAbsent' => $teamVacationCount,
            'maxAbsent' => $maxAbsent,
        ];
    }

    private function getTeamVacationLimit(User $user): int
    {
        $setting = \App\Models\Setting::where('key', 'max_team_vacation_percentage')->first();
        $percentage = $setting ? (int) $setting->value : 30;

        $teamSize = count($this->getTeamMemberIds($user));

        return max(1, (int) ceil($teamSize * $percentage / 100));
    }

    /**
     * @return list<int>
     */
    private function getTeamMemberIds(User $user): array
    {
        $ids = [$user->id];

        foreach ($user->subordinates as $subordinate) {
            $ids = array_merge($ids, $this->getTeamMemberIds($subordinate));
        }

        return $ids;
    }

    public function notifyApprover(VacationRequest $vacation): void
    {
        try {
            if (!$vacation->approver) {
                Log::warning("No approver found for vacation request {$vacation->id}");
                return;
            }

            $notification = new \App\Notifications\VacationRequestNotification($vacation, 'new');
            $vacation->approver->notify($notification);

            $this->logNotification($vacation->approver, 'vacation_request_received', $vacation);
        } catch (\Exception $e) {
            Log::error("Error notifying approver: {$e->getMessage()}");
        }
    }

    public function notifyRequester(VacationRequest $vacation, string $action): void
    {
        try {
            $notification = new \App\Notifications\VacationResponseNotification($vacation, $action);
            $vacation->user->notify($notification);

            $this->logNotification($vacation->user, "vacation_{$action}", $vacation);
        } catch (\Exception $e) {
            Log::error("Error notifying requester: {$e->getMessage()}");
        }
    }

    public function logAudit(User $actor, string $action, ?VacationRequest $vacation = null): void
    {
        try {
            AuditLog::create([
                'user_id' => $actor->id,
                'action' => $action,
                'model_type' => VacationRequest::class,
                'model_id' => $vacation?->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error logging audit: {$e->getMessage()}");
        }
    }

    private function logNotification(User $user, string $type, VacationRequest $vacation): void
    {
        try {
            \App\Models\NotificationLog::create([
                'user_id' => $user->id,
                'type' => $type,
                'model_type' => VacationRequest::class,
                'model_id' => $vacation->id,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error logging notification: {$e->getMessage()}");
        }
    }

    /**
     * @param list<array<string, mixed>> $blocks
     */
    public function sendSlackNotification(string $channel, string $message, array $blocks = []): void
    {
        try {
            $slackWebhook = \App\Models\Setting::where('key', 'slack_webhook_url')->first()?->value;

            if (!$slackWebhook) {
                Log::info("Slack webhook not configured, skipping notification");
                return;
            }

            Log::info("Slack notification prepared for channel: {$channel}");
        } catch (\Exception $e) {
            Log::error("Error sending Slack notification: {$e->getMessage()}");
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function checkUpcomingVacations(int $days = 7): array
    {
        $threshold = Carbon::now()->addDays($days);

        $upcomingVacations = VacationRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $threshold)
            ->whereDate('start_date', '>=', Carbon::now())
            ->with('user')
            ->get();

        foreach ($upcomingVacations as $vacation) {
            $notification = new \App\Notifications\VacationReminderNotification($vacation);
            $vacation->user->notify($notification);
        }

        return $upcomingVacations->toArray();
    }
}
