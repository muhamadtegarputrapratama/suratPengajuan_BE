<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /** POST /api/login  -> untuk Admin KPAP dan Admin Departemen */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('departemen:id,kode,nama,prefix_nim')->where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if (!in_array($user->role, [User::ROLE_ADMIN_KPAP, User::ROLE_ADMIN_DEPARTEMEN], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak memiliki akses.',
            ], 403);
        }

        if ($user->role === User::ROLE_ADMIN_DEPARTEMEN && !$user->departemen_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini belum terhubung ke departemen. Hubungi admin.',
            ], 403);
        }

        $token = $user->createToken($user->role)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->format($user),
            ],
        ]);
    }

    /** POST /api/logout */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    /** GET /api/me */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->format($request->user()->loadMissing('departemen:id,kode,nama,prefix_nim')),
        ]);
    }

    private function format(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'departemen' => $user->departemen,
        ];
    }
}