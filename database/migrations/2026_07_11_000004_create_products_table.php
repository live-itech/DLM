<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('cost_price', 18, 2)->default(0);   // harga beli (sensitif, admin)
            $table->decimal('sell_price', 18, 2)->default(0);   // harga jual
            $table->decimal('stock', 18, 2)->default(0);        // stok saat ini
            $table->decimal('min_stock', 18, 2)->default(0);    // stok minimum
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
