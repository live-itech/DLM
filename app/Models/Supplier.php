<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Supplier extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'name', 'contact_person', 'phone', 'email',
        'address', 'npwp', 'payment_term_days', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'phone', 'npwp', 'payment_term_days', 'is_active'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('supplier');
    }
}
