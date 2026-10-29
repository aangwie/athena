<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanDupak extends Model
{
    /**
     * Komponen Bidang A — Pendidikan.
     *
     * @var array<string, string>
     */
    public const JENIS_PENDIDIKAN = [
        'pendidikan_formal' => 'Pendidikan Formal',
        'diklat_prajabatan' => 'Pendidikan dan Pelatihan Prajabatan',
    ];

    /**
     * Komponen Bidang B — Pelaksanaan Pendidikan.
     *
     * @var array<string, string>
     */
    public const JENIS_PELAKSANAAN = [
        'perkuliahan' => 'Melaksanakan Perkuliahan',
        'seminar_mahasiswa' => 'Membimbing Seminar Mahasiswa',
        'pkl_kkn_pkn' => 'Membimbing PKL/KKN/PKN',
        'pembimbingan_skripsi' => 'Membimbing dan Ikut Membimbing dalam Menghasilkan Desertasi, Thesis, Skripsi, dan Laporan Akhir Studi',
        'penguji_ujian_akhir' => 'Penguji pada Ujian Akhir',
        'membina_kegiatan_mahasiswa' => 'Membina Kegiatan Mahasiswa',
        'pengembangan_program_kuliah' => 'Mengembangkan Program Kuliah',
        'pengembangan_bahan_ajar' => 'Mengembangkan Bahan Ajar',
        'orasi_ilmiah' => 'Menyampaikan Orasi Ilmiah',
        'jabatan_pimpinan_pt' => 'Menduduki Jabatan Pimpinan Perguruan Tinggi',
        'membimbing_dosen' => 'Membimbing Dosen yang Lebih Rendah Jabatan Fungsionalnya',
        'datasering_pencangkokan' => 'Melaksanakan Kegiatan Datasering dan Pencangkokan Akademik Dosen',
        'pengembangan_diri' => 'Melakukan Kegiatan Pengembangan Diri untuk Meningkatkan Kompetensi',
    ];

    /**
     * Rincian (select bertingkat) tiap komponen Bidang B. Komponen tanpa rincian
     * tidak ada di sini, sehingga `sub_jenis` dibiarkan kosong.
     *
     * @var array<string, array<string, string>>
     */
    public const SUB_PELAKSANAAN = [
        'pembimbingan_skripsi' => [
            'pembimbing_utama' => 'Pembimbing Utama',
            'pembimbing_pendamping' => 'Pembimbing Pendamping',
        ],
        'penguji_ujian_akhir' => [
            'ketua_penguji' => 'Ketua Penguji',
            'anggota_penguji' => 'Anggota Penguji',
        ],
        'pengembangan_bahan_ajar' => [
            'buku_ajar' => 'Buku Ajar',
            'modul_diktat_av_tutorial' => 'Modul/Petunjuk Praktikum/Diktat/Audio Visual/Naskah Tutorial',
        ],
        'jabatan_pimpinan_pt' => [
            'rektor' => 'Rektor/Ketua',
            'wakil_rektor' => 'Wakil Rektor/Ketua',
            'direktur_akademi' => 'Direktur Akademi',
            'ketua_prodi' => 'Ketua Prodi',
            'sekretaris_prodi' => 'Sekretaris Prodi',
            'kepala_laboratorium' => 'Kepala Laboratorium',
        ],
        'membimbing_dosen' => [
            'pembimbing_pencangkokan' => 'Pembimbing Pencangkokan',
            'reguler' => 'Reguler',
        ],
        'datasering_pencangkokan' => [
            'datasering' => 'Data Sering',
            'pencangkokan' => 'Pencangkokan',
        ],
        'pengembangan_diri' => [
            'jam_lebih_960' => 'Lamanya lebih dari 960 jam',
            'jam_641_960' => 'Lamanya 641-960 jam',
            'jam_481_640' => 'Lamanya 481-640 jam',
            'jam_161_480' => 'Lamanya 161-480 jam',
            'jam_81_160' => 'Lamanya 81-160 jam',
            'jam_31_80' => 'Lamanya 31-80 jam',
            'jam_10_30' => 'Lamanya 10-30 jam',
        ],
    ];

    /**
     * Komponen Bidang C — Penelitian.
     *
     * @var array<string, string>
     */
    public const JENIS_PENELITIAN = [
        'karya_ilmiah' => 'Menghasilkan Karya Ilmiah',
        'terjemahan_buku' => 'Menerjemahkan atau Menyadur Buku Ilmiah',
        'penyuntingan_buku' => 'Mengedit/Menyunting Buku Ilmiah',
        'paten_teknologi' => 'Membuat Rencana dan Karya Teknologi yang Dipatenkan',
        'rancangan_teknologi' => 'Membuat Rancangan dan Karya Teknologi',
        'karya_seni' => 'Rancangan dan Karya Seni Monumental/Pertunjukan/Karya Sastra',
    ];

    /**
     * Rincian tiap komponen Bidang C. Komponen tanpa rincian tidak ada di sini.
     *
     * @var array<string, array<string, string>>
     */
    public const SUB_PENELITIAN = [
        'karya_ilmiah' => [
            'dipublikasikan' => 'Hasil Penelitian yang Dipublikasikan',
            'tidak_dipublikasikan' => 'Hasil Penelitian atau Hasil Pemikiran yang Tidak Dipublikasikan',
        ],
        'karya_seni' => [
            'internasional' => 'Internasional',
            'nasional' => 'Nasional',
            'lokal' => 'Lokal',
        ],
    ];

    /**
     * Rincian tingkat ketiga Bidang C, dikelompokkan per `sub_jenis`.
     * Hanya "Hasil Penelitian yang Dipublikasikan" yang masih bercabang.
     *
     * @var array<string, array<string, string>>
     */
    public const RINCIAN_PENELITIAN = [
        'dipublikasikan' => [
            'buku_referensi' => 'Buku Referensi',
            'jurnal_internasional' => 'Jurnal Ilmiah Internasional',
            'jurnal_nasional_terakreditasi' => 'Jurnal Ilmiah Nasional Terakreditasi',
            'jurnal_nasional_tidak_terakreditasi' => 'Jurnal Ilmiah Nasional Tidak Terakreditasi',
            'seminar_internasional' => 'Seminar Internasional',
            'seminar_nasional' => 'Seminar Nasional',
            'koran_majalah_umum' => 'Koran atau Majalah Umum',
        ],
    ];

    /**
     * Komponen Bidang D — Pengabdian kepada Masyarakat.
     *
     * @var array<string, string>
     */
    public const JENIS_PENGABDIAN = [
        'jabatan_pimpinan' => 'Menduduki Jabatan Pimpinan',
        'pengembangan_hasil' => 'Melaksanakan Pengembangan Hasil Pendidikan dan Penelitian',
        'latihan_penyuluhan' => 'Memberikan Latihan/Penyuluhan/Penataran/Ceramah pada Masyarakat',
        'pelayanan_masyarakat' => 'Memberi Pelayanan kepada Masyarakat atau Kegiatan Lain yang Menunjang Pelaksanaan Tujuan Umum Pemerintah dan Pembangunan',
        'karya_pengabdian' => 'Membuat atau Menulis Karya Pengabdian',
    ];

    /**
     * Rincian tiap komponen Bidang D. Komponen tanpa rincian tidak ada di sini.
     *
     * @var array<string, array<string, string>>
     */
    public const SUB_PENGABDIAN = [
        'latihan_penyuluhan' => [
            'terjadwal_satu_semester' => 'Terjadwal dalam Satu Semester atau Lebih',
            'kurang_satu_semester' => 'Terjadwal Kurang dari Satu Semester',
        ],
        'pelayanan_masyarakat' => [
            'bidang_keahlian' => 'Berdasarkan Bidang Keahlian',
            'penugasan_lembaga' => 'Berdasarkan Penugasan Lembaga Perguruan Tinggi',
            'fungsi_jabatan' => 'Berdasarkan Fungsi/Jabatan',
        ],
    ];

    /**
     * Rincian tingkat ketiga Bidang D (tingkat pelaksanaan), per `sub_jenis`.
     *
     * @var array<string, array<string, string>>
     */
    public const RINCIAN_PENGABDIAN = [
        'terjadwal_satu_semester' => [
            'internasional' => 'Tingkat Internasional',
            'nasional' => 'Tingkat Nasional',
            'lokal' => 'Tingkat Lokal',
        ],
        'kurang_satu_semester' => [
            'internasional' => 'Tingkat Internasional',
            'nasional' => 'Tingkat Nasional',
            'lokal' => 'Tingkat Lokal',
        ],
    ];

    /**
     * Komponen Bidang E — Penunjang.
     *
     * @var array<string, string>
     */
    public const JENIS_PENUNJANG = [
        'panitia_pt' => 'Menjadi Anggota dalam Suatu Panitia/Badan pada Perguruan Tinggi',
        'panitia_pemerintah' => 'Menjadi Anggota Panitia/Badan pada Lembaga Pemerintah',
        'organisasi_profesi' => 'Menjadi Anggota Organisasi Profesi',
        'panitia_antar_lembaga' => 'Mewakili Perguruan Tinggi/Lembaga Pemerintah Duduk dalam Panitia Antar Lembaga',
        'delegasi_nasional' => 'Menjadi Anggota Delegasi Nasional ke Pertemuan Internasional',
        'pertemuan_ilmiah' => 'Berperan Aktif dalam Pertemuan Ilmiah',
        'penghargaan' => 'Mendapatkan Penghargaan/Tanda Jasa',
        'buku_pelajaran' => 'Menulis Buku Pelajaran SMA ke Bawah yang Diterbitkan dan Diedarkan Secara Nasional',
        'prestasi' => 'Mempunyai Prestasi di Bidang Olahraga/Humaniora',
        'tim_penilaian_ja' => 'Keanggotaan dalam Tim Penilaian Jabatan Akademik',
    ];

    /**
     * Rincian tiap komponen Bidang E. Komponen tanpa rincian tidak ada di sini.
     *
     * @var array<string, array<string, string>>
     */
    public const SUB_PENUNJANG = [
        'panitia_pt' => [
            'ketua_wakil_ketua' => 'Sebagai Ketua/Wakil Ketua Merangkap Anggota',
            'anggota' => 'Sebagai Anggota',
        ],
        'panitia_pemerintah' => [
            'panitia_pusat' => 'Panitia Pusat',
            'panitia_daerah' => 'Panitia Daerah',
        ],
        'organisasi_profesi' => [
            'profesi_internasional' => 'Tingkat Internasional',
            'profesi_nasional' => 'Tingkat Nasional',
        ],
        'delegasi_nasional' => [
            'ketua_delegasi' => 'Ketua Delegasi',
            'anggota_delegasi' => 'Anggota Delegasi',
        ],
        'pertemuan_ilmiah' => [
            'internasional_nasional_regional' => 'Tingkat Internasional/Nasional/Regional',
            'lingkungan_pt' => 'Di Lingkungan Perguruan Tinggi',
        ],
        'penghargaan' => [
            'satya_lancana_karya_satya' => 'Penghargaan/Tanda Jasa Satya Lancana Karya Satya',
            'penghargaan_lainnya' => 'Memperoleh Penghargaan Lainnya',
        ],
        'buku_pelajaran' => [
            'sma' => 'SMA Sederajat',
            'smp' => 'SMP Sederajat',
            'sd' => 'SD Sederajat',
        ],
        'prestasi' => [
            'prestasi_internasional' => 'Tingkat Internasional',
            'prestasi_nasional' => 'Tingkat Nasional',
            'prestasi_daerah_lokal' => 'Tingkat Daerah/Lokal',
        ],
    ];

    /**
     * Rincian tingkat ketiga Bidang E, dikelompokkan per `sub_jenis`.
     *
     * Kunci `sub_jenis` wajib unik lintas seluruh konstanta `SUB_*`, karena
     * `rincianMap()` pada request hanya melihat `sub_jenis`, bukan `jenis`.
     *
     * @var array<string, array<string, string>>
     */
    public const RINCIAN_PENUNJANG = [
        'panitia_pusat' => [
            'ketua_wakil_ketua' => 'Ketua/Wakil Ketua',
            'anggota' => 'Anggota',
        ],
        'panitia_daerah' => [
            'ketua_wakil_ketua' => 'Ketua/Wakil Ketua',
            'anggota' => 'Anggota',
        ],
        'profesi_internasional' => [
            'pengurus' => 'Pengurus',
            'anggota_atas_permintaan' => 'Anggota atas Permintaan',
            'anggota' => 'Anggota',
        ],
        'profesi_nasional' => [
            'pengurus' => 'Pengurus',
            'anggota_atas_permintaan' => 'Anggota atas Permintaan',
            'anggota' => 'Anggota',
        ],
        'internasional_nasional_regional' => [
            'ketua' => 'Ketua',
            'anggota' => 'Anggota',
        ],
        'lingkungan_pt' => [
            'ketua' => 'Ketua',
            'anggota' => 'Anggota',
        ],
        'satya_lancana_karya_satya' => [
            'tiga_puluh_tahun' => 'Tiga Puluh Tahun',
            'dua_puluh_tahun' => 'Dua Puluh Tahun',
            'sepuluh_tahun' => 'Sepuluh Tahun',
        ],
        'penghargaan_lainnya' => [
            'internasional' => 'Tingkat Internasional',
            'nasional' => 'Tingkat Nasional',
            'provinsi' => 'Tingkat Provinsi',
        ],
    ];

    public const SEMESTER = [
        'ganjil' => 'Ganjil',
        'genap' => 'Genap',
    ];

    protected $table = 'kegiatan_dupak';

    protected $fillable = [
        'komponen',
        'tahun',
        'semester',
        'jenis',
        'sub_jenis',
        'rincian',
        'nama_kegiatan',
        'link_bukti',
        'nilai_baru',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Batasi query pada satu periode (tahun akademik + semester).
     */
    public function scopePeriode(Builder $query, int $tahun, string $semester): Builder
    {
        return $query->where('tahun', $tahun)->where('semester', $semester);
    }

    public static function labelPeriode(int $tahun, string $semester): string
    {
        return $tahun.'/'.($tahun + 1).' · '.(self::SEMESTER[$semester] ?? ucfirst($semester));
    }

    public function semesterLabel(): string
    {
        return self::SEMESTER[$this->semester] ?? ucfirst((string) $this->semester);
    }

    public function periodeLabel(): string
    {
        return self::labelPeriode((int) $this->tahun, (string) $this->semester);
    }

    public function jenisLabel(): string
    {
        return self::JENIS_PENDIDIKAN[$this->jenis]
            ?? self::JENIS_PELAKSANAAN[$this->jenis]
            ?? self::JENIS_PENELITIAN[$this->jenis]
            ?? self::JENIS_PENGABDIAN[$this->jenis]
            ?? self::JENIS_PENUNJANG[$this->jenis]
            ?? str_replace('_', ' ', (string) $this->jenis);
    }

    public function subJenisLabel(): ?string
    {
        return self::SUB_PELAKSANAAN[$this->jenis][$this->sub_jenis]
            ?? self::SUB_PENELITIAN[$this->jenis][$this->sub_jenis]
            ?? self::SUB_PENGABDIAN[$this->jenis][$this->sub_jenis]
            ?? self::SUB_PENUNJANG[$this->jenis][$this->sub_jenis]
            ?? null;
    }

    public function rincianLabel(): ?string
    {
        return self::RINCIAN_PENELITIAN[$this->sub_jenis][$this->rincian]
            ?? self::RINCIAN_PENGABDIAN[$this->sub_jenis][$this->rincian]
            ?? self::RINCIAN_PENUNJANG[$this->sub_jenis][$this->rincian]
            ?? null;
    }
}
