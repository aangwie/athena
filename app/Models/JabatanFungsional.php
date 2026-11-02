<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data master jabatan akademik dosen (Asisten Ahli, Lektor, Lektor Kepala, dst).
 *
 * Istilah yang tampil ke pengguna adalah "Jabatan Akademik"; nama tabel/kolom
 * sengaja tetap `jabatan_fungsional` agar tidak perlu migrasi data.
 */
class JabatanFungsional extends Model
{
    protected $table = 'jabatan_fungsional';

    protected $fillable = [
        'nama',
    ];
}
