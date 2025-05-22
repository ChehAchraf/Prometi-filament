<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbsenceAlertNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $date;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, $date)
    {
        $this->user = $user;
        $this->date = $date;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerte d\'absence non justifiée')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line("Une absence non justifiée a été détectée.")
            ->line("Collaborateur: {$this->user->name}")
            ->line("Date: {$this->date->format('d/m/Y')}")
            ->action('Voir les détails', url('/admin/users/' . $this->user->id . '/edit'))
            ->line('Merci de votre attention!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'date' => $this->date->format('Y-m-d'),
            'message' => "Absence non justifiée détectée pour {$this->user->name} le {$this->date->format('d/m/Y')}.",
        ];
    }
}
