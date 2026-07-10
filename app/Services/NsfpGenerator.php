<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NsfpGenerator
{
    /**
     * Hasilkan Nomor Seri Faktur Pajak berikutnya secara berurutan & unik.
     * Memakai row-lock (lockForUpdate) pada baris counter agar tidak loncat/duplikat
     * meski ada dua proses generate bersamaan.
     *
     * Format: {kodeTransaksi}0.000-{YY}.{8 digit urut}  (mis. 040.000-26.00000001)
     */
    public function next(): string
    {
        $year = date('y');

        return DB::transaction(function () use ($year) {
            // Pastikan baris counter ada.
            DB::table('settings')->insertOrIgnore([
                ['key' => 'fp_last_serial', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
                ['key' => 'fp_serial_year', 'value' => $year, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Kunci baris counter.
            $serialRow = DB::table('settings')->where('key', 'fp_last_serial')->lockForUpdate()->first();
            $yearRow = DB::table('settings')->where('key', 'fp_serial_year')->lockForUpdate()->first();

            $serial = (int) $serialRow->value;

            // Reset urutan bila ganti tahun.
            if ($yearRow->value !== $year) {
                $serial = 0;
                DB::table('settings')->where('key', 'fp_serial_year')->update(['value' => $year, 'updated_at' => now()]);
            }

            $serial++;
            DB::table('settings')->where('key', 'fp_last_serial')->update(['value' => (string) $serial, 'updated_at' => now()]);
            Cache::forget('settings.all');

            $txCode = preg_replace('/\D/', '', (string) Setting::get('fp_transaction_code', '04')) ?: '04';
            $txCode = str_pad(substr($txCode, 0, 2), 2, '0', STR_PAD_LEFT);

            return sprintf('%s0.000-%s.%08d', $txCode, $year, $serial);
        });
    }
}
