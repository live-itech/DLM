<x-app-layout>
    <x-slot name="header">Pengguna</x-slot>

    <x-page-header title="Pengguna" subtitle="Kelola akun staff & admin">
        <x-slot name="action">
            <a href="{{ route('users.create') }}" class="btn-gold">+ Tambah Pengguna</a>
        </x-slot>
    </x-page-header>

    <div class="card p-0 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50"><tr>
                <th class="table-th">Nama</th><th class="table-th">Email</th><th class="table-th">Role</th>
                <th class="table-th text-center">Status</th><th class="table-th text-right">Aksi</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($users as $u)
                    <tr class="hover:bg-gray-50">
                        <td class="table-td font-medium text-navy">{{ $u->name }}</td>
                        <td class="table-td text-gray-500">{{ $u->email }}</td>
                        <td class="table-td">
                            @foreach ($u->roles as $role)
                                <span class="badge {{ $role->name === 'Admin' ? 'bg-gold-50 text-gold-700' : 'bg-gray-100 text-gray-600' }}">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="table-td text-center">
                            @if ($u->is_active)<span class="badge bg-green-100 text-green-700">Aktif</span>@else<span class="badge bg-red-100 text-red-600">Nonaktif</span>@endif
                        </td>
                        <td class="table-td text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('users.edit', $u) }}" class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                @if ($u->id !== auth()->id())
                                    <x-delete-form :action="route('users.destroy', $u)" confirm="Hapus pengguna {{ $u->name }}?" />
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($users->hasPages())<div class="border-t border-gray-100 p-4">{{ $users->links() }}</div>@endif
    </div>
</x-app-layout>
