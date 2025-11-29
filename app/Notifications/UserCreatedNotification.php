<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\User;

class UserCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $temporaryPassword;
    protected $createdBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($temporaryPassword = null, User $createdBy = null)
    {
        $this->temporaryPassword = $temporaryPassword;
        $this->createdBy = $createdBy;
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
        $roleName = $notifiable->getRoleNames()->first();
        $roleDisplay = match($roleName) {
            'super_admin' => 'Super Administrateur',
            'manager' => 'Gérant',
            'seller' => 'Vendeur',
            default => 'Utilisateur'
        };

        $message = (new MailMessage)
            ->subject('Bienvenue sur ' . config('app.name'))
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('Votre compte ' . $roleDisplay . ' a été créé avec succès sur ' . config('app.name') . '.')
            ->line('**Informations de connexion :**')
            ->line('Email : ' . $notifiable->email);

        if ($this->temporaryPassword) {
            $message->line('Mot de passe temporaire : **' . $this->temporaryPassword . '**')
                ->line('⚠️ Pour des raisons de sécurité, nous vous recommandons fortement de modifier votre mot de passe dès votre première connexion.')
                ->action('Modifier mon mot de passe', url('/profile'));
        }

        if ($this->createdBy) {
            $message->line('Votre compte a été créé par : ' . $this->createdBy->name);
        }

        $message->line('Merci d\'utiliser ' . config('app.name') . ' !');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Votre compte a été créé avec succès.',
            'role' => $notifiable->getRoleNames()->first(),
        ];
    }
}
