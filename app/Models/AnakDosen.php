<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnakDosen extends Model
{
    use HasFactory;

    protected $table = 'anak_dosen';

    protected $fillable = [
        'profil_dosen_id',
        'nama',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pekerjaan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function profilDosen(): BelongsTo
    {
        return $this->belongsTo(ProfilDosen::class);
    }

    public function jenisKelaminLabel(): string
    {
        return $this->jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki';
    }
}
