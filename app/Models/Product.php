<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_code',
        'name',
        'category_id',
        'purchase_price',
        'wholesale_price',
        'retail_price',
        'current_stock',
        'unit',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'current_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get sale details for this product
     */
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    /**
     * Get purchase details for this product
     */
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Generate unique product code
     */
    public static function generateProductCode(): string
    {
        $lastProduct = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastProduct ? intval(substr($lastProduct->product_code, -4)) + 1 : 1;

        return 'PRD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return $this->current_stock > 0;
    }

    /**
     * Check if product has sufficient stock for quantity
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->current_stock >= $quantity;
    }

    /**
     * Update stock after sale
     */
    public function reduceStock(int $quantity): bool
    {
        if (!$this->hasSufficientStock($quantity)) {
            return false;
        }

        $this->current_stock -= $quantity;
        return $this->save();
    }

    /**
     * Update stock after purchase
     */
    public function increaseStock(int $quantity): bool
    {
        $this->current_stock += $quantity;
        return $this->save();
    }

    /**
     * Get profit margin for wholesale
     */
    public function getWholesaleProfitMarginAttribute(): float
    {
        if ($this->purchase_price <= 0) {
            return 0;
        }

        return (($this->wholesale_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    /**
     * Get profit margin for retail
     */
    public function getRetailProfitMarginAttribute(): float
    {
        if ($this->purchase_price <= 0) {
            return 0;
        }

        return (($this->retail_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for products in stock
     */
    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    /**
     * Scope for low stock products
     */
    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('current_stock', '<=', $threshold)
                    ->where('current_stock', '>', 0);
    }

    /**
     * Scope for out of stock products
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', 0);
    }
}
