<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 50);
            $table->string('status_dari', 30)->nullable();
            $table->string('status_ke', 30)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['pengajuan_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengajuan');
    }
};