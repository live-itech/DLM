@php
    $statusColor = ['unpaid' => 'bg-red-100 text-red-600', 'partial' => 'bg-amber-100 text-amber-700', 'paid' => 'bg-green-100 text-green-700'];
@endphp
<x-app-layout>
    <x-slot name="header">Invoice / Faktur Penjualan</x-slot>

    <x-page-header title="Invoice" subtitle="Faktur penjualan & piutang pelanggan" />

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. invoice / pelanggan..." class="form-input max-w-xs">
                <select name="status" class="form-input max-w-[12rem]">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Invoice::STATUSES as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn-outline">Filter</button>
                @if (request()->hasAny(['search', 'status']))<a href="{{ route('invoices.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50"><tr>
                    <th class="table-th">No. Invoice</th><th class="table-th">Tanggal</th><th class="table-th">Jatuh Tempo</th>
                    <th class="table-th">Pelanggan</th><th class="table-th text-right">Total</th><th class="table-th text-right">Sisa</th>
                    <th class="table-th text-center">Status</th><th class="table-th text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($invoices as $inv)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs font-medium text-navy">{{ $inv->invoice_number }}</td>
                            <td class="table-td text-gray-500">{{ $inv->date->format('d/m/Y') }}</td>
                            <td class="table-td {{ $inv->is_overdue ? 'font-medium text-red-600' : 'text-gray-500' }}">
                                {{ $inv->due_date?->format('d/m/Y') ?? '—' }}
                                @if ($inv->is_overdue)<span class="badge ml-1 bg-red-100 text-red-600">jatuh tempo</span>@endif
                            </td>
                            <td class="table-td">{{ $inv->salesOrder->customer->name }}</td>
                            <td class="table-td text-right font-medium text-navy">Rp {{ number_format($inv->total,0,',','.') }}</td>
                            <td class="table-td text-right {{ $inv->outstanding > 0 ? 'text-red-600' : 'text-gray-400' }}">Rp {{ number_format($inv->outstanding,0,',','.') }}</td>
                            <td class="table-td text-center"><span class="badge {{ $statusColor[$inv->status] ?? 'bg-gray-100' }}">{{ $inv->status_label }}</span></td>
                            <td class="table-td text-right"><a href="{{ route('invoices.show', $inv) }}" class="text-gold-700 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="table-td py-10 text-center text-gray-400">Belum ada invoice.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($invoices->hasPages())<div class="border-t border-gray-100 p-4">{{ $invoices->links() }}</div>@endif
    </div>
</x-app-layout>
