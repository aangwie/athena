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
        return $this->user()?->isDosen() ?? false;
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

        $hasIjazah = $isUpdate && filled($riwayat->ijazah_path);
        $hasTranskrip = $isUpdate && filled($riwayat->transkrip_path);

        $isPerguruanTinggi = in_array(
            $this->input('jenjang'),
            RiwayatPendidikan::PERGURUAN_TINGGI,
            true,
        );

        $tahunSekarang = now()->year;

        return [
            'jenjang' => ['required', Rule::in(array_keys(RiwayatPendidikan::JENJANG))],
            'nama_institusi' => ['required', 'string', 'max:150'],
            'jurusan' => ['nullable', 'string', 'max:150'],
            'tahun_masuk' => ['required', 'integer', 'min:1900', "max:{$tahunSekarang}"],
            'tahun_lulus' => ['nullable', 'integer', 'min:1900', "max:{$tahunSekarang}", 'gte:tahun_masuk'],
            'ijazah' => [
                $hasIjazah ? 'nullable' : 'required',
                'file',
                'mimes:pdf',
                'max:500',
            ],
            'transkrip' => [
                $isPerguruanTinggi ? ($hasTranskrip ? 'nullable' : 'required') : 'nullable',
                'file',
                'mimes:pdf',
                'max:500',
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
            'jenjang' => 'jenjang pendidikan',
            'nama_institusi' => 'nama institusi',
            'jurusan' => 'jurusan',
            'tahun_masuk' => 'tahun masuk',
            'tahun_lulus' => 'tahun lulus',
            'ijazah' => 'bukti ijazah',
            'transkrip' => 'bukti transkrip',
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
            'ijazah.required' => 'Bukti ijazah wajib diunggah.',
            'ijazah.file' => 'Bukti ijazah gagal diunggah.',
            'ijazah.mimes' => 'Bukti ijazah harus berformat PDF.',
            'ijazah.max' => 'Ukuran berkas ijazah maksimal 500 KB.',
            'transkrip.required' => 'Bukti transkrip wajib diunggah untuk jenjang perguruan tinggi.',
            'transkrip.file' => 'Bukti transkrip gagal diunggah.',
            'transkrip.mimes' => 'Bukti transkrip harus berformat PDF.',
            'transkrip.max' => 'Ukuran berkas transkrip maksimal 500 KB.',
        ];
    }
}
