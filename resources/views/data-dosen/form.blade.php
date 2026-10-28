@extends('layouts.app')

@section('title', $item ? 'Edit Data Dosen' : 'Tambah Data Dosen')
@section('breadcrumb', 'Administrasi')
@section('page-title', $item ? 'Edit Data Dosen' : 'Tambah Data Dosen')

@section('content')
    @php
        $isEdit = $item !== null;
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Administrasi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $isEdit ? 'Edit Data Dosen' : 'Tambah Data Dosen' }}
                </h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    {{ $isEdit ? 'Perbarui data dosen.' : 'Tambahkan data dosen baru.' }}
                </p>
            </div>
            <a href="{{ route('data-dosen.index') }}" class="btn-secondary shrink-0">Kembali</a>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <div>
                    <p class="font-bold">Periksa kembali data Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">Terdapat {{ $errors->count() }} isian yang perlu diperbaiki.</p>
                </div>
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('data-dosen.update', $item->id) : route('data-dosen.store') }}"
              class="space-y-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="grid gap-5 px-5 py-5 sm:px-6">
                    @if ($isEdit)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">ID</p>
                                <p class="mt-0.5 font-mono text-sm font-semibold text-slate-700">#{{ $item->id }}</p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="name" class="form-label">Nama Dosen <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $item->name ?? '') }}"
                               required autofocus @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('name')])>
                        @error('name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="nidn" class="form-label">NIDN <span class="text-rose-500">*</span></label>
                        <input type="text" id="nidn" name="nidn" value="{{ old('nidn', $item->nidn ?? '') }}"
                               required @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nidn')])>
                        @error('nidn') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pangkat" class="form-label">Pangkat <span class="text-rose-500">*</span></label>
                        <select id="pangkat" name="pangkat" required
                                @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('pangkat')])>
                            <option value="" selected disabled>Pilih Pangkat</option>
                            @foreach ($pangkatOptions as $option)
                                <option value="{{ $option->nama }}" @selected(old('pangkat', $item->jabatan_fungsional ?? '') === $option->nama)>
                                    {{ $option->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('pangkat') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <label for="password" class="form-label">Password @if(!$isEdit)<span class="text-rose-500">*</span>@endif</label>
                        </div>
                        <div class="relative">
                            <input id="password" name="password" type="password"
                                   {{ $isEdit ? '' : 'required' }}
                                   @class(['form-input pr-12', 'border-rose-300 bg-rose-50/40' => $errors->has('password')])>
                            <button type="button" data-password-toggle="#password" aria-label="Tampilkan password" aria-pressed="false"
                                    class="absolute top-1/2 right-2.5 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                <svg data-eye-open class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-eye-off class="hidden size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 3l18 18" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                        @if ($isEdit)
                            <p class="mt-1.5 text-[11px] text-slate-400">Kosongkan jika tidak ingin mengubah password.</p>
                        @endif
                    </div>

                    <div>
                        <label for="password_confirmation" class="form-label">Konfirmasi Password @if(!$isEdit)<span class="text-rose-500">*</span>@endif</label>
                        <div class="relative">
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                   {{ $isEdit ? '' : 'required' }}
                                   @class(['form-input pr-12', 'border-rose-300 bg-rose-50/40' => $errors->has('password_confirmation')])>
                            <button type="button" data-password-toggle="#password_confirmation" aria-label="Tampilkan password" aria-pressed="false"
                                    class="absolute top-1/2 right-2.5 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                                <svg data-eye-open class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg data-eye-off class="hidden size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 3l18 18" stroke-linecap="round" />
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <a href="{{ route('data-dosen.index') }}" class="btn-secondary flex-1 whitespace-nowrap sm:flex-none">Batal</a>
                <button type="submit" class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                    {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
                </button>
            </div>
        </form>
    </div>
@endsection