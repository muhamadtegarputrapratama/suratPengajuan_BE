<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('jenis_pengajuan_id')->constrained('jenis_pengajuan')->restrictOnDelete();
            $table->foreignId('pengajuan_instansi_id')->nullable()
                ->constrained('pengajuan_instansis')->nullOnDelete();
            $table->string('nomor_surat')->unique();
            $table->string('path_pdf');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};