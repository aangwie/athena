<?php

namespace App\Http\Requests;

use App\Models\KegiatanDupak;
use Illuminate\Validation\Rule;

/**
 * Validasi entri yang punya select bertingkat (komponen → rincian → rincian kegiatan).
 * Subclass cukup menunjuk pasangan konstanta & label bidangnya.
 */
class KegiatanBidangRequest extends KegiatanPendidikanRequest
{
    /** Nama konstanta daftar komponen di {@see KegiatanDupak}. */
    protected const JENIS = 'JENIS_PELAKSANAAN';

    /** Nama konstanta daftar rincian di {@see KegiatanDupak}. */
    protected const SUB = 'SUB_PELAKSANAAN';

    /** Nama konstanta rincian tingkat ketiga di {@see KegiatanDupak}; null bila bidang hanya dua tingkat. */
    protected const RINCIAN = null;

    /** Sebutan bidang untuk pesan validasi. */
    protected const BIDANG = 'bidang B';

    /**
     * Bersihkan input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        foreach (['sub_jenis', 'rincian'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }

        // Komponen tanpa rincian tidak menyimpan sub_jenis sama sekali.
        if (! isset($this->subMap()[(string) $this->input('jenis')])) {
            $this->merge(['sub_jenis' => null]);
        }

        // Idem untuk rincian tingkat ketiga (bidang dua tingkat selalu mengosongkannya).
        if (! isset($this->rincianMap()[(string) $this->input('sub_jenis')])) {
            $this->merge(['rincian' => null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rincian = $this->subMap()[(string) $this->input('jenis')] ?? [];
        $rincianKegiatan = $this->rincianMap()[(string) $this->input('sub_jenis')] ?? [];

        return [
            ...parent::rules(),
            'jenis' => ['required', Rule::in(array_keys($this->jenisMap()))],
            'sub_jenis' => $rincian === [] ? ['nullable'] : ['required', Rule::in(array_keys($rincian))],
            'rincian' => $rincianKegiatan === [] ? ['nullable'] : ['required', Rule::in(array_keys($rincianKegiatan))],
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
            ...parent::attributes(),
            'jenis' => 'komponen '.static::BIDANG,
            'sub_jenis' => 'rincian komponen '.static::BIDANG,
            'rincian' => 'rincian kegiatan '.static::BIDANG,
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $bidang = static::BIDANG;

        return [
            ...parent::messages(),
            'jenis.required' => 'Komponen '.$bidang.' wajib dipilih.',
            'jenis.in' => 'Komponen '.$bidang.' tidak valid.',
            'sub_jenis.required' => 'Rincian komponen '.$bidang.' wajib dipilih.',
            'sub_jenis.in' => 'Rincian komponen '.$bidang.' tidak valid.',
            'rincian.required' => 'Rincian kegiatan '.$bidang.' wajib dipilih.',
            'rincian.in' => 'Rincian kegiatan '.$bidang.' tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function jenisMap(): array
    {
        return constant(KegiatanDupak::class.'::'.static::JENIS);
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function subMap(): array
    {
        return constant(KegiatanDupak::class.'::'.static::SUB);
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function rincianMap(): array
    {
        return static::RINCIAN === null ? [] : constant(KegiatanDupak::class.'::'.static::RINCIAN);
    }
}
