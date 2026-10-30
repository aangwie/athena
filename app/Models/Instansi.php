<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instansi extends Model
{
    protected $table = 'instansi';

    protected $fillable = [
        'nama_instansi',
        'nomor_sk_operasional',
        'alamat',
        'no_telp',
        'website',
        'logo_base64',
    ];

    /**
     * Tautan WhatsApp admin instansi untuk tombol bantuan di sidebar.
     * Nomor berawalan 0 dikonversi menjadi 62, contoh 0812-3456-7890 → https://wa.me/6281234567890.
     */
    public static function whatsappAdmin(): ?string
    {
        $nomor = preg_replace('/\D+/', '', (string) static::query()->value('no_telp'));

        if ($nomor === '') {
            return null;
        }

        $nomor = match (true) {
            str_starts_with($nomor, '62') => $nomor,
            str_starts_with($nomor, '0') => '62'.substr($nomor, 1),
            default => '62'.$nomor,
        };

        return 'https://wa.me/'.$nomor;
    }

    /**
     * Data URI logo instansi yang siap dipakai sebagai atribut src gambar.
     */
    public function logoDataUri(): ?string
    {
        return $this->logo_base64 ?: null;
    }
}
