<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'logged_in_at',
        'logged_out_at',
        'is_active',
    ];

    protected $casts = [
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Créer une nouvelle session
     */
    public static function createSession(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'logged_in_at' => now(),
            'is_active' => true,
        ]);
    }

    /**
     * Fermer la session
     */
    public function closeSession(): void
    {
        $this->update([
            'logged_out_at' => now(),
            'is_active' => false,
        ]);
    }

    /**
     * Vérifier si l'utilisateur est actuellement connecté
     */
    public static function isUserActive(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Obtenir la session active d'un utilisateur
     */
    public static function getActiveSession(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();
    }
}