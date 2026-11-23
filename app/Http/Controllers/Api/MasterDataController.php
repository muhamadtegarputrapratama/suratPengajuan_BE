<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\JenisPengajuan;

class MasterDataController extends Controller
{
    public function departemen()
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar departemen berhasil diambil.',
            'data' => Departemen::select(
                'id',
                'kode',
                'nama',
                'prefix_nim'
            )->orderBy('kode')->get(),
        ]);
    }

    public function jenisPengajuan()
    {
        return response()->json([
            'success' => true,
            'message' => 'Daftar jenis pengajuan berhasil diambil.',
            'data' => JenisPengajuan::where('is_active', true)
                ->select('id', 'kode', 'nama', 'deskripsi')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
