<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBapSenat extends Model
{
    /**
     * Bidang keilmuan senat, dikelompokkan per rumpun ilmu.
     * Format: [rumpun => [nama bidang, ...]] — ditampilkan apa adanya tanpa kode angka.
     *
     * @var array<string, list<string>>
     */
    public const BIDANG_KEILMUAN = [
        'A. Komputer & Teknologi' => [
            'APLIKASI PERANGKAT LUNAK SITUS',
            'PENDIDIKAN KOMPUTER ATAU INFORMATIKA',
            'TEKNIK JARINGAN KOMPUTER',
        ],
        'B. Bahasa & Linguistik' => [
            'BAHASA DAN KEBUDAYAAN INDONESIA',
            'BAHASA INGGRIS',
            'LINGUISTIK',
            'LINGUISTIK TERAPAN BAHASA INGGRIS',
            'PENDIDIKAN BAHASA DAN SASTRA INDONESIA',
            'PENDIDIKAN BAHASA INDONESIA',
            'PENDIDIKAN BAHASA INGGRIS',
        ],
        'C. Pendidikan & Keguruan' => [
            'MANAJEMEN PENDIDIKAN',
            'PENDIDIKAN',
            'PENDIDIKAN GURU SEKOLAH DASAR',
            'PENDIDIKAN ILMU PENGETAHUAN SOSIAL',
            'PENDIDIKAN MATEMATIKA',
            'PENDIDIKAN SEJARAH',
            'PSIKOLOGI PENDIDIKAN',
        ],
        'D. Humaniora, Sosial & Keagamaan' => [
            'KAJIAN SASTRA DAN BUDAYA',
            'SEJARAH',
            'ILMU ATAU SAINS KEOLAHRAGAAN',
            'KEAGAMAAN',
        ],
        'E. Ekonomi' => [
            'EKONOMI',
            'PENDIDIKAN EKONOMI',
            'EKONOMI SUMBERDAYA',
            'MANAJEMEN SDM',
        ],
    ];

    /**
     * Label rumpun untuk bidang keilmuan yang belum ada pada daftar (data lama).
     */
    public const RUMPUN_LAINNYA = 'Lainnya (data lama)';

    /**
     * Pilihan tetap prosentase proporsi penelitian pada BAP senat (dropdown).
     *
     * @var list<int>
     */
    public const PROSENTASE_PENELITIAN = [150, 200, 300, 400, 550, 700, 850, 1050];

    /**
     * Pilihan tujuan kenaikan jabatan akademik pada BAP senat.
     *
     * @var array<string, string>
     */
    public const KENAIKAN_JABATAN = [
        'belum_asisten_ahli' => 'Belum ke Asisten Ahli',
        'asisten_ahli_ke_lektor' => 'Asisten Ahli ke Lektor',
    ];

    /**
     * Mode pengisian jumlah kuorum pada naskah cetak: otomatis mengikuti jumlah anggota
     * senat yang dipilih, atau manual sesuai angka yang diisi admin.
     */
    public const KUORUM_OTOMATIS = 'otomatis';

    public const KUORUM_MANUAL = 'manual';

    /**
     * Pilihan mode pengisian jumlah kuorum (toggle pada formulir BAP senat).
     *
     * @var array<string, string>
     */
    public const KUORUM_MODE = [
        self::KUORUM_OTOMATIS => 'Otomatis (jumlah anggota senat)',
        self::KUORUM_MANUAL => 'Manual (isi sendiri)',
    ];

    protected $table = 'surat_bap_senat';

    protected $fillable = [
        'nomor_bap',
        'tanggal_bap',
        'user_id',
        'bidang_keilmuan',
        'kenaikan_jabatan',
        'prosentase_penelitian',
        'tempat_pelaksanaan',
        'materi_rapat',
        'anggota_senat_ids',
        'kuorum_mode',
        'jumlah_kuorum',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bap' => 'date',
            'prosentase_penelitian' => 'integer',
            'anggota_senat_ids' => 'array',
            'jumlah_kuorum' => 'integer',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pilihan bidang keilmuan untuk dropdown, dikelompokkan per rumpun.
     * Nama bidang ditampilkan apa adanya, tanpa kode angka di belakangnya.
     *
     * @return array<string, list<string>>
     */
    public static function bidangKeilmuanOptions(): array
    {
        return self::BIDANG_KEILMUAN;
    }

    /**
     * Daftar nama bidang keilmuan yang sah, untuk validasi.
     *
     * @return list<string>
     */
    public static function bidangKeilmuanList(): array
    {
        return array_merge(...array_values(self::BIDANG_KEILMUAN));
    }

    /**
     * Rumpun ilmu dari sebuah bidang keilmuan (null bila tidak terdaftar).
     */
    public static function rumpunBidangKeilmuan(?string $bidang): ?string
    {
        foreach (self::BIDANG_KEILMUAN as $rumpun => $daftar) {
            if (in_array((string) $bidang, $daftar, true)) {
                return $rumpun;
            }
        }

        return null;
    }

    /**
     * Daftar nilai prosentase proporsi penelitian yang sah, untuk dropdown & validasi.
     *
     * @return list<int>
     */
    public static function prosentasePenelitianList(): array
    {
        return self::PROSENTASE_PENELITIAN;
    }

    /**
     * Daftar kunci pilihan kenaikan jabatan akademik yang sah, untuk dropdown & validasi.
     *
     * @return list<string>
     */
    public static function kenaikanJabatanList(): array
    {
        return array_keys(self::KENAIKAN_JABATAN);
    }

    /**
     * Prosentase proporsi penelitian hanya dipakai pada kenaikan Asisten Ahli ke Lektor.
     */
    public function menggunakanProsentase(): bool
    {
        return $this->kenaikan_jabatan === 'asisten_ahli_ke_lektor';
    }

    /**
     * Daftar kunci pilihan mode pengisian kuorum yang sah, untuk validasi.
     *
     * @return list<string>
     */
    public static function kuorumModeList(): array
    {
        return array_keys(self::KUORUM_MODE);
    }

    /**
     * Jumlah kuorum diisi manual oleh admin (bukan mengikuti jumlah anggota senat).
     */
    public function kuorumManual(): bool
    {
        return $this->kuorum_mode === self::KUORUM_MANUAL;
    }

    /**
     * Angka yang dicetak pada kalimat "yang dihadiri oleh sejumlah ... orang anggota senat":
     * angka manual bila mode manual, selain itu mengikuti jumlah anggota senat terpilih.
     */
    public function jumlahKuorum(int $otomatis): int
    {
        if ($this->kuorumManual() && $this->jumlah_kuorum !== null) {
            return (int) $this->jumlah_kuorum;
        }

        return $otomatis;
    }

    /**
     * Tujuan kenaikan jabatan untuk naskah cetak, mis. "Asisten Ahli" atau "Lektor".
     */
    public function targetKenaikan(): string
    {
        return match ($this->kenaikan_jabatan) {
            'asisten_ahli_ke_lektor' => 'Lektor',
            default => 'Asisten Ahli',
        };
    }

    /**
     * Anggota senat yang tercatat hadir pada rapat, mengikuti urutan pilihan admin saat
     * mengisi formulir: pilihan pertama paling atas, pilihan terakhir paling bawah.
     *
     * @return Collection<int, Senat>
     */
    public function anggotaSenat(): Collection
    {
        $urutan = array_map('intval', $this->anggota_senat_ids ?? []);

        // Posisi pada daftar pilihan dipakai sebagai kunci urut. Anggota yang sudah tidak
        // ada pada daftar pilihan diletakkan paling bawah agar tetap tampil.
        $posisi = array_flip($urutan);

        return Senat::query()
            ->with('user')
            ->whereIn('user_id', $urutan)
            ->get()
            ->sortBy(fn (Senat $item) => $posisi[(int) $item->user_id] ?? PHP_INT_MAX)
            ->values();
    }
}
