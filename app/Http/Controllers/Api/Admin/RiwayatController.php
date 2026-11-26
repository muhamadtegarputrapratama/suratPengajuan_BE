<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Support\SuratFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiwayatController extends Controller
{
    /** Status yang dianggap "sudah diproses" (masuk Riwayat secara default). */
    private const STATUS_DIPROSES = [
        Pengajuan::STATUS_TERVERIFIKASI,
        Pengajuan::STATUS_PERLU_REVISI,
        Pengajuan::STATUS_DITOLAK,
        Pengajuan::STATUS_SURAT_DIBUAT,
    ];

    private const RELASI = [
        'departemen',
        'jenisPengajuans',
        'instansis',
        'verifikator:id,name',
        'surats.jenisPengajuan',
        'surats.instansi',
    ];

    /**
     * GET /api/admin/riwayat
     * Query: q (nama/NIM/judul/nomor surat), status, departemen_id, jenis_pengajuan_id, dari, sampai, per_page
     * Tanpa `status`, yang tampil adalah semua pengajuan yang sudah diproses.
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', Rule::in(Pengajuan::STATUSES)],
            'departemen_id' => ['nullable', 'integer', 'exists:departemens,id'],
            'jenis_pengajuan_id' => ['nullable', 'integer', 'exists:jenis_pengajuan,id'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Pengajuan::query()->with(self::RELASI);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->whereIn('status', self::STATUS_DIPROSES);
        }

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($q) {
                $w->where('nama_mahasiswa', 'like', "%{$q}%")
                    ->orWhere('nim', 'like', "%{$q}%")
                    ->orWhere('judul_penelitian', 'like', "%{$q}%")
                    ->orWhereHas('surats', fn ($s) => $s->where('nomor_surat', 'like', "%{$q}%"));
            });
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

        $hasil = $query
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate((int) $request->query('per_page', 10))
            ->withQueryString()
            ->through(fn (Pengajuan $p) => $this->format($p));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pengajuan berhasil diambil.',
            'data' => $hasil,
        ]);
    }

    /** GET /api/admin/riwayat/{id}  -> data lengkap + surat + log aktivitas */
    public function show(int $id)
    {
        $pengajuan = Pengajuan::with(array_merge(self::RELASI, ['riwayat.user:id,name']))->find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        $data = $this->format($pengajuan);
        $data['aktivitas'] = $pengajuan->riwayat->map(fn ($r) => [
            'aksi' => $r->aksi,
            'status_dari' => $r->status_dari,
            'status_ke' => $r->status_ke,
            'catatan' => $r->catatan,
            'oleh' => $r->user?->name,
            'waktu' => $r->created_at?->toIso8601String(),
        ])->values();

        return response()->json([
            'success' => true,
            'message' => 'Detail riwayat berhasil diambil.',
            'data' => $data,
        ]);
    }

    private function format(Pengajuan $p): array
    {
        return [
            'id' => $p->id,
            'nim' => $p->nim,
            'nama_mahasiswa' => $p->nama_mahasiswa,
            'departemen' => $p->departemen?->nama,
            'program' => $p->program,
            'judul_penelitian' => $p->judul_penelitian,
            'lokasi_penelitian' => $p->lokasi_penelitian,
            'tanggal_mulai' => $p->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $p->tanggal_selesai?->toDateString(),
            'periode' => SuratFormat::periode($p->tanggal_mulai, $p->tanggal_selesai),
            'dosen_pembimbing' => $p->dosen_pembimbing,
            'bentuk_bantuan' => $p->bentuk_bantuan,
            'jenis_pengajuan' => $p->jenisPengajuans->pluck('nama')->values(),
            'instansi' => $p->instansis->map(fn ($i) => [
                'nama' => $i->nama_instansi,
                'alamat' => $i->alamat_instansi,
            ])->values(),
            'status' => $p->status,
            'catatan_verifikasi' => $p->catatan_verifikasi,
            'catatan_penolakan' => $p->catatan_penolakan,
            'diverifikasi_oleh' => $p->verifikator?->name,
            'diverifikasi_pada' => $p->verified_at?->toIso8601String(),
            'diajukan_pada' => $p->created_at?->toIso8601String(),
            'surat' => $p->surats->map(fn ($s) => [
                'id' => $s->id,
                'nomor_surat' => $s->nomor_surat,
                'jenis' => $s->jenisPengajuan?->nama,
                'instansi' => $s->instansi?->nama_instansi,
                'dibuat_pada' => $s->generated_at?->toIso8601String(),
                'url_preview' => url("/api/admin/surat/{$s->id}/preview"),
                'url_pdf' => url("/api/admin/surat/{$s->id}/download/pdf"),
                'url_docx' => $s->path_docx ? url("/api/admin/surat/{$s->id}/download/docx") : null,
            ])->values(),
        ];
    }
}