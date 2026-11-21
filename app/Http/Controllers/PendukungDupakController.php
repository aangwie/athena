<?php

namespace App\Http\Controllers;

use App\Models\PendukungDupak;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendukungDupakController extends Controller
{
    /** Folder penyimpanan berkas pada disk privat (tidak dapat diakses langsung). */
    private const FOLDER = 'pendukung-dupak';

    /** Ukuran maksimal berkas PDF dalam KB (10 MB). */
    public const MAKS_KB = 10240;

    public function index(Request $request): View
    {
        $items = $request->user()->pendukungDupak()->latest()->get();

        // Parameter ?edit= memakai ulang formulir yang sama di halaman ini.
        return view('profil.pendukung-dupak.index', [
            'items' => $items,
            'editItem' => $items->firstWhere('id', (int) $request->query('edit')),
            'jenisList' => PendukungDupak::JENIS,
            'keteranganList' => PendukungDupak::KETERANGAN,
            'maksKb' => self::MAKS_KB,
        ]);
    }

    /**
     * Admin memilih dosen terlebih dahulu, lalu tabel menampilkan dokumen pendukung
     * dosen tersebut. Halaman ini hanya menampilkan data (tanpa formulir & aksi).
     */
    public function indexAdmin(Request $request): View
    {
        // Jumlah dokumen per dosen: dipakai untuk label pilihan dan menyaring dosen
        // yang benar-benar memiliki dokumen pendukung.
        $jumlahDokumen = PendukungDupak::query()
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $dosenList = User::berRole(['dosen'])
            ->filter(fn (User $dosen) => $jumlahDokumen->has($dosen->id))
            ->values();

        $dosenTerpilih = $dosenList->firstWhere('id', $request->integer('dosen'));

        return view('admin.pendukung-dupak.index', [
            'dosenList' => $dosenList,
            'jumlahDokumen' => $jumlahDokumen,
            'dosenTerpilih' => $dosenTerpilih,
            'items' => $dosenTerpilih
                ? $dosenTerpilih->pendukungDupak()->latest()->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->lampirkanBerkas($request, $data);

        $request->user()->pendukungDupak()->create($data);

        return redirect()
            ->route('profil.pendukung-dupak.index')
            ->with('status', 'Dokumen pendukung DUPAK berhasil ditambahkan.');
    }

    public function update(Request $request, PendukungDupak $pendukungDupak): RedirectResponse
    {
        $this->ensureOwner($request, $pendukungDupak);

        $data = $this->validated($request, $pendukungDupak);
        $data = $this->lampirkanBerkas($request, $data, $pendukungDupak);

        $pendukungDupak->update($data);

        return redirect()
            ->route('profil.pendukung-dupak.index')
            ->with('status', 'Dokumen pendukung DUPAK berhasil diperbarui.');
    }

    public function destroy(Request $request, PendukungDupak $pendukungDupak): RedirectResponse
    {
        $this->ensureOwner($request, $pendukungDupak);

        $this->hapusBerkas($pendukungDupak->berkas_path);
        $pendukungDupak->delete();

        return redirect()
            ->route('profil.pendukung-dupak.index')
            ->with('status', 'Dokumen pendukung DUPAK berhasil dihapus.');
    }

    /**
     * Tampilkan berkas pendukung DUPAK. Pemilik membuka berkasnya sendiri,
     * admin boleh membuka berkas seluruh dosen untuk keperluan verifikasi.
     */
    public function berkas(Request $request, PendukungDupak $pendukungDupak): StreamedResponse
    {
        $user = $request->user();

        abort_unless($pendukungDupak->user_id === $user->id || $user->isAdmin(), 404);

        $path = $pendukungDupak->berkas_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $pendukungDupak->namaBerkas() ?: basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function ensureOwner(Request $request, PendukungDupak $pendukungDupak): void
    {
        abort_unless($pendukungDupak->user_id === $request->user()->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PendukungDupak $item = null): array
    {
        $jenis = (string) $request->input('jenis');
        $memakaiBerkas = $jenis !== PendukungDupak::JENIS_PROFIL_SINTA;

        // Berkas baru wajib saat menambah, saat jenis dokumen diganti, atau saat
        // baris lama belum memiliki berkas. Berkas lama boleh dipertahankan saat
        // menyunting jenis yang sama.
        $wajibBerkas = $memakaiBerkas
            && ($item === null || $item->jenis !== $jenis || blank($item->berkas_path));

        return $request->validate([
            'jenis' => ['required', 'string', Rule::in(array_keys(PendukungDupak::JENIS))],
            'berkas' => [
                'exclude_if:jenis,'.PendukungDupak::JENIS_PROFIL_SINTA,
                $wajibBerkas ? 'required' : 'nullable',
                'file',
                'mimes:pdf',
                'max:'.self::MAKS_KB,
            ],
            'tautan' => [
                'exclude_unless:jenis,'.PendukungDupak::JENIS_PROFIL_SINTA,
                'required',
                'string',
                'max:2048',
                'url:http,https',
            ],
        ], [
            'jenis.required' => 'Jenis dokumen wajib dipilih.',
            'jenis.in' => 'Jenis dokumen harus salah satu pilihan yang tersedia.',
            'berkas.required' => 'Berkas wajib diunggah.',
            'berkas.file' => 'Berkas gagal diunggah.',
            'berkas.mimes' => 'Berkas harus berformat PDF.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
            'tautan.required' => 'Tautan profil SINTA wajib diisi.',
            'tautan.url' => 'Tautan profil SINTA harus berupa URL yang valid, diawali http:// atau https://.',
            'tautan.max' => 'Tautan profil SINTA maksimal 2048 karakter.',
        ], [
            'jenis' => 'jenis dokumen',
            'berkas' => 'berkas',
            'tautan' => 'tautan profil SINTA',
        ]);
    }

    /**
     * Simpan berkas unggahan (bila ada) dan kosongkan kolom yang tidak dipakai
     * jenis dokumen terpilih, sehingga tidak ada data basi saat jenis diganti.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function lampirkanBerkas(Request $request, array $data, ?PendukungDupak $item = null): array
    {
        unset($data['berkas']);

        if ($data['jenis'] === PendukungDupak::JENIS_PROFIL_SINTA) {
            // Pindah ke tautan: berkas lama tidak dipakai lagi.
            $this->hapusBerkas($item?->berkas_path);

            $data['berkas_path'] = null;
            $data['berkas_nama'] = null;

            return $data;
        }

        $data['tautan'] = null;

        if (! $request->hasFile('berkas')) {
            return $data;
        }

        $this->hapusBerkas($item?->berkas_path);

        $file = $request->file('berkas');

        $data['berkas_path'] = $file->storeAs(
            self::FOLDER.'/'.$request->user()->nidn,
            sprintf('%s_%s_%s.pdf', $data['jenis'], $request->user()->nidn, Str::random(16)),
            'local',
        );
        $data['berkas_nama'] = $file->getClientOriginalName();

        return $data;
    }

    private function hapusBerkas(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('local')->delete($path);
        }
    }
}
