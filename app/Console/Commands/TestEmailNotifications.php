<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Notifications\UserCreatedNotification;
use App\Notifications\Enable2FANotification;
use App\Notifications\PasswordResetNotification;
use App\Notifications\AccountStatusChangedNotification;
use App\Services\PasswordSetupLinkService;

class TestEmailNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {type?} {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test les notifications email du système';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $userId = $this->argument('user_id');

        // Si aucun utilisateur n'est spécifié, utiliser le premier super admin
        if (!$userId) {
            $user = User::role('super_admin')->first();
            if (!$user) {
                $this->error('Aucun super admin trouvé dans la base de données.');
                $this->info('Créez d\'abord un super admin ou spécifiez un user_id.');
                return 1;
            }
        } else {
            $user = User::find($userId);
            if (!$user) {
                $this->error("Utilisateur avec l'ID {$userId} introuvable.");
                return 1;
            }
        }

        $this->info("Test des notifications pour : {$user->name} ({$user->email})");
        $this->newLine();

        // Si aucun type n'est spécifié, afficher le menu
        if (!$type) {
            $type = $this->choice(
                'Quelle notification souhaitez-vous tester ?',
                [
                    '1' => 'UserCreatedNotification - Email de bienvenue',
                    '2' => 'Enable2FANotification - Invitation 2FA',
                    '3' => 'PasswordResetNotification - Réinitialisation mot de passe',
                    '4' => 'AccountStatusChangedNotification (Activé) - Compte activé',
                    '5' => 'AccountStatusChangedNotification (Désactivé) - Compte désactivé',
                    '6' => 'Toutes les notifications',
                ],
                '1'
            );
        }

        switch ($type) {
            case '1':
            case 'user-created':
                $this->testUserCreated($user);
                break;

            case '2':
            case 'enable-2fa':
                $this->testEnable2FA($user);
                break;

            case '3':
            case 'password-reset':
                $this->testPasswordReset($user);
                break;

            case '4':
            case 'status-activated':
                $this->testAccountStatusChanged($user, true);
                break;

            case '5':
            case 'status-deactivated':
                $this->testAccountStatusChanged($user, false);
                break;

            case '6':
            case 'all':
                $this->testUserCreated($user);
                $this->testEnable2FA($user);
                $this->testPasswordReset($user);
                $this->testAccountStatusChanged($user, true);
                $this->testAccountStatusChanged($user, false);
                break;

            default:
                $this->error('Type de notification invalide.');
                return 1;
        }

        $this->newLine();
        $this->info('✓ Notifications envoyées avec succès !');
        $this->newLine();
        $this->comment('Les emails sont en mode LOG. Consultez storage/logs/laravel.log pour voir le contenu.');
        $this->comment('Pour traiter la queue, exécutez : php artisan queue:work');

        return 0;
    }

    private function testUserCreated(User $user)
    {
        $this->line('→ Envoi de UserCreatedNotification...');
        $createdBy = User::role('super_admin')->first();
        $setupUrl = app(PasswordSetupLinkService::class)->createUrl($user);
        $user->notify(new UserCreatedNotification($setupUrl, $createdBy));
    }

    private function testEnable2FA(User $user)
    {
        $this->line('→ Envoi de Enable2FANotification...');
        $user->notify(new Enable2FANotification());
    }

    private function testPasswordReset(User $user)
    {
        $this->line('→ Envoi de PasswordResetNotification...');
        $resetUrl = app(PasswordSetupLinkService::class)->createUrl($user);
        $user->notify(new PasswordResetNotification($resetUrl));
    }

    private function testAccountStatusChanged(User $user, bool $isActive)
    {
        $status = $isActive ? 'activé' : 'désactivé';
        $this->line("→ Envoi de AccountStatusChangedNotification ({$status})...");
        $changedBy = 'Test Admin';
        $user->notify(new AccountStatusChangedNotification($isActive, $changedBy));
    }
}
