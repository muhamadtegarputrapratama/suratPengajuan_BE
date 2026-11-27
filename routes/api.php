<?php

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
| Login (satu pintu untuk Admin Departemen dan Admin KPAP)
|--------------------------------------------------------------------------
*/
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

/*
|--------------------------------------------------------------------------
| Wajib login (Authorization: Bearer <token>)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/me', [LoginController::class, 'me']);

    // Khusus Admin Departemen: pengajuan, upload berkas, riwayat, data master.
    // Data otomatis dibatasi per departemen oleh scope di model Pengajuan.
    Route::middleware('can:admin-departemen')->group(function () {
        require base_path('routes/pengajuan.php');
    });
});

/*
|--------------------------------------------------------------------------
| Khusus Admin KPAP
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'can:admin-kpap'])->group(function () {
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