<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminKpapTest extends TestCase
{
    use RefreshDatabase;

   private function admin(string $role = User::ROLE_ADMIN_KPAP): User
{
    Role::findOrCreate($role, 'web');

    $user = User::firstOrCreate(
        ['email' => "{$role}@test.com"],
        [
            'name' => 'Tester',
            'password' => 'password123',
        ]
    );

    $user->syncRoles([$role]);

    return $user;
}

private function pengajuan(
    string $status = Pengajuan::STATUS_MENUNGGU
): Pengajuan {
    $dep = Departemen::firstOrCreate(
        ['kode' => 'MNH'],
        [
            'nama' => 'Manajemen Hutan',
            'prefix_nim' => 'E141',
        ]
    );

    return Pengajuan::create([
        'nim' => 'E1410000001',
        'nama_mahasiswa' => 'Budi',
        'departemen_id' => $dep->id,
        'status' => $status,
    ]);
}

    public function test_login_hanya_untuk_admin_kpap(): void
    {
        $this->admin(User::ROLE_ADMIN_DEPARTEMEN);
        $this->postJson('/api/admin/login', ['email' => 'admin_departemen@test.com', 'password' => 'password123'])
            ->assertStatus(403);

        $this->admin();
        $this->postJson('/api/admin/login', ['email' => 'admin_kpap@test.com', 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_endpoint_admin_butuh_login_dan_role(): void
    {
        $this->getJson('/api/admin/pengajuan')->assertStatus(401);

        Sanctum::actingAs($this->admin(User::ROLE_ADMIN_DEPARTEMEN));
        $this->getJson('/api/admin/pengajuan')->assertStatus(403);
    }

    public function test_verifikasi_dan_tolak(): void
    {
        Sanctum::actingAs($admin = $this->admin());
        $p = $this->pengajuan();

        $this->postJson("/api/admin/pengajuan/{$p->id}/tolak")->assertStatus(422);

        $this->postJson("/api/admin/pengajuan/{$p->id}/verifikasi", ['catatan' => 'ok'])->assertOk();
        $p->refresh();
        $this->assertSame(Pengajuan::STATUS_TERVERIFIKASI, $p->status);
        $this->assertSame($admin->id, $p->verified_by);
        $this->assertNotNull($p->verified_at);
        $this->assertDatabaseHas('riwayat_pengajuan', ['pengajuan_id' => $p->id, 'aksi' => 'verifikasi']);

        // sudah diproses -> tidak bisa lagi
        $this->postJson("/api/admin/pengajuan/{$p->id}/tolak", ['catatan' => 'x'])->assertStatus(409);
    }

    public function test_generate_surat_menolak_jika_data_belum_lengkap(): void
    {
        Sanctum::actingAs($this->admin());
        $p = $this->pengajuan(Pengajuan::STATUS_TERVERIFIKASI);
        $jenis = JenisPengajuan::create(['kode' => 'test', 'nama' => 'Surat Test', 'deskripsi' => 'x', 'is_active' => true]);
        $p->jenisPengajuans()->attach($jenis->id);

        $this->postJson("/api/admin/pengajuan/{$p->id}/surat/generate")
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['data_kurang']]);
    }

    public function test_isi_data_search_dan_generate_surat_pdf_docx(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->admin());
        $p = $this->pengajuan(Pengajuan::STATUS_TERVERIFIKASI);
        $jenis = JenisPengajuan::create(['kode' => 'test', 'nama' => 'Surat Test', 'deskripsi' => 'x', 'is_active' => true]);
        $p->jenisPengajuans()->attach($jenis->id);

        $this->putJson("/api/admin/pengajuan/{$p->id}/data", [
            'judul_penelitian' => 'Studi Tegakan & Riap Jati',
            'lokasi_penelitian' => 'Bogor',
            'tanggal_mulai' => '2026-12-01',
            'tanggal_selesai' => '2027-02-01',
            'dosen_pembimbing' => 'Dr. Andi',
            'program' => 'Sarjana',
            'tujuan_penelitian' => 'Pengambilan data skripsi.',
            'instansi' => [
                ['nama_instansi' => 'Perhutani', 'alamat_instansi' => 'Jl. Contoh No. 1'],
                ['nama_instansi' => 'Dinas Kehutanan', 'alamat_instansi' => 'Jl. Contoh No. 2'],
            ],
        ])->assertOk();

        $this->getJson('/api/admin/pengajuan?q=Budi')->assertOk()->assertJsonPath('data.total', 1);

        $res = $this->postJson("/api/admin/pengajuan/{$p->id}/surat/generate")->assertStatus(201);

        // 1 jenis x 2 instansi = 2 surat
        $this->assertCount(2, $res->json('data'));
        $this->assertSame(Pengajuan::STATUS_SURAT_DIBUAT, $p->fresh()->status);

        $suratId = $res->json('data.0.id');
        Storage::disk('local')->assertExists($res->json('data.0.path_pdf'));
        Storage::disk('local')->assertExists($res->json('data.0.path_docx'));

        $this->get("/api/admin/surat/{$suratId}/download")->assertOk();
        $this->get("/api/admin/surat/{$suratId}/download/docx")->assertOk();

        $nomor = $res->json('data.0.nomor_surat');
        $this->getJson('/api/admin/pengajuan?q=' . urlencode($nomor))->assertOk()->assertJsonPath('data.total', 1);
    }
}
