<?php

namespace App\Http\Controllers;

use App\Http\Requests\RiwayatPendidikanRequest;
use App\Models\RiwayatPendidikan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatPendidikanController extends Controller
{
    public function index(Request $request): View
    {
        $riwayatList = $request->user()
            ->riwayatPendidikan()
            ->get()
            ->sortBy(fn (RiwayatPendidikan $item) => ($item->jenjangRank() * 10000) + $item->tahun_masuk)
            ->values();

        return view('profil.pendidikan.index', [
            'riwayatList' => $riwayatList,
        ]);
    }

    public function create(): View
    {
        return view('profil.pendidikan.form', [
            'riwayat' => null,
        ]);
    }

    public function store(RiwayatPendidikanRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        unset($data['ijazah'], $data['transkrip']);

        $data['ijazah_path'] = $this->simpanBerkas($request->file('ijazah'), $user, $data['jenjang']);
        $data['transkrip_path'] = $this->isPerguruanTinggi($data['jenjang'])
            ? $this->simpanBerkas($request->file('transkrip'), $user, $data['jenjang'])
            : null;

        $user->riwayatPendidikan()->create($data);

        return redirect()
            ->route('profil.pendidikan.index')
            ->with('status', 'Riwayat pendidikan berhasil ditambahkan.');
    }

    public function edit(Request $request, RiwayatPendidikan $riwayatPendidikan): View
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        return view('profil.pendidikan.form', [
            'riwayat' => $riwayatPendidikan,
        ]);
    }

    public function update(RiwayatPendidikanRequest $request, RiwayatPendidikan $riwayatPendidikan): RedirectResponse
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        $data = $request->validated();

        unset($data['ijazah'], $data['transkrip']);

        if ($request->hasFile('ijazah')) {
            $this->hapusBerkas($riwayatPendidikan->ijazah_path);
            $data['ijazah_path'] = $this->simpanBerkas($request->file('ijazah'), $request->user(), $data['jenjang']);
        }

        if ($this->isPerguruanTinggi($data['jenjang'])) {
            if ($request->hasFile('transkrip')) {
                $this->hapusBerkas($riwayatPendidikan->transkrip_path);
                $data['transkrip_path'] = $this->simpanBerkas($request->file('transkrip'), $request->user(), $data['jenjang']);
            }
        } else {
            $this->hapusBerkas($riwayatPendidikan->transkrip_path);
            $data['transkrip_path'] = null;
        }

        $riwayatPendidikan->update($data);

        return redirect()
            ->route('profil.pendidikan.index')
            ->with('status', 'Riwayat pendidikan berhasil diperbarui.');
    }

    public function destroy(Request $request, RiwayatPendidikan $riwayatPendidikan): RedirectResponse
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        $this->hapusBerkas($riwayatPendidikan->ijazah_path, $riwayatPendidikan->transkrip_path);
        $riwayatPendidikan->delete();

        return redirect()
            ->route('profil.pendidikan.index')
            ->with('status', 'Riwayat pendidikan beserta berkasnya berhasil dihapus.');
    }

    public function berkas(Request $request, RiwayatPendidikan $riwayatPendidikan, string $jenis): StreamedResponse
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        abort_unless(in_array($jenis, ['ijazah', 'transkrip'], true), 404);

        $path = $jenis === 'ijazah'
            ? $riwayatPendidikan->ijazah_path
            : $riwayatPendidikan->transkrip_path;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function ensureOwner(Request $request, RiwayatPendidikan $riwayatPendidikan): void
    {
        abort_unless($riwayatPendidikan->user_id === $request->user()->id, 404);
    }

    private function isPerguruanTinggi(string $jenjang): bool
    {
        return in_array($jenjang, RiwayatPendidikan::PERGURUAN_TINGGI, true);
    }

    /**
     * Simpan PDF dengan nama jenjang_nidn_kode_acak.pdf pada disk privat.
     */
    private function simpanBerkas(?UploadedFile $file, User $user, string $jenjang): ?string
    {
        if (! $file) {
            return null;
        }

        $nama = sprintf('%s_%s_%s.pdf', $jenjang, $user->nidn, Str::random(16));

        return $file->storeAs("riwayat-pendidikan/{$user->nidn}", $nama, 'local');
    }

    private function hapusBerkas(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }
}
