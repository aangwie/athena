<?php

namespace App\Http\Requests;

use App\Models\Prodi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Bersihkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if (is_string($this->input('nama_prodi'))) {
            $nama = trim($this->input('nama_prodi'));
            $data['nama_prodi'] = $nama === '' ? null : $nama;
        }

        if (is_string($this->input('kode_prodi'))) {
            $kode = strtoupper(trim($this->input('kode_prodi')));
            $data['kode_prodi'] = $kode === '' ? null : $kode;
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $prodi = $this->route('prodi');
        $ignoreId = $prodi instanceof Prodi ? $prodi->id : null;

        return [
            'nama_prodi' => ['required', 'string', 'max:150'],
            'kode_prodi' => [
                'required',
                'string',
                'max:20',
                Rule::unique('prodi', 'kode_prodi')->ignore($ignoreId),
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
            'nama_prodi' => 'nama prodi',
            'kode_prodi' => 'kode prodi',
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
            'nama_prodi.required' => 'Nama prodi wajib diisi.',
            'nama_prodi.max' => 'Nama prodi maksimal 150 karakter.',
            'kode_prodi.required' => 'Kode prodi wajib diisi.',
            'kode_prodi.max' => 'Kode prodi maksimal 20 karakter.',
            'kode_prodi.unique' => 'Kode prodi tersebut sudah terdaftar.',
        ];
    }
}
