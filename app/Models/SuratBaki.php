<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBaki extends Model
{
    protected $table = 'surat_baki';

    protected $fillable = [
        'nomor_surat',
        'surat_bap_senat_id',
        'tanggal_surat',
        'tempat_pelaksanaan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
        ];
    }

    /**
     * Usulan senat (BAP) yang ditindaklanjuti BAKI ini.
     */
    public function bapSenat(): BelongsTo
    {
        return $this->belongsTo(SuratBapSenat::class, 'surat_bap_senat_id');
    }
}
