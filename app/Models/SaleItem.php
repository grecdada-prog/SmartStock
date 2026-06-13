<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'service_name',
        'service_payload',
        'promotion_id',
        'promotion_snapshot',
        'quantity',
        'unit_price',
        'original_unit_price',
        'subtotal',
        'discount_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'original_unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'service_payload' => 'array',
        'promotion_snapshot' => 'array',
    ];

    // Relations
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function promotion()
    {
        return $this->belongsTo(ProductPromotion::class, 'promotion_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->product?->name ?? $this->service_name ?? 'N/A';
    }

    public function getPromotionDetailsAttribute(): ?array
    {
        $snapshot = $this->promotion_snapshot;

        if (is_array($snapshot) && ! empty($snapshot)) {
            return [
                'id' => $snapshot['id'] ?? $this->promotion_id,
                'name' => $snapshot['name'] ?? $this->promotion?->name ?? 'Promotion appliquee',
                'promotion_price' => (float) ($snapshot['promotion_price'] ?? $this->unit_price),
                'min_quantity' => isset($snapshot['min_quantity']) ? (int) $snapshot['min_quantity'] : $this->promotion?->min_quantity,
                'original_unit_price' => isset($snapshot['original_unit_price']) ? (float) $snapshot['original_unit_price'] : ($this->original_unit_price !== null ? (float) $this->original_unit_price : null),
                'discount_amount' => (float) ($snapshot['discount_amount'] ?? $this->discount_amount ?? 0),
            ];
        }

        if (! $this->promotion_id && $this->original_unit_price === null && (float) ($this->discount_amount ?? 0) <= 0) {
            return null;
        }

        return [
            'id' => $this->promotion_id,
            'name' => $this->promotion?->name ?? 'Promotion appliquee',
            'promotion_price' => (float) $this->unit_price,
            'min_quantity' => $this->promotion?->min_quantity,
            'original_unit_price' => $this->original_unit_price !== null ? (float) $this->original_unit_price : null,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
        ];
    }

    // Events
    protected static function booted()
    {
        static::creating(function ($saleItem) {
            $saleItem->subtotal = $saleItem->quantity * $saleItem->unit_price;
        });

        static::updating(function ($saleItem) {
            $saleItem->subtotal = $saleItem->quantity * $saleItem->unit_price;
        });
    }
}
