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
</style>
</head>
<body>
    @include('pdf._kop')

    <table style="width:100%; margin-bottom:10px;">
        <tr>
            <td style="width:60%; vertical-align:top;">
                <div style="font-size:9px; color:#888; text-transform:uppercase;">Kepada Yth.</div>
                <div style="font-weight:bold; color:#14233A; font-size:12px;">{{ $order->supplier->name }}</div>
                <div style="font-size:10px; color:#555;">
                    {{ $order->supplier->address }}<br>
                    @if ($order->supplier->npwp)NPWP: {{ $order->supplier->npwp }}<br>@endif
                    @if ($order->supplier->phone)Telp: {{ $order->supplier->phone }}@endif
                </div>
            </td>
            <td style="width:40%; vertical-align:top;">
                <div class="title">PURCHASE ORDER</div>
                <table class="meta">
                    <tr><td style="width:90px; color:#888;">No. PO</td><td><strong>{{ $order->po_number }}</strong></td></tr>
                    <tr><td style="color:#888;">Tanggal</td><td>{{ $order->date->format('d M Y') }}</td></tr>
                    <tr><td style="color:#888;">Jatuh Tempo</td><td><strong>{{ $order->due_date?->format('d M Y') ?? '-' }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="margin-bottom:6px; font-size:10px; color:#333; line-height:1.6;">
        Dengan hormat,<br>
        Bersama surat ini, kami dari {{ $company['name'] }} ingin menyampaikan surat pesanan dengan rincian sebagai berikut :
    </div>

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
            @foreach ($order->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td class="r">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }}</td>
                    <td class="r">{{ number_format($item->cost_price,0,',','.') }}</td>
                    <td class="r">{{ number_format($item->discount,0,',','.') }}</td>
                    <td class="r">{{ number_format($item->subtotal,0,',','.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totbox">
        <tr><td style="color:#888;">Subtotal</td><td class="r">Rp {{ number_format($order->subtotal,0,',','.') }}</td></tr>
        @if ((float) $order->discount > 0)
            <tr><td style="color:#888;">Diskon</td><td class="r">Rp {{ number_format($order->discount,0,',','.') }}</td></tr>
        @endif
        <tr><td style="color:#888;">DPP</td><td class="r">Rp {{ number_format($order->dpp,0,',','.') }}</td></tr>
        @if ($order->is_taxable)
            <tr><td style="color:#888;">PPN ({{ rtrim(rtrim(number_format($order->ppn_rate,2),'0'),'.') }}%)</td><td class="r">Rp {{ number_format($order->ppn,0,',','.') }}</td></tr>
        @endif
        <tr class="grand"><td>TOTAL</td><td class="r">Rp {{ number_format($order->total,0,',','.') }}</td></tr>
    </table>

    <div style="clear:both;"></div>

    @if ($order->notes)
        <div style="margin-top:16px; font-size:9px; color:#777;"><strong>Catatan:</strong> {{ $order->notes }}</div>
    @endif

    {{-- Tanda tangan --}}
    <table style="width:100%; margin-top:32px;">
        <tr>
            <td style="width:50%;"></td>
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
