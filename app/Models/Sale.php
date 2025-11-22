<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'sale_number',
        'total_amount',
        'discount',
        'tax',
        'final_amount',
        'payment_method',
        'payment_status',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'final_amount' => 'decimal:2',
    ];

    /**
     * Relation avec le vendeur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le client
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation avec les articles vendus
     */
    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Générer un numéro de vente unique
     */
    public static function generateSaleNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastSale = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastSale ? ((int) substr($lastSale->sale_number, -4)) + 1 : 1;

        return 'VEN-' . $year . $month . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method pour générer automatiquement le numéro
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (!$sale->sale_number) {
                $sale->sale_number = self::generateSaleNumber();
            }
        });
    }
}