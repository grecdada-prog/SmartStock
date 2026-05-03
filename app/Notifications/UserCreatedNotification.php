<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?string $setupUrl = null,
        protected ?User $createdBy = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $roleName = $notifiable->getRoleNames()->first();
        $roleDisplay = match ($roleName) {
            'super_admin' => 'Super Administrateur',
            'manager' => 'Gerant',
            'seller' => 'Vendeur',
            default => 'Utilisateur',
        };

        $message = (new MailMessage)
            ->subject('Bienvenue sur '.config('app.name'))
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre compte '.$roleDisplay.' a ete cree avec succes sur '.config('app.name').'.')
            ->line('Informations de connexion :')
            ->line('Email : '.$notifiable->email);

        if ($this->setupUrl) {
            $message->line('Pour des raisons de securite, aucun mot de passe ne vous est envoye par email.')
                ->line('Utilisez le lien ci-dessous pour definir votre mot de passe. Ce lien est temporaire.')
                ->action('Definir mon mot de passe', $this->setupUrl);
        }

        if ($this->createdBy) {
            $message->line('Votre compte a ete cree par : '.$this->createdBy->name);
        }

        return $message->line('Merci d utiliser '.config('app.name').' !');
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre compte a ete cree avec succes.',
            'role' => $notifiable->getRoleNames()->first(),
        ];
    }
}
