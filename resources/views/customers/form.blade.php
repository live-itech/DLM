<x-app-layout>
    <x-slot name="header">{{ $customer->exists ? 'Edit' : 'Tambah' }} Pelanggan</x-slot>

    <x-page-header :title="($customer->exists ? 'Edit' : 'Tambah') . ' Pelanggan'" />

    <div class="card max-w-2xl">
        <form method="POST" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}">
            @csrf
            @if ($customer->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Kode</label>
                    <input type="text" name="code" value="{{ old('code', $customer->code) }}" class="form-input" placeholder="Kosongkan untuk otomatis (CUST-xxxx)">
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-input" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Kontak (PIC)</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Telepon</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-input">
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">NPWP</label>
                    <input type="text" name="npwp" value="{{ old('npwp', $customer->npwp) }}" class="form-input" placeholder="Untuk faktur pajak">
                </div>
                <div class="sm:col-span-2">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" rows="2" class="form-input">{{ old('address', $customer->address) }}</textarea>
                </div>
                <div>
                    <label class="form-label">Termin Pembayaran (hari) <span class="text-red-500">*</span></label>
                    <input type="number" name="payment_term_days" value="{{ old('payment_term_days', $customer->payment_term_days ?? 0) }}" class="form-input max-w-[8rem]" min="0" max="365" required>
                    <x-input-error :messages="$errors->get('payment_term_days')" class="mt-1" />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true)) class="rounded border-gray-300 text-gold focus:ring-gold">
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-2">
                <button type="submit" class="btn-gold">Simpan</button>
                <a href="{{ route('customers.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
