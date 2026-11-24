<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanInstansi extends Model
{
    protected $table = 'pengajuan_instansis';

    protected $fillable = ['pengajuan_id', 'nama_instansi', 'alamat_instansi'];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }
}