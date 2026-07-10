<x-app-layout>
    <x-slot name="header">Satuan</x-slot>

    <x-page-header title="Satuan" subtitle="Satuan unit produk (pcs, box, unit, dll)">
        <x-slot name="action">
            @can('units.create')
                <a href="{{ route('units.create') }}" class="btn-gold">+ Tambah Satuan</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari satuan..." class="form-input max-w-xs">
                <button class="btn-outline">Cari</button>
                @if (request('search'))<a href="{{ route('units.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Simbol</th>
                        <th class="table-th text-center">Jml Produk</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($units as $unit)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-navy">{{ $unit->name }}</td>
                            <td class="table-td"><span class="badge bg-gray-100 text-gray-700">{{ $unit->symbol }}</span></td>
                            <td class="table-td text-center"><span class="badge bg-gold-50 text-gold-700">{{ $unit->products_count }}</span></td>
                            <td class="table-td text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @can('units.update')
                                        <a href="{{ route('units.edit', $unit) }}" class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    @can('units.delete')
                                        <x-delete-form :action="route('units.destroy', $unit)" confirm="Hapus satuan {{ $unit->name }}?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="table-td py-10 text-center text-gray-400">Belum ada satuan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($units->hasPages())<div class="border-t border-gray-100 p-4">{{ $units->links() }}</div>@endif
    </div>
</x-app-layout>
