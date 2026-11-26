<?php

namespace Database\Seeders;

use App\Models\Departemen;
use Illuminate\Database\Seeder;

class DepartemenDasarSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['prefix_nim' => 'E141', 'kode' => 'MNH', 'nama' => 'Manajemen Hutan'],
            ['prefix_nim' => 'E241', 'kode' => 'HH', 'nama' => 'Hasil Hutan'],
            ['prefix_nim' => 'E341', 'kode' => 'KSHE', 'nama' => 'Konservasi Sumberdaya Hutan dan Ekowisata'],
            ['prefix_nim' => 'E441', 'kode' => 'SVK', 'nama' => 'Silvikultur'],
        ];

        foreach ($daftar as $d) {
            $dep = Departemen::firstOrCreate(
                ['prefix_nim' => $d['prefix_nim']],
                ['kode' => $d['kode'], 'nama' => $d['nama']]
            );

            $this->command?->info(($dep->wasRecentlyCreated ? 'Ditambah : ' : 'Sudah ada: ') . "{$dep->prefix_nim}  {$dep->kode}  {$dep->nama}");
        }
    }
}