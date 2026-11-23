<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departemen extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'prefix_nim',
    ];

    public function pengajuans(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }
}
