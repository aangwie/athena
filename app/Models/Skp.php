<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Skp extends Model
{
    protected $table = 'skp';

    /**
     * Unsur perilaku kerja menurut pedoman SKP, kunci → label, kolom nilai_{kunci}.
     *
     * @var array<string, string>
     */
    public const UNSUR = [
        'orientasi' => 'Orientasi Pelayanan',
        'integritas' => 'Integritas',
        'komitmen' => 'Komitmen',
        'disiplin' => 'Disiplin',
        'kerjasama' => 'Kerjasama',
        'kepemimpinan' => 'Kepemimpinan',
    ];

    protected $fillable = [
        'dosen_id',
        'pejabat_penilai_id',
        'atasan_penilai_id',
        'prodi_id',
        'periode_mulai',
        'periode_akhir',
        'nilai_orientasi',
        'nilai_integritas',
        'nilai_komitmen',
        'nilai_disiplin',
        'nilai_kerjasama',
        'nilai_kepemimpinan',
    ];

    protected function casts(): array
    {
        return [
            'periode_mulai' => 'date',
            'periode_akhir' => 'date',
            'nilai_orientasi' => 'integer',
            'nilai_integritas' => 'integer',
            'nilai_komitmen' => 'integer',
            'nilai_disiplin' => 'integer',
            'nilai_kerjasama' => 'integer',
            'nilai_kepemimpinan' => 'integer',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function pejabatPenilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pejabat_penilai_id');
    }

    public function atasanPenilai(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_penilai_id');
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    /**
     * Rentang penilaian, misalnya "Januari 2026 s.d. Desember 2026".
     */
    public function periodeLabel(): string
    {
        return $this->bulanTahun($this->periode_mulai).' s.d. '.$this->bulanTahun($this->periode_akhir);
    }

    private function bulanTahun(?Carbon $tanggal): string
    {
        return $tanggal === null
            ? '—'
            : $tanggal->locale('id')->translatedFormat('F Y');
    }

    /**
     * Nilai keenam unsur perilaku kerja (urutan UNSUR), null bila belum diinput.
     *
     * @return Collection<int, ?int>
     */
    public function daftarNilai(): Collection
    {
        return collect(array_keys(self::UNSUR))
            ->map(fn (string $kunci) => $this->{'nilai_'.$kunci});
    }

    public function sudahDinilai(): bool
    {
        return $this->daftarNilai()->every(fn ($nilai) => $nilai !== null);
    }

    public function jumlahNilai(): ?int
    {
        return $this->sudahDinilai() ? $this->daftarNilai()->sum() : null;
    }

    public function rataRataNilai(): ?float
    {
        $jumlah = $this->jumlahNilai();

        return $jumlah === null ? null : round($jumlah / count(self::UNSUR), 2);
    }

    /**
     * Nilai perilaku kerja = rata-rata nilai × 40%.
     */
    public function nilaiPerilakuKerja(): ?float
    {
        $rata = $this->rataRataNilai();

        return $rata === null ? null : round($rata * 0.4, 2);
    }

    /**
     * Kategori kualitas dari sebuah nilai angka (0–100).
     */
    public function kategoriNilai(?int $nilai): string
    {
        if ($nilai === null) {
            return '—';
        }

        return match (true) {
            $nilai >= 91 => 'Sangat Baik',
            $nilai >= 76 => 'Baik',
            $nilai >= 61 => 'Cukup',
            default => 'Kurang',
        };
    }
}