@php
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->code . ' — ' . $p->name,
        'price' => (float) $p->sell_price, 'unit' => $p->unit?->symbol ?? '',
    ])->values();
    $itemsJson = $order->items->map(fn ($i) => [
        'product_id' => $i->product_id, 'qty' => (float) $i->qty, 'price' => (float) $i->sell_price,
        'discount' => (float) $i->discount, 'discount_reason' => $i->discount_reason,
    ])->values();
    $so = $order;
@endphp
<x-app-layout>
    <x-slot name="header">Edit Invoice</x-slot>

    <x-page-header :title="'Edit Invoice ' . $invoice->invoice_number" :subtitle="'SO: ' . $so->so_number . ' — ' . $so->customer->name" />

    <form method="POST" action="{{ route('invoices.update', $invoice) }}"
          x-data="orderForm({ products: {{ Illuminate\Support\Js::from($productsJson) }}, items: {{ Illuminate\Support\Js::from($itemsJson) }}, priceField: 'sell_price', isTaxable: {{ $so->is_taxable ? 'true' : 'false' }}, ppnRate: {{ $so->ppn_rate ?: 11 }}, discount: {{ $so->discount ?: 0 }}, withReason: true })">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                {{-- Info invoice (read-only) --}}
                <div class="card grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <label class="form-label text-gray-500">Pelanggan</label>
                        <p class="font-medium text-navy">{{ $so->customer->name }}</p>
                    </div>
                    <div>
                        <label class="form-label text-gray-500">No. Invoice</label>
                        <p class="font-medium text-navy">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div>
                        <label class="form-label text-gray-500">Tanggal</label>
                        <p class="font-medium text-navy">{{ $invoice->date->format('d/m/Y') }}</p>
                    </div>
                </div>

                {{-- Item --}}
                <div class="card p-0">
                    <div class="flex items-center justify-between border-b border-gray-100 p-4">
                        <h3 class="font-semibold text-navy">Item Produk</h3>
                        <button type="button" @click="addRow()" class="btn-outline text-sm">+ Tambah Baris</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="table-th">Produk</th>
                                    <th class="table-th text-right">Qty</th>
                                    <th class="table-th text-right">Harga</th>
                                    <th class="table-th text-right">Diskon</th>
                                    <th class="table-th text-right">Subtotal</th>
                                    <th class="table-th"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(row, i) in items" :key="i">
                                    <tr>
                                        <td class="px-3 py-2 align-top">
                                            <select :name="`items[${i}][product_id]`" x-model="row.product_id" @change="onProductChange(row)" class="form-input min-w-[14rem]" required>
                                                <option value="">— Pilih —</option>
                                                @foreach ($products as $p)
                                                    <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                            <template x-if="withReason">
                                                <input :name="`items[${i}][discount_reason]`" x-model="row.discount_reason" placeholder="Alasan diskon (opsional)" class="form-input mt-1 text-xs">
                                            </template>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <div class="flex items-center gap-1">
                                                <input type="number" step="0.01" min="0.01" :name="`items[${i}][qty]`" x-model.number="row.qty" class="form-input w-20 text-right" required>
                                                <span class="text-xs text-gray-400" x-text="productUnit(row)"></span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.01" min="0" :name="`items[${i}][sell_price]`" x-model.number="row.price" class="form-input w-28 text-right" required>
                                        </td>
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="0.01" min="0" :name="`items[${i}][discount]`" x-model.number="row.discount" class="form-input w-24 text-right">
                                        </td>
                                        <td class="px-3 py-2 text-right align-top font-medium text-navy" x-text="rp(rowSubtotal(row))"></td>
                                        <td class="px-3 py-2 align-top text-right">
                                            <button type="button" @click="removeRow(i)" class="text-red-500 hover:text-red-700" title="Hapus baris">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('items')" class="p-4" />
                </div>
            </div>

            {{-- Kanan: ringkasan pajak & total --}}
            <div class="space-y-5">
                <div class="card space-y-4">
                    <h3 class="font-semibold text-navy">Pajak & Total</h3>

                    <label class="flex items-center justify-between rounded-lg bg-gold-50 px-3 py-2">
                        <span class="text-sm font-medium text-gold-800">Kena PPN?</span>
                        <input type="checkbox" name="is_taxable" value="1" x-model="isTaxable" class="rounded border-gray-300 text-gold focus:ring-gold">
                    </label>

                    <div x-show="isTaxable">
                        <label class="form-label">Tarif PPN (%)</label>
                        <input type="number" step="0.01" name="ppn_rate" x-model.number="ppnRate" class="form-input" min="0" max="100">
                    </div>
                    <input x-show="!isTaxable" type="hidden" name="ppn_rate" :value="ppnRate">

                    <div>
                        <label class="form-label">Diskon Dokumen (Rp)</label>
                        <input type="number" step="0.01" name="discount" x-model.number="headerDiscount" class="form-input text-right" min="0">
                    </div>

                    <div class="space-y-1.5 border-t border-gray-100 pt-3 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span x-text="rp(subtotal)"></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span x-text="rp(headerDiscount)"></span></div>
                        <div class="flex justify-between"><span class="text-gray-500">DPP</span><span x-text="rp(dpp)"></span></div>
                        <div class="flex justify-between" x-show="isTaxable"><span class="text-gray-500">PPN (<span x-text="ppnRate"></span>%)</span><span x-text="rp(ppn)"></span></div>
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-navy"><span>Total</span><span x-text="rp(total)"></span></div>
                    </div>

                    @if ((float) $invoice->paid_amount > 0)
                        <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">
                            <strong>Perhatian:</strong> Invoice ini sudah memiliki pembayaran sebesar
                            Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}.
                            Status pembayaran akan disesuaikan otomatis setelah perubahan.
                        </div>
                    @endif

                    <div class="flex flex-col gap-2 pt-2">
                        <button type="submit" class="btn-gold w-full">Simpan Perubahan</button>
                        <a href="{{ route('invoices.show', $invoice) }}" class="btn-outline w-full">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
