@php
    $reports = [
        ['route' => 'reports.sales', 'label' => 'Laporan Penjualan', 'desc' => 'Rekap penjualan per periode', 'perm' => 'reports.operational', 'icon' => 'M3 3v18h18M7 14l3-3 4 4 5-6'],
        ['route' => 'reports.stock', 'label' => 'Laporan Stok', 'desc' => 'Stok saat ini & kartu stok', 'perm' => 'reports.operational', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7'],
        ['route' => 'reports.receivables', 'label' => 'Laporan Piutang', 'desc' => 'Outstanding & aging 30/60/90', 'perm' => 'reports.operational', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2'],
        ['route' => 'reports.tax-recap', 'label' => 'Rekap PPN', 'desc' => 'Faktur keluaran vs masukan', 'perm' => 'reports.operational', 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21l-7-3-7 3V5a2 2 0 012-2h10a2 2 0 012 2z'],
        ['route' => 'reports.purchases', 'label' => 'Laporan Pembelian', 'desc' => 'Rekap pembelian per periode', 'perm' => 'reports.finance', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0l-2 5H6l-2-5m16 0h-5l-1 2H9l-1-2H4'],
        ['route' => 'reports.payables', 'label' => 'Laporan Hutang', 'desc' => 'Outstanding ke supplier & aging', 'perm' => 'reports.finance', 'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2'],
        ['route' => 'reports.profit-loss', 'label' => 'Laporan Laba Rugi', 'desc' => 'Pendapatan, HPP, laba kotor', 'perm' => 'reports.finance', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 10v1'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Laporan</x-slot>

    <x-page-header title="Laporan" subtitle="Pilih laporan yang ingin ditampilkan / di-export" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($reports as $r)
            @can($r['perm'])
                <a href="{{ route($r['route']) }}" class="card group flex items-start gap-4 transition hover:shadow-gold">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gold-gradient text-navy-dark">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $r['icon'] }}"/></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-navy group-hover:text-gold-700">{{ $r['label'] }}</p>
                        <p class="text-sm text-gray-500">{{ $r['desc'] }}</p>
                    </div>
                </a>
            @endcan
        @endforeach
    </div>
</x-app-layout>
