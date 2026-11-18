<?php

namespace App\Support;

class Tautan
{
    /**
     * Tautan pratinjau Google Drive: /file/d/{id}/view -> /file/d/{id}/preview.
     *
     * Dipakai pratinjau karya tulis dan panduan penilaian agar berkas Drive
     * dapat ditampilkan langsung di dalam halaman.
     */
    public static function pratinjau(?string $url): string
    {
        $url = (string) $url;

        return preg_replace('#/view/?(\?.*)?$#', '/preview', $url) ?? $url;
    }
}
