<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'ip_address',
        'successful',
        'user_agent',
    ];

    /**
     * Compter les tentatives échouées dans les 15 dernières minutes
     */
    public static function countFailedAttempts(string $email): int
    {
        return self::where('email', $email)
            ->where('successful', false)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
    }

    /**
     * Enregistrer une tentative de connexion
     */
    public static function recordAttempt(string $email, bool $successful = false): void
    {
        self::create([
            'email' => $email,
            'ip_address' => request()->ip(),
            'successful' => $successful,
            'user_agent' => request()->userAgent(),
        ]);
    }
}