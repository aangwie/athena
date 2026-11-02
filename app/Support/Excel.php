<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Penulis & pembaca berkas .xlsx satu sheet tanpa pustaka tambahan
 * (ekstensi ZipArchive dan SimpleXML sudah tersedia di PHP).
 *
 * ponytail: satu sheet, tanpa rumus/gaya kompleks. Ganti ke PhpSpreadsheet
 * bila nanti butuh multi-sheet, format tanggal, atau berkas .xls lama.
 */
class Excel
{
    /**
     * Tulis berkas .xlsx berisi satu sheet. Baris pertama dianggap judul kolom
     * dan ditebalkan.
     *
     * @param  array<int, array<int, string|int|float|null>>  $baris
     * @param  array<int, int>  $lebar  lebar kolom (karakter), urut mulai kolom A
     */
    public static function tulis(string $jalur, array $baris, array $lebar = []): void
    {
        $isi = '';

        foreach (array_values($baris) as $r => $sel) {
            $isi .= '<row r="'.($r + 1).'">';

            foreach (array_values($sel) as $k => $nilai) {
                $isi .= '<c r="'.self::referensi($k, $r + 1).'" t="inlineStr"'.($r === 0 ? ' s="1"' : '')
                    .'><is><t xml:space="preserve">'.self::aman($nilai).'</t></is></c>';
            }

            $isi .= '</row>';
        }

        $cols = '';
        foreach (array_values($lebar) as $k => $w) {
            $kolom = $k + 1;
            $cols .= '<col min="'.$kolom.'" max="'.$kolom.'" width="'.$w.'" customWidth="1"/>';
        }

        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

        $berkas = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                .'</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<workbook xmlns="'.$ns.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<sheets><sheet name="Data Dosen" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
                .'</Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<styleSheet xmlns="'.$ns.'">'
                .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font>'
                .'<font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
                .'<borders count="1"><border/></borders>'
                .'<cellStyleXfs count="1"><xf/></cellStyleXfs>'
                .'<cellXfs count="2"><xf xfId="0"/><xf fontId="1" xfId="0" applyFont="1"/></cellXfs>'
                .'</styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<worksheet xmlns="'.$ns.'">'.($cols === '' ? '' : '<cols>'.$cols.'</cols>')
                .'<sheetData>'.$isi.'</sheetData></worksheet>',
        ];

        $zip = new ZipArchive();

        if ($zip->open($jalur, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Berkas Excel tidak dapat dibuat.');
        }

        foreach ($berkas as $nama => $xml) {
            $zip->addFromString($nama, $xml);
        }

        $zip->close();
    }

    /**
     * Baca sheet pertama berkas .xlsx menjadi baris nilai teks.
     *
     * @return array<int, array<int, string>>
     */
    public static function baca(string $jalur): array
    {
        $zip = new ZipArchive();

        if ($zip->open($jalur) !== true) {
            throw new RuntimeException('Berkas Excel tidak dapat dibaca. Pastikan berkas berformat .xlsx.');
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $shared = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($sheet === false) {
            throw new RuntimeException('Sheet pertama tidak ditemukan pada berkas Excel.');
        }

        // Excel menyimpan teks pada sharedStrings; berkas lain memakai inlineStr.
        $teks = [];
        if ($shared !== false) {
            $sst = simplexml_load_string($shared);

            foreach ($sst === false ? [] : $sst->si as $si) {
                $teks[] = self::teks($si);
            }
        }

        $xml = simplexml_load_string($sheet);

        if ($xml === false) {
            throw new RuntimeException('Isi sheet Excel tidak dapat dibaca.');
        }

        $baris = [];

        foreach ($xml->sheetData->row as $row) {
            $sel = [];
            $kolom = 0;

            foreach ($row->c as $c) {
                if (isset($c['r']) && preg_match('/^([A-Z]+)/', (string) $c['r'], $cocok) === 1) {
                    $kolom = self::indeks($cocok[1]);
                }

                $sel[$kolom] = match ((string) $c['t']) {
                    's' => $teks[(int) $c->v] ?? '',
                    'inlineStr' => self::teks($c->is),
                    default => (string) $c->v,
                };

                $kolom++;
            }

            if ($sel !== []) {
                $baris[] = array_values(array_replace(array_fill(0, max(array_keys($sel)) + 1, ''), $sel));
            }
        }

        return $baris;
    }

    /**
     * Teks dalam satu sel: <is><t>…</t></is> atau <si><r><t>…</t></r></si>.
     */
    private static function teks(?SimpleXMLElement $sel): string
    {
        $hasil = '';

        foreach ($sel?->xpath('.//*[local-name()="t"]') ?: [] as $t) {
            $hasil .= (string) $t;
        }

        return $hasil;
    }

    /**
     * "A1" → 0, "AB3" → 27.
     */
    private static function indeks(string $huruf): int
    {
        $nomor = 0;

        foreach (str_split($huruf) as $h) {
            $nomor = ($nomor * 26) + (ord($h) - 64);
        }

        return $nomor - 1;
    }

    private static function referensi(int $kolom, int $baris): string
    {
        $huruf = '';

        for ($i = $kolom + 1; $i > 0; $i = intdiv($i - 1, 26)) {
            $huruf = chr(65 + (($i - 1) % 26)).$huruf;
        }

        return $huruf.$baris;
    }

    /**
     * Escape isi sel untuk XML dan buang karakter kontrol yang tidak diizinkan.
     */
    private static function aman(mixed $nilai): string
    {
        $teks = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $nilai) ?? '';

        return htmlspecialchars($teks, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}