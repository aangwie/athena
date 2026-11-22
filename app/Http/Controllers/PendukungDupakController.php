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

    /**
     * Halaman Syarat Khusus DUPAK milik dosen: formulir 21 baris beserta entri
     * yang sudah pernah disimpan.
     */
    public function index(Request $request): View
    {
        return view('profil.pendukung-dupak.index', [
            'item' => $request->user()->pendukungDupak()->first(),
        ]);
    }

    /**
     * Simpan entri Syarat Khusus DUPAK dosen. Satu dosen hanya memiliki satu
     * entri, sehingga pengiriman ulang formulir memperbarui entri yang ada.
     */
    public function simpan(Request $request): RedirectResponse
    {
        $item = $request->user()->pendukungDupak()->first();

        $data = $this->lampirkanBerkas($request, $this->validated($request, $item), $item);

        $request->user()->pendukungDupak()->updateOrCreate([], $data);

        return redirect()
            ->route('profil.pendukung-dupak.index')
            ->with('status', 'Syarat Khusus DUPAK berhasil disimpan.');
    }

    /**
     * Admin memilih dosen terlebih dahulu, lalu tabel menampilkan hasil isian
     * syarat khusus dosen tersebut. Halaman ini hanya menampilkan data.
     */
    public function indexAdmin(Request $request): View
    {
        // Hanya dosen yang sudah mengirim syarat khusus yang dapat dipilih.
        $dosenList = User::berRole(['dosen'])
            ->filter(fn (User $dosen) => $dosen->pendukungDupak()->exists())
            ->values();

        $dosenTerpilih = $dosenList->firstWhere('id', $request->integer('dosen'));

        return view('admin.pendukung-dupak.index', [
            'dosenList' => $dosenList,
            'dosenTerpilih' => $dosenTerpilih,
            'item' => $dosenTerpilih?->pendukungDupak()->first(),
        ]);
    }

    /**
     * Tampilkan berkas bukti syarat khusus. Pemilik membuka berkasnya sendiri,
     * admin boleh membuka berkas seluruh dosen untuk keperluan verifikasi.
     */
    public function berkas(Request $request, PendukungDupak $pendukungDupak, string $field): StreamedResponse
    {
        $user = $request->user();

        abort_unless($pendukungDupak->user_id === $user->id || $user->isAdmin(), 404);
        abort_unless(array_key_exists($field, PendukungDupak::BERKAS), 404);

        $path = $pendukungDupak->pathBerkas($field);

        abort_unless(filled($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, $pendukungDupak->namaBerkas($field) ?: basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Aturan validasi dibangun dari definisi baris formulir: isian wajib, pilihan
     * dropdown, tautan, dan batas ukuran berkas mengikuti lampiran syarat khusus.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?PendukungDupak $item = null): array
    {
        $rules = [];

        foreach (PendukungDupak::BARIS as $baris) {
            $field = $baris['field'];
            $wajib = (bool) ($baris['wajib'] ?? false);

            $rules[$field] = match ($baris['tipe']) {
                // Berkas lama boleh dipertahankan saat formulir dikirim ulang.
                'berkas' => [
                    ($item?->adaBerkas($field) ?? false) ? 'nullable' : 'required',
                    'file',
                    'mimes:pdf',
                    'max:'.$baris['maks_kb'],
                ],
                'tautan' => [
                    $wajib ? 'required' : 'nullable',
                    'string',
                    'max:2048',
                    'url:http,https',
                ],
                'pilihan' => [
                    $wajib ? 'required' : 'nullable',
                    'string',
                    Rule::in($baris['opsi']),
                ],
                'tanggal' => [
                    $wajib ? 'required' : 'nullable',
                    'date',
                ],
                default => [
                    $wajib ? 'required' : 'nullable',
                    'string',
                    'max:'.$baris['max'],
                ],
            };
        }

        return $request->validate($rules, $this->pesanValidasi(), $this->atributValidasi());
    }

    /**
     * @return array<string, string>
     */
    private function pesanValidasi(): array
    {
        $pesan = [];

        foreach (PendukungDupak::BARIS as $baris) {
            $field = $baris['field'];
            $label = $baris['label'];
            $berkas = $baris['tipe'] === 'berkas';

            $pesan[$field.'.required'] = $berkas ? $label.' wajib diunggah.' : $label.' wajib diisi.';
            $pesan[$field.'.file'] = $label.' gagal diunggah.';
            $pesan[$field.'.mimes'] = $label.' harus berformat PDF.';
            $pesan[$field.'.max'] = match (true) {
                $berkas => $label.' maksimal '.PendukungDupak::labelUkuran($baris['maks_kb']).'.',
                isset($baris['max']) => $label.' maksimal '.$baris['max'].' karakter.',
                default => $label.' maksimal 2048 karakter.',
            };
            $pesan[$field.'.url'] = $label.' harus berupa URL yang valid, diawali http:// atau https://.';
            $pesan[$field.'.date'] = $label.' harus berupa tanggal yang valid.';
            $pesan[$field.'.in'] = $label.' harus salah satu pilihan yang tersedia.';
        }

        return $pesan;
    }

    /**
     * @return array<string, string>
     */
    private function atributValidasi(): array
    {
        return array_column(PendukungDupak::BARIS, 'label', 'field');
    }

    /**
     * Simpan berkas bukti yang diunggah dan hapus berkas lama saat diganti.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function lampirkanBerkas(Request $request, array $data, ?PendukungDupak $item = null): array
    {
        foreach (array_keys(PendukungDupak::BERKAS) as $kunci) {
            // Berkas unggahan tidak masuk ke kolom tabel; hanya path & nama aslinya.
            unset($data[$kunci]);

            if (! $request->hasFile($kunci)) {
                continue;
            }

            $lama = $item?->pathBerkas($kunci);

            if (filled($lama)) {
                Storage::disk('local')->delete($lama);
            }

            $file = $request->file($kunci);

            $data[PendukungDupak::kolomBerkas($kunci)] = $file->storeAs(
                self::FOLDER.'/'.$request->user()->nidn,
                sprintf('%s_%s_%s.pdf', $kunci, $request->user()->nidn, Str::random(16)),
                'local',
            );
            $data['berkas_'.$kunci.'_nama'] = $file->getClientOriginalName();
        }

        return $data;
    }
}
