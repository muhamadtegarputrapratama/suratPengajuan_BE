<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\BerkasPengajuanController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\BerkasController as AdminBerkasController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Api\Admin\SuratController as AdminSuratController;
use App\Http\Controllers\Api\Admin\VerifikasiController as AdminVerifikasiController;


Route::get('/departemen', [
    MasterDataController::class,
    'departemen'
]);

Route::get('/jenis-pengajuan', [
    MasterDataController::class,
    'jenisPengajuan'
]);




Route::middleware('throttle:pengajuan-public')->group(function () {


    Route::post('/cari-mahasiswa', [
        MahasiswaController::class,
        'cari'
    ]);


    Route::post('/pengajuan', [
        PengajuanController::class,
        'store'
    ]);


    Route::post('/pengajuan/{id}/berkas', [
        BerkasPengajuanController::class,
        'store'
    ]);


    Route::get('/pengajuan/search', [
        PengajuanController::class,
        'search'
    ]);


    Route::get('/riwayat-pengajuan', [
        PengajuanController::class,
        'riwayat'
    ]);


    Route::get('/pengajuan', [
        PengajuanController::class,
        'index'
    ]);


    Route::get('/pengajuan/{id}', [
        PengajuanController::class,
        'show'
    ])->whereNumber('id');

});


Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'role:admin_kpap'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        Route::get('/statistik', [AdminDashboardController::class, 'statistik']);

        Route::get('/pengajuan', [AdminPengajuanController::class, 'index']);
        Route::get('/pengajuan-export', [AdminPengajuanController::class, 'export']);
        Route::get('/pengajuan/{id}', [AdminPengajuanController::class, 'show']);
        Route::put('/pengajuan/{id}/data', [AdminPengajuanController::class, 'updateData']);
        Route::get('/pengajuan/{id}/riwayat', [AdminPengajuanController::class, 'riwayat']);

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