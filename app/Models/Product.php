<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Product extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'name', 'category_id', 'unit_id',
        'cost_price', 'sell_price', 'stock', 'min_stock',
        'image', 'description', 'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
        'stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** Stok di bawah / sama dengan minimum. */
    public function getIsLowStockAttribute(): bool
    {
        return $this->min_stock > 0 && $this->stock <= $this->min_stock;
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'min_stock')->where('min_stock', '>', 0);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'category_id', 'unit_id', 'cost_price', 'sell_price', 'min_stock', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('produk');
    }
}
