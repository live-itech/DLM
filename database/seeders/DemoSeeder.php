<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\TaxInvoice;
use App\Services\NsfpGenerator;
use App\Services\StockService;
use App\Support\Totals;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private StockService $stock;
    private NsfpGenerator $nsfp;
    private float $ppnRate;

    public function run(): void
    {
        if (SalesOrder::exists() || PurchaseOrder::exists()) {
            $this->command?->warn('Data transaksi sudah ada — DemoSeeder dilewati. Kosongkan tabel transaksi dulu bila ingin re-seed.');

            return;
        }

        $this->stock = app(StockService::class);
        $this->nsfp = app(NsfpGenerator::class);
        $this->ppnRate = (float) Setting::get('ppn_rate', 11);

        $products = Product::all()->keyBy('id');
        $customers = Customer::all()->values();
        $suppliers = Supplier::all()->values();

        // ---- PEMBELIAN: 1-2 PO per bulan (5 bulan terakhir), semua diterima ----
        for ($m = 4; $m >= 0; $m--) {
            $date = Carbon::now()->subMonthsNoOverflow($m)->startOfMonth()->addDays(rand(2, 20));
            $poCount = rand(1, 2);
            for ($k = 0; $k < $poCount; $k++) {
                $this->makePurchase($suppliers->random(), $products, $date->copy()->addDays($k * 3), rand(0, 1) === 1);
            }
        }

        // ---- PENJUALAN: 2-4 SO per bulan, dikonfirmasi & diinvoice ----
        for ($m = 4; $m >= 0; $m--) {
            $soCount = rand(2, 4);
            for ($k = 0; $k < $soCount; $k++) {
                $date = Carbon::now()->subMonthsNoOverflow($m)->startOfMonth()->addDays(rand(3, 26));
                $this->makeSale($customers->random(), $products, $date);
            }
        }

        // ---- QUOTATION: beberapa dengan status berbeda ----
        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'sent'];
        foreach ($statuses as $i => $st) {
            $this->makeQuotation($customers->random(), $products, Carbon::now()->subDays(rand(1, 40)), $st);
        }

        $this->command?->info('Demo: '
            . PurchaseOrder::count() . ' PO, '
            . SalesOrder::count() . ' SO, '
            . Invoice::count() . ' invoice, '
            . TaxInvoice::count() . ' faktur pajak, '
            . Quotation::count() . ' penawaran.');
    }

    private function makePurchase(Supplier $supplier, $products, Carbon $date, bool $taxable): void
    {
        $picked = $products->random(rand(1, 3));

        DB::transaction(function () use ($supplier, $picked, $date, $taxable) {
            $po = PurchaseOrder::create([
                'po_number' => 'PO/' . $date->format('ym') . '/' . str_pad((string) (PurchaseOrder::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'date' => $date,
                'status' => 'ordered',
                'is_taxable' => $taxable,
                'ppn_rate' => $this->ppnRate,
                'discount' => 0,
            ]);

            $sub = 0;
            foreach ($picked as $p) {
                $qty = rand(10, 60);
                $cost = (float) $p->cost_price ?: 10000;
                $line = $qty * $cost;
                $po->items()->create(['product_id' => $p->id, 'qty' => $qty, 'cost_price' => $cost, 'discount' => 0, 'subtotal' => $line]);
                $sub += $line;
            }
            $po->fill(Totals::compute($sub, 0, $taxable, $this->ppnRate))->save();

            // Penerimaan penuh + stok masuk
            $gr = $po->receipts()->create([
                'gr_number' => 'GR/' . $date->format('ym') . '/' . str_pad((string) (\App\Models\GoodsReceipt::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'warehouse_id' => \App\Models\Warehouse::default()?->id,
                'date' => $date->copy()->addDays(2),
            ]);
            foreach ($po->items as $item) {
                $gr->items()->create(['purchase_order_item_id' => $item->id, 'product_id' => $item->product_id, 'qty' => $item->qty]);
                $item->update(['qty_received' => $item->qty]);
                $this->stock->move(Product::find($item->product_id), (float) $item->qty, 'in', 'Penerimaan PO ' . $po->po_number, $gr->id, 'Demo');
            }
            $po->update(['status' => 'received']);

            // Sebagian PO dibayar
            $r = rand(0, 2);
            if ($r > 0) {
                $amount = $r === 2 ? (float) $po->total : round((float) $po->total * 0.5);
                $po->payments()->create(['date' => $date->copy()->addDays(5), 'amount' => $amount, 'method' => 'transfer']);
                $po->increment('paid_amount', $amount);
            }

            $po->created_at = $date;
            $po->save();
        });
    }

    private function makeSale(Customer $customer, $products, Carbon $date): void
    {
        $taxable = rand(0, 3) > 0; // ~75% kena PPN
        $picked = $products->where('stock', '>', 5)->random(min(3, max(1, rand(1, 3))));

        DB::transaction(function () use ($customer, $picked, $date, $taxable) {
            $so = SalesOrder::create([
                'so_number' => 'SO/' . $date->format('ym') . '/' . str_pad((string) (SalesOrder::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'date' => $date,
                'status' => 'draft',
                'is_taxable' => $taxable,
                'ppn_rate' => $this->ppnRate,
                'discount' => 0,
            ]);

            $sub = 0;
            foreach ($picked as $p) {
                $max = max(1, min(20, (int) $p->stock - 2));
                $qty = rand(1, $max);
                $price = (float) $p->sell_price ?: 20000;
                $line = $qty * $price;
                $so->items()->create(['product_id' => $p->id, 'qty' => $qty, 'sell_price' => $price, 'discount' => 0, 'subtotal' => $line]);
                $sub += $line;
            }
            $so->fill(Totals::compute($sub, 0, $taxable, $this->ppnRate))->save();

            // Konfirmasi -> stok keluar
            foreach ($so->items as $item) {
                $this->stock->move(Product::find($item->product_id), -1 * (float) $item->qty, 'out', 'Sales Order ' . $so->so_number, $so->id, 'Demo', preventNegative: false);
            }
            $so->update(['status' => 'confirmed', 'stock_deducted' => true]);

            // Invoice
            $term = (int) ($customer->payment_term_days ?: 30);
            $inv = Invoice::create([
                'invoice_number' => 'INV/' . $date->format('ym') . '/' . str_pad((string) (Invoice::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'sales_order_id' => $so->id,
                'date' => $date->copy()->addDay(),
                'due_date' => $date->copy()->addDay()->addDays($term),
                'status' => 'unpaid',
                'total' => $so->total,
            ]);
            $so->update(['status' => 'completed']);

            // Pembayaran: acak lunas / sebagian / belum
            $r = rand(0, 3);
            if ($r >= 2) { // lunas
                $inv->payments()->create(['date' => $date->copy()->addDays(rand(3, $term)), 'amount' => $so->total, 'method' => 'transfer']);
            } elseif ($r === 1) { // sebagian
                $inv->payments()->create(['date' => $date->copy()->addDays(rand(3, 10)), 'amount' => round((float) $so->total * 0.4), 'method' => 'transfer']);
            }
            $inv->refreshPaymentStatus();

            // Faktur pajak untuk yang kena PPN
            if ($taxable && $so->ppn > 0) {
                TaxInvoice::create([
                    'type' => 'output',
                    'tax_number' => $this->nsfp->next(),
                    'invoice_id' => $inv->id,
                    'partner_name' => $customer->name,
                    'partner_npwp' => $customer->npwp,
                    'date' => $inv->date,
                    'dpp' => $so->dpp,
                    'ppn_rate' => $so->ppn_rate,
                    'ppn' => $so->ppn,
                ]);
            }

            $so->created_at = $date;
            $so->save();
            $inv->created_at = $date;
            $inv->save();
        });
    }

    private function makeQuotation(Customer $customer, $products, Carbon $date, string $status): void
    {
        $taxable = true;
        $picked = $products->random(rand(1, 3));

        DB::transaction(function () use ($customer, $picked, $date, $taxable, $status) {
            $q = Quotation::create([
                'quotation_number' => 'QT/' . $date->format('ym') . '/' . str_pad((string) (Quotation::max('id') + 1), 5, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'date' => $date,
                'valid_until' => $date->copy()->addDays(14),
                'status' => $status,
                'is_taxable' => $taxable,
                'ppn_rate' => $this->ppnRate,
                'discount' => 0,
                'terms' => "1. Harga sudah termasuk PPN.\n2. Penawaran berlaku 14 hari.",
            ]);
            $sub = 0;
            foreach ($picked as $p) {
                $qty = rand(2, 15);
                $price = (float) $p->sell_price ?: 20000;
                $line = $qty * $price;
                $q->items()->create(['product_id' => $p->id, 'qty' => $qty, 'sell_price' => $price, 'discount' => 0, 'subtotal' => $line]);
                $sub += $line;
            }
            $q->fill(Totals::compute($sub, 0, $taxable, $this->ppnRate))->save();
            $q->created_at = $date;
            $q->save();
        });
    }
}
