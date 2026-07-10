@php
    $growth = ($isFinance ?? false) && ($omzetLastMonth ?? 0) > 0
        ? (($omzetThisMonth - $omzetLastMonth) / $omzetLastMonth * 100) : null;
@endphp
<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    <div class="mb-6 overflow-hidden rounded-xl bg-navy-dark p-6 text-white shadow-sm"
         style="background-image: radial-gradient(circle at 100% 0%, rgba(205,164,94,0.25), transparent 55%);">
        <p class="text-sm text-gray-300">Selamat datang,</p>
        <h2 class="font-display text-2xl font-bold text-gold">{{ Auth::user()->name }}</h2>
        <p class="mt-1 text-sm text-gray-400">{{ Auth::user()?->getRoleNames()->first() ?? 'Staff' }} · PT Dimas Love Medika</p>
    </div>

    {{-- ===== KARTU FINANSIAL (Admin) ===== --}}
    @if ($isFinance)
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="card">
                <p class="text-xs font-medium uppercase text-gray-400">Omzet Bulan Ini</p>
                <p class="mt-1 text-xl font-bold text-navy">Rp {{ number_format($omzetThisMonth,0,',','.') }}</p>
                @if ($growth !== null)
                    <p class="mt-1 text-xs {{ $growth >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $growth >= 0 ? '▲' : '▼' }} {{ number_format(abs($growth),1) }}% vs bulan lalu
                    </p>
                @else
                    <p class="mt-1 text-xs text-gray-400">Bulan lalu: Rp {{ number_format($omzetLastMonth,0,',','.') }}</p>
                @endif
            </div>
            <div class="card">
                <p class="text-xs font-medium uppercase text-gray-400">Belanja (Modal) Bln Ini</p>
                <p class="mt-1 text-xl font-bold text-navy">Rp {{ number_format($purchaseThisMonth,0,',','.') }}</p>
            </div>
            <div class="card">
                <p class="text-xs font-medium uppercase text-gray-400">Total Piutang</p>
                <p class="mt-1 text-xl font-bold text-amber-600">Rp {{ number_format($receivables,0,',','.') }}</p>
            </div>
            <div class="card">
                <p class="text-xs font-medium uppercase text-gray-400">Total Hutang</p>
                <p class="mt-1 text-xl font-bold text-red-600">Rp {{ number_format($payables,0,',','.') }}</p>
            </div>
        </div>
    @endif

    {{-- ===== KARTU OPERASIONAL (semua role) ===== --}}
    <div class="mt-4 grid grid-cols-2 gap-4 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total Produk', 'value' => $stats['products'], 'color' => 'text-navy'],
            ['label' => 'Pelanggan Aktif', 'value' => $stats['customers'], 'color' => 'text-navy'],
            ['label' => 'Invoice Belum Lunas', 'value' => $stats['unpaid_invoices'], 'color' => 'text-amber-600'],
            ['label' => 'Stok Kritis', 'value' => $stats['low_stock'], 'color' => 'text-red-600'],
        ] as $s)
            <div class="card">
                <p class="text-xs font-medium uppercase text-gray-400">{{ $s['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $s['color'] }}">{{ $s['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-5 lg:grid-cols-3">
        {{-- Grafik penjualan vs pembelian (Admin) --}}
        @if ($isFinance)
            <div class="card lg:col-span-2">
                <h3 class="mb-3 font-semibold text-navy">Penjualan vs Pembelian (6 Bulan)</h3>
                <div id="chartSalesPurchase"></div>
            </div>
        @endif

        {{-- Produk terlaris --}}
        <div class="card {{ $isFinance ? '' : 'lg:col-span-2' }}">
            <h3 class="mb-3 font-semibold text-navy">Produk Terlaris</h3>
            @forelse ($topProducts as $tp)
                <div class="flex items-center justify-between border-b border-gray-50 py-2 text-sm">
                    <span class="text-gray-700">{{ $tp->product?->name ?? '—' }}</span>
                    <span class="badge bg-gold-50 text-gold-700">{{ rtrim(rtrim(number_format($tp->total_qty,2,',','.'),'0'),',') }} terjual</span>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-gray-400">Belum ada penjualan.</p>
            @endforelse
        </div>

        {{-- Stok kritis --}}
        <div class="card {{ $isFinance ? 'lg:col-span-3' : '' }}">
            <h3 class="mb-3 font-semibold text-navy">Stok Kritis (≤ minimum)</h3>
            @forelse ($lowStockProducts as $p)
                <div class="flex items-center justify-between border-b border-gray-50 py-2 text-sm">
                    <span class="text-gray-700">{{ $p->name }}</span>
                    <span class="text-red-600">{{ rtrim(rtrim(number_format($p->stock,2,',','.'),'0'),',') }} {{ $p->unit?->symbol }} <span class="text-gray-400">/ min {{ rtrim(rtrim(number_format($p->min_stock,2,',','.'),'0'),',') }}</span></span>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-gray-400">Semua stok aman. 👍</p>
            @endforelse
        </div>
    </div>

    @if ($isFinance)
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const el = document.querySelector('#chartSalesPurchase');
                if (!el || !window.ApexCharts) return;
                new ApexCharts(el, {
                    chart: { type: 'bar', height: 300, fontFamily: 'Figtree, sans-serif', toolbar: { show: false } },
                    series: [
                        { name: 'Penjualan', data: @json($chart['sales']) },
                        { name: 'Pembelian', data: @json($chart['purchases']) },
                    ],
                    colors: ['#CDA45E', '#14233A'],
                    xaxis: { categories: @json($chart['labels']) },
                    yaxis: { labels: { formatter: (v) => 'Rp ' + (v/1000000).toFixed(1) + ' jt' } },
                    plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                    dataLabels: { enabled: false },
                    legend: { position: 'top' },
                    tooltip: { y: { formatter: (v) => 'Rp ' + v.toLocaleString('id-ID') } },
                    grid: { borderColor: '#f1f1f1' },
                }).render();
            });
        </script>
        @endpush
    @endif
</x-app-layout>
