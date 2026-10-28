<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDataPribadiRequest extends FormRequest
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
        if ($this->input('status_pernikahan') === 'lajang') {
            $this->merge([
                'nama_pasangan' => null,
                'pekerjaan_pasangan' => null,
                'nip_pasangan' => null,
                'tempat_lahir_pasangan' => null,
                'tanggal_lahir_pasangan' => null,
                'tanggal_nikah' => null,
                'children' => [],
            ]);

            return;
        }

        if ($this->input('pekerjaan_pasangan') !== 'asn') {
            $this->merge(['nip_pasangan' => null]);
        }

        $children = $this->input('children');

        if (is_array($children)) {
            $children = array_values(array_filter(
                $children,
                fn ($child) => is_array($child) && collect($child)->contains(fn ($value) => filled($value)),
            ));

            $this->merge(['children' => $children]);
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
            'nama' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'alamat' => ['required', 'string', 'max:500'],
            'no_hp' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'status_pernikahan' => ['required', 'in:lajang,menikah'],
            'nama_pasangan' => ['nullable', 'required_if:status_pernikahan,menikah', 'string', 'max:100'],
            'pekerjaan_pasangan' => ['nullable', 'required_if:status_pernikahan,menikah', 'in:asn,non_asn'],
            'nip_pasangan' => ['nullable', 'required_if:pekerjaan_pasangan,asn', 'string', 'max:30'],
            'tempat_lahir_pasangan' => ['nullable', 'required_if:status_pernikahan,menikah', 'string', 'max:100'],
            'tanggal_lahir_pasangan' => ['nullable', 'required_if:status_pernikahan,menikah', 'date', 'before:today'],
            'tanggal_nikah' => ['nullable', 'required_if:status_pernikahan,menikah', 'date', 'before_or_equal:today'],
            'children' => ['nullable', 'array'],
            'children.*.nama' => ['required', 'string', 'max:100'],
            'children.*.tempat_lahir' => ['required', 'string', 'max:100'],
            'children.*.tanggal_lahir' => ['required', 'date', 'before:today'],
            'children.*.jenis_kelamin' => ['required', 'in:L,P'],
            'children.*.pekerjaan' => ['nullable', 'string', 'max:100'],
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
            'nama' => 'nama lengkap',
            'tempat_lahir' => 'tempat lahir',
            'tanggal_lahir' => 'tanggal lahir',
            'alamat' => 'alamat',
            'no_hp' => 'nomor HP',
            'status_pernikahan' => 'status pernikahan',
            'nama_pasangan' => 'nama pasangan',
            'pekerjaan_pasangan' => 'pekerjaan pasangan',
            'nip_pasangan' => 'NIP pasangan',
            'tempat_lahir_pasangan' => 'tempat lahir pasangan',
            'tanggal_lahir_pasangan' => 'tanggal lahir pasangan',
            'tanggal_nikah' => 'tanggal nikah',
            'children.*.nama' => 'nama anak',
            'children.*.tempat_lahir' => 'tempat lahir anak',
            'children.*.tanggal_lahir' => 'tanggal lahir anak',
            'children.*.jenis_kelamin' => 'jenis kelamin anak',
            'children.*.pekerjaan' => 'pekerjaan anak',
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
            'nama.required' => 'Nama lengkap wajib diisi.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'alamat.required' => 'Alamat wajib diisi.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
            'no_hp.regex' => 'Format nomor HP tidak valid.',
            'status_pernikahan.required' => 'Status pernikahan wajib dipilih.',
            'nama_pasangan.required_if' => 'Nama pasangan wajib diisi.',
            'pekerjaan_pasangan.required_if' => 'Pekerjaan pasangan wajib dipilih.',
            'nip_pasangan.required_if' => 'NIP pasangan wajib diisi untuk pekerjaan ASN.',
            'tempat_lahir_pasangan.required_if' => 'Tempat lahir pasangan wajib diisi.',
            'tanggal_lahir_pasangan.required_if' => 'Tanggal lahir pasangan wajib diisi.',
            'tanggal_nikah.required_if' => 'Tanggal nikah wajib diisi.',
            'children.*.nama.required' => 'Nama anak wajib diisi.',
            'children.*.tempat_lahir.required' => 'Tempat lahir anak wajib diisi.',
            'children.*.tanggal_lahir.required' => 'Tanggal lahir anak wajib diisi.',
            'children.*.jenis_kelamin.required' => 'Jenis kelamin anak wajib dipilih.',
        ];
    }
}
