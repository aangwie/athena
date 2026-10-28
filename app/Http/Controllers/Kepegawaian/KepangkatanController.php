<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Models\Kepangkatan;

class KepangkatanController extends KepegawaianController
{
    protected function modelClass(): string
    {
        return Kepangkatan::class;
    }

    protected function modul(): array
    {
        return [
            'kunci' => 'kepangkatan',
            'label' => 'Kepangkatan',
            'deskripsi' => 'Kelola daftar pangkat dan golongan kepegawaian dosen.',
            'route' => 'kepegawaian.kepangkatan',
            'ikon' => 'kepangkatan',
            'contoh' => 'Contoh: Penata Tingkat I',
        ];
    }
}
