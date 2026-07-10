<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'product_id', 'qty', 'qty_received',
        'cost_price', 'discount', 'subtotal',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'qty_received' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getOutstandingQtyAttribute(): float
    {
        return max(0, (float) $this->qty - (float) $this->qty_received);
    }
}
