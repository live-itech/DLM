<x-app-layout>
    <x-slot name="header">Rekap PPN</x-slot>
    <x-page-header title="Rekap PPN" :subtitle="$from->format('d/m/Y') . ' – ' . $to->format('d/m/Y')" />

    <x-report-filter :route="route('reports.tax-recap')" :from="$from" :to="$to" />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <p class="text-xs uppercase text-gray-400">PPN Keluaran</p>
            <p class="text-xl font-bold text-navy">Rp {{ number_format($ppnOut,0,',','.') }}</p>
            <p class="text-xs text-gray-400">{{ $output->count() }} faktur · DPP Rp {{ number_format($output->sum('dpp'),0,',','.') }}</p>
        </div>
        <div class="card">
            <p class="text-xs uppercase text-gray-400">PPN Masukan</p>
            <p class="text-xl font-bold text-navy">Rp {{ number_format($ppnIn,0,',','.') }}</p>
            <p class="text-xs text-gray-400">{{ $input->count() }} faktur · DPP Rp {{ number_format($input->sum('dpp'),0,',','.') }}</p>
        </div>
        <div class="card {{ $net >= 0 ? 'bg-red-50' : 'bg-green-50' }}">
            <p class="text-xs uppercase text-gray-400">{{ $net >= 0 ? 'PPN Kurang Bayar' : 'PPN Lebih Bayar' }}</p>
            <p class="text-xl font-bold {{ $net >= 0 ? 'text-red-600' : 'text-green-700' }}">Rp {{ number_format(abs($net),0,',','.') }}</p>
            <p class="text-xs text-gray-400">Keluaran − Masukan</p>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach (['Keluaran' => $output, 'Masukan' => $input] as $label => $list)
            <div class="card p-0">
                <div class="border-b border-gray-100 p-3"><h3 class="font-semibold text-navy">Faktur {{ $label }}</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr><th class="table-th">Nomor</th><th class="table-th">Tgl</th><th class="table-th text-right">DPP</th><th class="table-th text-right">PPN</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($list as $ti)
                                <tr><td class="table-td font-mono text-xs">{{ $ti->tax_number }}</td><td class="table-td text-gray-500">{{ $ti->date->format('d/m/Y') }}</td><td class="table-td text-right">Rp {{ number_format($ti->dpp,0,',','.') }}</td><td class="table-td text-right">Rp {{ number_format($ti->ppn,0,',','.') }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="table-td py-6 text-center text-gray-400">Kosong.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-app-layout>
