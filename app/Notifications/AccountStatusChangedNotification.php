<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $isActive;
    protected $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($isActive, $changedBy = null)
    {
        $this->isActive = $isActive;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $status = $this->isActive ? 'activé' : 'désactivé';
        $statusColor = $this->isActive ? 'success' : 'error';

        $message = (new MailMessage)
            ->subject('Statut de votre compte modifié')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre compte a été **' . $status . '**.');

        if ($this->isActive) {
            $message->line('Vous pouvez maintenant vous connecter et accéder à toutes les fonctionnalités de votre compte.')
                ->action('Me connecter', url('/login'));
        } else {
            $message->line('Vous ne pouvez plus accéder à votre compte pour le moment.')
                ->line('Si vous pensez qu\'il s\'agit d\'une erreur, veuillez contacter votre administrateur.');
        }

        if ($this->changedBy) {
            $message->line('Cette modification a été effectuée par : ' . $this->changedBy);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Le statut de votre compte a été modifié.',
            'is_active' => $this->isActive,
        ];
    }
}
