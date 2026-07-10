<x-app-layout>
    <x-slot name="header">Detail Faktur Pajak</x-slot>

    <x-page-header :title="$taxInvoice->tax_number" :subtitle="'Faktur Pajak ' . $taxInvoice->type_label">
        <x-slot name="action">
            <a href="{{ route('tax-invoices.index', ['type' => $taxInvoice->type]) }}" class="btn-outline">Kembali</a>
            @if ($taxInvoice->type === 'output')
                <a href="{{ route('tax-invoices.pdf', $taxInvoice) }}" target="_blank" class="btn-gold">Cetak PDF</a>
            @endif
        </x-slot>
    </x-page-header>

    <div class="card max-w-2xl space-y-3">
        <div class="flex justify-between text-sm"><span class="text-gray-500">Tipe</span><span class="badge {{ $taxInvoice->type === 'output' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">{{ $taxInvoice->type_label }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">Nomor Faktur Pajak</span><span class="font-mono font-medium text-navy">{{ $taxInvoice->tax_number }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">Tanggal</span><span>{{ $taxInvoice->date->format('d M Y') }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">{{ $taxInvoice->type === 'output' ? 'Pembeli' : 'Penjual' }}</span><span class="font-medium text-navy">{{ $taxInvoice->partner_name }}</span></div>
        <div class="flex justify-between text-sm"><span class="text-gray-500">NPWP Lawan Transaksi</span><span>{{ $taxInvoice->partner_npwp ?: '—' }}</span></div>
        @if ($taxInvoice->invoice)
            <div class="flex justify-between text-sm"><span class="text-gray-500">Invoice Terkait</span>
                <a href="{{ route('invoices.show', $taxInvoice->invoice) }}" class="text-gold-700 hover:underline">{{ $taxInvoice->invoice->invoice_number }}</a>
            </div>
        @endif
        @if ($taxInvoice->purchaseOrder)
            <div class="flex justify-between text-sm"><span class="text-gray-500">PO Terkait</span>
                <a href="{{ route('purchase-orders.show', $taxInvoice->purchaseOrder) }}" class="text-gold-700 hover:underline">{{ $taxInvoice->purchaseOrder->po_number }}</a>
            </div>
        @endif
        <div class="space-y-1.5 border-t border-gray-100 pt-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">DPP</span><span>Rp {{ number_format($taxInvoice->dpp,0,',','.') }}</span></div>
            <div class="flex justify-between font-semibold"><span class="text-gray-500">PPN ({{ rtrim(rtrim(number_format($taxInvoice->ppn_rate,2),'0'),'.') }}%)</span><span class="text-gold-700">Rp {{ number_format($taxInvoice->ppn,0,',','.') }}</span></div>
        </div>
        @if ($taxInvoice->notes)<div class="border-t border-gray-100 pt-3 text-sm text-gray-600">{{ $taxInvoice->notes }}</div>@endif
    </div>
</x-app-layout>
