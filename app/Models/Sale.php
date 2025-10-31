<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Sale extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'total_amount',
        'discount',
        'final_amount',
        'paid_amount',
        'change_amount',
        'payment_status',
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
        'change_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    /**
     * Get the customer that owns the sale
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user that created the sale
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get sale details for this sale
     */
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

    /**
     * Generate unique invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $today = Carbon::now()->format('Ymd');
        $lastSale = self::whereDate('created_at', Carbon::today())
                       ->where('invoice_number', 'like', "INV-{$today}-%")
                       ->orderBy('id', 'desc')
                       ->first();

        if ($lastSale) {
            $lastNumber = intval(substr($lastSale->invoice_number, -3));
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "INV-{$today}-" . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Check if sale is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if sale is credit
     */
    public function isCredit(): bool
    {
        return $this->payment_status === 'credit';
    }

    /**
     * Check if sale is overdue
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
     * Calculate total items in sale
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->saleDetails()->sum('quantity');
    }

    /**
     * Mark sale as paid
     */
    public function markAsPaid(float $paidAmount): bool
    {
        $this->paid_amount = $paidAmount;
        $this->change_amount = max(0, $paidAmount - $this->final_amount);
        $this->payment_status = 'paid';

        return $this->save();
    }

    /**
     * Process sale and update stock
     */
    public function processSale(): bool
    {
        foreach ($this->saleDetails as $detail) {
            if (!$detail->product->reduceStock($detail->quantity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cancel sale and restore stock
     */
    public function cancelSale(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        foreach ($this->saleDetails as $detail) {
            $detail->product->increaseStock($detail->quantity);
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Scope for today's sales
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for this month's sales
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope for paid sales
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope for credit sales
     */
    public function scopeCredit($query)
    {
        return $query->where('payment_status', 'credit');
    }

    /**
     * Scope for active sales
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for overdue sales
     */
    public function scopeOverdue($query)
    {
        return $query->credit()
                    ->where('due_date', '<', Carbon::now())
                    ->active();
    }
}
