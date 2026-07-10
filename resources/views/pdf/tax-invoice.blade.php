@php
    $inv = $ti->invoice;
    $so = $inv?->salesOrder;
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #222; margin: 0; }
    .fp-title { text-align: center; font-weight: bold; font-size: 14px; margin-bottom: 2px; }
    .fp-sub { text-align: center; font-size: 10px; color: #555; margin-bottom: 10px; }
    .nsfp { text-align: center; font-size: 13px; font-weight: bold; color: #A67C2A; letter-spacing: 1px; margin: 6px 0 14px; }
    .party { font-size: 10px; margin-bottom: 8px; }
    .party b { color: #14233A; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th { background: #14233A; color: #fff; font-size: 9px; padding: 5px 6px; text-align: left; }
    table.items td { padding: 5px 6px; border-bottom: 1px solid #eee; font-size: 9px; }
    .r { text-align: right; }
    .sumtbl { width: 50%; margin-left: 50%; margin-top: 8px; }
    .sumtbl td { padding: 3px 6px; font-size: 10px; }
    .grand { border-top: 2px solid #CDA45E; font-weight: bold; color: #14233A; }
</style>
</head>
<body>
    @include('pdf._kop')

    <div class="fp-title">FAKTUR PAJAK</div>
    <div class="fp-sub">Kode dan Nomor Seri Faktur Pajak:</div>
    <div class="nsfp">{{ $ti->tax_number }}</div>

    <table style="width:100%;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="party">
                    <b>Pengusaha Kena Pajak</b><br>
                    Nama: {{ $company['name'] }}<br>
                    Alamat: {{ $company['address'] }}<br>
                    NPWP: {{ $company['npwp'] ?: '-' }}
                </div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="party">
                    <b>Pembeli Barang Kena Pajak</b><br>
                    Nama: {{ $ti->partner_name }}<br>
                    @if ($so?->customer?->address)Alamat: {{ $so->customer->address }}<br>@endif
                    NPWP: {{ $ti->partner_npwp ?: '-' }}
                </div>
            </td>
        </tr>
    </table>

    @if ($so)
        <table class="items">
            <thead>
                <tr>
                    <th style="width:28px;">No</th>
                    <th>Nama Barang Kena Pajak</th>
                    <th class="r" style="width:110px;">Harga/Satuan</th>
                    <th class="r" style="width:110px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($so->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $item->product->name }}<br><span style="color:#888;">{{ rtrim(rtrim(number_format($item->qty,2,',','.'),'0'),',') }} {{ $item->product->unit?->symbol }} x Rp {{ number_format($item->sell_price,0,',','.') }}</span></td>
                        <td class="r">{{ number_format($item->sell_price,0,',','.') }}</td>
                        <td class="r">{{ number_format($item->subtotal,0,',','.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="sumtbl">
        <tr><td style="color:#555;">Dasar Pengenaan Pajak (DPP)</td><td class="r">Rp {{ number_format($ti->dpp,0,',','.') }}</td></tr>
        <tr class="grand"><td>PPN ({{ rtrim(rtrim(number_format($ti->ppn_rate,2),'0'),'.') }}%)</td><td class="r">Rp {{ number_format($ti->ppn,0,',','.') }}</td></tr>
    </table>

    <div style="clear:both;"></div>

    <table style="width:100%; margin-top:36px;">
        <tr>
            <td style="width:60%; font-size:8px; color:#888; vertical-align:bottom;">
                Sesuai dengan ketentuan yang berlaku, Direktorat Jenderal Pajak mengatur bahwa Faktur Pajak ini
                telah dibuat sesuai data yang sebenarnya.
            </td>
            <td style="width:40%; text-align:center; font-size:10px;">
                {{ $company['address'] ? 'Tangerang Selatan' : '' }}, {{ $ti->date->format('d M Y') }}<br>
                {{ $company['name'] }}<br><br><br><br>
                <strong>{{ $company['pjt'] ?: '( ..................... )' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
