<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPendidikan extends Model
{
    use HasFactory;

    /**
     * Daftar jenjang pendidikan beserta labelnya.
     *
     * @var array<string, string>
     */
    public const JENJANG = [
        'sd' => 'SD',
        'smp' => 'SMP',
        'sma' => 'SMA',
        'smk' => 'SMK',
        'd3' => 'D3',
        'd4' => 'D4',
        's1' => 'S1',
        's2' => 'S2',
        's3' => 'S3',
    ];

    /**
     * Jenjang yang termasuk perguruan tinggi (wajib ijazah + transkrip).
     *
     * @var list<string>
     */
    public const PERGURUAN_TINGGI = ['d3', 'd4', 's1', 's2', 's3'];

    protected $table = 'riwayat_pendidikan';

    protected $fillable = [
        'user_id',
        'jenjang',
        'nama_institusi',
        'jurusan',
        'tahun_masuk',
        'tahun_lulus',
        'ijazah_path',
        'transkrip_path',
    ];

    protected function casts(): array
    {
        return [
            'tahun_masuk' => 'integer',
            'tahun_lulus' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jenjangLabel(): string
    {
        return self::JENJANG[$this->jenjang] ?? strtoupper($this->jenjang);
    }

    /**
     * Urutan jenjang dari pendidikan dasar hingga doktor.
     */
    public function jenjangRank(): int
    {
        $rank = array_search($this->jenjang, array_keys(self::JENJANG), true);

        return $rank === false ? 99 : $rank + 1;
    }

    public function isPerguruanTinggi(): bool
    {
        return in_array($this->jenjang, self::PERGURUAN_TINGGI, true);
    }

    /**
     * Rentang tahun studi untuk ditampilkan.
     */
    public function tahunLabel(): string
    {
        $akhir = $this->tahun_lulus ? (string) $this->tahun_lulus : 'sekarang';

        return "{$this->tahun_masuk} – {$akhir}";
    }
}
