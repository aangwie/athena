<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    /**
     * Kunci pengaturan nama web yang tampil di sidebar, judul halaman, dan footer.
     */
    public const NAMA_WEB = 'nama_web';

    /**
     * Kunci pengaturan URL WhatsApp gateway.
     */
    public const WHATSAPP_GATEWAY = 'whatsapp_gateway_url';

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

    /**
     * URL dasar WhatsApp gateway, tanpa garis miring di akhir.
     */
    public static function whatsappGatewayUrl(): ?string
    {
        $url = static::query()->where('kunci', self::WHATSAPP_GATEWAY)->value('nilai');

        return filled($url) ? rtrim((string) $url, '/') : null;
    }

    public static function simpanWhatsappGatewayUrl(?string $url): void
    {
        $url = $url === null ? null : rtrim(trim($url), '/');

        static::query()->updateOrCreate(
            ['kunci' => self::WHATSAPP_GATEWAY],
            ['nilai' => $url === '' ? null : $url],
        );
    }
}
