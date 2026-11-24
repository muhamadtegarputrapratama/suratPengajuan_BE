<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->string('program', 50)->nullable();      // Sarjana / Magister / Doktor
            $table->text('bentuk_bantuan')->nullable();     // dipakai surat Permohonan Bantuan
        });
    }

    public function down(): void
    {
        Schema::table('pengajuans', function (Blueprint $table) {
            $table->dropColumn(['program', 'bentuk_bantuan']);
        });
    }
};