@extends('layouts.app')

@section('title', 'Syarat Khusus DUPAK')
@section('breadcrumb', 'Syarat Khusus DUPAK')
@section('page-title', 'Syarat Khusus DUPAK')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Syarat Khusus DUPAK</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Penilaian untuk syarat khusus: lengkapi data publikasi dan unggah berkas bukti.
                    Beri tanda - jika tidak ada jawaban yang diberikan.
                </p>
            </div>

            <a href="{{ route('profil.pengajuan.riwayat') }}" class="btn-secondary shrink-0 justify-center">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v13A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                    <rect x="9" y="3" width="6" height="4" rx="1.5" />
                    <path d="M9 12h6M9 16h4" stroke-linecap="round" />
                </svg>
                Riwayat DUPAK
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

        <form id="form-syarat-khusus" method="POST" enctype="multipart/form-data"
              action="{{ route('profil.pendukung-dupak.store') }}">
            @csrf

            <section class="section-card overflow-hidden">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Penilaian untuk Syarat Khusus</h3>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Lengkapi seluruh baris di bawah, lalu tekan Simpan. Berkas bukti berformat PDF sesuai
                            ukuran maksimal pada tiap baris.
                        </p>
                    </div>
                </div>

                <x-syarat-khusus-tabel :item="$item" />

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4.5 3.5h9.2L17 6.8v9.7a1 1 0 0 1-1 1H4.5a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round" />
                            <path d="M7 3.5v4h6v-4M7 13.5h6" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round" />
                        </svg>
                        {{ $item ? 'Simpan Perubahan' : 'Simpan Syarat Khusus' }}
                    </button>
                </div>
            </section>
        </form>
    </div>
@endsection
