<x-app-layout>
    <x-slot name="header">Laporan Pembelian</x-slot>
    <x-page-header title="Laporan Pembelian" :subtitle="$from->format('d/m/Y') . ' – ' . $to->format('d/m/Y')" />

    <x-report-filter :route="route('reports.purchases')" :from="$from" :to="$to" />

    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="card"><p class="text-xs uppercase text-gray-400">Jml PO</p><p class="text-xl font-bold text-navy">{{ $summary['count'] }}</p></div>
        <div class="card"><p class="text-xs uppercase text-gray-400">DPP</p><p class="text-lg font-bold text-navy">Rp {{ number_format($summary['dpp'],0,',','.') }}</p></div>
        <div class="card"><p class="text-xs uppercase text-gray-400">PPN</p><p class="text-lg font-bold text-navy">Rp {{ number_format($summary['ppn'],0,',','.') }}</p></div>
        <div class="card"><p class="text-xs uppercase text-gray-400">Total Belanja</p><p class="text-lg font-bold text-gold-700">Rp {{ number_format($summary['total'],0,',','.') }}</p></div>
    </div>

    <div class="card p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="table-th">Tanggal</th><th class="table-th">No. PO</th><th class="table-th">Supplier</th>
                <th class="table-th text-right">DPP</th><th class="table-th text-right">PPN</th><th class="table-th text-right">Total</th><th class="table-th text-center">Status</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $o)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td text-gray-500">{{ $o->date->format('d/m/Y') }}</td>
                        <td class="table-td font-mono text-xs">{{ $o->po_number }}</td>
                        <td class="table-td">{{ $o->supplier->name }}</td>
                        <td class="table-td text-right">Rp {{ number_format($o->dpp,0,',','.') }}</td>
                        <td class="table-td text-right">Rp {{ number_format($o->ppn,0,',','.') }}</td>
                        <td class="table-td text-right font-medium text-navy">Rp {{ number_format($o->total,0,',','.') }}</td>
                        <td class="table-td text-center text-xs">{{ $o->status_label }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Tidak ada pembelian di periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
