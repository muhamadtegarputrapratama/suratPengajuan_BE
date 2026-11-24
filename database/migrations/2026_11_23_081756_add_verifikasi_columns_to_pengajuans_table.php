<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // enum -> string supaya bisa menambah status 'perlu_revisi' (validasi status dilakukan di aplikasi)
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('status', 30)->default('menunggu_verifikasi')->change();
        });

        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('judul_penelitian')->nullable();
            $table->string('lokasi_penelitian')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('dosen_pembimbing')->nullable();
            $table->text('tujuan_penelitian')->nullable();

            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'judul_penelitian', 'lokasi_penelitian', 'tanggal_mulai', 'tanggal_selesai',
                'dosen_pembimbing', 'tujuan_penelitian', 'catatan_verifikasi', 'verified_at',
            ]);
        });
    }
};