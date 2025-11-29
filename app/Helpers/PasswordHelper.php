<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class PasswordHelper
{
    /**
     * Génère un mot de passe temporaire sécurisé
     *
     * @param int $length Longueur du mot de passe (minimum 12)
     * @return string Mot de passe généré
     */
    public static function generateTemporaryPassword(int $length = 12): string
    {
        // Utiliser la méthode native de Laravel pour générer un mot de passe sécurisé
        // qui respecte les critères : lettres, chiffres, symboles, majuscules/minuscules
        return Str::password(
            length: max($length, 12), // Minimum 12 caractères
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false
        );
    }

    /**
     * Génère un mot de passe temporaire avec un format personnalisé plus lisible
     * Format: XXXX-xxxx-9999-@#$% (4 majuscules, 4 minuscules, 4 chiffres, 4 symboles)
     *
     * @return string Mot de passe généré
     */
    public static function generateReadablePassword(): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ'; // Sans I, O pour éviter confusion
        $lowercase = 'abcdefghjkmnpqrstuvwxyz'; // Sans i, l, o pour éviter confusion
        $numbers = '23456789'; // Sans 0, 1 pour éviter confusion
        $symbols = '@#$%&*!?';

        $password = '';

        // 4 lettres majuscules
        for ($i = 0; $i < 4; $i++) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        }

        $password .= '-';

        // 4 lettres minuscules
        for ($i = 0; $i < 4; $i++) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        }

        $password .= '-';

        // 4 chiffres
        for ($i = 0; $i < 4; $i++) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }

        $password .= '-';

        // 4 symboles
        for ($i = 0; $i < 4; $i++) {
            $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        }

        return $password;
    }

    /**
     * Valide qu'un mot de passe respecte les critères de sécurité
     *
     * @param string $password
     * @return bool
     */
    public static function isSecure(string $password): bool
    {
        // Minimum 8 caractères
        if (strlen($password) < 8) {
            return false;
        }

        // Au moins une lettre majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Au moins une lettre minuscule
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Au moins un caractère spécial
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }
}
