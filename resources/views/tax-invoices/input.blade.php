@php
    $poJson = $purchaseOrders->map(fn ($po) => [
        'id' => $po->id, 'label' => $po->po_number . ' — ' . $po->supplier->name,
        'partner_name' => $po->supplier->name, 'partner_npwp' => $po->supplier->npwp,
        'dpp' => (float) $po->dpp, 'ppn' => (float) $po->ppn, 'ppn_rate' => (float) $po->ppn_rate,
    ])->values();
@endphp
<x-app-layout>
    <x-slot name="header">Input Faktur Pajak Masukan</x-slot>

    <x-page-header title="Faktur Pajak Masukan" subtitle="Catat faktur pajak dari supplier untuk pelaporan PPN" />

    <div class="card max-w-2xl"
         x-data="{
            pos: {{ Illuminate\Support\Js::from($poJson) }},
            poId: '', dpp: 0, ppnRate: {{ $ppnRate }}, ppn: 0,
            partner: '', npwp: '',
            onPo() {
                const p = this.pos.find(x => String(x.id) === String(this.poId));
                if (p) { this.partner = p.partner_name; this.npwp = p.partner_npwp || ''; this.dpp = p.dpp; this.ppnRate = p.ppn_rate; this.ppn = p.ppn; }
            },
            calc() { this.ppn = Math.round(this.dpp * this.ppnRate / 100 * 100) / 100; }
         }">
        <form method="POST" action="{{ route('tax-invoices.input.store') }}">
            @csrf
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="form-label">Ambil dari Purchase Order (opsional)</label>
                    <select x-model="poId" @change="onPo()" name="purchase_order_id" class="form-input">
                        <option value="">— Manual / tanpa PO —</option>
                        <template x-for="p in pos" :key="p.id"><option :value="p.id" x-text="p.label"></option></template>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Nomor Faktur Pajak (dari supplier) <span class="text-red-500">*</span></label>
                    <input type="text" name="tax_number" value="{{ old('tax_number') }}" class="form-input" placeholder="mis. 010.000-26.00000123" required>
                    <x-input-error :messages="$errors->get('tax_number')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Penjual (Supplier) <span class="text-red-500">*</span></label>
                    <input type="text" name="partner_name" x-model="partner" value="{{ old('partner_name') }}" class="form-input" required>
                    <x-input-error :messages="$errors->get('partner_name')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">NPWP Penjual</label>
                    <input type="text" name="partner_npwp" x-model="npwp" value="{{ old('partner_npwp') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">Tarif PPN (%)</label>
                    <input type="number" step="0.01" name="ppn_rate" x-model.number="ppnRate" @input="calc()" class="form-input">
                </div>
                <div>
                    <label class="form-label">DPP <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="dpp" x-model.number="dpp" @input="calc()" class="form-input text-right" required>
                    <x-input-error :messages="$errors->get('dpp')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">PPN <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="ppn" x-model.number="ppn" class="form-input text-right" required>
                    <x-input-error :messages="$errors->get('ppn')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="mt-6 flex gap-2">
                <button class="btn-gold">Simpan</button>
                <a href="{{ route('tax-invoices.index', ['type' => 'input']) }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
