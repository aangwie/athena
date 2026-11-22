@extends('layouts.app')

@section('title', 'Syarat Khusus DUPAK')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Syarat Khusus DUPAK')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Administrasi
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Syarat Khusus DUPAK</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Pilih dosen terlebih dahulu untuk menampilkan hasil isian syarat khusus DUPAK-nya.
                Halaman ini hanya menampilkan data; pengisian dilakukan dosen pengusul.
            </p>
        </div>

        {{-- Pemilihan dosen: tabel syarat khusus baru tampil setelah dosen dipilih. --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">01</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Pilih Dosen</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Syarat khusus ditampilkan per dosen pengusul.</p>
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
                                {{ $dosen->name }} — {{ $dosen->nidn }} (sudah mengisi)
                            </option>
                        @endforeach
                    </select>

                    @if ($dosenList->isEmpty())
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Belum ada dosen yang mengirim syarat khusus DUPAK.
                        </p>
                    @else
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Hanya dosen yang sudah mengirim syarat khusus yang ditampilkan.
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

        @if ($dosenTerpilih && $item)
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
                    Diisi {{ $item->updated_at?->translatedFormat('d M Y') }}
                </span>
            </div>

            <section class="section-card overflow-hidden">
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Penilaian untuk Syarat Khusus</h3>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Hasil isian dosen pengusul. Berkas bukti dapat dibuka untuk keperluan verifikasi.
                        </p>
                    </div>
                </div>

                <x-syarat-khusus-tabel :item="$item" :readonly="true" />
            </section>
        @elseif ($dosenTerpilih)
            <section class="section-card px-5 py-16 text-center sm:px-6">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                        <path d="M14 3.5V8h4.5M9 13h6M9 16.5h4" stroke-linecap="round" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-700">Belum ada isian syarat khusus</p>
                <p class="mt-1 text-xs text-slate-400">Dosen ini belum mengirim syarat khusus DUPAK.</p>
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
                <p class="mt-1 text-xs text-slate-400">Tabel syarat khusus DUPAK akan tampil setelah dosen dipilih di atas.</p>
            </section>
        @endif
    </div>
@endsection

