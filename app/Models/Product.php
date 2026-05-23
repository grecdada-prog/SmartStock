<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'description',
        'category_id',
        'purchase_price',
        'selling_price',
        'quantity',
        'alert_quantity',
        'unit',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'alert_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'alert_quantity');
    }

    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    // Helper methods
    public function isLowStock()
    {
        return $this->quantity <= $this->alert_quantity;
    }

    public function isOutOfStock()
    {
        return $this->quantity <= 0;
    }

    public function profit()
    {
        return $this->selling_price - $this->purchase_price;
    }

    public function profitMargin()
    {
        if ($this->purchase_price == 0) return 0;
        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }
}
