<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    /**
     * Kunci pengaturan nama web yang tampil di sidebar, judul halaman, dan footer.
     */
    public const NAMA_WEB = 'nama_web';

    protected $table = 'pengaturan';

    protected $fillable = [
        'kunci',
        'nilai',
    ];

    /**
     * Nama web aktif; kembali ke config app.name bila admin belum menyimpannya.
     */
    public static function namaWeb(): string
    {
        return static::query()->where('kunci', self::NAMA_WEB)->value('nilai')
            ?: config('app.name', 'Athena');
    }

    public static function simpanNamaWeb(string $nama): void
    {
        static::query()->updateOrCreate(
            ['kunci' => self::NAMA_WEB],
            ['nilai' => trim($nama)],
        );
    }
}
