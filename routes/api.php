<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\MahasiswaController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\BerkasPengajuanController;
use App\Http\Controllers\Api\MasterDataController;


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

});


Route::get('/departemen', [
    MasterDataController::class,
    'departemen'
]);

Route::get('/jenis-pengajuan', [
    MasterDataController::class,
    'jenisPengajuan'
]);


