@extends('layouts.app')

@section('title', 'Ajuan PAK')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Ajuan PAK')

@section('content')
    @php
        $menungguPenugasan = $pengajuan->where('status', 'menunggu_penugasan')->count();
        // Status yang tampil pada halaman ini, dipakai juga sebagai pilihan filter tabel.
        $statusAjuanPak = ['menunggu_penugasan', 'diajukan', 'dinilai'];
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Administrasi
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Ajuan PAK</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Ajuan dosen yang masuk ke admin kepegawaian. Teruskan setiap ajuan kepada satu anggota TIM PAK
                yang dipilih; hanya TIM PAK tersebut yang dapat melihat dan menilai ajuannya.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Penugasan TIM PAK gagal diproses.</p>
                    <p class="mt-0.5 text-xs text-rose-600">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-extrabold tracking-tight text-slate-900">Ajuan PAK Masuk</h3>
                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                            {{ $pengajuan->count() }} data
                        </span>
                        @if ($menungguPenugasan > 0)
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200 ring-inset">
                                {{ $menungguPenugasan }} menunggu penugasan
                            </span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                        Tekan <span class="font-semibold text-slate-600">Tugaskan</span> pada baris berstatus
                        menunggu penugasan, lalu pilih satu anggota TIM PAK.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="relative block">
                        <span class="sr-only">Cari ajuan PAK</span>
                        <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="6.5" />
                                <path d="m16 16 4 4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="search" data-table-search placeholder="Cari dosen, periode, TIM PAK…"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-64">
                        <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </label>

                    <div class="relative">
                        <label for="ajuanPakStatusFilter" class="sr-only">Filter status ajuan PAK</label>
                        <select id="ajuanPakStatusFilter" data-table-filter data-column="2"
                                class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-3 text-xs font-medium text-slate-600 transition hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-48">
                            <option value="">Semua status</option>
                            @foreach ($statusAjuanPak as $statusDupak)
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
                @include('admin.ajuan-pak.partials.table')
            </div>
        </section>
    </div>
@endsection