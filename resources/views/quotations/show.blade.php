@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600', 'sent' => 'bg-blue-100 text-blue-700',
        'accepted' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-600',
        'expired' => 'bg-amber-100 text-amber-700',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Detail Surat Penawaran</x-slot>

    <x-page-header :title="$quotation->quotation_number" :subtitle="'Untuk ' . $quotation->customer->name">
        <x-slot name="action">
            <a href="{{ route('quotations.index') }}" class="btn-outline">Kembali</a>
            <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" class="btn-outline">Cetak PDF</a>
            @can('quotations.update')
                @if ($quotation->isEditable())
                    <a href="{{ route('quotations.edit', $quotation) }}" class="btn-outline">Edit</a>
                @endif
                @if ($quotation->status === 'draft')
                    <form method="POST" action="{{ route('quotations.status', $quotation) }}" x-data>@csrf<input type="hidden" name="status" value="sent"><button class="btn-outline">Tandai Terkirim</button></form>
                @endif
                @if (in_array($quotation->status, ['draft','sent']))
                    <form method="POST" action="{{ route('quotations.status', $quotation) }}" x-data>@csrf<input type="hidden" name="status" value="rejected"><button class="btn-outline text-red-600">Ditolak</button></form>
                @endif
                @if (! $quotation->converted_sales_order_id && in_array($quotation->status, ['draft','sent','accepted']))
                    <form method="POST" action="{{ route('quotations.to-sales-order', $quotation) }}" x-data @submit.prevent="if(confirm('Konversi penawaran ini menjadi Sales Order?')) $el.submit()">@csrf<button class="btn-gold">Terima &amp; Jadikan SO</button></form>
                @endif
            @endcan
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-0">
                <div class="border-b border-gray-100 p-4"><h3 class="font-semibold text-navy">Item Penawaran</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="table-th">Produk</th><th class="table-th text-right">Qty</th>
                            <th class="table-th text-right">Harga</th><th class="table-th text-right">Diskon</th><th class="table-th text-right">Subtotal</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($quotation->items as $item)
                                <tr>
                                    <td class="table-td font-medium text-navy">{{ $item->product->name }}</td>
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
            @if ($quotation->terms)<div class="card"><h3 class="mb-1 font-semibold text-navy">Syarat &amp; Ketentuan</h3><p class="whitespace-pre-line text-sm text-gray-600">{{ $quotation->terms }}</p></div>@endif
        </div>

        <div class="space-y-5">
            <div class="card space-y-3">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Status</span><span class="badge {{ $statusColor[$quotation->status] ?? 'bg-gray-100' }}">{{ $quotation->status_label }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Tanggal</span><span>{{ $quotation->date->format('d/m/Y') }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Berlaku s/d</span><span>{{ $quotation->valid_until?->format('d/m/Y') ?? '—' }}</span></div>
                <div class="space-y-1.5 border-t border-gray-100 pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>Rp {{ number_format($quotation->subtotal,0,',','.') }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">DPP</span><span>Rp {{ number_format($quotation->dpp,0,',','.') }}</span></div>
                    @if ($quotation->is_taxable)
                        <div class="flex justify-between"><span class="text-gray-500">PPN ({{ rtrim(rtrim(number_format($quotation->ppn_rate,2),'0'),'.') }}%)</span><span>Rp {{ number_format($quotation->ppn,0,',','.') }}</span></div>
                    @else
                        <div class="flex justify-between text-gray-400"><span>PPN</span><span>Tanpa PPN</span></div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-navy"><span>Total</span><span>Rp {{ number_format($quotation->total,0,',','.') }}</span></div>
                </div>
            </div>
            @if ($quotation->salesOrder)
                <div class="card">
                    <h3 class="mb-2 font-semibold text-navy">Sudah dikonversi</h3>
                    <a href="{{ route('sales-orders.show', $quotation->salesOrder) }}" class="flex items-center justify-between rounded-lg bg-gold-50 px-3 py-2 hover:bg-gold-100">
                        <span class="font-mono text-sm text-gold-800">{{ $quotation->salesOrder->so_number }}</span>
                        <span class="text-xs text-gold-700">Lihat SO →</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
