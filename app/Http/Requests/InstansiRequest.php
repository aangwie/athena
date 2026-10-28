<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstansiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Bersihkan input dan lengkapi skema website sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['nama_instansi', 'nomor_sk_operasional', 'alamat', 'no_telp', 'website'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $value = trim($this->input($field));
                $data[$field] = $value === '' ? null : $value;
            }
        }

        if (! empty($data['website']) && ! preg_match('#^https?://#i', $data['website'])) {
            $data['website'] = 'https://'.$data['website'];
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
        return [
            'nama_instansi' => ['required', 'string', 'max:150'],
            'nomor_sk_operasional' => ['required', 'string', 'max:100'],
            'alamat' => ['required', 'string', 'max:1000'],
            'no_telp' => ['required', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:150', 'url'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:500'],
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
            'nama_instansi' => 'nama instansi',
            'nomor_sk_operasional' => 'nomor SK operasional',
            'alamat' => 'alamat',
            'no_telp' => 'nomor telepon',
            'website' => 'website',
            'logo' => 'logo instansi',
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
            'nama_instansi.required' => 'Nama instansi wajib diisi.',
            'nomor_sk_operasional.required' => 'Nomor SK operasional wajib diisi.',
            'alamat.required' => 'Alamat instansi wajib diisi.',
            'no_telp.required' => 'Nomor telepon wajib diisi.',
            'website.url' => 'Format website tidak valid.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat JPG, PNG, atau WEBP.',
            'logo.max' => 'Ukuran logo maksimal 500 KB.',
        ];
    }
}
