<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct($temporaryPassword)
    {
        $this->temporaryPassword = $temporaryPassword;
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
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre mot de passe a été réinitialisé par un administrateur.')
            ->line('**Nouveau mot de passe temporaire :**')
            ->line('**' . $this->temporaryPassword . '**')
            ->line('⚠️ Pour des raisons de sécurité, veuillez modifier ce mot de passe dès votre prochaine connexion.')
            ->action('Me connecter', url('/login'))
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, veuillez contacter immédiatement un administrateur.')
            ->salutation('Cordialement, L\'équipe ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre mot de passe a été réinitialisé.',
        ];
    }
}
