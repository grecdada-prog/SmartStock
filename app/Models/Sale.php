<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'seller_id',
        'subtotal',
        'tax',
        'discount',
        'total',
        'amount_received',
        'change_given',
        'customer_name',
        'customer_phone',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];

    // Relations
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function saleItems()
    {
        return $this->items();
    }

    // Helper methods
    public static function generateInvoiceNumber()
    {
        $date = now()->format('Ymd');
        $lastSale = self::whereDate('created_at', today())
            ->lockForUpdate()
            ->latest('id')
            ->first();

        if ($lastSale) {
            $lastNumber = intval(substr($lastSale->invoice_number, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return 'INV-' . $date . '-' . $newNumber;
    }

    public function calculateTotals()
    {
        $this->subtotal = $this->items->sum('subtotal');
        $this->total = $this->subtotal + $this->tax - $this->discount;
        $this->save();
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Espèces',
            'card' => 'Orange Money',
            'mobile_money' => 'MTN Momo',
            default => $this->payment_method ?? 'N/A',
        };
    }
}
