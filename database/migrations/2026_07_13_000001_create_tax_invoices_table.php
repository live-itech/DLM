<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['output', 'input']); // keluaran / masukan
            $table->string('tax_number')->unique();     // NSFP
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();          // keluaran
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete(); // masukan
            $table->string('partner_name');             // lawan transaksi (pembeli/penjual)
            $table->string('partner_npwp', 30)->nullable();
            $table->date('date');
            $table->decimal('dpp', 18, 2)->default(0);
            $table->decimal('ppn_rate', 5, 2)->default(11);
            $table->decimal('ppn', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
