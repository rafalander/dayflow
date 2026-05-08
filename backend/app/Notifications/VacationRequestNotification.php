<?php

namespace App\Notifications;

use App\Models\VacationRequest;
use Illuminate\Notifications\Notification;

class VacationRequestNotification extends Notification
{
    public function __construct(private VacationRequest $vacation, private string $type) {}

    /**
     * @return list<string>
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array{
     *     vacation_id: int,
     *     user_name: string,
     *     start_date: string,
     *     end_date: string,
     *     reason: string|null,
     *     type: string
     * }
     */
    public function toArray($notifiable): array
    {
        return [
            'vacation_id' => $this->vacation->id,
            'user_name' => $this->vacation->user->name,
            'start_date' => $this->vacation->start_date->format('Y-m-d'),
            'end_date' => $this->vacation->end_date->format('Y-m-d'),
            'reason' => $this->vacation->reason,
            'type' => $this->type,
        ];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("New Vacation Request from {$this->vacation->user->name}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->vacation->user->name} has requested vacation from {$this->vacation->start_date->format('M d, Y')} to {$this->vacation->end_date->format('M d, Y')}.")
            ->action('Revisar solicitação', rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/').'/approvals')
            ->line('Thank you for using Dayflow!');
    }
}
