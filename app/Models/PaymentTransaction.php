<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory;

    public const TYPE_POS_SALE = 'pos_sale';
    public const TYPE_ENERGY_TOKEN = 'energy_token';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PAID_ACTION_REQUIRED = 'paid_action_required';

    protected $fillable = [
        'reference',
        'type',
        'status',
        'seller_id',
        'manager_id',
        'sale_id',
        'payment_method',
        'operator_code',
        'operator_label',
        'customer_phone',
        'amount',
        'operator_fee',
        'total_amount',
        'currency',
        'country',
        'monetbil_payment_id',
        'monetbil_transaction_uuid',
        'monetbil_status',
        'monetbil_message',
        'sale_payload',
        'request_payload',
        'response_payload',
        'callback_payload',
        'last_check_payload',
        'failure_reason',
        'expires_at',
        'confirmed_at',
        'failed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'operator_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'sale_payload' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
        'last_check_payload' => 'array',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'PAY-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
        } while (self::where('reference', $reference)->exists());

        return $reference;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
