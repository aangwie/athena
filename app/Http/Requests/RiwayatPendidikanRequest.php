<?php

namespace App\Http\Requests;

use App\Models\RiwayatPendidikan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiwayatPendidikanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isDosen() || $this->user()?->isTendik() || false;
    }

    /**
     * Bersihkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        foreach (['nama_institusi', 'jurusan'] as $field) {
            if ($this->has($field) && is_string($this->input($field))) {
                $data[$field] = trim($this->input($field));
            }
        }

        if (($data['jurusan'] ?? null) === '') {
            $data['jurusan'] = null;
        }

        if ($this->input('tahun_lulus') === '') {
            $data['tahun_lulus'] = null;
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
        $riwayat = $this->route('riwayatPendidikan');
        $isUpdate = $riwayat instanceof RiwayatPendidikan;

        $hasDokumen = $isUpdate && filled($riwayat->ijazah_transkrip_path);

        $tahunSekarang = now()->year;

        return [
            'jenjang' => ['required', Rule::in(array_keys(RiwayatPendidikan::JENJANG))],
            'nama_institusi' => ['required', 'string', 'max:150'],
            'jurusan' => ['nullable', 'string', 'max:150'],
            'tahun_masuk' => ['required', 'integer', 'min:1900', "max:{$tahunSekarang}"],
            'tahun_lulus' => ['nullable', 'integer', 'min:1900', "max:{$tahunSekarang}", 'gte:tahun_masuk'],
            'dokumen' => [
                $hasDokumen ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:1024',
            ],
            'akreditasi_prodi' => ['nullable', 'file', 'mimes:pdf', 'max:1024'],
            'disertasi_tesis' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
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
            'jenjang' => 'jenjang pendidikan',
            'nama_institusi' => 'nama institusi',
            'jurusan' => 'jurusan',
            'tahun_masuk' => 'tahun masuk',
            'tahun_lulus' => 'tahun lulus',
            'dokumen' => 'bukti ijazah & transkrip',
            'akreditasi_prodi' => 'akreditasi prodi',
            'disertasi_tesis' => 'disertasi / tesis',
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
            'jenjang.required' => 'Jenjang pendidikan wajib dipilih.',
            'jenjang.in' => 'Jenjang pendidikan tidak valid.',
            'nama_institusi.required' => 'Nama institusi wajib diisi.',
            'tahun_masuk.required' => 'Tahun masuk wajib diisi.',
            'tahun_masuk.min' => 'Tahun masuk tidak valid.',
            'tahun_masuk.max' => 'Tahun masuk tidak boleh melebihi tahun ini.',
            'tahun_lulus.min' => 'Tahun lulus tidak valid.',
            'tahun_lulus.max' => 'Tahun lulus tidak boleh melebihi tahun ini.',
            'tahun_lulus.gte' => 'Tahun lulus tidak boleh lebih awal dari tahun masuk.',
            'dokumen.required' => 'Bukti ijazah & transkrip wajib diunggah.',
            'dokumen.file' => 'Bukti ijazah & transkrip gagal diunggah.',
            'dokumen.mimes' => 'Bukti ijazah & transkrip harus berformat PDF.',
            'dokumen.max' => 'Ukuran bukti ijazah & transkrip maksimal 1 MB.',
            'akreditasi_prodi.file' => 'Akreditasi prodi gagal diunggah.',
            'akreditasi_prodi.mimes' => 'Akreditasi prodi harus berformat PDF.',
            'akreditasi_prodi.max' => 'Ukuran akreditasi prodi maksimal 1 MB.',
            'disertasi_tesis.file' => 'Disertasi / tesis gagal diunggah.',
            'disertasi_tesis.mimes' => 'Disertasi / tesis harus berformat PDF.',
            'disertasi_tesis.max' => 'Ukuran disertasi / tesis maksimal 4 MB.',
        ];
    }
}
