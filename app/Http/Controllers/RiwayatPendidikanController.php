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

        unset($data['dokumen'], $data['akreditasi_prodi'], $data['disertasi_tesis']);

        $data['ijazah_transkrip_path'] = $this->simpanBerkas($request->file('dokumen'), $user, $data['jenjang'], 'ijazah_transkrip');
        $data['akreditasi_prodi_path'] = $this->simpanBerkas($request->file('akreditasi_prodi'), $user, $data['jenjang'], 'akreditasi_prodi');
        $data['disertasi_tesis_path'] = $this->simpanBerkas($request->file('disertasi_tesis'), $user, $data['jenjang'], 'disertasi_tesis');

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

        unset($data['dokumen'], $data['akreditasi_prodi'], $data['disertasi_tesis']);

        $this->gantiBerkas($request, 'dokumen', 'ijazah_transkrip_path', $riwayatPendidikan, $data);
        $this->gantiBerkas($request, 'akreditasi_prodi', 'akreditasi_prodi_path', $riwayatPendidikan, $data);
        $this->gantiBerkas($request, 'disertasi_tesis', 'disertasi_tesis_path', $riwayatPendidikan, $data);

        $riwayatPendidikan->update($data);

        return redirect()
            ->route('profil.pendidikan.index')
            ->with('status', 'Riwayat pendidikan berhasil diperbarui.');
    }

    public function destroy(Request $request, RiwayatPendidikan $riwayatPendidikan): RedirectResponse
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        $this->hapusBerkas(
            $riwayatPendidikan->ijazah_transkrip_path,
            $riwayatPendidikan->akreditasi_prodi_path,
            $riwayatPendidikan->disertasi_tesis_path,
            $riwayatPendidikan->ijazah_path,
            $riwayatPendidikan->transkrip_path,
        );
        $riwayatPendidikan->delete();

        return redirect()
            ->route('profil.pendidikan.index')
            ->with('status', 'Riwayat pendidikan beserta berkasnya berhasil dihapus.');
    }

    public function berkas(Request $request, RiwayatPendidikan $riwayatPendidikan, string $jenis): StreamedResponse
    {
        $this->ensureOwner($request, $riwayatPendidikan);

        $path = $riwayatPendidikan->pathBerkas($jenis);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function ensureOwner(Request $request, RiwayatPendidikan $riwayatPendidikan): void
    {
        abort_unless($riwayatPendidikan->user_id === $request->user()->id, 404);
    }

    /**
     * Simpan PDF dengan nama jenis_jenjang_nidn_kode_acak.pdf pada disk privat.
     */
    private function simpanBerkas(?UploadedFile $file, User $user, string $jenjang, string $jenis): ?string
    {
        if (! $file) {
            return null;
        }

        $nama = sprintf('%s_%s_%s_%s.pdf', $jenis, $jenjang, $user->nidn, Str::random(16));

        return $file->storeAs("riwayat-pendidikan/{$user->nidn}", $nama, 'local');
    }

    /**
     * Ganti berkas pada kolom tertentu saat pengguna mengunggah berkas baru.
     */
    private function gantiBerkas(Request $request, string $field, string $column, RiwayatPendidikan $riwayatPendidikan, array &$data): void
    {
        if (! $request->hasFile($field)) {
            return;
        }

        $this->hapusBerkas($riwayatPendidikan->{$column});
        $data[$column] = $this->simpanBerkas($request->file($field), $request->user(), $data['jenjang'], $field);
    }

    private function hapusBerkas(?string ...$paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk('local')->delete($paths);
        }
    }
}
