<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuans', function (Blueprint $table) {
            $table->id();

            $table->string('nim', 20);
            $table->string('nama_mahasiswa');

            $table->foreignId('departemen_id')
                ->constrained('departemens')
                ->restrictOnDelete();

            $table->enum('status', [
                'menunggu_verifikasi',
                'terverifikasi',
                'ditolak',
                'surat_dibuat',
            ])->default('menunggu_verifikasi');

            $table->text('catatan_penolakan')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuans');
    }
};