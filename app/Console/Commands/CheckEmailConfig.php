<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class CheckEmailConfig extends Command
{
    protected $signature = 'email:check-config';

    protected $description = 'Verifie la configuration email et affiche les parametres';

    public function handle()
    {
        $this->info('Verification de la configuration email...');
        $this->newLine();

        $mailer = Config::get('mail.default');
        $host = Config::get('mail.mailers.smtp.host');
        $port = Config::get('mail.mailers.smtp.port');
        $username = Config::get('mail.mailers.smtp.username');
        $password = Config::get('mail.mailers.smtp.password');
        $encryption = Config::get('mail.mailers.smtp.encryption');
        $fromAddress = Config::get('mail.from.address');
        $fromName = Config::get('mail.from.name');

        $this->table(
            ['Parametre', 'Valeur', 'Statut'],
            [
                ['MAIL_MAILER', $mailer, $this->checkMailer($mailer)],
                ['MAIL_HOST', $host, $this->checkHost($host)],
                ['MAIL_PORT', $port, $this->checkPort($port)],
                ['MAIL_USERNAME', $username, $this->checkUsername($username)],
                ['MAIL_PASSWORD', $this->maskPassword($password), $this->checkPassword($password)],
                ['MAIL_ENCRYPTION', $encryption, $this->checkEncryption($encryption)],
                ['MAIL_FROM_ADDRESS', $fromAddress, $this->checkFromAddress($fromAddress)],
                ['MAIL_FROM_NAME', $fromName, $fromName ? 'OK' : 'Manquant'],
            ]
        );

        $this->newLine();

        if ($mailer === 'log') {
            $this->warn('Mode LOG active - les emails seront seulement enregistres dans les logs.');
            $this->comment('Pour envoyer de vrais emails, configurez un serveur SMTP dans le fichier .env.');
        }

        $this->newLine();

        $queueConnection = Config::get('queue.default');
        $this->info("Queue configuration : {$queueConnection}");

        if ($queueConnection === 'database') {
            $this->comment('Assurez-vous que le worker tourne : php artisan queue:work');
        }

        $this->newLine();

        if ($this->confirm('Voulez-vous tester l\'envoi d\'un email maintenant ?', false)) {
            $this->call('email:test', ['type' => 'user-created']);
        }

        return self::SUCCESS;
    }

    private function checkMailer($mailer)
    {
        if ($mailer === 'smtp') {
            return 'OK SMTP';
        }

        if ($mailer === 'log') {
            return 'LOG (mode test)';
        }

        return 'A verifier';
    }

    private function checkHost($host)
    {
        if (empty($host) || $host === 'null') {
            return 'Manquant';
        }

        return 'OK ' . $host;
    }

    private function checkPort($port)
    {
        if ($port === 587 || $port === '587') {
            return 'OK TLS (587)';
        }

        if ($port === 465 || $port === '465') {
            return 'OK SSL (465)';
        }

        return 'A verifier ' . $port;
    }

    private function checkUsername($username)
    {
        if (empty($username) || $username === 'null') {
            return 'Manquant';
        }

        return 'OK';
    }

    private function checkPassword($password)
    {
        if (empty($password) || $password === 'null') {
            return 'Manquant';
        }

        if (strlen($password) < 10) {
            return 'Trop court';
        }

        return 'OK';
    }

    private function checkEncryption($encryption)
    {
        if ($encryption === 'tls') {
            return 'OK TLS';
        }

        if ($encryption === 'ssl') {
            return 'OK SSL';
        }

        return 'A verifier ' . $encryption;
    }

    private function checkFromAddress($address)
    {
        if (empty($address)) {
            return 'Manquant';
        }

        if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return 'OK';
        }

        return 'Email invalide';
    }

    private function maskPassword($password)
    {
        if (empty($password) || $password === 'null') {
            return 'Non configure';
        }

        if (strlen($password) > 20) {
            return substr($password, 0, 10) . '...' . substr($password, -5);
        }

        return str_repeat('*', min(strlen($password), 20));
    }
}
