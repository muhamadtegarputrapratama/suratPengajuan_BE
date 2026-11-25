<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $nomor }}</title>
    <style>
        @page { margin: 35px 60px 85px 60px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12pt; line-height: 1.4; color: #000; }
        p { margin: 0 0 12px 0; }
        table { border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .kop { width: 100%; margin-bottom: 26px; }
        .kop .k1 { font-size: 9.5pt; font-weight: bold; letter-spacing: 0.2px; }
        .kop .k2 { font-size: 22pt; font-weight: bold; line-height: 1.05; }
        .kop .k3 { font-size: 11.5pt; color: #555; }
        .kop .kanan { font-size: 9.5pt; line-height: 1.35; padding-left: 14px; }
        .identitas td { padding: 2px 0; }
        .ttd { margin-left: 52%; margin-top: 22px; }
        .ttd .ruang { height: 80px; }
        .tembusan { margin-top: 26px; }
        .footer { position: fixed; bottom: -50px; left: 0; right: 0; text-align: center; font-size: 9.5pt; font-style: italic; }
        .ornamen-kiri { position: fixed; left: -60px; top: -35px; height: 1120px; }
    </style>
</head>
<body>
    @php
        $ornamen = \App\Support\SuratFormat::ornamenKiriDataUri();
        $logo = \App\Support\SuratFormat::logoDataUri();
    @endphp

    @if ($ornamen)
        <img src="{{ $ornamen }}" class="ornamen-kiri">
    @endif

    <div class="footer">{{ config('surat.tagline') }}</div>

    <table class="kop">
        <tr>
            @if ($logo)
                <td width="12%" style="padding-right: 8px;">
                    <img src="{{ $logo }}" style="height: 64px;">
                </td>
            @endif
            <td width="{{ $logo ? '52%' : '62%' }}" style="border-right: 1px solid #999; padding-right: 10px;">
                <div class="k1">{{ config('surat.kop.kementerian') }}</div>
                <div class="k2">{{ config('surat.kop.universitas') }}</div>
                <div class="k3">{{ config('surat.kop.fakultas') }}</div>
            </td>
            <td width="{{ $logo ? '36%' : '38%' }}" class="kanan">
                @foreach (config('surat.kop.kontak') as $baris)
                    <div>{{ $baris }}</div>
                @endforeach
            </td>
        </tr>
    </table>

    @yield('isi')
</body>
</html>