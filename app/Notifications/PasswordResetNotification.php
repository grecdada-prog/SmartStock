<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected string $resetUrl)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reinitialisation de votre mot de passe')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Une reinitialisation de mot de passe a ete demandee par un administrateur.')
            ->line('Pour des raisons de securite, aucun mot de passe temporaire ne vous est envoye par email.')
            ->line('Utilisez le lien ci-dessous pour choisir un nouveau mot de passe. Ce lien est temporaire.')
            ->action('Choisir un nouveau mot de passe', $this->resetUrl)
            ->line('Si vous n etes pas a l origine de cette demande, veuillez contacter immediatement un administrateur.')
            ->salutation('Cordialement, l equipe '.config('app.name'));
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Un lien de reinitialisation de mot de passe a ete envoye.',
        ];
    }
}
