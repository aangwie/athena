<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProfilDosen extends Model
{
    use HasFactory;

    protected $table = 'profil_dosen';

    /**
     * Pilihan agama yang dipakai pada formulir data pribadi.
     *
     * @var array<string, string>
     */
    public const AGAMA = [
        'islam' => 'Islam',
        'kristen' => 'Kristen',
        'katolik' => 'Katolik',
        'hindu' => 'Hindu',
        'buddha' => 'Buddha',
        'konghucu' => 'Konghucu',
    ];

    protected $fillable = [
        'user_id',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'agama',
        'no_hp',
        'status_pernikahan',
        'nama_pasangan',
        'pekerjaan_pasangan',
        'nip_pasangan',
        'tempat_lahir_pasangan',
        'tanggal_lahir_pasangan',
        'tanggal_nikah',
        'niy_npp',
        'nuptk',
        'sk_pplp_tanggal',
        'sk_pplp_tahun',
        'sk_pplp_bulan',
        'sk_jafa_tanggal',
        'sk_jafa_tahun',
        'sk_jafa_bulan',
        'sk_impasing_tanggal',
        'sk_impasing_tahun',
        'sk_impasing_bulan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_lahir_pasangan' => 'date',
            'tanggal_nikah' => 'date',
            'sk_pplp_tanggal' => 'date',
            'sk_jafa_tanggal' => 'date',
            'sk_impasing_tanggal' => 'date',
            'sk_pplp_tahun' => 'integer',
            'sk_pplp_bulan' => 'integer',
            'sk_jafa_tahun' => 'integer',
            'sk_jafa_bulan' => 'integer',
            'sk_impasing_tahun' => 'integer',
            'sk_impasing_bulan' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anak(): HasMany
    {
        return $this->hasMany(AnakDosen::class);
    }

    public function statusPernikahanLabel(): string
    {
        return $this->status_pernikahan === 'menikah' ? 'Menikah' : 'Lajang';
    }

    public function agamaLabel(): string
    {
        return self::AGAMA[$this->agama] ?? '—';
    }

    /**
     * Masa kerja satu SK dalam format "11 Tahun 02 Bulan".
     */
    public function masaKerjaLabel(string $sk): string
    {
        $tahun = $this->{"sk_{$sk}_tahun"};
        $bulan = $this->{"sk_{$sk}_bulan"};

        if ($tahun === null && $bulan === null) {
            return '—';
        }

        return sprintf('%d Tahun %02d Bulan', $tahun ?? 0, $bulan ?? 0);
    }

    /**
     * Tanggal SK dalam format Indonesia, misalnya "02 Agustus 2019".
     */
    public function tanggalSkLabel(string $sk): string
    {
        return $this->{"sk_{$sk}_tanggal"}?->locale('id')->translatedFormat('d F Y') ?? '—';
    }

    public function pekerjaanPasanganLabel(): string
    {
        return match ($this->pekerjaan_pasangan) {
            'asn' => 'ASN',
            'non_asn' => 'Non ASN',
            default => '—',
        };
    }
}
