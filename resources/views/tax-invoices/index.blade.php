<x-app-layout>
    <x-slot name="header">Faktur Pajak</x-slot>

    <x-page-header title="Faktur Pajak" subtitle="Faktur Pajak Keluaran & Masukan (untuk pelaporan PPN)">
        <x-slot name="action">
            @can('tax-invoices.create')
                <a href="{{ route('tax-invoices.input.create') }}" class="btn-outline">+ Input Faktur Masukan</a>
            @endcan
        </x-slot>
    </x-page-header>

    {{-- Tab tipe --}}
    <div class="mb-4 flex gap-2 border-b border-gray-200">
        <a href="{{ route('tax-invoices.index', ['type' => 'output', 'month' => $month]) }}"
           @class(['border-b-2 px-4 py-2 text-sm font-medium', 'border-gold text-gold-700' => $type === 'output', 'border-transparent text-gray-500' => $type !== 'output'])>Keluaran</a>
        <a href="{{ route('tax-invoices.index', ['type' => 'input', 'month' => $month]) }}"
           @class(['border-b-2 px-4 py-2 text-sm font-medium', 'border-gold text-gold-700' => $type === 'input', 'border-transparent text-gray-500' => $type !== 'input'])>Masukan</a>
    </div>

    {{-- Ringkasan periode --}}
    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card"><p class="text-xs uppercase text-gray-400">Jumlah Faktur</p><p class="text-xl font-bold text-navy">{{ $summary['count'] }}</p></div>
        <div class="card"><p class="text-xs uppercase text-gray-400">Total DPP</p><p class="text-xl font-bold text-navy">Rp {{ number_format($summary['dpp'],0,',','.') }}</p></div>
        <div class="card"><p class="text-xs uppercase text-gray-400">Total PPN</p><p class="text-xl font-bold text-gold-700">Rp {{ number_format($summary['ppn'],0,',','.') }}</p></div>
    </div>

    <div class="card p-0">
        <div class="border-b border-gray-100 p-4">
            <form method="GET" class="flex items-center gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <label class="text-sm text-gray-500">Periode</label>
                <input type="month" name="month" value="{{ $month }}" class="form-input max-w-[12rem]">
                <button class="btn-outline">Tampilkan</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50"><tr>
                    <th class="table-th">Nomor Faktur Pajak</th>
                    <th class="table-th">Tanggal</th>
                    <th class="table-th">{{ $type === 'output' ? 'Pembeli' : 'Penjual' }}</th>
                    <th class="table-th">NPWP</th>
                    <th class="table-th text-right">DPP</th>
                    <th class="table-th text-right">PPN</th>
                    <th class="table-th text-right">Aksi</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($taxInvoices as $ti)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs font-medium text-navy">{{ $ti->tax_number }}</td>
                            <td class="table-td text-gray-500">{{ $ti->date->format('d/m/Y') }}</td>
                            <td class="table-td">{{ $ti->partner_name }}</td>
                            <td class="table-td text-gray-500">{{ $ti->partner_npwp ?: '—' }}</td>
                            <td class="table-td text-right">Rp {{ number_format($ti->dpp,0,',','.') }}</td>
                            <td class="table-td text-right font-medium text-gold-700">Rp {{ number_format($ti->ppn,0,',','.') }}</td>
                            <td class="table-td text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('tax-invoices.show', $ti) }}" class="text-gold-700 hover:underline">Detail</a>
                                    @if ($ti->type === 'output')
                                        <a href="{{ route('tax-invoices.pdf', $ti) }}" target="_blank" class="text-gray-500 hover:underline">PDF</a>
                                    @endif
                                    @can('tax-invoices.delete')
                                        <x-delete-form :action="route('tax-invoices.destroy', $ti)" confirm="Hapus faktur pajak {{ $ti->tax_number }}?" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="table-td py-10 text-center text-gray-400">Belum ada faktur pajak {{ $type === 'output' ? 'keluaran' : 'masukan' }} di periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($taxInvoices->hasPages())<div class="border-t border-gray-100 p-4">{{ $taxInvoices->links() }}</div>@endif
    </div>
</x-app-layout>
