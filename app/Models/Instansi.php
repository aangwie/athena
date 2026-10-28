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
     * Data URI logo instansi yang siap dipakai sebagai atribut src gambar.
     */
    public function logoDataUri(): ?string
    {
        return $this->logo_base64 ?: null;
    }
}
