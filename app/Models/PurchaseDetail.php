<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_id',
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
     * Get the purchase that owns the purchase detail
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the product that owns the purchase detail
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
        static::saving(function ($purchaseDetail) {
            $purchaseDetail->total_price = $purchaseDetail->calculateTotalPrice();
        });
    }

    /**
     * Update product purchase price if this is more recent
     */
    public function updateProductPurchasePrice(): bool
    {
        if (!$this->product) {
            return false;
        }

        // Update product purchase price with the latest purchase price
        $this->product->purchase_price = $this->unit_price;
        return $this->product->save();
    }
}
