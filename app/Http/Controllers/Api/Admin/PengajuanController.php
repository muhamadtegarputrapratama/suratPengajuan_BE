<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\PengajuanInstansi;
use App\Models\RiwayatPengajuan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PengajuanController extends Controller
{
    /**
     * GET /api/admin/pengajuan
     * Query: q (nama/NIM/nomor surat), status, departemen_id, jenis_pengajuan_id, dari, sampai, per_page
     */
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->filtered($request)
            ->with(['departemen', 'jenisPengajuans'])
            ->withCount('berkas')
            ->latest();

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan berhasil diambil.',
            'data' => $query->paginate((int) $request->query('per_page', 10))->withQueryString(),
        ]);
    }

    /** GET /api/admin/pengajuan/{id} */
    public function show(int $id)
    {
        $pengajuan = Pengajuan::with([
            'departemen',
            'jenisPengajuans',
            'berkas',
            'instansis',
            'surats.jenisPengajuan',
            'verifikator:id,name',
        ])->find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pengajuan berhasil diambil.',
            'data' => $pengajuan,
        ]);
    }

    /** GET /api/admin/pengajuan/{id}/riwayat */
    public function riwayat(int $id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Riwayat aktivitas berhasil diambil.',
            'data' => $pengajuan->riwayat()->with('user:id,name')->get(),
        ]);
    }

    /**
     * PUT /api/admin/pengajuan/{id}/data
     * Admin KPAP mengisi data penelitian & instansi berdasarkan foto surat rekomendasi.
     */
    public function updateData(Request $request, int $id)
    {
        $data = $request->validate([
            'judul_penelitian' => ['nullable', 'string', 'max:255'],
            'lokasi_penelitian' => ['nullable', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'dosen_pembimbing' => ['nullable', 'string', 'max:255'],
            'tujuan_penelitian' => ['nullable', 'string'],
            'program' => ['nullable', 'string', 'max:50'],
            'bentuk_bantuan' => ['nullable', 'string'],
            'instansi' => ['nullable', 'array'],
            'instansi.*.nama_instansi' => ['required_with:instansi', 'string', 'max:255'],
            'instansi.*.alamat_instansi' => ['nullable', 'string'],
        ]);

        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        if ($pengajuan->status === Pengajuan::STATUS_SURAT_DIBUAT) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak bisa diubah karena surat sudah dibuat.',
            ], 409);
        }

        DB::transaction(function () use ($request, $pengajuan, $data) {
            $pengajuan->update(collect($data)->except('instansi')->all());

            if (array_key_exists('instansi', $data)) {
                $pengajuan->instansis()->delete();
                foreach ($data['instansi'] ?? [] as $row) {
                    PengajuanInstansi::create([
                        'pengajuan_id' => $pengajuan->id,
                        'nama_instansi' => $row['nama_instansi'],
                        'alamat_instansi' => $row['alamat_instansi'] ?? null,
                    ]);
                }
            }

            RiwayatPengajuan::create([
                'pengajuan_id' => $pengajuan->id,
                'user_id' => $request->user()->id,
                'aksi' => 'koreksi_data',
                'status_dari' => $pengajuan->status,
                'status_ke' => $pengajuan->status,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data pengajuan berhasil diperbarui.',
            'data' => $pengajuan->fresh(['departemen', 'jenisPengajuans', 'instansis']),
        ]);
    }

    /** GET /api/admin/pengajuan-export  (CSV, memakai filter yang sama dengan daftar) */
    public function export(Request $request)
    {
        $query = $this->filtered($request)
            ->with(['departemen', 'jenisPengajuans', 'surats'])
            ->latest();

        $filename = 'laporan-pengajuan-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($out, [
                'ID', 'NIM', 'Nama Mahasiswa', 'Departemen', 'Jenis Pengajuan',
                'Status', 'Nomor Surat', 'Diverifikasi Pada', 'Dibuat Pada',
            ]);

            $query->chunk(200, function ($rows) use ($out) {
                foreach ($rows as $p) {
                    fputcsv($out, [
                        $p->id,
                        $p->nim,
                        $p->nama_mahasiswa,
                        $p->departemen?->nama,
                        $p->jenisPengajuans->pluck('nama')->implode('; '),
                        $p->status,
                        $p->surats->pluck('nomor_surat')->implode('; '),
                        $p->verified_at?->format('Y-m-d H:i:s'),
                        $p->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request): Builder
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Pengajuan::STATUSES)],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'jenis_pengajuan_id' => ['nullable', 'integer', 'exists:jenis_pengajuan,id'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $query = Pengajuan::query();

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($q) {
                $w->where('nama_mahasiswa', 'like', "%{$q}%")
                    ->orWhere('nim', 'like', "%{$q}%")
                    ->orWhereHas('surats', fn ($s) => $s->where('nomor_surat', 'like', "%{$q}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('departemen_id')) {
            $query->where('departemen_id', $request->query('departemen_id'));
        }

        if ($request->filled('jenis_pengajuan_id')) {
            $query->whereHas('jenisPengajuans', fn ($j) => $j->where('jenis_pengajuan.id', $request->query('jenis_pengajuan_id')));
        }

        if ($request->filled('dari')) {
            $query->whereDate('created_at', '>=', $request->query('dari'));
        }

        if ($request->filled('sampai')) {
            $query->whereDate('created_at', '<=', $request->query('sampai'));
        }

        return $query;
    }
}