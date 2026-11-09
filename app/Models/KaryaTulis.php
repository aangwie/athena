<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaryaTulis extends Model
{
    /**
     * Pilihan jenis karya ilmiah pada formulir Riwayat Karya Tulis.
     *
     * @var list<string>
     */
    public const JENIS = [
        'Jurnal Nasional Tidak Terakreditasi',
        'Jurnal Nasional Terakreditasi Kemendiktisaintek (1,2,3,4,5,6)',
        'Prosiding Internasional Bereputasi',
        'Jurnal Internasional Bereputasi',
        'Karya Seni yang diakui Nasional',
        'Karya Seni yang diakui Internasional',
    ];

    /**
     * Jenis karya ilmiah berupa jurnal — memerlukan nama jurnal & alamat web jurnal.
     *
     * @var list<string>
     */
    public const JENIS_JURNAL = [
        'Jurnal Nasional Tidak Terakreditasi',
        'Jurnal Nasional Terakreditasi Kemendiktisaintek (1,2,3,4,5,6)',
        'Jurnal Internasional Bereputasi',
    ];

    /** Jenis karya ilmiah berupa prosiding — memerlukan nama prosiding, ISSN, URL, dan URL plagiasi. */
    public const JENIS_PROSIDING = 'Prosiding Internasional Bereputasi';

    protected $table = 'karya_tulis';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'karya_ilmiah',
        'judul',
        'link_bukti',
        'nama_jurnal',
        'url_jurnal',
        'nama_prosiding',
        'issn',
        'url_prosiding',
        'url_plagiasi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Apakah karya ini berupa jurnal (butuh nama & alamat web jurnal). */
    public function isJurnal(): bool
    {
        return in_array($this->karya_ilmiah, self::JENIS_JURNAL, true);
    }

    /** Apakah karya ini berupa prosiding (butuh nama prosiding, ISSN, URL, URL plagiasi). */
    public function isProsiding(): bool
    {
        return $this->karya_ilmiah === self::JENIS_PROSIDING;
    }

    /**
     * Tautan pratinjau Google Drive: /file/d/{id}/view -> /file/d/{id}/preview.
     */
    public function linkPratinjau(): string
    {
        return self::tautanPratinjau($this->link_bukti);
    }

    /**
     * Tautan pratinjau untuk kolom URL lain (jurnal, prosiding, plagiasi).
     */
    public function linkPratinjauKolom(?string $url): string
    {
        return self::tautanPratinjau($url);
    }

    private static function tautanPratinjau(?string $url): string
    {
        $url = (string) $url;

        return preg_replace('#/view/?(\?.*)?$#', '/preview', $url) ?? $url;
    }
}
