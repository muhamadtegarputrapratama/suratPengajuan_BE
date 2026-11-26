<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\User;
use Database\Seeders\AdminDepartemenSeeder;
use Database\Seeders\AdminKpapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDepartemenSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'akun.kpap.email' => 'adminkpap@gmail.com',
            'akun.kpap.password' => 'rahasia-kpap-123',
            'akun.departemen.email' => 'admindept@gmail.com',
            'akun.departemen.password' => 'rahasia-dept-123',
        ]);
    }

    private function buatDepartemen(): void
    {
        foreach ([
            ['MNH', 'Manajemen Hutan', 'E141'],
            ['THH', 'Hasil Hutan', 'E241'],
            ['KSHE', 'Konservasi Sumberdaya Hutan dan Ekowisata', 'E341'],
            ['SVK', 'Silvikultur', 'E441'],
        ] as [$kode, $nama, $prefix]) {
            Departemen::create(['kode' => $kode, 'nama' => $nama, 'prefix_nim' => $prefix]);
        }
    }

    public function test_akun_admin_kpap_dibuat(): void
    {
        $this->seed(AdminKpapSeeder::class);

        $u = User::where('email', 'adminkpap@gmail.com')->first();

        $this->assertNotNull($u);
        $this->assertSame(User::ROLE_ADMIN_KPAP, $u->role);
        $this->assertTrue(Hash::check('rahasia-kpap-123', $u->password));
    }

    public function test_tanpa_password_tidak_ada_akun_dibuat(): void
    {
        config(['akun.kpap.password' => null, 'akun.departemen.password' => '']);
        $this->buatDepartemen();

        $this->seed(AdminKpapSeeder::class);
        $this->seed(AdminDepartemenSeeder::class);

        $this->assertSame(0, User::count());
    }

    public function test_satu_akun_dibuat_untuk_setiap_departemen(): void
    {
        $this->buatDepartemen();

        $this->seed(AdminDepartemenSeeder::class);

        $akun = User::where('role', User::ROLE_ADMIN_DEPARTEMEN)->get();

        $this->assertCount(4, $akun);
        $this->assertSame(
            Departemen::pluck('id')->sort()->values()->all(),
            $akun->pluck('departemen_id')->sort()->values()->all()
        );

        $mnh = Departemen::where('kode', 'MNH')->first();
        $this->assertSame($mnh->id, User::where('email', 'admindept+mnh@gmail.com')->value('departemen_id'));
        $this->assertTrue(Hash::check('rahasia-dept-123', $akun->first()->password));
    }

    public function test_seeder_dijalankan_lagi_tidak_duplikat_dan_tidak_mereset_password(): void
    {
        $this->buatDepartemen();
        $this->seed(AdminDepartemenSeeder::class);

        $akun = User::where('role', User::ROLE_ADMIN_DEPARTEMEN)->first();
        $akun->forceFill(['password' => 'password-baru-123'])->save();

        $this->seed(AdminDepartemenSeeder::class);

        $this->assertSame(4, User::where('role', User::ROLE_ADMIN_DEPARTEMEN)->count());
        $this->assertTrue(Hash::check('password-baru-123', $akun->fresh()->password));
    }

    public function test_tanpa_departemen_tidak_membuat_akun(): void
    {
        $this->seed(AdminDepartemenSeeder::class);

        $this->assertSame(0, User::where('role', User::ROLE_ADMIN_DEPARTEMEN)->count());
    }
}