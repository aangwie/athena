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

    protected $fillable = [
        'user_id',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_hp',
        'status_pernikahan',
        'nama_pasangan',
        'pekerjaan_pasangan',
        'nip_pasangan',
        'tempat_lahir_pasangan',
        'tanggal_lahir_pasangan',
        'tanggal_nikah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_lahir_pasangan' => 'date',
            'tanggal_nikah' => 'date',
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

    public function pekerjaanPasanganLabel(): string
    {
        return match ($this->pekerjaan_pasangan) {
            'asn' => 'ASN',
            'non_asn' => 'Non ASN',
            default => '—',
        };
    }
}
