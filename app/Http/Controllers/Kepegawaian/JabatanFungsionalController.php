<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Models\JabatanFungsional;

class JabatanFungsionalController extends KepegawaianController
{
    protected function modelClass(): string
    {
        return JabatanFungsional::class;
    }

    protected function modul(): array
    {
        return [
            'kunci' => 'jabatan-fungsional',
            'label' => 'Jabatan Fungsional',
            'deskripsi' => 'Kelola daftar jabatan fungsional dosen yang digunakan pada pengajuan jabatan akademik.',
            'route' => 'kepegawaian.jabatan-fungsional',
            'ikon' => 'fungsional',
            'contoh' => 'Contoh: Lektor Kepala',
        ];
    }
}
