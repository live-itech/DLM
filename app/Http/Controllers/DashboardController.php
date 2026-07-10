<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $isFinance = auth()->user()->can('reports.finance');
        $now = now();

        // Statistik dasar (semua role)
        $stats = [
            'products' => Product::count(),
            'customers' => Customer::where('is_active', true)->count(),
            'low_stock' => Product::lowStock()->count(),
            'unpaid_invoices' => Invoice::whereIn('status', ['unpaid', 'partial'])->count(),
        ];

        $lowStockProducts = Product::lowStock()->with('unit')->orderBy('stock')->limit(6)->get();

        $data = compact('stats', 'lowStockProducts', 'isFinance');

        // Produk terlaris (qty terjual) — operasional
        $data['topProducts'] = SalesOrderItem::query()
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->whereHas('salesOrder', fn ($q) => $q->whereIn('status', ['confirmed', 'shipped', 'completed']))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->with('product')
            ->limit(5)
            ->get();

        // ---- Data finansial (Admin saja) ----
        if ($isFinance) {
            $thisMonth = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            $lastMonth = [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()];

            $data['omzetThisMonth'] = (float) Invoice::whereBetween('date', $thisMonth)->sum('total');
            $data['omzetLastMonth'] = (float) Invoice::whereBetween('date', $lastMonth)->sum('total');
            $data['purchaseThisMonth'] = (float) PurchaseOrder::whereNot('status', 'cancelled')->whereBetween('date', $thisMonth)->sum('total');
            $data['receivables'] = (float) Invoice::whereIn('status', ['unpaid', 'partial'])->get()->sum('outstanding');
            $data['payables'] = (float) PurchaseOrder::whereIn('status', ['ordered', 'partial', 'received'])->get()->sum('outstanding');

            // Grafik 6 bulan: penjualan vs pembelian
            $months = collect(range(5, 0))->map(fn ($i) => $now->copy()->subMonthsNoOverflow($i));
            $data['chart'] = [
                'labels' => $months->map(fn ($m) => $m->translatedFormat('M Y'))->values(),
                'sales' => $months->map(fn ($m) => (float) Invoice::whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('total'))->values(),
                'purchases' => $months->map(fn ($m) => (float) PurchaseOrder::whereNot('status', 'cancelled')->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('total'))->values(),
            ];
        }

        return view('dashboard', $data);
    }
}
