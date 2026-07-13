@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600', 'ordered' => 'bg-blue-100 text-blue-700',
        'partial' => 'bg-amber-100 text-amber-700', 'received' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Purchase Order</x-slot>

    <x-page-header title="Purchase Order" subtitle="Pesanan pembelian ke supplier">
        <x-slot name="action">
            @can('purchase-orders.create')
                <a href="{{ route('purchase-orders.create') }}" class="btn-gold">+ Buat Purchase Order</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. PO / supplier..." class="form-input max-w-xs">
                <select name="status" class="form-input max-w-[12rem]">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\PurchaseOrder::STATUSES as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn-outline">Filter</button>
                @if (request()->hasAny(['search', 'status']))<a href="{{ route('purchase-orders.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50"><tr>
                    <th class="table-th">No. PO</th><th class="table-th">Tanggal</th><th class="table-th">Jatuh Tempo</th>
                    <th class="table-th">Supplier</th>
                    <th class="table-th text-right">Total</th><th class="table-th text-right">Sisa Hutang</th>
                    <th class="table-th text-center">Status</th><th class="table-th text-center">Pembayaran</th><th class="table-th text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $po)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs font-medium text-navy">{{ $po->po_number }}</td>
                            <td class="table-td text-gray-500">{{ $po->date->format('d/m/Y') }}</td>
                            <td class="table-td {{ $po->is_overdue ? 'font-medium text-red-600' : 'text-gray-500' }}">
                                {{ $po->due_date?->format('d/m/Y') ?? '—' }}
                                @if ($po->is_overdue)<span class="badge ml-1 bg-red-100 text-red-600">lewat tempo</span>@endif
                            </td>
                            <td class="table-td">{{ $po->supplier->name }}</td>
                            <td class="table-td text-right font-medium text-navy">Rp {{ number_format($po->total, 0, ',', '.') }}</td>
                            <td class="table-td text-right {{ $po->outstanding > 0 && $po->status !== 'cancelled' ? 'text-red-600' : 'text-gray-400' }}">
                                {{ $po->status === 'cancelled' ? '—' : 'Rp ' . number_format($po->outstanding, 0, ',', '.') }}
                            </td>
                            <td class="table-td text-center"><span class="badge {{ $statusColor[$po->status] ?? 'bg-gray-100' }}">{{ $po->status_label }}</span></td>
                            <td class="table-td text-center">
                                @if ($po->status !== 'draft' && $po->status !== 'cancelled')
                                    @if ($po->payment_status === 'paid')
                                        <span class="badge bg-green-100 text-green-700">Sudah Bayar</span>
                                    @elseif ($po->payment_status === 'partial')
                                        <span class="badge bg-amber-100 text-amber-700">Bayar Sebagian</span>
                                    @else
                                        <span class="badge bg-red-100 text-red-600">Belum Bayar</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="table-td text-right"><a href="{{ route('purchase-orders.show', $po) }}" class="text-gold-700 hover:underline">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="table-td py-10 text-center text-gray-400">Belum ada purchase order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="border-t border-gray-100 p-4">{{ $orders->links() }}</div>@endif
    </div>
</x-app-layout>
