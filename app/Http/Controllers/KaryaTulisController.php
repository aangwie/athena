<?php

namespace App\Http\Controllers;

use App\Models\KaryaTulis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KaryaTulisController extends Controller
{
    public function index(Request $request): View
    {
        $items = $request->user()->karyaTulis()->latest()->get();

        // Parameter ?edit= memakai ulang formulir yang sama di halaman ini.
        return view('profil.karya-tulis.index', [
            'items' => $items,
            'editItem' => $items->firstWhere('id', (int) $request->query('edit')),
            'jenisList' => KaryaTulis::JENIS,
            // Bidang tambahan per jenis: jurnal (nama & alamat web) atau prosiding.
            'jenisJurnal' => KaryaTulis::JENIS_JURNAL,
            'jenisProsiding' => KaryaTulis::JENIS_PROSIDING,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->user()->karyaTulis()->create($this->validated($request));

        return redirect()
            ->route('profil.karya-tulis.index')
            ->with('status', 'Riwayat karya tulis berhasil ditambahkan.');
    }

    public function update(Request $request, KaryaTulis $karyaTulis): RedirectResponse
    {
        $this->ensureOwner($request, $karyaTulis);

        $karyaTulis->update($this->validated($request));

        return redirect()
            ->route('profil.karya-tulis.index')
            ->with('status', 'Riwayat karya tulis berhasil diperbarui.');
    }

    public function destroy(Request $request, KaryaTulis $karyaTulis): RedirectResponse
    {
        $this->ensureOwner($request, $karyaTulis);

        $karyaTulis->delete();

        return redirect()
            ->route('profil.karya-tulis.index')
            ->with('status', 'Riwayat karya tulis berhasil dihapus.');
    }

    private function ensureOwner(Request $request, KaryaTulis $karyaTulis): void
    {
        abort_unless($karyaTulis->user_id === $request->user()->id, 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $jenis = (string) $request->input('karya_ilmiah');
        $jurnal = in_array($jenis, KaryaTulis::JENIS_JURNAL, true);
        $prosiding = $jenis === KaryaTulis::JENIS_PROSIDING;

        $data = $request->validate([
            'karya_ilmiah' => ['required', 'string', Rule::in(KaryaTulis::JENIS)],
            'judul' => ['required', 'string', 'max:255'],
            'link_bukti' => ['required', 'string', 'max:2048', 'url:http,https'],
            // Bidang jurnal hanya wajib saat jenis karya berupa jurnal.
            'nama_jurnal' => ['nullable', 'string', 'max:255', Rule::requiredIf($jurnal)],
            'url_jurnal' => ['nullable', 'string', 'max:2048', 'url:http,https', Rule::requiredIf($jurnal)],
            // Bidang prosiding hanya wajib saat jenis karya berupa prosiding.
            'nama_prosiding' => ['nullable', 'string', 'max:255', Rule::requiredIf($prosiding)],
            'url_prosiding' => ['nullable', 'string', 'max:2048', 'url:http,https', Rule::requiredIf($prosiding)],
            'url_plagiasi' => ['nullable', 'string', 'max:2048', 'url:http,https', Rule::requiredIf($prosiding)],
            // ISSN bersifat opsional pada jurnal maupun prosiding (boleh diisi atau dikosongkan).
            'issn' => ['nullable', 'string', 'max:50'],
        ], [
            'karya_ilmiah.required' => 'Karya ilmiah wajib dipilih.',
            'karya_ilmiah.in' => 'Karya ilmiah harus salah satu pilihan yang tersedia.',
            'judul.required' => 'Judul wajib diisi.',
            'judul.max' => 'Judul maksimal 255 karakter.',
            'link_bukti.required' => 'Link bukti wajib diisi.',
            'link_bukti.url' => 'Link bukti harus berupa URL yang valid, diawali http:// atau https://.',
            'link_bukti.max' => 'Link bukti maksimal 2048 karakter.',
            'nama_jurnal.required' => 'Nama jurnal wajib diisi.',
            'nama_jurnal.max' => 'Nama jurnal maksimal 255 karakter.',
            'url_jurnal.required' => 'Alamat web jurnal wajib diisi.',
            'url_jurnal.url' => 'Alamat web jurnal harus berupa URL yang valid, diawali http:// atau https://.',
            'url_jurnal.max' => 'Alamat web jurnal maksimal 2048 karakter.',
            'nama_prosiding.required' => 'Nama prosiding wajib diisi.',
            'nama_prosiding.max' => 'Nama prosiding maksimal 255 karakter.',
            'issn.max' => 'ISSN maksimal 50 karakter.',
            'url_prosiding.required' => 'URL prosiding wajib diisi.',
            'url_prosiding.url' => 'URL prosiding harus berupa URL yang valid, diawali http:// atau https://.',
            'url_prosiding.max' => 'URL prosiding maksimal 2048 karakter.',
            'url_plagiasi.required' => 'URL plagiasi wajib diisi.',
            'url_plagiasi.url' => 'URL plagiasi harus berupa URL yang valid, diawali http:// atau https://.',
            'url_plagiasi.max' => 'URL plagiasi maksimal 2048 karakter.',
        ], [
            'karya_ilmiah' => 'karya ilmiah',
            'judul' => 'judul',
            'link_bukti' => 'link bukti',
            'nama_jurnal' => 'nama jurnal',
            'url_jurnal' => 'alamat web jurnal',
            'nama_prosiding' => 'nama prosiding',
            'issn' => 'ISSN',
            'url_prosiding' => 'URL prosiding',
            'url_plagiasi' => 'URL plagiasi',
        ]);

        // Bidang jenis lain dikosongkan agar tidak ada data basi saat jenis karya diganti.
        return [
            'karya_ilmiah' => $data['karya_ilmiah'],
            'judul' => $data['judul'],
            'link_bukti' => $data['link_bukti'],
            'nama_jurnal' => $jurnal ? ($data['nama_jurnal'] ?? null) : null,
            'url_jurnal' => $jurnal ? ($data['url_jurnal'] ?? null) : null,
            'nama_prosiding' => $prosiding ? ($data['nama_prosiding'] ?? null) : null,
            // ISSN dipakai oleh jurnal maupun prosiding; dikosongkan untuk jenis lain.
            'issn' => ($jurnal || $prosiding) ? ($data['issn'] ?? null) : null,
            'url_prosiding' => $prosiding ? ($data['url_prosiding'] ?? null) : null,
            'url_plagiasi' => $prosiding ? ($data['url_plagiasi'] ?? null) : null,
        ];
    }
}
