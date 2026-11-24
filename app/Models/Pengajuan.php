<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengajuan extends Model
{
    public const STATUS_MENUNGGU = 'menunggu_verifikasi';
    public const STATUS_PERLU_REVISI = 'perlu_revisi';
    public const STATUS_TERVERIFIKASI = 'terverifikasi';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_SURAT_DIBUAT = 'surat_dibuat';

    public const STATUSES = [
        self::STATUS_MENUNGGU,
        self::STATUS_PERLU_REVISI,
        self::STATUS_TERVERIFIKASI,
        self::STATUS_DITOLAK,
        self::STATUS_SURAT_DIBUAT,
    ];

    protected $fillable = [
        'nim',
        'nama_mahasiswa',
        'departemen_id',
        'status',
        'catatan_penolakan',
        // tambahan BE 2
        'judul_penelitian',
        'lokasi_penelitian',
        'tanggal_mulai',
        'tanggal_selesai',
        'dosen_pembimbing',
        'tujuan_penelitian',
        'catatan_verifikasi',
        'verified_by',
        'verified_at',
        'program',
        'bentuk_bantuan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'verified_at' => 'datetime',
        ];
    }

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

    // ---- tambahan BE 2 ----
    public function instansis(): HasMany
    {
        return $this->hasMany(PengajuanInstansi::class);
    }

    public function surats(): HasMany
    {
        return $this->hasMany(Surat::class);
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatPengajuan::class)->latest();
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}