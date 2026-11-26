<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

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

        Role::findOrCreate(User::ROLE_ADMIN_KPAP, 'web');
        $user->assignRole(User::ROLE_ADMIN_KPAP);
    }
}
