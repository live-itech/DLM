<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\TaxInvoice;
use App\Services\NsfpGenerator;
use App\Support\Brand;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class TaxInvoiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:tax-invoices.view', only: ['index', 'show', 'pdf']),
            new Middleware('permission:tax-invoices.create', only: ['generate', 'createInput', 'storeInput']),
            new Middleware('permission:tax-invoices.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $type = $request->get('type', 'output');
        $month = $request->get('month', now()->format('Y-m'));

        $taxInvoices = TaxInvoice::query()
            ->where('type', $type)
            ->when($month, function ($q) use ($month) {
                [$y, $m] = explode('-', $month);
                $q->whereYear('date', $y)->whereMonth('date', $m);
            })
            ->latest('date')
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'count' => $taxInvoices->total(),
            'dpp' => (clone $taxInvoices)->sum('dpp'),
            'ppn' => (clone $taxInvoices)->sum('ppn'),
        ];

        return view('tax-invoices.index', compact('taxInvoices', 'type', 'month', 'summary'));
    }

    /** Generate Faktur Pajak Keluaran dari Invoice. */
    public function generate(Invoice $invoice, NsfpGenerator $nsfp)
    {
        $so = $invoice->salesOrder;
        abort_unless($so->is_taxable && $so->ppn > 0, 422, 'Invoice ini tidak kena PPN, tidak bisa dibuat Faktur Pajak.');

        if ($existing = TaxInvoice::where('invoice_id', $invoice->id)->first()) {
            return redirect()->route('tax-invoices.show', $existing)->with('status', 'Faktur Pajak untuk invoice ini sudah ada.');
        }

        $taxInvoice = DB::transaction(function () use ($invoice, $so, $nsfp) {
            return TaxInvoice::create([
                'type' => 'output',
                'tax_number' => $nsfp->next(),
                'invoice_id' => $invoice->id,
                'partner_name' => $so->customer->name,
                'partner_npwp' => $so->customer->npwp,
                'date' => now()->toDateString(),
                'dpp' => $so->dpp,
                'ppn_rate' => $so->ppn_rate,
                'ppn' => $so->ppn,
                'user_id' => auth()->id(),
            ]);
        });

        return redirect()->route('tax-invoices.show', $taxInvoice)->with('status', 'Faktur Pajak Keluaran berhasil dibuat: ' . $taxInvoice->tax_number);
    }

    public function show(TaxInvoice $taxInvoice)
    {
        $taxInvoice->load(['invoice.salesOrder.customer', 'purchaseOrder.supplier']);

        return view('tax-invoices.show', compact('taxInvoice'));
    }

    /** Form input Faktur Pajak Masukan (dari supplier). */
    public function createInput()
    {
        return view('tax-invoices.input', [
            'purchaseOrders' => PurchaseOrder::with('supplier')->whereIn('status', ['ordered', 'partial', 'received'])->latest()->get(),
            'ppnRate' => (float) Setting::get('ppn_rate', 11),
        ]);
    }

    public function storeInput(Request $request)
    {
        $data = $request->validate([
            'tax_number' => ['required', 'string', 'max:50', 'unique:tax_invoices,tax_number'],
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'partner_name' => ['required', 'string', 'max:255'],
            'partner_npwp' => ['nullable', 'string', 'max:30'],
            'date' => ['required', 'date'],
            'dpp' => ['required', 'numeric', 'min:0'],
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'ppn' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
        $data['type'] = 'input';
        $data['user_id'] = auth()->id();

        TaxInvoice::create($data);

        return redirect()->route('tax-invoices.index', ['type' => 'input'])->with('status', 'Faktur Pajak Masukan tercatat.');
    }

    public function destroy(TaxInvoice $taxInvoice)
    {
        $taxInvoice->delete();

        return back()->with('status', 'Faktur Pajak dihapus.');
    }

    /** Cetak PDF Faktur Pajak Keluaran. */
    public function pdf(TaxInvoice $taxInvoice)
    {
        abort_unless($taxInvoice->type === 'output', 404);
        $taxInvoice->load('invoice.salesOrder.items.product.unit', 'invoice.salesOrder.customer');

        $pdf = Pdf::loadView('pdf.tax-invoice', [
            'ti' => $taxInvoice,
            'company' => Brand::company(),
            'logo' => Brand::logoDataUri(),
        ])->setPaper('a4');

        return $pdf->stream('FakturPajak-' . str_replace(['/', '.'], '-', $taxInvoice->tax_number) . '.pdf');
    }
}
