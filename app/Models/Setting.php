<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public $timestamps = true;

    /** Ambil nilai setting berdasarkan key (dengan cache). */
    public static function get(string $key, $default = null)
    {
        $all = Cache::rememberForever('settings.all', function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default;
    }

    /** Simpan / update nilai setting. */
    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    public static function putMany(array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }
}
