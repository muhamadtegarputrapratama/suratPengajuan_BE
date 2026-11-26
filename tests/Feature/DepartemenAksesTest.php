<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DepartemenAksesTest extends TestCase
{
    use RefreshDatabase;

    private Departemen $mnh;
    private Departemen $kshe;
    private JenisPengajuan $jenis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mnh = Departemen::create(['kode' => 'MNH', 'nama' => 'Manajemen Hutan', 'prefix_nim' => 'E141']);
        $this->kshe = Departemen::create(['kode' => 'KSHE', 'nama' => 'Konservasi Sumberdaya Hutan dan Ekowisata', 'prefix_nim' => 'E341']);
        $this->jenis = JenisPengajuan::create(['kode' => 'PENELITIAN', 'nama' => 'Pengajuan Penelitian', 'deskripsi' => 'x', 'is_active' => true]);
    }

    private function akun(string $email, string $role, ?Departemen $dep = null): User
    {
        $u = new User();
        $u->forceFill([
            'name' => 'Tester',
            'email' => $email,
            'password' => 'rahasia-123',
            'role' => $role,
            'departemen_id' => $dep?->id,
        ])->save();

        return $u;
    }

    private function pengajuan(Departemen $dep, string $nim): Pengajuan
    {
        return Pengajuan::create([
            'nim' => $nim,
            'nama_mahasiswa' => 'Mahasiswa ' . $nim,
            'departemen_id' => $dep->id,
            'status' => Pengajuan::STATUS_MENUNGGU,
        ]);
    }

    public function test_tanpa_login_semua_route_ditolak(): void
    {
        $this->getJson('/api/pengajuan')->assertStatus(401);
        $this->getJson('/api/departemen')->assertStatus(401);
        $this->getJson('/api/jenis-pengajuan')->assertStatus(401);
        $this->postJson('/api/pengajuan', [])->assertStatus(401);
        $this->postJson('/api/cari-mahasiswa', ['nim' => 'E1411234567'])->assertStatus(401);
    }

    public function test_admin_departemen_hanya_melihat_data_departemennya(): void
    {
        $this->pengajuan($this->mnh, 'E1411234567');
        $milikLain = $this->pengajuan($this->kshe, 'E3411234567');

        Sanctum::actingAs($this->akun('mnh@test.com', User::ROLE_ADMIN_DEPARTEMEN, $this->mnh));

        $this->getJson('/api/pengajuan')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("/api/pengajuan/{$milikLain->id}")->assertStatus(404);
    }

    public function test_admin_kpap_melihat_semua_departemen(): void
    {
        $this->pengajuan($this->mnh, 'E1411234567');
        $this->pengajuan($this->kshe, 'E3411234567');

        Sanctum::actingAs($this->akun('kpap@test.com', User::ROLE_ADMIN_KPAP));

        $this->getJson('/api/pengajuan')->assertOk()->assertJsonPath('data.total', 2);
    }

    public function test_departemen_tidak_bisa_mengajukan_untuk_departemen_lain(): void
    {
        Sanctum::actingAs($this->akun('mnh@test.com', User::ROLE_ADMIN_DEPARTEMEN, $this->mnh));

        $this->postJson('/api/pengajuan', [
            'nim' => 'E3411234567',
            'nama_mahasiswa' => 'Orang KSHE',
            'jenis_pengajuan' => [$this->jenis->id],
        ])->assertStatus(403);

        $this->postJson('/api/pengajuan', [
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Orang MNH',
            'jenis_pengajuan' => [$this->jenis->id],
        ])->assertStatus(201);
    }

    public function test_command_menambah_akun_departemen(): void
    {
        $this->artisan('akun:departemen', ['kode' => 'mnh', 'email' => 'staf2@gmail.com', '--password' => 'rahasia-123'])
            ->assertExitCode(0);

        $akun = User::where('email', 'staf2@gmail.com')->first();
        $this->assertSame($this->mnh->id, $akun->departemen_id);
        $this->assertSame(User::ROLE_ADMIN_DEPARTEMEN, $akun->role);

        $this->artisan('akun:departemen', ['kode' => 'xxx', 'email' => 'lain@gmail.com', '--password' => 'rahasia-123'])
            ->assertExitCode(1);
    }
}