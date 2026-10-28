@php
    $namaKey = "children.{$index}.nama";
    $tempatKey = "children.{$index}.tempat_lahir";
    $tanggalKey = "children.{$index}.tanggal_lahir";
    $genderKey = "children.{$index}.jenis_kelamin";
    $pekerjaanKey = "children.{$index}.pekerjaan";
@endphp

<div data-anak-row class="rounded-xl border border-slate-200 bg-slate-50/40 p-4 transition hover:border-slate-300 sm:p-5">
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="flex items-center gap-2 text-xs font-bold text-slate-700">
            <span class="flex size-6 items-center justify-center rounded-md bg-teal-100 text-teal-700">
                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="8" r="3.5" />
                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                </svg>
            </span>
            Anak <span data-anak-number>{{ is_numeric($index) ? $index + 1 : '' }}</span>
        </p>
        <button type="button" data-remove-anak
                class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[11px] font-semibold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                aria-label="Hapus data anak">
            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
            </svg>
            Hapus
        </button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sm:col-span-2 lg:col-span-1">
            <label class="form-label" for="anak-{{ $index }}-nama">Nama Anak <span class="text-rose-500">*</span></label>
            <input type="text" id="anak-{{ $index }}-nama" name="children[{{ $index }}][nama]"
                   value="{{ $anak['nama'] ?? '' }}" placeholder="Nama lengkap anak"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($namaKey)])>
            @error($namaKey)
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label" for="anak-{{ $index }}-tempat">Tempat Lahir <span class="text-rose-500">*</span></label>
            <input type="text" id="anak-{{ $index }}-tempat" name="children[{{ $index }}][tempat_lahir]"
                   value="{{ $anak['tempat_lahir'] ?? '' }}" placeholder="Kota kelahiran"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($tempatKey)])>
            @error($tempatKey)
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label" for="anak-{{ $index }}-tanggal">Tanggal Lahir <span class="text-rose-500">*</span></label>
            <input type="date" id="anak-{{ $index }}-tanggal" name="children[{{ $index }}][tanggal_lahir]"
                   value="{{ $anak['tanggal_lahir'] ?? '' }}" max="{{ now()->format('Y-m-d') }}"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($tanggalKey)])>
            @error($tanggalKey)
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="form-label" for="anak-{{ $index }}-gender">Jenis Kelamin <span class="text-rose-500">*</span></label>
            <select id="anak-{{ $index }}-gender" name="children[{{ $index }}][jenis_kelamin]"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has($genderKey)])>
                <option value="">Pilih jenis kelamin</option>
                <option value="L" @selected(($anak['jenis_kelamin'] ?? '') === 'L')>Laki-laki</option>
                <option value="P" @selected(($anak['jenis_kelamin'] ?? '') === 'P')>Perempuan</option>
            </select>
            @error($genderKey)
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="sm:col-span-2 lg:col-span-1">
            <label class="form-label" for="anak-{{ $index }}-pekerjaan">Pekerjaan <span class="font-normal text-slate-400">(opsional)</span></label>
            <input type="text" id="anak-{{ $index }}-pekerjaan" name="children[{{ $index }}][pekerjaan]"
                   value="{{ $anak['pekerjaan'] ?? '' }}" placeholder="Contoh: Pelajar / Mahasiswa"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($pekerjaanKey)])>
            @error($pekerjaanKey)
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
