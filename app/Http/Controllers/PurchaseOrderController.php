<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\StockService;
use App\Support\Brand;
use App\Support\Totals;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchase-orders.view', only: ['index', 'show', 'pdf']),
            new Middleware('permission:purchase-orders.create', only: ['create', 'store']),
            new Middleware('permission:purchase-orders.update', only: ['edit', 'update', 'order', 'cancel', 'receiveForm', 'storeReceipt', 'storePayment']),
            new Middleware('permission:purchase-orders.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->when($request->search, fn ($q, $s) => $q->where('po_number', 'like', "%{$s}%")
                ->orWhereHas('supplier', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchase-orders.index', compact('orders'));
    }

    public function create()
    {
        return view('purchase-orders.form', [
            'order' => new PurchaseOrder([
                'date' => now()->toDateString(),
                'is_taxable' => true,
                'ppn_rate' => (float) Setting::get('ppn_rate', 11),
            ]),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $order = DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'po_number' => 'TMP-' . Str::random(24),
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'status' => 'draft',
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'notes' => $data['notes'] ?? null,
            ]);
            $order->po_number = $this->makeNumber('PO', $order->id, $order->date);
            $this->syncItems($order, $data['items']);
            $order->save();

            return $order;
        });

        return redirect()->route('purchase-orders.show', $order)->with('status', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product.unit', 'receipts.items', 'payments', 'user']);

        return view('purchase-orders.show', ['order' => $purchaseOrder]);
    }

    public function pdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product.unit']);

        $pdf = Pdf::loadView('pdf.purchase-order', [
            'order' => $purchaseOrder,
            'company' => Brand::company(),
            'logo' => Brand::logoDataUri(),
        ])->setPaper('a4');

        return $pdf->stream('PO-' . str_replace('/', '-', $purchaseOrder->po_number) . '.pdf');
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->isEditable(), 403, 'PO yang sudah diproses tidak bisa diedit.');

        $purchaseOrder->load('items');

        return view('purchase-orders.form', [
            'order' => $purchaseOrder,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->isEditable(), 403);
        $data = $this->validateData($request);

        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'notes' => $data['notes'] ?? null,
            ]);
            $purchaseOrder->items()->delete();
            $this->syncItems($purchaseOrder, $data['items']);
            $purchaseOrder->save();
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase Order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->isEditable(), 403, 'Hanya PO draft yang bisa dihapus.');
        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')->with('status', 'Purchase Order dihapus.');
    }

    /** Konfirmasi PO (draft -> ordered). */
    public function order(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->status === 'draft', 403);
        $purchaseOrder->update(['status' => 'ordered']);

        return back()->with('status', 'PO dikonfirmasi & dipesan ke supplier.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        abort_if(in_array($purchaseOrder->status, ['received', 'cancelled']), 403, 'PO ini tidak bisa dibatalkan.');
        abort_if($purchaseOrder->receipts()->exists(), 422, 'PO sudah ada penerimaan barang, tidak bisa dibatalkan.');
        $purchaseOrder->update(['status' => 'cancelled']);

        return back()->with('status', 'PO dibatalkan.');
    }

    /** Form penerimaan barang. */
    public function receiveForm(PurchaseOrder $purchaseOrder)
    {
        abort_unless(in_array($purchaseOrder->status, ['ordered', 'partial']), 403, 'PO belum dipesan atau sudah selesai.');
        $purchaseOrder->load('items.product.unit');

        return view('purchase-orders.receive', ['order' => $purchaseOrder]);
    }

    /** Simpan penerimaan barang -> stok bertambah. */
    public function storeReceipt(Request $request, PurchaseOrder $purchaseOrder, StockService $stock)
    {
        abort_unless(in_array($purchaseOrder->status, ['ordered', 'partial']), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated, $stock) {
            $purchaseOrder->load('items');
            $receipt = $purchaseOrder->receipts()->create([
                'gr_number' => 'TMP-' . Str::random(24),
                'warehouse_id' => \App\Models\Warehouse::default()?->id,
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);
            $receipt->update(['gr_number' => $this->makeNumber('GR', $receipt->id, $validated['date'])]);

            $anyReceived = false;
            foreach ($purchaseOrder->items as $item) {
                $qty = (float) ($validated['qty'][$item->id] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                // Tidak melebihi sisa yang belum diterima.
                $qty = min($qty, $item->outstanding_qty);
                if ($qty <= 0) {
                    continue;
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'qty' => $qty,
                ]);

                $item->increment('qty_received', $qty);

                $stock->move(
                    $item->product,
                    $qty,
                    'in',
                    'Penerimaan PO ' . $purchaseOrder->po_number,
                    $receipt->id,
                    'GR ' . $receipt->gr_number,
                );
                $anyReceived = true;
            }

            abort_if(! $anyReceived, 422, 'Tidak ada qty yang diterima.');

            $purchaseOrder->status = $purchaseOrder->isFullyReceived() ? 'received' : 'partial';
            $purchaseOrder->save();
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Penerimaan barang tersimpan & stok diperbarui.');
    }

    /** Catat pembayaran ke supplier (hutang). */
    public function storePayment(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_if(in_array($purchaseOrder->status, ['draft', 'cancelled']), 403);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, $purchaseOrder->outstanding)],
            'method' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('supplier-invoices', 'public');
        }

        DB::transaction(function () use ($purchaseOrder, $validated, $attachmentPath) {
            $purchaseOrder->payments()->create([
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                'amount' => $validated['amount'],
                'method' => $validated['method'] ?? null,
                'note' => $validated['note'] ?? null,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'attachment' => $attachmentPath,
            ]);
            $purchaseOrder->increment('paid_amount', $validated['amount']);
        });

        return back()->with('status', 'Pembayaran ke supplier tercatat.');
    }

    // ---------- helpers ----------

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'is_taxable' => ['nullable', 'boolean'],
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['is_taxable'] = $request->boolean('is_taxable');
        $data['discount'] = (float) ($data['discount'] ?? 0);
        // Jatuh tempo kosong -> pakai termin supplier.
        $data['due_date'] = $data['due_date']
            ?? Supplier::find($data['supplier_id'])?->dueDateFrom($data['date']);

        return $data;
    }

    private function syncItems(PurchaseOrder $order, array $items): void
    {
        $itemsSubtotal = 0;
        foreach ($items as $row) {
            $sub = max(0, (float) $row['qty'] * (float) $row['cost_price'] - (float) ($row['discount'] ?? 0));
            $order->items()->create([
                'product_id' => $row['product_id'],
                'qty' => $row['qty'],
                'cost_price' => $row['cost_price'],
                'discount' => $row['discount'] ?? 0,
                'subtotal' => $sub,
            ]);
            $itemsSubtotal += $sub;
        }

        $t = Totals::compute($itemsSubtotal, (float) $order->discount, (bool) $order->is_taxable, (float) $order->ppn_rate);
        $order->fill($t);
    }

    private function makeNumber(string $prefix, int $id, string $date): string
    {
        return $prefix . '/' . date('ym', strtotime($date)) . '/' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }
}
