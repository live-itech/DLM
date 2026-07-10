<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SalesOrder extends Model
{
    protected $fillable = [
        'so_number', 'customer_id', 'user_id', 'date', 'status',
        'is_taxable', 'ppn_rate', 'subtotal', 'discount', 'dpp', 'ppn',
        'total', 'stock_deducted', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_taxable' => 'boolean',
        'stock_deducted' => 'boolean',
        'ppn_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'dpp' => 'decimal:2',
        'ppn' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public const STATUSES = [
        'draft' => 'Draft',
        'confirmed' => 'Dikonfirmasi',
        'shipped' => 'Dikirim',
        'completed' => 'Selesai',
        'cancelled' => 'Batal',
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
        return $this->hasMany(SalesOrderItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['draft', 'confirmed']) && ! $this->invoice()->exists();
    }
}
