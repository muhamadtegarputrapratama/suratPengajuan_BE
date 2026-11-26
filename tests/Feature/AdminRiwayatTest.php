<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanInstansi;
use App\Models\RiwayatPengajuan;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role = User::ROLE_ADMIN_KPAP): void
    {
        $u = User::firstOrNew(['email' => "{$role}@test.com"]);
        $u->forceFill(['name' => 'Tester', 'password' => 'password123', 'role' => $role])->save();
        Sanctum::actingAs($u);
    }

    /** @return array{0:Pengajuan,1:Pengajuan,2:Surat} [menunggu, selesai, surat] */
    private function siapkanData(): array
    {
        $dep = Departemen::create(['kode' => 'MNH', 'nama' => 'Manajemen Hutan', 'prefix_nim' => 'E141']);
        $jenis = JenisPengajuan::create(['kode' => 'test', 'nama' => 'Surat Test', 'deskripsi' => 'x', 'is_active' => true]);

        $menunggu = Pengajuan::create([
            'nim' => 'E1410000001',
            'nama_mahasiswa' => 'Andi',
            'departemen_id' => $dep->id,
            'status' => Pengajuan::STATUS_MENUNGGU,
        ]);

        $selesai = Pengajuan::create([
            'nim' => 'E1410000002',
            'nama_mahasiswa' => 'Budi',
            'departemen_id' => $dep->id,
            'status' => Pengajuan::STATUS_SURAT_DIBUAT,
            'program' => 'Sarjana',
            'judul_penelitian' => 'Studi Riap Jati',
            'lokasi_penelitian' => 'Bogor',
            'tanggal_mulai' => '2026-12-01',
            'tanggal_selesai' => '2026-12-15',
            'dosen_pembimbing' => 'Dr. Andi',
        ]);
        $selesai->jenisPengajuans()->attach($jenis->id);

        $instansi = PengajuanInstansi::create([
            'pengajuan_id' => $selesai->id,
            'nama_instansi' => 'Perhutani',
            'alamat_instansi' => 'Jl. Contoh No. 1',
        ]);

        $surat = Surat::create([
            'pengajuan_id' => $selesai->id,
            'jenis_pengajuan_id' => $jenis->id,
            'pengajuan_instansi_id' => $instansi->id,
            'nomor_surat' => '1/IT3.F4/PT.01.04/M/B/2026',
            'path_pdf' => 'surat/x.pdf',
            'path_docx' => 'surat/x.docx',
            'generated_at' => now(),
        ]);

        RiwayatPengajuan::create([
            'pengajuan_id' => $selesai->id,
            'aksi' => 'generate_surat',
            'status_dari' => Pengajuan::STATUS_TERVERIFIKASI,
            'status_ke' => Pengajuan::STATUS_SURAT_DIBUAT,
        ]);

        return [$menunggu, $selesai, $surat];
    }

    public function test_riwayat_hanya_menampilkan_yang_sudah_diproses_beserta_datanya(): void
    {
        $this->login();
        $this->siapkanData();

        $res = $this->getJson('/api/admin/riwayat')->assertOk();

        $res->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nama_mahasiswa', 'Budi')
            ->assertJsonPath('data.data.0.judul_penelitian', 'Studi Riap Jati')
            ->assertJsonPath('data.data.0.lokasi_penelitian', 'Bogor')
            ->assertJsonPath('data.data.0.departemen', 'Manajemen Hutan')
            ->assertJsonPath('data.data.0.instansi.0.nama', 'Perhutani')
            ->assertJsonPath('data.data.0.surat.0.nomor_surat', '1/IT3.F4/PT.01.04/M/B/2026');

        $this->assertStringContainsString('/download/pdf', $res->json('data.data.0.surat.0.url_pdf'));
        $this->assertStringContainsString('/download/docx', $res->json('data.data.0.surat.0.url_docx'));
    }

    public function test_riwayat_bisa_dicari_dan_difilter(): void
    {
        $this->login();
        $this->siapkanData();

        $this->getJson('/api/admin/riwayat?q=Studi')->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/riwayat?q=' . urlencode('1/IT3.F4'))->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson('/api/admin/riwayat?q=tidak-ada')->assertOk()->assertJsonPath('data.total', 0);

        $this->getJson('/api/admin/riwayat?status=menunggu_verifikasi')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nama_mahasiswa', 'Andi');
    }

    public function test_detail_riwayat_memuat_aktivitas(): void
    {
        $this->login();
        [, $selesai] = $this->siapkanData();

        $this->getJson("/api/admin/riwayat/{$selesai->id}")
            ->assertOk()
            ->assertJsonPath('data.nama_mahasiswa', 'Budi')
            ->assertJsonPath('data.aktivitas.0.aksi', 'generate_surat');

        $this->getJson('/api/admin/riwayat/999999')->assertStatus(404);
    }

    public function test_bukan_admin_kpap_ditolak(): void
    {
        $this->login(User::ROLE_ADMIN_DEPARTEMEN);

        $this->getJson('/api/admin/riwayat')->assertStatus(403);
    }
}