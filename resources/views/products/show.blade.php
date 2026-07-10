<x-app-layout>
    <x-slot name="header">Detail Produk</x-slot>

    <x-page-header :title="$product->name" :subtitle="$product->code">
        <x-slot name="action">
            @can('products.update')
                <a href="{{ route('products.edit', $product) }}" class="btn-outline">Edit</a>
            @endcan
            <a href="{{ route('products.index') }}" class="btn-outline">Kembali</a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="card flex items-center justify-center">
            @if ($product->image)
                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="max-h-56 rounded-lg object-contain">
            @else
                <div class="flex h-40 w-full items-center justify-center rounded-lg bg-gray-50 text-gray-300">
                    <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            @endif
        </div>

        <div class="card lg:col-span-2">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-xs uppercase tracking-wide text-gray-400">Kategori</dt><dd class="text-sm font-medium text-navy">{{ $product->category?->name ?? '—' }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-gray-400">Satuan</dt><dd class="text-sm font-medium text-navy">{{ $product->unit?->name ?? '—' }}</dd></div>
                <div>
                    <dt class="text-xs uppercase tracking-wide text-gray-400">Stok Saat Ini</dt>
                    <dd class="text-sm font-medium {{ $product->is_low_stock ? 'text-red-600' : 'text-navy' }}">
                        {{ rtrim(rtrim(number_format($product->stock, 2, ',', '.'), '0'), ',') }} {{ $product->unit?->symbol }}
                        @if ($product->is_low_stock)<span class="badge ml-1 bg-red-100 text-red-600">di bawah minimum</span>@endif
                    </dd>
                </div>
                <div><dt class="text-xs uppercase tracking-wide text-gray-400">Stok Minimum</dt><dd class="text-sm font-medium text-navy">{{ rtrim(rtrim(number_format($product->min_stock, 2, ',', '.'), '0'), ',') }} {{ $product->unit?->symbol }}</dd></div>
                @can('reports.finance')
                    <div><dt class="text-xs uppercase tracking-wide text-gray-400">Harga Beli (Modal)</dt><dd class="text-sm font-medium text-navy">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</dd></div>
                @endcan
                <div><dt class="text-xs uppercase tracking-wide text-gray-400">Harga Jual</dt><dd class="text-sm font-medium text-navy">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</dd></div>
                @can('reports.finance')
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-400">Margin</dt>
                        <dd class="text-sm font-medium text-navy">
                            Rp {{ number_format($product->sell_price - $product->cost_price, 0, ',', '.') }}
                            @if ($product->sell_price > 0)
                                <span class="text-gray-400">({{ number_format(($product->sell_price - $product->cost_price) / $product->sell_price * 100, 1) }}%)</span>
                            @endif
                        </dd>
                    </div>
                @endcan
                <div><dt class="text-xs uppercase tracking-wide text-gray-400">Status</dt><dd>@if ($product->is_active)<span class="badge bg-green-100 text-green-700">Aktif</span>@else<span class="badge bg-gray-100 text-gray-500">Nonaktif</span>@endif</dd></div>
                @if ($product->description)
                    <div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wide text-gray-400">Deskripsi</dt><dd class="text-sm text-gray-600">{{ $product->description }}</dd></div>
                @endif
            </dl>
        </div>
    </div>
</x-app-layout>
