<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatJabatanStruktural extends Model
{
    protected $table = 'riwayat_jabatan_struktural';

    protected $fillable = [
        'user_id',
        'jabatan_struktural_id',
        'jabatan_fungsional_id',
        'tmt',
    ];

    protected function casts(): array
    {
        return [
            'tmt' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jabatanStruktural(): BelongsTo
    {
        return $this->belongsTo(JabatanStruktural::class);
    }

    public function jabatanFungsional(): BelongsTo
    {
        return $this->belongsTo(JabatanFungsional::class);
    }

    /**
     * TMT dalam format Indonesia, misalnya "02 Agustus 2019".
     */
    public function tmtLabel(): string
    {
        return $this->tmt?->locale('id')->translatedFormat('d F Y') ?? '—';
    }

    public function strukturalLabel(): string
    {
        return $this->jabatanStruktural?->nama ?? '—';
    }

    public function fungsionalLabel(): string
    {
        return $this->jabatanFungsional?->nama ?? '—';
    }
}
