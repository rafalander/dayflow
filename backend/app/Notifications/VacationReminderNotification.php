<?php

namespace App\Notifications;

use App\Models\VacationRequest;
use Illuminate\Notifications\Notification;

class VacationReminderNotification extends Notification
{
    public function __construct(private VacationRequest $vacation) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray($notifiable): array
    {
        return [
            'vacation_id' => $this->vacation->id,
            'start_date' => $this->vacation->start_date->format('Y-m-d'),
            'end_date' => $this->vacation->end_date->format('Y-m-d'),
            'days_until' => $this->vacation->start_date->diffInDays(now()),
        ];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $days = $this->vacation->start_date->diffInDays(now());

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Vacation Reminder: {$days} days until your vacation")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your vacation starts in {$days} days ({$this->vacation->start_date->format('M d, Y')}).")
            ->line('Have a great time!')
            ->action('Ver detalhes', rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/').'/ausencias');
    }
}
