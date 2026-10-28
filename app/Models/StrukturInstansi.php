<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StrukturInstansi extends Model
{
    protected $table = 'struktur_instansi';

    protected $fillable = [
        'jabatan_struktural_id',
        'user_id',
    ];

    public function jabatanStruktural(): BelongsTo
    {
        return $this->belongsTo(JabatanStruktural::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
