<?php

namespace App\Support;

use App\Models\Instansi;
use App\Models\SuratPernyataanPimpinan;
use Fpdf\Fpdf;
use Illuminate\Support\Str;

/**
 * Surat Pernyataan Pimpinan dalam format PDF (FPDF).
 *
 * Format cetak diatur lewat konstanta di bawah, mengikuti dokumentasi FPDF:
 * - ukuran kertas  : parameter ke-3 __construct() — 'A4', 'Legal', 'Letter', atau [lebar, tinggi]
 * - orientasi      : parameter ke-1 __construct() — 'P' portrait / 'L' landscape
 * - margin         : SetMargins(kiri, atas, kanan) dan SetAutoPageBreak(true, bawah)
 */
class SuratPernyataanPimpinanPdf extends Fpdf
{
    private const ORIENTASI = 'P';

    /** Satuan FPDF: 'mm', 'cm', 'pt' atau 'in'. */
    private const SATUAN = 'cm';

    private const UKURAN = 'A4';

    private const MARGIN_KIRI = 2.0;

    private const MARGIN_ATAS = 2.0;

    private const MARGIN_KANAN = 2.0;

    private const MARGIN_BAWAH = 1.0;

    private const UKURAN_LOGO = 2.4;

    private const FONT = 'Times';

    private const UKURAN_FONT = 10.5;

    private const TINGGI_BARIS = 0.5;

    private bool $adaLogo = false;

    /**
     * @param  array<string, string>  $pimpinan
     * @param  array<string, string>  $dosen
     */
    public function __construct(
        private readonly ?Instansi $instansi,
        private readonly SuratPernyataanPimpinan $pernyataan,
        private readonly array $pimpinan,
        private readonly array $dosen,
    ) {
        parent::__construct(self::ORIENTASI, self::SATUAN, self::UKURAN);

        $this->SetMargins(self::MARGIN_KIRI, self::MARGIN_ATAS, self::MARGIN_KANAN);
        $this->SetAutoPageBreak(true, self::MARGIN_BAWAH);
        $this->SetTitle($this->teks('Surat Pernyataan Pimpinan — '.$this->dosen['nama']));
    }

    public function buat(): static
    {
        $this->AddPage();
        $this->kop();
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
        return 'surat-pernyataan-pimpinan-'.Str::slug($this->dosen['nama']).'.pdf';
    }

    private function kop(): void
    {
        $this->logo();

        $this->SetY(self::MARGIN_ATAS);
        $this->SetLeftMargin(self::MARGIN_KIRI + ($this->adaLogo ? self::UKURAN_LOGO + 0.4 : 0));
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

        $this->SetY($y + 0.5);
    }

    private function judul(): void
    {
        $this->SetFont(self::FONT, 'BU', 12);
        $this->Cell(0, 0.7, $this->teks('SURAT PERNYATAAN PEMIMPIN PERGURUAN TINGGI'), 0, 1, 'C');

        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->Cell(0, 0.6, $this->teks('Nomor: '.($this->pernyataan->nomor_surat ?: '…')), 0, 1, 'C');
        $this->Ln(0.3);
    }

    private function badan(): void
    {
        $this->paragraf('Yang bertandatangan di bawah ini, Pimpinan '.($this->instansi?->nama_instansi ?: '…').':');

        $this->identitas([
            'Nama' => $this->pimpinan['nama'],
            'NIDN' => $this->pimpinan['nidn'],
            'NUPTK' => $this->pimpinan['nuptk'],
            'Status dosen tetap' => $this->pimpinan['status'],
            'Tempat, tanggal lahir' => $this->pimpinan['ttl'],
            'Pangkat golongan ruang / TMT' => $this->pimpinan['pangkat'].' / '.$this->pimpinan['pangkat_tmt'],
            'Jabatan / TMT' => $this->pimpinan['jabatan'].' / '.$this->pimpinan['jabatan_tmt'],
        ]);

        $this->paragraf('Menyatakan dengan sebenarnya bahwa seluruh dokumen persyaratan yang diajukan sebagai bahan pertimbangan kelayakan kenaikan jabatan akademik dosen, atas nama:');

        $this->identitas([
            'Nama' => $this->dosen['nama'],
            'NIDN' => $this->dosen['nidn'],
            'NUPTK' => $this->dosen['nuptk'],
            'Status dosen tetap' => $this->dosen['status'],
            'Tempat, tanggal lahir' => $this->dosen['ttl'],
            'Pangkat golongan ruang / TMT' => $this->dosen['pangkat'].' / '.$this->dosen['pangkat_tmt'],
            'Jabatan / TMT / pendidikan tertinggi' => $this->dosen['jabatan'].' /-/ '.$this->dosen['pendidikan'],
            'Jurusan/program studi' => $this->dosen['prodi'],
        ]);

        $this->paragraf('Telah diperiksa dan divalidasi dengan baik bahwa seluruh dokumen yang diajukan adalah benar dan dapat kami pertanggungjawabkan sesuai ketentuan yang berlaku, baik dalam proses perolehannya maupun hal-hal yang berkaitan dengan dokumen yang bersangkutan.');

        $this->paragraf('Apabila di kemudian hari terbukti ada pelanggaran integritas akademik terhadap dokumen tersebut, maka kami bersedia menerima sanksi sesuai dengan ketentuan peraturan perundang-undangan, dan bersedia mempertanggungjawabkan secara hukum sesuai dengan peraturan yang berlaku, serta menerima bahwa proses kenaikan jabatan akademik di Perguruan Tinggi akan ditangguhkan hingga periode kenaikan jabatan berikutnya.');

        $this->paragraf('Demikian surat pernyataan ini saya buat untuk dipergunakan sebagaimana mestinya.');

        $this->tandaTangan();
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

    private function tandaTangan(): void
    {
        $this->Ln(0.4);

        $this->barisTandaTangan($this->teks(($this->instansi?->kota ?: '…').', '.$this->pernyataan->tanggal_surat?->format('d-m-Y')));
        $this->barisTandaTangan('Pimpinan,');
        $this->barisTandaTangan('', '', 2.2);
        $this->barisTandaTangan($this->teks(mb_strtoupper($this->pimpinan['nama'])), 'BU');
        $this->barisTandaTangan($this->teks('NIDN: '.$this->pimpinan['nidn']));
    }

    private function barisTandaTangan(string $teks, string $gaya = '', float $tinggi = self::TINGGI_BARIS): void
    {
        $this->SetFont(self::FONT, $gaya, self::UKURAN_FONT);
        $this->SetX($this->GetPageWidth() - self::MARGIN_KANAN - 8);
        $this->Cell(8, $tinggi, $teks, 0, 1, 'L');
    }

    /**
     * @param  array<string, string>  $identitas
     */
    private function nidnNuptk(array $identitas): string
    {
        return $identitas['nuptk'] ? $identitas['nidn'].' / '.$identitas['nuptk'] : $identitas['nidn'];
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
