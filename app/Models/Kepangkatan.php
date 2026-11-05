<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kepangkatan extends Model
{
    protected $table = 'kepangkatan';

    protected $fillable = [
        'nama',
        'golongan',
    ];

    /**
     * Label pangkat beserta golongan, mis. "IIIa / Penata Muda".
     * Bila golongan belum diisi, hanya nama pangkat yang tampil.
     */
    public function label(): string
    {
        $golongan = trim((string) $this->golongan);
        $nama = trim((string) $this->nama);

        if ($golongan === '') {
            return $nama ?: '—';
        }

        return $nama === '' ? $golongan : $golongan.' / '.$nama;
    }
}
