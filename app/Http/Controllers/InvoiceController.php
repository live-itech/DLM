<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Support\Brand;
use App\Support\Totals;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'show', 'pdf']),
            new Middleware('permission:invoices.update', only: ['edit', 'update', 'storePayment', 'updateDates']),
        ];
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['salesOrder.customer', 'salesOrder.items.product.unit']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => Brand::company(),
            'logo' => Brand::logoDataUri(),
        ])->setPaper('a4');

        return $pdf->stream('Invoice-' . str_replace('/', '-', $invoice->invoice_number) . '.pdf');
    }

    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with(['salesOrder.customer'])
            ->when($request->search, fn ($q, $s) => $q->where('invoice_number', 'like', "%{$s}%")
                ->orWhereHas('salesOrder.customer', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['salesOrder.customer', 'salesOrder.items.product.unit', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Invoice yang sudah lunas tidak bisa diedit.');

        $invoice->load(['salesOrder.customer', 'salesOrder.items']);
        $so = $invoice->salesOrder;

        return view('invoices.form', [
            'invoice' => $invoice,
            'order' => $so,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Invoice yang sudah lunas tidak bisa diedit.');

        $data = $request->validate([
            'is_taxable' => ['nullable', 'boolean'],
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.sell_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_taxable'] = $request->boolean('is_taxable');
        $data['discount'] = (float) ($data['discount'] ?? 0);

        DB::transaction(function () use ($invoice, $data) {
            $so = $invoice->salesOrder;

            // Hapus item lama & buat ulang
            $so->items()->delete();

            $itemsSubtotal = 0;
            foreach ($data['items'] as $row) {
                $sub = max(0, (float) $row['qty'] * (float) $row['sell_price'] - (float) ($row['discount'] ?? 0));
                $so->items()->create([
                    'product_id' => $row['product_id'],
                    'qty' => $row['qty'],
                    'sell_price' => $row['sell_price'],
                    'discount' => $row['discount'] ?? 0,
                    'discount_reason' => $row['discount_reason'] ?? null,
                    'subtotal' => $sub,
                ]);
                $itemsSubtotal += $sub;
            }

            // Hitung ulang total SO
            $t = Totals::compute($itemsSubtotal, $data['discount'], $data['is_taxable'], (float) $data['ppn_rate']);
            $so->update(array_merge($t, [
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
            ]));

            // Update total invoice
            $invoice->update(['total' => $t['total']]);

            // Jika sudah ada pembayaran, refresh status
            if ((float) $invoice->paid_amount > 0) {
                $invoice->refreshPaymentStatus();
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Item invoice berhasil diperbarui.');
    }

    /** Ubah tanggal invoice & tanggal jatuh tempo. */
    public function updateDates(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
        ]);

        $invoice->update($validated);

        return back()->with('status', 'Tanggal invoice diperbarui.');
    }

    public function storePayment(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 422, 'Invoice sudah lunas.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, $invoice->outstanding)],
            'method' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->payments()->create([
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            $invoice->refreshPaymentStatus();
        });

        return back()->with('status', 'Pembayaran pelanggan tercatat.');
    }
}
