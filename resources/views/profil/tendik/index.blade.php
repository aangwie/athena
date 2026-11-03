@extends('layouts.app')

@section('title', 'Riwayat Tendik')
@section('breadcrumb', 'Profil Tendik')
@section('page-title', 'Riwayat Tendik')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Tendik
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Tendik</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Riwayat kepegawaian tenaga kependidikan Anda.
                </p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="px-5 py-16 text-center sm:px-6">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="3.5" y="7.5" width="17" height="12" rx="2.5" />
                        <path d="M9 7.5V6a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 6v1.5M3.5 12h17" stroke-linecap="round" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-700">Belum ada riwayat tendik</p>
                <p class="mt-1 text-xs text-slate-400">Data riwayat tendik akan tampil di halaman ini.</p>
            </div>
        </section>
    </div>
@endsection
