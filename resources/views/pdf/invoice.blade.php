@php $so = $invoice->salesOrder; @endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #222; margin: 0; }
    .title { font-size: 20px; font-weight: bold; color: #14233A; letter-spacing: 1px; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.items th { background: #14233A; color: #fff; font-size: 10px; padding: 6px 8px; text-align: left; }
    table.items td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    .r { text-align: right; }
    .meta td { font-size: 10px; padding: 2px 0; vertical-align: top; }
    .totbox { width: 45%; margin-left: 55%; margin-top: 10px; }
    .totbox td { padding: 3px 8px; font-size: 10px; }
    .grand { border-top: 2px solid #CDA45E; font-weight: bold; font-size: 12px; color: #14233A; }
    .badge { padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; }

    /* Blok instruksi pembayaran */
    .paybox { width: 100%; border-collapse: collapse; border: 1px solid #E5DCC6; border-left: 4px solid #CDA45E; background: #FCF9F2; }
    .paybox td { padding: 10px 12px; }
    .paybox-head { font-size: 8.5px; font-weight: bold; color: #A67C2A; letter-spacing: 1px; text-transform: uppercase; }
    .bank-name { font-size: 15px; font-weight: bold; color: #14233A; letter-spacing: 0.5px; margin-top: 3px; }
    .bank-acc { font-size: 16px; font-weight: bold; color: #14233A; letter-spacing: 1.5px; padding: 2px 0; }
    .bank-holder { font-size: 10.5px; font-weight: bold; color: #14233A; text-transform: uppercase; padding: 2px 0; }
    .paybox-note { margin-top: 8px; padding-top: 7px; border-top: 1px dashed #E0D5BB; font-size: 8.5px; color: #6B7280; line-height: 1.5; }
</style>
</head>
<body>
    @include('pdf._kop')

    <table style="width:100%; margin-bottom:10px;">
        <tr>
            <td style="width:60%; vertical-align:top;">
                <div style="font-size:9px; color:#888; text-transform:uppercase;">Ditagihkan Kepada</div>
                <div style="font-weight:bold; color:#14233A; font-size:12px;">{{ $so->customer->name }}</div>
                <div style="font-size:10px; color:#555;">
                    {{ $so->customer->address }}<br>
                    @if ($so->customer->npwp)NPWP: {{ $so->customer->npwp }}<br>@endif
                    @if ($so->customer->phone)Telp: {{ $so->customer->phone }}@endif
                </div>
            </td>
            <td style="width:40%; vertical-align:top;">
                <div class="title">INVOICE</div>
                <table class="meta">
                    <tr><td style="width:90px; color:#888;">No. Invoice</td><td><strong>{{ $invoice->invoice_number }}</strong></td></tr>
                    <tr><td style="color:#888;">No. SO</td><td>{{ $so->so_number }}</td></tr>
                    <tr><td style="color:#888;">Tanggal</td><td>{{ $invoice->date->format('d M Y') }}</td></tr>
                    <tr><td style="color:#888;">Jatuh Tempo</td><td><strong>{{ $invoice->due_date?->format('d M Y') ?? '-' }}</strong></td></tr>
                    <tr><td style="color:#888;">Status</td><td>{{ $invoice->status_label }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:30px;">No</th>
                <th>Produk</th>
                <th class="r" style="width:60px;">Qty</th>
                <th class="r" style="width:90px;">Harga</th>
                <th class="r" style="width:90px;">Diskon</th>
                <th class="r" style="width:100px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($so->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                    <td class="r">{{ number_format($item->sell_price,0,',','.') }}</td>
                    <td class="r">{{ number_format($item->discount,0,',','.') }}</td>
                    <td class="r">{{ number_format($item->subtotal,0,',','.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totbox">
        <tr><td style="color:#888;">Subtotal</td><td class="r">Rp {{ number_format($so->subtotal,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">Diskon</td><td class="r">Rp {{ number_format($so->discount,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">DPP</td><td class="r">Rp {{ number_format($so->dpp,0,',','.') }}</td></tr>
        @if ($so->is_taxable)
            <tr><td style="color:#888;">PPN ({{ rtrim(rtrim(number_format($so->ppn_rate,2),'0'),'.') }}%)</td><td class="r">Rp {{ number_format($so->ppn,0,',','.') }}</td></tr>
        @endif
        <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($invoice->total,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">Dibayar</td><td class="r">Rp {{ number_format($invoice->paid_amount,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">Sisa</td><td class="r"><strong>Rp {{ number_format($invoice->outstanding,0,',','.') }}</strong></td></tr>
    </table>

    <div style="clear:both;"></div>

    {{-- Instruksi pembayaran --}}
    <table style="width:100%; margin-top:26px;">
        <tr>
            <td style="width:58%; vertical-align:top;">
                <table class="paybox">
                    <tr>
                        <td>
                            <div class="paybox-head">PEMBAYARAN DAPAT DITRANSFER MELALUI</div>
                            <div class="bank-name">{{ $company['bank_name'] ?: '—' }}</div>
                            <table style="width:100%; margin-top:6px;">
                                <tr>
                                    <td style="width:74px; font-size:9px; color:#6B7280; padding:2px 0;">No. Rekening</td>
                                    <td class="bank-acc">{{ $company['bank_account_no'] ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="font-size:9px; color:#6B7280; padding:2px 0;">A/N</td>
                                    <td class="bank-holder">{{ $company['bank_account_holder'] ?: $company['name'] }}</td>
                                </tr>
                            </table>
                            <div class="paybox-note">
                                Mohon cantumkan nomor invoice <strong>{{ $invoice->invoice_number }}</strong> pada berita transfer.
                                @if ($invoice->due_date)
                                    Pembayaran jatuh tempo <strong>{{ $invoice->due_date->format('d M Y') }}</strong>.
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>

                @if ($invoice->notes)
                    <div style="margin-top:10px; font-size:9px; color:#777;"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
                @endif
            </td>
            <td style="width:42%;"></td>
        </tr>
    </table>

    {{-- Tanda tangan: penerima (diisi tulis tangan saat invoice dicetak) & pengirim --}}
    <table style="width:100%; margin-top:24px;">
        <tr>
            <td style="width:50%; text-align:center; vertical-align:top; font-size:10px;">
                <div style="color:#555;">Diterima oleh,</div>
                <div style="font-size:9px; color:#999; margin-top:2px;">{{ $so->customer->name }}</div>
                <div style="height:64px;"></div>
                <div style="border-top:1px solid #14233A; width:78%; margin:0 auto; padding-top:5px;">
                    <div style="font-size:9px; color:#999;">Nama jelas &amp; tanggal terima</div>
                </div>
            </td>
            <td style="width:50%; text-align:center; vertical-align:top; font-size:10px;">
                <div style="color:#555;">Hormat kami,</div>
                <div style="font-weight:bold; color:#14233A; margin-top:2px;">{{ $company['name'] }}</div>
                <div style="height:64px;"></div>
                <div style="border-top:1px solid #14233A; width:78%; margin:0 auto; padding-top:5px;">
                    <div style="font-weight:bold; color:#14233A; font-size:11px;">{{ $company['director_name'] ?: '—' }}</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
