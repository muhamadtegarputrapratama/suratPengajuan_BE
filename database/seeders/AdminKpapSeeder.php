<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminKpapSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrNew(['email' => env('ADMIN_KPAP_EMAIL', 'kpap@example.com')]);

        $user->forceFill([
            'name' => 'Admin KPAP',
            'password' => env('ADMIN_KPAP_PASSWORD', 'ganti-password-ini'),
            'role' => User::ROLE_ADMIN_KPAP,
        ])->save();
    }
}