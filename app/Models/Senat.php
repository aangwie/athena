<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Senat extends Model
{
    protected $table = 'senat';

    /**
     * Jabatan yang tersedia pada senat.
     *
     * @var array<string, string>
     */
    public const JABATAN = [
        'ketua' => 'Ketua',
        'sekretaris' => 'Sekretaris',
        'anggota' => 'Anggota',
        'anggota_ex_officio' => 'Anggota/Ex Officio',
    ];

    /**
     * Jabatan yang tidak boleh dipakai sebagai jabatan rangkapan. Ketua tetap
     * hanya boleh menjadi jabatan utama agar senat punya satu pemimpin rapat.
     *
     * @var list<string>
     */
    public const JABATAN_TIDAK_DIRANGKAP = ['ketua'];

    protected $fillable = [
        'user_id',
        'jabatan',
        'jabatan_tambahan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jabatanLabel(): string
    {
        return self::JABATAN[$this->jabatan] ?? '—';
    }

    /**
     * Label jabatan beserta rangkapannya, mis. "Ketua merangkap Anggota".
     * Dosen yang tidak merangkap cukup menampilkan jabatan utamanya.
     */
    public function jabatanLengkap(): string
    {
        $tambahan = self::JABATAN[$this->jabatan_tambahan] ?? null;

        return $tambahan === null
            ? $this->jabatanLabel()
            : $this->jabatanLabel().' merangkap '.$tambahan;
    }

    /**
     * Pilihan jabatan rangkapan: semua jabatan kecuali Ketua.
     *
     * @return array<string, string>
     */
    public static function rangkapanOptions(): array
    {
        return array_diff_key(self::JABATAN, array_flip(self::JABATAN_TIDAK_DIRANGKAP));
    }

    /**
     * Peta opsi jabatan rangkapan untuk setiap jabatan utama; jabatan utama itu
     * sendiri tidak ditawarkan. Dipakai dropdown bertingkat pada formulir.
     *
     * @return array<string, array<string, string>>
     */
    public static function rangkapanPerJabatan(): array
    {
        $peta = [];

        foreach (array_keys(self::JABATAN) as $jabatan) {
            $peta[$jabatan] = array_diff_key(self::rangkapanOptions(), [$jabatan => '']);
        }

        return $peta;
    }

    /**
     * Urutan tampil jabatan: Ketua, Sekretaris, Anggota, lalu Anggota/Ex Officio.
     * Dosen yang merangkap diurutkan memakai jabatan tertingginya.
     *
     * Dipakai untuk mengurutkan pilihan pada daftar senat dan dropdown formulir BAP.
     * Urutan nama pada daftar hadir cetak justru mengikuti urutan pilihan admin
     * (lihat SuratBapSenat::anggotaSenat()).
     */
    public function urutanJabatan(): int
    {
        $kunci = array_keys(self::JABATAN);
        $utama = array_search($this->jabatan, $kunci, true);

        if ($utama === false) {
            return count($kunci);
        }

        $tambahan = array_search($this->jabatan_tambahan, $kunci, true);

        return $tambahan === false ? (int) $utama : min((int) $utama, (int) $tambahan);
    }
}
