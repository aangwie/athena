@extends('layouts.app')

@section('title', 'Pengajuan Masuk')
@section('breadcrumb', 'TIM PAK')
@section('page-title', 'Pengajuan Masuk')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                TIM PAK
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Pengajuan DUPAK Masuk</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Daftar usulan angka kredit yang dikirim dosen. Buka salah satu baris untuk mengisi kolom angka kredit baru.
            </p>
        </div>

        <section data-table-card class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Pengajuan</h2>
                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                            {{ $pengajuan->count() }} data
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Buka salah satu baris untuk mengisi kolom angka kredit baru.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="relative block">
                        <span class="sr-only">Cari pengajuan</span>
                        <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="6.5" />
                                <path d="m16 16 4 4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="search" data-table-search placeholder="Cari dosen, periode…"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-64">
                        <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </label>

                    <div class="relative">
                        <label for="pengajuanStatusFilter" class="sr-only">Filter status</label>
                        <select id="pengajuanStatusFilter" data-table-filter data-column="2"
                                class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-3 text-xs font-medium text-slate-600 transition hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-44">
                            <option value="">Semua status</option>
                            @foreach (\App\Models\PengajuanDupak::STATUS_DUPAK as $statusDupak)
                                <option value="{{ \App\Models\PengajuanDupak::STATUS[$statusDupak] }}">
                                    {{ \App\Models\PengajuanDupak::STATUS[$statusDupak] }}
                                </option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                @include('tim-pak.pengajuan.partials.table')
            </div>
        </section>
    </div>
@endsection
