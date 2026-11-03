<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Excel;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import data dosen dari berkas Excel.
 *
 * Berkas diunggah sekali (mulai), lalu barisnya dimasukkan ke database
 * sedikit-sedikit (langkah) supaya bilah progres di halaman bergerak nyata.
 * Berkas Excel dihapus begitu seluruh baris selesai diproses.
 */
class DataDosenImportController extends Controller
{
    /** Folder sementara di disk privat untuk berkas Excel & salinan barisnya. */
    private const FOLDER = 'import-data-dosen';

    /** Jumlah baris yang disimpan per permintaan langkah. */
    private const PER_LANGKAH = 20;

    /** Password bawaan bila kolom Password dikosongkan pada template. */
    private const PASSWORD_BAWAAN = 'password';

    /** @var array<int, string> */
    private const KOLOM = [
        'Nama Dosen',
        'NIDN',
        'Memiliki Jabatan Akademik',
        'Jabatan Akademik',
        'Pangkat',
        'Role',
        'Password',
    ];

    /** @var array<int, array<int, string>> */
    private const CONTOH = [
        ['Dr. Citra Maharani, M.Si.', '1985041722', 'Ya', 'Lektor Kepala', 'Penata Tingkat I', 'dosen,tim_pak', 'password'],
        ['Budi Santoso, M.Pd.', '1990011220', 'Tidak', '', '', 'dosen', 'password'],
    ];

    public function create(): View
    {
        return view('data-dosen.import', [
            'kolom' => self::KOLOM,
            'contoh' => self::CONTOH,
            'passwordBawaan' => self::PASSWORD_BAWAAN,
        ]);
    }

    /**
     * Unduh template Excel yang sudah berisi judul kolom dan dua baris contoh.
     */
    public function template(): BinaryFileResponse
    {
        $jalur = sys_get_temp_dir().DIRECTORY_SEPARATOR.'template-import-data-dosen-'.Str::random(8).'.xlsx';

        Excel::tulis($jalur, [self::KOLOM, ...self::CONTOH], [30, 18, 26, 22, 20, 22, 16]);

        return response()->download($jalur, 'template-import-data-dosen.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Terima berkas Excel, baca seluruh barisnya, lalu siapkan proses bertahap.
     */
    public function mulai(Request $request): JsonResponse
    {
        $request->validate([
            'berkas' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
        ], [
            'berkas.required' => 'Pilih berkas Excel yang akan diimpor.',
            'berkas.mimes' => 'Berkas harus berformat .xlsx. Gunakan template yang disediakan.',
            'berkas.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $disk = Storage::disk('local');
        $folder = trim(self::FOLDER.'/'.session()->getId(), '/');

        // Sisa proses sebelumnya (berkas Excel & salinan baris) dibersihkan dulu.
        $disk->deleteDirectory($folder);

        $nama = 'data-dosen-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.xlsx';
        $request->file('berkas')->storeAs($folder, $nama, 'local');
        $jalur = $folder.'/'.$nama;

        try {
            $baris = Excel::baca($disk->path($jalur));
        } catch (RuntimeException $exception) {
            $disk->delete($jalur);

            return response()->json(['pesan' => $exception->getMessage()], 422);
        }

        // Baris pertama adalah judul kolom; baris kosong tidak perlu diproses.
        $data = array_values(array_filter(
            array_slice($baris, 1),
            fn (array $sel) => trim(implode('', $sel)) !== '',
        ));

        if ($data === []) {
            $disk->delete($jalur);

            return response()->json([
                'pesan' => 'Tidak ada baris data pada berkas. Salin data dosen ke template, lalu unggah ulang.',
            ], 422);
        }

        $salinan = $folder.'/'.Str::uuid().'.json';
        $disk->put($salinan, json_encode($data, JSON_UNESCAPED_UNICODE));

        session(['import-dosen' => [
            'folder' => $folder,
            'berkas' => $jalur,
            'salinan' => $salinan,
            'total' => count($data),
            'posisi' => 0,
            'masuk' => 0,
            'gagal' => [],
        ]]);

        return response()->json([
            'persen' => 0,
            'pesan' => count($data).' baris terbaca. Mulai menyimpan ke database…',
        ]);
    }

    /**
     * Simpan satu potong baris (lihat mulai) dan laporkan kemajuannya.
     */
    public function langkah(): JsonResponse
    {
        $job = session('import-dosen');
        $disk = Storage::disk('local');

        if (! is_array($job) || ! $disk->exists($job['salinan'] ?? '')) {
            session()->forget('import-dosen');

            return response()->json([
                'pesan' => 'Tidak ada proses import yang berjalan. Unggah ulang berkas Excel.',
            ], 409);
        }

        $baris = json_decode((string) $disk->get($job['salinan']), true) ?: [];
        $potong = array_slice($baris, $job['posisi'], self::PER_LANGKAH);

        foreach ($potong as $urutan => $row) {
            $galat = $this->simpan($row);

            if ($galat === null) {
                $job['masuk']++;
            } else {
                // Nomor baris mengikuti Excel, jadi baris data pertama adalah baris 2.
                $job['gagal'][] = 'Baris '.($job['posisi'] + $urutan + 2).': '.$galat;
            }
        }

        $job['posisi'] += count($potong);
        $selesai = $job['posisi'] >= $job['total'];
        $persen = $job['total'] > 0 ? (int) floor(($job['posisi'] / $job['total']) * 100) : 100;

        if (! $selesai) {
            session(['import-dosen' => $job]);

            return response()->json([
                'persen' => $persen,
                'pesan' => $job['posisi'].' dari '.$job['total'].' baris diproses…',
            ]);
        }

        // Seluruh baris sudah masuk database: berkas Excel & salinan sementaranya dihapus.
        $disk->deleteDirectory($job['folder']);
        session()->forget('import-dosen');

        $pesan = $job['masuk'].' data dosen berhasil diimpor.';

        if ($job['gagal'] !== []) {
            $pesan .= ' '.count($job['gagal']).' baris dilewati — '
                .implode(' ', array_slice($job['gagal'], 0, 5))
                .(count($job['gagal']) > 5 ? ' …' : '');
        }

        session()->flash('status', $pesan);

        return response()->json([
            'persen' => 100,
            'pesan' => $pesan,
            'redirect' => route('data-dosen.index'),
        ]);
    }

    /**
     * Simpan satu baris Excel. Kembalikan pesan galat bila baris dilewati.
     */
    private function simpan(array $row): ?string
    {
        [$nama, $nidn, $memiliki, $jabatan, $pangkat, $roles, $password] = array_pad(
            array_map(fn ($sel) => trim((string) $sel), $row),
            7,
            '',
        );

        $memiliki = $this->punyaJabatan($memiliki, $jabatan, $pangkat);

        $validator = Validator::make([
            'name' => $nama,
            'nidn' => $nidn,
            'jabatan_fungsional' => $memiliki === 'ya' ? $jabatan : null,
            'pangkat' => $memiliki === 'ya' ? $pangkat : null,
            'roles' => $this->roles($roles),
            'password' => $password !== '' ? $password : self::PASSWORD_BAWAAN,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'nidn' => ['required', 'string', 'max:20'],
            'jabatan_fungsional' => ['nullable', 'string', 'max:100', Rule::requiredIf($memiliki === 'ya')],
            'pangkat' => ['nullable', 'string', 'max:100', Rule::requiredIf($memiliki === 'ya')],
            'roles' => ['array', 'min:1'],
            'roles.*' => [Rule::in(array_keys(User::ROLES))],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'Nama Dosen wajib diisi.',
            'nidn.required' => 'NIDN wajib diisi.',
            'jabatan_fungsional.required' => 'Jabatan Akademik wajib dipilih jika dosen memiliki jabatan akademik.',
            'pangkat.required' => 'Pangkat wajib diisi jika memiliki jabatan akademik.',
            'roles.min' => 'Role tidak dikenali. Gunakan dosen, tendik, tim_pak, atau admin.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->first();
        }

        $data = $validator->validated();

        try {
            User::create([
                'name' => $data['name'],
                'nidn' => $data['nidn'],
                'jabatan_fungsional' => $data['jabatan_fungsional'] ?? null,
                'pangkat' => $data['pangkat'] ?? null,
                'role' => $data['roles'][0],
                'roles' => $data['roles'],
                'is_active' => true,
                'password' => $data['password'],
            ]);
        } catch (UniqueConstraintViolationException) {
            return 'NIDN '.$data['nidn'].' sudah terdaftar.';
        }

        return null;
    }

    /**
     * Aturan yang sama dengan form: kolom kosong disimpulkan dari terisi/tidaknya
     * jabatan akademik & pangkat.
     */
    private function punyaJabatan(string $teks, string $jabatan, string $pangkat): string
    {
        $teks = Str::lower(trim($teks));

        return match (true) {
            in_array($teks, ['ya', 'y', '1', 'true'], true) => 'ya',
            in_array($teks, ['tidak', 't', '0', 'false'], true) => 'tidak',
            default => filled($jabatan) || filled($pangkat) ? 'ya' : 'tidak',
        };
    }

    /**
     * "dosen,tim_pak" / "Dosen; TIM PAK" → ['dosen', 'tim_pak'].
     * Teks role yang tidak dikenali mengembalikan daftar kosong agar baris dilewati.
     *
     * @return array<int, string>
     */
    private function roles(string $teks): array
    {
        $peta = [];
        foreach (User::ROLES as $kunci => $label) {
            $peta[Str::lower($kunci)] = $kunci;
            $peta[Str::lower($label)] = $kunci;
        }

        $dipilih = collect(preg_split('/[,;|]+/', Str::lower($teks)) ?: [])
            ->map(fn (string $role) => $peta[trim($role)] ?? null)
            ->filter()
            ->all();

        if ($dipilih === [] && trim($teks) !== '') {
            return [];
        }

        // Urutkan seperti urutan role di form agar kolom role lama konsisten.
        $roles = array_values(array_filter(
            array_keys(User::ROLES),
            fn (string $role) => in_array($role, $dipilih, true),
        ));

        return $roles !== [] ? $roles : ['dosen'];
    }
}