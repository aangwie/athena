<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use App\Support\WhatsAppGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    public function index(): View
    {
        return view('whatsapp.index', [
            'gatewayUrl' => Pengaturan::whatsappGatewayUrl(),
            'sesi' => WhatsAppGateway::sesiTerpasang(),
        ]);
    }

    /**
     * Simpan URL dasar gateway. Skema http:// ditambahkan otomatis bila admin
     * hanya mengetik host:port (mis. 192.168.1.10:3000).
     */
    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'url_gateway' => ['required', 'string', 'max:255'],
        ], [
            'url_gateway.required' => 'URL gateway wajib diisi.',
            'url_gateway.max' => 'URL gateway maksimal 255 karakter.',
        ]);

        $url = trim($data['url_gateway']);

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'http://'.$url;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw ValidationException::withMessages([
                'url_gateway' => 'Format URL tidak valid. Contoh: http://localhost:3000 atau https://wa.domain.com.',
            ]);
        }

        $url = rtrim($url, '/');

        Pengaturan::simpanWhatsappGatewayUrl($url);

        Log::info('[whatsapp] Simpan URL gateway', ['url' => $url, 'oleh' => auth()->user()->nidn]);

        return redirect()
            ->route('whatsapp.index')
            ->with('status', 'URL gateway WhatsApp disimpan. Status koneksi akan diperiksa ulang.');
    }

    /**
     * Status realtime untuk polling halaman (dipanggil JavaScript tiap 10 detik).
     */
    public function status(): JsonResponse
    {
        return response()
            ->json(WhatsAppGateway::aktif()->status())
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function qr(): JsonResponse
    {
        return response()
            ->json(WhatsAppGateway::aktif()->qr())
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * Putus koneksi WhatsApp pada gateway. Gateway umumnya akan membuat sesi
     * baru dan menerbitkan QR code baru agar perangkat lain bisa ditautkan.
     */
    public function putus(): JsonResponse
    {
        $hasil = WhatsAppGateway::aktif()->putus();

        Log::info('[whatsapp] Putus koneksi gateway', [
            'sukses' => $hasil['sukses'],
            'http_status' => $hasil['http_status'],
            'endpoint' => $hasil['endpoint'],
            'oleh' => auth()->user()->nidn,
        ]);

        return response()
            ->json($hasil, $hasil['terkonfigurasi'] ? 200 : 422)
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function kirim(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nomor' => ['required', 'string', 'max:25', 'regex:/^[0-9+()\s.-]{8,25}$/'],
            'pesan' => ['required', 'string', 'max:4096'],
        ], [
            'nomor.required' => 'Nomor tujuan wajib diisi.',
            'nomor.regex' => 'Nomor tujuan hanya boleh berisi angka, spasi, tanda +, tanda hubung, dan tanda kurung.',
            'nomor.max' => 'Nomor tujuan terlalu panjang.',
            'pesan.required' => 'Isi pesan wajib diisi.',
            'pesan.max' => 'Pesan maksimal 4096 karakter.',
        ]);

        $hasil = WhatsAppGateway::aktif()->kirim($data['nomor'], $data['pesan']);

        Log::info('[whatsapp] Uji kirim pesan', [
            'nomor' => $hasil['nomor'],
            'sukses' => $hasil['sukses'],
            'http_status' => $hasil['http_status'],
            'oleh' => auth()->user()->nidn,
        ]);

        return response()
            ->json($hasil, $hasil['terkonfigurasi'] ? 200 : 422)
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
