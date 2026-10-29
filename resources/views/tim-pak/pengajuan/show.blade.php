@extends('layouts.app')

@section('title', 'Penilaian DUPAK')
@section('breadcrumb', 'TIM PAK')
@section('page-title', 'Penilaian DUPAK')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    TIM PAK
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $pengajuan->user->name }}</h2>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span>{{ $pengajuan->periodeLabel() }}</span>
                    <span class="text-slate-300">·</span>
                    <span>{{ $pengajuan->user->nidn ?? '—' }} · {{ $pengajuan->user->program_studi ?? '—' }}</span>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 ring-inset {{ $pengajuan->statusClasses() }}">
                        {{ $pengajuan->statusLabel() }}
                    </span>
                </p>
            </div>

            <a href="{{ route('tim-pak.pengajuan.index') }}" class="btn-secondary">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M10.5 6 4.5 12l6 6M4.5 12h15" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Daftar Pengajuan
            </a>
        </div>

        <form method="POST" action="{{ route('tim-pak.pengajuan.update', $pengajuan) }}"
              data-confirm="Nilai angka kredit akan disimpan dan langsung terlihat oleh dosen."
              data-confirm-title="Simpan penilaian?">
            @csrf
            @method('PUT')

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:p-4">
                @include('profil.pengajuan.partials.review')
            </div>

            <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">
                    @if ($pengajuan->nilaiTerkunci())
                        DUPAK periode ini sudah <span class="font-bold text-emerald-700">disetujui</span> admin.
                        Nilai terkunci dan halaman ini hanya untuk dilihat.
                    @else
                        Isi kolom <span class="font-bold text-slate-700">Baru</span> untuk tiap butir kegiatan, lalu simpan.
                        Baris <span class="font-bold text-slate-700">Jumlah</span> terhitung otomatis.
                    @endif
                </p>
                <button type="submit" class="btn-primary w-auto px-5 py-2.5 text-sm" @disabled($pengajuan->nilaiTerkunci())>
                    Update Nilai
                </button>
            </div>
        </form>
    </div>
@endsection
