<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Teks di dalam PDF hasil FPDF (stream halaman di-deflate zlib) — untuk diperiksa di test.
     */
    protected function teksPdf(string $pdf): string
    {
        preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $stream);

        return collect($stream[1])
            ->map(fn ($data) => function_exists('gzuncompress') ? (@gzuncompress($data) ?: $data) : $data)
            ->implode("\n");
    }
}
