<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use RuntimeException;

class StockService
{
    /**
     * Catat pergerakan stok & update saldo produk.
     * WAJIB dipanggil di dalam DB::transaction().
     *
     * @param  float  $signedQty  positif = masuk, negatif = keluar
     */
    public function move(
        Product $product,
        float $signedQty,
        string $type,
        string $referenceType,
        ?int $referenceId = null,
        ?string $note = null,
        bool $preventNegative = true,
    ): StockMovement {
        // Kunci baris produk agar aman dari race condition.
        $locked = Product::whereKey($product->getKey())->lockForUpdate()->first();

        $newBalance = (float) $locked->stock + $signedQty;

        if ($preventNegative && $newBalance < 0) {
            throw new RuntimeException(
                "Stok produk \"{$locked->name}\" tidak mencukupi (tersedia {$locked->stock})."
            );
        }

        $locked->stock = $newBalance;
        $locked->save();

        return StockMovement::create([
            'product_id' => $locked->id,
            'warehouse_id' => Warehouse::default()?->id,
            'type' => $type,
            'qty' => $signedQty,
            'balance_after' => $newBalance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'user_id' => auth()->id(),
            'moved_at' => now(),
        ]);
    }
}
