<?php

namespace App\Http\Requests;

use App\Models\SuratBaki;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratBakiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kembali ke tab BAKI (tetap dalam mode ubah) saat validasi gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        $baki = $this->route('baki');
        $tujuan = route('surat.index', array_filter([
            'edit_baki' => $baki instanceof SuratBaki ? $baki->getKey() : null,
        ]));

        throw (new ValidationException($validator))->redirectTo($tujuan.'#baki');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nomor_surat' => ['required', 'string', 'max:100'],
            'surat_bap_senat_id' => [
                'required',
                'integer',
                Rule::exists('surat_bap_senat', 'id'),
            ],
            'tanggal_surat' => ['required', 'date_format:d-m-Y'],
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
            'surat_bap_senat_id' => 'nama dosen yang diusulkan senat',
            'tanggal_surat' => 'tanggal surat',
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
            'nomor_surat.max' => 'Nomor surat maksimal 100 karakter.',
            'surat_bap_senat_id.required' => 'Nama dosen yang diusulkan senat wajib dipilih.',
            'surat_bap_senat_id.exists' => 'Usulan senat yang dipilih tidak valid.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
            'tanggal_surat.date_format' => 'Tanggal surat harus berformat DD-MM-YYYY.',
        ];
    }
}
