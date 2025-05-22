<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PointageReminderNotification extends Notification
{
    use Queueable;

    protected $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $type)
    {
        $this->type = $type; // 'arrival' or 'departure'
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
        $subject = $this->type === 'arrival' 
            ? 'Rappel: N\'oubliez pas de pointer votre arrivée' 
            : 'Rappel: N\'oubliez pas de pointer votre départ';
            
        $message = $this->type === 'arrival'
            ? 'N\'oubliez pas de pointer votre arrivée sur le chantier aujourd\'hui.'
            : 'N\'oubliez pas de pointer votre départ avant de quitter le chantier.';
            
        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line($message)
            ->action('Pointer maintenant', url('/admin/pointages/create'))
            ->line('Merci de votre collaboration!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = $this->type === 'arrival'
            ? 'N\'oubliez pas de pointer votre arrivée sur le chantier aujourd\'hui.'
            : 'N\'oubliez pas de pointer votre départ avant de quitter le chantier.';
            
        return [
            'type' => $this->type,
            'message' => $message,
        ];
    }
}
