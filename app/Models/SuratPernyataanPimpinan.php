<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratPernyataanPimpinan extends Model
{
    protected $table = 'surat_pernyataan_pimpinan';

    protected $fillable = [
        'struktur_instansi_id',
        'user_id',
        'tanggal_surat',
        'nomor_surat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
        ];
    }

    public function pimpinan(): BelongsTo
    {
        return $this->belongsTo(StrukturInstansi::class, 'struktur_instansi_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
