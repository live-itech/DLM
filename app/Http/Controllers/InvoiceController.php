<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\Brand;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:invoices.view', only: ['index', 'show', 'pdf']),
            new Middleware('permission:invoices.update', only: ['storePayment', 'updateDates']),
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

        $duplicate = false;

        DB::transaction(function () use ($invoice, $validated, &$duplicate) {
            // Kunci baris invoice agar pembayaran ganda/berbarengan diproses berurutan.
            $locked = Invoice::whereKey($invoice->getKey())->lockForUpdate()->first();

            // Request kedua dari klik ganda: invoice mungkin sudah lunas.
            abort_if($locked->status === 'paid', 422, 'Invoice sudah lunas.');

            // Cegah duplikat: pembayaran identik (jumlah & tanggal) dalam 15 detik terakhir.
            if ($locked->payments()
                ->where('amount', $validated['amount'])
                ->whereDate('date', $validated['date'])
                ->where('created_at', '>=', now()->subSeconds(15))
                ->exists()
            ) {
                $duplicate = true;

                return;
            }

            // Re-cek sisa tagihan dengan data terkunci (anti balapan).
            $outstanding = (float) $locked->total - (float) $locked->payments()->sum('amount');
            if ((float) $validated['amount'] > $outstanding + 0.009) {
                throw ValidationException::withMessages([
                    'amount' => 'Jumlah melebihi sisa tagihan (Rp ' . number_format($outstanding, 0, ',', '.') . ').',
                ]);
            }

            $locked->payments()->create([
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? null,
                'note' => $validated['note'] ?? null,
            ]);
            $locked->refreshPaymentStatus();
        });

        return back()->with('status', $duplicate
            ? 'Pembayaran identik baru saja tercatat — duplikat diabaikan.'
            : 'Pembayaran pelanggan tercatat.');
    }
}
