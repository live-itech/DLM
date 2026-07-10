<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Support\Brand;
use App\Support\Totals;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuotationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:quotations.view', only: ['index', 'show', 'pdf']),
            new Middleware('permission:quotations.create', only: ['create', 'store']),
            new Middleware('permission:quotations.update', only: ['edit', 'update', 'setStatus', 'toSalesOrder']),
            new Middleware('permission:quotations.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $quotations = Quotation::query()
            ->with('customer')
            ->when($request->search, fn ($q, $s) => $q->where('quotation_number', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('quotations.form', [
            'quotation' => new Quotation([
                'date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'is_taxable' => true,
                'ppn_rate' => (float) Setting::get('ppn_rate', 11),
                'terms' => "1. Harga sudah termasuk PPN (bila dikenakan).\n2. Penawaran berlaku sampai tanggal yang tertera.\n3. Ketersediaan barang sewaktu-waktu dapat berubah.",
            ]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $quotation = DB::transaction(function () use ($data) {
            $q = Quotation::create([
                'quotation_number' => 'TMP-' . Str::random(24),
                'customer_id' => $data['customer_id'],
                'user_id' => auth()->id(),
                'date' => $data['date'],
                'valid_until' => $data['valid_until'] ?? null,
                'status' => 'draft',
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $q->quotation_number = $this->makeNumber($q->id, $q->date);
            $this->syncItems($q, $data['items']);
            $q->save();

            return $q;
        });

        return redirect()->route('quotations.show', $quotation)->with('status', 'Surat Penawaran berhasil dibuat.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product.unit', 'salesOrder', 'user']);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        abort_unless($quotation->isEditable(), 403, 'Penawaran ini tidak bisa diedit.');
        $quotation->load('items');

        return view('quotations.form', [
            'quotation' => $quotation,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        abort_unless($quotation->isEditable(), 403);
        $data = $this->validateData($request);

        DB::transaction(function () use ($quotation, $data) {
            $quotation->update([
                'customer_id' => $data['customer_id'],
                'date' => $data['date'],
                'valid_until' => $data['valid_until'] ?? null,
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
            $quotation->items()->delete();
            $this->syncItems($quotation, $data['items']);
        });

        return redirect()->route('quotations.show', $quotation)->with('status', 'Surat Penawaran diperbarui.');
    }

    public function destroy(Quotation $quotation)
    {
        abort_if($quotation->converted_sales_order_id, 422, 'Penawaran sudah dikonversi ke SO.');
        $quotation->delete();

        return redirect()->route('quotations.index')->with('status', 'Surat Penawaran dihapus.');
    }

    /** Ubah status: sent / accepted / rejected. */
    public function setStatus(Request $request, Quotation $quotation)
    {
        $to = $request->validate(['status' => ['required', 'in:sent,accepted,rejected']])['status'];
        $quotation->update(['status' => $to]);

        return back()->with('status', 'Status penawaran diperbarui menjadi "' . Quotation::STATUSES[$to] . '".');
    }

    /** Konversi penawaran -> Sales Order (draft). */
    public function toSalesOrder(Quotation $quotation)
    {
        abort_if($quotation->converted_sales_order_id, 422, 'Penawaran ini sudah dikonversi.');
        $quotation->load('items');

        $so = DB::transaction(function () use ($quotation) {
            $so = SalesOrder::create([
                'so_number' => 'TMP-' . Str::random(24),
                'customer_id' => $quotation->customer_id,
                'user_id' => auth()->id(),
                'date' => now()->toDateString(),
                'status' => 'draft',
                'is_taxable' => $quotation->is_taxable,
                'ppn_rate' => $quotation->ppn_rate,
                'discount' => $quotation->discount,
                'subtotal' => $quotation->subtotal,
                'dpp' => $quotation->dpp,
                'ppn' => $quotation->ppn,
                'total' => $quotation->total,
                'notes' => 'Dari penawaran ' . $quotation->quotation_number,
            ]);
            $so->so_number = 'SO/' . date('ym') . '/' . str_pad((string) $so->id, 5, '0', STR_PAD_LEFT);
            $so->save();

            foreach ($quotation->items as $item) {
                $so->items()->create([
                    'product_id' => $item->product_id,
                    'qty' => $item->qty,
                    'sell_price' => $item->sell_price,
                    'discount' => $item->discount,
                    'subtotal' => $item->subtotal,
                ]);
            }

            $quotation->update(['status' => 'accepted', 'converted_sales_order_id' => $so->id]);

            return $so;
        });

        return redirect()->route('sales-orders.show', $so)->with('status', 'Penawaran dikonversi menjadi Sales Order ' . $so->so_number);
    }

    public function pdf(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product.unit']);

        $pdf = Pdf::loadView('pdf.quotation', [
            'q' => $quotation,
            'company' => Brand::company(),
            'logo' => Brand::logoDataUri(),
        ])->setPaper('a4');

        return $pdf->stream('Penawaran-' . str_replace('/', '-', $quotation->quotation_number) . '.pdf');
    }

    // ---------- helpers ----------

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:date'],
            'is_taxable' => ['nullable', 'boolean'],
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.sell_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_taxable'] = $request->boolean('is_taxable');
        $data['discount'] = (float) ($data['discount'] ?? 0);

        return $data;
    }

    private function syncItems(Quotation $q, array $items): void
    {
        $itemsSubtotal = 0;
        foreach ($items as $row) {
            $sub = max(0, (float) $row['qty'] * (float) $row['sell_price'] - (float) ($row['discount'] ?? 0));
            $q->items()->create([
                'product_id' => $row['product_id'],
                'qty' => $row['qty'],
                'sell_price' => $row['sell_price'],
                'discount' => $row['discount'] ?? 0,
                'subtotal' => $sub,
            ]);
            $itemsSubtotal += $sub;
        }

        $q->fill(Totals::compute($itemsSubtotal, (float) $q->discount, (bool) $q->is_taxable, (float) $q->ppn_rate));
    }

    private function makeNumber(int $id, string $date): string
    {
        return 'QT/' . date('ym', strtotime($date)) . '/' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
}
