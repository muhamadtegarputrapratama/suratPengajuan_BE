<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminDepartemenSeeder extends Seeder
{
    public function run(): void
    {
        $dasar = (string) config('akun.departemen.email');
        $password = config('akun.departemen.password');

        if (!str_contains($dasar, '@') || blank($password)) {
            $this->command?->error('Isi ADMIN_DEPARTEMEN_EMAIL dan ADMIN_DEPARTEMEN_PASSWORD di .env dulu, lalu jalankan php artisan config:clear.');

            return;
        }

        $departemens = Departemen::orderBy('id')->get();

        if ($departemens->isEmpty()) {
            $this->command?->warn('Tabel departemens masih kosong. Jalankan DepartemenSeeder dulu.');

            return; 
        }

        [$lokal, $domain] = explode('@', $dasar, 2);

        foreach ($departemens as $dep) {
            $email = $lokal . '+' . Str::slug($dep->kode) . '@' . $domain;

            $user = User::firstOrNew(['email' => $email]);

            $user->name = 'Admin Departemen ' . $dep->nama;
            $user->role = User::ROLE_ADMIN_DEPARTEMEN;
            $user->departemen_id = $dep->id;

            // Password hanya diisi untuk akun baru; menjalankan seeder lagi tidak mereset password.
            if (!$user->exists) {
                $user->password = $password;
            }

            $user->save();

            $this->command?->info(($user->wasRecentlyCreated ? 'Dibuat    : ' : 'Diperbarui: ') . "{$email}  ({$dep->nama})");
        }
    }
}
