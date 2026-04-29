<?php

namespace App\Services;

use App\Models\VacationRequest;
use App\Models\User;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VacationService
{
    /**
     * Check for vacation conflicts
     */
    public function checkConflicts(int $userId, string $startDate, string $endDate): array
    {
        $user = User::findOrFail($userId);
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        // Get user's team
        $teamMemberIds = $this->getTeamMemberIds($user);

        // Check if user has overlapping vacation
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

        // Get team vacation count for the period
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

        // Get team size
        $teamSize = count($teamMemberIds);

        // Check if team vacation limit is exceeded
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

    /**
     * Get team vacation limit based on settings
     */
    private function getTeamVacationLimit(User $user): int
    {
        // Get setting for vacation percentage
        $setting = \App\Models\Setting::where('key', 'max_team_vacation_percentage')->first();
        $percentage = $setting ? (int)$setting->value : 30;

        $teamSize = count($this->getTeamMemberIds($user));

        return max(1, (int)ceil($teamSize * $percentage / 100));
    }

    /**
     * Get all team members (subordinates) of a user recursively
     */
    private function getTeamMemberIds(User $user): array
    {
        $ids = [$user->id];

        foreach ($user->subordinates as $subordinate) {
            $ids = array_merge($ids, $this->getTeamMemberIds($subordinate));
        }

        return $ids;
    }

    /**
     * Notify approver of new vacation request
     */
    public function notifyApprover(VacationRequest $vacation): void
    {
        try {
            if (!$vacation->approver) {
                Log::warning("No approver found for vacation request {$vacation->id}");
                return;
            }

            $notification = new \App\Notifications\VacationRequestNotification($vacation, 'new');
            $vacation->approver->notify($notification);

            // Log notification
            $this->logNotification($vacation->approver, 'vacation_request_received', $vacation);
        } catch (\Exception $e) {
            Log::error("Error notifying approver: {$e->getMessage()}");
        }
    }

    /**
     * Notify requester of vacation approval/rejection
     */
    public function notifyRequester(VacationRequest $vacation, string $action): void
    {
        try {
            $notification = new \App\Notifications\VacationResponseNotification($vacation, $action);
            $vacation->user->notify($notification);

            // Log notification
            $this->logNotification($vacation->user, "vacation_{$action}", $vacation);
        } catch (\Exception $e) {
            Log::error("Error notifying requester: {$e->getMessage()}");
        }
    }

    /**
     * Log audit action
     */
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

    /**
     * Log notification
     */
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
     * Send Slack notification (endpoint prepared for future configuration)
     */
    public function sendSlackNotification(string $channel, string $message, array $blocks = []): void
    {
        try {
            $slackWebhook = \App\Models\Setting::where('key', 'slack_webhook_url')->first()?->value;

            if (!$slackWebhook) {
                Log::info("Slack webhook not configured, skipping notification");
                return;
            }

            // TODO: Implement actual Slack notification via webhook
            Log::info("Slack notification prepared for channel: {$channel}");
        } catch (\Exception $e) {
            Log::error("Error sending Slack notification: {$e->getMessage()}");
        }
    }

    /**
     * Check if vacation is approaching (within X days)
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
            // Send reminder notification
            $notification = new \App\Notifications\VacationReminderNotification($vacation);
            $vacation->user->notify($notification);
        }

        return $upcomingVacations->toArray();
    }
}
