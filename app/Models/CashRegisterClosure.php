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
        'closed_at',
        'opened_at',
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
}
