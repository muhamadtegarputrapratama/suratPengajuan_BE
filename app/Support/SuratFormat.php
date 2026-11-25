<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class SuratFormat
{
    public static function tanggal(?Carbon $tanggal): string
    {
        return $tanggal ? $tanggal->copy()->locale('id')->translatedFormat('j F Y') : '-';
    }

    /** "18 November - 15 Desember 2026" (tahun ditulis di kedua sisi bila berbeda). */
    public static function periode(?Carbon $mulai, ?Carbon $selesai): string
    {
        if (!$mulai || !$selesai) {
            return '-';
        }

        $m = $mulai->copy()->locale('id');
        $s = $selesai->copy()->locale('id');

        if ($m->year === $s->year) {
            return $m->translatedFormat('j F') . ' - ' . $s->translatedFormat('j F Y');
        }

        return $m->translatedFormat('j F Y') . ' - ' . $s->translatedFormat('j F Y');
    }

    public static function karyaTulis(?string $program): string
    {
        return match (strtolower(trim((string) $program))) {
            'magister', 's2' => 'tesis',
            'doktor', 's3' => 'disertasi',
            default => 'skripsi',
        };
    }

    /** Path gambar di folder public/ (mencoba png/jpg/jpeg bila ekstensi beda), atau null. */
    public static function gambarPath(?string $relatif): ?string
    {
        if (!$relatif) {
            return null;
        }

        $path = public_path($relatif);

        if (is_file($path)) {
            return $path;
        }

        $dasar = pathinfo($path, PATHINFO_DIRNAME) . DIRECTORY_SEPARATOR . pathinfo($path, PATHINFO_FILENAME);

        foreach (['png', 'jpg', 'jpeg'] as $ext) {
            if (is_file("{$dasar}.{$ext}")) {
                return "{$dasar}.{$ext}";
            }
        }

        return null;
    }

    /** Gambar sebagai data URI (aman dipakai DomPDF), atau null. */
    public static function gambarDataUri(?string $path): ?string
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public static function logoPath(): ?string
    {
        return self::gambarPath(config('surat.logo'));
    }

    public static function logoDataUri(): ?string
    {
        return self::gambarDataUri(self::logoPath());
    }

    public static function ornamenKiriPath(): ?string
    {
        return self::gambarPath(config('surat.ornamen_kiri'));
    }

    public static function ornamenKiriDataUri(): ?string
    {
        return self::gambarDataUri(self::ornamenKiriPath());
    }
}