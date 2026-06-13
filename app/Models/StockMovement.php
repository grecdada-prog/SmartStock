<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_CORRECTION_CANCELLATION = 'correction_cancellation';

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'purchase_price',
        'selling_price',
        'remaining_quantity',
        'batch_code',
        'is_perishable',
        'expiration_date',
        'reference',
        'reason',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'remaining_quantity' => 'integer',
        'is_perishable' => 'boolean',
        'expiration_date' => 'date',
    ];

    // Relations
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeIn($query)
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeOut($query)
    {
        return $query->where('type', self::TYPE_OUT);
    }

    public function scopeAdjustment($query)
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    public function scopeSellableBatches($query)
    {
        return $query
            ->whereIn('type', [self::TYPE_IN, self::TYPE_CORRECTION_CANCELLATION])
            ->where('remaining_quantity', '>', 0);
    }
}
