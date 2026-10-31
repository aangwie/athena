<p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
    @if ($paktaEdit)
        Ubah pakta integritas untuk <span class="font-semibold text-slate-700">{{ $paktaEdit->dosen?->name }}</span>.
    @else
        Catat pakta integritas keabsahan karya ilmiah dosen beserta tanggal pakta.
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
                Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum pakta dapat disimpan.
            </p>
        </div>
    </div>
@endif

<form method="POST"
      action="{{ $paktaEdit ? route('surat.pakta-integritas.update', $paktaEdit) : route('surat.pakta-integritas.store') }}"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
    @csrf
    @if ($paktaEdit)
        @method('PUT')
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="pakta_dosen_id" class="form-label">
                Nama Dosen <span class="text-rose-500">*</span>
            </label>
            <select id="pakta_dosen_id" name="dosen_id" required
                    data-select-search="Cari nama dosen…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('dosen_id')])>
                <option value="">Pilih dosen</option>
                @foreach ($dosenList as $dosen)
                    <option value="{{ $dosen->id }}" @selected((string) old('dosen_id', $paktaEdit?->user_id) === (string) $dosen->id)>
                        {{ $dosen->name }} — {{ $dosen->nidn }}
                    </option>
                @endforeach
            </select>
            @error('dosen_id') <p class="form-error">{{ $message }}</p> @enderror

            @if ($dosenList->isEmpty())
                <p class="mt-1.5 text-[11px] text-amber-600">
                    Belum ada dosen aktif yang dapat dipilih.
                </p>
            @endif
        </div>

        <div>
            <label for="tanggal_pakta" class="form-label">
                Tanggal Pakta <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="tanggal_pakta" name="tanggal_pakta" required
                   value="{{ old('tanggal_pakta', $paktaEdit?->tanggal_pakta?->format('d-m-Y')) }}" placeholder="DD-MM-YYYY"
                   maxlength="10" inputmode="numeric" autocomplete="off" data-date-mask
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_pakta')])>
            @error('tanggal_pakta') <p class="form-error">{{ $message }}</p> @enderror

            <p class="mt-1.5 text-[11px] text-slate-400">
                Format tanggal: DD-MM-YYYY. Tempat: {{ $kota ?: 'belum diisi' }}
                @if (! $kota)
                    — <a href="{{ route('instansi.profil.edit') }}" class="font-bold underline">atur kota instansi</a>.
                @endif
            </p>
        </div>
    </div>

    <div class="mt-5 border-t border-slate-100 pt-4" data-karya-picker
         data-selected="{{ implode(',', old('karya_tulis_ids', $paktaEdit?->karya_tulis_ids ?? [])) }}"
         data-dosen-target="#pakta_dosen_id">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p class="form-label mb-0">Karya Ilmiah pada Surat</p>
            <p class="text-[11px] font-semibold text-slate-500" data-karya-info>Pilih dosen terlebih dahulu.</p>
        </div>

        <div class="mt-2 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
            <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-bold text-slate-600">
                <input type="checkbox" data-karya-all
                       class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30">
                Pilih semua karya ilmiah
            </label>

            <p class="mt-2 text-xs text-slate-400" data-karya-empty>
                Belum ada karya ilmiah yang terdaftar untuk dosen ini.
            </p>

            <div class="mt-2 grid gap-2 sm:grid-cols-2" data-karya-list role="group" aria-label="Karya ilmiah dosen"></div>
        </div>

        @error('karya_tulis_ids') <p class="form-error">{{ $message }}</p> @enderror
        @error('karya_tulis_ids.*') <p class="form-error">{{ $message }}</p> @enderror

        <p class="mt-1.5 text-[11px] text-slate-400">
            Karya yang dicentang akan dicetak pada tabel surat. Tanpa centang berarti semua karya ilmiah dosen dicetak.
        </p>

        <script type="application/json" data-karya-data>@json($karyaPerDosen)</script>
    </div>

    <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        @if ($paktaEdit)
            <a href="{{ route('surat.index') }}#pakta-integritas" class="btn-secondary w-auto px-5 whitespace-nowrap">
                Batal
            </a>
        @endif
        <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
            </svg>
            {{ $paktaEdit ? 'Perbarui' : 'Simpan' }}
        </button>
    </div>
</form>

<div data-table-card class="mt-5 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h4 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Pakta Integritas</h4>
            <p class="mt-1 text-xs text-slate-500">Surat yang sudah disimpan, terbaru di atas.</p>
        </div>

        <label class="relative block">
            <span class="sr-only">Cari pakta integritas</span>
            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.5" cy="10.5" r="6.5" />
                    <path d="m16 16 4 4" stroke-linecap="round" />
                </svg>
            </span>
            <input type="search" data-table-search placeholder="Cari dosen…"
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
                    <th scope="col">Nama Dosen</th>
                    <th scope="col">Tempat dan Tanggal Pakta</th>
                    <th scope="col" class="no-sort text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($paktaList as $pakta)
                    <tr>
                        <td>
                            <p class="text-sm font-bold text-slate-700">{{ $pakta->dosen?->name ?? '—' }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $pakta->dosen?->nidn }}</p>
                        </td>
                        <td>
                            <p class="font-semibold text-slate-600">{{ $kota ?: '—' }}, {{ $pakta->tanggal_pakta?->format('d-m-Y') }}</p>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('surat.pakta-integritas.cetak', $pakta) }}"
                                   target="_blank" rel="noopener" title="Print" aria-label="Print"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M7 9V4h10v5" stroke-linejoin="round" />
                                        <rect x="4" y="9" width="16" height="7" rx="2" />
                                        <path d="M7 14h10v5.5H7z" stroke-linejoin="round" />
                                    </svg>
                                </a>
                                <a href="{{ route('surat.index', ['edit_pakta' => $pakta->id]) }}#pakta-integritas"
                                   title="Edit" aria-label="Edit"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                        <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('surat.pakta-integritas.destroy', $pakta) }}"
                                      data-confirm="Hapus pakta integritas untuk {{ $pakta->dosen?->name }}? Tindakan ini tidak dapat dibatalkan.">
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
                        <td colspan="3" class="px-5 py-12 text-center">
                            <p class="text-sm font-bold text-slate-700">Belum ada pakta integritas</p>
                            <p class="mt-1 text-xs text-slate-400">Isi formulir di atas untuk menambahkan surat pertama.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
