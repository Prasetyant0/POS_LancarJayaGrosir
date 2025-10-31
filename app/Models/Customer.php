<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'credit_limit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Get sales for this customer
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Generate unique customer code
     */
    public static function generateCustomerCode(): string
    {
        $lastCustomer = self::orderBy('id', 'desc')->first();
        $nextNumber = $lastCustomer ? intval(substr($lastCustomer->customer_code, -4)) + 1 : 1;

        return 'CUST-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get total credit used by customer
     */
    public function getTotalCreditUsedAttribute(): float
    {
        return $this->sales()
            ->where('payment_status', 'credit')
            ->where('status', 'active')
            ->sum('final_amount');
    }

    /**
     * Get remaining credit limit
     */
    public function getRemainingCreditAttribute(): float
    {
        return $this->credit_limit - $this->total_credit_used;
    }

    /**
     * Check if customer can make credit purchase
     */
    public function canMakeCreditPurchase(float $amount): bool
    {
        return ($this->total_credit_used + $amount) <= $this->credit_limit;
    }
}
