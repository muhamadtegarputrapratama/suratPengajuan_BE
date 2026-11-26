<?php

namespace App\Console\Commands;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BuatAkunDepartemen extends Command
{
    protected $signature = 'akun:departemen
        {kode : Kode departemen, mis. MNH}
        {email : Email akun baru}
        {--nama= : Nama akun (opsional)}
        {--password= : Password (kosongkan untuk diminta saat dijalankan)}';

    protected $description = 'Menambah satu akun admin departemen';

    public function handle(): int
    {
        $kode = strtolower((string) $this->argument('kode'));
        $departemen = Departemen::whereRaw('LOWER(kode) = ?', [$kode])->first();

        if (!$departemen) {
            $this->error('Departemen dengan kode "' . $this->argument('kode') . '" tidak ditemukan.');
            $this->line('Kode yang ada: ' . Departemen::orderBy('id')->pluck('kode')->implode(', '));

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => ['required', 'email']])->fails()) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error("Email {$email} sudah terdaftar.");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: $this->secret('Password (minimal 8 karakter)'));

        if (strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = new User();
        $user->name = $this->option('nama') ?: 'Admin Departemen ' . $departemen->nama;
        $user->email = $email;
        $user->password = $password;
        $user->role = User::ROLE_ADMIN_DEPARTEMEN;
        $user->departemen_id = $departemen->id;
        $user->save();

        $this->info("Akun dibuat: {$email} (departemen {$departemen->nama})");

        return self::SUCCESS;
    }
}