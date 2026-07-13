@php use App\Models\Setting; @endphp
<x-app-layout>
    <x-slot name="header">Pengaturan</x-slot>

    <x-page-header title="Pengaturan" subtitle="Profil perusahaan & konfigurasi pajak (untuk kop surat & faktur)" />

    <div x-data="{ tab: 'company' }" class="space-y-5">
        <div class="flex gap-2 border-b border-gray-200">
            <button @click="tab='company'" :class="tab==='company' ? 'border-gold text-gold-700' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-2 text-sm font-medium">Profil Perusahaan</button>
            <button @click="tab='tax'" :class="tab==='tax' ? 'border-gold text-gold-700' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-2 text-sm font-medium">Pajak & Faktur</button>
            <button @click="tab='bank'" :class="tab==='bank' ? 'border-gold text-gold-700' : 'border-transparent text-gray-500'" class="border-b-2 px-4 py-2 text-sm font-medium">Pembayaran & Tanda Tangan</button>
        </div>

        {{-- Profil Perusahaan --}}
        <div x-show="tab==='company'" class="card max-w-3xl">
            <form method="POST" action="{{ route('settings.company') }}">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="form-label">Nama Perusahaan <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name', Setting::get('company_name')) }}" class="form-input" required>
                        <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">NPWP</label>
                        <input type="text" name="company_npwp" value="{{ old('company_npwp', Setting::get('company_npwp')) }}" class="form-input" placeholder="Wajib untuk faktur pajak">
                    </div>
                    <div>
                        <label class="form-label">NIB</label>
                        <input type="text" name="company_nib" value="{{ old('company_nib', Setting::get('company_nib')) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">No. Izin Distributor Alkes</label>
                        <input type="text" name="company_izin" value="{{ old('company_izin', Setting::get('company_izin')) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">KBLI</label>
                        <input type="text" name="company_kbli" value="{{ old('company_kbli', Setting::get('company_kbli')) }}" class="form-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Alamat</label>
                        <textarea name="company_address" rows="2" class="form-input">{{ old('company_address', Setting::get('company_address')) }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">Telepon</label>
                        <input type="text" name="company_phone" value="{{ old('company_phone', Setting::get('company_phone')) }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input type="email" name="company_email" value="{{ old('company_email', Setting::get('company_email')) }}" class="form-input">
                        <x-input-error :messages="$errors->get('company_email')" class="mt-1" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Penanggung Jawab Teknis (PJT)</label>
                        <input type="text" name="company_pjt" value="{{ old('company_pjt', Setting::get('company_pjt')) }}" class="form-input">
                    </div>
                </div>
                <div class="mt-6"><button class="btn-gold">Simpan Profil</button></div>
            </form>
        </div>

        {{-- Pajak --}}
        <div x-show="tab==='tax'" x-cloak class="card max-w-2xl">
            <form method="POST" action="{{ route('settings.tax') }}">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Tarif PPN (%) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="ppn_rate" value="{{ old('ppn_rate', Setting::get('ppn_rate', 11)) }}" class="form-input" min="0" max="100" required>
                        <x-input-error :messages="$errors->get('ppn_rate')" class="mt-1" />
                    </div>
                    <div>
                        <label class="form-label">Kode Transaksi FP <span class="text-red-500">*</span></label>
                        <input type="text" name="fp_transaction_code" value="{{ old('fp_transaction_code', Setting::get('fp_transaction_code', '04')) }}" class="form-input" placeholder="mis. 04" required>
                        <x-input-error :messages="$errors->get('fp_transaction_code')" class="mt-1" />
                        <p class="mt-1 text-xs text-gray-400">Kode transaksi Faktur Pajak sesuai DJP (01, 04, dst).</p>
                    </div>
                    <div>
                        <label class="form-label">Prefix Nomor FP</label>
                        <input type="text" name="fp_prefix" value="{{ old('fp_prefix', Setting::get('fp_prefix')) }}" class="form-input" placeholder="opsional">
                    </div>
                </div>
                <div class="mt-6"><button class="btn-gold">Simpan Pajak</button></div>
            </form>
        </div>

        {{-- Rekening & penanda tangan (dipakai di cetakan invoice) --}}
        <div x-show="tab==='bank'" x-cloak class="card max-w-3xl">
            <form method="POST" action="{{ route('settings.bank') }}">
                @csrf @method('PUT')
                <h3 class="mb-3 font-semibold text-navy">Rekening Pembayaran</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Nama Bank</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', Setting::get('bank_name')) }}" class="form-input" placeholder="mis. Bank BCA">
                    </div>
                    <div>
                        <label class="form-label">No. Rekening</label>
                        <input type="text" name="bank_account_no" value="{{ old('bank_account_no', Setting::get('bank_account_no')) }}" class="form-input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="form-label">Atas Nama (A/N)</label>
                        <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', Setting::get('bank_account_holder')) }}" class="form-input">
                    </div>
                </div>

                <h3 class="mb-3 mt-6 border-t border-gray-100 pt-5 font-semibold text-navy">Penanda Tangan Dokumen</h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label">Nama Penanda Tangan</label>
                        <input type="text" name="director_name" value="{{ old('director_name', Setting::get('director_name')) }}" class="form-input">
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-400">Nama ini muncul di kolom tanda tangan "Hormat kami" pada cetakan invoice (tanpa jabatan).</p>
                <div class="mt-6"><button class="btn-gold">Simpan</button></div>
            </form>
        </div>
    </div>
</x-app-layout>
