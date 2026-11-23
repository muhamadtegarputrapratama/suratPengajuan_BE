<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasPengajuan extends Model
{
    protected $table = 'berkas_pengajuan';

    protected $fillable = [
        'pengajuan_id',
        'nama_berkas',
        'path_berkas',
        'tipe_berkas',
        'ukuran_berkas',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }
}
