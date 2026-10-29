{{-- Bidang A — Pendidikan --}}
@php
    $entriEdit = $entriEdit ?? null;
    $isEdit = $entriEdit !== null;
    $tahunForm = old('tahun', $entriEdit?->tahun ?? $tahun);
    $semesterForm = old('semester', $entriEdit?->semester ?? $semester);
@endphp
<form method="POST"
      action="{{ $isEdit ? route('profil.pengajuan.pendidikan.update', $entriEdit) : route('profil.pengajuan.pendidikan.store') }}"
      class="rounded-xl border bg-slate-50/60 p-4 sm:p-5"
      @class(['border-teal-300 ring-4 ring-teal-500/10' => $isEdit, 'border-slate-200' => ! $isEdit])>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif
    {{-- Periode ikut terkirim pada setiap input komponen --}}
    <input type="hidden" name="tahun" value="{{ $tahunForm }}">
    <input type="hidden" name="semester" value="{{ $semesterForm }}">

    <div class="flex items-center gap-2.5">
        <span class="section-number">A</span>
        <div>
            <h4 class="text-sm font-extrabold text-slate-800">
                {{ $isEdit ? 'Edit Entri Bidang A' : 'Bidang A — Pendidikan' }}
            </h4>
            <p class="mt-0.5 text-xs text-slate-400">
                {{ $isEdit
                    ? 'Perbarui komponen, nama kegiatan, atau link bukti entri ini.'
                    : 'Pilih komponen, lalu lampirkan link bukti pendukungnya.' }}
            </p>
        </div>
        <span class="ml-auto whitespace-nowrap rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
            {{ \App\Models\KegiatanDupak::labelPeriode((int) $tahunForm, (string) $semesterForm) }}
        </span>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <label for="jenis" class="form-label">Komponen <span class="text-rose-500">*</span></label>
            <select id="jenis" name="jenis" data-reveal-select="#bukti-pendidikan"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jenis')])>
                <option value="">Pilih komponen pendidikan</option>
                @foreach (\App\Models\KegiatanDupak::JENIS_PENDIDIKAN as $value => $label)
                    <option value="{{ $value }}" @selected(old('jenis', $entriEdit?->jenis) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('jenis') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div id="bukti-pendidikan" class="hidden space-y-4 sm:col-span-2">
            <div>
                <label for="nama_kegiatan" class="form-label">Nama Kegiatan <span class="text-rose-500">*</span></label>
                <input type="text" id="nama_kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan', $entriEdit?->nama_kegiatan) }}"
                       placeholder="Contoh: Diklat Prajabatan CPNS Golongan III Angkatan XLV"
                       maxlength="200"
                       @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama_kegiatan')])>
                @error('nama_kegiatan') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="link_bukti" class="form-label">Link Bukti Google Drive <span class="text-rose-500">*</span></label>
                <input type="url" id="link_bukti" name="link_bukti" value="{{ old('link_bukti', $entriEdit?->link_bukti) }}"
                       placeholder="https://drive.google.com/file/d/…/view" data-link-input inputmode="url"
                       @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('link_bukti')])>
                @error('link_bukti') <p class="form-error">{{ $message }}</p> @enderror

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
                <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                    Pastikan akses berkas di Google Drive disetel "Anyone with the link" agar dapat diperiksa.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-end gap-2">
        @if ($isEdit)
            <a href="{{ route('profil.pengajuan.create', ['tahun' => $tahun, 'semester' => $semester]) }}#pendidikan" class="btn-secondary w-auto whitespace-nowrap">Batal</a>
        @endif
        <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                @if ($isEdit)
                    <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                    <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                @else
                    <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                @endif
            </svg>
            {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Entri' }}
        </button>
    </div>
</form>

{{-- Daftar entri bidang A --}}
<div data-table-card class="mt-5">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-sm font-extrabold tracking-tight text-slate-900">Daftar Entri Bidang A</h4>
                <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                    {{ $kegiatanPendidikan->count() }} data
                </span>
            </div>

            <label class="relative block">
                <span class="sr-only">Cari entri bidang A</span>
                <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="10.5" cy="10.5" r="6.5" />
                        <path d="m16 16 4 4" stroke-linecap="round" />
                    </svg>
                </span>
                <input type="search" data-table-search placeholder="Cari komponen atau link…"
                       class="h-9 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-64">
                <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                    </svg>
                </button>
            </label>
        </div>

        <div class="overflow-x-auto px-2 pb-2 sm:px-4">
            <table data-datatable data-page-length="5" class="w-full text-left text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="w-10">No</th>
                        <th scope="col">Komponen</th>
                        <th scope="col">Nama Kegiatan</th>
                        <th scope="col">Link Bukti</th>
                        <th scope="col">Periode</th>
                        <th scope="col">Ditambahkan</th>
                        <th scope="col" class="no-sort text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kegiatanPendidikan as $entri)
                        <tr @class(['bg-teal-50/50' => $isEdit && $entriEdit->is($entri)])>
                            <td class="text-xs text-slate-400">{{ $loop->iteration }}</td>
                            <td>
                                <span class="badge-status bg-teal-50 text-teal-700 ring-teal-200">
                                    <span class="size-1.5 rounded-full bg-teal-500"></span>
                                    {{ $entri->jenisLabel() }}
                                </span>
                            </td>
                            <td>
                                <p class="max-w-[18rem] truncate text-xs font-bold text-slate-700" title="{{ $entri->nama_kegiatan }}">
                                    {{ $entri->nama_kegiatan }}
                                </p>
                            </td>
                            <td>
                                <a href="{{ $entri->link_bukti }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex max-w-[16rem] items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="truncate underline underline-offset-2">{{ $entri->link_bukti }}</span>
                                </a>
                            </td>
                            <td data-order="{{ $entri->tahun.($entri->semester === 'genap' ? '2' : '1') }}">
                                <span class="text-xs whitespace-nowrap text-slate-600">{{ $entri->periodeLabel() }}</span>
                            </td>
                            <td data-order="{{ $entri->created_at->timestamp }}">
                                <span class="text-xs whitespace-nowrap text-slate-500">{{ $entri->created_at->translatedFormat('d M Y') }}</span>
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('profil.pengajuan.create', ['edit' => $entri->id, 'tahun' => $entri->tahun, 'semester' => $entri->semester]) }}#pendidikan"
                                       class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-teal-50 hover:text-teal-700"
                                       title="Edit entri" aria-label="Edit entri {{ $entri->jenisLabel() }}">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="m4.5 16.5-.9 3.9 3.9-.9L19 8a2.1 2.1 0 0 0-3-3L4.5 16.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M14.5 6.5 17.5 9.5" stroke-linecap="round" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('profil.pengajuan.pendidikan.destroy', $entri) }}"
                                          data-confirm="Hapus entri {{ $entri->jenisLabel() }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                title="Hapus entri" aria-label="Hapus entri {{ $entri->jenisLabel() }}">
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
                            <td colspan="7" class="px-5 py-14 text-center">
                                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path d="M6 3.5h8l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5Z" stroke-linejoin="round" />
                                        <path d="M14 3.5v4.5h4M9 13h6M9 16.5h4" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <p class="mt-3 text-sm font-bold text-slate-700">Belum ada entri bidang A</p>
                                <p class="mt-1 text-xs text-slate-400">Pilih komponen di atas, lalu simpan link buktinya.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
