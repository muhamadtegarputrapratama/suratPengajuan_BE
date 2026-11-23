<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MahasiswaController extends Controller
{
    public function cari(Request $request)
    {
        $validator = Validator::make($request->all(), [
           'nim' => ['required', 'string', 'regex:/^E[0-9]{10}$/'],
        ], [
            'nim.required' => 'NIM wajib diisi.',
            'nim.regex' => 'NIM harus terdiri dari 11 karakter, diawali huruf E dan diikuti 10 angka.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $prefixNim = substr($request->nim, 0, 4);

        $departemen = Departemen::where('prefix_nim', $prefixNim)
            ->first();

        if (!$departemen) {
            return response()->json([
                'success' => false,
                'message' => 'Departemen tidak ditemukan untuk NIM tersebut.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data mahasiswa berhasil dikenali.',
            'data' => [
                'nim' => $request->nim,
                'departemen' => [
                    'id' => $departemen->id,
                    'kode' => $departemen->kode,
                    'nama' => $departemen->nama,
                ],
            ],
        ]);
    }
}
