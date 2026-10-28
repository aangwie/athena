<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanJabatan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_jabatan';

    protected $fillable = [
        'user_id',
        'nomor_pengajuan',
        'jabatan_asal',
        'jabatan_tujuan',
        'program_studi',
        'periode',
        'tanggal_pengajuan',
        'status',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $user->isAdmin() ? $query : $query->where('user_id', $user->id);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'diverifikasi' => 'Diverifikasi',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => 'Tidak diketahui',
        };
    }

    public function statusClasses(): string
    {
        return match ($this->status) {
            'draft' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'diajukan' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'diverifikasi' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'disetujui' => 'bg-teal-50 text-teal-700 ring-teal-200',
            'ditolak' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }

    public function statusDotClasses(): string
    {
        return match ($this->status) {
            'draft' => 'bg-slate-400',
            'diajukan' => 'bg-sky-500',
            'diverifikasi' => 'bg-amber-500',
            'disetujui' => 'bg-teal-500',
            'ditolak' => 'bg-rose-500',
            default => 'bg-slate-400',
        };
    }
}
