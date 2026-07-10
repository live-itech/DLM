<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 10px; color: #222; margin: 0; }
    .rtitle { font-size: 16px; font-weight: bold; color: #14233A; }
    .rperiod { font-size: 10px; color: #666; margin-bottom: 8px; }
    table.data { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.data th { background: #14233A; color: #fff; font-size: 9px; padding: 5px 6px; text-align: left; }
    table.data td { padding: 4px 6px; border-bottom: 1px solid #eee; font-size: 9px; }
    table.data tr:last-child td { border-top: 2px solid #CDA45E; font-weight: bold; }
    .num { text-align: right; }
</style>
</head>
<body>
    @include('pdf._kop')

    <div class="rtitle">{{ $title }}</div>
    @if ($period)<div class="rperiod">Periode: {{ $period }}</div>@endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($headings as $h)
                    <th @class(['num' => $loop->index > 0 && ! in_array($h, ['No. Invoice','No. PO','Pelanggan','Supplier','Produk','Kategori','Status','Referensi','Tipe','Keterangan'])])>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td class="{{ is_numeric($cell) && $loop->index > 0 ? 'num' : '' }}">
                            {{ is_numeric($cell) && $loop->index > 0 ? number_format((float) $cell, 0, ',', '.') : $cell }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
            @if (! empty($footer))
                <tr>
                    @foreach ($footer as $cell)
                        <td class="{{ is_numeric($cell) ? 'num' : '' }}">{{ is_numeric($cell) ? number_format((float) $cell, 0, ',', '.') : $cell }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
    </table>

    <div style="margin-top:20px; font-size:8px; color:#999;">
        Dicetak: {{ now()->format('d/m/Y H:i') }} · {{ $company['name'] }}
    </div>
</body>
</html>
