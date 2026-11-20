<?php

/**
 * Pemeriksaan akhir buku manual: tautan gambar, tabel, dan sisa penanda kerja.
 *
 * Pemakaian (dari akar proyek):
 *   php docs/manual/tools/check.php
 *
 * Kode keluar 1 bila ada masalah, sehingga bisa dipakai pada pemeriksaan otomatis.
 */

$folder = dirname(__DIR__);
$markdown = (string) file_get_contents($folder.'/buku-manual.md');
$html = is_file($folder.'/buku-manual.html') ? (string) file_get_contents($folder.'/buku-manual.html') : '';

$masalah = [];

// 1. Semua tautan gambar harus ada berkasnya.
preg_match_all('/!\[[^\]]*\]\((images\/[^)]+)\)/', $markdown, $cocok);
$tautan = array_values(array_unique($cocok[1]));
foreach ($tautan as $berkas) {
    if (! is_file($folder.'/'.$berkas)) {
        $masalah[] = 'Gambar hilang: '.$berkas;
    }
}

// 2. Berkas gambar yang tidak pernah dirujuk.
$semua = glob($folder.'/images/*.png') ?: [];
$takTerpakai = array_values(array_filter(
    array_map(static fn ($p) => 'images/'.basename($p), $semua),
    static fn ($nama) => ! in_array($nama, $tautan, true),
));
if ($takTerpakai !== []) {
    $masalah[] = 'Gambar tidak dirujuk: '.implode(', ', $takTerpakai);
}

// 3. Sisa penanda kerja.
foreach (['<!--CHUNK-->', 'TODO', 'TBD', 'lorem'] as $sisa) {
    if (stripos($markdown, $sisa) !== false) {
        $masalah[] = 'Sisa penanda: '.$sisa;
    }
}

// 4. Setiap tabel markdown harus punya baris pemisah setelah baris judul.
$baris = preg_split('/\R/', $markdown);
$jumlahTabel = 0;
foreach ($baris as $i => $isi) {
    $barisIni = trim($isi);
    $sebelum = trim($baris[$i - 1] ?? '');
    $berikut = trim($baris[$i + 1] ?? '');

    $awalTabel = str_starts_with($barisIni, '|') && ! str_starts_with($sebelum, '|');
    if (! $awalTabel) {
        continue;
    }

    $jumlahTabel++;
    if (! preg_match('/^\|[\s:|-]+\|$/', $berikut)) {
        $masalah[] = 'Tabel tanpa baris pemisah pada baris '.($i + 1);
    }
}

// 5. Jumlah gambar pada HTML harus sama dengan jumlah rujukan.
$diHtml = preg_match_all('/<img[\s>]/i', $html);

printf("Rujukan gambar unik : %d\n", count($tautan));
printf("Berkas gambar      : %d\n", count($semua));
printf("Gambar di HTML     : %d\n", $diHtml);
printf("Tabel markdown     : %d\n", $jumlahTabel);
printf("Tabel di HTML      : %d\n", preg_match_all('/<table[\s>]/i', $html));
printf("Karakter markdown  : %d\n", strlen($markdown));

if ($masalah !== []) {
    echo "\nMASALAH:\n- ".implode("\n- ", $masalah)."\n";
    exit(1);
}

echo "\nOK - tidak ada masalah.\n";
