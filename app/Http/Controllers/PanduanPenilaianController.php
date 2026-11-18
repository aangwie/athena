<?php

namespace App\Http\Controllers;

use App\Models\PanduanPenilaian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PanduanPenilaianController extends Controller
{
    /** Folder penyimpanan berkas panduan pada disk lokal (tidak dapat diakses langsung). */
    private const FOLDER = 'panduan-penilaian';

    /**
     * Halaman panduan penilaian. Admin dapat mengubah sumber panduan,
     * TIM PAK hanya membaca isi panduan yang sudah disimpan.
     */
    public function index(): View
    {
        $panduan = PanduanPenilaian::saatIni();

        return view('panduan.penilaian', [
            'panduan' => $panduan,
            'isAdmin' => auth()->user()->isAdmin(),
            'tautanPratinjau' => $panduan?->tautanPratinjau(),
        ]);
    }

    /**
     * Simpan panduan dari berkas PDF yang diunggah admin atau dari tautan berkas.
     *
     * Sumber yang tidak dipilih ikut dikosongkan: saat beralih ke tautan, berkas
     * lama dihapus dari penyimpanan; saat mengunggah berkas, tautan lama dibuang.
     */
    public function simpan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipe' => ['required', 'in:file,link'],
            'berkas' => ['exclude_unless:tipe,file', 'required', 'file', 'mimetypes:application/pdf', 'max:'.PanduanPenilaian::MAKS_KB],
            'tautan' => ['exclude_unless:tipe,link', 'required', 'string', 'max:2048', 'url', 'starts_with:http://,https://'],
        ], [
            'tipe.required' => 'Pilih sumber panduan: upload file atau kirim link file.',
            'tipe.in' => 'Sumber panduan yang dipilih tidak dikenal.',
            'berkas.required' => 'Pilih berkas PDF panduan yang akan diunggah.',
            'berkas.file' => 'Berkas panduan gagal diunggah, silakan coba lagi.',
            'berkas.mimetypes' => 'Berkas panduan harus berformat PDF.',
            'berkas.max' => 'Ukuran berkas panduan maksimal 10 MB.',
            'tautan.required' => 'Link berkas panduan wajib diisi.',
            'tautan.string' => 'Link berkas panduan tidak valid.',
            'tautan.max' => 'Link berkas panduan maksimal 2048 karakter.',
            'tautan.url' => 'Link panduan harus berupa URL yang valid.',
            'tautan.starts_with' => 'Link panduan harus diawali http:// atau https://.',
        ]);

        $panduan = PanduanPenilaian::saatIni() ?? new PanduanPenilaian();
        $berkasLama = $panduan->path;

        if ($data['tipe'] === 'file') {
            $berkas = $request->file('berkas');
            $namaAsli = $berkas->getClientOriginalName();

            $panduan->fill([
                'tipe' => 'file',
                'path' => $berkas->store(self::FOLDER, 'local'),
                'nama_asli' => $namaAsli,
                'url' => null,
            ]);
        } else {
            $panduan->fill([
                'tipe' => 'link',
                'path' => null,
                'nama_asli' => null,
                'url' => trim($data['tautan']),
            ]);
        }

        $panduan->user_id = $request->user()->id;
        $panduan->save();

        // Berkas lama dihapus agar penyimpanan tidak menumpuk saat sumber diganti.
        if (filled($berkasLama) && $berkasLama !== $panduan->path) {
            Storage::disk('local')->delete($berkasLama);
        }

        Log::info('[panduan-penilaian] Simpan panduan', [
            'tipe' => $panduan->tipe,
            'oleh' => $request->user()->nidn,
        ]);

        return redirect()
            ->route('panduan.penilaian.index')
            ->with('status', 'Panduan penilaian berhasil disimpan dan dapat dibaca TIM PAK.');
    }

    /**
     * Hapus panduan beserta berkasnya. TIM PAK tidak lagi melihat isi panduan
     * sampai admin menyimpan panduan baru.
     */
    public function hapus(): RedirectResponse
    {
        $panduan = PanduanPenilaian::saatIni();

        if ($panduan === null) {
            return redirect()
                ->route('panduan.penilaian.index')
                ->with('error', 'Belum ada panduan penilaian yang tersimpan.');
        }

        if (filled($panduan->path)) {
            Storage::disk('local')->delete($panduan->path);
        }

        $panduan->delete();

        Log::info('[panduan-penilaian] Hapus panduan', ['oleh' => auth()->user()->nidn]);

        return redirect()
            ->route('panduan.penilaian.index')
            ->with('status', 'Panduan penilaian dihapus. TIM PAK dapat membaca panduan setelah admin menyimpan yang baru.');
    }

    /**
     * Tampilkan berkas panduan untuk dipratinjau admin maupun TIM PAK.
     */
    public function berkas(): StreamedResponse
    {
        $panduan = PanduanPenilaian::saatIni();

        abort_unless($panduan?->memakaiBerkas() && Storage::disk('local')->exists($panduan->path), 404);

        return Storage::disk('local')->response($panduan->path, $panduan->nama_asli ?: basename($panduan->path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
