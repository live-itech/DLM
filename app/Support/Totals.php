<?php

namespace App\Support;

class Totals
{
    /**
     * Hitung ringkasan nilai transaksi.
     *
     * @param  float  $itemsSubtotal  jumlah subtotal semua baris item
     * @param  float  $headerDiscount diskon tambahan tingkat dokumen
     * @param  bool   $isTaxable      apakah kena PPN (opsional)
     * @param  float  $ppnRate        tarif PPN dalam persen (mis. 11)
     * @return array{subtotal: float, discount: float, dpp: float, ppn: float, total: float}
     */
    public static function compute(float $itemsSubtotal, float $headerDiscount, bool $isTaxable, float $ppnRate): array
    {
        $subtotal = round($itemsSubtotal, 2);
        $discount = round(min($headerDiscount, $subtotal), 2);
        $dpp = round($subtotal - $discount, 2);
        $ppn = $isTaxable ? round($dpp * $ppnRate / 100, 2) : 0.0;
        $total = round($dpp + $ppn, 2);

        return compact('subtotal', 'discount', 'dpp', 'ppn', 'total');
    }
}
