<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengajuan extends Model
{
    protected $fillable = [
        'nim',
        'nama_mahasiswa',
        'departemen_id',
        'status',
        'catatan_penolakan',
    ];

    public function departemen(): BelongsTo
    {
        return $this->belongsTo(Departemen::class);
    }

    public function jenisPengajuans(): BelongsToMany
    {
        return $this->belongsToMany(
            JenisPengajuan::class,
            'pengajuan_jenis_pengajuan'
        )->withTimestamps();
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(BerkasPengajuan::class);
    }
}
