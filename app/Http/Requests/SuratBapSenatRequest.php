<?php

namespace App\Http\Requests;

use App\Models\SuratBapSenat;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratBapSenatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kembali ke tab BAP Senat (tetap dalam mode ubah) saat validasi gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        $bap = $this->route('bap');
        $tujuan = route('surat.index', array_filter([
            'edit_bap' => $bap instanceof SuratBapSenat ? $bap->getKey() : null,
        ]));

        throw (new ValidationException($validator))->redirectTo($tujuan.'#bap-senat');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Prosentase proporsi penelitian hanya relevan saat kenaikan yang dipilih
        // adalah Asisten Ahli ke Lektor.
        $memakaiProsentase = $this->input('kenaikan_jabatan') === 'asisten_ahli_ke_lektor';

        // Jumlah kuorum manual wajib diisi saat admin memilih mode manual.
        $kuorumManual = $this->input('kuorum_mode') === SuratBapSenat::KUORUM_MANUAL;

        return [
            'nomor_bap' => ['required', 'string', 'max:100'],
            'tanggal_bap' => ['required', 'date_format:d-m-Y'],
            'dosen_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
            ],
            'bidang_keilmuan' => ['required', 'string', 'max:255', Rule::in($this->bidangKeilmuanDiizinkan())],
            'kenaikan_jabatan' => ['required', 'string', Rule::in(SuratBapSenat::kenaikanJabatanList())],
            'prosentase_penelitian' => [
                'nullable',
                'integer',
                Rule::in($this->prosentaseDiizinkan()),
                Rule::requiredIf($memakaiProsentase),
            ],
            'tempat_pelaksanaan' => ['required', 'string', 'max:150'],
            'materi_rapat' => ['required', 'string', 'max:255'],
            'anggota_senat_ids' => ['required', 'array', 'min:1'],
            // Anggota rapat hanya boleh diambil dari daftar senat instansi.
            'anggota_senat_ids.*' => ['integer', 'distinct', Rule::exists('senat', 'user_id')],
            // Kuorum: otomatis dari jumlah anggota senat, atau angka manual dari admin.
            'kuorum_mode' => ['nullable', 'string', Rule::in(SuratBapSenat::kuorumModeList())],
            'jumlah_kuorum' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
                Rule::requiredIf($kuorumManual),
            ],
        ];
    }

    /**
     * Bidang keilmuan yang boleh dipilih: daftar rumpun pada model, ditambah nilai
     * lama milik BAP yang sedang diubah agar data lama tetap dapat disunting.
     *
     * @return list<string>
     */
    private function bidangKeilmuanDiizinkan(): array
    {
        $daftar = SuratBapSenat::bidangKeilmuanList();
        $bap = $this->route('bap');

        if ($bap instanceof SuratBapSenat && filled($bap->bidang_keilmuan)) {
            $daftar[] = $bap->bidang_keilmuan;
        }

        return array_values(array_unique($daftar));
    }

    /**
     * Nilai prosentase proporsi penelitian yang boleh dipilih: daftar tetap pada model,
     * ditambah nilai lama milik BAP yang sedang diubah agar data lama tetap dapat disunting.
     *
     * @return list<int>
     */
    private function prosentaseDiizinkan(): array
    {
        $daftar = SuratBapSenat::prosentasePenelitianList();
        $bap = $this->route('bap');

        if ($bap instanceof SuratBapSenat && $bap->prosentase_penelitian !== null) {
            $daftar[] = (int) $bap->prosentase_penelitian;
        }

        return array_values(array_unique($daftar));
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nomor_bap' => 'nomor BAP',
            'tanggal_bap' => 'tanggal',
            'dosen_id' => 'nama dosen',
            'bidang_keilmuan' => 'bidang keilmuan',
            'kenaikan_jabatan' => 'pilihan kenaikan jabatan akademik',
            'prosentase_penelitian' => 'prosentase proporsi penelitian',
            'tempat_pelaksanaan' => 'tempat pelaksanaan',
            'materi_rapat' => 'materi rapat',
            'anggota_senat_ids' => 'anggota senat',
            'kuorum_mode' => 'cara pengisian kuorum',
            'jumlah_kuorum' => 'jumlah kuorum',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_bap.required' => 'Nomor BAP wajib diisi.',
            'tanggal_bap.required' => 'Tanggal wajib diisi.',
            'tanggal_bap.date_format' => 'Tanggal harus berformat DD-MM-YYYY.',
            'dosen_id.required' => 'Nama dosen wajib dipilih.',
            'dosen_id.exists' => 'Nama dosen harus dosen aktif.',
            'bidang_keilmuan.required' => 'Bidang keilmuan wajib dipilih.',
            'bidang_keilmuan.in' => 'Bidang keilmuan tidak valid, pilih dari daftar yang tersedia.',
            'kenaikan_jabatan.required' => 'Pilihan kenaikan jabatan akademik wajib dipilih.',
            'kenaikan_jabatan.in' => 'Pilihan kenaikan jabatan akademik tidak valid.',
            'prosentase_penelitian.required' => 'Prosentase proporsi penelitian wajib diisi.',
            'prosentase_penelitian.integer' => 'Prosentase proporsi penelitian harus berupa angka.',
            'prosentase_penelitian.in' => 'Prosentase proporsi penelitian tidak valid, pilih dari daftar yang tersedia.',
            'tempat_pelaksanaan.required' => 'Tempat pelaksanaan wajib diisi.',
            'materi_rapat.required' => 'Materi rapat wajib diisi.',
            'anggota_senat_ids.required' => 'Anggota senat wajib dipilih.',
            'anggota_senat_ids.min' => 'Pilih minimal satu anggota senat.',
            'anggota_senat_ids.*.exists' => 'Anggota senat yang dipilih tidak terdaftar pada senat instansi.',
            'kuorum_mode.in' => 'Cara pengisian kuorum tidak valid.',
            'jumlah_kuorum.required' => 'Jumlah kuorum wajib diisi saat memilih mode manual.',
            'jumlah_kuorum.integer' => 'Jumlah kuorum harus berupa angka.',
            'jumlah_kuorum.min' => 'Jumlah kuorum minimal 1.',
            'jumlah_kuorum.max' => 'Jumlah kuorum maksimal 999.',
        ];
    }
}
