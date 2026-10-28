@extends('layouts.app')

@section('title', $item ? 'Edit '.$modul['label'] : 'Tambah '.$modul['label'])
@section('breadcrumb', 'Kepegawaian')
@section('page-title', $item ? 'Edit '.$modul['label'] : 'Tambah '.$modul['label'])

@section('content')
    @php
        $isEdit = $item !== null;
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Kepegawaian
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $isEdit ? 'Edit '.$modul['label'] : 'Tambah '.$modul['label'] }}
                </h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    {{ $isEdit
                        ? 'Perbarui nama data master kepegawaian.'
                        : 'Tambahkan data master baru. ID akan dibuat otomatis oleh sistem.' }}
                </p>
            </div>
            <a href="{{ route($modul['route'].'.index') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M19.5 12h-15M9 6.5 3.5 12 9 17.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Kembali
            </a>
        </div>

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

        <form method="POST"
              action="{{ $isEdit ? route($modul['route'].'.update', $item->id) : route($modul['route'].'.store') }}"
              class="space-y-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Data {{ $modul['label'] }}</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Data master ini hanya menyimpan ID dan nama.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6">
                    @if ($isEdit)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">ID</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-700">#{{ $item->id }}</p>
                            </div>
                            <span class="rounded-full bg-white px-2 py-1 text-[10px] font-bold text-slate-400 ring-1 ring-slate-200 ring-inset">
                                Otomatis
                            </span>
                        </div>
                    @endif

                    <div>
                        <label for="nama" class="form-label">
                            Nama <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="nama" name="nama"
                               value="{{ old('nama', $item->nama ?? '') }}"
                               placeholder="{{ $modul['contoh'] }}" maxlength="100" required autofocus
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama')])>
                        @error('nama') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">Maksimal 100 karakter dan tidak boleh sama dengan data lain.</p>
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Perubahan langsung tersimpan setelah tombol ditekan.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route($modul['route'].'.index') }}" class="btn-secondary flex-1 whitespace-nowrap sm:flex-none">Batal</a>
                    <button type="submit" class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                            <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
