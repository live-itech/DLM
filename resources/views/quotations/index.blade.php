@php
    $statusColor = [
        'draft' => 'bg-gray-100 text-gray-600', 'sent' => 'bg-blue-100 text-blue-700',
        'accepted' => 'bg-green-100 text-green-700', 'rejected' => 'bg-red-100 text-red-600',
        'expired' => 'bg-amber-100 text-amber-700',
    ];
@endphp
<x-app-layout>
    <x-slot name="header">Surat Penawaran</x-slot>

    <x-page-header title="Surat Penawaran" subtitle="Penawaran harga ke calon pelanggan">
        <x-slot name="action">
            @can('quotations.create')
                <a href="{{ route('quotations.create') }}" class="btn-gold">+ Buat Penawaran</a>
            @endcan
        </x-slot>
    </x-page-header>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex flex-wrap gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no. / pelanggan..." class="form-input max-w-xs">
                <select name="status" class="form-input max-w-[12rem]">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Quotation::STATUSES as $k => $v)
                        <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn-outline">Filter</button>
                @if (request()->hasAny(['search', 'status']))<a href="{{ route('quotations.index') }}" class="btn-outline">Reset</a>@endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50"><tr>
                    <th class="table-th">No. Penawaran</th><th class="table-th">Tanggal</th><th class="table-th">Berlaku s/d</th>
                    <th class="table-th">Pelanggan</th><th class="table-th text-right">Total</th>
                    <th class="table-th text-center">Status</th><th class="table-th text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($quotations as $q)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs font-medium text-navy">{{ $q->quotation_number }}</td>
                            <td class="table-td text-gray-500">{{ $q->date->format('d/m/Y') }}</td>
                            <td class="table-td {{ $q->is_expired ? 'text-amber-600' : 'text-gray-500' }}">{{ $q->valid_until?->format('d/m/Y') ?? '—' }}</td>
                            <td class="table-td">{{ $q->customer->name }}</td>
                            <td class="table-td text-right font-medium text-navy">Rp {{ number_format($q->total,0,',','.') }}</td>
                            <td class="table-td text-center"><span class="badge {{ $statusColor[$q->status] ?? 'bg-gray-100' }}">{{ $q->status_label }}</span></td>
                            <td class="table-td text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('quotations.pdf', $q) }}" target="_blank" class="text-gray-500 hover:underline">PDF</a>
                                    <a href="{{ route('quotations.show', $q) }}" class="text-gold-700 hover:underline">Detail</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Belum ada surat penawaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($quotations->hasPages())<div class="border-t border-gray-100 p-4">{{ $quotations->links() }}</div>@endif
    </div>
</x-app-layout>
