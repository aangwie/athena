<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratPaktaIntegritas extends Model
{
    protected $table = 'surat_pakta_integritas';

    protected $fillable = [
        'user_id',
        'tanggal_pakta',
        'karya_tulis_ids',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pakta' => 'date',
            'karya_tulis_ids' => 'array',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Karya ilmiah yang dicetak pada surat: pilihan admin, atau semua karya dosen bila kosong.
     *
     * @return Collection<int, KaryaTulis>
     */
    public function karyaTerpilih(): Collection
    {
        $semua = $this->dosen?->karyaTulis ?? new Collection;

        return $this->karya_tulis_ids
            ? $semua->whereIn('id', $this->karya_tulis_ids)->values()
            : $semua;
    }
}
