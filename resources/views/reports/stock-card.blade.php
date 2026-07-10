<x-app-layout>
    <x-slot name="header">Kartu Stok</x-slot>
    <x-page-header :title="'Kartu Stok: ' . $product->name" :subtitle="$product->code . ' · ' . $from->format('d/m/Y') . ' – ' . $to->format('d/m/Y')">
        <x-slot name="action"><a href="{{ route('reports.stock') }}" class="btn-outline">Kembali</a></x-slot>
    </x-page-header>

    <x-report-filter :route="route('reports.stock-card', $product)" :from="$from" :to="$to" />

    <div class="card p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="table-th">Tanggal</th><th class="table-th">Tipe</th><th class="table-th">Referensi</th>
                <th class="table-th text-right">Masuk</th><th class="table-th text-right">Keluar</th><th class="table-th text-right">Saldo</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($movements as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td text-gray-500">{{ $m->moved_at->format('d/m/Y H:i') }}</td>
                        <td class="table-td">
                            @if ($m->type === 'in')<span class="badge bg-green-100 text-green-700">Masuk</span>
                            @elseif ($m->type === 'out')<span class="badge bg-red-100 text-red-600">Keluar</span>
                            @else<span class="badge bg-gray-100 text-gray-600">Penyesuaian</span>@endif
                        </td>
                        <td class="table-td text-gray-500">{{ $m->reference_type }}</td>
                        <td class="table-td text-right text-green-600">{{ $m->qty > 0 ? rtrim(rtrim(number_format($m->qty,2,',','.'),'0'),',') : '' }}</td>
                        <td class="table-td text-right text-red-600">{{ $m->qty < 0 ? rtrim(rtrim(number_format(abs($m->qty),2,',','.'),'0'),',') : '' }}</td>
                        <td class="table-td text-right font-medium text-navy">{{ rtrim(rtrim(number_format($m->balance_after,2,',','.'),'0'),',') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="table-td py-10 text-center text-gray-400">Tidak ada pergerakan stok di periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
