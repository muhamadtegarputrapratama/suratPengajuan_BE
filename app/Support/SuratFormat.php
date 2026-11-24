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
}