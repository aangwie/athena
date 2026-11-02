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
            'label' => 'Jabatan Akademik',
            'deskripsi' => 'Kelola daftar jabatan akademik dosen (mis. Asisten Ahli, Lektor, Lektor Kepala).',
            'route' => 'kepegawaian.jabatan-fungsional',
            'ikon' => 'fungsional',
            'contoh' => 'Contoh: Lektor Kepala',
        ];
    }
}
