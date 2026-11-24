<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPengajuan extends Model
{
    protected $table = 'riwayat_pengajuan';

    protected $fillable = [
        'pengajuan_id',
        'user_id',
        'aksi',
        'status_dari',
        'status_ke',
        'catatan',
    ];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}