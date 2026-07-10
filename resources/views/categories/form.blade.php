<x-app-layout>
    <x-slot name="header">{{ $category->exists ? 'Edit' : 'Tambah' }} Kategori</x-slot>

    <x-page-header :title="($category->exists ? 'Edit' : 'Tambah') . ' Kategori'" />

    <div class="card max-w-xl">
        <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
            @csrf
            @if ($category->exists) @method('PUT') @endif

            <div class="mb-4">
                <label class="form-label">Nama Kategori <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-input" required autofocus>
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div class="mb-4">
                <label class="form-label">Deskripsi</label>
                <textarea name="description" rows="3" class="form-input">{{ old('description', $category->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-1" />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn-gold">Simpan</button>
                <a href="{{ route('categories.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
