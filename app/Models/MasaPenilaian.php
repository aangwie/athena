<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MasaPenilaian extends Model
{
    /**
     * Nama bulan untuk formulir "Bulan W Tahun X s.d. Bulan Y Tahun Z".
     *
     * @var array<int, string>
     */
    public const BULAN = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    protected $table = 'masa_penilaian';

    protected $fillable = [
        'user_id',
        'tahun',
        'semester',
        'mulai',
        'akhir',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'mulai' => 'date',
            'akhir' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Label rentang, misalnya "Januari 2026 s.d. Desember 2026".
     */
    public function label(): string
    {
        return $this->bulanTahun($this->mulai).' s.d. '.$this->bulanTahun($this->akhir);
    }

    private function bulanTahun(?Carbon $tanggal): string
    {
        return $tanggal === null
            ? '—'
            : self::BULAN[$tanggal->month].' '.$tanggal->year;
    }
}
