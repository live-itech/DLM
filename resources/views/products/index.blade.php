<x-app-layout>
    <x-slot name="header">Produk</x-slot>

    <x-page-header title="Produk" subtitle="Katalog alat kesehatan">
        <x-slot name="action">
            @can('products.create')
                <a href="{{ route('products.create') }}" class="btn-gold">+ Tambah Produk</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap items-center gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode..." class="form-input max-w-xs">
                <select name="category" class="form-input max-w-[12rem]">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <label class="inline-flex items-center gap-1.5 text-sm text-gray-600">
                    <input type="checkbox" name="low_stock" value="1" @checked(request('low_stock')) class="rounded border-gray-300 text-gold focus:ring-gold">
                    Stok menipis
                </label>
                <button class="btn-outline">Filter</button>
                @if (request()->hasAny(['search', 'category', 'low_stock']))
                    <a href="{{ route('products.index') }}" class="btn-outline">Reset</a>
                @endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Produk</th>
                        <th class="table-th">Kategori</th>
                        <th class="table-th text-right">Stok</th>
                        @can('reports.finance')<th class="table-th text-right">Harga Beli</th>@endcan
                        <th class="table-th text-right">Harga Jual</th>
                        <th class="table-th text-center">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-100">
                                        @if ($product->image)
                                            <img src="{{ asset('storage/' . $product->image) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-navy">{{ $product->name }}</p>
                                        <p class="font-mono text-xs text-gray-400">{{ $product->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                            <td class="table-td text-right">
                                <span @class(['font-medium', 'text-red-600' => $product->is_low_stock, 'text-navy' => ! $product->is_low_stock])>
                                    {{ rtrim(rtrim(number_format($product->stock, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $product->unit?->symbol }}</span>
                                @if ($product->is_low_stock)
                                    <span class="badge ml-1 bg-red-100 text-red-600">menipis</span>
                                @endif
                            </td>
                            @can('reports.finance')
                                <td class="table-td text-right text-gray-500">Rp {{ number_format($product->cost_price, 0, ',', '.') }}</td>
                            @endcan
                            <td class="table-td text-right font-medium text-navy">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                            <td class="table-td text-center">
                                @if ($product->is_active)
                                    <span class="badge bg-green-100 text-green-700">Aktif</span>
                                @else
                                    <span class="badge bg-gray-100 text-gray-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="table-td text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('products.show', $product) }}" class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100" title="Detail">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    @can('products.update')
                                        <a href="{{ route('products.edit', $product) }}" class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    @can('products.delete')
                                        <x-delete-form :action="route('products.destroy', $product)" confirm="Hapus produk {{ $product->name }}?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Belum ada produk.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($products->hasPages())<div class="border-t border-gray-100 p-4">{{ $products->links() }}</div>@endif
    </div>
</x-app-layout>
