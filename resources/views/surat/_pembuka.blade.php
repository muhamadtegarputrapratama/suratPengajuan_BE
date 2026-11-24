<table width="100%" style="margin-bottom: 14px;">
    <tr>
        <td width="62%">
            <div>Nomor : {{ $nomor }}</div>
            <div>Hal : <b>{{ $hal }}</b></div>
        </td>
        <td width="38%">{{ config('surat.kota') }}, {{ \App\Support\SuratFormat::tanggal($tanggal) }}</td>
    </tr>
</table>

<p>
    Yth. <b>{{ $instansi?->nama_instansi ?? '-' }}</b><br>
    <span style="padding-left: 22px; display: block;"><b>{!! nl2br(e((string) $instansi?->alamat_instansi)) !!}</b></span>
</p>

<p>Disampaikan dengan hormat bahwa mahasiswa Fakultas Kehutanan dan Lingkungan Institut Pertanian Bogor :</p>

<table class="identitas" style="margin: 0 0 10px 40px;">
    <tr><td width="130">Nama</td><td width="14">:</td><td><b>{{ mb_strtoupper($pengajuan->nama_mahasiswa) }}</b></td></tr>
    <tr><td>NIM</td><td>:</td><td>{{ $pengajuan->nim }}</td></tr>
    <tr><td>Program</td><td>:</td><td>{{ $pengajuan->program ?? '-' }}</td></tr>
    <tr><td>Departemen</td><td>:</td><td>{{ $pengajuan->departemen->nama }}</td></tr>
    <tr><td>Komisi Pembimbing</td><td>:</td><td>{{ $pengajuan->dosen_pembimbing ?? '-' }}</td></tr>
</table>

<p>
    bermaksud untuk melaksanakan praktik khusus dalam rangka penyusunan {{ \App\Support\SuratFormat::karyaTulis($pengajuan->program) }} yang berjudul :<br>
    <b><i>“{{ $pengajuan->judul_penelitian }}”.</i></b>
</p>