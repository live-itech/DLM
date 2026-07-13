{{-- Kop surat PT Dimas Love Medika (untuk dokumen PDF) --}}
<table style="width:100%; border-bottom:3px solid #CDA45E; padding-bottom:6px; margin-bottom:14px;">
    <tr>
        @if ($logo)
            {{-- Lebar saja, tanpa height: dompdf menskalakan tinggi mengikuti rasio asli logo. --}}
            <td style="width:160px; padding-right:14px; vertical-align:middle;">
                <img src="{{ $logo }}" alt="logo" style="width:150px;">
            </td>
        @endif
        <td style="vertical-align:middle;">
            <div style="font-size:16px; font-weight:bold; color:#A67C2A; letter-spacing:0.5px;">{{ $company['name'] }}</div>
            <div style="font-size:9px; color:#333; line-height:1.4;">
                {{ $company['address'] }}<br>
                @if ($company['phone'])Telp: {{ $company['phone'] }}@endif
                @if ($company['email']) &nbsp;·&nbsp; Email: {{ $company['email'] }}@endif
                @if ($company['npwp'])<br>NPWP: {{ $company['npwp'] }}@endif
                @if ($company['nib']) &nbsp;·&nbsp; NIB: {{ $company['nib'] }}@endif
            </div>
        </td>
    </tr>
</table>
