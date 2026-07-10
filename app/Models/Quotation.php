<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_number', 'customer_id', 'user_id', 'date', 'valid_until',
        'status', 'is_taxable', 'ppn_rate', 'subtotal', 'discount', 'dpp',
        'ppn', 'total', 'terms', 'notes', 'converted_sales_order_id',
    ];

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'is_taxable' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'sent' => 'Terkirim',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
        'expired' => 'Kadaluarsa',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_sales_order_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent']);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->valid_until && $this->valid_until->isPast() && ! in_array($this->status, ['accepted', 'rejected']);
    }
}
