<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BerkasPengajuan;
use Illuminate\Support\Facades\Storage;

class BerkasController extends Controller
{
    public function preview(int $id)
{
    $berkas = BerkasPengajuan::find($id);

    if (!$berkas) {
        return response()->json([
            'success' => false,
            'message' => 'Data berkas tidak ditemukan.',
        ], 404);
    }

    $diskName = config('surat.berkas_disk')
        ?: config('filesystems.default');

    $disk = Storage::disk($diskName);

    if (!$disk->exists($berkas->path_berkas)) {
        return response()->json([
            'success' => false,
            'message' => 'File berkas tidak ditemukan.',
        ], 404);
    }

    $mimeType = $disk->mimeType($berkas->path_berkas);

    return response()->file(
        $disk->path($berkas->path_berkas),
        [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' .
                basename($berkas->path_berkas) . '"',
        ]
    );
}
}
