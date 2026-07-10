<x-app-layout>
    <x-slot name="header">{{ $supplier->exists ? 'Edit' : 'Tambah' }} Supplier</x-slot>

    <x-page-header :title="($supplier->exists ? 'Edit' : 'Tambah') . ' Supplier'" />

    <div class="card max-w-2xl">
        <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
            @csrf
            @if ($supplier->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" value="{{ old('code', $supplier->code) }}" class="form-input" placeholder="Kosongkan untuk otomatis (SUP-xxxx)">
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Supplier <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $supplier->name) }}" class="form-input" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Kontak (PIC)</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $supplier->email) }}" class="form-input">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" value="{{ old('npwp', $supplier->npwp) }}" class="form-input" placeholder="Untuk faktur pajak masukan">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" rows="2" class="form-input">{{ old('address', $supplier->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Termin Pembayaran (hari) <span class="text-red-500">*</span></label>
                    <input type="number" name="payment_term_days" value="{{ old('payment_term_days', $supplier->payment_term_days ?? 0) }}" class="form-input max-w-[8rem]" min="0" max="365" required>
                    <x-input-error :messages="$errors->get('payment_term_days')" class="mt-1" />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $supplier->is_active ?? true)) class="rounded border-gray-300 text-gold focus:ring-gold">
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-2">
                <button type="submit" class="btn-gold">Simpan</button>
                <a href="{{ route('suppliers.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
