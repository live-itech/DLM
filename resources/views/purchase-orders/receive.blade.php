<x-app-layout>
    <x-slot name="header">Penerimaan Barang</x-slot>

    <x-page-header :title="'Terima Barang — ' . $order->po_number" subtitle="Isi jumlah yang diterima. Stok akan bertambah otomatis." />

    <form method="POST" action="{{ route('purchase-orders.receipt.store', $order) }}">
        @csrf
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="card p-0 lg:col-span-2">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="table-th">Produk</th><th class="table-th text-right">Dipesan</th>
                            <th class="table-th text-right">Sudah Diterima</th><th class="table-th text-right">Sisa</th>
                            <th class="table-th text-right">Terima Sekarang</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="table-td font-medium text-navy">{{ $item->product->name }}</td>
                                    <td class="table-td text-right">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                                    <td class="table-td text-right text-gray-500">{{ rtrim(rtrim(number_format($item->qty_received,2,',','.'),'0'),',') }}</td>
                                    <td class="table-td text-right font-medium text-amber-600">{{ rtrim(rtrim(number_format($item->outstanding_qty,2,',','.'),'0'),',') }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <input type="number" step="0.01" min="0" max="{{ $item->outstanding_qty }}"
                                               name="qty[{{ $item->id }}]" value="{{ $item->outstanding_qty }}"
                                               class="form-input w-28 text-right" {{ $item->outstanding_qty <= 0 ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="space-y-5">
                <div class="card space-y-4">
                    <div>
                        <label class="form-label">Tanggal Terima <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-input" required>
                    </div>
                    <div>
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="form-input"></textarea>
                    </div>
                    <button type="submit" class="btn-gold w-full">Simpan Penerimaan</button>
                    <a href="{{ route('purchase-orders.show', $order) }}" class="btn-outline w-full">Batal</a>
                    <p class="text-xs text-gray-400">Anda bisa menerima sebagian; sisanya diterima di penerimaan berikutnya.</p>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
