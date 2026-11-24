<div class="ttd">
    @foreach (config('surat.penandatangan.jabatan') as $baris)
        <div>{{ $baris }}</div>
    @endforeach
    <div class="ruang"></div>
    <div>{{ config('surat.penandatangan.nama') }}</div>
    <div>NIP. {{ config('surat.penandatangan.nip') }}</div>
</div>