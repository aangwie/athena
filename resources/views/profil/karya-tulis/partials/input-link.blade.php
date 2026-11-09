{{-- Bidang URL dengan pratinjau tautan (dipakai link bukti, alamat web jurnal, URL prosiding, URL plagiasi).
     Parameter: $name, $label, $value, $placeholder, $required (bool, tampil wajib), $hint (opsional),
     $wajib (bool, penanda `data-wajib` agar JS dapat mengaktifkan required saat grup tampil),
     $disabled (bool, grup sedang tidak tampil sehingga isian tidak terkirim). --}}
@php
    $tampilWajib = $required ?? false;
    $wajib = $wajib ?? $tampilWajib;
    $catatan = $hint ?? null;
    $mati = $disabled ?? false;
@endphp
<div>
    <label for="{{ $name }}" class="form-label">
        {{ $label }} @if ($wajib)<span class="text-rose-500">*</span>@endif
    </label>
    <input type="url" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}"
           placeholder="{{ $placeholder }}" data-link-input inputmode="url" @required($tampilWajib) @disabled($mati)
           @if ($wajib) data-wajib @endif
           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($name)])>
    @error($name) <p class="form-error">{{ $message }}</p> @enderror

    <div data-link-preview class="mt-2 hidden items-center gap-2.5 rounded-xl border border-teal-100 bg-teal-50/60 px-3 py-2">
        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-teal-600 shadow-sm ring-1 ring-teal-100">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <div class="min-w-0">
            <p data-link-host class="text-[11px] font-bold text-teal-800"></p>
            <a data-link-url href="#" target="_blank" rel="noopener noreferrer"
               class="block truncate text-[11px] text-teal-600 underline underline-offset-2"></a>
        </div>
    </div>
    <p data-link-invalid class="form-error hidden" role="alert">Link belum valid, sertakan http:// atau https://.</p>

    @if ($catatan)
        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">{{ $catatan }}</p>
    @endif
</div>
