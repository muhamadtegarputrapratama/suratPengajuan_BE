<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengajuanController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => ['required', 'string', 'regex:/^E[0-9]{10}$/'],
            'nama_mahasiswa' => ['required', 'string', 'max:255'],
            'jenis_pengajuan' => ['required', 'array', 'min:1'],
            'jenis_pengajuan.*' => [
                'required',
                'integer',
                'distinct',
                'exists:jenis_pengajuan,id'
            ],
        ], [
            'nim.regex' => 'NIM harus terdiri dari 11 karakter, diawali huruf E dan diikuti 10 angka.',
            'jenis_pengajuan.required' => 'Pilih minimal satu jenis pengajuan.',
            'jenis_pengajuan.min' => 'Pilih minimal satu jenis pengajuan.',
            'jenis_pengajuan.*.exists' => 'Jenis pengajuan tidak ditemukan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $prefixNim = substr($request->nim, 0, 4);

        $departemen = Departemen::where(
            'prefix_nim',
            $prefixNim
        )->first();

        if (!$departemen) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak ditemukan.',
            ], 404);
        }

        $jenisAktif = JenisPengajuan::whereIn(
            'id',
            $request->jenis_pengajuan
        )->where('is_active', true)->count();

        if ($jenisAktif !== count($request->jenis_pengajuan)) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat jenis pengajuan yang tidak aktif atau tidak valid.',
            ], 422);
        }

        $pengajuan = DB::transaction(function () use ($request, $departemen) {
            $pengajuan = Pengajuan::create([
                'nim' => $request->nim,
                'nama_mahasiswa' => $request->nama_mahasiswa,
                'departemen_id' => $departemen->id,
                'status' => 'menunggu_verifikasi',
            ]);

            $pengajuan->jenisPengajuans()->attach(
                $request->jenis_pengajuan
            );

            return $pengajuan;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dibuat.',
            'data' => $pengajuan->load([
                'departemen',
                'jenisPengajuans',
            ]),
        ], 201);
    }


    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => ['nullable', 'string', 'regex:/^E[0-9]{10}$/'],
            'status' => [
                'nullable',
                'in:menunggu_verifikasi,terverifikasi,ditolak,surat_dibuat'
            ],
            'departemen_id' => [
                'nullable',
                'integer',
                'exists:departemens,id'
            ],
            'jenis_pengajuan_id' => [
                'nullable',
                'integer',
                'exists:jenis_pengajuan,id'
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter filter tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Pengajuan::with([
            'departemen',
            'jenisPengajuans',
            'berkas',
        ]);

        if ($request->filled('nim')) {
            $query->where('nim', $request->nim);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('departemen_id')) {
            $query->where(
                'departemen_id',
                $request->departemen_id
            );
        }

        if ($request->filled('jenis_pengajuan_id')) {
            $query->whereHas('jenisPengajuans', function ($q) use ($request) {
                $q->where(
                    'jenis_pengajuan.id',
                    $request->jenis_pengajuan_id
                );
            });
        }

        $pengajuans = $query->latest()->paginate(
            $request->input('per_page', 10)
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan berhasil diambil.',
            'data' => $pengajuans,
        ]);
    }


    public function show($id)
    {
        $pengajuan = Pengajuan::with([
            'departemen',
            'jenisPengajuans',
            'berkas',
        ])->find($id);

        if (!$pengajuan) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pengajuan berhasil diambil.',
            'data' => $pengajuan,
        ]);
    }


    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter pencarian tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $keyword = trim($request->q);

        $pengajuans = Pengajuan::with([
            'departemen',
            'jenisPengajuans',
            'berkas',
        ])
        ->where(function ($query) use ($keyword) {
            $query->where('nim', 'like', '%' . $keyword . '%')
                ->orWhere(
                    'nama_mahasiswa',
                    'like',
                    '%' . $keyword . '%'
                );
        })
        ->latest()
        ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Hasil pencarian pengajuan berhasil diambil.',
            'data' => $pengajuans,
        ]);
    }


    public function riwayat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => ['required', 'string', 'regex:/^E[0-9]{10}$/'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ], [
            'nim.required' => 'NIM wajib diisi untuk melihat riwayat.',
            'nim.regex' => 'Format NIM tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter riwayat tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $riwayat = Pengajuan::with([
            'departemen',
            'jenisPengajuans',
            'berkas',
        ])
        ->where('nim', $request->nim)
        ->latest()
        ->paginate($request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Riwayat pengajuan berhasil diambil.',
            'data' => $riwayat,
        ]);
    }
}
