@extends('layouts.app')

@section('title', 'Riwayat Karya Tulis')
@section('breadcrumb', 'Riwayat Karya Tulis')
@section('page-title', 'Riwayat Karya Tulis')

@section('content')
    @php
        $isEdit = $editItem !== null;
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Karya Tulis</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Catat karya ilmiah yang pernah Anda hasilkan beserta tautan bukti Google Drive-nya.
                </p>
            </div>

            <a href="{{ route('profil.show') }}" class="btn-secondary shrink-0 justify-center">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Profil
            </a>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Periksa kembali data Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">
                        Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum data dapat disimpan.
                    </p>
                </div>
            </div>
        @endif

        {{-- Formulir tambah/edit karya tulis --}}
        <form id="form-karya-tulis" method="POST"
              action="{{ $isEdit ? route('profil.karya-tulis.update', $editItem) : route('profil.karya-tulis.store') }}"
              class="mb-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">
                            {{ $isEdit ? 'Edit Karya Tulis' : 'Tambah Karya Tulis' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400">Isi jenis karya dan judul, lalu tempelkan tautan bukti dari Google Drive.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="karya_ilmiah" class="form-label">Karya Ilmiah <span class="text-rose-500">*</span></label>
                            <select id="karya_ilmiah" name="karya_ilmiah" required
                                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('karya_ilmiah')])>
                                <option value="">Pilih jenis karya ilmiah</option>
                                @foreach ($jenisList as $jenis)
                                    <option value="{{ $jenis }}" @selected(old('karya_ilmiah', $editItem?->karya_ilmiah) === $jenis)>
                                        {{ $jenis }}
                                    </option>
                                @endforeach
                            </select>
                            @error('karya_ilmiah') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="judul" class="form-label">Judul <span class="text-rose-500">*</span></label>
                            <input type="text" id="judul" name="judul" value="{{ old('judul', $editItem?->judul) }}"
                                   placeholder="Judul lengkap karya ilmiah" maxlength="255" required
                                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('judul')])>
                            @error('judul') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="link_bukti" class="form-label">Link Bukti Google Drive <span class="text-rose-500">*</span></label>
                        <input type="url" id="link_bukti" name="link_bukti" value="{{ old('link_bukti', $editItem?->link_bukti) }}"
                               placeholder="https://drive.google.com/file/d/…/view" data-link-input inputmode="url" required
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
                            Pastikan akses berkas di Google Drive disetel "Anyone with the link" agar tautan dapat dibuka.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    @if ($isEdit)
                        <a href="{{ route('profil.karya-tulis.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                    @endif
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Karya Tulis' }}
                    </button>
                </div>
            </section>
        </form>

        {{-- Daftar karya tulis --}}
        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4.5 5.5A1.5 1.5 0 0 1 6 4h5v16H6a1.5 1.5 0 0 1-1.5-1.5v-13Z" stroke-linejoin="round" />
                            <path d="M19.5 5.5A1.5 1.5 0 0 0 18 4h-5v16h5a1.5 1.5 0 0 0 1.5-1.5v-13Z" stroke-linejoin="round" />
                            <path d="M12 4v16" stroke-linecap="round" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Karya Tulis</h3>
                            <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600">{{ $items->count() }} data</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">Buka tautan bukti untuk memeriksa berkas di Google Drive.</p>
                    </div>
                </div>

                <div class="relative w-full lg:w-72">
                    <label for="karya-tulis-search" class="sr-only">Cari karya tulis</label>
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.13 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                    <input type="search" id="karya-tulis-search" data-table-search placeholder="Cari karya ilmiah atau judul…"
                           class="form-input pl-9">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="no-sort w-16">No</th>
                            <th scope="col">Karya Ilmiah</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Bukti</th>
                            <th scope="col" class="no-sort text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                                        {{ $item->karya_ilmiah }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <p class="max-w-[20rem] truncate font-semibold text-slate-800" title="{{ $item->judul }}">{{ $item->judul }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $item->created_at->translatedFormat('d M Y') }}</p>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <a href="{{ $item->link_bukti }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            Buka
                                        </a>
                                        <a href="{{ $item->linkPratinjau() }}" target="_blank" rel="noopener noreferrer"
                                           class="text-[11px] font-semibold text-slate-500 underline underline-offset-2 transition hover:text-teal-700">
                                            Pratinjau
                                        </a>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('profil.karya-tulis.index', ['edit' => $item->id]) }}#form-karya-tulis"
                                           title="Edit" aria-label="Edit"
                                           class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('profil.karya-tulis.destroy', $item) }}"
                                              data-confirm="Hapus karya tulis &quot;{{ $item->judul }}&quot;? Tindakan ini tidak dapat dibatalkan.">
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
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path d="M4.5 5.5A1.5 1.5 0 0 1 6 4h5v16H6a1.5 1.5 0 0 1-1.5-1.5v-13Z" stroke-linejoin="round" />
                                            <path d="M19.5 5.5A1.5 1.5 0 0 0 18 4h-5v16h5a1.5 1.5 0 0 0 1.5-1.5v-13Z" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada karya tulis</p>
                                    <p class="mt-1 text-xs text-slate-400">Tambahkan melalui formulir di atas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
