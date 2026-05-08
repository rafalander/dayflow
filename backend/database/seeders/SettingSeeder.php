<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\UpcomingAbsencesService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'max_team_vacation_percentage',
                'value' => '30',
                'description' => 'Maximum percentage of team members that can be absent simultaneously',
            ],
            [
                'key' => 'allowed_email_domains',
                'value' => '@uello.com.br',
                'description' => 'Comma-separated list of allowed email domains for login',
            ],
            [
                'key' => 'vacation_reminder_days',
                'value' => '7',
                'description' => 'Number of days before vacation to send reminder',
            ],
            [
                'key' => 'slack_webhook_url',
                'value' => '',
                'description' => 'Slack webhook URL for notifications (optional)',
            ],
            [
                'key' => 'slack_channel',
                'value' => '',
                'description' => 'Default Slack channel for notifications',
            ],
            [
                'key' => 'app_name',
                'value' => 'Dayflow',
                'description' => 'Application name',
            ],
            [
                'key' => 'organization_name',
                'value' => 'Uello',
                'description' => 'Organization name',
            ],
            [
                'key' => UpcomingAbsencesService::SETTING_KEY,
                'value' => '30',
                'description' => 'Horizonte (dias) para próximas ausências aprovadas (Configurações / integrações)',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
