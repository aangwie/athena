<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SuratPengantar extends Model
{
    protected $table = 'surat_pengantar';

    protected $fillable = [
        'nomor_surat',
        'lampiran',
        'perihal',
        'tujuan',
        'kota_tujuan',
        'jabatan_diusulkan',
        'tanggal_surat',
        'kolektif',
        'dosen_ids',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_surat' => 'date',
            'kolektif' => 'boolean',
            'dosen_ids' => 'array',
        ];
    }

    /**
     * Dosen yang diusulkan pada surat ini, urut nama.
     *
     * @return Collection<int, User>
     */
    public function dosenList(): Collection
    {
        return User::query()
            ->with(['profilDosen', 'riwayatJabatanStruktural.jabatanFungsional'])
            ->whereIn('id', $this->dosen_ids ?? [])
            ->get()
            ->sortBy(fn (User $item) => $item->name)
            ->values();
    }
}
