<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    protected $table = 'surats';

    protected $fillable = [
        'pengajuan_id',
        'jenis_pengajuan_id',
        'pengajuan_instansi_id',
        'nomor_surat',
        'path_pdf',
        'path_docx',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return ['generated_at' => 'datetime'];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function jenisPengajuan(): BelongsTo
    {
        return $this->belongsTo(JenisPengajuan::class);
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(PengajuanInstansi::class, 'pengajuan_instansi_id');
    }
}