<x-app-layout>
    <x-slot name="header">Pelanggan</x-slot>

    <x-page-header title="Pelanggan" subtitle="Daftar pelanggan / customer">
        <x-slot name="action">
            @can('customers.create')
                <a href="{{ route('customers.create') }}" class="btn-gold">+ Tambah Pelanggan</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama / kode / telepon..." class="form-input max-w-sm">
                <button class="btn-outline">Cari</button>
                @if (request('search'))<a href="{{ route('customers.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Kode</th>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Kontak</th>
                        <th class="table-th">NPWP</th>
                        <th class="table-th text-center">Termin</th>
                        <th class="table-th text-center">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs text-gray-500">{{ $customer->code }}</td>
                            <td class="table-td font-medium text-navy">
                                {{ $customer->name }}
                                @if ($customer->contact_person)<span class="block text-xs text-gray-400">{{ $customer->contact_person }}</span>@endif
                            </td>
                            <td class="table-td text-gray-500">{{ $customer->phone ?: '—' }}</td>
                            <td class="table-td text-gray-500">{{ $customer->npwp ?: '—' }}</td>
                            <td class="table-td text-center">{{ $customer->payment_term_days }} hari</td>
                            <td class="table-td text-center">
                                @if ($customer->is_active)
                                    <span class="badge bg-green-100 text-green-700">Aktif</span>
                                @else
                                    <span class="badge bg-gray-100 text-gray-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="table-td text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @can('customers.update')
                                        <a href="{{ route('customers.edit', $customer) }}" class="rounded-md p-1.5 text-gray-500 transition hover:bg-gray-100" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endcan
                                    @can('customers.delete')
                                        <x-delete-form :action="route('customers.destroy', $customer)" confirm="Hapus pelanggan {{ $customer->name }}?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Belum ada pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())<div class="border-t border-gray-100 p-4">{{ $customers->links() }}</div>@endif
    </div>
</x-app-layout>
