<x-app-layout>
    <x-slot name="header">{{ $unit->exists ? 'Edit' : 'Tambah' }} Satuan</x-slot>

    <x-page-header :title="($unit->exists ? 'Edit' : 'Tambah') . ' Satuan'" />

    <div class="card max-w-xl">
        <form method="POST" action="{{ $unit->exists ? route('units.update', $unit) : route('units.store') }}">
            @csrf
            @if ($unit->exists) @method('PUT') @endif

            <div class="mb-4">
                <label class="form-label">Nama Satuan <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $unit->name) }}" class="form-input" placeholder="mis. Pieces" required autofocus>
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="mb-4">
                <label class="form-label">Simbol <span class="text-red-500">*</span></label>
                <input type="text" name="symbol" value="{{ old('symbol', $unit->symbol) }}" class="form-input max-w-[10rem]" placeholder="mis. pcs" required>
                <x-input-error :messages="$errors->get('symbol')" class="mt-1" />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-gold">Simpan</button>
                <a href="{{ route('units.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
