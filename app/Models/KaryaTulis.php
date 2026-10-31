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

    protected $table = 'karya_tulis';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'karya_ilmiah',
        'judul',
        'link_bukti',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tautan pratinjau Google Drive: /file/d/{id}/view -> /file/d/{id}/preview.
     */
    public function linkPratinjau(): string
    {
        return preg_replace('#/view/?(\?.*)?$#', '/preview', $this->link_bukti) ?? $this->link_bukti;
    }
}
