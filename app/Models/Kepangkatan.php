<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kepangkatan extends Model
{
    protected $table = 'kepangkatan';

    protected $fillable = [
        'nama',
    ];
}
