<x-app-layout>
    <x-slot name="header">{{ $product->exists ? 'Edit' : 'Tambah' }} Produk</x-slot>

    <x-page-header :title="($product->exists ? 'Edit' : 'Tambah') . ' Produk'" />

    <div class="card max-w-3xl">
        <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($product->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Kode Produk</label>
                    <input type="text" name="code" value="{{ old('code', $product->code) }}" class="form-input" placeholder="Kosongkan untuk otomatis (PRD-xxxx)">
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Nama Produk <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="form-input" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-input">
                        <option value="">— Pilih kategori —</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Satuan</label>
                    <select name="unit_id" class="form-input">
                        <option value="">— Pilih satuan —</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected(old('unit_id', $product->unit_id) == $unit->id)>{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unit_id')" class="mt-1" />
                </div>

                @can('reports.finance')
                    <div>
                        <label class="form-label">Harga Beli (Modal) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                            <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price ?? 0) }}" class="form-input pl-9" min="0" required>
                        </div>
                        <x-input-error :messages="$errors->get('cost_price')" class="mt-1" />
                    </div>
                @endcan
                <div>
                    <label class="form-label">Harga Jual <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                        <input type="number" step="0.01" name="sell_price" value="{{ old('sell_price', $product->sell_price ?? 0) }}" class="form-input pl-9" min="0" required>
                    </div>
                    <x-input-error :messages="$errors->get('sell_price')" class="mt-1" />
                </div>

                @unless ($product->exists)
                    <div>
                        <label class="form-label">Stok Awal <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="stock" value="{{ old('stock', 0) }}" class="form-input" min="0" required>
                        <x-input-error :messages="$errors->get('stock')" class="mt-1" />
                    </div>
                @else
                    <div>
                        <label class="form-label">Stok Saat Ini</label>
                        <input type="text" value="{{ rtrim(rtrim(number_format($product->stock, 2, ',', '.'), '0'), ',') }} {{ $product->unit?->symbol }}" class="form-input bg-gray-50" disabled>
                        <p class="mt-1 text-xs text-gray-400">Stok diubah lewat transaksi pembelian/penjualan.</p>
                    </div>
                @endunless
                <div>
                    <label class="form-label">Stok Minimum <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 0) }}" class="form-input" min="0" required>
                    <x-input-error :messages="$errors->get('min_stock')" class="mt-1" />
                    <p class="mt-1 text-xs text-gray-400">Peringatan muncul jika stok ≤ minimum.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" rows="2" class="form-input">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="form-label">Gambar Produk</label>
                    <div class="flex items-center gap-4">
                        @if ($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="" class="h-16 w-16 rounded-lg border object-cover">
                        @endif
                        <input type="file" name="image" accept="image/*" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gold-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gold-700 hover:file:bg-gold-100">
                    </div>
                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                </div>

                <div class="flex items-center">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true)) class="rounded border-gray-300 text-gold focus:ring-gold">
                        <span class="text-sm text-gray-700">Produk aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-2">
                <button type="submit" class="btn-gold">Simpan</button>
                <a href="{{ route('products.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
