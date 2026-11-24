<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\Pengajuan;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statistik()
    {
        $counts = Pengajuan::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $perStatus = [];
        foreach (Pengajuan::STATUSES as $status) {
            $perStatus[$status] = (int) ($counts[$status] ?? 0);
        }

        $perDepartemen = Departemen::withCount('pengajuans')
            ->get(['id', 'kode', 'nama'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'kode' => $d->kode,
                'nama' => $d->nama,
                'total_pengajuan' => $d->pengajuans_count,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Statistik pengajuan berhasil diambil.',
            'data' => [
                'total' => array_sum($perStatus),
                'per_status' => $perStatus,
                'per_departemen' => $perDepartemen,
            ],
        ]);
    }
}