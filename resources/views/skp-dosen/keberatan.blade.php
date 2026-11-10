@extends('layouts.app')

@section('title', 'Ajukan Keberatan SKP')
@section('breadcrumb', 'SKP-Dosen')
@section('page-title', 'Ajukan Keberatan SKP')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('skp-dosen.index') }}"
               class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-800">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M14 6.5 8.5 12l5.5 5.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Kembali ke SKP-Dosen
            </a>

            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Penilaian
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Ajukan Keberatan SKP</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Ajukan keberatan atas nilai SKP yang Anda terima beserta tanggal pengajuannya. Keberatan tercetak pada
                blok 5 halaman cetak SKP.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        {{-- Ringkasan penilaian yang diajukan keberatan. --}}
        <section class="section-card mb-6">
            <div class="section-heading">
                <span class="section-number">01</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Penilaian yang Diberatkan</h3>
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $skp->dosen?->name ?? '—' }} — {{ $skp->periodeLabel() }}
                        (Prodi {{ $skp->prodi?->nama_prodi ?? '—' }})
                    </p>
                </div>
            </div>

            <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6">
                <div>
                    <p class="text-[11px] font-bold tracking-[0.1em] text-slate-400 uppercase">Pejabat Penilai</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $skp->pejabatPenilai?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-[0.1em] text-slate-400 uppercase">Tanggal Penilaian</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $skp->tanggal_penilaian?->locale('id')->translatedFormat('j F Y') ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-[0.1em] text-slate-400 uppercase">Jumlah Nilai</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $skp->jumlahNilai() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-[0.1em] text-slate-400 uppercase">Nilai Perilaku Kerja</p>
                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ $skp->nilaiPerilakuKerja() ?? '—' }}</p>
                </div>
            </div>
        </section>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Periksa kembali data Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">
                        Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum keberatan dapat diajukan.
                    </p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('skp-dosen.keberatan.update', $skp) }}" class="section-card">
            @csrf
            @method('PUT')

            <div class="section-heading">
                <span class="section-number">02</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Keberatan atas Nilai SKP</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Isi uraian keberatan dan tanggal pengajuannya.</p>
                </div>
            </div>

            <div class="grid gap-5 px-5 py-5 sm:px-6">
                <div>
                    <label for="keberatan" class="form-label">
                        Uraian Keberatan <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="keberatan" name="keberatan" rows="6" required maxlength="2000"
                              placeholder="Tuliskan alasan keberatan atas nilai SKP yang Anda terima…"
                              @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('keberatan')])>{{ old('keberatan', $skp->keberatan) }}</textarea>
                    @error('keberatan') <p class="form-error">{{ $message }}</p> @enderror

                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Keberatan ini tercetak pada blok 5 halaman cetak SKP (maksimal 2000 karakter).
                    </p>
                </div>

                <div class="sm:max-w-xs">
                    <label for="tanggal_keberatan" class="form-label">
                        Tanggal Ajuan Keberatan <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="tanggal_keberatan" name="tanggal_keberatan" required
                           value="{{ old('tanggal_keberatan', $skp->tanggal_keberatan?->format('d-m-Y')) }}" placeholder="DD-MM-YYYY"
                           maxlength="10" inputmode="numeric" autocomplete="off" data-date-mask data-date-picker
                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_keberatan')])>
                    @error('tanggal_keberatan') <p class="form-error">{{ $message }}</p> @enderror

                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Pilih lewat kalender atau ketik format DD-MM-YYYY.
                    </p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                <a href="{{ route('skp-dosen.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                <button type="submit" class="btn-primary w-auto justify-center px-5">
                    {{ $skp->mengajukanKeberatan() ? 'Perbarui Keberatan' : 'Ajukan Keberatan' }}
                </button>
            </div>
        </form>
    </div>
@endsection
