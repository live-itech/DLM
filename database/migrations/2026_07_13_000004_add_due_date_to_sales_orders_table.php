<?php

use App\Models\SalesOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('date');
        });

        // SO lama: pakai jatuh tempo invoice-nya kalau sudah terbit, sisanya dari termin pelanggan.
        SalesOrder::with(['customer', 'invoice'])->whereNull('due_date')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $order->updateQuietly([
                    'due_date' => $order->invoice?->due_date ?? $order->customer?->dueDateFrom($order->date),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
