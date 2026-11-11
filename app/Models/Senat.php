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
    ];

    protected $fillable = [
        'user_id',
        'jabatan',
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
     * Urutan tampil jabatan: Ketua, Sekretaris, lalu Anggota.
     *
     * Dipakai bersama oleh daftar senat di layar maupun tabel daftar hadir
     * pada dokumen cetak agar urutannya selalu sama.
     */
    public function urutanJabatan(): int
    {
        return (int) array_search($this->jabatan, array_keys(self::JABATAN), true);
    }
}
