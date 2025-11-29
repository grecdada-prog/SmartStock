<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CheckEmailConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la configuration email et affiche les paramètres';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Vérification de la configuration email...');
        $this->newLine();

        // Récupérer les configurations
        $mailer = Config::get('mail.default');
        $host = Config::get('mail.mailers.smtp.host');
        $port = Config::get('mail.mailers.smtp.port');
        $username = Config::get('mail.mailers.smtp.username');
        $password = Config::get('mail.mailers.smtp.password');
        $encryption = Config::get('mail.mailers.smtp.encryption');
        $fromAddress = Config::get('mail.from.address');
        $fromName = Config::get('mail.from.name');

        // Afficher les informations
        $this->table(
            ['Paramètre', 'Valeur', 'Statut'],
            [
                ['MAIL_MAILER', $mailer, $this->checkMailer($mailer)],
                ['MAIL_HOST', $host, $this->checkHost($host)],
                ['MAIL_PORT', $port, $this->checkPort($port)],
                ['MAIL_USERNAME', $username, $this->checkUsername($username)],
                ['MAIL_PASSWORD', $this->maskPassword($password), $this->checkPassword($password)],
                ['MAIL_ENCRYPTION', $encryption, $this->checkEncryption($encryption)],
                ['MAIL_FROM_ADDRESS', $fromAddress, $this->checkFromAddress($fromAddress)],
                ['MAIL_FROM_NAME', $fromName, $fromName ? '✓' : '✗'],
            ]
        );

        $this->newLine();

        // Vérifier si c'est SendGrid
        if ($this->isSendGrid($host)) {
            $this->info('✅ Configuration SendGrid détectée');
            $this->newLine();

            if ($username !== 'apikey') {
                $this->warn('⚠️  MAIL_USERNAME devrait être "apikey" pour SendGrid');
            }

            if (!$this->isValidSendGridKey($password)) {
                $this->error('❌ La clé API SendGrid semble invalide (devrait commencer par SG.)');
            } else {
                $this->info('✓ Format de clé API SendGrid valide');
            }

            $this->newLine();
            $this->comment('Pour vérifier votre sender sur SendGrid :');
            $this->comment('https://app.sendgrid.com/settings/sender_auth/senders');
        } elseif ($mailer === 'log') {
            $this->warn('⚠️  Mode LOG activé - Les emails ne seront pas envoyés, seulement loggés');
            $this->comment('Pour envoyer de vrais emails, configurez SendGrid (voir SENDGRID_SETUP.md)');
        }

        $this->newLine();

        // Vérifier la queue
        $queueConnection = Config::get('queue.default');
        $this->info("Queue configuration : {$queueConnection}");

        if ($queueConnection === 'database') {
            $this->comment('Assurez-vous que le worker tourne : php artisan queue:work');
        }

        $this->newLine();

        // Proposer un test
        if ($this->confirm('Voulez-vous tester l\'envoi d\'un email maintenant ?', false)) {
            $this->call('email:test', ['type' => 'user-created']);
        }

        return 0;
    }

    private function checkMailer($mailer)
    {
        if ($mailer === 'smtp') {
            return '✓ SMTP';
        } elseif ($mailer === 'log') {
            return '⚠️  LOG (mode test)';
        }
        return '✗';
    }

    private function checkHost($host)
    {
        if (empty($host)) {
            return '✗ Manquant';
        }
        if ($host === 'smtp.sendgrid.net') {
            return '✓ SendGrid';
        }
        return '✓ ' . $host;
    }

    private function checkPort($port)
    {
        if ($port === 587 || $port === '587') {
            return '✓ TLS (587)';
        } elseif ($port === 465 || $port === '465') {
            return '✓ SSL (465)';
        }
        return '⚠️  ' . $port;
    }

    private function checkUsername($username)
    {
        if (empty($username) || $username === 'null') {
            return '✗ Manquant';
        }
        return '✓';
    }

    private function checkPassword($password)
    {
        if (empty($password) || $password === 'null') {
            return '✗ Manquant';
        }
        if (strlen($password) < 10) {
            return '⚠️  Trop court';
        }
        return '✓';
    }

    private function checkEncryption($encryption)
    {
        if ($encryption === 'tls') {
            return '✓ TLS';
        } elseif ($encryption === 'ssl') {
            return '✓ SSL';
        }
        return '⚠️  ' . $encryption;
    }

    private function checkFromAddress($address)
    {
        if (empty($address)) {
            return '✗ Manquant';
        }
        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return '✓';
        }
        return '✗ Email invalide';
    }

    private function maskPassword($password)
    {
        if (empty($password) || $password === 'null') {
            return 'Non configuré';
        }

        if (strlen($password) > 20) {
            return substr($password, 0, 10) . '...' . substr($password, -5);
        }

        return str_repeat('*', min(strlen($password), 20));
    }

    private function isSendGrid($host)
    {
        return $host === 'smtp.sendgrid.net';
    }

    private function isValidSendGridKey($key)
    {
        return !empty($key) && str_starts_with($key, 'SG.');
    }
}
