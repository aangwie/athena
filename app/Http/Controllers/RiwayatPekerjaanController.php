<?php

namespace App\Http\Controllers;

use App\Models\JabatanFungsional;
use App\Models\JabatanStruktural;
use App\Models\Kepangkatan;
use App\Models\RiwayatAngkaKredit;
use App\Models\RiwayatJabatanStruktural;
use App\Models\RiwayatKepangkatan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        // Parameter ?edit-kepangkatan= / ?edit-jabatan= / ?edit-angka-kredit=
        // memakai ulang formulir yang sama.
        return view('profil.pekerjaan.index', [
            'user' => $user,
            'profil' => $user->profilDosen,
            'kepangkatanList' => Kepangkatan::query()->orderBy('nama')->get(),
            'jabatanStrukturalList' => JabatanStruktural::query()->orderBy('nama')->get(),
            'jabatanFungsionalList' => JabatanFungsional::query()->orderBy('nama')->get(),
            'riwayatKepangkatanList' => $user->riwayatKepangkatan->sortByDesc('tmt')->values(),
            'riwayatJabatanList' => $user->riwayatJabatanStruktural->sortByDesc('tmt')->values(),
            'riwayatAngkaKreditList' => $user->riwayatAngkaKredit->sortByDesc('tahun')->values(),
            'editKepangkatan' => $user->riwayatKepangkatan->firstWhere('id', (int) $request->query('edit-kepangkatan')),
            'editJabatan' => $user->riwayatJabatanStruktural->firstWhere('id', (int) $request->query('edit-jabatan')),
            'editAngkaKredit' => $user->riwayatAngkaKredit->firstWhere('id', (int) $request->query('edit-angka-kredit')),
        ]);
    }

    public function updateIdentitas(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'niy_npp' => ['nullable', 'string', 'max:30'],
            'nuptk' => ['nullable', 'string', 'max:30'],
        ];

        foreach (self::SK as $sk) {
            $rules["sk_{$sk}_tanggal"] = ['nullable', 'date'];
            $rules["sk_{$sk}_tahun"] = ['nullable', 'integer', 'min:0', 'max:60'];
            $rules["sk_{$sk}_bulan"] = ['nullable', 'integer', 'min:0', 'max:11'];
        }

        // Masa kerja golongan lama & baru, formatnya sama dengan masa kerja SK.
        foreach (['lama', 'baru'] as $jenis) {
            $rules["masa_kerja_golongan_{$jenis}_tahun"] = ['nullable', 'integer', 'min:0', 'max:60'];
            $rules["masa_kerja_golongan_{$jenis}_bulan"] = ['nullable', 'integer', 'min:0', 'max:11'];
        }

        $data = $request->validate($rules, [], [
            'niy_npp' => 'NIY/NPP',
            'nuptk' => 'NUPTK',
        ]);

        $profil = $user->profilDosen()->firstOrNew([]);
        $profil->fill($data);
        $profil->user_id = $user->id;
        $profil->save();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Data kepegawaian dan masa kerja berhasil disimpan.');
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

    public function storeJabatan(Request $request): RedirectResponse
    {
        $request->user()->riwayatJabatanStruktural()->create($this->validatedJabatan($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil ditambahkan.');
    }

    public function updateJabatan(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        $riwayatJabatan->update($this->validatedJabatan($request));

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil diperbarui.');
    }

    public function destroyJabatan(Request $request, RiwayatJabatanStruktural $riwayatJabatan): RedirectResponse
    {
        $this->ensureOwner($request->user(), $riwayatJabatan->user_id);

        $riwayatJabatan->delete();

        return redirect()
            ->route('profil.pekerjaan.index')
            ->with('status', 'Riwayat jabatan struktural berhasil dihapus.');
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
    private function validatedJabatan(Request $request): array
    {
        return $request->validate([
            'jabatan_struktural_id' => ['required', Rule::exists('jabatan_struktural', 'id')],
            'jabatan_fungsional_id' => ['required', Rule::exists('jabatan_fungsional', 'id')],
            'tmt' => ['required', 'date'],
        ], [
            'jabatan_struktural_id.required' => 'Jabatan struktural wajib dipilih.',
            'jabatan_struktural_id.exists' => 'Jabatan struktural tidak ditemukan.',
            'jabatan_fungsional_id.required' => 'Jabatan fungsional wajib dipilih.',
            'jabatan_fungsional_id.exists' => 'Jabatan fungsional tidak ditemukan.',
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
