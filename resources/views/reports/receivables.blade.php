<x-app-layout>
    <x-slot name="header">Laporan Piutang</x-slot>
    <x-page-header title="Laporan Piutang" subtitle="Outstanding pelanggan & umur piutang" />

    <x-report-filter :route="route('reports.receivables')" :dates="false" />

    <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
        @foreach ($buckets as $label => $val)
            <div class="card">
                <p class="text-xs text-gray-400">{{ $label }}</p>
                <p class="text-base font-bold {{ str_contains($label, '90') ? 'text-red-600' : 'text-navy' }}">Rp {{ number_format($val,0,',','.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="card p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="table-th">No. Invoice</th><th class="table-th">Pelanggan</th><th class="table-th">Jatuh Tempo</th>
                <th class="table-th text-right">Total</th><th class="table-th text-right">Dibayar</th><th class="table-th text-right">Sisa</th><th class="table-th text-center">Umur</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($aged as $i)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-mono text-xs">{{ $i->invoice_number }}</td>
                        <td class="table-td">{{ $i->salesOrder->customer->name }}</td>
                        <td class="table-td text-gray-500">{{ $i->due_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="table-td text-right">Rp {{ number_format($i->total,0,',','.') }}</td>
                        <td class="table-td text-right text-green-600">Rp {{ number_format($i->paid_amount,0,',','.') }}</td>
                        <td class="table-td text-right font-medium text-red-600">Rp {{ number_format($i->outstanding,0,',','.') }}</td>
                        <td class="table-td text-center"><span class="badge {{ str_contains($i->bucket,'90') ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600' }}">{{ $i->bucket }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Tidak ada piutang.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
