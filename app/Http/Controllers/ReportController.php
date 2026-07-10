<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\TaxInvoice;
use App\Support\Brand;
use App\Support\XlsxExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:reports.operational', only: ['index', 'sales', 'stock', 'stockCard', 'receivables', 'taxRecap']),
            new Middleware('permission:reports.finance', only: ['purchases', 'payables', 'profitLoss']),
        ];
    }

    private function range(Request $r): array
    {
        $from = $r->date('from') ?: now()->startOfMonth();
        $to = $r->date('to') ?: now()->endOfMonth();

        return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
    }

    public function index()
    {
        return view('reports.index');
    }

    // ---------------- PENJUALAN ----------------
    public function sales(Request $request)
    {
        [$from, $to] = $this->range($request);

        $invoices = Invoice::with('salesOrder.customer')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $summary = [
            'count' => $invoices->count(),
            'dpp' => $invoices->sum(fn ($i) => (float) $i->salesOrder->dpp),
            'ppn' => $invoices->sum(fn ($i) => (float) $i->salesOrder->ppn),
            'total' => $invoices->sum('total'),
        ];

        if ($export = $this->wantsExport($request)) {
            $headings = ['Tanggal', 'No. Invoice', 'Pelanggan', 'DPP', 'PPN', 'Total', 'Status'];
            $rows = $invoices->map(fn ($i) => [
                $i->date->format('d/m/Y'), $i->invoice_number, $i->salesOrder->customer->name,
                (float) $i->salesOrder->dpp, (float) $i->salesOrder->ppn, (float) $i->total, $i->status_label,
            ]);
            $footer = ['', '', 'TOTAL', $summary['dpp'], $summary['ppn'], $summary['total'], ''];

            return $this->export($export, 'Laporan-Penjualan', 'Laporan Penjualan', $from, $to, $headings, $rows, $footer);
        }

        return view('reports.sales', compact('invoices', 'summary', 'from', 'to'));
    }

    // ---------------- PEMBELIAN ----------------
    public function purchases(Request $request)
    {
        [$from, $to] = $this->range($request);

        $orders = PurchaseOrder::with('supplier')
            ->whereNot('status', 'cancelled')
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        $summary = [
            'count' => $orders->count(),
            'dpp' => $orders->sum('dpp'),
            'ppn' => $orders->sum('ppn'),
            'total' => $orders->sum('total'),
        ];

        if ($export = $this->wantsExport($request)) {
            $headings = ['Tanggal', 'No. PO', 'Supplier', 'DPP', 'PPN', 'Total', 'Status'];
            $rows = $orders->map(fn ($o) => [
                $o->date->format('d/m/Y'), $o->po_number, $o->supplier->name,
                (float) $o->dpp, (float) $o->ppn, (float) $o->total, $o->status_label,
            ]);
            $footer = ['', '', 'TOTAL', $summary['dpp'], $summary['ppn'], $summary['total'], ''];

            return $this->export($export, 'Laporan-Pembelian', 'Laporan Pembelian', $from, $to, $headings, $rows, $footer);
        }

        return view('reports.purchases', compact('orders', 'summary', 'from', 'to'));
    }

    // ---------------- STOK ----------------
    public function stock(Request $request)
    {
        $canCost = auth()->user()->can('reports.finance');

        $products = Product::with(['category', 'unit'])
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock())
            ->orderBy('name')
            ->get();

        $stockValue = $canCost ? $products->sum(fn ($p) => (float) $p->stock * (float) $p->cost_price) : null;

        if ($export = $this->wantsExport($request)) {
            $headings = ['Kode', 'Produk', 'Kategori', 'Stok', 'Satuan', 'Stok Min', 'Status'];
            if ($canCost) {
                $headings[] = 'Harga Beli';
                $headings[] = 'Nilai Stok';
            }
            $rows = $products->map(function ($p) use ($canCost) {
                $row = [$p->code, $p->name, $p->category?->name ?? '-', (float) $p->stock, $p->unit?->symbol ?? '', (float) $p->min_stock, $p->is_low_stock ? 'Menipis' : 'Aman'];
                if ($canCost) {
                    $row[] = (float) $p->cost_price;
                    $row[] = (float) $p->stock * (float) $p->cost_price;
                }

                return $row;
            });

            return $this->export($export, 'Laporan-Stok', 'Laporan Stok', null, null, $headings, $rows);
        }

        return view('reports.stock', compact('products', 'stockValue', 'canCost'));
    }

    public function stockCard(Product $product, Request $request)
    {
        [$from, $to] = $this->range($request);

        $movements = StockMovement::where('product_id', $product->id)
            ->whereBetween('moved_at', [$from, $to])
            ->orderBy('moved_at')
            ->orderBy('id')
            ->get();

        if ($export = $this->wantsExport($request)) {
            $headings = ['Tanggal', 'Tipe', 'Referensi', 'Masuk', 'Keluar', 'Saldo'];
            $rows = $movements->map(fn ($m) => [
                $m->moved_at->format('d/m/Y H:i'), ucfirst($m->type), $m->reference_type,
                $m->qty > 0 ? (float) $m->qty : 0, $m->qty < 0 ? abs((float) $m->qty) : 0, (float) $m->balance_after,
            ]);

            return $this->export($export, 'KartuStok-' . $product->code, 'Kartu Stok: ' . $product->name, $from, $to, $headings, $rows);
        }

        return view('reports.stock-card', compact('product', 'movements', 'from', 'to'));
    }

    // ---------------- PIUTANG (aging) ----------------
    public function receivables(Request $request)
    {
        $invoices = Invoice::with('salesOrder.customer')
            ->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date')
            ->get();

        $aged = $invoices->map(function ($i) {
            $i->aging_days = $i->due_date ? now()->startOfDay()->diffInDays($i->due_date, false) : 0;
            $i->bucket = $this->agingBucket($i->due_date);

            return $i;
        });

        $buckets = $this->bucketTotals($aged);

        if ($export = $this->wantsExport($request)) {
            $headings = ['No. Invoice', 'Pelanggan', 'Jatuh Tempo', 'Total', 'Dibayar', 'Sisa', 'Umur (hari)', 'Kategori'];
            $rows = $aged->map(fn ($i) => [
                $i->invoice_number, $i->salesOrder->customer->name, $i->due_date?->format('d/m/Y') ?? '-',
                (float) $i->total, (float) $i->paid_amount, (float) $i->outstanding,
                $i->due_date ? (int) now()->startOfDay()->diffInDays($i->due_date, false) * -1 : 0, $i->bucket,
            ]);

            return $this->export($export, 'Laporan-Piutang', 'Laporan Piutang (Aging)', null, null, $headings, $rows);
        }

        return view('reports.receivables', compact('aged', 'buckets'));
    }

    // ---------------- HUTANG (aging) ----------------
    public function payables(Request $request)
    {
        $orders = PurchaseOrder::with('supplier')
            ->whereIn('status', ['ordered', 'partial', 'received'])
            ->get()
            ->filter(fn ($o) => $o->outstanding > 0.009)
            ->values();

        $aged = $orders->map(function ($o) {
            $due = $o->date?->copy()->addDays((int) $o->supplier->payment_term_days);
            $o->due_date_calc = $due;
            $o->bucket = $this->agingBucket($due);

            return $o;
        });

        $buckets = $this->bucketTotals($aged, 'outstanding');

        if ($export = $this->wantsExport($request)) {
            $headings = ['No. PO', 'Supplier', 'Jatuh Tempo', 'Total', 'Dibayar', 'Sisa', 'Kategori'];
            $rows = $aged->map(fn ($o) => [
                $o->po_number, $o->supplier->name, $o->due_date_calc?->format('d/m/Y') ?? '-',
                (float) $o->total, (float) $o->paid_amount, (float) $o->outstanding, $o->bucket,
            ]);

            return $this->export($export, 'Laporan-Hutang', 'Laporan Hutang (Aging)', null, null, $headings, $rows);
        }

        return view('reports.payables', compact('aged', 'buckets'));
    }

    // ---------------- LABA RUGI ----------------
    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->range($request);

        $invoices = Invoice::with('salesOrder.items.product')
            ->whereBetween('date', [$from, $to])
            ->get();

        $revenue = 0;
        $cogs = 0;
        $ppnOut = 0;
        foreach ($invoices as $inv) {
            $so = $inv->salesOrder;
            $revenue += (float) $so->dpp;
            $ppnOut += (float) $so->ppn;
            foreach ($so->items as $item) {
                $cogs += (float) $item->qty * (float) $item->product->cost_price;
            }
        }
        $grossProfit = $revenue - $cogs;
        $margin = $revenue > 0 ? $grossProfit / $revenue * 100 : 0;

        $data = compact('revenue', 'cogs', 'grossProfit', 'margin', 'ppnOut', 'from', 'to') + ['count' => $invoices->count()];

        if ($export = $this->wantsExport($request)) {
            $headings = ['Keterangan', 'Nilai'];
            $rows = collect([
                ['Pendapatan (DPP)', $revenue],
                ['HPP (Modal barang terjual)', $cogs],
                ['Laba Kotor', $grossProfit],
                ['Margin (%)', round($margin, 2)],
                ['PPN Keluaran', $ppnOut],
            ]);

            return $this->export($export, 'Laba-Rugi', 'Laporan Laba Rugi', $from, $to, $headings, $rows);
        }

        return view('reports.profit-loss', $data);
    }

    // ---------------- REKAP PAJAK ----------------
    public function taxRecap(Request $request)
    {
        [$from, $to] = $this->range($request);

        $output = TaxInvoice::output()->whereBetween('date', [$from, $to])->get();
        $input = TaxInvoice::input()->whereBetween('date', [$from, $to])->get();

        $ppnOut = $output->sum('ppn');
        $ppnIn = $input->sum('ppn');
        $net = $ppnOut - $ppnIn; // + = kurang bayar, - = lebih bayar

        $data = compact('output', 'input', 'ppnOut', 'ppnIn', 'net', 'from', 'to');

        if ($export = $this->wantsExport($request)) {
            $headings = ['Keterangan', 'Jml Faktur', 'DPP', 'PPN'];
            $rows = collect([
                ['Faktur Keluaran', $output->count(), (float) $output->sum('dpp'), (float) $ppnOut],
                ['Faktur Masukan', $input->count(), (float) $input->sum('dpp'), (float) $ppnIn],
                [($net >= 0 ? 'PPN Kurang Bayar' : 'PPN Lebih Bayar'), '', '', abs($net)],
            ]);

            return $this->export($export, 'Rekap-PPN', 'Rekap PPN', $from, $to, $headings, $rows);
        }

        return view('reports.tax-recap', $data);
    }

    // ---------------- helpers ----------------
    private function wantsExport(Request $r): ?string
    {
        return in_array($r->get('export'), ['excel', 'pdf']) ? $r->get('export') : null;
    }

    private function export(string $type, string $filename, string $title, ?Carbon $from, ?Carbon $to, array $headings, $rows, array $footer = [])
    {
        if ($type === 'excel') {
            return XlsxExport::download($filename, $headings, $rows, $footer);
        }

        $pdf = Pdf::loadView('pdf.report', [
            'title' => $title,
            'period' => $from && $to ? $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y') : null,
            'headings' => $headings,
            'rows' => collect($rows)->map(fn ($r) => array_values((array) $r)),
            'footer' => $footer,
            'company' => Brand::company(),
            'logo' => Brand::logoDataUri(),
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($filename . '.pdf');
    }

    private function agingBucket(?Carbon $due): string
    {
        if (! $due) {
            return 'Belum jatuh tempo';
        }
        $days = now()->startOfDay()->diffInDays($due, false); // negatif = lewat tempo
        $overdue = -1 * $days;
        if ($overdue <= 0) {
            return 'Belum jatuh tempo';
        }
        if ($overdue <= 30) {
            return '1-30 hari';
        }
        if ($overdue <= 60) {
            return '31-60 hari';
        }
        if ($overdue <= 90) {
            return '61-90 hari';
        }

        return '> 90 hari';
    }

    private function bucketTotals($items, string $field = 'outstanding'): array
    {
        $buckets = ['Belum jatuh tempo' => 0, '1-30 hari' => 0, '31-60 hari' => 0, '61-90 hari' => 0, '> 90 hari' => 0];
        foreach ($items as $i) {
            $buckets[$i->bucket] += (float) $i->{$field};
        }

        return $buckets;
    }
}
