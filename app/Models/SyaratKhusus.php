<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyaratKhusus extends Model
{
    /**
     * Status verifikasi berkas syarat khusus beserta labelnya.
     *
     * @var array<string, string>
     */
    public const STATUS = [
        'draf' => 'Belum diajukan',
        'diajukan' => 'Menunggu verifikasi',
        'diterima' => 'Diterima',
        'ditolak' => 'Ditolak',
    ];

    /**
     * Status yang masih boleh diunggah ulang dan diajukan ke admin.
     *
     * @var list<string>
     */
    public const BISA_DIUBAH = ['draf', 'ditolak'];

    /**
     * Jenis berkas syarat khusus yang diverifikasi terpisah.
     *
     * @var list<string>
     */
    public const JENIS = ['bkd', 'ikd'];

    protected $table = 'syarat_khusus';

    protected $fillable = [
        'user_id',
        'tahun',
        'semester',
        'status',
        'catatan',
        'bkd_path',
        'ikd_path',
        'bkd_status',
        'ikd_status',
        'bkd_catatan',
        'ikd_catatan',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Berkas masih bisa diunggah ulang: belum diajukan atau ditolak admin.
     */
    public function bisaDiubah(): bool
    {
        return in_array($this->status, self::BISA_DIUBAH, true);
    }

    public function statusJenis(string $jenis): string
    {
        return $this->{$jenis.'_status'} ?? 'draf';
    }

    public function catatanJenis(string $jenis): ?string
    {
        return $this->{$jenis.'_catatan'};
    }

    /**
     * Ajukan berkas yang belum diajukan (draf/ditolak) ke admin.
     * Mengembalikan false bila tidak ada berkas yang berubah.
     */
    public function ajukan(): bool
    {
        $berubah = false;

        foreach (self::JENIS as $jenis) {
            if ($this->{$jenis.'_path'} && in_array($this->statusJenis($jenis), self::BISA_DIUBAH, true)) {
                $this->{$jenis.'_status'} = 'diajukan';
                $this->{$jenis.'_catatan'} = null;
                $berubah = true;
            }
        }

        if ($berubah) {
            $this->sinkronkanStatus()->save();
        }

        return $berubah;
    }

    /**
     * Keputusan admin untuk satu berkas saja.
     */
    public function putuskan(string $jenis, string $keputusan, ?string $catatan = null): void
    {
        $this->{$jenis.'_status'} = $keputusan;
        $this->{$jenis.'_catatan'} = $catatan;
        $this->sinkronkanStatus()->save();
    }

    /**
     * Turunkan status/catatan ajuan dari status tiap berkas.
     */
    public function sinkronkanStatus(): static
    {
        $status = array_map($this->statusJenis(...), self::JENIS);

        $this->status = match (true) {
            in_array('ditolak', $status, true) => 'ditolak',
            in_array('diajukan', $status, true) => 'diajukan',
            in_array('draf', $status, true) => 'draf',
            default => 'diterima',
        };

        $this->catatan = collect(self::JENIS)
            ->filter(fn (string $jenis) => $this->statusJenis($jenis) === 'ditolak' && filled($this->catatanJenis($jenis)))
            ->map(fn (string $jenis) => strtoupper($jenis).': '.$this->catatanJenis($jenis))
            ->implode(' · ') ?: null;

        return $this;
    }

    public function periodeLabel(): string
    {
        return KegiatanDupak::labelPeriode($this->tahun, $this->semester);
    }

    public function statusLabel(?string $jenis = null): string
    {
        $status = $jenis ? $this->statusJenis($jenis) : $this->status;

        return self::STATUS[$status] ?? ucfirst((string) $status);
    }

    public function statusClasses(?string $jenis = null): string
    {
        return match ($jenis ? $this->statusJenis($jenis) : $this->status) {
            'diajukan' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'diterima' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'ditolak' => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-50 text-slate-600 ring-slate-200',
        };
    }
}
