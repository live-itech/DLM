<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->decimal('qty', 18, 2);                 // + masuk / - keluar (nilai bertanda)
            $table->decimal('balance_after', 18, 2);       // saldo stok setelah pergerakan
            $table->string('reference_type')->nullable();  // mis. "Penerimaan PO", "Sales Order"
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            $table->index(['product_id', 'moved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
