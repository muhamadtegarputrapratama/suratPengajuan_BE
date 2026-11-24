@extends('surat.layout')

@section('isi')
    @include('surat._pembuka', ['hal' => 'Izin Praktik Khusus/Penelitian'])

    <table style="margin-bottom: 10px;">
        <tr><td width="120"><b>Lokasi Penelitian</b></td><td width="14">:</td><td>{{ $pengajuan->lokasi_penelitian ?? '-' }}</td></tr>
        <tr><td><b>Waktu Penelitian</b></td><td>:</td><td>{{ \App\Support\SuratFormat::periode($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai) }}</td></tr>
    </table>

    <p>Sehubungan dengan hal tersebut di atas, kami mohon agar mahasiswa yang bersangkutan dapat diberikan kemudahan izin penelitian pada lokasi tersebut.</p>

    <p>Atas bantuan dan kerjasama yang baik diucapkan terima kasih.</p>

    @include('surat._ttd')

    <div class="tembusan">
        Tembusan:<br>
        Ketua Departemen {{ $pengajuan->departemen->nama }} Fakultas Kehutanan dan Lingkungan IPB
    </div>
@endsection