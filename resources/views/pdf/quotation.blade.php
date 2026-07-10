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
    .terms { margin-top: 16px; font-size: 9px; color: #555; }
</style>
</head>
<body>
    @include('pdf._kop')

    <table style="width:100%; margin-bottom:8px;">
        <tr>
            <td style="width:60%; vertical-align:top;">
                <div style="font-size:9px; color:#888; text-transform:uppercase;">Kepada Yth.</div>
                <div style="font-weight:bold; color:#14233A; font-size:12px;">{{ $q->customer->name }}</div>
                <div style="font-size:10px; color:#555;">
                    {{ $q->customer->address }}<br>
                    @if ($q->customer->contact_person)u.p. {{ $q->customer->contact_person }}@endif
                </div>
            </td>
            <td style="width:40%; vertical-align:top;">
                <div class="title">SURAT PENAWARAN</div>
                <table class="meta">
                    <tr><td style="width:80px; color:#888;">Nomor</td><td><strong>{{ $q->quotation_number }}</strong></td></tr>
                    <tr><td style="color:#888;">Tanggal</td><td>{{ $q->date->format('d M Y') }}</td></tr>
                    <tr><td style="color:#888;">Berlaku s/d</td><td>{{ $q->valid_until?->format('d M Y') ?? '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p style="font-size:10px;">Dengan hormat, bersama ini kami sampaikan penawaran harga untuk produk berikut:</p>

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
            @foreach ($q->items as $i => $item)
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
        <tr><td style="color:#888;">Subtotal</td><td class="r">Rp {{ number_format($q->subtotal,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">Diskon</td><td class="r">Rp {{ number_format($q->discount,0,',','.') }}</td></tr>
        <tr><td style="color:#888;">DPP</td><td class="r">Rp {{ number_format($q->dpp,0,',','.') }}</td></tr>
        @if ($q->is_taxable)
            <tr><td style="color:#888;">PPN ({{ rtrim(rtrim(number_format($q->ppn_rate,2),'0'),'.') }}%)</td><td class="r">Rp {{ number_format($q->ppn,0,',','.') }}</td></tr>
        @endif
        <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($q->total,0,',','.') }}</td></tr>
    </table>

    <div style="clear:both;"></div>

    @if ($q->terms)
        <div class="terms">
            <strong>Syarat &amp; Ketentuan:</strong><br>
            {!! nl2br(e($q->terms)) !!}
        </div>
    @endif

    <table style="width:100%; margin-top:30px;">
        <tr>
            <td style="width:60%;"></td>
            <td style="width:40%; text-align:center; font-size:10px;">
                Hormat kami,<br><br><br><br>
                <strong>{{ $company['name'] }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
