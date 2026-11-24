<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\BerkasPengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BerkasPengajuanController extends Controller
{
    public function store(Request $request, $id)
    {
        $pengajuan = Pengajuan::find($id);

        if (!$pengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan tidak ditemukan.',
            ], 404);
        }

        if ($pengajuan->status !== 'menunggu_verifikasi') {
            return response()->json([
                'success' => false,
                'message' => 'Berkas hanya dapat diunggah saat pengajuan menunggu verifikasi.',
            ], 422);
        }


        $validator = Validator::make($request->all(), [
            'surat_rekomendasi' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'bukti_spp' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
                'surat_permohonan_izin_penelitian' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
        ], [
            'surat_rekomendasi.required' => 'Surat Rekomendasi wajib diunggah.',
            'surat_rekomendasi.mimes' => 'Surat Rekomendasi harus berformat PDF, JPG, JPEG, atau PNG.',
            'surat_rekomendasi.max' => 'Ukuran Surat Rekomendasi maksimal 2 MB.',

            'bukti_spp.required' => 'Bukti Pembayaran SPP wajib diunggah.',
            'bukti_spp.mimes' => 'Bukti Pembayaran SPP harus berformat PDF, JPG, JPEG, atau PNG.',
            'bukti_spp.max' => 'Ukuran Bukti Pembayaran SPP maksimal 2 MB.',

            'surat_permohonan_izin_penelitian.required' => 'Surat Permohonan Izin Penelitian wajib diunggah.',
            'surat_permohonan_izin_penelitian.mimes' => 'Surat Permohonan Izin Penelitian harus berformat PDF, JPG, JPEG, atau PNG.',
            'surat_permohonan_izin_penelitian.max' => 'Ukuran Surat Permohonan Izin Penelitian maksimal 2 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi berkas gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cegah upload ulang jika berkas sudah tersedia
        $existingFiles = BerkasPengajuan::where(
            'pengajuan_id',
            $pengajuan->id
        )->whereIn('nama_berkas', [
            'Surat Rekomendasi',
            'Bukti Pembayaran SPP',
            'Surat Permohonan Izin Penelitian',
        ])->exists();

        if ($existingFiles) {
            return response()->json([
                'success' => false,
                'message' => 'Berkas pengajuan sudah pernah diunggah.',
            ], 422);
        }

        $files = [
            'Surat Rekomendasi' => $request->file('surat_rekomendasi'),
            'Bukti Pembayaran SPP' => $request->file('bukti_spp'),
            'Surat Permohonan Izin Penelitian' => $request->file('surat_permohonan_izin_penelitian'),
        ];

        $storedPaths = [];

        try {
            $hasil = DB::transaction(function () use (
                $files,
                $pengajuan,
                &$storedPaths
            ) {
                $hasil = [];

                foreach ($files as $namaBerkas => $file) {
                    $path = $file->store(
                        "pengajuan/{$pengajuan->id}",
                        'public'
                    );

                    if (!$path) {
                        throw new \RuntimeException(
                            'Gagal menyimpan berkas.'
                        );
                    }

                    $storedPaths[] = $path;

                    $hasil[] = BerkasPengajuan::create([
                        'pengajuan_id' => $pengajuan->id,
                        'nama_berkas' => $namaBerkas,
                        'path_berkas' => $path,
                        'tipe_berkas' => $file->getMimeType(),
                        'ukuran_berkas' => $file->getSize(),
                    ]);
                }

                return $hasil;
            });

            return response()->json([
                'success' => true,
                'message' => 'Surat Rekomendasi, Bukti Pembayaran SPP, dan Surat Permohonan Izin Penelitian berhasil diunggah.',
                'data' => $hasil,
            ], 201);

        } catch (Throwable $e) {
            foreach ($storedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan berkas.',
            ], 500);
        }
    }
}
