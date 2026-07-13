<?php

use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
        });

        // PO lama: isi jatuh tempo dari termin supplier supaya laporan hutang tetap konsisten.
        PurchaseOrder::with('supplier')->whereNull('due_date')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $order->updateQuietly([
                    'due_date' => $order->date?->copy()->addDays((int) ($order->supplier->payment_term_days ?? 0)),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
