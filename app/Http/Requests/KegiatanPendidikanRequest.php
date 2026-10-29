<?php

namespace App\Http\Requests;

use App\Models\KegiatanDupak;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KegiatanPendidikanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isDosen() ?? false;
    }

    /**
     * Bersihkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        foreach (['nama_kegiatan', 'link_bukti'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun' => ['required', 'integer', 'min:2000', 'max:2100'],
            'semester' => ['required', Rule::in(array_keys(KegiatanDupak::SEMESTER))],
            'jenis' => ['required', Rule::in(array_keys(KegiatanDupak::JENIS_PENDIDIKAN))],
            'nama_kegiatan' => ['required', 'string', 'max:200'],
            'link_bukti' => ['required', 'string', 'max:2048', 'url:http,https'],
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
            'tahun' => 'tahun akademik',
            'semester' => 'semester',
            'jenis' => 'komponen bidang A',
            'nama_kegiatan' => 'nama kegiatan',
            'link_bukti' => 'link bukti',
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
            'tahun.required' => 'Tahun akademik wajib diisi.',
            'tahun.integer' => 'Tahun akademik tidak valid.',
            'tahun.min' => 'Tahun akademik tidak valid.',
            'tahun.max' => 'Tahun akademik tidak valid.',
            'semester.required' => 'Semester wajib dipilih.',
            'semester.in' => 'Semester tidak valid.',
            'jenis.required' => 'Komponen bidang A wajib dipilih.',
            'jenis.in' => 'Komponen bidang A tidak valid.',
            'nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
            'nama_kegiatan.max' => 'Nama kegiatan maksimal 200 karakter.',
            'link_bukti.required' => 'Link bukti wajib diisi.',
            'link_bukti.url' => 'Link bukti harus berupa URL yang valid, diawali http:// atau https://.',
            'link_bukti.max' => 'Link bukti maksimal 2048 karakter.',
        ];
    }
}
