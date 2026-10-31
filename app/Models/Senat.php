<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Senat extends Model
{
    protected $table = 'senat';

    /**
     * Jabatan yang tersedia pada senat.
     *
     * @var array<string, string>
     */
    public const JABATAN = [
        'ketua' => 'Ketua',
        'anggota' => 'Anggota',
    ];

    protected $fillable = [
        'user_id',
        'jabatan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jabatanLabel(): string
    {
        return self::JABATAN[$this->jabatan] ?? '—';
    }
}
