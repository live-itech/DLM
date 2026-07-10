<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 18, 2);
            $table->decimal('sell_price', 18, 2);
            $table->decimal('discount', 18, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('subtotal', 18, 2);   // qty*sell_price - discount
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
