<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminKpap
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->role !== 'admin_kpap') {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses.',
            ], 403);
        }

        return $next($request);
    }
}