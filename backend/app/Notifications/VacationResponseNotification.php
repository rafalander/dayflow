<?php

namespace App\Notifications;

use App\Models\VacationRequest;
use Illuminate\Notifications\Notification;

class VacationResponseNotification extends Notification
{
    public function __construct(private VacationRequest $vacation, private string $action) {}

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
     *     action: string,
     *     start_date: string,
     *     end_date: string,
     *     approver_name: string
     * }
     */
    public function toArray($notifiable): array
    {
        return [
            'vacation_id' => $this->vacation->id,
            'action' => $this->action,
            'start_date' => $this->vacation->start_date->format('Y-m-d'),
            'end_date' => $this->vacation->end_date->format('Y-m-d'),
            'approver_name' => $this->vacation->approver->name,
        ];
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        $status = ucfirst($this->action);
        
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Your Vacation Request has been {$status}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your vacation request for {$this->vacation->start_date->format('M d, Y')} to {$this->vacation->end_date->format('M d, Y')} has been {$this->action}.")
            ->action('Ver detalhes', rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/').'/ausencias')
            ->line('Thank you for using Dayflow!');
    }
}
