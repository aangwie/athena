@extends('layouts.app')

@section('title', 'Pendukung DUPAK')
@section('breadcrumb', 'Pendukung DUPAK')
@section('page-title', 'Pendukung DUPAK')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Administrasi
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Pendukung DUPAK</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Pilih dosen terlebih dahulu untuk menampilkan dokumen pendukung DUPAK-nya: check similarity,
                bukti korespondensi, tautan profil SINTA, dan artikel. Halaman ini hanya menampilkan data;
                pengelolaan dilakukan dosen pengusul.
            </p>
        </div>

        {{-- Pemilihan dosen: tabel dokumen baru tampil setelah dosen dipilih. --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">01</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Pilih Dosen</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Dokumen pendukung ditampilkan per dosen pengusul.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('pendukung-dupak.index') }}"
                  class="flex flex-col gap-3 px-5 py-5 sm:flex-row sm:items-end sm:px-6">
                <div class="w-full sm:max-w-md">
                    <label for="dosen" class="form-label">Nama Dosen <span class="text-rose-500">*</span></label>
                    <select id="dosen" name="dosen" required
                            data-select-search="Cari nama dosen…"
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('dosen')])>
                        <option value="">Pilih dosen</option>
                        @foreach ($dosenList as $dosen)
                            <option value="{{ $dosen->id }}" @selected($dosenTerpilih?->id === $dosen->id)>
                                {{ $dosen->name }} — {{ $dosen->nidn }} ({{ $jumlahDokumen[$dosen->id] }} dokumen)
                            </option>
                        @endforeach
                    </select>

                    @if ($dosenList->isEmpty())
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Belum ada dosen yang mengunggah dokumen pendukung DUPAK.
                        </p>
                    @else
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Hanya dosen yang sudah memiliki dokumen pendukung yang ditampilkan.
                        </p>
                    @endif
                </div>

                <button type="submit" class="btn-primary w-auto justify-center px-5" @disabled($dosenList->isEmpty())>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <circle cx="10.5" cy="10.5" r="6.5" />
                        <path d="m16 16 4 4" stroke-linecap="round" />
                    </svg>
                    Tampilkan
                </button>
            </form>
        </section>

        @if ($dosenTerpilih)
            {{-- Identitas dosen yang dipilih --}}
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-teal-100 bg-teal-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-600 text-[11px] font-bold text-white">
                        {{ $dosenTerpilih->initials() }}
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-extrabold text-slate-800">{{ $dosenTerpilih->name }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">
                            NIDN {{ $dosenTerpilih->nidn ?: '—' }}
                            · {{ $dosenTerpilih->program_studi ?: 'Prodi belum diisi' }}
                            · {{ $dosenTerpilih->jabatan_fungsional ?: 'Belum ada jabatan akademik' }}
                        </p>
                    </div>
                </div>
                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                    {{ $items->count() }} dokumen
                </span>
            </div>

            <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                            <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                            <path d="m9 14 2 2 3.5-4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold tracking-tight text-slate-900">Dokumen Pendukung</h3>
                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">{{ $items->count() }} data</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">Dokumen pendukung DUPAK milik dosen yang dipilih.</p>
                    </div>
                </div>

                <label class="relative block sm:w-64">
                    <span class="sr-only">Cari dokumen pendukung</span>
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" />
                            <path d="m16 16 4 4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input type="search" data-table-search placeholder="Cari jenis dokumen…"
                           class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none">
                    <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </label>
            </div>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="no-sort w-16">No</th>
                            <th scope="col">Jenis Dokumen</th>
                            <th scope="col">Berkas / Tautan</th>
                            <th scope="col">Diunggah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                                        {{ $item->labelJenis() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($item->memakaiBerkas())
                                        <a href="{{ route('pendukung-dupak.berkas', $item) }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                                <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                                            </svg>
                                            Lihat Berkas
                                        </a>
                                        <p class="mt-0.5 max-w-[18rem] truncate text-[11px] text-slate-400" title="{{ $item->namaBerkas() }}">
                                            {{ $item->namaBerkas() }}
                                        </p>
                                    @else
                                        <a href="{{ $item->tautan }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            {{ $item->hostTautan() }}
                                        </a>
                                        <p class="mt-0.5 max-w-[18rem] truncate text-[11px] text-slate-400" title="{{ $item->tautan }}">
                                            {{ $item->tautan }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $item->created_at->translatedFormat('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-16 text-center">
                                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                            <path d="M14 3.5V8h4.5M9 13h6M9 16.5h4" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada dokumen pendukung</p>
                                    <p class="mt-1 text-xs text-slate-400">Dosen ini belum mengunggah dokumen pendukung DUPAK.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </section>
        @else
            {{-- Belum ada dosen dipilih: tabel belum ditampilkan. --}}
            <section class="section-card px-5 py-16 text-center sm:px-6">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="12" cy="8" r="3.5" />
                        <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-700">Pilih dosen terlebih dahulu</p>
                <p class="mt-1 text-xs text-slate-400">Tabel dokumen pendukung DUPAK akan tampil setelah dosen dipilih di atas.</p>
            </section>
        @endif
    </div>
@endsection
