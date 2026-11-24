<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $nomor }}</title>
    <style>
        @page { margin: 35px 55px 75px 55px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.35; color: #000; }
        p { margin: 0 0 10px 0; }
        table { border-collapse: collapse; }
        td { vertical-align: top; padding: 0; }
        .kop { width: 100%; margin-bottom: 18px; }
        .kop .k1 { font-size: 9pt; font-weight: bold; }
        .kop .k2 { font-size: 20pt; font-weight: bold; line-height: 1.1; }
        .kop .k3 { font-size: 11pt; color: #444; }
        .kop .kanan { font-size: 9pt; padding-left: 12px; }
        .identitas td { padding: 1px 0; }
        .ttd { margin-left: 52%; margin-top: 18px; }
        .ttd .ruang { height: 70px; }
        .tembusan { margin-top: 20px; }
        .footer { position: fixed; bottom: -45px; left: 0; right: 0; text-align: center; font-size: 9pt; font-style: italic; }
    </style>
</head>
<body>
    <div class="footer">{{ config('surat.tagline') }}</div>

    <table class="kop">
        <tr>
            <td width="62%" style="border-right: 1px solid #999; padding-right: 10px;">
                <div class="k1">{{ config('surat.kop.kementerian') }}</div>
                <div class="k2">{{ config('surat.kop.universitas') }}</div>
                <div class="k3">{{ config('surat.kop.fakultas') }}</div>
            </td>
            <td width="38%" class="kanan">
                @foreach (config('surat.kop.kontak') as $baris)
                    <div>{{ $baris }}</div>
                @endforeach
            </td>
        </tr>
    </table>

    @yield('isi')
</body>
</html>