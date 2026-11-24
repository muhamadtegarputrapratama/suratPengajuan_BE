<?php

namespace App\Services;

use App\Models\JenisPengajuan;
use App\Models\Pengajuan;
use App\Models\PengajuanInstansi;
use App\Support\SuratFormat;
use Illuminate\Support\Carbon;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

class SuratDocxGenerator
{
    /** Mengembalikan isi file .docx (binary string). Isi mengikuti template PDF yang sama. */
    public function render(
        Pengajuan $pengajuan,
        JenisPengajuan $jenis,
        ?PengajuanInstansi $instansi,
        string $nomor,
        Carbon $tanggal
    ): string {
        Settings::setOutputEscapingEnabled(true);

        $view = config("surat.template.{$jenis->kode}", config('surat.default_template'));
        $bantuan = $view === 'surat.permohonan-bantuan';
        $hal = $bantuan ? 'Permohonan Bantuan' : 'Izin Praktik Khusus/Penelitian';
        $rapat = ['spaceAfter' => 0];

        $word = new PhpWord();
        $word->setDefaultFontName('Arial');
        $word->setDefaultFontSize(11);

        $section = $word->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1300,
            'marginLeft' => 1250,
            'marginRight' => 1250,
        ]);

        $section->addFooter()->addText(
            config('surat.tagline'),
            ['italic' => true, 'size' => 9],
            ['alignment' => Jc::CENTER]
        );

        $this->kop($section);
        $section->addTextBreak(1);

        // Nomor, Hal, tanggal
        $section->addText('Nomor : ' . $nomor, [], $rapat);
        $run = $section->addTextRun($rapat);
        $run->addText('Hal : ');
        $run->addText($hal, ['bold' => true]);
        $section->addText(
            config('surat.kota') . ', ' . SuratFormat::tanggal($tanggal),
            [],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 200]
        );

        // Yth.
        $run = $section->addTextRun($rapat);
        $run->addText('Yth. ');
        $run->addText($instansi?->nama_instansi ?? '-', ['bold' => true]);
        foreach (preg_split('/\R/', (string) $instansi?->alamat_instansi) as $baris) {
            if (trim($baris) !== '') {
                $section->addText(trim($baris), ['bold' => true], ['spaceAfter' => 0, 'indentation' => ['left' => 400]]);
            }
        }
        $section->addTextBreak(1);

        $section->addText('Disampaikan dengan hormat bahwa mahasiswa Fakultas Kehutanan dan Lingkungan Institut Pertanian Bogor :');

        $this->tabel($section, [
            ['Nama', mb_strtoupper($pengajuan->nama_mahasiswa), true],
            ['NIM', $pengajuan->nim, false],
            ['Program', $pengajuan->program ?? '-', false],
            ['Departemen', $pengajuan->departemen->nama ?? '-', false],
            ['Komisi Pembimbing', $pengajuan->dosen_pembimbing ?? '-', false],
        ], 400);
        $section->addTextBreak(1);

        $run = $section->addTextRun();
        $run->addText('bermaksud untuk melaksanakan praktik khusus dalam rangka penyusunan '
            . SuratFormat::karyaTulis($pengajuan->program) . ' yang berjudul :');
        $run->addTextBreak();
        $run->addText('“' . $pengajuan->judul_penelitian . '”.', ['bold' => true, 'italic' => true]);

        $periode = SuratFormat::periode($pengajuan->tanggal_mulai, $pengajuan->tanggal_selesai);

        if ($bantuan) {
            $this->tabel($section, [['Waktu Penelitian', $periode, false]], 0, true);
            $section->addTextBreak(1);
            $section->addText(
                'Sehubungan dengan hal tersebut di atas, kami mohon agar mahasiswa yang bersangkutan dapat diberikan bantuan berupa '
                . rtrim((string) $pengajuan->bentuk_bantuan, ". \t\n\r") . '.'
            );
        } else {
            $this->tabel($section, [
                ['Lokasi Penelitian', $pengajuan->lokasi_penelitian ?? '-', false],
                ['Waktu Penelitian', $periode, false],
            ], 0, true);
            $section->addTextBreak(1);
            $section->addText('Sehubungan dengan hal tersebut di atas, kami mohon agar mahasiswa yang bersangkutan dapat diberikan kemudahan izin penelitian pada lokasi tersebut.');
        }

        $section->addText('Atas bantuan dan kerjasama yang baik diucapkan terima kasih.');
        $section->addTextBreak(1);

        // Tanda tangan
        $kanan = ['spaceAfter' => 0, 'indentation' => ['left' => 5000]];
        foreach (config('surat.penandatangan.jabatan') as $baris) {
            $section->addText($baris, [], $kanan);
        }
        $section->addTextBreak(3);
        $section->addText(config('surat.penandatangan.nama'), [], $kanan);
        $section->addText('NIP. ' . config('surat.penandatangan.nip'), [], $kanan);

        if (!$bantuan) {
            $section->addTextBreak(1);
            $section->addText('Tembusan:', [], $rapat);
            $section->addText('Ketua Departemen ' . ($pengajuan->departemen->nama ?? '') . ' Fakultas Kehutanan dan Lingkungan IPB');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'surat_');
        IOFactory::createWriter($word, 'Word2007')->save($tmp);
        $isi = file_get_contents($tmp);
        @unlink($tmp);

        return $isi;
    }

    private function kop(Section $section): void
    {
        $kop = config('surat.kop');
        $rapat = ['spaceAfter' => 0];

        $table = $section->addTable();
        $row = $table->addRow();

        $kiri = $row->addCell(6000, ['borderRightSize' => 6, 'borderRightColor' => '999999']);
        $kiri->addText($kop['kementerian'], ['bold' => true, 'size' => 9], $rapat);
        $kiri->addText($kop['universitas'], ['bold' => true, 'size' => 18], $rapat);
        $kiri->addText($kop['fakultas'], ['size' => 11, 'color' => '444444'], $rapat);

        $kanan = $row->addCell(3500);
        foreach ($kop['kontak'] as $baris) {
            $kanan->addText($baris, ['size' => 9], ['spaceAfter' => 0, 'indentation' => ['left' => 200]]);
        }
    }

    /** @param array<int, array{0:string,1:string,2:bool}> $rows */
    private function tabel(Section $section, array $rows, int $indent = 0, bool $labelTebal = false): void
    {
        $table = $section->addTable(['indent' => new \PhpOffice\PhpWord\ComplexType\TblWidth($indent)]);

        foreach ($rows as [$label, $nilai, $tebal]) {
            $row = $table->addRow();
            $row->addCell(2500)->addText($label, ['bold' => $labelTebal], ['spaceAfter' => 0]);
            $row->addCell(300)->addText(':', [], ['spaceAfter' => 0]);
            $row->addCell(6200)->addText((string) $nilai, ['bold' => $tebal], ['spaceAfter' => 0]);
        }
    }
}