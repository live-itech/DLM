<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            // draft, ordered, partial, received, cancelled
            $table->string('status')->default('draft');
            $table->boolean('is_taxable')->default(true);   // PPN opsional
            $table->decimal('ppn_rate', 5, 2)->default(11);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('dpp', 18, 2)->default(0);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->decimal('paid_amount', 18, 2)->default(0); // untuk hutang
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
