<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxInvoice extends Model
{
    protected $fillable = [
        'type', 'tax_number', 'invoice_id', 'purchase_order_id',
        'partner_name', 'partner_npwp', 'date', 'dpp', 'ppn_rate', 'ppn',
        'notes', 'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'dpp' => 'decimal:2',
        'ppn_rate' => 'decimal:2',
        'ppn' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function scopeOutput($q)
    {
        return $q->where('type', 'output');
    }

    public function scopeInput($q)
    {
        return $q->where('type', 'input');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'output' ? 'Keluaran' : 'Masukan';
    }
}
