@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'shipped' => 'bg-indigo-100 text-indigo-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Sales Order</x-slot>

    <x-page-header title="Sales Order" subtitle="Pesanan penjualan">
        <x-slot name="action">
            @can('sales-orders.create')
                <a href="{{ route('sales-orders.create') }}" class="btn-gold">+ Buat Sales Order</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. SO / pelanggan..." class="form-input max-w-xs">
                <select name="status" class="form-input max-w-[12rem]">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\SalesOrder::STATUSES as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn-outline">Filter</button>
                @if (request()->hasAny(['search', 'status']))<a href="{{ route('sales-orders.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">No. SO</th>
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Pelanggan</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-center">Status</th>
                        <th class="table-th text-center">Invoice</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $so)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs font-medium text-navy">{{ $so->so_number }}</td>
                            <td class="table-td text-gray-500">{{ $so->date->format('d/m/Y') }}</td>
                            <td class="table-td">{{ $so->customer->name }}</td>
                            <td class="table-td text-right font-medium text-navy">Rp {{ number_format($so->total, 0, ',', '.') }}</td>
                            <td class="table-td text-center"><span class="badge {{ $statusColor[$so->status] ?? 'bg-gray-100' }}">{{ $so->status_label }}</span></td>
                            <td class="table-td text-center">
                                @if ($so->invoice)<span class="badge bg-green-50 text-green-700">✓</span>@else<span class="text-gray-300">—</span>@endif
                            </td>
                            <td class="table-td text-right">
                                <a href="{{ route('sales-orders.show', $so) }}" class="text-gold-700 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Belum ada sales order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())<div class="border-t border-gray-100 p-4">{{ $orders->links() }}</div>@endif
    </div>
</x-app-layout>
