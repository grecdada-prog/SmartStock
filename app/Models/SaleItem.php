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
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
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