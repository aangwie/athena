<p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
    @if ($pengantarEdit)
        Ubah surat pengantar nomor <span class="font-semibold text-slate-700">{{ $pengantarEdit->nomor_surat }}</span>.
    @else
        Surat pengantar usulan jabatan akademik dosen — satu dosen (pribadi) atau beberapa dosen (kolektif).
    @endif
</p>

@if ($errors->any())
    <div class="mt-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
            <p class="font-bold">Periksa kembali data Anda.</p>
            <p class="mt-0.5 text-xs text-rose-600">
                Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum surat pengantar dapat disimpan.
            </p>
        </div>
    </div>
@endif

<form method="POST"
      action="{{ $pengantarEdit ? route('surat.pengantar.update', $pengantarEdit) : route('surat.pengantar.store') }}"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
    @csrf
    @if ($pengantarEdit)
        @method('PUT')
    @endif

    <h4 class="text-sm font-extrabold tracking-tight text-slate-800">Data Surat Pengantar</h4>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="nomor_surat" class="form-label">
                Nomor Surat <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="nomor_surat" name="nomor_surat" required maxlength="100"
                   value="{{ old('nomor_surat', $pengantarEdit?->nomor_surat) }}" placeholder="Contoh: 613/STKIP PGRI/KP/III/2026"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nomor_surat')])>
            @error('nomor_surat') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tanggal_surat" class="form-label">
                Tanggal Surat <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="tanggal_surat" name="tanggal_surat" required
                   value="{{ old('tanggal_surat', $pengantarEdit?->tanggal_surat?->format('d-m-Y')) }}" placeholder="DD-MM-YYYY"
                   maxlength="10" inputmode="numeric" autocomplete="off" data-date-mask
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_surat')])>
            @error('tanggal_surat') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Format tanggal: DD-MM-YYYY.</p>
        </div>

        <div>
            <label for="lampiran" class="form-label">Lampiran</label>
            <input type="text" id="lampiran" name="lampiran" maxlength="100"
                   value="{{ old('lampiran', $pengantarEdit?->lampiran) }}" placeholder="Contoh: 1 berkas"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('lampiran')])>
            @error('lampiran') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label for="perihal" class="form-label">
                Perihal <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="perihal" name="perihal" required maxlength="255"
                   value="{{ old('perihal', $pengantarEdit?->perihal) }}" placeholder="Contoh: Usulan Jabatan Akademik"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('perihal')])>
            @error('perihal') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tujuan" class="form-label">Tujuan Surat (Yth.)</label>
            <input type="text" id="tujuan" name="tujuan" maxlength="150"
                   value="{{ old('tujuan', $pengantarEdit?->tujuan) }}" placeholder="Contoh: Kepala LLDIKTI Wilayah VII Jawa Timur"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tujuan')])>
            @error('tujuan') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
            <label class="choice-card items-center">
                <input type="checkbox" name="kolektif" value="1" class="sr-only"
                       @checked(old('kolektif', $pengantarEdit?->kolektif))>
                <span class="choice-indicator">
                    <svg class="size-3 opacity-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                    </svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-bold text-slate-800">Kolektif</span>
                    <span class="mt-0.5 block text-[11px] text-slate-500">
                        Centang bila surat memuat lebih dari satu dosen (dicetak sebagai tabel).
                        Tanpa centang, surat berisi satu dosen.
                    </span>
                </span>
            </label>
            @error('kolektif') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
            <label for="pengantar_dosen" class="form-label">
                Nama Dosen <span class="text-rose-500">*</span>
            </label>
            <select id="pengantar_dosen" name="dosen_ids[]" multiple required size="6"
                    data-select-search="Cari nama dosen…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('dosen_ids')])>
                @foreach ($dosenList as $dosen)
                    <option value="{{ $dosen->id }}"
                            @selected(in_array((string) $dosen->id, array_map('strval', old('dosen_ids', $pengantarEdit?->dosen_ids ?? [])), true))>
                        {{ $dosen->name }} — {{ $dosen->nidn }}
                    </option>
                @endforeach
            </select>
            @error('dosen_ids') <p class="form-error">{{ $message }}</p> @enderror
            @error('dosen_ids.*') <p class="form-error">{{ $message }}</p> @enderror

            <p class="mt-1.5 text-[11px] text-slate-400">
                Tahan Ctrl (Windows) atau Cmd (macOS) untuk memilih lebih dari satu dosen.
                @if ($dosenList->isEmpty())
                    Belum ada dosen aktif yang dapat dipilih.
                @endif
            </p>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        @if ($pengantarEdit)
            <a href="{{ route('surat.index') }}#surat-pengantar" class="btn-secondary w-auto px-5 whitespace-nowrap">
                Batal
            </a>
        @endif
        <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
            </svg>
            {{ $pengantarEdit ? 'Perbarui' : 'Simpan' }}
        </button>
    </div>
</form>

<div data-table-card class="mt-5 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h4 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Surat Pengantar</h4>
            <p class="mt-1 text-xs text-slate-500">Surat pengantar yang sudah disimpan, terbaru di atas.</p>
        </div>

        <label class="relative block">
            <span class="sr-only">Cari surat pengantar</span>
            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.5" cy="10.5" r="6.5" />
                    <path d="m16 16 4 4" stroke-linecap="round" />
                </svg>
            </span>
            <input type="search" data-table-search placeholder="Cari nama atau nomor surat…"
                   class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-72">
            <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </label>
    </div>

    <div class="overflow-x-auto px-2 pb-2 sm:px-4">
        <table data-datatable data-page-length="10" class="w-full text-left text-sm">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">No Surat</th>
                    <th scope="col">Tanggal Surat</th>
                    <th scope="col" class="no-sort text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>

                @forelse ($pengantarList as $pengantar)
                    @php
                        $dosenPengantar = $pengantar->dosenList();
                    @endphp
                    <tr>
                        <td>
                            @if ($pengantar->kolektif)
                                <p class="text-sm font-bold text-slate-700">{{ $dosenPengantar->count() }} dosen</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">
                                    Kolektif · {{ $dosenPengantar->take(2)->pluck('name')->implode(', ') }}{{ $dosenPengantar->count() > 2 ? ', …' : '' }}
                                </p>
                            @else
                                <p class="text-sm font-bold text-slate-700">{{ $dosenPengantar->first()?->name ?? '—' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">Pribadi</p>
                            @endif
                        </td>
                        <td>
                            <p class="text-sm font-semibold text-slate-700">{{ $pengantar->nomor_surat }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $pengantar->perihal }}</p>
                        </td>
                        <td class="whitespace-nowrap">
                            <span class="text-xs text-slate-600">{{ $pengantar->tanggal_surat?->format('d-m-Y') ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('surat.pengantar.cetak', $pengantar) }}"
                                   target="_blank" rel="noopener" title="Print" aria-label="Print"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M7 9V4h10v5" stroke-linejoin="round" />
                                        <rect x="4" y="9" width="16" height="7" rx="2" />
                                        <path d="M7 14h10v5.5H7z" stroke-linejoin="round" />
                                    </svg>
                                </a>
                                <a href="{{ route('surat.index', ['edit_pengantar' => $pengantar->id]) }}#surat-pengantar"
                                   title="Edit" aria-label="Edit"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                        <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('surat.pengantar.destroy', $pengantar) }}"
                                      data-confirm="Hapus surat pengantar nomor {{ $pengantar->nomor_surat }}? Tindakan ini tidak dapat dibatalkan.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" aria-label="Hapus"
                                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center">
                            <p class="text-sm font-bold text-slate-700">Belum ada surat pengantar</p>
                            <p class="mt-1 text-xs text-slate-400">Isi formulir di atas untuk menambahkan surat pengantar pertama.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


