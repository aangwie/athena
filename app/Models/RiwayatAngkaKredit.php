<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatAngkaKredit extends Model
{
    protected $table = 'riwayat_angka_kredit';

    protected $fillable = [
        'user_id',
        'tahun',
        'angka_kredit',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'angka_kredit' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Angka kredit dalam format Indonesia, misalnya "152,50".
     */
    public function angkaKreditLabel(): string
    {
        return number_format((float) $this->angka_kredit, 2, ',', '.');
    }
}
