<?php

namespace App\Notifications;

use App\Models\Pointage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class OvertimeApprovalNotification extends Notification
{
    use Queueable;

    protected $pointage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Pointage $pointage)
    {
        $this->pointage = $pointage;
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
        $users = $this->pointage->users->pluck('name')->implode(', ');
        $project = $this->pointage->project->name;
        $date = $this->pointage->date->format('d/m/Y');
        $hours = $this->pointage->heures_supplementaires;

        return (new MailMessage)
            ->subject('Approbation d\'heures supplémentaires requise')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line("Des heures supplémentaires nécessitent votre approbation.")
            ->line("Projet: {$project}")
            ->line("Date: {$date}")
            ->line("Collaborateurs: {$users}")
            ->line("Heures supplémentaires: {$hours} heures")
            ->action('Approuver maintenant', url('/admin/pointages/' . $this->pointage->id . '/edit'))
            ->line('Merci de votre attention!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $users = $this->pointage->users->pluck('name')->implode(', ');
        
        return [
            'pointage_id' => $this->pointage->id,
            'project_id' => $this->pointage->project_id,
            'project_name' => $this->pointage->project->name,
            'date' => $this->pointage->date->format('Y-m-d'),
            'users' => $users,
            'hours' => $this->pointage->heures_supplementaires,
            'message' => "Heures supplémentaires à approuver pour {$users} sur le projet {$this->pointage->project->name}.",
        ];
    }
}
