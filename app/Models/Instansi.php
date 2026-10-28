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
        'logo_path',
    ];

    /**
     * URL publik logo instansi.
     *
     * Memakai host dan port dari request aktif sehingga logo tetap tampil
     * meskipun APP_URL tidak memuat port (mis. saat `artisan serve`).
     */
    public function logoUrl(): ?string
    {
        return $this->logo_path
            ? asset('storage/'.ltrim($this->logo_path, '/'))
            : null;
    }
}
