<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegisterClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'business_date',
        'amount',
        'closed_by',
        'closed_by_user_id',
        'closed_at',
        'opened_at',
        'opened_by',
        'opened_by_user_id',
    ];

    protected $casts = [
        'business_date' => 'date',
        'amount' => 'decimal:2',
        'closed_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function openedByUser()
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }
}
