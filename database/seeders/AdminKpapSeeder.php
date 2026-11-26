<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminKpapSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('akun.kpap.email');
        $password = config('akun.kpap.password');

        if (blank($email) || blank($password)) {
            $this->command?->error('Isi ADMIN_KPAP_EMAIL dan ADMIN_KPAP_PASSWORD di .env dulu, lalu jalankan php artisan config:clear.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);

        $user->name = 'Admin KPAP';
        $user->role = User::ROLE_ADMIN_KPAP;

        // Password hanya diisi untuk akun baru; menjalankan seeder lagi tidak mereset password.
        if (!$user->exists) {
            $user->password = $password;
        }

        $user->save();

        $this->command?->info(($user->wasRecentlyCreated ? 'Dibuat    : ' : 'Diperbarui: ') . $email);
    }
}