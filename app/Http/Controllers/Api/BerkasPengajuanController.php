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
            'berkas' => ['required', 'array', 'min:1', 'max:10'],
            'berkas.*' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:2048',
            ],
            'nama_berkas' => ['required', 'array', 'min:1', 'max:10'],
            'nama_berkas.*' => ['required', 'string', 'max:255'],
        ], [
            'berkas.required' => 'Berkas wajib diunggah.',
            'berkas.*.mimes' => 'Format berkas harus PDF, JPG, JPEG, atau PNG.',
            'berkas.*.max' => 'Ukuran setiap berkas maksimal 2 MB.',
            'nama_berkas.required' => 'Nama berkas wajib diisi.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi berkas gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $files = $request->file('berkas');
        $names = $request->input('nama_berkas');

        if (count($files) !== count($names)) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah nama berkas harus sama dengan jumlah file.',
            ], 422);
        }

        $storedPaths = [];

        try {
            $hasil = DB::transaction(function () use (
                $files,
                $names,
                $pengajuan,
                &$storedPaths
            ) {
                $hasil = [];

                foreach ($files as $index => $file) {
                    $path = $file->store(
                        "pengajuan/{$pengajuan->id}",
                        'public'
                    );

                    if (!$path) {
                        throw new \RuntimeException('Gagal menyimpan berkas.');
                    }

                    $storedPaths[] = $path;

                    $hasil[] = BerkasPengajuan::create([
                        'pengajuan_id' => $pengajuan->id,
                        'nama_berkas' => $names[$index],
                        'path_berkas' => $path,
                        'tipe_berkas' => $file->getMimeType(),
                        'ukuran_berkas' => $file->getSize(),
                    ]);
                }

                return $hasil;
            });

            return response()->json([
                'success' => true,
                'message' => 'Berkas berhasil diunggah.',
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
