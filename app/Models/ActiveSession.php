<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper methods
    public function isExpired($minutes = 120)
    {
        return $this->last_activity->addMinutes($minutes)->isPast();
    }
}