<?php

namespace App\Http\Requests;

use App\Models\SuratPengantar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratPengantarRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kembali ke tab Surat Pengantar (tetap dalam mode ubah) saat validasi gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        $pengantar = $this->route('pengantar');
        $tujuan = route('surat.index', array_filter([
            'edit_pengantar' => $pengantar instanceof SuratPengantar ? $pengantar->getKey() : null,
        ]));

        throw (new ValidationException($validator))->redirectTo($tujuan.'#surat-pengantar');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Surat pribadi hanya untuk satu dosen; surat kolektif boleh banyak.
        $kolektif = $this->boolean('kolektif');

        return [
            'nomor_surat' => ['required', 'string', 'max:100'],
            'tanggal_surat' => ['required', 'date_format:d-m-Y'],
            'lampiran' => ['nullable', 'string', 'max:100'],
            'perihal' => ['required', 'string', 'max:255'],
            'tujuan' => ['nullable', 'string', 'max:150'],
            'kota_tujuan' => ['nullable', 'string', 'max:100'],
            'jabatan_diusulkan' => ['nullable', 'string', 'max:100'],
            'kolektif' => ['nullable', 'boolean'],
            'dosen_ids' => ['required', 'array', $kolektif ? 'min:1' : 'size:1'],
            'dosen_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
            ],
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
            'nomor_surat' => 'nomor surat',
            'tanggal_surat' => 'tanggal surat',
            'lampiran' => 'lampiran',
            'perihal' => 'perihal',
            'tujuan' => 'tujuan surat',
            'kota_tujuan' => 'kota tujuan surat',
            'jabatan_diusulkan' => 'jabatan akademik yang diusulkan',
            'dosen_ids' => 'nama dosen',
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
            'nomor_surat.required' => 'Nomor surat wajib diisi.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
            'tanggal_surat.date_format' => 'Tanggal surat harus berformat DD-MM-YYYY.',
            'perihal.required' => 'Perihal wajib diisi.',
            'dosen_ids.required' => 'Nama dosen wajib dipilih.',
            'dosen_ids.size' => 'Surat pribadi hanya untuk satu dosen. Centang Kolektif untuk lebih dari satu.',
            'dosen_ids.min' => 'Pilih minimal satu dosen.',
            'dosen_ids.*.exists' => 'Nama dosen harus dosen aktif.',
        ];
    }
}
