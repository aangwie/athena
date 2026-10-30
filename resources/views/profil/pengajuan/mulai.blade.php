@extends('layouts.app')

@section('title', 'Status Jabatan Akademik')
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Pengajuan Baru')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengajuan Jabatan Akademik
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Status Jabatan Akademik</h2>
            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                Alur pengajuan baru mengikuti status jabatan akademik Anda saat ini.
            </p>
        </div>

        <section class="section-card p-5 sm:p-6">
            <div class="flex items-start gap-4">
                @if ($jabatan)
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Jabatan Akademik Saat Ini</p>
                        <p class="mt-1 text-lg font-extrabold text-slate-800">{{ $jabatan }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            Anda sudah memiliki jabatan akademik. Lanjutkan ke pemilihan periode pengajuan seperti biasa.
                        </p>
                    </div>
                @else
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 8v5m0 3.5h.01M12 3l9 16H3l9-16Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Jabatan Akademik</p>
                        <p class="mt-1 text-lg font-extrabold text-slate-800">Belum ada</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            Anda belum memiliki jabatan akademik. Pengajuan pertama memakai alur khusus: pilih periode
                            terlebih dahulu, lalu lengkapi syarat khusus.
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('dashboard') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali ke Dashboard
                </a>
                <a href="{{ $jabatan ? route('profil.pengajuan.periode') : route('profil.pengajuan.periode-pertama') }}"
                   class="btn-primary w-auto justify-center px-5">
                    Lanjut Pilih Periode
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4.5 12h15M14.5 5.5 21 12l-6.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
        </section>
    </div>
@endsection
