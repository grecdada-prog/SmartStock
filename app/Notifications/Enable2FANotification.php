<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Enable2FANotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
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
            ->subject('Sécurisez votre compte avec l\'authentification à deux facteurs')
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('La sécurité de votre compte est notre priorité.')
            ->line('L\'authentification à deux facteurs (2FA) ajoute une couche de sécurité supplémentaire à votre compte en exigeant un code de vérification lors de la connexion.')
            ->line('**Avantages de l\'activation du 2FA :**')
            ->line('✓ Protection renforcée contre les accès non autorisés')
            ->line('✓ Sécurité même si votre mot de passe est compromis')
            ->line('✓ Conformité aux meilleures pratiques de sécurité')
            ->action('Activer le 2FA maintenant', url('/2fa/setup'))
            ->line('L\'activation ne prend que quelques minutes et utilise une application d\'authentification sur votre téléphone.')
            ->line('Merci de contribuer à la sécurité de ' . config('app.name') . ' !');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'message' => 'Invitation à activer l\'authentification à deux facteurs.',
        ];
    }
}
