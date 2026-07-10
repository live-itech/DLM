@php
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->code . ' — ' . $p->name,
        'price' => (float) $p->sell_price, 'unit' => $p->unit?->symbol ?? '',
    ])->values();
    $itemsJson = $order->exists ? $order->items->map(fn ($i) => [
        'product_id' => $i->product_id, 'qty' => (float) $i->qty, 'price' => (float) $i->sell_price,
        'discount' => (float) $i->discount, 'discount_reason' => $i->discount_reason,
    ])->values() : collect();
@endphp
<x-app-layout>
    <x-slot name="header">{{ $order->exists ? 'Edit' : 'Buat' }} Sales Order</x-slot>

    <x-page-header :title="($order->exists ? 'Edit' : 'Buat') . ' Sales Order'" subtitle="Pesanan penjualan ke pelanggan" />

    <form method="POST" action="{{ $order->exists ? route('sales-orders.update', $order) : route('sales-orders.store') }}"
          x-data="orderForm({ products: {{ Illuminate\Support\Js::from($productsJson) }}, items: {{ Illuminate\Support\Js::from($itemsJson) }}, priceField: 'sell_price', isTaxable: {{ old('is_taxable', $order->is_taxable) ? 'true' : 'false' }}, ppnRate: {{ old('ppn_rate', $order->ppn_rate ?: 11) }}, discount: {{ old('discount', $order->discount ?: 0) }}, withReason: true })">
        @csrf
        @if ($order->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            {{-- Kiri: header + item --}}
            <div class="space-y-5 lg:col-span-2">
                <div class="card grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                        <select name="customer_id" class="form-input" required>
                            <option value="">— Pilih pelanggan —</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $order->customer_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('customer_id')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', optional($order->date)->toDateString() ?? now()->toDateString()) }}" class="form-input" required>
                        <x-input-error :messages="$errors->get('date')" class="mt-1" />
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
                                                <template x-for="p in products" :key="p.id">
                                                    <option :value="p.id" x-text="p.name"></option>
                                                </template>
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

                <div class="card">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-input">{{ old('notes', $order->notes) }}</textarea>
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

                    <div class="flex flex-col gap-2 pt-2">
                        <button type="submit" class="btn-gold w-full">Simpan (Draft)</button>
                        <a href="{{ route('sales-orders.index') }}" class="btn-outline w-full">Batal</a>
                    </div>
                    <p class="text-xs text-gray-400">SO tersimpan sebagai draft. Stok baru dikurangi saat SO dikonfirmasi.</p>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
