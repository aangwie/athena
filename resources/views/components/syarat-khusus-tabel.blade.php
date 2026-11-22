{{-- Tabel Syarat Khusus DUPAK sesuai lampiran penilaian syarat khusus.
     Parameter: $item (entri PendukungDupak milik dosen, boleh null) dan
     $readonly (true untuk admin: hanya menampilkan hasil isian). --}}
@props(['item' => null, 'readonly' => false])

@php
    $barisList = \App\Models\PendukungDupak::BARIS;
@endphp

<div class="overflow-x-auto">
    <table class="w-full min-w-[56rem] text-left text-sm">
        <thead>
            <tr class="border-b border-slate-200 bg-slate-50/70 text-[10px] font-bold tracking-[0.12em] text-slate-500 uppercase">
                <th scope="col" class="w-12 px-4 py-3">No</th>
                <th scope="col" class="px-4 py-3">Persyaratan</th>
                <th scope="col" class="w-[22rem] px-4 py-3">Isian</th>
                <th scope="col" class="w-44 px-4 py-3">Bukti</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barisList as $nomor => $baris)
                @php
                    $field = $baris['field'];
                    $wajib = (bool) ($baris['wajib'] ?? false);
                    $berkas = $baris['tipe'] === 'berkas';
                    $nilai = $item?->{$field};
                    $pathBerkas = $berkas ? $item?->pathBerkas($field) : null;
                    $tautanBerkas = $berkas && filled($pathBerkas)
                        ? route('pendukung-dupak.berkas', ['pendukungDupak' => $item, 'field' => $field])
                        : null;
                @endphp

                <tr class="border-b border-slate-100 align-top last:border-0">
                    <td class="px-4 py-4 font-mono text-xs text-slate-400">{{ $nomor }}.</td>

                    <td class="px-4 py-4">
                        @if ($readonly)
                            <p class="text-[13px] leading-snug font-semibold text-slate-700">
                                {{ $baris['label'] }}
                                @if ($wajib)
                                    <span class="text-rose-500">*</span>
                                @endif
                            </p>
                        @else
                            <label for="{{ $field }}" class="text-[13px] leading-snug font-semibold text-slate-700">
                                {{ $baris['label'] }}
                                @if ($wajib)
                                    <span class="text-rose-500">*</span>
                                @endif
                            </label>
                        @endif
                        @if (filled($baris['catatan'] ?? null))
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-400">{{ $baris['catatan'] }}</p>
                        @endif
                    </td>

                    <td class="px-4 py-4">
                        @if ($readonly)
                            @switch($baris['tipe'])
                                @case('berkas')
                                    <p class="max-w-[20rem] truncate text-xs text-slate-700" title="{{ $item?->namaBerkas($field) }}">
                                        {{ $item?->namaBerkas($field) ?: '-' }}
                                    </p>
                                    @break

                                @case('tautan')
                                    <p class="max-w-[20rem] break-all text-xs text-slate-700">{{ filled($nilai) ? $nilai : '-' }}</p>
                                    @break

                                @case('tanggal')
                                    <p class="text-sm text-slate-800">{{ $nilai ? $nilai->translatedFormat('d/m/Y') : '-' }}</p>
                                    @break

                                @default
                                    <p class="text-sm text-slate-800">{{ filled($nilai) ? $nilai : '-' }}</p>
                            @endswitch
                        @else
                            {{-- Isian formulir dosen --}}
                            @switch($baris['tipe'])
                                @case('pilihan')
                                    <select id="{{ $field }}" name="{{ $field }}" @required($wajib)
                                            data-select-search="Cari {{ \Illuminate\Support\Str::lower($baris['label']) }}…"
                                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has($field)])>
                                        <option value="">-- Pilih --</option>
                                        @foreach ($baris['opsi'] as $opsi)
                                            <option value="{{ $opsi }}" @selected(old($field, $nilai) === $opsi)>{{ $opsi }}</option>
                                        @endforeach
                                    </select>
                                    @error($field) <p class="form-error">{{ $message }}</p> @enderror
                                    @break

                                @case('tanggal')
                                    <input type="date" id="{{ $field }}" name="{{ $field }}"
                                           value="{{ old($field, $nilai?->format('Y-m-d')) }}" @required($wajib)
                                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($field)])>
                                    @error($field) <p class="form-error">{{ $message }}</p> @enderror
                                    @break

                                @case('tautan')
                                    <input type="url" id="{{ $field }}" name="{{ $field }}"
                                           value="{{ old($field, $nilai) }}" placeholder="{{ $baris['placeholder'] }}"
                                           maxlength="2048" inputmode="url" @required($wajib)
                                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($field)])>
                                    @error($field) <p class="form-error">{{ $message }}</p> @enderror
                                    @break

                                @case('berkas')
                                    <p class="mb-1.5 text-[11px] font-semibold text-slate-500">
                                        File .PDF dengan maksimal ukuran {{ \App\Models\PendukungDupak::labelUkuran($baris['maks_kb']) }}
                                        @if ($wajib)
                                            <span class="text-rose-500">*</span>
                                        @endif
                                    </p>
                                    <div data-file-field>
                                        <input type="file" id="{{ $field }}" name="{{ $field }}"
                                               accept="application/pdf,.pdf" data-pdf-input
                                               data-max-kb="{{ $baris['maks_kb'] }}"
                                               @required($wajib && ! filled($pathBerkas))
                                               @class([
                                                   'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                                   'border-rose-300 bg-rose-50/40' => $errors->has($field),
                                                   'border-slate-200' => ! $errors->has($field),
                                               ])>
                                        <p data-file-error class="form-error hidden" role="alert"></p>
                                        @error($field) <p class="form-error">{{ $message }}</p> @enderror
                                    </div>
                                    @if (filled($pathBerkas))
                                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                            Berkas saat ini:
                                            <span class="font-mono text-teal-700">{{ $item->namaBerkas($field) }}</span>.
                                            Kosongkan jika tidak ingin mengganti.
                                        </p>
                                    @endif
                                    @break

                                @default
                                    <input type="text" id="{{ $field }}" name="{{ $field }}"
                                           value="{{ old($field, $nilai) }}" placeholder="{{ $baris['placeholder'] ?? '' }}"
                                           maxlength="{{ $baris['max'] }}" @required($wajib)
                                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has($field)])>
                                    @error($field) <p class="form-error">{{ $message }}</p> @enderror
                            @endswitch
                        @endif
                    </td>

                    <td class="px-4 py-4 text-[11px] leading-relaxed text-slate-400">
                        @if ($berkas)
                            @if (filled($tautanBerkas))
                                <a href="{{ $tautanBerkas }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                        <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                                    </svg>
                                    Lihat Berkas
                                </a>
                            @elseif (! $readonly)
                                <span>Unggah Bukti</span>
                            @else
                                <span>-</span>
                            @endif
                        @elseif ($baris['tipe'] === 'tautan')
                            @if (filled($nilai))
                                <a href="{{ $nilai }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Buka Tautan
                                </a>
                            @elseif (! $readonly)
                                <span>Unggah Tautan</span>
                            @endif
                        @endif

                        @if (filled($baris['keterangan'] ?? null))
                            <p class="mt-1.5">{{ $baris['keterangan'] }}</p>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
