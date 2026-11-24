<?php

namespace Tests\Feature;

use App\Models\BerkasPengajuan;
use App\Models\Departemen;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminBerkasTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        $u = User::firstOrNew(['email' => "{$role}@test.com"]);
        $u->forceFill(['name' => 'Tester', 'password' => 'password123', 'role' => $role])->save();

        return $u;
    }

    private function berkas(): BerkasPengajuan
    {
        Storage::fake('public');
        config(['surat.berkas_disk' => 'public']);

        $dep = Departemen::create(['kode' => 'MNH', 'nama' => 'Manajemen Hutan', 'prefix_nim' => 'E141']);
        $p = Pengajuan::create([
            'nim' => 'E1410000001',
            'nama_mahasiswa' => 'Budi',
            'departemen_id' => $dep->id,
            'status' => Pengajuan::STATUS_MENUNGGU,
        ]);

        $path = UploadedFile::fake()->image('surat.jpg')->store("pengajuan/{$p->id}", 'public');

        return BerkasPengajuan::create([
            'pengajuan_id' => $p->id,
            'nama_berkas' => 'Surat Rekomendasi',
            'path_berkas' => $path,
            'tipe_berkas' => 'image/jpeg',
        ]);
    }

    public function test_admin_kpap_bisa_melihat_foto_berkas(): void
    {
        Sanctum::actingAs($this->user(User::ROLE_ADMIN_KPAP));
        $berkas = $this->berkas();

        $res = $this->get("/api/admin/berkas/{$berkas->id}/preview");

        $this->assertSame(200, $res->getStatusCode(), 'Isi respons: ' . mb_substr($res->getContent(), 0, 500));
        $this->assertStringStartsWith('image/', (string) $res->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', (string) $res->headers->get('Content-Disposition'));
    }

    public function test_berkas_tidak_ditemukan_menghasilkan_404(): void
    {
        Sanctum::actingAs($this->user(User::ROLE_ADMIN_KPAP));
        $this->berkas();

        $this->getJson('/api/admin/berkas/9999/preview')->assertStatus(404);
    }

    public function test_bukan_admin_kpap_ditolak(): void
    {
        Sanctum::actingAs($this->user(User::ROLE_ADMIN_DEPARTEMEN));
        $berkas = $this->berkas();

        $this->getJson("/api/admin/berkas/{$berkas->id}/preview")->assertStatus(403);
    }
}