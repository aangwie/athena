<?php

namespace App\Http\Requests;

use App\Models\SuratPaktaIntegritas;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratPaktaIntegritasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kembali ke tab Pakta Integritas (tetap dalam mode ubah) saat validasi gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        $pakta = $this->route('pakta');
        $tujuan = route('surat.index', array_filter([
            'edit_pakta' => $pakta instanceof SuratPaktaIntegritas ? $pakta->getKey() : null,
        ]));

        throw (new ValidationException($validator))->redirectTo($tujuan.'#pakta-integritas');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dosen_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
            ],
            'tanggal_pakta' => [
                'required',
                'date_format:d-m-Y',
            ],
            'karya_tulis_ids' => [
                'nullable',
                'array',
            ],
            'karya_tulis_ids.*' => [
                'integer',
                // Hanya karya ilmiah milik dosen yang dipilih.
                Rule::exists('karya_tulis', 'id')->where(
                    fn ($query) => $query->where('user_id', $this->input('dosen_id')),
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
            'dosen_id' => 'nama dosen',
            'tanggal_pakta' => 'tanggal pakta',
            'karya_tulis_ids' => 'karya ilmiah',
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
            'dosen_id.required' => 'Nama dosen wajib dipilih.',
            'dosen_id.exists' => 'Nama dosen harus dosen aktif.',
            'tanggal_pakta.required' => 'Tanggal pakta wajib diisi.',
            'tanggal_pakta.date_format' => 'Tanggal pakta harus berformat DD-MM-YYYY.',
            'karya_tulis_ids.*.exists' => 'Karya ilmiah yang dipilih bukan milik dosen ini.',
        ];
    }
}
