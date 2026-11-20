<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManualController extends Controller
{
    /** Folder buku manual di dalam repositori (sumber Markdown + hasil render HTML). */
    private const FOLDER = 'docs/manual';

    /** Berkas HTML hasil `php docs/manual/tools/render.php`. */
    private const BERKAS = 'buku-manual.html';

    /** Nama gambar yang sah: nomor urut, huruf kecil, tanda hubung, berakhiran .png. */
    private const POLA_GAMBAR = '/^[a-z0-9][a-z0-9-]*\.png$/';

    /**
     * Buku manual pengguna untuk semua peran. Berkas HTML disajikan apa adanya
     * (gaya dan tata letaknya sudah menyatu dengan dokumen), hanya tautan gambar
     * yang diarahkan ke rute gambar di aplikasi agar tidak bergantung pada folder
     * yang dapat diakses publik.
     */
    public function index(): Response
    {
        $lokasi = $this->lokasiBerkas();
        $isi = is_file($lokasi) ? file_get_contents($lokasi) : false;

        abort_if($isi === false, 404, 'Buku manual belum tersedia. Jalankan php docs/manual/tools/render.php untuk membuat versi HTML-nya.');

        $isi = $this->arahkanGambar($isi);
        $isi = $this->sisipkanBilah($isi);

        return response($isi, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Kirim satu tangkapan layar buku manual. Nama berkas dibatasi pola gambar
     * agar rute ini tidak bisa dipakai membaca berkas lain di server.
     */
    public function gambar(string $berkas): BinaryFileResponse
    {
        abort_unless(preg_match(self::POLA_GAMBAR, $berkas) === 1, 404);

        $lokasi = base_path(self::FOLDER.'/images/'.$berkas);

        abort_unless(is_file($lokasi), 404);

        return response()->file($lokasi, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** Lokasi berkas HTML buku manual. */
    private function lokasiBerkas(): string
    {
        return base_path(self::FOLDER.'/'.self::BERKAS);
    }

    /**
     * Ubah `<img src="images/…">` menjadi URL rute `manual.gambar` supaya gambar
     * tetap tampil walau dokumen tidak berada di folder publik.
     */
    private function arahkanGambar(string $isi): string
    {
        $hasil = preg_replace_callback(
            '/src="images\/([^"]+)"/',
            fn (array $cocok): string => 'src="'.route('manual.gambar', $cocok[1]).'"',
            $isi,
        );

        return $hasil ?? $isi;
    }

    /**
     * Tambahkan bilah navigasi kecil di atas dokumen: jalan kembali ke aplikasi,
     * nama web yang sedang dipakai, dan tombol cetak/Simpan PDF.
     */
    private function sisipkanBilah(string $isi): string
    {
        $nama = e(Pengaturan::namaWeb());
        $kembali = e(route('dashboard'));

        $gaya = <<<'HTML'
<style>
.athena-manual-bar{position:sticky;top:0;z-index:20;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.6rem;padding:.6rem .9rem;background:#0f766e;color:#ecfdf5;font:600 13px/1.4 "Segoe UI",system-ui,sans-serif;box-shadow:0 2px 8px rgba(15,23,42,.18)}
.athena-manual-bar a{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.45);border-radius:.5rem;padding:.35rem .7rem;white-space:nowrap}
.athena-manual-bar a:hover{background:rgba(255,255,255,.16)}
.athena-manual-bar span{flex:1;min-width:10rem;text-align:center;font-weight:500;color:#d1fae5}
.athena-manual-bar button{cursor:pointer;border:0;border-radius:.5rem;padding:.35rem .7rem;background:#ecfdf5;color:#0f766e;font:inherit;white-space:nowrap}
.athena-manual-bar button:hover{background:#fff}
@media print{.athena-manual-bar{display:none}}
</style>
HTML;

        $bilah = <<<HTML
<div class="athena-manual-bar">
    <a href="{$kembali}">&larr; Kembali ke aplikasi</a>
    <span>{$nama} &middot; Buku Manual Pengguna</span>
    <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
</div>
HTML;

        $isi = str_replace('</head>', $gaya."\n</head>", $isi);

        return preg_replace('/<body>/', '<body>'."\n".$bilah, $isi, 1) ?? $isi;
    }
}
