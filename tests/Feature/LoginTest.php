<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function akun(string $email, string $role, ?int $departemenId = null): User
    {
        $u = User::firstOrNew(['email' => $email]);
        $u->forceFill([
            'name' => 'Tester',
            'password' => 'rahasia-123',
            'role' => $role,
            'departemen_id' => $departemenId,
        ])->save();

        return $u;
    }

    public function test_admin_kpap_bisa_login(): void
    {
        $this->akun('adminkpap@gmail.com', User::ROLE_ADMIN_KPAP);

        $this->postJson('/api/login', ['email' => 'adminkpap@gmail.com', 'password' => 'rahasia-123'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin_kpap')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_admin_departemen_login_dan_membawa_data_departemen(): void
    {
        $dep = Departemen::create(['kode' => 'MNH', 'nama' => 'Manajemen Hutan', 'prefix_nim' => 'E141']);
        $this->akun('admindept+mnh@gmail.com', User::ROLE_ADMIN_DEPARTEMEN, $dep->id);

        $this->postJson('/api/login', ['email' => 'admindept+mnh@gmail.com', 'password' => 'rahasia-123'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'admin_departemen')
            ->assertJsonPath('data.user.departemen.kode', 'MNH');
    }

    public function test_password_salah_atau_email_tidak_ada_ditolak(): void
    {
        $this->akun('adminkpap@gmail.com', User::ROLE_ADMIN_KPAP);

        $this->postJson('/api/login', ['email' => 'adminkpap@gmail.com', 'password' => 'salah'])->assertStatus(401);
        $this->postJson('/api/login', ['email' => 'tidakada@gmail.com', 'password' => 'rahasia-123'])->assertStatus(401);
    }

    public function test_akun_departemen_tanpa_departemen_ditolak(): void
    {
        $this->akun('admindept@gmail.com', User::ROLE_ADMIN_DEPARTEMEN, null);

        $this->postJson('/api/login', ['email' => 'admindept@gmail.com', 'password' => 'rahasia-123'])
            ->assertStatus(403);
    }

    public function test_role_lain_ditolak(): void
    {
        $this->akun('lain@gmail.com', 'mahasiswa');

        $this->postJson('/api/login', ['email' => 'lain@gmail.com', 'password' => 'rahasia-123'])
            ->assertStatus(403);
    }

    public function test_me_dan_logout_memakai_token(): void
    {
        $this->akun('adminkpap@gmail.com', User::ROLE_ADMIN_KPAP);

        $token = $this->postJson('/api/login', ['email' => 'adminkpap@gmail.com', 'password' => 'rahasia-123'])
            ->json('data.token');

        $this->getJson('/api/me')->assertStatus(401);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'adminkpap@gmail.com');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}