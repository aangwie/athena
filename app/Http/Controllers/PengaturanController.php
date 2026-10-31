<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;

class PengaturanController extends Controller
{
    private const TOKEN_FILE = '.token-gh';

    private const TOKEN_PATTERN = '/^github_pat_[A-Za-z0-9_]{10,255}$/';

    private const MAX_LOG = 30;

    /** Folder penyimpanan sementara berkas backup/import database. */
    private const DB_DIR = 'app/backups';

    /** Baris per pernyataan INSERT pada berkas backup. */
    private const BARIS_INSERT = 200;

    /** Byte berkas SQL yang dieksekusi per langkah import (±2 MB per request). */
    private const BYTE_LANGKAH = 2097152;

    /**
     * Tabel yang dipertahankan saat "hapus seluruh data": akun, konfigurasi web,
     * data master, dan tabel internal Laravel. Sisanya dikosongkan.
     */
    private const TABEL_TETAP = [
        'migrations',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'pengaturan',
        'instansi',
        'prodi',
        'jabatan_fungsional',
        'jabatan_struktural',
        'kepangkatan',
    ];

    public function index(): View
    {
        $token = $this->token();

        return view('pengaturan.index', [
            'namaWeb' => Pengaturan::namaWeb(),
            'tokenSamar' => $token === null ? null : substr($token, 0, 15).'…'.substr($token, -4),
            'storageTertaut' => file_exists(public_path('storage')),
            'cabang' => $this->cabang(),
            'log' => session('log-pengaturan', []),
        ]);
    }

    /**
     * Nama web yang tampil di sidebar, judul halaman, dan footer — maksimal 10 karakter.
     */
    public function simpanNamaWeb(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_web' => ['required', 'string', 'max:10'],
        ], [
            'nama_web.required' => 'Nama web wajib diisi.',
            'nama_web.max' => 'Nama web maksimal 10 karakter.',
        ]);

        Pengaturan::simpanNamaWeb($data['nama_web']);

        Log::info('[pengaturan] Ubah nama web', ['nama' => $data['nama_web'], 'oleh' => auth()->user()->nidn]);

        return redirect()
            ->route('pengaturan.index')
            ->with('status', 'Nama web diperbarui menjadi "'.trim($data['nama_web']).'".');
    }

    public function simpanToken(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token_github' => ['required', 'string', 'regex:'.self::TOKEN_PATTERN],
        ], [
            'token_github.required' => 'Token GitHub wajib diisi.',
            'token_github.regex' => 'Token harus berformat github_patxxxxxxxxx.',
        ]);

        $berkas = base_path(self::TOKEN_FILE);
        file_put_contents($berkas, trim($data['token_github']));
        @chmod($berkas, 0600);

        return $this->catat('Simpan token GitHub', [
            'baris' => ['Token GitHub disimpan pada '.self::TOKEN_FILE.' (permission 0600).'],
            'sukses' => true,
        ]);
    }

    public function update(): RedirectResponse
    {
        $token = $this->token();
        $perintah = ['git', '-c', 'credential.helper='];
        $rahasia = null;

        if ($token !== null) {
            $rahasia = base64_encode('x-access-token:'.$token);
            $perintah[] = '-c';
            $perintah[] = 'http.https://github.com/.extraheader=AUTHORIZATION: basic '.$rahasia;
        }

        $perintah = [...$perintah, 'pull', '--ff-only'];

        return $this->catat('Perbarui aplikasi (git pull)', $this->jalankan($perintah, $rahasia));
    }

    public function clearCache(): RedirectResponse
    {
        return $this->catat('Bersihkan cache', $this->jalankan($this->artisan('optimize:clear')));
    }

    public function migrate(): RedirectResponse
    {
        return $this->catat('Migrasi tabel', $this->jalankan($this->artisan('migrate', '--force')));
    }

    public function storageLink(): RedirectResponse
    {
        return $this->catat('Symlink storage', $this->jalankan($this->artisan('storage:link', '--force')));
    }

    /**
     * Mulai proses backup atau import database.
     *
     * Pekerjaan dipecah menjadi beberapa langkah (dbLangkah) supaya bilah progres di halaman
     * pengaturan bergerak nyata — per tabel untuk backup, per ±2 MB berkas untuk import.
     */
    public function dbMulai(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:backup,import'],
            'berkas' => ['nullable', 'file', 'max:102400'],
        ]);

        $direktori = $this->direktoriDb();
        File::ensureDirectoryExists($direktori);

        if ($data['mode'] === 'import') {
            $berkas = $request->file('berkas');

            if ($berkas === null || strtolower($berkas->getClientOriginalExtension()) !== 'sql') {
                return response()->json(['pesan' => 'Berkas backup harus berformat .sql.'], 422);
            }

            File::delete(File::glob($direktori.DIRECTORY_SEPARATOR.'import-*.sql') ?: []);

            $nama = 'import-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.sql';
            $berkas->move($direktori, $nama);
            $ukuran = (int) filesize($direktori.DIRECTORY_SEPARATOR.$nama);

            session(['db-job' => ['jenis' => 'import', 'berkas' => $nama, 'offset' => 0, 'total' => $ukuran]]);

            return response()->json([
                'persen' => 0,
                'pesan' => 'Berkas diterima ('.$this->ukuranBerkas($ukuran).').',
            ]);
        }

        File::delete(File::glob($direktori.DIRECTORY_SEPARATOR.'backup-*.sql') ?: []);

        // Tanpa nama skema, MySQL mengembalikan tabel seluruh database di server.
        $tabel = collect(DB::getSchemaBuilder()->getTables(DB::getSchemaBuilder()->getCurrentSchemaName()))
            ->pluck('name')
            ->map(fn ($nama) => (string) $nama)
            ->values()
            ->all();

        $nama = 'backup-'.now()->format('Ymd-His').'.sql';
        $kepala = '-- Backup database '.config('database.connections.'.config('database.default').'.database')
            ."\n-- Dibuat: ".now()->toDateTimeString()."\n"
            .($this->mysql() ? "SET FOREIGN_KEY_CHECKS=0;\n" : '')."\n";

        file_put_contents($direktori.DIRECTORY_SEPARATOR.$nama, $kepala);

        session(['db-job' => ['jenis' => 'backup', 'berkas' => $nama, 'tabel' => $tabel, 'posisi' => 0]]);

        return response()->json([
            'persen' => 0,
            'pesan' => 'Menulis '.count($tabel).' tabel…',
        ]);
    }

    /**
     * Kerjakan satu potong pekerjaan database yang sedang berjalan (lihat dbMulai).
     */
    public function dbLangkah(): JsonResponse
    {
        $job = session('db-job');

        if (! is_array($job) || ! isset($job['jenis'], $job['berkas'])) {
            return response()->json(['pesan' => 'Tidak ada proses database yang berjalan.'], 409);
        }

        return $job['jenis'] === 'backup'
            ? $this->langkahBackup($job)
            : $this->langkahImport($job);
    }

    /**
     * Unduh berkas hasil backup. Berkas dibiarkan di storage sampai backup berikutnya
     * menggantinya, supaya tautan "unduh ulang" di halaman tetap berfungsi.
     */
    public function dbUnduh(string $berkas): BinaryFileResponse
    {
        // Hanya berkas hasil backup di folder internal yang boleh diunduh (cegah path traversal).
        abort_unless(basename($berkas) === $berkas && str_starts_with($berkas, 'backup-'), 404);

        $jalur = $this->direktoriDb().DIRECTORY_SEPARATOR.$berkas;

        abort_unless(is_file($jalur), 404, 'Berkas backup tidak ditemukan.');

        return response()->download($jalur);
    }

    public function hapusLog(): RedirectResponse
    {
        session()->forget('log-pengaturan');

        Log::info('[pengaturan] Hapus log terminal', ['oleh' => auth()->user()->nidn]);

        return redirect()
            ->route('pengaturan.index')
            ->with('status', 'Log terminal dibersihkan.');
    }

    /**
     * Kosongkan seluruh data dosen (profil, riwayat, kegiatan, pengajuan, berkas syarat khusus).
     * Akun pengguna, pengaturan web, dan data master tetap disimpan — lihat TABEL_TETAP.
     *
     * Berkas unggahan di storage tidak ikut dihapus; sisa berkas tanpa pemilik dibersihkan manual.
     */
    public function dbKosongkan(): RedirectResponse
    {
        $mysql = $this->mysql();

        if ($mysql) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            $tabel = collect(DB::getSchemaBuilder()->getTables(DB::getSchemaBuilder()->getCurrentSchemaName()))
                ->pluck('name')
                ->map(fn ($nama) => (string) $nama)
                ->reject(fn (string $nama) => in_array($nama, self::TABEL_TETAP, true))
                ->values()
                ->all();

            $baris = [];

            foreach ($tabel as $nama) {
                $baris[] = $nama.': '.DB::table($nama)->delete().' baris dihapus';
            }
        } catch (Throwable $exception) {
            return $this->catat('Hapus seluruh data', [
                'baris' => [...($baris ?? []), $exception->getMessage()],
                'sukses' => false,
            ]);
        } finally {
            if ($mysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return $this->catat('Hapus seluruh data', [
            'baris' => [
                ...$baris,
                count($tabel).' tabel dikosongkan; users, pengaturan, dan data master dipertahankan.',
            ],
            'sukses' => true,
        ]);
    }

    /**
     * Jalankan perintah lalu kembalikan baris keluaran untuk panel terminal.
     *
     * @param  array<int, string>  $perintah
     * @return array{baris: array<int, string>, sukses: bool}
     */
    public function jalankan(array $perintah, ?string $rahasia = null): array
    {
        $baris = ['$ '.$this->samarkan(implode(' ', $perintah), $rahasia)];
        $kode = null;

        try {
            $process = new Process($perintah, base_path(), ['GIT_TERMINAL_PROMPT' => '0'], null, 300);
            $process->run();

            $kode = $process->getExitCode();
            $keluaran = trim($process->getOutput().$process->getErrorOutput());
            $sukses = $process->isSuccessful();
        } catch (Throwable $exception) {
            $keluaran = $exception->getMessage();
            $sukses = false;
        }

        $baris[] = $this->samarkan($keluaran === '' ? '(tidak ada keluaran)' : $keluaran, $rahasia);
        $baris = array_map(fn ($teks) => preg_replace('/\x1b\[[0-9;]*m/', '', $teks), $baris);
        $baris[] = 'Exit code: '.($kode ?? '-').' · '.($sukses ? 'berhasil' : 'gagal');

        return ['baris' => $baris, 'sukses' => $sukses];
    }

    /**
     * Tampilkan hasil tindakan pada panel terminal (session) sekaligus tulis ke storage/logs/laravel.log.
     *
     * @param  array{baris: array<int, string>, sukses: bool}  $hasil
     */
    private function catat(string $judul, array $hasil): RedirectResponse
    {
        $entri = [
            'waktu' => now()->format('Y-m-d H:i:s'),
            'judul' => $judul,
            'sukses' => $hasil['sukses'],
            'baris' => $hasil['baris'],
        ];

        session(['log-pengaturan' => array_slice([$entri, ...session('log-pengaturan', [])], 0, self::MAX_LOG)]);

        Log::info('[pengaturan] '.$judul, ['sukses' => $hasil['sukses'], 'keluaran' => $hasil['baris']]);

        return redirect()
            ->route('pengaturan.index')
            ->with(
                $hasil['sukses'] ? 'status' : 'error',
                $judul.($hasil['sukses'] ? ' berhasil dijalankan.' : ' gagal dijalankan — periksa terminal log.'),
            );
    }

    /**
     * @return array<int, string>
     */
    private function artisan(string ...$argumen): array
    {
        return [PHP_BINARY, base_path('artisan'), ...$argumen];
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function langkahBackup(array $job): JsonResponse
    {
        $jalur = $this->direktoriDb().DIRECTORY_SEPARATOR.$job['berkas'];
        $tabel = is_array($job['tabel'] ?? null) ? array_values($job['tabel']) : [];
        $total = count($tabel);
        $posisi = (int) ($job['posisi'] ?? 0);
        $pesan = 'Menutup berkas backup…';

        if ($posisi < $total) {
            file_put_contents($jalur, $this->dumpTabel((string) $tabel[$posisi]), FILE_APPEND);
            $pesan = 'Menulis tabel '.$tabel[$posisi].' ('.($posisi + 1).'/'.$total.')…';
            $posisi++;
        }

        if ($posisi >= $total) {
            file_put_contents($jalur, $this->mysql() ? "SET FOREIGN_KEY_CHECKS=1;\n" : '', FILE_APPEND);
            session()->forget('db-job');

            $ukuran = (int) filesize($jalur);
            Log::info('[pengaturan] Backup database', ['berkas' => $job['berkas'], 'tabel' => $total, 'ukuran' => $ukuran]);

            return response()->json([
                'selesai' => true,
                'persen' => 100,
                'pesan' => 'Backup selesai — '.$total.' tabel, '.$this->ukuranBerkas($ukuran).'.',
                'unduh' => route('pengaturan.db.unduh', ['berkas' => $job['berkas']]),
            ]);
        }

        session(['db-job' => [...$job, 'posisi' => $posisi]]);

        return response()->json([
            'selesai' => false,
            'persen' => (int) floor($posisi / $total * 100),
            'pesan' => $pesan,
        ]);
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function langkahImport(array $job): JsonResponse
    {
        $jalur = $this->direktoriDb().DIRECTORY_SEPARATOR.$job['berkas'];
        $ukuran = (int) ($job['total'] ?? 0);
        $offset = (int) ($job['offset'] ?? 0);

        [$pernyataan, $offsetBaru] = $this->bacaPernyataan($jalur, $offset);

        // Potongan tanpa pernyataan (hanya komentar/spasi) tetap dimajukan agar tidak berulang.
        if ($offsetBaru <= $offset) {
            $offsetBaru = min($ukuran, $offset + self::BYTE_LANGKAH);
        }

        if ($this->mysql()) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        try {
            foreach ($pernyataan as $sql) {
                DB::unprepared($sql);
            }
        } catch (Throwable $exception) {
            session()->forget('db-job');
            Log::error('[pengaturan] Import database gagal', ['berkas' => $job['berkas'], 'pesan' => $exception->getMessage()]);

            return response()->json(['pesan' => 'Import berhenti: '.$exception->getMessage()], 422);
        }

        if ($this->mysql()) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        if ($offsetBaru >= $ukuran) {
            File::delete($jalur);
            session()->forget('db-job');
            Log::info('[pengaturan] Import database', ['berkas' => $job['berkas'], 'ukuran' => $ukuran]);

            return response()->json([
                'selesai' => true,
                'persen' => 100,
                'pesan' => 'Import selesai — '.$this->ukuranBerkas($ukuran).' dipulihkan.',
            ]);
        }

        session(['db-job' => [...$job, 'offset' => $offsetBaru]]);

        $persen = $ukuran === 0 ? 100 : (int) floor(min($offsetBaru, $ukuran) / $ukuran * 100);

        return response()->json([
            'selesai' => false,
            'persen' => $persen,
            'pesan' => 'Memulihkan data… '.$persen.'%',
        ]);
    }

    /**
     * Potongan SQL satu tabel: DROP, CREATE TABLE, lalu INSERT per 200 baris.
     */
    private function dumpTabel(string $tabel): string
    {
        $grammar = DB::getQueryGrammar();
        $tabelSql = $grammar->wrapTable($tabel);
        $kolom = collect(DB::getSchemaBuilder()->getColumns($tabel))
            ->pluck('name')
            ->map(fn ($nama) => (string) $nama)
            ->values()
            ->all();

        $sql = 'DROP TABLE IF EXISTS '.$tabelSql.";\n"
            .rtrim($this->createTable($tabel), "\n;").";\n";

        if ($kolom === []) {
            return $sql;
        }

        $daftarKolom = implode(', ', array_map(fn ($nama) => $grammar->wrap($nama), $kolom));
        $kueri = DB::table($tabel);
        $kunci = $this->kunciUtama($tabel);

        if ($kunci !== null) {
            $kueri->orderBy($kunci);
        }

        $pdo = DB::connection()->getPdo();
        $nilai = [];

        $tulis = function () use (&$nilai, &$sql, $tabelSql, $daftarKolom): void {
            if ($nilai !== []) {
                $sql .= "INSERT INTO {$tabelSql} ({$daftarKolom}) VALUES ".implode(",\n", $nilai).";\n";
                $nilai = [];
            }
        };

        // Satu tabel per langkah; untuk tabel raksasa pecah lagi per 200 baris bila perlu.
        foreach ($kueri->lazy(self::BARIS_INSERT) as $baris) {
            $data = (array) $baris;
            $nilai[] = '('.implode(', ', array_map(fn ($nama) => $this->nilaiSql($data[$nama] ?? null, $pdo), $kolom)).')';

            if (count($nilai) === self::BARIS_INSERT) {
                $tulis();
            }
        }

        $tulis();

        return $sql;
    }

    private function createTable(string $tabel): string
    {
        $db = DB::connection();

        if ($db->getDriverName() === 'sqlite') {
            $baris = $db->selectOne("select sql from sqlite_master where type = 'table' and name = ?", [$tabel]);

            return (string) ($baris?->sql ?? '');
        }

        $baris = (array) $db->selectOne('SHOW CREATE TABLE '.$db->getQueryGrammar()->wrapTable($tabel));

        return (string) (array_values($baris)[1] ?? '');
    }

    private function nilaiSql(mixed $nilai, \PDO $pdo): string
    {
        return match (true) {
            $nilai === null => 'NULL',
            is_bool($nilai) => $nilai ? '1' : '0',
            is_int($nilai), is_float($nilai) => (string) $nilai,
            default => $pdo->quote((string) $nilai),
        };
    }

    private function kunciUtama(string $tabel): ?string
    {
        $indeks = collect(DB::getSchemaBuilder()->getIndexes($tabel))->firstWhere('primary', true);

        if (! is_array($indeks) || empty($indeks['columns'])) {
            return null;
        }

        $kolom = is_array($indeks['columns']) ? implode(',', $indeks['columns']) : (string) $indeks['columns'];

        return trim(explode(',', $kolom)[0]) ?: null;
    }

    /**
     * Baca sepotong berkas SQL dari $offset dan pisahkan menjadi pernyataan utuh.
     * Titik koma di dalam string, identifier, atau komentar tidak mengakhiri pernyataan.
     *
     * @return array{0: array<int, string>, 1: int} [pernyataan, offset berikutnya]
     */
    private function bacaPernyataan(string $berkas, int $offset): array
    {
        $handle = fopen($berkas, 'rb');

        if ($handle === false) {
            return [[], $offset + self::BYTE_LANGKAH];
        }

        fseek($handle, $offset);

        $pernyataan = [];
        $buffer = '';
        $kutip = null;
        $komentarBlok = false;
        $posisi = $offset;
        $batas = $offset;

        while ($posisi - $offset < self::BYTE_LANGKAH && ($baris = fgets($handle)) !== false) {
            $posisi += strlen($baris);
            $rapat = ltrim($baris);

            if ($komentarBlok) {
                $komentarBlok = ! str_contains($baris, '*/');

                continue;
            }

            if ($kutip === null && ($rapat === '' || str_starts_with($rapat, '--') || str_starts_with($rapat, '#'))) {
                continue;
            }

            if ($kutip === null && str_starts_with($rapat, '/*')) {
                $komentarBlok = ! str_contains($baris, '*/');

                continue;
            }

            $panjang = strlen($baris);

            for ($i = 0; $i < $panjang; $i++) {
                $c = $baris[$i];
                $buffer .= $c;

                if ($kutip !== null) {
                    if ($c === '\\' && $i + 1 < $panjang) {
                        $buffer .= $baris[++$i];
                    } elseif ($c === $kutip) {
                        $kutip = null;
                    }

                    continue;
                }

                if ($c === "'" || $c === '"' || $c === '`') {
                    $kutip = $c;
                } elseif ($c === ';') {
                    $pernyataan[] = trim($buffer);
                    $buffer = '';
                }
            }

            // Offset hanya boleh maju saat tidak ada pernyataan setengah jadi di buffer.
            if (trim($buffer) === '') {
                $buffer = '';
                $batas = $posisi;
            }
        }

        $eof = feof($handle);
        fclose($handle);

        if ($eof && trim($buffer) !== '') {
            $pernyataan[] = trim($buffer);
            $batas = $posisi;
        }

        return [$pernyataan, $batas];
    }

    private function direktoriDb(): string
    {
        return storage_path(self::DB_DIR);
    }

    private function mysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    private function ukuranBerkas(int $byte): string
    {
        return match (true) {
            $byte >= 1048576 => round($byte / 1048576, 1).' MB',
            $byte >= 1024 => round($byte / 1024, 1).' KB',
            default => $byte.' byte',
        };
    }

    private function token(): ?string
    {
        $berkas = base_path(self::TOKEN_FILE);

        if (! is_file($berkas)) {
            return null;
        }

        $token = trim((string) file_get_contents($berkas));

        return preg_match(self::TOKEN_PATTERN, $token) === 1 ? $token : null;
    }

    private function cabang(): string
    {
        $head = base_path('.git/HEAD');

        if (! is_file($head)) {
            return '-';
        }

        return trim(str_replace('ref: refs/heads/', '', (string) file_get_contents($head))) ?: '-';
    }

    private function samarkan(string $teks, ?string $rahasia): string
    {
        return $rahasia === null || $rahasia === '' ? $teks : str_replace($rahasia, '***', $teks);
    }
}
