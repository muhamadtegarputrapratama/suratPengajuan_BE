<?php

return [
    /*
     * Format nomor: contoh hasil "1/IT3.F4/PT.01.04/M/B/2026".
     * Argumen sprintf: 1 = nomor urut, 2 = bulan romawi (tidak dipakai), 3 = tahun.
     */
    'format_nomor' => '%1$d/IT3.F4/PT.01.04/M/B/%3$d',

    'kota' => 'Bogor',
    'tagline' => 'Inspiring Innovation with Integrity',

    'kop' => [
        'kementerian' => 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI',
        'universitas' => 'INSTITUT PERTANIAN BOGOR',
        'fakultas' => 'FAKULTAS KEHUTANAN DAN LINGKUNGAN',
        'kontak' => [
            'Kampus IPB Dramaga, Bogor 16680',
            'Telepon (0251) 8621677',
            'fahutan@apps.ipb.ac.id',
            'fahutan.ipb.ac.id',
        ],
    ],

    // Logo kop surat, relatif terhadap folder public/. Kosongkan atau hapus file untuk tanpa logo.
    'logo' => 'images/logo-ipb.png',
    // Ornamen tepi kiri halaman, relatif terhadap folder public/. File tidak ada = tidak ditampilkan.
    'ornamen_kiri' => 'images/ornamen-kiri.png',

    /*
     * Penandatangan surat. Diisi lewat .env (SURAT_TTD_*), nilai di bawah hanya cadangan.
     * Tampil di PDF (view surat/_ttd) dan Word (SuratDocxGenerator).
     */
    'penandatangan' => [
        'jabatan' => array_values(array_filter([
            env('SURAT_TTD_JABATAN_1', 'Wakil Dekan'),
            env('SURAT_TTD_JABATAN_2', 'Bidang Akademik, Kemahasiswaan dan Alumni'),
        ])),
        'nama' => env('SURAT_TTD_NAMA', 'Dr. Dede Aulia Rahman, S.Hut, M.Si'),
        'nip' => env('SURAT_TTD_NIP', '198701142014041001'),
    ],

    // Dipakai bila kode jenis pengajuan tidak ada di peta 'template'.
    'default_template' => 'surat.izin-penelitian',

    /*
     * Peta: kode JenisPengajuan (kolom `kode` di JenisPengajuanSeeder) => view surat.
     * Pilihan view: 'surat.izin-penelitian' atau 'surat.permohonan-bantuan'.
     * GANTI kunci di bawah dengan kode asli dari seeder.
     */
   'template' => [
    'PERMOHONAN_BANTUAN' => 'surat.permohonan-bantuan',
    'PERMOHONAN_IZIN_PRAKTIK_KHUSUS' => 'surat.izin-penelitian',
    'PENJAJAGAN' => 'surat.izin-penelitian',
    'TUGAS_JALAN' => 'surat.izin-penelitian',
],

    'disk' => 'public',

    // Disk tempat BerkasPengajuanController menyimpan upload foto. Kosong = disk default.
    'berkas_disk' => env('BERKAS_DISK', 'public'),
];
