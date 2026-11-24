<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\RiwayatPengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifikasiController extends Controller
{
    /** POST /api/admin/pengajuan/{id}/verifikasi  (catatan opsional) */
    public function verifikasi(Request $request, int $id)
    {
        return $this->ubahStatus($request, $id, Pengajuan::STATUS_TERVERIFIKASI, false, 'verifikasi', 'Pengajuan berhasil diverifikasi.');
    }

    /** POST /api/admin/pengajuan/{id}/tolak  (catatan wajib) */
    public function tolak(Request $request, int $id)
    {
        return $this->ubahStatus($request, $id, Pengajuan::STATUS_DITOLAK, true, 'tolak', 'Pengajuan ditolak.');
    }

    /** POST /api/admin/pengajuan/{id}/revisi  (catatan wajib) */
    public function revisi(Request $request, int $id)
    {
        return $this->ubahStatus($request, $id, Pengajuan::STATUS_PERLU_REVISI, true, 'minta_revisi', 'Pengajuan dikembalikan untuk revisi.');
    }

    private function ubahStatus(Request $request, int $id, string $statusBaru, bool $wajibCatatan, string $aksi, string $pesan)
    {
        $request->validate([
            'catatan' => [$wajibCatatan ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);

        [$kode, $pesanHasil, $data] = DB::transaction(function () use ($request, $id, $statusBaru, $aksi, $pesan) {
            $pengajuan = Pengajuan::lockForUpdate()->find($id);

            if (!$pengajuan) {
                return [404, 'Pengajuan tidak ditemukan.', null];
            }

            if ($pengajuan->status !== Pengajuan::STATUS_MENUNGGU) {
                return [409, "Pengajuan berstatus {$pengajuan->status}. Hanya status menunggu_verifikasi yang bisa diproses.", null];
            }

            $statusLama = $pengajuan->status;
            $catatan = $request->input('catatan');

            $pengajuan->status = $statusBaru;
            $pengajuan->verified_by = $request->user()->id;
            $pengajuan->verified_at = now();

            if ($statusBaru === Pengajuan::STATUS_DITOLAK) {
                $pengajuan->catatan_penolakan = $catatan;
            } else {
                $pengajuan->catatan_verifikasi = $catatan;
            }

            $pengajuan->save();

            RiwayatPengajuan::create([
                'pengajuan_id' => $pengajuan->id,
                'user_id' => $request->user()->id,
                'aksi' => $aksi,
                'status_dari' => $statusLama,
                'status_ke' => $statusBaru,
                'catatan' => $catatan,
            ]);

            return [200, $pesan, $pengajuan->load(['departemen', 'verifikator:id,name'])];
        });

        return response()->json([
            'success' => $kode === 200,
            'message' => $pesanHasil,
            'data' => $data,
        ], $kode);
    }
}