<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'address', 'is_default', 'is_active'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** Gudang default (single-gudang). */
    public static function default(): self
    {
        return static::query()->where('is_default', true)->firstOr(function () {
            return static::query()->first();
        });
    }
}
