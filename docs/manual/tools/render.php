<?php

/**
 * Perender buku manual menjadi satu berkas HTML mandiri.
 *
 * Pemakaian (dari akar proyek):
 *   php docs/manual/tools/render.php
 *
 * Hasil: docs/manual/buku-manual.html — membuka tautan images/… secara relatif,
 * sehingga folder images/ harus tetap berada di samping berkas HTML tersebut.
 *
 * Berkas hasil ini juga disajikan aplikasi pada rute /manual (tautan "Buku Manual"
 * di navbar). Saat disajikan, ManualController mengarahkan tautan images/… ke rute
 * manual.gambar, jadi tidak perlu menaruh gambar di folder public.
 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

$sumber = dirname(__DIR__).'/buku-manual.md';
$tujuan = dirname(__DIR__).'/buku-manual.html';

$environment = new Environment([]);
$environment->addExtension(new CommonMarkCoreExtension());
$environment->addExtension(new GithubFlavoredMarkdownExtension());

$converter = new MarkdownConverter($environment);
$markdown = file_get_contents($sumber);
$html = (string) $converter->convert($markdown);

$gaya = <<<'CSS'
body{max-width:900px;margin:2rem auto;padding:0 1.25rem;font:16px/1.65 "Segoe UI",system-ui,sans-serif;color:#1e293b}
h1{border-bottom:2px solid #0d9488;padding-bottom:.35rem;margin-top:2.5rem;font-size:1.6rem}
h2{margin-top:2rem;font-size:1.3rem;color:#0f766e}
h3{margin-top:1.5rem;font-size:1.1rem}
img{max-width:100%;height:auto;border:1px solid #e2e8f0;border-radius:.5rem;margin:.75rem 0}
table{border-collapse:collapse;width:100%;margin:1rem 0;font-size:.9rem}
th,td{border:1px solid #cbd5e1;padding:.45rem .6rem;text-align:left;vertical-align:top}
th{background:#f1f5f9}
code{background:#f1f5f9;padding:.1rem .3rem;border-radius:.25rem;font-size:.9em}
pre{background:#f8fafc;border:1px solid #e2e8f0;padding:.75rem;overflow:auto}
blockquote{border-left:4px solid #14b8a6;background:#f0fdfa;margin:1rem 0;padding:.6rem 1rem}
em{color:#475569}
@media print{body{max-width:none;margin:0}img{break-inside:avoid}h1{break-before:page}}
CSS;

$dokumen = '<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buku Manual Pengguna SIMJAD</title>
<style>'.$gaya.'</style>
</head>
<body>
'.$html.'
</body>
</html>';

file_put_contents($tujuan, $dokumen);

printf("Markdown : %d baris, %d karakter\n", substr_count($markdown, "\n") + 1, strlen($markdown));
printf("HTML     : %d karakter -> %s\n", strlen($dokumen), basename($tujuan));
printf("Heading  : h1=%d h2=%d h3=%d\n",
    preg_match_all('/<h1[\s>]/i', $html),
    preg_match_all('/<h2[\s>]/i', $html),
    preg_match_all('/<h3[\s>]/i', $html));
printf("Tabel    : %d\n", preg_match_all('/<table[\s>]/i', $html));
printf("Gambar   : %d\n", preg_match_all('/<img[\s>]/i', $html));

