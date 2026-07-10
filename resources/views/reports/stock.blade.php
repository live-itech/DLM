<x-app-layout>
    <x-slot name="header">Laporan Stok</x-slot>
    <x-page-header title="Laporan Stok" subtitle="Stok saat ini per produk" />

    <x-report-filter :route="route('reports.stock')" :dates="false">
        <x-slot name="extra">
            <label class="flex items-center gap-1.5 text-sm text-gray-600">
                <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock')) class="rounded border-gray-300 text-gold focus:ring-gold">
                Hanya stok menipis
            </label>
        </x-slot>
    </x-report-filter>

    @if ($canCost)
        <div class="mb-4 card inline-block">
            <p class="text-xs uppercase text-gray-400">Total Nilai Stok (modal)</p>
            <p class="text-xl font-bold text-gold-700">Rp {{ number_format($stockValue,0,',','.') }}</p>
        </div>
    @endif

    <div class="card p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="table-th">Kode</th><th class="table-th">Produk</th><th class="table-th">Kategori</th>
                <th class="table-th text-right">Stok</th><th class="table-th text-right">Min</th>
                @if ($canCost)<th class="table-th text-right">Nilai Stok</th>@endif
                <th class="table-th text-center">Status</th><th class="table-th text-right">Kartu Stok</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($products as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-mono text-xs text-gray-500">{{ $p->code }}</td>
                        <td class="table-td font-medium text-navy">{{ $p->name }}</td>
                        <td class="table-td text-gray-500">{{ $p->category?->name ?? '—' }}</td>
                        <td class="table-td text-right {{ $p->is_low_stock ? 'font-medium text-red-600' : '' }}">{{ rtrim(rtrim(number_format($p->stock,2,',','.'),'0'),',') }} {{ $p->unit?->symbol }}</td>
                        <td class="table-td text-right text-gray-500">{{ rtrim(rtrim(number_format($p->min_stock,2,',','.'),'0'),',') }}</td>
                        @if ($canCost)<td class="table-td text-right">Rp {{ number_format($p->stock * $p->cost_price,0,',','.') }}</td>@endif
                        <td class="table-td text-center">@if ($p->is_low_stock)<span class="badge bg-red-100 text-red-600">Menipis</span>@else<span class="badge bg-green-100 text-green-700">Aman</span>@endif</td>
                        <td class="table-td text-right"><a href="{{ route('reports.stock-card', $p) }}" class="text-gold-700 hover:underline">Lihat</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="table-td py-10 text-center text-gray-400">Tidak ada produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
