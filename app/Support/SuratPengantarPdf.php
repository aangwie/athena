<?php

namespace App\Support;

use App\Models\Instansi;
use App\Models\SuratPengantar;
use Fpdf\Fpdf;
use Illuminate\Support\Str;

/**
 * Surat Pengantar usulan jabatan akademik dosen dalam format PDF (FPDF).
 *
 * Dua bentuk isi mengikuti template instansi:
 * - pribadi  : satu dosen dengan blok identitas (Nama, NIDN/NUPTK, Jabatan Akademik, Diusulkan)
 * - kolektif : banyak dosen dalam tabel (No, NUPTK, NIDN, Nama, Jabatan Fungsional)
 *
 * Kertas A4 portrait, margin atas 1.5 cm, kiri/kanan/bawah 2 cm.
 */
class SuratPengantarPdf extends Fpdf
{
    private const ORIENTASI = 'P';

    /** Satuan FPDF: 'mm', 'cm', 'pt' atau 'in'. */
    private const SATUAN = 'cm';

    private const UKURAN = 'A4';

    private const MARGIN_KIRI = 2.0;

    private const MARGIN_ATAS = 2.0;

    private const MARGIN_KANAN = 2.0;

    private const MARGIN_BAWAH = 2.0;

    private const UKURAN_LOGO = 2.4;

    private const FONT = 'Times';

    private const UKURAN_FONT = 11.0;

    private const TINGGI_BARIS = 0.55;

    /** Lebar kolom tanda tangan Ketua. */
    private const LEBAR_KOLOM_TTD = 8.0;

    private bool $adaLogo = false;

    /**
     * @param  array<int, array<string, string>>  $dosen
     * @param  array<string, string>  $pimpinan
     */
    public function __construct(
        private readonly ?Instansi $instansi,
        private readonly SuratPengantar $pengantar,
        private readonly array $dosen,
        private readonly array $pimpinan,
    ) {
        parent::__construct(self::ORIENTASI, self::SATUAN, self::UKURAN);

        $this->SetMargins(self::MARGIN_KIRI, self::MARGIN_ATAS, self::MARGIN_KANAN);
        $this->SetAutoPageBreak(true, self::MARGIN_BAWAH);
        $this->SetTitle($this->teks('Surat Pengantar '.$this->pengantar->nomor_surat));
    }

    public function buat(): static
    {
        $this->AddPage();
        $this->kop();
        $this->kepalaSurat();
        $this->tujuan();
        $this->badan();
        $this->tandaTangan();

        return $this;
    }

    /**
     * Isi PDF sebagai string — dipakai controller untuk response inline.
     */
    public function isi(): string
    {
        return $this->Output('S');
    }

    public function namaBerkas(): string
    {
        $nama = $this->pengantar->kolektif
            ? 'kolektif-'.count($this->dosen).'-dosen'
            : ($this->dosen[0]['nama'] ?? 'dosen');

        return 'surat-pengantar-'.Str::slug($nama).'.pdf';
    }

    private function kop(): void
    {
        $this->logo();

        $this->SetY(self::MARGIN_ATAS);
        $this->SetLeftMargin(self::MARGIN_KIRI + ($this->adaLogo ? self::UKURAN_LOGO + 0.4 : 0));
        $this->SetFont(self::FONT, 'B', 11);
        $this->MultiCell(0, 0.55, $this->teks('PERKUMPULAN PENYELENGGARA LEMBAGA PENDIDIKAN'), 0, 'C');
        $this->MultiCell(0, 0.55, $this->teks('PERGURUAN TINGGI PGRI PACITAN'), 0, 'C');

        $this->SetFont(self::FONT, 'B', 14);
        $this->MultiCell(0, 0.7, $this->teks(mb_strtoupper($this->instansi?->nama_instansi ?: 'Nama Instansi Belum Diisi')), 0, 'C');

        $this->SetFont(self::FONT, '', 9);
        $this->MultiCell(0, 0.45, $this->teks($this->kontak()), 0, 'C');

        $this->SetLeftMargin(self::MARGIN_KIRI);

        $y = ($this->adaLogo ? max($this->GetY(), self::MARGIN_ATAS + self::UKURAN_LOGO) : $this->GetY()) + 0.15;
        $kanan = $this->GetPageWidth() - self::MARGIN_KANAN;

        $this->SetLineWidth(0.07);
        $this->Line(self::MARGIN_KIRI, $y, $kanan, $y);
        $this->SetLineWidth(0.02);
        $this->Line(self::MARGIN_KIRI, $y + 0.12, $kanan, $y + 0.12);

        $this->SetY($y + 0.6);
    }

    /**
     * Baris "No / Lampiran / Hal" seperti pada template surat pengantar.
     */
    private function kepalaSurat(): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        foreach ([
            'No' => $this->pengantar->nomor_surat,
            'Lampiran' => (string) $this->pengantar->lampiran,
            'Hal' => $this->pengantar->perihal,
        ] as $label => $nilai) {
            $this->Cell(2.6, self::TINGGI_BARIS, $this->teks($label), 0, 0);
            $this->Cell(0.4, self::TINGGI_BARIS, ':', 0, 0);
            $this->MultiCell(0, self::TINGGI_BARIS, $this->teks($nilai !== '' ? $nilai : '—'), 0, 'L');
        }

        $this->Ln(0.5);
    }

    /**
     * Blok tujuan: "Kepada. Yth. <nama>" / "di" / "<kota tujuan>".
     */
    private function tujuan(): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        // Tujuan & kota opsional; dibiarkan bergaris titik untuk diisi tangan bila kosong.
        $kepada = trim((string) $this->pengantar->tujuan);
        $kota = trim((string) $this->pengantar->kota_tujuan);
        $this->SetFont(self::FONT, 'B', 11);
        $this->MultiCell(0, self::TINGGI_BARIS, $this->teks('Yth. '.($kepada !== '' ? $kepada : str_repeat('.', 50))), 0, 'L');
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->Cell(0, self::TINGGI_BARIS, $this->teks('di'), 0, 1);
        $this->Cell(0, self::TINGGI_BARIS, $this->teks($kota !== '' ? $kota : str_repeat('.', 30)), 0, 1);

        $this->Ln(0.45);
        $this->Cell(0, self::TINGGI_BARIS, $this->teks('Dengan hormat,'), 0, 1);
        $this->Ln(0.15);
    }

    private function badan(): void
    {
        if ($this->pengantar->kolektif) {
            $this->badanKolektif();

            return;
        }

        $this->badanPribadi();
    }

    private function badanPribadi(): void
    {
        $dosen = $this->dosen[0] ?? ['nama' => '—', 'nidn' => '—', 'nuptk' => '', 'jabatan' => '—'];

        $this->paragraf('Bersama ini kami kirimkan berkas usulan Jabatan Akademik Dosen '.$this->namaInstansi().', sebagai berikut:');

        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        $baris = [
            'Nama' => $dosen['nama'],
            'NIDN / NUPTK' => $this->nidnNuptk($dosen),
            'Jabatan Akademik' => $dosen['jabatan'],
        ];

        // Baris usulan hanya muncul bila jabatan yang diusulkan diisi.
        $diusulkan = trim((string) $this->pengantar->jabatan_diusulkan);

        if ($diusulkan !== '') {
            $baris['Diusulkan'] = $diusulkan;
        }

        foreach ($baris as $label => $nilai) {
            $this->Cell(4.6, self::TINGGI_BARIS, $this->teks($label), 0, 0);
            $this->Cell(0.4, self::TINGGI_BARIS, ':', 0, 0);
            $this->MultiCell(0, self::TINGGI_BARIS, $this->teks($nilai !== '' ? $nilai : '—'), 0, 'L');
        }

        $this->Ln(0.3);
        $this->paragraf('Demikian surat pengantar ini dibuat, atas kerjasamanya kami ucapkan terima kasih.');
    }

    private function badanKolektif(): void
    {
        $this->paragraf('Bersama ini kami kirimkan data dosen '.$this->namaInstansi().'yang belum masuk dalam aplikasi SIAP Jafa, sebagai berikut:');

        $this->tabelDosen();

        $this->paragraf('Adapun kelengkapan data dosen di atas, sebagaimana terlampir.');
        $this->paragraf('Demikian surat pengantar ini dibuat, atas perhatiannya diucapkan terima kasih.');
    }

    private function tabelDosen(): void
    {
        $kolom = [
            ['label' => 'No', 'lebar' => 1.2, 'align' => 'C'],
            ['label' => 'NUPTK', 'lebar' => 3.2, 'align' => 'C'],
            ['label' => 'NIDN', 'lebar' => 3.0, 'align' => 'C'],
            ['label' => 'Nama', 'lebar' => 5.0, 'align' => 'C'],
            ['label' => 'Jabatan Fungsional', 'lebar' => 3.6, 'align' => 'C'],
        ];

        $this->SetFont(self::FONT, 'B', 9.5);
        $this->SetX(self::MARGIN_KIRI);

        foreach ($kolom as $item) {
            $this->Cell($item['lebar'], 0.9, $this->teks($item['label']), 1, 0, $item['align']);
        }

        $this->Ln();
        $this->SetFont(self::FONT, '', 9.5);

        foreach ($this->dosen as $index => $dosen) {
            $this->barisTabel($kolom, [
                (string) ($index + 1),
                $dosen['nuptk'] !== '' ? $dosen['nuptk'] : '—',
                $dosen['nidn'],
                $dosen['nama'],
                $dosen['jabatan'],
            ]);
        }

        $this->Ln(0.35);
    }

    /**
     * Satu baris tabel; nama/jabatan panjang dibungkus ke baris berikutnya.
     *
     * @param  array<int, array<string, mixed>>  $kolom
     * @param  array<int, string>  $isi
     */
    private function barisTabel(array $kolom, array $isi): void
    {
        $terbungkus = [];
        $tertinggi = 1;

        foreach ($kolom as $index => $item) {
            $terbungkus[$index] = $this->bungkus($isi[$index], $item['lebar'] - 0.2);
            $tertinggi = max($tertinggi, count($terbungkus[$index]));
        }

        $tinggi = $tertinggi * 0.5;
        $x = self::MARGIN_KIRI;
        $y = $this->GetY();

        foreach ($kolom as $index => $item) {
            $this->Rect($x, $y, $item['lebar'], $tinggi);

            foreach ($terbungkus[$index] as $baris => $teks) {
                $this->SetXY($x, $y + ($baris * 0.5));
                // Teks sudah dikonversi ke Windows-1252 oleh bungkus(); jangan dikonversi dua kali.
                $this->Cell($item['lebar'], 0.5, $teks, 0, 0, $item['align']);
            }

            $x += $item['lebar'];
        }

        $this->SetXY(self::MARGIN_KIRI, $y + $tinggi);
    }

    /**
     * Pecah teks menjadi baris yang muat pada lebar kolom.
     *
     * @return list<string>
     */
    private function bungkus(string $teks, float $lebar): array
    {
        $teks = trim($this->teks($teks));

        if ($teks === '') {
            return [''];
        }

        $baris = [];
        $sekarang = '';

        foreach (explode(' ', $teks) as $kata) {
            $coba = $sekarang === '' ? $kata : $sekarang.' '.$kata;

            if ($sekarang === '' || $this->GetStringWidth($coba) <= $lebar) {
                $sekarang = $coba;

                continue;
            }

            $baris[] = $sekarang;
            $sekarang = $kata;
        }

        $baris[] = $sekarang;

        return $baris;
    }

    private function tandaTangan(): void
    {
        $baris = [
            [$this->kotaTanggal(), self::TINGGI_BARIS, ''],
            ['Ketua,', self::TINGGI_BARIS, ''],
            ['', 1.7, ''],
            // Nama ketua dicetak tebal.
            [$this->pimpinan['nama'], self::TINGGI_BARIS, 'B'],
            ['NIDN. '.$this->pimpinan['nidn'], self::TINGGI_BARIS, ''],
        ];

        $x = $this->GetPageWidth() - self::MARGIN_KANAN - self::LEBAR_KOLOM_TTD;
        $y = $this->GetY() + 0.4;

        foreach ($baris as [$teks, $tinggi, $gaya]) {
            $this->SetFont(self::FONT, $gaya, self::UKURAN_FONT);
            $this->SetXY($x, $y);
            $this->Cell(self::LEBAR_KOLOM_TTD, $tinggi, $this->teks($teks), 0, 0, 'L');

            $y += $tinggi;
        }

        $this->SetY($y);
    }

    private function paragraf(string $teks): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->MultiCell(0, self::TINGGI_BARIS + 0.05, $this->teks($teks), 0, 'J');
        $this->Ln(0.15);
    }

    /**
     * @param  array<string, string>  $dosen
     */
    private function nidnNuptk(array $dosen): string
    {
        return $dosen['nuptk'] !== '' ? $dosen['nidn'].' / '.$dosen['nuptk'] : $dosen['nidn'];
    }

    private function namaInstansi(): string
    {
        return $this->instansi?->nama_instansi ?: 'Nama Instansi Belum Diisi';
    }

    private function kotaTanggal(): string
    {
        // "Pacitan, 10 Maret 2025"
        return ($this->instansi?->kota ?: str_repeat('.', 15)).', '.($this->pengantar->tanggal_surat?->locale('id')->translatedFormat('j F Y') ?? str_repeat('.', 10));
    }

    private function kontak(): string
    {
        return collect([$this->instansi?->alamat, $this->instansi?->website, $this->instansi?->no_telp])
            ->filter()
            ->implode(' · ');
    }

    private function logo(): void
    {
        // ponytail: FPDF hanya mengenal JPG/PNG; logo WebP diabaikan. Tambah konversi bila perlu.
        if (! preg_match('#^data:image/(png|jpe?g);base64,(.+)$#s', (string) $this->instansi?->logo_base64, $cocok)) {
            return;
        }

        $berkas = tempnam(sys_get_temp_dir(), 'athena-logo');
        file_put_contents($berkas, base64_decode($cocok[2]));

        try {
            $this->Image($berkas, self::MARGIN_KIRI, self::MARGIN_ATAS, self::UKURAN_LOGO, self::UKURAN_LOGO, $cocok[1]);
            $this->adaLogo = true;
        } catch (\Throwable) {
            $this->adaLogo = false;
        } finally {
            @unlink($berkas);
        }
    }

    /**
     * FPDF memakai font core ber-encoding Windows-1252, bukan UTF-8.
     */
    private function teks(?string $teks): string
    {
        $teks = (string) $teks;
        $hasil = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $teks);

        return $hasil === false ? mb_convert_encoding($teks, 'Windows-1252', 'UTF-8') : $hasil;
    }
}

