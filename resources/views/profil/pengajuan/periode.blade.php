@php
    // Halaman ini dipakai dua alur: periode pengajuan biasa dan periode khusus
    // dosen yang belum memiliki jabatan akademik. Nilai bawaan = alur biasa.
    $judul = $judul ?? 'Pilih Periode Pengajuan';
    $kicker = $kicker ?? 'Pengajuan Jabatan Akademik';
    $deskripsi = $deskripsi ?? 'Tentukan tahun akademik dan semester pengajuan. Periode ini dipakai untuk seluruh komponen kegiatan yang Anda isi.';
    $action = $action ?? route('profil.pengajuan.create');
    $labelTombol = $labelTombol ?? 'Lanjut Isi Komponen';
@endphp

@extends('layouts.app')

@section('title', $judul)
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Pengajuan Baru')

@section('content')
    @php
        $tahunSekarang = now()->month === 1 ? now()->year - 1 : now()->year;
        $semesterSekarang = in_array(now()->month, [8, 9, 10, 11, 12, 1], true) ? 'ganjil' : 'genap';
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                {{ $kicker }}
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $judul }}</h2>
            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                {{ $deskripsi }}
            </p>
        </div>

        <form method="GET" action="{{ $action }}" class="section-card p-5 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="tahun" class="form-label">Tahun Akademik <span class="text-rose-500">*</span></label>
                    <select id="tahun" name="tahun" class="form-select" required>
                        @for ($tahun = $tahunSekarang; $tahun >= $tahunSekarang - 4; $tahun--)
                            <option value="{{ $tahun }}" @selected($tahun === $tahunSekarang)>{{ $tahun }}/{{ $tahun + 1 }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label for="semester" class="form-label">Semester <span class="text-rose-500">*</span></label>
                    <select id="semester" name="semester" class="form-select" required>
                        @foreach (\App\Models\KegiatanDupak::SEMESTER as $value => $label)
                            <option value="{{ $value }}" @selected($value === $semesterSekarang)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('dashboard') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali ke Dashboard
                </a>
                <button type="submit" class="btn-primary w-auto px-5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4.5 12h15M14.5 5.5 21 12l-6.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $labelTombol }}
                </button>
            </div>
        </form>
    </div>
@endsection
