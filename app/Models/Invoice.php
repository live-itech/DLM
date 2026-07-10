<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'sales_order_id', 'user_id', 'date', 'due_date',
        'status', 'total', 'paid_amount', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public const STATUSES = [
        'unpaid' => 'Belum Dibayar',
        'partial' => 'Dibayar Sebagian',
        'paid' => 'Lunas',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getOutstandingAttribute(): float
    {
        return (float) $this->total - (float) $this->paid_amount;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date && $this->due_date->isPast();
    }

    /** Perbarui status & paid_amount dari akumulasi pembayaran. */
    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $paid;

        if ($paid <= 0) {
            $this->status = 'unpaid';
        } elseif ($paid + 0.009 >= (float) $this->total) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }

        $this->save();
    }
}
