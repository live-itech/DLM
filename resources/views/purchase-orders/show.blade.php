@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600', 'ordered' => 'bg-blue-100 text-blue-700',
        'partial' => 'bg-amber-100 text-amber-700', 'received' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Detail Purchase Order</x-slot>

    <x-page-header :title="$order->po_number" :subtitle="'Dibuat ' . $order->created_at->format('d/m/Y H:i')">
        <x-slot name="action">
            <a href="{{ route('purchase-orders.index') }}" class="btn-outline">Kembali</a>
            @can('purchase-orders.update')
                @if ($order->isEditable())
                    <a href="{{ route('purchase-orders.edit', $order) }}" class="btn-outline">Edit</a>
                    <form method="POST" action="{{ route('purchase-orders.order', $order) }}" x-data @submit.prevent="if(confirm('Konfirmasi & pesan PO ini?')) $el.submit()">@csrf<button class="btn-gold">Konfirmasi Pesanan</button></form>
                @endif
                @if (in_array($order->status, ['ordered','partial']))
                    <a href="{{ route('purchase-orders.receive', $order) }}" class="btn-gold">Terima Barang</a>
                @endif
                @if (! in_array($order->status, ['received','cancelled']) && ! $order->receipts()->exists())
                    <form method="POST" action="{{ route('purchase-orders.cancel', $order) }}" x-data @submit.prevent="if(confirm('Batalkan PO ini?')) $el.submit()">@csrf<button class="btn-outline text-red-600">Batalkan</button></form>
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-0">
                <div class="border-b border-gray-100 p-4"><h3 class="font-semibold text-navy">Item Produk</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="table-th">Produk</th><th class="table-th text-right">Qty</th><th class="table-th text-right">Diterima</th>
                            <th class="table-th text-right">Harga</th><th class="table-th text-right">Subtotal</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="table-td font-medium text-navy">{{ $item->product->name }}</td>
                                    <td class="table-td text-right">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                                    <td class="table-td text-right {{ $item->qty_received >= $item->qty ? 'text-green-600' : 'text-amber-600' }}">{{ rtrim(rtrim(number_format($item->qty_received,2,',','.'),'0'),',') }}</td>
                                    <td class="table-td text-right">Rp {{ number_format($item->cost_price,0,',','.') }}</td>
                                    <td class="table-td text-right font-medium">Rp {{ number_format($item->subtotal,0,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Riwayat penerimaan --}}
            @if ($order->receipts->isNotEmpty())
                <div class="card">
                    <h3 class="mb-2 font-semibold text-navy">Riwayat Penerimaan Barang</h3>
                    <div class="space-y-2">
                        @foreach ($order->receipts as $gr)
                            <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <span class="font-mono text-gray-600">{{ $gr->gr_number }}</span>
                                <span class="text-gray-500">{{ $gr->date->format('d/m/Y') }} · {{ $gr->items->count() }} item</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Pembayaran hutang --}}
            <div class="card" x-data="{ open: false }">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-semibold text-navy">Pembayaran ke Supplier (Hutang)</h3>
                    @can('purchase-orders.update')
                        @if ($order->outstanding > 0 && ! in_array($order->status, ['draft','cancelled']))
                            <button @click="open=!open" class="btn-outline text-sm">+ Bayar</button>
                        @endif
                    @endcan
                </div>
                @can('purchase-orders.update')
                    <form x-show="open" x-cloak method="POST" action="{{ route('purchase-orders.payments.store', $order) }}"
                          class="mb-3 grid grid-cols-1 gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-4"
                          x-data="{ amount: '', outstanding: {{ number_format($order->outstanding, 2, '.', '') }} }">
                        @csrf
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-input" required>
                        <div class="relative">
                            <input type="number" step="0.01" name="amount" x-model="amount" placeholder="Jumlah" class="form-input pr-16" :max="outstanding" required>
                            <button type="button" @click="amount = outstanding"
                                    class="absolute inset-y-1 right-1 rounded-md bg-gold-100 px-2 text-xs font-semibold text-gold-800 hover:bg-gold-200">
                                Lunas
                            </button>
                        </div>
                        <input type="text" name="method" placeholder="Metode (transfer/tunai)" class="form-input">
                        <button class="btn-gold">Simpan</button>
                    </form>
                @endcan
                @forelse ($order->payments as $p)
                    <div class="flex items-center justify-between border-b border-gray-50 py-1.5 text-sm">
                        <span class="text-gray-500">{{ $p->date->format('d/m/Y') }} · {{ $p->method ?: '—' }}</span>
                        <span class="font-medium text-navy">Rp {{ number_format($p->amount,0,',','.') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada pembayaran.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-5">
            <div class="card space-y-3">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Status</span><span class="badge {{ $statusColor[$order->status] ?? 'bg-gray-100' }}">{{ $order->status_label }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Supplier</span><span class="font-medium text-navy">{{ $order->supplier->name }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Tanggal PO</span><span>{{ $order->date->format('d/m/Y') }}</span></div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Jatuh Tempo</span>
                    <span class="{{ $order->is_overdue ? 'font-medium text-red-600' : '' }}">
                        {{ $order->due_date?->format('d/m/Y') ?? '—' }}
                        @if ($order->is_overdue)<span class="badge ml-1 bg-red-100 text-red-600">lewat tempo</span>@endif
                    </span>
                </div>
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
                    <div class="flex justify-between pt-1"><span class="text-gray-500">Dibayar</span><span class="text-green-600">Rp {{ number_format($order->paid_amount,0,',','.') }}</span></div>
                    <div class="flex justify-between font-semibold"><span class="text-gray-500">Sisa Hutang</span><span class="text-red-600">Rp {{ number_format($order->outstanding,0,',','.') }}</span></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
