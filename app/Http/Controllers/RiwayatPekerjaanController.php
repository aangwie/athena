<?php

namespace App\Http\Controllers;

use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\Kepangkatan;
use App\Models\Prodi;
use App\Models\RiwayatAngkaKredit;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKepangkatan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RiwayatPekerjaanController extends Controller
{
    /**
     * SK yang dipakai pada bagian masa kerja.
     *
     * @var list<string>
     */
    private const SK = ['pplp', 'jafa', 'impasing'];

    public function index(Request $request): View
    {
        $user = $request->user()->load([
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'riwayatJabatanStruktural.jabatanStruktural',
            'riwayatJabatanStruktural.jabatanFungsional',
            'riwayatAngkaKredit',
        ]);

        // Parameter ?edit-kepangkatan= / ?edit-struktural= / ?edit-fungsional= /
        // ?edit-angka-kredit= memakai ulang formulir yang sama.
        return view('profil.pekerjaan.index', [
            'user' => $user,
            'profil' => $user->profilDosen,
            'prodiList' => Prodi::query()->orderBy('nama_prodi')->get(),
            'kepangkatanList' => Kepangkatan::query()->orderBy('nama')->get(),
            'jabatanStrukturalList' => JabatanStruktural::query()->orderBy('nama')->get(),
            'jabatanFungsionalList' => JabatanFungsional::query()->orderBy('nama')->get(),
            'riwayatKepangkatanList' => $user->riwayatKepangkatan->sortByDesc('tmt')->values(),
            // Satu baris hanya mewakili satu jenis jabatan, jadi daftar dipisah
            // menurut kolom yang terisi: struktural atau akademik (fungsional).
            'riwayatStrukturalList' => $user->riwayatJabatanStruktural
                ->whereNotNull('jabatan_struktural_id')->sortByDesc('tmt')->values(),
            'riwayatFungsionalList' => $user->riwayatJabatanStruktural
                ->whereNotNull('jabatan_fungsional_id')->sortByDesc('tmt')->values(),
            'riwayatAngkaKreditList' => $user->riwayatAngkaKredit->sortByDesc('tahun')->values(),
            'editKepangkatan' => $user->riwayatKepangkatan->firstWhere('id', (int) $request->query('edit-kepangkatan')),
            'editStruktural' => $user->riwayatJabatanStruktural->firstWhere('id', (int) $request->query('edit-struktural')),
            'editFungsional' => $user->riwayatJabatanStruktural->firstWhere('id', (int) $request->query('edit-fungsional')),
            'editAngkaKredit' => $user->riwayatAngkaKredit->firstWhere('id', (int) $request->query('edit-angka-kredit')),
        ]);
    }

    public function updateIdentitas(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'nuptk' => ['nullable', 'string', 'max:30'],
            'bidang_ilmu' => ['nullable', 'string', 'max:100'],
            'program_studi' => ['nullable', 'string', Rule::exists('prodi', 'nama_prodi')],
            'sk_dosen_tetap' => ['nullable', 'file', 'mimes:pdf', 'max:1024'],
        ];

        foreach (self::SK as $sk) {
            $rules["sk_{$sk}_tanggal"] = ['nullable', 'date'];
        }

        // Masa kerja golongan lama & baru, formatnya sama dengan masa kerja SK.
        foreach (['lama', 'baru'] as $jenis) {
            $rules["masa_kerja_golongan_{$jenis}_tahun"] = ['nullable', 'integer', 'min:0', 'max:60'];
            $rules["masa_kerja_golongan_{$jenis}_bulan"] = ['nullable', 'integer', 'min:0', 'max:11'];
        }

        $data = $request->validate($rules, [
            'program_studi.exists' => 'Prodi tidak ditemukan pada data master prodi.',
            'sk_dosen_tetap.file' => 'Berkas SK Dosen Tetap gagal diunggah.',
            'sk_dosen_tetap.mimes' => 'Berkas SK Dosen Tetap harus berformat PDF.',
            'sk_dosen_tetap.max' => 'Ukuran berkas SK Dosen Tetap maksimal 1 MB.',
        ], [
            'nuptk' => 'NUPTK',
            'bidang_ilmu' => 'Bidang Ilmu',
            'program_studi' => 'Prodi',
            'sk_dosen_tetap' => 'berkas SK Dosen Tetap',
        ]);

        // Prodi tempat mengajar disimpan pada akun (users.program_studi),
        // kolom yang sudah dipakai profil & surat pernyataan pimpinan.
        if (array_key_exists('program_studi', $data)) {
            $user->update(['program_studi' => $data['program_studi']]);
            unset($data['program_studi']);
        }

        $skDosenTetap = $request->file('sk_dosen_tetap');
        unset($data['sk_dosen_tetap']);

        $profil = $user->profilDosen()->firstOrNew([]);
        $profil->fill($data);
        $profil->user_id = $user->id;

        if ($skDosenTetap) {
            if (filled($profil->sk_dosen_tetap_path)) {
                Storage::disk('local')->delete($profil->sk_dosen_tetap_path);
            }
            $nama = sprintf('sk_dosen_tetap_%s_%s.pdf', $user->nidn, Str::random(16));
            $profil->sk_dosen_tetap_path = $skDosenTetap->storeAs("riwayat-pekerjaan/{$user->nidn}", $nama, 'local');
        }

        // Masa kerja tidak diisi manual: dihitung dari Tanggal SK (hari ini − tanggal SK).
        foreach (self::SK as $sk) {
            [$profil->{"sk_{$sk}_tahun"}, $profil->{"sk_{$sk}_bulan"}] = $profil->masaKerja($sk);
        }

        $profil->save();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Data kepegawaian, prodi, dan masa kerja berhasil disimpan.');
    }

    public function skDosenTetap(Request $request): StreamedResponse
    {
        $profil = $request->user()->profilDosen;

        abort_unless(
            $profil && $profil->sk_dosen_tetap_path && Storage::disk('local')->exists($profil->sk_dosen_tetap_path),
            404,
        );

        return Storage::disk('local')->response($profil->sk_dosen_tetap_path, basename($profil->sk_dosen_tetap_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function storeKepangkatan(Request $request): RedirectResponse
    {
        $request->user()->riwayatKepangkatan()->create($this->validatedKepangkatan($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat kepangkatan berhasil ditambahkan.');
    }

    public function updateKepangkatan(Request $request, RiwayatKepangkatan $riwayatKepangkatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatKepangkatan->user_id);

        $riwayatKepangkatan->update($this->validatedKepangkatan($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat kepangkatan berhasil diperbarui.');
    }

    public function destroyKepangkatan(Request $request, RiwayatKepangkatan $riwayatKepangkatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatKepangkatan->user_id);

        $riwayatKepangkatan->delete();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat kepangkatan berhasil dihapus.');
    }

    public function storeAngkaKredit(Request $request): RedirectResponse
    {
        $request->user()->riwayatAngkaKredit()->create($this->validatedAngkaKredit($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat angka kredit berhasil ditambahkan.');
    }

    public function updateAngkaKredit(Request $request, RiwayatAngkaKredit $riwayatAngkaKredit): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatAngkaKredit->user_id);

        $riwayatAngkaKredit->update($this->validatedAngkaKredit($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat angka kredit berhasil diperbarui.');
    }

    public function destroyAngkaKredit(Request $request, RiwayatAngkaKredit $riwayatAngkaKredit): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatAngkaKredit->user_id);

        $riwayatAngkaKredit->delete();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat angka kredit berhasil dihapus.');
    }

    public function storeStruktural(Request $request): RedirectResponse
    {
        $request->user()->riwayatJabatanStruktural()->create(
            $this->validatedStruktural($request) + ['jabatan_fungsional_id' => null],
        );

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil ditambahkan.');
    }

    public function updateStruktural(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        // Baris ini tetap dianggap jabatan struktural; kolom akademik dibiarkan.
        $riwayatJabatan->update($this->validatedStruktural($request) + ['jabatan_fungsional_id' => null]);

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil diperbarui.');
    }

    public function destroyStruktural(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        $riwayatJabatan->delete();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil dihapus.');
    }

    public function storeFungsional(Request $request): RedirectResponse
    {
        $request->user()->riwayatJabatanStruktural()->create(
            $this->validatedFungsional($request) + ['jabatan_struktural_id' => null],
        );

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan akademik berhasil ditambahkan.');
    }

    public function updateFungsional(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        // Baris ini tetap dianggap jabatan akademik; kolom struktural dibiarkan.
        $riwayatJabatan->update($this->validatedFungsional($request) + ['jabatan_struktural_id' => null]);

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan akademik berhasil diperbarui.');
    }

    public function destroyFungsional(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        $riwayatJabatan->delete();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan akademik berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedKepangkatan(Request $request): array
    {
        return $request->validate([
            'kepangkatan_id' => ['required', Rule::exists('kepangkatan', 'id')],
            'tmt' => ['required', 'date'],
        ], [
            'kepangkatan_id.required' => 'Pangkat dan golongan wajib dipilih.',
            'kepangkatan_id.exists' => 'Pangkat dan golongan tidak ditemukan.',
            'tmt.required' => 'TMT wajib diisi.',
            'tmt.date' => 'Format TMT tidak valid.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedStruktural(Request $request): array
    {
        return $request->validate([
            'jabatan_struktural_id' => ['required', Rule::exists('jabatan_struktural', 'id')],
            'tmt' => ['required', 'date'],
        ], [
            'jabatan_struktural_id.required' => 'Jabatan struktural wajib dipilih.',
            'jabatan_struktural_id.exists' => 'Jabatan struktural tidak ditemukan.',
            'tmt.required' => 'TMT wajib diisi.',
            'tmt.date' => 'Format TMT tidak valid.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedFungsional(Request $request): array
    {
        return $request->validate([
            'jabatan_fungsional_id' => ['required', Rule::exists('jabatan_fungsional', 'id')],
            'tmt' => ['required', 'date'],
        ], [
            'jabatan_fungsional_id.required' => 'Jabatan akademik wajib dipilih.',
            'jabatan_fungsional_id.exists' => 'Jabatan akademik tidak ditemukan.',
            'tmt.required' => 'TMT wajib diisi.',
            'tmt.date' => 'Format TMT tidak valid.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAngkaKredit(Request $request): array
    {
        return $request->validate([
            'angka_kredit' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'tahun' => ['required', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
        ], [
            'angka_kredit.required' => 'Angka kredit wajib diisi.',
            'angka_kredit.numeric' => 'Angka kredit harus berupa angka.',
            'tahun.required' => 'Tahun pengajuan wajib diisi.',
            'tahun.integer' => 'Tahun pengajuan harus berupa angka.',
        ]);
    }

    private function ensureOwner(User $user, int $userId): void
    {
        abort_unless($userId === $user->id, 404);
    }
}
