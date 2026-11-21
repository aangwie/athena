<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendukungDupak extends Model
{
    /** Dokumen check similarity (Turnitin/iThenticate) beserta protokolnya. */
    public const JENIS_CHECK_SIMILARITY = 'check_similarity';

    /** Bukti korespondensi artikel dalam satu berkas PDF. */
    public const JENIS_BUKTI_KORESPONDENSI = 'bukti_korespondensi';

    /** Tautan profil akun SINTA (rekam jejak) milik pengusul. */
    public const JENIS_PROFIL_SINTA = 'profil_sinta';

    /** Dokumen artikel dalam format PDF. */
    public const JENIS_ARTIKEL = 'artikel';

    /**
     * Pilihan jenis dokumen pendukung DUPAK pada dropdown formulir.
     *
     * @var array<string, string>
     */
    public const JENIS = [
        self::JENIS_CHECK_SIMILARITY => 'Dokumen Check Similarity (Wajib Turnitin atau iThenticate)',
        self::JENIS_BUKTI_KORESPONDENSI => 'Dokumen Bukti Korespondensi',
        self::JENIS_PROFIL_SINTA => 'Tautan Profil Akun SINTA (Rekam Jejak)',
        self::JENIS_ARTIKEL => 'Dokumen Artikel (PDF)',
    ];

    /**
     * Keterangan tiap jenis dokumen, tampil sebagai petunjuk pada formulir.
     *
     * @var array<string, string>
     */
    public const KETERANGAN = [
        self::JENIS_CHECK_SIMILARITY => 'Unggah dokumen check similarity (wajib Turnitin atau iThenticate) beserta dokumen protokol check similarity yang minimal memuat penjelasan bagian yang diexclude, digabung dalam satu berkas PDF.',
        self::JENIS_BUKTI_KORESPONDENSI => 'Unggah Bukti Korespondensi dalam satu file pdf, antara lain: bukti submit artikel, bukti catatan koreksi dari para reviewer berkaitan substansi, bukti respon penulis terkait catatan dari para reviewer, bukti diterima, dan bukti publikasi.',
        self::JENIS_PROFIL_SINTA => 'Tempel tautan profil akun SINTA Anda, misalnya https://sinta.kemdiktisaintek.go.id/authors/profile/123456.',
        self::JENIS_ARTIKEL => 'Unggah dokumen artikel dalam format PDF.',
    ];

    protected $table = 'pendukung_dupak';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'jenis',
        'berkas_path',
        'berkas_nama',
        'tautan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah jenis dokumen ini memakai berkas unggahan (bukan tautan). */
    public function memakaiBerkas(): bool
    {
        return $this->jenis !== self::JENIS_PROFIL_SINTA;
    }

    /** Label jenis dokumen yang siap ditampilkan. */
    public function labelJenis(): string
    {
        return self::JENIS[$this->jenis] ?? ucfirst(str_replace('_', ' ', (string) $this->jenis));
    }

    /** Keterangan jenis dokumen yang siap ditampilkan. */
    public function keterangan(): ?string
    {
        return self::KETERANGAN[$this->jenis] ?? null;
    }

    /** Nama berkas asli saat diunggah, atau nama berkas pada disk sebagai cadangan. */
    public function namaBerkas(): ?string
    {
        if (! filled($this->berkas_path)) {
            return null;
        }

        return $this->berkas_nama ?: basename($this->berkas_path);
    }

    /** Host tautan (mis. sinta.kemdiktisaintek.go.id) untuk ditampilkan pada tabel. */
    public function hostTautan(): ?string
    {
        if (! filled($this->tautan)) {
            return null;
        }

        return parse_url($this->tautan, PHP_URL_HOST) ?: $this->tautan;
    }
}
