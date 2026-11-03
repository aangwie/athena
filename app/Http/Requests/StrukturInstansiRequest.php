<?php

namespace App\Http\Requests;

use App\Models\StrukturInstansi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StrukturInstansiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $struktur = $this->route('struktur');
        $ignoreId = $struktur instanceof StrukturInstansi ? $struktur->id : null;

        return [
            'jabatan_struktural_id' => [
                'required',
                'integer',
                Rule::exists('jabatan_struktural', 'id'),
                // Satu jabatan struktural hanya untuk satu pejabat.
                Rule::unique('struktur_instansi', 'jabatan_struktural_id')->ignore($ignoreId),
            ],
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query->where('role', 'dosen')->where('is_active', true),
                ),
                // Satu dosen hanya boleh merangkap satu jabatan struktural.
                Rule::unique('struktur_instansi', 'user_id')->ignore($ignoreId),
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
            'jabatan_struktural_id' => 'jabatan struktural',
            'user_id' => 'nama pejabat',
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
            'jabatan_struktural_id.required' => 'Jabatan struktural wajib dipilih.',
            'jabatan_struktural_id.exists' => 'Jabatan struktural tidak valid.',
            'jabatan_struktural_id.unique' => 'Jabatan struktural tersebut sudah terisi. Kosongkan atau ubah pejabat lama terlebih dahulu.',
            'user_id.required' => 'Nama pejabat wajib dipilih.',
            'user_id.exists' => 'Pejabat harus merupakan dosen aktif.',
            'user_id.unique' => 'Pejabat tersebut sudah menjabat pada jabatan struktural lain. Satu dosen hanya boleh merangkap satu jabatan.',
        ];
    }
}
