<?php

namespace App\Models;

use App\Support\Tautan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PanduanPenilaian extends Model
{
    /**
     * Sumber panduan penilaian: berkas PDF yang diunggah admin atau tautan berkas.
     *
     * @var array<string, string>
     */
    public const TIPE = [
        'file' => 'Berkas PDF',
        'link' => 'Tautan Berkas',
    ];

    /** Batas ukuran berkas panduan dalam kilobyte (10 MB). */
    public const MAKS_KB = 10240;

    protected $table = 'panduan_penilaian';

    protected $fillable = [
        'tipe',
        'path',
        'nama_asli',
        'url',
        'user_id',
    ];

    /**
     * Admin yang terakhir menyimpan panduan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Panduan yang sedang dipakai seluruh pengguna; hanya satu baris disimpan.
     */
    public static function saatIni(): ?self
    {
        return static::query()->with('user')->latest('updated_at')->first();
    }

    /**
     * Apakah panduan berasal dari berkas unggahan admin.
     */
    public function memakaiBerkas(): bool
    {
        return $this->tipe === 'file' && filled($this->path);
    }

    /**
     * Label sumber panduan untuk badge di halaman.
     */
    public function labelTipe(): string
    {
        return self::TIPE[$this->tipe] ?? '-';
    }

    /**
     * Tautan pratinjau (Google Drive /view -> /preview) untuk ditampilkan di iframe.
     */
    public function tautanPratinjau(): ?string
    {
        return filled($this->url) ? Tautan::pratinjau($this->url) : null;
    }

    /**
     * Ukuran berkas panduan dalam label ringkas, mis. "1,2 MB".
     */
    public function ukuranBerkas(): ?string
    {
        if (! $this->memakaiBerkas() || ! Storage::disk('local')->exists($this->path)) {
            return null;
        }

        $byte = Storage::disk('local')->size($this->path);

        return match (true) {
            $byte >= 1048576 => number_format($byte / 1048576, 1, ',', '.').' MB',
            $byte >= 1024 => number_format($byte / 1024, 0, ',', '.').' KB',
            default => $byte.' byte',
        };
    }
}
