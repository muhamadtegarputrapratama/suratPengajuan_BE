<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BerkasPengajuan;
use Illuminate\Support\Facades\Storage;

class BerkasController extends Controller
{
    /** GET /api/admin/berkas/{id}/preview  (foto/PDF ditampilkan inline, jenis lain diunduh) */
    public function preview(int $id)
    {
        $berkas = BerkasPengajuan::find($id);
        $disk = Storage::disk(config('surat.berkas_disk') ?: config('filesystems.default'));

        if (!$berkas || !$disk->exists($berkas->path_berkas)) {
            return response()->json([
                'success' => false,
                'message' => 'File berkas tidak ditemukan.',
            ], 404);
        }

        $path = $berkas->path_berkas;
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        // Hanya gambar (selain SVG) dan PDF yang aman ditampilkan inline.
        $aman = ($mime !== 'image/svg+xml' && str_starts_with($mime, 'image/')) || $mime === 'application/pdf';
        $nama = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($berkas->nama_berkas ?: $path));

        return response($disk->get($path), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => ($aman ? 'inline' : 'attachment') . '; filename="' . $nama . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}