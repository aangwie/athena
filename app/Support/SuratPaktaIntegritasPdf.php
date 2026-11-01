<?php

namespace App\Support;

use App\Models\Instansi;
use App\Models\KaryaTulis;
use App\Models\SuratPaktaIntegritas;
use Fpdf\Fpdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Surat Pernyataan Pakta Integritas Keabsahan Karya Ilmiah dalam format PDF (FPDF).
 *
 * ponytail: status dosen tetap belum punya kolom di database, tampil "—" sampai
 * field kepegawaian ditambahkan.
 */
class SuratPaktaIntegritasPdf extends Fpdf
{
    private const ORIENTASI = 'P';

    /** Satuan FPDF: 'mm', 'cm', 'pt' atau 'in'. */
    private const SATUAN = 'cm';

    private const UKURAN = 'A4';

    private const MARGIN_KIRI = 2.0;

    private const MARGIN_ATAS = 2.0;

    private const MARGIN_KANAN = 2.0;

    private const MARGIN_BAWAH = 1.0;

    private const FONT = 'Times';

    private const UKURAN_FONT = 10.5;

    private const TINGGI_BARIS = 0.5;

    /**
     * @param  array<string, string>  $dosen
     * @param  Collection<int, KaryaTulis>  $karya
     */
    public function __construct(
        private readonly ?Instansi $instansi,
        private readonly SuratPaktaIntegritas $pakta,
        private readonly array $dosen,
        private readonly Collection $karya,
    ) {
        parent::__construct(self::ORIENTASI, self::SATUAN, self::UKURAN);

        $this->SetMargins(self::MARGIN_KIRI, self::MARGIN_ATAS, self::MARGIN_KANAN);
        $this->SetAutoPageBreak(true, self::MARGIN_BAWAH);
        $this->SetTitle($this->teks('Surat Pernyataan Pakta Integritas — '.$this->dosen['nama']));
    }

    public function buat(): static
    {
        $this->AddPage();
        $this->judul();
        $this->badan();

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
        return 'surat-pakta-integritas-'.Str::slug($this->dosen['nama']).'.pdf';
    }

    private function judul(): void
    {
        $this->SetFont(self::FONT, 'BU', 12);
        $this->Cell(0, 0.7, $this->teks('SURAT PERNYATAAN PAKTA INTEGRITAS'), 0, 1, 'C');
        $this->Cell(0, 0.7, $this->teks('KEABSAHAN KARYA ILMIAH'), 0, 1, 'C');
        $this->Ln(0.5);
    }

    private function badan(): void
    {
        $this->paragraf('Yang bertanda tangan di bawah ini:');

        $this->identitas([
            'Nama' => $this->dosen['nama'],
            'NIDN' => $this->dosen['nidn'],
            'NUPTK' => $this->dosen['nuptk'] ?: '—',
            'Status dosen tetap' => $this->dosen['status'],
            'Tempat tanggal lahir' => $this->dosen['ttl'],
            'Pangkat golongan ruang / TMT' => $this->dosen['pangkat'].' / '.$this->dosen['pangkat_tmt'],
            'Jabatan / TMT' => $this->dosen['jabatan'].' / '.$this->dosen['jabatan_tmt'],
            'Pendidikan tertinggi' => $this->dosen['pendidikan'],
            'Jurusan /program studi' => $this->dosen['prodi'],
        ]);

        $this->paragraf('Dengan ini menyatakan bahwa semua Karya Ilmiah yang digunakan untuk usulan kenaikan jabatan akademik dosen telah memenuhi integritas akademik, di antara karya ilmiah tersebut sebagai pemenuhan persyaratan khusus di bawah ini:');

        $this->tabelKarya();

        $this->daftar('1.', 'Adalah benar karya saya sendiri dan tidak dihasilkan dengan cara melanggar integritas akademik.');
        $this->daftar('2.', 'Apabila di kemudian hari terbukti bahwa karya ilmiah ini bukan karya saya sendiri atau ada pelanggaran integritas akademik maka saya bersedia menerima sanksi sesuai ketentuan perundang-undangan yang berlaku.');

        $this->paragraf('Demikian surat pernyataan ini saya buat untuk dipergunakan sebagaimana mestinya.');

        $this->tandaTangan();
    }

    /**
     * Tabel karya ilmiah: No, Karya Ilmiah, Judul, Tautan.
     * Formulir cetak selalu menyediakan minimal tiga baris.
     */
    private function tabelKarya(): void
    {
        $kolom = [
            ['lebar' => 1.4, 'align' => 'C'],
            ['lebar' => 4.2, 'align' => 'L'],
            ['lebar' => 6.8, 'align' => 'L'],
            ['lebar' => 4.6, 'align' => 'L'],
        ];

        $baris = $this->karya
            ->values()
            ->map(fn (KaryaTulis $karya, int $index) => [
                (string) ($index + 1),
                $karya->karya_ilmiah,
                $karya->judul,
                $karya->link_bukti,
            ]);

        while ($baris->count() < 3) {
            $baris->push(['', '', '', '']);
        }

        $this->barisTabel($kolom, ['No', 'Karya Ilmiah', 'Judul', 'Tautan Karya Ilmiah'], true);

        foreach ($baris as $sel) {
            $this->barisTabel($kolom, $sel);
        }

        $this->Ln(0.3);
    }

    /**
     * @param  list<array{lebar: float, align: string}>  $kolom
     * @param  list<string>  $sel
     */
    private function barisTabel(array $kolom, array $sel, bool $tebal = false): void
    {
        $this->SetFont(self::FONT, $tebal ? 'B' : '', self::UKURAN_FONT);

        $tinggi = 0.2;

        foreach ($kolom as $index => $item) {
            $tinggi = max(
                $tinggi,
                0.2 + $this->jumlahBaris((string) ($sel[$index] ?? ''), $item['lebar'] - 0.2) * self::TINGGI_BARIS,
            );
        }

        $x = self::MARGIN_KIRI;
        $y = $this->GetY();

        foreach ($kolom as $index => $item) {
            $this->Rect($x, $y, $item['lebar'], $tinggi);
            $this->SetXY($x + 0.1, $y + 0.1);
            $this->MultiCell($item['lebar'] - 0.2, self::TINGGI_BARIS, $this->teks((string) ($sel[$index] ?? '')), 0, $item['align']);
            $x += $item['lebar'];
        }

        $this->SetXY(self::MARGIN_KIRI, $y + $tinggi);
    }

    /**
     * Jumlah baris MultiCell untuk lebar tertentu — tinggi baris tabel ikut menyesuaikan.
     */
    private function jumlahBaris(string $teks, float $lebar): int
    {
        $s = $this->teks($teks);
        $baris = 1;
        $lebarBaris = 0.0;

        for ($i = 0, $n = strlen($s); $i < $n; $i++) {
            if ($s[$i] === "\n") {
                $baris++;
                $lebarBaris = 0.0;

                continue;
            }

            $lebarBaris += $this->GetStringWidth($s[$i]);

            if ($lebarBaris > $lebar) {
                $baris++;
                $lebarBaris = 0.0;
            }
        }

        return $baris;
    }

    /**
     * @param  array<string, string>  $baris
     */
    private function identitas(array $baris): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        foreach ($baris as $label => $nilai) {
            $this->Cell(6.3, self::TINGGI_BARIS, $this->teks($label), 0, 0);
            $this->Cell(0.4, self::TINGGI_BARIS, ':', 0, 0);
            $this->MultiCell(0, self::TINGGI_BARIS, $this->teks($nilai), 0, 'L');
        }

        $this->Ln(0.2);
    }

    private function paragraf(string $teks): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->MultiCell(0, self::TINGGI_BARIS + 0.05, $this->teks($teks), 0, 'J');
        $this->Ln(0.15);
    }

    private function daftar(string $nomor, string $teks): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->SetX(self::MARGIN_KIRI);
        $this->Cell(0.6, self::TINGGI_BARIS, $this->teks($nomor), 0, 0);
        $this->MultiCell(0, self::TINGGI_BARIS + 0.05, $this->teks($teks), 0, 'J');
        $this->Ln(0.1);
    }

    private function tandaTangan(): void
    {
        $this->Ln(0.4);

        $this->barisTandaTangan($this->teks(($this->instansi?->kota ?: '…').', '.$this->pakta->tanggal_pakta?->locale('id')->translatedFormat('j F Y')));
        $this->barisTandaTangan('Yang membuat pernyataan');
        $this->barisTandaTangan('', '', 1.8);
        // $this->barisTandaTangan('Ttd dan materai');
        // $this->barisTandaTangan('', '', 1.8);
        $this->barisTandaTangan($this->teks($this->dosen['nama']),'B');
        $this->barisTandaTangan($this->teks('NIDN: '.$this->dosen['nidn']));
    }

    private function barisTandaTangan(string $teks, string $gaya = '', float $tinggi = self::TINGGI_BARIS): void
    {
        $this->SetFont(self::FONT, $gaya, self::UKURAN_FONT);
        $this->SetX($this->GetPageWidth() - self::MARGIN_KANAN - 8);
        $this->Cell(8, $tinggi, $teks, 0, 1, 'L');
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
