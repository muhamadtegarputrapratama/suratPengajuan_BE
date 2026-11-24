<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\RiwayatPengajuan;
use App\Models\Surat;
use App\Services\SuratDocxGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SuratController extends Controller
{
    private const MIME_DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /** GET /api/admin/pengajuan/{id}/surat */
    public function index(int $id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar surat berhasil diambil.',
            'data' => $pengajuan->surats()->with(['jenisPengajuan', 'instansi'])->get(),
        ]);
    }

    /**
     * POST /api/admin/pengajuan/{id}/surat/generate
     * Satu surat (PDF + DOCX) per kombinasi jenis pengajuan x instansi.
     */
    public function generate(Request $request, int $id, SuratDocxGenerator $docx)
    {
        $pengajuan = Pengajuan::with(['departemen', 'jenisPengajuans', 'instansis'])->find($id);

        if (!$pengajuan) {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        if ($pengajuan->status !== Pengajuan::STATUS_TERVERIFIKASI) {
            return response()->json([
                'success' => false,
                'message' => 'Surat hanya bisa dibuat untuk pengajuan berstatus terverifikasi.',
            ], 409);
        }

        $kurang = $this->dataKurang($pengajuan);
        if ($kurang) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan belum lengkap untuk membuat surat.',
                'errors' => ['data_kurang' => $kurang],
            ], 422);
        }

        $disk = Storage::disk(config('surat.disk'));
        $dibuat = collect();

        DB::transaction(function () use ($request, $pengajuan, $disk, $docx, &$dibuat) {
            foreach ($pengajuan->jenisPengajuans as $jenis) {
                foreach ($pengajuan->instansis as $instansi) {
                    $nomor = $this->nomorBerikut();
                    $tanggal = now();
                    $view = config("surat.template.{$jenis->kode}", config('surat.default_template'));

                    $dasar = "surat/{$pengajuan->id}/" . Str::slug($nomor . '-' . $jenis->kode . '-' . $instansi->id);

                    $pdf = Pdf::loadView($view, [
                        'pengajuan' => $pengajuan,
                        'jenis' => $jenis,
                        'instansi' => $instansi,
                        'nomor' => $nomor,
                        'tanggal' => $tanggal,
                    ])->setPaper('a4');

                    $disk->put("{$dasar}.pdf", $pdf->output());
                    $disk->put("{$dasar}.docx", $docx->render($pengajuan, $jenis, $instansi, $nomor, $tanggal));

                    $dibuat->push(Surat::create([
                        'pengajuan_id' => $pengajuan->id,
                        'jenis_pengajuan_id' => $jenis->id,
                        'pengajuan_instansi_id' => $instansi->id,
                        'nomor_surat' => $nomor,
                        'path_pdf' => "{$dasar}.pdf",
                        'path_docx' => "{$dasar}.docx",
                        'generated_by' => $request->user()->id,
                        'generated_at' => $tanggal,
                    ]));
                }
            }

            $pengajuan->update(['status' => Pengajuan::STATUS_SURAT_DIBUAT]);

            RiwayatPengajuan::create([
                'pengajuan_id' => $pengajuan->id,
                'user_id' => $request->user()->id,
                'aksi' => 'generate_surat',
                'status_dari' => Pengajuan::STATUS_TERVERIFIKASI,
                'status_ke' => Pengajuan::STATUS_SURAT_DIBUAT,
                'catatan' => $dibuat->count() . ' surat dibuat (PDF + DOCX)',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Surat berhasil dibuat.',
            'data' => $dibuat,
        ], 201);
    }

    /** GET /api/admin/surat/{id}/preview  (PDF inline) */
    public function preview(int $id)
    {
        $surat = Surat::find($id);
        $disk = Storage::disk(config('surat.disk'));

        if (!$surat || !$disk->exists($surat->path_pdf)) {
            return response()->json(['success' => false, 'message' => 'File surat tidak ditemukan.'], 404);
        }

        return response()->file($disk->path($surat->path_pdf), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($surat->path_pdf) . '"',
        ]);
    }

    /** GET /api/admin/surat/{id}/download/{format?}  format = pdf (default) | docx */
    public function download(int $id, string $format = 'pdf')
    {
        $surat = Surat::find($id);
        $disk = Storage::disk(config('surat.disk'));

        $path = $format === 'docx' ? $surat?->path_docx : $surat?->path_pdf;

        if (!$surat || !$path || !$disk->exists($path)) {
            return response()->json(['success' => false, 'message' => 'File surat tidak ditemukan.'], 404);
        }

        return $disk->download($path, basename($path), [
            'Content-Type' => $format === 'docx' ? self::MIME_DOCX : 'application/pdf',
        ]);
    }

    /** Daftar data yang masih kosong dan wajib ada sebelum surat dibuat. */
    private function dataKurang(Pengajuan $pengajuan): array
    {
        $wajib = [
            'judul_penelitian' => 'Judul penelitian',
            'program' => 'Program (Sarjana/Magister/Doktor)',
            'tanggal_mulai' => 'Tanggal mulai',
            'tanggal_selesai' => 'Tanggal selesai',
            'dosen_pembimbing' => 'Komisi pembimbing',
        ];

        $views = $pengajuan->jenisPengajuans->map(
            fn ($j) => config("surat.template.{$j->kode}", config('surat.default_template'))
        );

        if ($views->contains('surat.izin-penelitian')) {
            $wajib['lokasi_penelitian'] = 'Lokasi penelitian';
        }

        if ($views->contains('surat.permohonan-bantuan')) {
            $wajib['bentuk_bantuan'] = 'Bentuk bantuan yang diminta';
        }

        $kurang = [];
        foreach ($wajib as $kolom => $label) {
            if (blank($pengajuan->{$kolom})) {
                $kurang[] = $label;
            }
        }

        if ($pengajuan->instansis->isEmpty()) {
            $kurang[] = 'Instansi tujuan (minimal satu)';
        }

        if ($pengajuan->jenisPengajuans->isEmpty()) {
            $kurang[] = 'Jenis pengajuan';
        }

        return $kurang;
    }

    private function nomorBerikut(): string
    {
        $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $now = now();

        $urut = Surat::whereYear('created_at', $now->year)->lockForUpdate()->count() + 1;

        return sprintf(config('surat.format_nomor'), $urut, $romawi[$now->month], $now->year);
    }
}