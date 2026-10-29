<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatKepangkatan extends Model
{
    protected $table = 'riwayat_kepangkatan';

    protected $fillable = [
        'user_id',
        'kepangkatan_id',
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

    public function kepangkatan(): BelongsTo
    {
        return $this->belongsTo(Kepangkatan::class);
    }

    /**
     * TMT dalam format Indonesia, misalnya "02 Agustus 2019".
     */
    public function tmtLabel(): string
    {
        return $this->tmt?->locale('id')->translatedFormat('d F Y') ?? '—';
    }

    public function pangkatLabel(): string
    {
        return $this->kepangkatan?->nama ?? '—';
    }
}
