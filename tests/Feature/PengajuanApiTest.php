<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\BerkasPengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PengajuanApiTest extends TestCase
{
    use RefreshDatabase;

    protected $departemen;
    protected $jenisPengajuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->departemen = Departemen::create([
            'kode' => 'MNH',
            'nama' => 'Manajemen Hutan',
            'prefix_nim' => 'E141',
        ]);

        $this->jenisPengajuan = JenisPengajuan::create([
            'kode' => 'PENELITIAN',
            'nama' => 'Pengajuan Penelitian',
            'deskripsi' => 'Pengajuan surat penelitian.',
            'is_active' => true,
        ]);
    }

    public function test_nim_valid_berhasil_dikenali(): void
    {
        $response = $this->postJson('/api/cari-mahasiswa', [
            'nim' => 'E1411234567',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.departemen.kode', 'MNH');
    }

    public function test_nim_tidak_valid_ditolak(): void
    {
        $response = $this->postJson('/api/cari-mahasiswa', [
            'nim' => 'E14112345',
        ]);

        $response->assertStatus(422);
    }

    public function test_prefix_nim_tidak_dikenal_ditolak(): void
    {
        $response = $this->postJson('/api/cari-mahasiswa', [
            'nim' => 'E9991234567',
        ]);

        $response->assertStatus(404);
    }

    public function test_pengajuan_berhasil_dibuat(): void
    {
        $response = $this->postJson('/api/pengajuan', [
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
            'jenis_pengajuan' => [$this->jenisPengajuan->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('pengajuans', [
            'nim' => 'E1411234567',
            'departemen_id' => $this->departemen->id,
            'status' => 'menunggu_verifikasi',
        ]);
    }

    public function test_pengajuan_dengan_jenis_kosong_ditolak(): void
    {
        $response = $this->postJson('/api/pengajuan', [
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
            'jenis_pengajuan' => [],
        ]);

        $response->assertStatus(422);
    }


public function test_upload_berkas_berhasil(): void
{
    Storage::fake('public');

    $pengajuan = Pengajuan::create([
        'nim' => 'E1411234567',
        'nama_mahasiswa' => 'Budi Santoso',
        'departemen_id' => $this->departemen->id,
        'status' => 'menunggu_verifikasi',
    ]);

    $response = $this->post(
        "/api/pengajuan/{$pengajuan->id}/berkas",
        [
            'surat_rekomendasi' => UploadedFile::fake()->create(
                'surat_rekomendasi.pdf',
                100,
                'application/pdf'
            ),
            'bukti_spp' => UploadedFile::fake()->create(
                'bukti_spp.pdf',
                100,
                'application/pdf'
            ),
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('berkas_pengajuan', [
        'pengajuan_id' => $pengajuan->id,
        'nama_berkas' => 'Surat Rekomendasi',
    ]);

    $this->assertDatabaseHas('berkas_pengajuan', [
        'pengajuan_id' => $pengajuan->id,
        'nama_berkas' => 'Bukti Pembayaran SPP',
    ]);

    $this->assertDatabaseCount('berkas_pengajuan', 2);
}


    public function test_upload_berkas_ke_pengajuan_tidak_ada_ditolak(): void
    {
        $response = $this->post(
            '/api/pengajuan/999999/berkas',
            []
        );

        $response->assertStatus(404);
    }

    public function test_daftar_departemen_berhasil_diakses(): void
{
    $response = $this->getJson('/api/departemen');

    $response->assertOk()
        ->assertJsonPath('success', true);
}

public function test_daftar_jenis_pengajuan_berhasil_diakses(): void
{
    $response = $this->getJson('/api/jenis-pengajuan');

    $response->assertOk()
        ->assertJsonPath('success', true);
}

public function test_jenis_pengajuan_nonaktif_ditolak(): void
{
    $this->jenisPengajuan->update([
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/pengajuan', [
        'nim' => 'E1411234567',
        'nama_mahasiswa' => 'Budi Santoso',
        'jenis_pengajuan' => [$this->jenisPengajuan->id],
    ]);

    $response->assertStatus(422);
}

public function test_pengajuan_yang_sudah_terverifikasi_tidak_bisa_upload(): void
{
    Storage::fake('public');

    $pengajuan = Pengajuan::create([
        'nim' => 'E1411234567',
        'nama_mahasiswa' => 'Budi Santoso',
        'departemen_id' => $this->departemen->id,
        'status' => 'terverifikasi',
    ]);

    $response = $this->post(
        "/api/pengajuan/{$pengajuan->id}/berkas",
        [
            'nama_berkas' => ['Surat Rekomendasi'],
            'berkas' => [
                UploadedFile::fake()->create(
                    'surat.pdf',
                    100,
                    'application/pdf'
                ),
            ],
        ]
    );

    $response->assertStatus(422);
}
}
