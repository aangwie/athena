<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Models\JabatanStruktural;

class JabatanStrukturalController extends KepegawaianController
{
    protected function modelClass(): string
    {
        return JabatanStruktural::class;
    }

    protected function modul(): array
    {
        return [
            'kunci' => 'jabatan-struktural',
            'label' => 'Jabatan Struktural',
            'deskripsi' => 'Kelola daftar jabatan struktural pada lingkungan perguruan tinggi.',
            'route' => 'kepegawaian.jabatan-struktural',
            'ikon' => 'struktural',
            'contoh' => 'Contoh: Ketua Program Studi',
        ];
    }
}
