@php
    $statusColor = ['unpaid' => 'bg-red-100 text-red-600', 'partial' => 'bg-amber-100 text-amber-700', 'paid' => 'bg-green-100 text-green-700'];
    $so = $invoice->salesOrder;
@endphp
<x-app-layout>
    <x-slot name="header">Detail Invoice</x-slot>

    <x-page-header :title="$invoice->invoice_number" :subtitle="'Dari SO ' . $so->so_number">
        <x-slot name="action">
            <a href="{{ route('invoices.index') }}" class="btn-outline">Kembali</a>
            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="btn-outline">Cetak PDF</a>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div class="space-y-5 lg:col-span-2">
            <div class="card p-0">
                <div class="border-b border-gray-100 p-4"><h3 class="font-semibold text-navy">Item</h3></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50"><tr>
                            <th class="table-th">Produk</th><th class="table-th text-right">Qty</th>
                            <th class="table-th text-right">Harga</th><th class="table-th text-right">Subtotal</th>
                        </tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($so->items as $item)
                                <tr>
                                    <td class="table-td font-medium text-navy">{{ $item->product->name }}</td>
                                    <td class="table-td text-right">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                                    <td class="table-td text-right">Rp {{ number_format($item->sell_price,0,',','.') }}</td>
                                    <td class="table-td text-right font-medium">Rp {{ number_format($item->subtotal,0,',','.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pembayaran / piutang --}}
            <div class="card" x-data="{ open: false }">
                <div class="mb-2 flex items-center justify-between">
                    <h3 class="font-semibold text-navy">Pembayaran Pelanggan (Piutang)</h3>
                    @can('invoices.update')
                        @if ($invoice->status !== 'paid')
                            <button @click="open=!open" class="btn-outline text-sm">+ Terima Pembayaran</button>
                        @endif
                    @endcan
                </div>
                @can('invoices.update')
                    <form x-show="open" x-cloak method="POST" action="{{ route('invoices.payments.store', $invoice) }}"
                          class="mb-3 grid grid-cols-1 gap-2 rounded-lg bg-gray-50 p-3 sm:grid-cols-4"
                          x-data="{ amount: '', outstanding: {{ number_format($invoice->outstanding, 2, '.', '') }} }">
                        @csrf
                        <input type="date" name="date" value="{{ now()->toDateString() }}" class="form-input" required>
                        <div class="relative">
                            <input type="number" step="0.01" name="amount" x-model="amount" placeholder="Jumlah" class="form-input pr-16" :max="outstanding" required>
                            <button type="button" @click="amount = outstanding"
                                    class="absolute inset-y-1 right-1 rounded-md bg-gold-100 px-2 text-xs font-semibold text-gold-800 hover:bg-gold-200">
                                Lunas
                            </button>
                        </div>
                        <input type="text" name="method" placeholder="Metode" class="form-input">
                        <button class="btn-gold">Simpan</button>
                    </form>
                @endcan
                @forelse ($invoice->payments as $p)
                    <div class="flex items-center justify-between border-b border-gray-50 py-1.5 text-sm">
                        <span class="text-gray-500">{{ $p->date->format('d/m/Y') }} · {{ $p->method ?: '—' }}</span>
                        <span class="font-medium text-navy">Rp {{ number_format($p->amount,0,',','.') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Belum ada pembayaran.</p>
                @endforelse
            </div>
        </div>

        <div class="space-y-5">
            <div class="card space-y-3">
                <div class="flex items-center justify-between"><span class="text-sm text-gray-500">Status</span><span class="badge {{ $statusColor[$invoice->status] ?? 'bg-gray-100' }}">{{ $invoice->status_label }}</span></div>
                <div class="flex justify-between text-sm"><span class="text-gray-500">Pelanggan</span><span class="font-medium text-navy">{{ $so->customer->name }}</span></div>
                <div x-data="{ editing: false }">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Tanggal</span>
                        <span>{{ $invoice->date->format('d/m/Y') }}</span>
                    </div>
                    <div class="mt-3 flex justify-between text-sm">
                        <span class="text-gray-500">Jatuh Tempo</span>
                        <span class="{{ $invoice->is_overdue ? 'font-medium text-red-600' : '' }}">
                            {{ $invoice->due_date?->format('d/m/Y') ?? '—' }}
                            @if ($invoice->is_overdue)<span class="badge ml-1 bg-red-100 text-red-600">lewat tempo</span>@endif
                        </span>
                    </div>
                    @can('invoices.update')
                        <button type="button" x-show="! editing" @click="editing = true" class="mt-2 text-xs text-gold-700 hover:underline">Ubah tanggal</button>
                        <form x-show="editing" x-cloak method="POST" action="{{ route('invoices.dates.update', $invoice) }}"
                              class="mt-3 space-y-2 rounded-lg bg-gray-50 p-3"
                              x-data="dueDatePicker({
                                 terms: {{ Illuminate\Support\Js::from([$so->customer_id => $so->customer->payment_term_days]) }},
                                 partyId: '{{ $so->customer_id }}',
                                 date: '{{ $invoice->date->toDateString() }}',
                                 dueDate: '{{ $invoice->due_date?->toDateString() ?? '' }}',
                              })">
                            @csrf @method('PATCH')
                            <div>
                                <label class="form-label text-xs">Tanggal Invoice</label>
                                <input type="date" name="date" x-model="date" @change="applyTerm()" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label text-xs">Jatuh Tempo</label>
                                <input type="date" name="due_date" x-model="dueDate" :min="date" class="form-input">
                            </div>
                            <div class="flex gap-2">
                                <button class="btn-gold flex-1 text-sm">Simpan</button>
                                <button type="button" @click="editing = false" class="btn-outline text-sm">Batal</button>
                            </div>
                        </form>
                    @endcan
                </div>
                <div class="space-y-1.5 border-t border-gray-100 pt-3 text-sm">
                    <div class="flex justify-between"><span class="text-gray-500">DPP</span><span>Rp {{ number_format($so->dpp,0,',','.') }}</span></div>
                    @if ($so->is_taxable)
                        <div class="flex justify-between"><span class="text-gray-500">PPN ({{ rtrim(rtrim(number_format($so->ppn_rate,2),'0'),'.') }}%)</span><span>Rp {{ number_format($so->ppn,0,',','.') }}</span></div>
                    @endif
                    <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-bold text-navy"><span>Total</span><span>Rp {{ number_format($invoice->total,0,',','.') }}</span></div>
                    <div class="flex justify-between pt-1"><span class="text-gray-500">Dibayar</span><span class="text-green-600">Rp {{ number_format($invoice->paid_amount,0,',','.') }}</span></div>
                    <div class="flex justify-between font-semibold"><span class="text-gray-500">Sisa Piutang</span><span class="text-red-600">Rp {{ number_format($invoice->outstanding,0,',','.') }}</span></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
