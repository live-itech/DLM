<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Services\StockService;
use App\Support\Totals;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:sales-orders.view', only: ['index', 'show']),
            new Middleware('permission:sales-orders.create', only: ['create', 'store']),
            new Middleware('permission:sales-orders.update', only: ['edit', 'update', 'confirm', 'cancel', 'toInvoice']),
            new Middleware('permission:sales-orders.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        $orders = SalesOrder::query()
            ->with(['customer', 'invoice'])
            ->when($request->search, fn ($q, $s) => $q->where('so_number', 'like', "%{$s}%")
                ->orWhereHas('customer', fn ($w) => $w->where('name', 'like', "%{$s}%")))
            ->when($request->status, fn ($q, $st) => $q->where('status', $st))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('sales-orders.index', compact('orders'));
    }

    public function create()
    {
        return view('sales-orders.form', [
            'order' => new SalesOrder([
                'date' => now()->toDateString(),
                'is_taxable' => true,
                'ppn_rate' => (float) Setting::get('ppn_rate', 11),
            ]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $order = DB::transaction(function () use ($data) {
            $order = SalesOrder::create([
                'so_number' => 'TMP-' . Str::random(24),
                'customer_id' => $data['customer_id'],
                'user_id' => auth()->id(),
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'status' => 'draft',
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'notes' => $data['notes'] ?? null,
            ]);
            $order->so_number = $this->makeNumber('SO', $order->id, $order->date);
            $this->syncItems($order, $data['items']);
            $order->save();

            return $order;
        });

        return redirect()->route('sales-orders.show', $order)->with('status', 'Sales Order berhasil dibuat.');
    }

    public function show(SalesOrder $salesOrder)
    {
        $salesOrder->load(['customer', 'items.product.unit', 'invoice', 'user']);

        return view('sales-orders.show', ['order' => $salesOrder]);
    }

    public function edit(SalesOrder $salesOrder)
    {
        abort_unless($salesOrder->isEditable(), 403, 'SO yang sudah dikonfirmasi tidak bisa diedit.');
        $salesOrder->load('items');

        return view('sales-orders.form', [
            'order' => $salesOrder,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, SalesOrder $salesOrder)
    {
        abort_unless($salesOrder->isEditable(), 403);
        $data = $this->validateData($request);

        DB::transaction(function () use ($salesOrder, $data) {
            $salesOrder->update([
                'customer_id' => $data['customer_id'],
                'date' => $data['date'],
                'due_date' => $data['due_date'],
                'is_taxable' => $data['is_taxable'],
                'ppn_rate' => $data['ppn_rate'],
                'discount' => $data['discount'],
                'notes' => $data['notes'] ?? null,
            ]);
            $salesOrder->items()->delete();
            $this->syncItems($salesOrder, $data['items']);
        });

        return redirect()->route('sales-orders.show', $salesOrder)->with('status', 'Sales Order berhasil diperbarui.');
    }

    public function destroy(SalesOrder $salesOrder)
    {
        abort_unless($salesOrder->isEditable(), 403, 'Hanya SO draft yang bisa dihapus.');
        $salesOrder->delete();

        return redirect()->route('sales-orders.index')->with('status', 'Sales Order dihapus.');
    }

    /** Konfirmasi SO -> kurangi stok. */
    public function confirm(SalesOrder $salesOrder, StockService $stock)
    {
        abort_unless($salesOrder->status === 'draft', 403);

        try {
            DB::transaction(function () use ($salesOrder, $stock) {
                $salesOrder->load('items.product');
                foreach ($salesOrder->items as $item) {
                    $stock->move(
                        $item->product,
                        -1 * (float) $item->qty,
                        'out',
                        'Sales Order ' . $salesOrder->so_number,
                        $salesOrder->id,
                        'Penjualan SO ' . $salesOrder->so_number,
                    );
                }
                $salesOrder->update(['status' => 'confirmed', 'stock_deducted' => true]);
            });
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages(['stock' => $e->getMessage()]);
        }

        return back()->with('status', 'SO dikonfirmasi & stok telah dikurangi.');
    }

    /** Batalkan SO -> kembalikan stok jika sudah dikurangi. */
    public function cancel(SalesOrder $salesOrder, StockService $stock)
    {
        abort_unless($salesOrder->isCancellable(), 403, 'SO ini tidak bisa dibatalkan.');

        DB::transaction(function () use ($salesOrder, $stock) {
            if ($salesOrder->stock_deducted) {
                $salesOrder->load('items.product');
                foreach ($salesOrder->items as $item) {
                    $stock->move(
                        $item->product,
                        (float) $item->qty,
                        'in',
                        'Pembatalan SO ' . $salesOrder->so_number,
                        $salesOrder->id,
                        'Retur stok pembatalan',
                        preventNegative: false,
                    );
                }
            }
            $salesOrder->update(['status' => 'cancelled', 'stock_deducted' => false]);
        });

        return back()->with('status', 'SO dibatalkan & stok dikembalikan.');
    }

    /** Buat Invoice dari SO. */
    public function toInvoice(Request $request, SalesOrder $salesOrder)
    {
        abort_unless(in_array($salesOrder->status, ['confirmed', 'shipped']), 403, 'SO harus dikonfirmasi dulu.');
        abort_if($salesOrder->invoice()->exists(), 422, 'SO ini sudah memiliki invoice.');

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
        ]);

        $invoice = DB::transaction(function () use ($salesOrder, $validated) {
            $invoice = Invoice::create([
                'invoice_number' => 'TMP-' . Str::random(24),
                'sales_order_id' => $salesOrder->id,
                'user_id' => auth()->id(),
                'date' => $validated['date'],
                // Jatuh tempo kosong -> ikut jatuh tempo SO, lalu termin pelanggan.
                'due_date' => $validated['due_date']
                    ?? $salesOrder->due_date
                    ?? $salesOrder->customer->dueDateFrom($validated['date']),
                'status' => 'unpaid',
                'total' => $salesOrder->total,
                'paid_amount' => 0,
            ]);
            $invoice->update(['invoice_number' => $this->makeNumber('INV', $invoice->id, $invoice->date)]);
            $salesOrder->update(['status' => 'completed']);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice berhasil dibuat dari SO.');
    }

    // ---------- helpers ----------

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'is_taxable' => ['nullable', 'boolean'],
            'ppn_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.sell_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_taxable'] = $request->boolean('is_taxable');
        $data['discount'] = (float) ($data['discount'] ?? 0);
        // Jatuh tempo kosong -> pakai termin pelanggan.
        $data['due_date'] = $data['due_date']
            ?? Customer::find($data['customer_id'])?->dueDateFrom($data['date']);

        return $data;
    }

    private function syncItems(SalesOrder $order, array $items): void
    {
        $itemsSubtotal = 0;
        foreach ($items as $row) {
            $sub = max(0, (float) $row['qty'] * (float) $row['sell_price'] - (float) ($row['discount'] ?? 0));
            $order->items()->create([
                'product_id' => $row['product_id'],
                'qty' => $row['qty'],
                'sell_price' => $row['sell_price'],
                'discount' => $row['discount'] ?? 0,
                'discount_reason' => $row['discount_reason'] ?? null,
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
