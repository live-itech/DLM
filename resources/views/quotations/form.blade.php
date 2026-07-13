@php
    $productsJson = $products->map(fn ($p) => [
        'id' => $p->id, 'name' => $p->code . ' — ' . $p->name,
        'price' => (float) $p->sell_price, 'unit' => $p->unit?->symbol ?? '',
    ])->values();
    $itemsJson = $quotation->exists ? $quotation->items->map(fn ($i) => [
        'product_id' => $i->product_id, 'qty' => (float) $i->qty, 'price' => (float) $i->sell_price,
        'discount' => (float) $i->discount,
    ])->values() : collect();
@endphp
<x-app-layout>
    <x-slot name="header">{{ $quotation->exists ? 'Edit' : 'Buat' }} Surat Penawaran</x-slot>

    <x-page-header :title="($quotation->exists ? 'Edit' : 'Buat') . ' Surat Penawaran'" subtitle="Penawaran harga ke calon pelanggan" />

    <form method="POST" action="{{ $quotation->exists ? route('quotations.update', $quotation) : route('quotations.store') }}"
          x-data="orderForm({ products: {{ Illuminate\Support\Js::from($productsJson) }}, items: {{ Illuminate\Support\Js::from($itemsJson) }}, priceField: 'sell_price', isTaxable: {{ old('is_taxable', $quotation->is_taxable) ? 'true' : 'false' }}, ppnRate: {{ old('ppn_rate', $quotation->ppn_rate ?: 11) }}, discount: {{ old('discount', $quotation->discount ?: 0) }}, withReason: false })">
        @csrf
        @if ($quotation->exists) @method('PUT') @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                <div class="card grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Pelanggan <span class="text-red-500">*</span></label>
                        <select name="customer_id" class="form-input" required>
                            <option value="">— Pilih pelanggan —</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $quotation->customer_id) == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('customer_id')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="date" value="{{ old('date', optional($quotation->date)->toDateString() ?? now()->toDateString()) }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">Berlaku s/d</label>
                            <input type="date" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until)->toDateString()) }}" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="card p-0">
                    <div class="flex items-center justify-between border-b border-gray-100 p-4">
                        <h3 class="font-semibold text-navy">Item Produk</h3>
                        <button type="button" @click="addRow()" class="btn-outline text-sm">+ Tambah Baris</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50"><tr>
                                <th class="table-th">Produk</th><th class="table-th text-right">Qty</th>
                                <th class="table-th text-right">Harga</th><th class="table-th text-right">Diskon</th>
                                <th class="table-th text-right">Subtotal</th><th class="table-th"></th>
                            </tr></thead>
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
                                        <td class="px-3 py-2 align-top text-right"><button type="button" @click="removeRow(i)" class="text-red-500 hover:text-red-700">&times;</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <x-input-error :messages="$errors->get('items')" class="p-4" />
                </div>

                <div class="card">
                    <label class="form-label">Syarat &amp; Ketentuan</label>
                    <textarea name="terms" rows="3" class="form-input">{{ old('terms', $quotation->terms) }}</textarea>
                    <label class="form-label mt-3">Catatan Internal</label>
                    <textarea name="notes" rows="2" class="form-input">{{ old('notes', $quotation->notes) }}</textarea>
                </div>
            </div>

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
                        <div class="flex justify-between"><span class="text-gray-500">DPP</span><span x-text="rp(dpp)"></span></div>
                        <div class="flex justify-between" x-show="isTaxable"><span class="text-gray-500">PPN (<span x-text="ppnRate"></span>%)</span><span x-text="rp(ppn)"></span></div>
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-navy"><span>Total</span><span x-text="rp(total)"></span></div>
                    </div>
                    <div class="flex flex-col gap-2 pt-2">
                        <button type="submit" class="btn-gold w-full">Simpan</button>
                        <a href="{{ route('quotations.index') }}" class="btn-outline w-full">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
