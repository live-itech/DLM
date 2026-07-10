<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_number', 'supplier_id', 'user_id', 'date', 'status',
        'is_taxable', 'ppn_rate', 'subtotal', 'discount', 'dpp', 'ppn',
        'total', 'paid_amount', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_taxable' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'ordered' => 'Dipesan',
        'partial' => 'Diterima Sebagian',
        'received' => 'Diterima Penuh',
        'cancelled' => 'Batal',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getOutstandingAttribute(): float
    {
        return (float) $this->total - (float) $this->paid_amount;
    }

    public function getPaymentStatusAttribute(): string
    {
        if ($this->paid_amount <= 0) {
            return 'unpaid';
        }

        return $this->outstanding <= 0.009 ? 'paid' : 'partial';
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(fn ($i) => $i->qty_received >= $i->qty);
    }
}
