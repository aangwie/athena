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
            'bidang_keilmuan' => ['required', 'string', 'max:255'],
            'prosentase_penelitian' => ['required', 'integer', 'between:0,100'],
            'anggota_senat_ids' => ['required', 'array', 'min:1'],
            // Anggota rapat hanya boleh diambil dari daftar senat instansi.
            'anggota_senat_ids.*' => ['integer', 'distinct', Rule::exists('senat', 'user_id')],
        ];
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
            'prosentase_penelitian' => 'prosentase proporsi penelitian',
            'anggota_senat_ids' => 'anggota senat',
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
            'bidang_keilmuan.required' => 'Bidang keilmuan wajib diisi.',
            'prosentase_penelitian.required' => 'Prosentase proporsi penelitian wajib diisi.',
            'prosentase_penelitian.between' => 'Prosentase proporsi penelitian harus 0 sampai 100.',
            'anggota_senat_ids.required' => 'Anggota senat wajib dipilih.',
            'anggota_senat_ids.min' => 'Pilih minimal satu anggota senat.',
            'anggota_senat_ids.*.exists' => 'Anggota senat yang dipilih tidak terdaftar pada senat instansi.',
        ];
    }
}
