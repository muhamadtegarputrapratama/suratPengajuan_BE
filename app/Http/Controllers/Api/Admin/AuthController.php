<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

       
        if (
            !$user->isAdminKpap() &&
            $user->role !== User::ROLE_ADMIN_DEPARTEMEN
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Akun tidak memiliki akses admin.',
            ], 403);
        }

        $token = $user->createToken('admin-' . $user->role)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'role',
                    'departemen_id',
                ]),
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->only([
                'id',
                'name',
                'email',
                'role',
                'departemen_id',
            ]),
        ]);
    }
}
