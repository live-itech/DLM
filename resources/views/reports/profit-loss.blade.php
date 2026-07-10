<x-app-layout>
    <x-slot name="header">Laporan Laba Rugi</x-slot>
    <x-page-header title="Laporan Laba Rugi" :subtitle="$from->format('d/m/Y') . ' – ' . $to->format('d/m/Y')" />

    <x-report-filter :route="route('reports.profit-loss')" :from="$from" :to="$to" />

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <table class="min-w-full">
                <tbody class="divide-y divide-gray-100 text-sm">
                    <tr><td class="py-3 text-gray-600">Pendapatan (DPP dari {{ $count }} invoice)</td><td class="py-3 text-right font-medium text-navy">Rp {{ number_format($revenue,0,',','.') }}</td></tr>
                    <tr><td class="py-3 text-gray-600">Harga Pokok Penjualan (HPP / modal barang terjual)</td><td class="py-3 text-right font-medium text-red-600">(Rp {{ number_format($cogs,0,',','.') }})</td></tr>
                    <tr class="bg-gold-50"><td class="py-3 pl-2 font-semibold text-navy">Laba Kotor</td><td class="py-3 pr-2 text-right text-lg font-bold text-gold-700">Rp {{ number_format($grossProfit,0,',','.') }}</td></tr>
                    <tr><td class="py-3 text-gray-500">Margin</td><td class="py-3 text-right text-gray-600">{{ number_format($margin,1) }}%</td></tr>
                    <tr><td class="py-3 text-gray-400">PPN Keluaran (bukan pendapatan)</td><td class="py-3 text-right text-gray-400">Rp {{ number_format($ppnOut,0,',','.') }}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="space-y-4">
            <div class="card bg-navy-dark text-white">
                <p class="text-xs uppercase text-gray-400">Laba Kotor Periode</p>
                <p class="mt-1 text-2xl font-bold text-gold">Rp {{ number_format($grossProfit,0,',','.') }}</p>
                <p class="mt-1 text-sm text-gray-400">Margin {{ number_format($margin,1) }}%</p>
            </div>
            <div class="card text-sm text-gray-500">
                <p><strong class="text-navy">Catatan:</strong> HPP dihitung dari harga modal (cost) tiap produk terjual. Laba bersih memerlukan pengurangan biaya operasional (belum termasuk di sini).</p>
            </div>
        </div>
    </div>
</x-app-layout>
