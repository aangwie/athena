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
        return $request->validate([
            'karya_ilmiah' => ['required', 'string', Rule::in(KaryaTulis::JENIS)],
            'judul' => ['required', 'string', 'max:255'],
            'link_bukti' => ['required', 'string', 'max:2048', 'url:http,https'],
        ], [
            'karya_ilmiah.required' => 'Karya ilmiah wajib dipilih.',
            'karya_ilmiah.in' => 'Karya ilmiah harus salah satu pilihan yang tersedia.',
            'judul.required' => 'Judul wajib diisi.',
            'judul.max' => 'Judul maksimal 255 karakter.',
            'link_bukti.required' => 'Link bukti wajib diisi.',
            'link_bukti.url' => 'Link bukti harus berupa URL yang valid, diawali http:// atau https://.',
            'link_bukti.max' => 'Link bukti maksimal 2048 karakter.',
        ], [
            'karya_ilmiah' => 'karya ilmiah',
            'judul' => 'judul',
            'link_bukti' => 'link bukti',
        ]);
    }
}
