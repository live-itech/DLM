@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600', 'confirmed' => 'bg-blue-100 text-blue-700',
        'shipped' => 'bg-indigo-100 text-indigo-700', 'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Detail Sales Order</x-slot>

    <x-page-header :title="$order->so_number" :subtitle="'Dibuat ' . $order->created_at->format('d/m/Y H:i')">
        <x-slot name="action">
            <a href="{{ route('sales-orders.index') }}" class="btn-outline">Kembali</a>
            @can('sales-orders.update')
                @if ($order->isEditable())
                    <a href="{{ route('sales-orders.edit', $order) }}" class="btn-outline">Edit</a>
                    <form method="POST" action="{{ route('sales-orders.confirm', $order) }}" x-data @submit.prevent="if(confirm('Konfirmasi SO ini? Stok akan dikurangi.')) $el.submit()">
                        @csrf<button class="btn-gold">Konfirmasi & Kurangi Stok</button>
                    </form>
                @endif
                @if (in_array($order->status, ['confirmed','shipped']) && ! $order->invoice)
                    <form method="POST" action="{{ route('sales-orders.to-invoice', $order) }}" x-data @submit.prevent="if(confirm('Buat invoice dari SO ini?')) $el.submit()">
                        @csrf<button class="btn-gold">Buat Invoice</button>
                    </form>
                @endif
                @if ($order->isCancellable())
                    <form method="POST" action="{{ route('sales-orders.cancel', $order) }}" x-data @submit.prevent="if(confirm('Batalkan SO ini?')) $el.submit()">
                        @csrf<button class="btn-outline text-red-600">Batalkan</button>
                    </form>
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    @error('stock')<div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>@enderror

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-0">
                <div class="border-b border-gray-100 p-4"><h3 class="font-semibold text-navy">Item Produk</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="table-th">Produk</th><th class="table-th text-right">Qty</th>
                            <th class="table-th text-right">Harga</th><th class="table-th text-right">Diskon</th><th class="table-th text-right">Subtotal</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="table-td">
                                        <span class="font-medium text-navy">{{ $item->product->name }}</span>
                                        @if ($item->discount_reason)<span class="block text-xs text-gray-400">Diskon: {{ $item->discount_reason }}</span>@endif
                                    </td>
                                    <td class="table-td text-right">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                                    <td class="table-td text-right">Rp {{ number_format($item->sell_price,0,',','.') }}</td>
                                    <td class="table-td text-right">Rp {{ number_format($item->discount,0,',','.') }}</td>
                                    <td class="table-td text-right font-medium">Rp {{ number_format($item->subtotal,0,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($order->notes)<div class="card"><h3 class="mb-1 font-semibold text-navy">Catatan</h3><p class="text-sm text-gray-600">{{ $order->notes }}</p></div>@endif
        </div>

        <div class="space-y-5">
            <div class="card space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="badge {{ $statusColor[$order->status] ?? 'bg-gray-100' }}">{{ $order->status_label }}</span>
                </div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Pelanggan</span><span class="font-medium text-navy">{{ $order->customer->name }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Tanggal</span><span>{{ $order->date->format('d/m/Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Dibuat oleh</span><span>{{ $order->user?->name ?? '—' }}</span></div>
                <div class="space-y-1.5 border-t border-gray-100 pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($order->subtotal,0,',','.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span>Rp {{ number_format($order->discount,0,',','.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">DPP</span><span>Rp {{ number_format($order->dpp,0,',','.') }}</span></div>
                    @if ($order->is_taxable)
                        <div class="flex justify-between"><span class="text-gray-500">PPN ({{ rtrim(rtrim(number_format($order->ppn_rate,2),'0'),'.') }}%)</span><span>Rp {{ number_format($order->ppn,0,',','.') }}</span></div>
                    @else
                        <div class="flex justify-between text-gray-400"><span>PPN</span><span>Tanpa PPN</span></div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-navy"><span>Total</span><span>Rp {{ number_format($order->total,0,',','.') }}</span></div>
                </div>
            </div>

            @if ($order->invoice)
                <div class="card">
                    <h3 class="mb-2 font-semibold text-navy">Invoice</h3>
                    <a href="{{ route('invoices.show', $order->invoice) }}" class="flex items-center justify-between rounded-lg bg-gold-50 px-3 py-2 hover:bg-gold-100">
                        <span class="font-mono text-sm text-gold-800">{{ $order->invoice->invoice_number }}</span>
                        <span class="badge bg-white text-gold-700">{{ $order->invoice->status_label }}</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
