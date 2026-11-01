<?php

namespace App\Support;

use App\Models\Instansi;
use App\Models\Senat;
use App\Models\SuratBapSenat;
use Fpdf\Fpdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Berita Acara Persetujuan Senat (halaman 1) beserta Daftar Hadir Rapat Senat
 * (halaman 2) dalam format PDF (FPDF).
 *
 * Kertas A4 portrait, margin atas/bawah/kiri/kanan 2 cm.
 *
 * ponytail: sekretaris senat & tempat pelaksanaan rapat belum punya kolom di
 * form, jadi sekretaris diambil dari tabel senat bila ada dan tempat dibiarkan
 * bergaris titik untuk diisi tangan. Tambah input bila memang dibutuhkan.
 */
class SuratBapSenatPdf extends Fpdf
{
    private const ORIENTASI = 'P';

    /** Satuan FPDF: 'mm', 'cm', 'pt' atau 'in'. */
    private const SATUAN = 'cm';

    private const UKURAN = 'A4';

    private const MARGIN_KIRI = 2.0;

    private const MARGIN_ATAS = 1.5;

    private const MARGIN_KANAN = 2.0;

    private const MARGIN_BAWAH = 2.0;

    private const UKURAN_LOGO = 2.4;

    private const FONT = 'Times';

    private const UKURAN_FONT = 10.5;

    private const TINGGI_BARIS = 0.48;

    /** Lebar kolom tanda tangan (kiri: sekretaris, kanan: ketua senat). */
    private const LEBAR_KOLOM_TTD = 8.0;

    private bool $adaLogo = false;

    /**
     * @param  array<string, string>  $dosen
     * @param  Collection<int, Senat>  $anggota
     */
    public function __construct(
        private readonly ?Instansi $instansi,
        private readonly SuratBapSenat $bap,
        private readonly array $dosen,
        private readonly Collection $anggota,
        private readonly ?Senat $ketua,
        private readonly ?Senat $sekretaris,
    ) {
        parent::__construct(self::ORIENTASI, self::SATUAN, self::UKURAN);

        $this->SetMargins(self::MARGIN_KIRI, self::MARGIN_ATAS, self::MARGIN_KANAN);
        $this->SetAutoPageBreak(true, self::MARGIN_BAWAH);
        $this->SetTitle($this->teks('Berita Acara Persetujuan Senat — '.$this->dosen['nama']));
    }

    public function buat(): static
    {
        $this->AddPage();
        $this->kop();
        $this->judul();
        $this->pembukaan();
        $this->identitas();
        $this->penutup();
        $this->tandaTangan();
        $this->daftarAnggota();

        $this->AddPage();
        $this->daftarHadir();

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
        return 'bap-senat-'.Str::slug($this->dosen['nama']).'.pdf';
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

        $this->SetY($y + 0.5);
    }

    private function judul(): void
    {
        $this->SetFont(self::FONT, 'BU', 12);
        $this->Cell(0, 0.6, $this->teks('BERITA ACARA PERSETUJUAN SENAT'), 0, 1, 'C');

        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->Cell(0, 0.55, $this->teks('NOMOR: '.$this->bap->nomor_bap), 0, 1, 'C');
        $this->Ln(0.25);
    }

    private function pembukaan(): void
    {
        $tanggal = $this->bap->tanggal_bap?->locale('id');

        $this->paragraf(sprintf(
            'Pada hari ini %s tanggal %s bulan %s tahun %s, Senat %s yang dihadiri oleh sejumlah %d orang anggota senat (dengan jumlah yang memenuhi kuorum) sebagaimana daftar hadir terlampir, telah melakukan rapat pemberian pertimbangan/persetujuan pengajuan Kenaikan Jabatan Akademik kepada:',
            $tanggal?->translatedFormat('l') ?: '-',
            $tanggal?->translatedFormat('d') ?: '-',
            $tanggal?->translatedFormat('F') ?: '-',
            $tanggal?->translatedFormat('Y') ?: '-',
            $this->instansi?->nama_instansi ?: '-',
            $this->anggota->count(),
        ));
    }

    private function identitas(): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        foreach ([
            'Nama' => $this->dosen['nama'],
            'NIDN' => $this->dosen['nidn'],
            'NUPTK' => $this->dosen['nuptk'] ?: '—',
            'Status dosen tetap' => $this->dosen['status'],
            'Tempat, tanggal lahir' => $this->dosen['ttl'],
            'Pangkat golongan ruang, TMT' => $this->dosen['pangkat'].' / '.$this->dosen['pangkat_tmt'],
            'Jabatan, TMT' => $this->dosen['jabatan'].' / '.$this->dosen['jabatan_tmt'],
            'Pendidikan tertinggi' => $this->dosen['pendidikan'],
            'Jurusan/program studi' => $this->dosen['prodi'],
        ] as $label => $nilai) {
            $this->Cell(6.3, self::TINGGI_BARIS, $this->teks($label), 0, 0);
            $this->Cell(0.4, self::TINGGI_BARIS, ':', 0, 0);
            $this->MultiCell(0, self::TINGGI_BARIS, $this->teks($nilai), 0, 'L');
        }

        $this->Ln(0.2);
    }

    private function penutup(): void
    {
        $this->paragraf('Untuk Kenaikan Jabatan Akademik Dosen ke Asisten Ahli/Lektor/Lektor Kepala/Profesor dalam bidang ilmu penugasan keahlian/kompetensi/kepakaran '.$this->bap->bidang_keilmuan.'.');

        $this->paragraf('Hasil pertimbangan diberikan berdasarkan penilaian atas integritas, kinerja, tanggung jawab, dan tata krama dalam kehidupan kampus yang telah ditetapkan oleh yang bersangkutan dalam melaksanakan tridharma Perguruan Tinggi sejak yang bersangkutan menduduki jabatan akademik terakhir sampai dengan rapat pemberian pertimbangan persetujuan ini dilaksanakan, dengan hasil penilaian proporsi penelitian '.$this->bap->prosentase_penelitian.'% dari total kebutuhan kenaikan jabatan.');

        $this->paragraf('Apabila di kemudian hari terbukti bahwa ada pelanggaran integritas akademik maka Tim Senat ikut bertanggung jawab sesuai dengan Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Nomor 39 Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah.');

        $this->paragraf('Berita acara ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.');
    }

    private function tandaTangan(): void
    {
        $this->SetFont(self::FONT, '', self::UKURAN_FONT);

        $baris = [
            ['', $this->kotaTanggal()],
            ['Sekretaris', 'Ketua Senat'],
            ['', ''],
            [$this->namaSenat($this->sekretaris), $this->namaSenat($this->ketua)],
            [$this->nidnSenat($this->sekretaris), $this->nidnSenat($this->ketua)],
        ];

        $y = $this->GetY() + 0.3;

        foreach ($baris as $index => $isi) {
            $tinggi = $index === 2 ? 1.5 : self::TINGGI_BARIS;

            // Baris nama (index 3) dicetak tebal.
            $this->SetFont(self::FONT, $index === 3 ? 'B' : '', self::UKURAN_FONT);
            $this->SetXY(self::MARGIN_KIRI, $y);
            $this->Cell(self::LEBAR_KOLOM_TTD, $tinggi, $this->teks($isi[0]), 0, 0, 'L');
            $this->Cell(self::LEBAR_KOLOM_TTD, $tinggi, $this->teks($isi[1]), 0, 0, 'L');

            $y += $tinggi;
        }

        $this->SetY($y);
    }

    private function daftarAnggota(): void
    {
        $anggota = $this->anggota->values();

        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->Cell(0, 1.55, $this->teks('Anggota Senat'), 0, 1, 'C');

        if ($anggota->isEmpty()) {
            $this->SetFont(self::FONT, 'I', self::UKURAN_FONT);
            $this->Cell(0, self::TINGGI_BARIS, $this->teks('Belum ada anggota senat yang dipilih.'), 0, 1, 'C');

            return;
        }

        $this->SetFont(self::FONT, '', 9.5);

        $perKolom = (int) ceil($anggota->count() / 2);
        $y = $this->GetY();
        $kolom = [self::MARGIN_KIRI, self::MARGIN_KIRI + 8.5];
        $terbanyak = 0;

        foreach ($anggota->chunk($perKolom) as $indexKolom => $isiKolom) {
            $terbanyak = max($terbanyak, $isiKolom->count());

            foreach ($isiKolom->values() as $index => $senat) {
                $this->SetXY($kolom[$indexKolom], $y + $index * 0.5);
                $this->Cell(0.7, 0.5, $this->teks(($indexKolom * $perKolom + $index + 1).'.'), 0, 0);
                $this->Cell(4.3, 0.5, $this->teks($senat->user?->name ?? ''), 0, 0);
                $this->Cell(2.5, 0.5, str_repeat('_', 14), 0, 0, 'L');
            }
        }

        $this->SetY($y + $terbanyak * 0.5);
    }

    private function daftarHadir(): void
    {
        $this->SetFont(self::FONT, 'B', 12);
        $this->MultiCell(0, 0.7, $this->teks('DAFTAR HADIR RAPAT SENAT '.mb_strtoupper($this->instansi?->nama_instansi ?: '')), 0, 'C');
        $this->Ln(0.4);

        $this->SetFont(self::FONT, '', self::UKURAN_FONT);
        $this->barisTitik('Hari, tanggal', (string) $this->bap->tanggal_bap?->locale('id')->translatedFormat('l, d-m-Y'));
        $this->barisTitik('Tempat pelaksanaan', '');
        $this->barisTitik('Materi rapat', 'Pemberian pertimbangan/persetujuan kenaikan jabatan akademik dosen');
        $this->Ln(0.3);

        $this->tabelHadir();
        $this->tandaTanganPemimpin();
    }

    private function barisTitik(string $label, string $nilai): void
    {
        $this->Cell(4.2, 0.55, $this->teks($label), 0, 0);
        $this->Cell(0.3, 0.55, ':', 0, 0);
        $this->Cell(0, 0.55, $this->teks($nilai !== '' ? $nilai : str_repeat('.', 55)), 0, 1);
    }

    private function tabelHadir(): void
    {
        $kolom = [
            ['label' => 'NO', 'lebar' => 1.2, 'align' => 'C'],
            ['label' => 'NAMA', 'lebar' => 7.0, 'align' => 'C'],
            ['label' => 'JABATAN', 'lebar' => 4.0, 'align' => 'C'],
            ['label' => 'TANDATANGAN', 'lebar' => 4.8, 'align' => 'C'],
        ];

        $this->SetFont(self::FONT, 'B', 10);
        $this->SetX(self::MARGIN_KIRI);

        foreach ($kolom as $item) {
            $this->Cell($item['lebar'], 0.8, $this->teks($item['label']), 1, 0, $item['align']);
        }

        $this->Ln();

        $this->SetFont(self::FONT, '', 10);

        $anggota = $this->anggota->values();
        $jumlahBaris = max(12, $anggota->count());

        for ($i = 0; $i < $jumlahBaris; $i++) {
            $senat = $anggota->get($i);
            $isi = [
                (string) ($i + 1),
                $senat?->user?->name ?? '',
                $senat?->jabatanLabel() ?? '',
                '',
            ];

            $this->SetX(self::MARGIN_KIRI);

            foreach ($kolom as $index => $item) {
                $this->Cell($item['lebar'], 0.6, $this->teks($isi[$index]), 1, 0, $index === 0 ? 'C' : 'L');
            }

            $this->Ln();
        }
    }

    private function tandaTanganPemimpin(): void
    {
        $y = $this->GetY() + 0.6;
        $x = $this->GetPageWidth() - self::MARGIN_KANAN - self::LEBAR_KOLOM_TTD;

        foreach ([
            [$this->kotaTanggal(), self::TINGGI_BARIS, ''],
            ['Pemimpin rapat', self::TINGGI_BARIS, ''],
            ['', 1.7, ''],
            // Nama pemimpin rapat dicetak tebal.
            [$this->namaSenat($this->ketua), self::TINGGI_BARIS, 'B'],
            [$this->nidnSenat($this->ketua), self::TINGGI_BARIS, ''],
        ] as [$teks, $tinggi, $gaya]) {
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
        $this->MultiCell(0, self::TINGGI_BARIS + 0.04, $this->teks($teks), 0, 'J');
        $this->Ln(0.12);
    }

    private function kotaTanggal(): string
    {
        // "Pacitan, 12 Agustus 2025"
        return ($this->instansi?->kota ?: str_repeat('.', 15)).', '.($this->bap->tanggal_bap?->locale('id')->translatedFormat('j F Y') ?? str_repeat('.', 10));
    }

    private function namaSenat(?Senat $senat): string
    {
        // Apa adanya seperti di database (huruf besar/kecil + gelar tidak diubah).
        return $senat?->user?->name ?? '';
    }

    private function nidnSenat(?Senat $senat): string
    {
        return 'NIDN. '.($senat?->user?->nidn ?: str_repeat('.', 20));
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
