<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Get the sale that owns the sale detail
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product that owns the sale detail
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total price based on quantity and unit price
     */
    public function calculateTotalPrice(): float
    {
        return $this->quantity * $this->unit_price;
    }

    /**
     * Update total price automatically
     */
    protected static function booted()
    {
        static::saving(function ($saleDetail) {
            $saleDetail->total_price = $saleDetail->calculateTotalPrice();
        });
    }

    /**
     * Get profit from this sale detail
     */
    public function getProfitAttribute(): float
    {
        if (!$this->product) {
            return 0;
        }

        $costPrice = $this->product->purchase_price * $this->quantity;
        return $this->total_price - $costPrice;
    }

    /**
     * Get profit margin percentage
     */
    public function getProfitMarginAttribute(): float
    {
        if (!$this->product || $this->product->purchase_price <= 0) {
            return 0;
        }

        $costPrice = $this->product->purchase_price * $this->quantity;

        if ($costPrice <= 0) {
            return 0;
        }

        return (($this->total_price - $costPrice) / $costPrice) * 100;
    }
}
