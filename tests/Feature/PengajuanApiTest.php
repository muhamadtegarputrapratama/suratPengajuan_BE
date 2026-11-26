<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

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

        // Semua route kini wajib login: masuk sebagai admin departemen MNH.
        $akun = new User();
        $akun->forceFill([
            'name' => 'Admin MNH',
            'email' => 'admin.mnh@test.com',
            'password' => 'rahasia-123',
            'role' => User::ROLE_ADMIN_DEPARTEMEN,
            'departemen_id' => $this->departemen->id,
        ])->save();

        Sanctum::actingAs($akun);
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



      public function test_upload_berkas_berhasil(): void
    {
        Storage::fake('public');

        $pengajuan = $this->buatPengajuan();

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
                'surat_permohonan_izin_penelitian' => UploadedFile::fake()->create(
                    'surat_permohonan_izin_penelitian.pdf',
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

        $this->assertDatabaseHas('berkas_pengajuan', [
            'pengajuan_id' => $pengajuan->id,
            'nama_berkas' => 'Surat Permohonan Izin Penelitian',
        ]);

        $this->assertDatabaseCount('berkas_pengajuan', 3);
    }

    public function test_upload_berkas_ke_pengajuan_tidak_ada_ditolak(): void
    {
        $response = $this->post(
            '/api/pengajuan/999999/berkas',
            []
        );

        $response->assertStatus(404);
    }

    public function test_pengajuan_yang_sudah_terverifikasi_tidak_bisa_upload(): void
    {
        Storage::fake('public');

        $pengajuan = $this->buatPengajuan([
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


    public function test_daftar_pengajuan_berhasil(): void
    {
        $this->buatPengajuan([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
        ]);

        $this->buatPengajuan([
            'nim' => 'E1411234568',
            'nama_mahasiswa' => 'Andi Saputra',
        ]);

        $response = $this->getJson('/api/pengajuan?per_page=1');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.per_page', 1);
    }



    public function test_detail_pengajuan_berhasil(): void
    {
        $pengajuan = $this->buatPengajuan([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
        ]);

        $response = $this->getJson(
            "/api/pengajuan/{$pengajuan->id}"
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nim', 'E1411234567')
            ->assertJsonPath('data.nama_mahasiswa', 'Budi Santoso')
            ->assertJsonPath('data.departemen.kode', 'MNH');
    }

    public function test_detail_pengajuan_tidak_ditemukan(): void
    {
        $response = $this->getJson('/api/pengajuan/999999');

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }



    public function test_pencarian_pengajuan_berdasarkan_nim(): void
    {
        $this->buatPengajuan([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
        ]);

        $this->buatPengajuan([
            'nim' => 'E1411234568',
            'nama_mahasiswa' => 'Andi Saputra',
        ]);

        $response = $this->getJson(
            '/api/pengajuan/search?q=E1411234567'
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nim', 'E1411234567');
    }

    public function test_pencarian_pengajuan_berdasarkan_nama(): void
    {
        $this->buatPengajuan([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
        ]);

        $this->buatPengajuan([
            'nim' => 'E1411234568',
            'nama_mahasiswa' => 'Andi Saputra',
        ]);

        $response = $this->getJson(
            '/api/pengajuan/search?q=Budi'
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nama_mahasiswa', 'Budi Santoso');
    }

    public function test_pencarian_dengan_keyword_kosong_ditolak(): void
    {
        $response = $this->getJson(
            '/api/pengajuan/search?q='
        );

        $response->assertStatus(422);
    }



    public function test_riwayat_pengajuan_berdasarkan_nim(): void
    {
        $this->buatPengajuan([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
        ]);

        $this->buatPengajuan([
            'nim' => 'E1411234568',
            'nama_mahasiswa' => 'Andi Saputra',
        ]);

        $response = $this->getJson(
            '/api/riwayat-pengajuan?nim=E1411234567'
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.nim', 'E1411234567');
    }

    public function test_riwayat_tanpa_nim_ditolak(): void
    {
        $response = $this->getJson('/api/riwayat-pengajuan');

        $response->assertStatus(422);
    }


    public function test_filter_pengajuan_berdasarkan_status(): void
    {
        $this->buatPengajuan([
            'nim' => 'E1411234567',
            'status' => 'menunggu_verifikasi',
        ]);

        $this->buatPengajuan([
            'nim' => 'E1411234568',
            'status' => 'terverifikasi',
        ]);

        $response = $this->getJson(
            '/api/pengajuan?status=menunggu_verifikasi'
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath(
                'data.data.0.status',
                'menunggu_verifikasi'
            );
    }



    private function buatPengajuan(array $data = []): Pengajuan
    {
        $pengajuan = Pengajuan::create(array_merge([
            'nim' => 'E1411234567',
            'nama_mahasiswa' => 'Budi Santoso',
            'departemen_id' => $this->departemen->id,
            'status' => 'menunggu_verifikasi',
        ], $data));

        $pengajuan->jenisPengajuans()->attach(
            $this->jenisPengajuan->id
        );

        return $pengajuan;
    }
}
