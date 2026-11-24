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

    public function test_admin_kpap_bisa_melihat_foto_berkas(): void
    {
        Storage::fake('local');
        config(['surat.berkas_disk' => 'local']);

        $admin = User::firstOrNew(['email' => 'kpap@test.com']);
        $admin->forceFill(['name' => 'Admin', 'password' => 'password123', 'role' => User::ROLE_ADMIN_KPAP])->save();
        Sanctum::actingAs($admin);

        $dep = Departemen::create(['kode' => 'MNH', 'nama' => 'Manajemen Hutan', 'prefix_nim' => 'E141']);
        $p = Pengajuan::create([
            'nim' => 'E1410000001',
            'nama_mahasiswa' => 'Budi',
            'departemen_id' => $dep->id,
            'status' => Pengajuan::STATUS_MENUNGGU,
        ]);

        Storage::disk('local')->putFileAs('berkas', UploadedFile::fake()->image('surat.jpg'), 'surat.jpg');
        $berkas = BerkasPengajuan::create([
            'pengajuan_id' => $p->id,
            'nama_berkas' => 'surat.jpg',
            'path_berkas' => 'berkas/surat.jpg',
        ]);

        $this->get("/api/admin/berkas/{$berkas->id}/preview")->assertOk();
        $this->getJson('/api/admin/berkas/9999/preview')->assertStatus(404);
    }
}