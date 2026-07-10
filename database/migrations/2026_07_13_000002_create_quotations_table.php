<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->date('valid_until')->nullable();
            // draft, sent, accepted, rejected, expired
            $table->string('status')->default('draft');
            $table->boolean('is_taxable')->default(true);
            $table->decimal('ppn_rate', 5, 2)->default(11);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('dpp', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 18, 2);
            $table->decimal('sell_price', 18, 2);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('subtotal', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
