<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBapSenat extends Model
{
    /**
     * Bidang keilmuan senat, dikelompokkan per rumpun ilmu.
     * Format: [rumpun => [nama bidang => kode bidang]].
     *
     * @var array<string, array<string, string>>
     */
    public const BIDANG_KEILMUAN = [
        'A. Komputer & Teknologi' => [
            'APLIKASI PERANGKAT LUNAK SITUS' => '20.101.1003',
            'PENDIDIKAN KOMPUTER ATAU INFORMATIKA' => '50.132.1053',
            'TEKNIK JARINGAN KOMPUTER' => '50.150.1003',
        ],
        'B. Bahasa & Linguistik' => [
            'BAHASA DAN KEBUDAYAAN INDONESIA' => '30.105.1012',
            'BAHASA INGGRIS' => '30.105.1022',
            'LINGUISTIK' => '30.105.1043',
            'LINGUISTIK TERAPAN BAHASA INGGRIS' => '30.105.1046',
            'PENDIDIKAN BAHASA DAN SASTRA INDONESIA' => '50.132.1017',
            'PENDIDIKAN BAHASA INDONESIA' => '50.132.1019',
            'PENDIDIKAN BAHASA INGGRIS' => '50.132.1020',
        ],
        'C. Pendidikan & Keguruan' => [
            'MANAJEMEN PENDIDIKAN' => '50.132.1004',
            'PENDIDIKAN' => '50.132.1006',
            'PENDIDIKAN GURU SEKOLAH DASAR' => '50.132.1039',
            'PENDIDIKAN ILMU PENGETAHUAN SOSIAL' => '50.132.1042',
            'PENDIDIKAN MATEMATIKA' => '50.132.1057',
            'PSIKOLOGI PENDIDIKAN' => '50.113.1040',
        ],
        'D. Humaniora, Sosial & Keagamaan' => [
            'KAJIAN SASTRA DAN BUDAYA' => '30.108.1001',
            'SEJARAH' => '30.106.1002',
            'ILMU ATAU SAINS KEOLAHRAGAAN' => '50.114.1005',
            'KEAGAMAAN' => '60.101.1001',
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

    protected $table = 'surat_bap_senat';

    protected $fillable = [
        'nomor_bap',
        'tanggal_bap',
        'user_id',
        'bidang_keilmuan',
        'prosentase_penelitian',
        'tempat_pelaksanaan',
        'materi_rapat',
        'anggota_senat_ids',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bap' => 'date',
            'prosentase_penelitian' => 'integer',
            'anggota_senat_ids' => 'array',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pilihan bidang keilmuan untuk dropdown, dikelompokkan per rumpun.
     * Label memuat kode bidang di belakangnya, mis. "SEJARAH (30.106.1002)".
     *
     * @return array<string, array<string, string>>
     */
    public static function bidangKeilmuanOptions(): array
    {
        $opsi = [];

        foreach (self::BIDANG_KEILMUAN as $rumpun => $daftar) {
            foreach ($daftar as $nama => $kode) {
                $opsi[$rumpun][$nama] = $nama.' ('.$kode.')';
            }
        }

        return $opsi;
    }

    /**
     * Daftar nama bidang keilmuan yang sah (tanpa kode), untuk validasi.
     *
     * @return list<string>
     */
    public static function bidangKeilmuanList(): array
    {
        return array_keys(array_merge(...array_values(self::BIDANG_KEILMUAN)));
    }

    /**
     * Rumpun ilmu dari sebuah bidang keilmuan (null bila tidak terdaftar).
     */
    public static function rumpunBidangKeilmuan(?string $bidang): ?string
    {
        foreach (self::BIDANG_KEILMUAN as $rumpun => $daftar) {
            if (array_key_exists((string) $bidang, $daftar)) {
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
