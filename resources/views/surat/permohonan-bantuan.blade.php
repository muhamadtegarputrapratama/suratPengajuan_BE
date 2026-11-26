@extends('surat.layout')

@section('isi')
    @include('surat._pembuka', ['hal' => 'Permohonan Bantuan'])

    <table style="margin-bottom: 10px;">
        <tr><td width="120"><b>Waktu Penelitian</b></td><td width="14">:</td><td>{{ \App\Support\SuratFormat::periode($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai) }}</td></tr>
    </table>

    <p>Sehubungan dengan hal tersebut di atas, kami mohon agar mahasiswa yang bersangkutan dapat diberikan bantuan berupa {{ rtrim((string) $pengajuan->bentuk_bantuan, ". \t\n\r") }}.</p>

    <p>Atas bantuan dan kerjasama yang baik diucapkan terima kasih.</p>

    @include('surat._ttd')
@endsection
