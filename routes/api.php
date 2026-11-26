<?php

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\BerkasController as AdminBerkasController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Api\Admin\RiwayatController as AdminRiwayatController;
use App\Http\Controllers\Api\Admin\SuratController as AdminSuratController;
use App\Http\Controllers\Api\Admin\VerifikasiController as AdminVerifikasiController;
use App\Http\Controllers\Api\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Satu-satunya route tanpa token: login
|--------------------------------------------------------------------------
*/
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Semua route di bawah ini WAJIB login (header Authorization: Bearer <token>)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

    // Route BE 1 (pengajuan, upload berkas, pencarian, riwayat, data master).
    // Datanya otomatis dibatasi per departemen oleh scope di model Pengajuan.
    require base_path('routes/pengajuan.php');
});

/*
|--------------------------------------------------------------------------
| Admin KPAP
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'can:admin-kpap'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        Route::get('/statistik', [AdminDashboardController::class, 'statistik']);

        Route::get('/pengajuan', [AdminPengajuanController::class, 'index']);
        Route::get('/pengajuan-export', [AdminPengajuanController::class, 'export']);
        Route::get('/pengajuan/{id}', [AdminPengajuanController::class, 'show']);
        Route::put('/pengajuan/{id}/data', [AdminPengajuanController::class, 'updateData']);
        Route::get('/pengajuan/{id}/riwayat', [AdminPengajuanController::class, 'riwayat']);

        Route::get('/riwayat', [AdminRiwayatController::class, 'index']);
        Route::get('/riwayat/{id}', [AdminRiwayatController::class, 'show']);

        Route::get('/berkas/{id}/preview', [AdminBerkasController::class, 'preview']);

        Route::post('/pengajuan/{id}/verifikasi', [AdminVerifikasiController::class, 'verifikasi']);
        Route::post('/pengajuan/{id}/tolak', [AdminVerifikasiController::class, 'tolak']);
        Route::post('/pengajuan/{id}/revisi', [AdminVerifikasiController::class, 'revisi']);

        Route::get('/pengajuan/{id}/surat', [AdminSuratController::class, 'index']);
        Route::post('/pengajuan/{id}/surat/generate', [AdminSuratController::class, 'generate']);
        Route::get('/surat/{id}/preview', [AdminSuratController::class, 'preview']);
        Route::get('/surat/{id}/download/{format?}', [AdminSuratController::class, 'download'])
            ->whereIn('format', ['pdf', 'docx']);
    });
});