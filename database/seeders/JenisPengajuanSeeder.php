<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisPengajuan;

class JenisPengajuanSeeder extends Seeder
{
    public function run(): void
    {
        $jenisPengajuans = [
            [
                'kode' => 'PENJAJAGAN',
                'nama' => 'Pengajuan Penjajagan',
                'deskripsi' => 'Pengajuan surat penjajagan.',
                'is_active' => true,
            ],
            [
                'kode' => 'TUGAS_JALAN',
                'nama' => 'Tugas Jalan',
                'deskripsi' => 'Pengajuan surat tugas jalan.',
                'is_active' => true,
            ],
            [
                'kode' => 'PERMOHONAN_BANTUAN',
                'nama' => 'Pengajuan Permohonan Bantuan',
                'deskripsi' => 'Pengajuan surat permohonan bantuan.',
                'is_active' => true,
            ],
            [
                'kode' => 'PERMOHONAN_IZIN_PRAKTIK_KHUSUS',
                'nama' => 'Pengajuan Izin Praktik Khusus',
                'deskripsi' => 'Pengajuan surat permohonan izin praktik khusus.',
                'is_active' => true,
            ]
        ];

        foreach ($jenisPengajuans as $jenis) {
            JenisPengajuan::updateOrCreate(
                ['kode' => $jenis['kode']],
                $jenis
            );
        }
    }
}
