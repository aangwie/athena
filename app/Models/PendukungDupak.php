<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entri Syarat Khusus DUPAK milik satu dosen: data publikasi beserta berkas
 * bukti yang dipakai untuk penilaian syarat khusus.
 */
class PendukungDupak extends Model
{
    /** Pilihan "Sebagai Penulis Pertama / Utama". */
    public const PENULIS = ['Pertama', 'Utama'];

    /**
     * Berkas bukti syarat khusus: kunci => label, ukuran maksimal (KB), dan keterangan.
     *
     * @var array<string, array{label: string, maks_kb: int, keterangan?: string}>
     */
    public const BERKAS = [
        'pakta_integritas' => [
            'label' => 'Surat Pernyataan Pakta Integritas Karya Ilmiah Dosen (tidak menggunakan kop surat PT) (format Lampiran H.4 Kepmen 39/M/KEP/2026)',
            'maks_kb' => 1024,
        ],
        'pakta_validasi' => [
            'label' => 'Surat Pernyataan Pakta Integritas Hasil Validasi Karya Ilmiah oleh Pimpinan PT (menggunakan kop surat PT)',
            'maks_kb' => 1024,
        ],
        'disertasi' => [
            'label' => 'Disertasi atau Tesis Pendidikan Tertinggi (Cover, Daftar Isi, Lembar Pengesahan, Abstrak, Resume (Untuk Disertasi) atau Ringkasan Eksekutif (Untuk Tesis))',
            'maks_kb' => 4096,
        ],
        'check_similarity' => [
            'label' => 'Dokumen Check Similarity (Wajib Turnitin atau iThenticate) dan disertai dokumen protokol check similarity yang minimal memuat penjelasan bagian yang diexclude',
            'maks_kb' => 2048,
        ],
        'korespondensi' => [
            'label' => 'Dokumen Bukti Korespondensi',
            'maks_kb' => 2048,
            'keterangan' => 'Unggah Bukti Korespondensi dalam satu file pdf, antara lain: bukti submit artikel, bukti catatan koreksi dari para reviewer berkaitan substansi, bukti respon penulis terkait catatan dari para reviewer, bukti diterima, dan bukti publikasi.',
        ],
        'artikel' => [
            'label' => 'Dokumen Artikel (PDF)',
            'maks_kb' => 1024,
        ],
    ];

    /**
     * Baris formulir Syarat Khusus DUPAK sesuai lampiran penilaian syarat khusus.
     * Tipe isian: teks, tanggal, pilihan, tautan, atau berkas.
     *
     * @var array<int, array<string, mixed>>
     */
    public const BARIS = [
        1 => [
            'field' => 'judul_publikasi',
            'label' => 'Judul Publikasi',
            'tipe' => 'teks',
            'wajib' => true,
            'max' => 255,
            'placeholder' => 'Judul artikel yang dipublikasikan',
        ],
        2 => [
            'field' => 'kategori_kegiatan',
            'label' => 'Kategori Kegiatan',
            'tipe' => 'pilihan',
            'wajib' => true,
            'opsi' => KaryaTulis::KATEGORI_KEGIATAN,
        ],
        3 => [
            'field' => 'jenis_publikasi',
            'label' => 'Jenis Publikasi',
            'tipe' => 'pilihan',
            'wajib' => true,
            'opsi' => KaryaTulis::JENIS,
        ],
        4 => [
            'field' => 'nama_jurnal',
            'label' => 'Nama Jurnal/Prosiding',
            'tipe' => 'teks',
            'max' => 255,
            'placeholder' => 'Jurnal EduSains : Kajian Ilmu Pendidikan 8 (1)',
        ],
        5 => [
            'field' => 'volume',
            'label' => 'Volume Jurnal/Prosiding',
            'tipe' => 'teks',
            'max' => 50,
            'placeholder' => '8',
        ],
        6 => [
            'field' => 'nomor',
            'label' => 'Nomor Jurnal/Prosiding',
            'tipe' => 'teks',
            'max' => 50,
            'placeholder' => '1',
            'catatan' => 'Jika tidak ada, isi dengan -',
        ],
        7 => [
            'field' => 'tahun_terbit',
            'label' => 'Tahun Terbit',
            'tipe' => 'tanggal',
            'wajib' => true,
        ],
        8 => [
            'field' => 'halaman',
            'label' => 'Halaman',
            'tipe' => 'teks',
            'max' => 50,
            'placeholder' => '385-401',
        ],
        9 => [
            'field' => 'issn',
            'label' => 'ISSN',
            'tipe' => 'teks',
            'max' => 50,
            'placeholder' => '2548-78',
        ],
        10 => [
            'field' => 'penerbit',
            'label' => 'Penerbit',
            'tipe' => 'teks',
            'max' => 255,
            'placeholder' => 'Fakultas Ilmu Ekonomi dan Bisnis Universitas',
        ],
        11 => [
            'field' => 'doi',
            'label' => 'DOI (Optional)',
            'tipe' => 'tautan',
            'placeholder' => 'https://doi.org/10.25217/jiv8i1.1234556',
        ],
        12 => [
            'field' => 'tautan_jurnal',
            'label' => 'Tautan Web Jurnal',
            'tipe' => 'tautan',
            'placeholder' => 'https://journal.example.ac.id/index.php/…',
        ],
        13 => [
            'field' => 'tautan_artikel',
            'label' => 'Tautan Artikel di Laman SINTA/Scopus/WOS',
            'tipe' => 'tautan',
            'placeholder' => 'https://sinta.kemdikbud.go.id/authors/profile/…',
        ],
        14 => [
            'field' => 'penulis',
            'label' => 'Sebagai Penulis Pertama / Utama',
            'tipe' => 'pilihan',
            'wajib' => true,
            'opsi' => self::PENULIS,
        ],
        15 => [
            'field' => 'pakta_integritas',
            'label' => self::BERKAS['pakta_integritas']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['pakta_integritas']['maks_kb'],
        ],
        16 => [
            'field' => 'pakta_validasi',
            'label' => self::BERKAS['pakta_validasi']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['pakta_validasi']['maks_kb'],
        ],
        17 => [
            'field' => 'disertasi',
            'label' => self::BERKAS['disertasi']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['disertasi']['maks_kb'],
        ],
        18 => [
            'field' => 'check_similarity',
            'label' => self::BERKAS['check_similarity']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['check_similarity']['maks_kb'],
        ],
        19 => [
            'field' => 'korespondensi',
            'label' => self::BERKAS['korespondensi']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['korespondensi']['maks_kb'],
            'keterangan' => self::BERKAS['korespondensi']['keterangan'],
        ],
        20 => [
            'field' => 'tautan_sinta',
            'label' => 'Tautan profil akun SINTA dari pengusul (Rekam Jejak)',
            'tipe' => 'tautan',
            'placeholder' => 'https://sinta.kemdikbud.go.id/authors/profile/…',
        ],
        21 => [
            'field' => 'artikel',
            'label' => self::BERKAS['artikel']['label'],
            'tipe' => 'berkas',
            'wajib' => true,
            'maks_kb' => self::BERKAS['artikel']['maks_kb'],
        ],
    ];


    protected $table = 'pendukung_dupak';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'judul_publikasi',
        'kategori_kegiatan',
        'jenis_publikasi',
        'nama_jurnal',
        'volume',
        'nomor',
        'tahun_terbit',
        'halaman',
        'issn',
        'penerbit',
        'doi',
        'tautan_jurnal',
        'tautan_artikel',
        'penulis',
        'tautan_sinta',
        'berkas_pakta_integritas_path',
        'berkas_pakta_integritas_nama',
        'berkas_pakta_validasi_path',
        'berkas_pakta_validasi_nama',
        'berkas_disertasi_path',
        'berkas_disertasi_nama',
        'berkas_check_similarity_path',
        'berkas_check_similarity_nama',
        'berkas_korespondensi_path',
        'berkas_korespondensi_nama',
        'berkas_artikel_path',
        'berkas_artikel_nama',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_terbit' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kolom path berkas untuk kunci berkas tertentu (mis. berkas_artikel_path). */
    public static function kolomBerkas(string $kunci): string
    {
        return 'berkas_'.$kunci.'_path';
    }

    /** Path berkas bukti tersimpan, atau null bila belum diunggah. */
    public function pathBerkas(string $kunci): ?string
    {
        return $this->{self::kolomBerkas($kunci)};
    }

    /** Nama berkas asli saat diunggah, atau nama berkas pada disk sebagai cadangan. */
    public function namaBerkas(string $kunci): ?string
    {
        $path = $this->pathBerkas($kunci);

        if (! filled($path)) {
            return null;
        }

        return $this->{'berkas_'.$kunci.'_nama'} ?: basename($path);
    }

    /** Apakah berkas bukti untuk kunci ini sudah diunggah. */
    public function adaBerkas(string $kunci): bool
    {
        return filled($this->pathBerkas($kunci));
    }

    /** Label ukuran berkas siap tampil, mis. "1 MB" atau "500 KB". */
    public static function labelUkuran(int $kb): string
    {
        return $kb >= 1024
            ? rtrim(rtrim(number_format($kb / 1024, 1, ',', '.'), '0'), ',').' MB'
            : $kb.' KB';
    }

    /** Host tautan (mis. sinta.kemdikbud.go.id) untuk ditampilkan pada tabel. */
    public static function hostTautan(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        return parse_url($url, PHP_URL_HOST) ?: $url;
    }
}
