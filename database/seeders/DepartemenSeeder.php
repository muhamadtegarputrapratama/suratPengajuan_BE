<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Departemen;

class DepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $departemens = [
            [
                'kode' => 'MNH',
                'nama' => 'Manajemen Hutan',
                'prefix_nim' => 'E141',
            ],
            [
                'kode' => 'HH',
                'nama' => 'Teknologi Hasil Hutan',
                'prefix_nim' => 'E241',
            ],
            [
                'kode' => 'KSHE',
                'nama' => 'Konservasi Sumber Daya Hutan dan Ekowisata',
                'prefix_nim' => 'E341',
            ],
            [
                'kode' => 'SVK',
                'nama' => 'Silvikultur',
                'prefix_nim' => 'E441',
            ],
        ];

        foreach ($departemens as $departemen) {
            Departemen::updateOrCreate(
                ['kode' => $departemen['kode']],
                $departemen
            );
        }
    }
}
