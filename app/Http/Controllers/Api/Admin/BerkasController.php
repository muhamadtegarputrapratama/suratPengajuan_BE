<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BerkasPengajuan;
use Illuminate\Support\Facades\Storage;

class BerkasController extends Controller
{
    /** GET /api/admin/berkas/{id}/preview  (foto/PDF ditampilkan inline) */
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

        return $disk->response($berkas->path_berkas, $berkas->nama_berkas, [], 'inline');
    }
}