<?php

namespace App\Http\Controllers;

use App\Models\Skp;
use App\Support\WhatsAppNotifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkpUsulanController extends Controller
{
    public function index(Request $request): View
    {
        // Hanya SKP yang menugaskan pejabat penilai = dosen yang sedang login.
        $skpList = Skp::query()
            ->where('pejabat_penilai_id', $request->user()->id)
            ->with(['dosen', 'prodi'])
            ->orderByDesc('periode_mulai')
            ->orderByDesc('id')
            ->get();

        return view('skp-usulan.index', [
            'skpList' => $skpList,
            'unsurList' => Skp::UNSUR,
            'editSkp' => $request->filled('edit')
                ? $skpList->firstWhere('id', (int) $request->integer('edit'))
                : null,
        ]);
    }

    public function update(Request $request, Skp $skp): RedirectResponse
    {
        // Pejabat penilai hanya boleh mengisi nilai pada SKP yang ditugaskan kepadanya.
        abort_unless($skp->pejabat_penilai_id === $request->user()->id, 403);

        $skp->update($this->validated($request));

        WhatsAppNotifikasi::skpDinilai($skp);

        return redirect()
            ->route('skp-usulan.index')
            ->with('status', 'Nilai perilaku kerja berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $aturan = [];
        foreach (array_keys(Skp::UNSUR) as $kunci) {
            $aturan['nilai_'.$kunci] = ['required', 'integer', 'between:0,100'];
        }

        return $request->validate($aturan, [
            'nilai_*.required' => 'Nilai wajib diisi untuk seluruh unsur perilaku kerja.',
            'nilai_*.integer' => 'Nilai angka harus berupa bilangan bulat.',
            'nilai_*.between' => 'Nilai angka harus antara 0 sampai 100.',
        ]);
    }
}