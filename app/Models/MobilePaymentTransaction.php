<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobilePaymentTransaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'seller_id',
        'sale_id',
        'provider',
        'payment_ref',
        'payment_id',
        'status',
        'operator',
        'phone',
        'amount',
        'customer_name',
        'cart_payload',
        'provider_payload',
        'failure_reason',
        'confirmed_at',
        'failed_at',
        'canceled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cart_payload' => 'array',
        'provider_payload' => 'array',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACCEPTED], true);
    }
}
