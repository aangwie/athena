<?php

namespace App\Http\Requests;

use App\Models\SuratPernyataanPimpinan;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratPernyataanPimpinanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Kembali ke tab Pernyataan Pimpinan (tetap dalam mode ubah) saat validasi gagal.
     */
    protected function failedValidation(Validator $validator): void
    {
        $pernyataan = $this->route('pernyataan');
        $tujuan = route('surat.index', array_filter([
            'edit' => $pernyataan instanceof SuratPernyataanPimpinan ? $pernyataan->getKey() : null,
        ]));

        throw (new ValidationException($validator))->redirectTo($tujuan.'#pernyataan-pimpinan');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'struktur_instansi_id' => [
                'required',
                'integer',
                Rule::exists('struktur_instansi', 'id'),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
            ],
            'tanggal_surat' => [
                'required',
                'date_format:d-m-Y',
            ],
            'nomor_surat' => [
                'nullable',
                'string',
                'max:100',
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
            'struktur_instansi_id' => 'nama pimpinan',
            'user_id' => 'dosen yang diusulkan',
            'tanggal_surat' => 'tanggal surat',
            'nomor_surat' => 'nomor surat',
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
            'struktur_instansi_id.required' => 'Nama pimpinan wajib dipilih.',
            'struktur_instansi_id.exists' => 'Pimpinan tidak valid.',
            'user_id.required' => 'Dosen yang diusulkan wajib dipilih.',
            'user_id.exists' => 'Dosen yang diusulkan harus dosen aktif.',
            'tanggal_surat.required' => 'Tanggal surat wajib diisi.',
            'tanggal_surat.date_format' => 'Tanggal surat harus berformat DD-MM-YYYY.',
            'nomor_surat.max' => 'Nomor surat maksimal 100 karakter.',
        ];
    }
}
