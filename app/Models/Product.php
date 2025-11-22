<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'barcode',
        'category_id',
        'purchase_price',
        'selling_price',
        'quantity',
        'min_quantity',
        'unit',
        'image',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relation avec la catégorie
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relation avec les ventes
     */
    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Vérifier si le stock est faible
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->min_quantity && $this->quantity > 0;
    }

    /**
     * Vérifier si le produit est en rupture
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Calculer la marge bénéficiaire
     */
    public function getMarginAttribute(): float
    {
        return $this->selling_price - $this->purchase_price;
    }

    /**
     * Calculer le pourcentage de marge
     */
    public function getMarginPercentageAttribute(): float
    {
        if ($this->purchase_price == 0) return 0;
        return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
    }
}