<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBapSenat extends Model
{
    protected $table = 'surat_bap_senat';

    protected $fillable = [
        'nomor_bap',
        'tanggal_bap',
        'user_id',
        'bidang_keilmuan',
        'prosentase_penelitian',
        'anggota_senat_ids',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bap' => 'date',
            'prosentase_penelitian' => 'integer',
            'anggota_senat_ids' => 'array',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Anggota senat yang tercatat hadir pada rapat, urut nama.
     *
     * @return Collection<int, Senat>
     */
    public function anggotaSenat(): Collection
    {
        return Senat::query()
            ->with('user')
            ->whereIn('user_id', $this->anggota_senat_ids ?? [])
            ->get()
            ->sortBy(fn (Senat $item) => $item->user?->name ?? '')
            ->values();
    }
}
