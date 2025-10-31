<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Purchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'purchase_number',
        'supplier_name',
        'user_id',
        'total_amount',
        'discount',
        'final_amount',
        'paid_amount',
        'payment_status',
        'purchase_date',
        'due_date',
        'status',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'purchase_date' => 'date',
        'due_date' => 'date',
    ];

    /**
     * Get the user that created the purchase
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get purchase details for this purchase
     */
    public function purchaseDetails()
    {
        return $this->hasMany(PurchaseDetail::class);
    }

    /**
     * Generate unique purchase number
     */
    public static function generatePurchaseNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $lastPurchase = self::whereDate('created_at', Carbon::today())
                           ->where('purchase_number', 'like', "PUR-{$today}-%")
                           ->orderBy('id', 'desc')
                           ->first();

        if ($lastPurchase) {
            $lastNumber = intval(substr($lastPurchase->purchase_number, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "PUR-{$today}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Check if purchase is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if purchase is credit
     */
    public function isCredit(): bool
    {
        return $this->payment_status === 'credit';
    }

    /**
     * Check if purchase is overdue
     */
    public function isOverdue(): bool
    {
        return $this->isCredit() && $this->due_date && Carbon::now()->gt($this->due_date);
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->final_amount - $this->paid_amount);
    }

    /**
     * Calculate total items in purchase
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->purchaseDetails()->sum('quantity');
    }

    /**
     * Mark purchase as paid
     */
    public function markAsPaid(float $paidAmount): bool
    {
        $this->paid_amount = $paidAmount;
        $this->payment_status = 'paid';

        return $this->save();
    }

    /**
     * Process purchase and update stock
     */
    public function processPurchase(): bool
    {
        foreach ($this->purchaseDetails as $detail) {
            $detail->product->increaseStock($detail->quantity);
        }

        return true;
    }

    /**
     * Cancel purchase and reduce stock
     */
    public function cancelPurchase(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        foreach ($this->purchaseDetails as $detail) {
            if (!$detail->product->hasSufficientStock($detail->quantity)) {
                return false; // Cannot cancel if it would result in negative stock
            }
        }

        foreach ($this->purchaseDetails as $detail) {
            $detail->product->reduceStock($detail->quantity);
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Scope for today's purchases
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for this month's purchases
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope for paid purchases
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for credit purchases
     */
    public function scopeCredit($query)
    {
        return $query->where('payment_status', 'credit');
    }

    /**
     * Scope for active purchases
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for overdue purchases
     */
    public function scopeOverdue($query)
    {
        return $query->credit()
                    ->where('due_date', '<', Carbon::now())
                    ->active();
    }
}
