<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanDupak extends Model
{
    /**
     * Status usulan DUPAK beserta labelnya.
     *
     * @var array<string, string>
     */
    public const STATUS = [
        'diajukan' => 'Menunggu penilaian',
        'dinilai' => 'Sudah dinilai',
        'diajukan_dupak' => 'Diajukan DUPAK',
        'disetujui' => 'Disetujui',
        'perlu_perbaikan' => 'Butuh Perbaikan',
        'ditolak' => 'Ditolak',
    ];

    /**
     * Status yang berarti periode sudah masuk tahap pengajuan DUPAK.
     *
     * @var list<string>
     */
    public const STATUS_DUPAK = ['diajukan_dupak', 'disetujui', 'perlu_perbaikan', 'ditolak'];

    protected $table = 'pengajuan_dupak';

    protected $fillable = [
        'user_id',
        'tahun',
        'semester',
        'status',
        'diajukan_at',
        'dinilai_at',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'diajukan_at' => 'datetime',
            'dinilai_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Periode yang sudah diajukan sebagai DUPAK, apa pun keputusan admin.
     */
    public function scopeDiajukanDupak(Builder $query): Builder
    {
        return $query->whereIn('status', self::STATUS_DUPAK);
    }

    public function periodeLabel(): string
    {
        return KegiatanDupak::labelPeriode($this->tahun, $this->semester);
    }

    /**
     * Nilai PAK terkunci setelah admin menyetujui DUPAK periode ini:
     * TIM PAK tidak bisa menilai ulang dan dosen tidak bisa mengajukan lagi.
     */
    public function nilaiTerkunci(): bool
    {
        return $this->status === 'disetujui';
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            'dinilai' => 'bg-teal-50 text-teal-700 ring-teal-200',
            'diajukan_dupak' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'disetujui' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'perlu_perbaikan' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'ditolak' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-amber-50 text-amber-700 ring-amber-200',
        };
    }
}
