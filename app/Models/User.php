<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'last_activity',
        'session_id',
        'google2fa_enabled',
        'google2fa_secret',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'google2fa_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'google2fa_enabled' => 'boolean',
        'last_activity' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'seller_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function activeSessions()
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManagers($query)
    {
        return $query->role('manager');
    }

    public function scopeSellers($query)
    {
        return $query->role('seller');
    }

    // Helper methods
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    public function isManager()
    {
        return $this->hasRole('manager');
    }

    public function isSeller()
    {
        return $this->hasRole('seller');
    }

    public function updateLastActivity()
    {
        $this->update(['last_activity' => now()]);
    }
}