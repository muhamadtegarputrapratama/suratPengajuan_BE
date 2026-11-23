<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JenisPengajuan extends Model
{
    protected $table = 'jenis_pengajuan';

    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function pengajuans(): BelongsToMany
    {
        return $this->belongsToMany(
            Pengajuan::class,
            'pengajuan_jenis_pengajuan'
        )->withTimestamps();
    }
}
