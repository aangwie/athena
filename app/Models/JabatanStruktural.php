<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JabatanStruktural extends Model
{
    protected $table = 'jabatan_struktural';

    protected $fillable = [
        'nama',
    ];
}
