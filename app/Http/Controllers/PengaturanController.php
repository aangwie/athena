<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\Process\Process;
use Throwable;

class PengaturanController extends Controller
{
    private const TOKEN_FILE = '.token-gh';

    private const TOKEN_PATTERN = '/^github_pat_[A-Za-z0-9_]{10,255}$/';

    private const MAX_LOG = 30;

    public function index(): View
    {
        $token = $this->token();

        return view('pengaturan.index', [
            'tokenSamar' => $token === null ? null : substr($token, 0, 15).'…'.substr($token, -4),
            'storageTertaut' => file_exists(public_path('storage')),
            'cabang' => $this->cabang(),
            'log' => session('log-pengaturan', []),
        ]);
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

    public function hapusLog(): RedirectResponse
    {
        session()->forget('log-pengaturan');

        Log::info('[pengaturan] Hapus log terminal', ['oleh' => auth()->user()->nidn]);

        return redirect()
            ->route('pengaturan.index')
            ->with('status', 'Log terminal dibersihkan.');
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
