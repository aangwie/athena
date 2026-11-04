<?php

namespace App\Support;

use App\Models\Pengaturan;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Klien tipis untuk WhatsApp gateway berbasis REST.
 *
 * Gateway yang didukung cukup menyediakan tiga jenis endpoint di bawah URL yang
 * diisi admin (lihat JALUR_*). Beberapa variasi nama jalur umum dicoba otomatis,
 * lalu jalur yang berhasil diingat selama satu jam agar polling status tetap ringan.
 */
class WhatsAppGateway
{
    /** Batas waktu satu permintaan ke gateway (detik). */
    private const TIMEOUT = 6;

    private const CONNECT_TIMEOUT = 4;

    /** Lama jalur endpoint yang berhasil diingat (detik). */
    private const CACHE_TTL = 3600;

    /** Panjang maksimum isi respons mentah yang dikirim ke halaman. */
    private const MAX_RESPONS = 5000;

    /** Nama sesi/device default untuk gateway multi-sesi (mis. /status/default). */
    private const SESI_DEFAULT = 'default';

    /**
     * Kandidat jalur status koneksi WhatsApp. Penanda %s diganti nama sesi.
     * Jalur bersesi dicoba lebih dulu karena gateway multi-sesi (Baileys)
     * biasanya memakai /status/{sesi}, sedangkan gateway single-tenant
     * memakai /status.
     */
    private const JALUR_STATUS = [
        'status/%s',
        'status',
        'api/status',
        'connection/status',
        'api/connection/status',
        'session/status',
        'status-session',
        'health',
        '',
    ];

    /**
     * Kandidat jalur QR code. Penting: jangan memakai jalur seperti "status/qr"
     * karena pada gateway dengan rute generik /status/:sesi jalur tersebut ikut
     * cocok dan mengembalikan status (bukan QR), lalu sempat tersimpan sebagai
     * endpoint QR yang salah.
     */
    private const JALUR_QR = [
        'qr/%s',
        'qr',
        'api/qr',
        'qrcode/%s',
        'qrcode',
        'instance/qr/%s',
        'instance/qr',
        'session/qr',
        'qr-code',
        'api/qr-code',
    ];

    /** Kandidat jalur pengiriman pesan. */
    private const JALUR_KIRIM = [
        'send-message',
        'api/send-message',
        'send',
        'api/send',
        'messages/send',
        'api/messages/send',
        'send-text',
    ];

    public function __construct(
        private readonly ?string $url,
        private readonly string $sesi = self::SESI_DEFAULT,
    ) {}

    /**
     * Gateway yang URL-nya sedang tersimpan di pengaturan.
     */
    public static function aktif(): self
    {
        return new self(Pengaturan::whatsappGatewayUrl());
    }

    public function terkonfigurasi(): bool
    {
        return filled($this->url);
    }

    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Cek apakah host gateway dapat dijangkau dan apakah perangkat WhatsApp
     * pada gateway sedang terhubung.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        if (! $this->terkonfigurasi()) {
            return $this->hasilKosong('URL gateway belum diatur.');
        }

        $hasil = $this->probe(self::JALUR_STATUS, 'status', function (Response $response): bool {
            return $this->json($response) !== null;
        });

        if ($hasil['response'] === null) {
            return [
                'terkonfigurasi' => true,
                'gateway' => [
                    'online' => false,
                    'kompatibel' => false,
                    'http_status' => null,
                    'latency_ms' => $hasil['latency_ms'],
                    'pesan' => $hasil['error'] ?? 'Gateway tidak dapat dihubungi.',
                    'endpoint' => $hasil['url'],
                ],
                'whatsapp' => [
                    'terhubung' => false,
                    'diketahui' => false,
                    'status' => 'tidak_terhubung',
                    'nomor' => null,
                    'nama' => null,
                ],
                'respon' => null,
                'diperiksa_pada' => now()->toIso8601String(),
            ];
        }

        $response = $hasil['response'];
        $json = $this->json($response);
        $whatsapp = $this->normalisasiStatus($json ?? $response->body());

        return [
            'terkonfigurasi' => true,
            'gateway' => [
                'online' => true,
                'kompatibel' => $response->successful() && $json !== null,
                'http_status' => $response->status(),
                'latency_ms' => $hasil['latency_ms'],
                'pesan' => $response->successful()
                    ? 'Gateway merespons.'
                    : 'Gateway merespons dengan status HTTP '.$response->status().'.',
                'endpoint' => $hasil['url'],
            ],
            'whatsapp' => $whatsapp,
            'respon' => $this->responRingkas($response, $json),
            'diperiksa_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * Ambil QR code terbaru dari gateway. QR hanya tersedia saat perangkat
     * WhatsApp belum terhubung (atau sesi baru dimulai).
     *
     * @return array<string, mixed>
     */
    public function qr(): array
    {
        if (! $this->terkonfigurasi()) {
            return [
                'terkonfigurasi' => false,
                'tersedia' => false,
                'terhubung' => false,
                'qr' => null,
                'qr_teks' => null,
                'pesan' => 'URL gateway belum diatur.',
                'endpoint' => null,
                'http_status' => null,
                'diperiksa_pada' => now()->toIso8601String(),
            ];
        }

        $hasil = $this->probe(self::JALUR_QR, 'qr', function (Response $response): bool {
            [$gambar, $teks] = $this->ekstrakQr($response);

            return $gambar !== null || $teks !== null;
        });

        if ($hasil['response'] === null) {
            return [
                'terkonfigurasi' => true,
                'tersedia' => false,
                'terhubung' => false,
                'qr' => null,
                'qr_teks' => null,
                'pesan' => $hasil['error'] ?? 'QR code belum tersedia.',
                'endpoint' => $hasil['url'],
                'http_status' => null,
                'diperiksa_pada' => now()->toIso8601String(),
            ];
        }

        $response = $hasil['response'];
        [$gambar, $teks] = $this->ekstrakQr($response);

        return [
            'terkonfigurasi' => true,
            'tersedia' => $gambar !== null || $teks !== null,
            'terhubung' => false,
            'qr' => $gambar,
            'qr_teks' => $teks,
            'pesan' => $gambar !== null
                ? 'Pindai QR code memakai WhatsApp pada ponsel.'
                : ($teks !== null
                    ? 'QR code dibuat dari data teks yang dikirim gateway.'
                    : 'Gateway tidak mengirim QR code yang dapat ditampilkan. Buka tautan QR di gateway untuk memindainya.'),
            'endpoint' => $hasil['url'],
            'http_status' => $response->status(),
            'diperiksa_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * Kirim pesan uji melalui gateway.
     *
     * @return array<string, mixed>
     */
    public function kirim(string $nomor, string $pesan): array
    {
        if (! $this->terkonfigurasi()) {
            return [
                'terkonfigurasi' => false,
                'sukses' => false,
                'pesan' => 'URL gateway belum diatur.',
                'nomor' => $this->normalisasiNomor($nomor),
                'http_status' => null,
                'durasi_ms' => null,
                'endpoint' => null,
                'respon' => null,
            ];
        }

        $nomor = $this->normalisasiNomor($nomor);
        $mulai = hrtime(true);

        // Beberapa gateway memakai nama kunci yang berbeda; kirim beberapa alias
        // sekaligus agar satu payload dapat diterima berbagai implementasi.
        // device_id/nomor_penerima/pesan dipakai gateway Baileys kustom
        // (mis. billnesia), sedangkan number/message dipakai gateway umum.
        $payload = [
            'device_id' => $this->sesi,
            'number' => $nomor,
            'phone' => $nomor,
            'target' => $nomor,
            'to' => $nomor,
            'nomor_penerima' => $nomor,
            'message' => $pesan,
            'pesan' => $pesan,
            'text' => $pesan,
        ];

        $error = null;

        foreach (self::JALUR_KIRIM as $jalur) {
            $url = $this->gabungUrl($jalur);

            try {
                $response = $this->request()->asJson()->post($url, $payload);

                // Sebagian gateway hanya membaca form-urlencoded, bukan JSON.
                if (in_array($response->status(), [400, 415], true)) {
                    $response = $this->request()->asForm()->post($url, $payload);
                }
            } catch (Throwable $exception) {
                $error = 'Tidak dapat terhubung ke gateway: '.$exception->getMessage();

                break;
            }

            if (in_array($response->status(), [404, 405], true)) {
                continue;
            }

            Cache::put($this->kunciCache('kirim'), $jalur, self::CACHE_TTL);

            $json = $this->json($response);

            return [
                'terkonfigurasi' => true,
                'sukses' => $this->suksesKirim($response, $json),
                'pesan' => $this->pesanKirim($response, $json),
                'nomor' => $nomor,
                'http_status' => $response->status(),
                'durasi_ms' => $this->durasi($mulai),
                'endpoint' => $url,
                'respon' => $this->responRingkas($response, $json),
            ];
        }

        return [
            'terkonfigurasi' => true,
            'sukses' => false,
            'pesan' => $error ?? 'Endpoint pengiriman tidak ditemukan pada gateway (404).',
            'nomor' => $nomor,
            'http_status' => null,
            'durasi_ms' => $this->durasi($mulai),
            'endpoint' => null,
            'respon' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hasilKosong(string $pesan): array
    {
        return [
            'terkonfigurasi' => false,
            'gateway' => [
                'online' => false,
                'kompatibel' => false,
                'http_status' => null,
                'latency_ms' => null,
                'pesan' => $pesan,
                'endpoint' => null,
            ],
            'whatsapp' => [
                'terhubung' => false,
                'diketahui' => false,
                'status' => 'belum_dikonfigurasi',
                'nomor' => null,
                'nama' => null,
            ],
            'respon' => null,
            'diperiksa_pada' => now()->toIso8601String(),
        ];
    }

    /**
     * Coba beberapa kandidat jalur secara berurutan. Pencarian berhenti saat
     * host tidak dapat dihubungi, tetapi lanjut ke jalur berikutnya bila hanya
     * mendapat 404/405.
     *
     * Bila $terima diberikan, respons 200 yang tidak lolos pemeriksaan dianggap
     * tidak cocok dan pencarian lanjut. Hal ini mencegah endpoint yang kebetulan
     * membalas 200 (mis. rute generik) tersimpan sebagai endpoint yang salah.
     *
     * @param  array<int, string>  $jalur
     * @param  (callable(Response): bool)|null  $terima
     * @return array{response: Response|null, url: string|null, latency_ms: int, error: string|null}
     */
    private function probe(array $jalur, string $jenis, ?callable $terima = null): array
    {
        $mulai = hrtime(true);
        $tersimpan = Cache::get($this->kunciCache($jenis));
        $daftar = filled($tersimpan)
            ? array_values(array_unique([$tersimpan, ...$jalur]))
            : $jalur;
        $error = null;
        $cadangan = null;

        foreach ($daftar as $pola) {
            $jalurAktif = $this->terapkanSesi($pola);
            $url = $this->gabungUrl($jalurAktif);

            try {
                $response = $this->request()->get($url);
            } catch (Throwable $exception) {
                $error = 'Tidak dapat terhubung ke gateway: '.$exception->getMessage();

                break;
            }

            if (in_array($response->status(), [404, 405], true)) {
                continue;
            }

            // Simpan sebagai cadangan agar status tetap bisa dilaporkan saat
            // tidak ada endpoint yang benar-benar cocok.
            $cadangan = [
                'response' => $response,
                'url' => $url,
                'latency_ms' => $this->durasi($mulai),
                'error' => null,
            ];

            if ($terima !== null && ! $terima($response)) {
                continue;
            }

            if ($response->successful()) {
                Cache::put($this->kunciCache($jenis), $pola, self::CACHE_TTL);
            }

            return [
                'response' => $response,
                'url' => $url,
                'latency_ms' => $this->durasi($mulai),
                'error' => null,
            ];
        }

        if ($cadangan !== null) {
            return $cadangan;
        }

        return [
            'response' => null,
            'url' => null,
            'latency_ms' => $this->durasi($mulai),
            'error' => $error ?? 'Endpoint tidak ditemukan pada gateway (404).',
        ];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::TIMEOUT)
            ->withHeaders(['User-Agent' => 'Athena-WhatsApp-Gateway/1.0']);
    }

    private function gabungUrl(string $jalur): string
    {
        return rtrim((string) $this->url, '/').($jalur === '' ? '' : '/'.ltrim($jalur, '/'));
    }

    /**
     * Ganti penanda %s pada pola jalur dengan nama sesi gateway.
     */
    private function terapkanSesi(string $pola): string
    {
        return str_contains($pola, '%s') ? sprintf($pola, $this->sesi) : $pola;
    }

    private function kunciCache(string $jenis): string
    {
        return 'wa-gateway:'.$jenis.':'.md5((string) $this->url);
    }

    /**
     * @return array<mixed>|null
     */
    private function json(Response $response): ?array
    {
        try {
            $data = $response->json();
        } catch (Throwable) {
            return null;
        }

        return is_array($data) ? $data : null;
    }

    /**
     * @return array{terhubung: bool, diketahui: bool, status: string, nomor: string|null, nama: string|null}
     */
    private function normalisasiStatus(mixed $data): array
    {
        $bendera = $this->cariNilai($data, [
            'connected', 'isConnected', 'connect', 'ready', 'isReady',
            'loggedIn', 'isLoggedIn', 'authenticated', 'active', 'online',
        ]);

        $teksStatus = $this->cariNilai($data, [
            'status', 'state', 'connection', 'connectionStatus', 'session', 'message',
        ]);

        $terhubung = $this->keBool($bendera);

        if ($terhubung === null && is_string($teksStatus)) {
            $terhubung = $this->statusTeksKeBool($teksStatus);
        }

        $nomor = $this->cariNilai($data, ['number', 'phone', 'nomor', 'phoneNumber', 'wid', 'user.id', 'me.id']);
        $nama = $this->cariNilai($data, ['pushname', 'pushName', 'name', 'user.name', 'me.name', 'profileName']);

        $status = match (true) {
            $terhubung === null => 'tidak_diketahui',
            $terhubung === true => 'terhubung',
            is_string($teksStatus) && preg_match('/pending|connecting|start|qr|pair|scan|wait/i', $teksStatus) === 1 => 'menunggu',
            default => 'terputus',
        };

        return [
            'terhubung' => $terhubung ?? false,
            'diketahui' => $terhubung !== null,
            'status' => $status,
            'nomor' => is_scalar($nomor) && filled((string) $nomor) ? (string) $nomor : null,
            'nama' => is_scalar($nama) && filled((string) $nama) ? (string) $nama : null,
        ];
    }

    private function keBool(mixed $nilai): ?bool
    {
        if (is_bool($nilai)) {
            return $nilai;
        }

        if (is_int($nilai) || is_float($nilai)) {
            return (int) $nilai === 1;
        }

        if (! is_string($nilai)) {
            return null;
        }

        return match (strtolower(trim($nilai))) {
            'true', '1', 'yes', 'ya', 'connected', 'open', 'ready', 'online', 'aktif', 'terhubung', 'authenticated' => true,
            'false', '0', 'no', 'tidak', 'disconnected', 'close', 'closed', 'offline', 'qr', 'pairing', 'loading', 'pending', 'connecting', 'starting', 'waiting', 'scan', 'terputus' => false,
            default => null,
        };
    }

    private function statusTeksKeBool(string $status): ?bool
    {
        $status = strtolower(trim($status));

        return match (true) {
            $status === '' => null,
            str_contains($status, 'disconnect'),
            str_contains($status, 'not connect'),
            str_contains($status, 'not_con'),
            str_contains($status, 'offline'),
            str_contains($status, 'qr'),
            str_contains($status, 'pair'),
            str_contains($status, 'loading'),
            str_contains($status, 'pending'),
            str_contains($status, 'connecting'),
            str_contains($status, 'starting'),
            str_contains($status, 'waiting'),
            str_contains($status, 'scan'),
            str_contains($status, 'close') => false,
            str_contains($status, 'connect'),
            str_contains($status, 'open'),
            str_contains($status, 'ready'),
            str_contains($status, 'authenticated'),
            str_contains($status, 'online'),
            str_contains($status, 'terhubung') => true,
            default => $this->keBool($status),
        };
    }

    /**
     * Cari nilai pertama dari daftar kunci, termasuk pada array bersarang.
     * Kunci bertitik (mis. "user.id") juga didukung.
     */
    private function cariNilai(mixed $data, array $kunci): mixed
    {
        if (! is_array($data)) {
            return null;
        }

        foreach ($kunci as $kunciAktif) {
            if (array_key_exists($kunciAktif, $data)) {
                return $data[$kunciAktif];
            }

            if (! str_contains($kunciAktif, '.')) {
                continue;
            }

            $nilai = $data;

            foreach (explode('.', $kunciAktif) as $bagian) {
                if (! is_array($nilai) || ! array_key_exists($bagian, $nilai)) {
                    $nilai = null;

                    break;
                }

                $nilai = $nilai[$bagian];
            }

            if ($nilai !== null) {
                return $nilai;
            }
        }

        foreach ($data as $nilai) {
            if (! is_array($nilai)) {
                continue;
            }

            $hasil = $this->cariNilai($nilai, $kunci);

            if ($hasil !== null) {
                return $hasil;
            }
        }

        return null;
    }

    private function gambarDariRespons(Response $response, ?array $json): ?string
    {
        $tipe = strtolower((string) $response->header('Content-Type'));

        if (str_starts_with($tipe, 'image/')) {
            return 'data:'.$tipe.';base64,'.base64_encode($response->body());
        }

        return $this->cariGambar($json ?? $response->body());
    }

    /**
     * Ambil QR dari respons gateway, baik berupa gambar maupun teks mentah.
     *
     * @return array{0: string|null, 1: string|null} [gambar, teks]
     */
    private function ekstrakQr(Response $response): array
    {
        $json = $this->json($response);
        $gambar = $this->gambarDariRespons($response, $json);
        $teks = $gambar === null ? $this->cariTeksQr($json ?? $response->body()) : null;

        return [$gambar, $teks];
    }

    private function cariGambar(mixed $data, int $level = 0): ?string
    {
        if ($level > 5) {
            return null;
        }

        if (is_string($data)) {
            return $this->stringGambar($data);
        }

        if (! is_array($data)) {
            return null;
        }

        $prioritas = [
            'qr', 'qrcode', 'qr_code', 'qrCode', 'qrimage', 'qr_image',
            'image', 'img', 'base64', 'base64Qr', 'data', 'result', 'message',
        ];

        foreach ($prioritas as $kunci) {
            if (! array_key_exists($kunci, $data)) {
                continue;
            }

            $hasil = $this->cariGambar($data[$kunci], $level + 1);

            if ($hasil !== null) {
                return $hasil;
            }
        }

        foreach ($data as $nilai) {
            $hasil = $this->cariGambar($nilai, $level + 1);

            if ($hasil !== null) {
                return $hasil;
            }
        }

        return null;
    }

    private function stringGambar(string $nilai): ?string
    {
        $nilai = trim($nilai);

        if ($nilai === '') {
            return null;
        }

        if (str_starts_with($nilai, 'data:image/')) {
            return $nilai;
        }

        if (preg_match('#^https?://\S+$#i', $nilai) === 1) {
            return $nilai;
        }

        if (preg_match('#^<svg[\s>]#i', $nilai) === 1) {
            return 'data:image/svg+xml;base64,'.base64_encode($nilai);
        }

        $bersih = preg_replace('/\s+/', '', $nilai) ?? '';

        if (strlen($bersih) >= 200 && preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $bersih) === 1) {
            $mime = str_starts_with($bersih, '/9j/') ? 'image/jpeg' : 'image/png';

            return 'data:'.$mime.';base64,'.$bersih;
        }

        return null;
    }

    /**
     * Sebagian gateway (mis. Baileys) mengirim QR sebagai teks mentah, bukan
     * gambar. Teks itu dikembalikan agar browser dapat menggambarnya menjadi QR.
     */
    private function cariTeksQr(mixed $data, int $level = 0): ?string
    {
        if ($level > 5) {
            return null;
        }

        if (is_string($data)) {
            return $this->stringTeksQr($data, false);
        }

        if (! is_array($data)) {
            return null;
        }

        foreach (['qr', 'qrcode', 'qr_code', 'qrCode', 'qrText', 'qr_text', 'code'] as $kunci) {
            if (! array_key_exists($kunci, $data)) {
                continue;
            }

            $nilai = $data[$kunci];

            if (is_string($nilai)) {
                $hasil = $this->stringTeksQr($nilai, true);

                if ($hasil !== null) {
                    return $hasil;
                }
            }
        }

        foreach (['data', 'result'] as $kunci) {
            if (array_key_exists($kunci, $data) && is_array($data[$kunci])) {
                $hasil = $this->cariTeksQr($data[$kunci], $level + 1);

                if ($hasil !== null) {
                    return $hasil;
                }
            }
        }

        return null;
    }

    private function stringTeksQr(string $nilai, bool $dariJson): ?string
    {
        $nilai = trim($nilai);

        if ($nilai === ''
            || str_starts_with(strtolower($nilai), 'data:image/')
            || preg_match('#^https?://#i', $nilai) === 1
            || preg_match('#^<svg[\s>]#i', $nilai) === 1) {
            return null;
        }

        // Pola QR teks Baileys: "2@..." atau "1@...".
        if (preg_match('/^\d+@/', $nilai) === 1) {
            return $nilai;
        }

        if (! $dariJson || strlen($nilai) < 50 || preg_match('/\s/', $nilai) === 1) {
            return null;
        }

        // Jangan salah menganggap string base64 gambar sebagai QR teks.
        if (strlen($nilai) >= 200 && preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $nilai) === 1) {
            return null;
        }

        return $nilai;
    }

    private function suksesKirim(Response $response, ?array $json): bool
    {
        if (! $response->successful()) {
            return false;
        }

        if ($json === null) {
            return true;
        }

        foreach (['success', 'ok', 'sent', 'status'] as $kunci) {
            if (! array_key_exists($kunci, $json)) {
                continue;
            }

            $nilai = $json[$kunci];

            if (is_bool($nilai)) {
                return $nilai;
            }

            if (is_numeric($nilai)) {
                return (int) $nilai < 400;
            }

            if (is_string($nilai)) {
                $teks = strtolower(trim($nilai));

                if (in_array($teks, ['false', 'error', 'failed', 'gagal', 'invalid'], true)) {
                    return false;
                }

                if (in_array($teks, ['true', 'success', 'sent', 'ok', 'queued', 'pending', 'delivered'], true)) {
                    return true;
                }
            }
        }

        if (isset($json['error']) && filled($json['error'])) {
            return false;
        }

        return true;
    }

    private function pesanKirim(Response $response, ?array $json): string
    {
        if (! $response->successful()) {
            return 'Gateway menolak permintaan (HTTP '.$response->status().').';
        }

        $pesan = $this->cariNilai($json, ['message', 'msg', 'pesan', 'info', 'status']);

        return is_scalar($pesan) && filled((string) $pesan)
            ? (string) $pesan
            : 'Permintaan diterima gateway.';
    }

    /**
     * Ringkas respons agar aman dikirim ke browser (JSON terstruktur atau teks).
     */
    private function responRingkas(Response $response, ?array $json): mixed
    {
        if ($json !== null) {
            return $json;
        }

        return Str::limit($response->body(), self::MAX_RESPONS);
    }

    private function normalisasiNomor(string $nomor): string
    {
        $nomor = preg_replace('/\D+/', '', $nomor) ?? '';

        return match (true) {
            str_starts_with($nomor, '0') => '62'.substr($nomor, 1),
            str_starts_with($nomor, '8') => '62'.$nomor,
            default => $nomor,
        };
    }

    private function durasi(int $mulai): int
    {
        return (int) round((hrtime(true) - $mulai) / 1_000_000);
    }
}
