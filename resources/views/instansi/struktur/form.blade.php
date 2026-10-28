@extends('layouts.app')

@section('title', $struktur ? 'Edit Pejabat Struktural' : 'Tambah Pejabat Struktural')
@section('breadcrumb', 'Instansi')
@section('page-title', $struktur ? 'Edit Pejabat Struktural' : 'Tambah Pejabat Struktural')

@section('content')
    @php
        $isEdit = $struktur !== null;
        $jabatanTerpilih = old('jabatan_struktural_id', $struktur->jabatan_struktural_id ?? '');
        $dosenTerpilih = old('user_id', $struktur->user_id ?? '');
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Instansi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $isEdit ? 'Edit Pejabat Struktural' : 'Tambah Pejabat Struktural' }}
                </h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Pilih jabatan struktural dan dosen yang menjabat pada posisi tersebut.
                </p>
            </div>
            <a href="{{ route('instansi.struktur.index') }}" class="btn-secondary shrink-0">
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
              action="{{ $isEdit ? route('instansi.struktur.update', $struktur) : route('instansi.struktur.store') }}"
              class="space-y-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Data Pejabat Struktural</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Jabatan bersumber dari master jabatan struktural.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6">
                    <div>
                        <label for="jabatan_struktural_id" class="form-label">
                            Jabatan Struktural <span class="text-rose-500">*</span>
                        </label>
                        <select id="jabatan_struktural_id" name="jabatan_struktural_id" required
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan_struktural_id')])>
                            <option value="">Pilih jabatan struktural</option>
                            @foreach ($jabatanList as $jabatan)
                                <option value="{{ $jabatan->id }}" @selected((string) $jabatanTerpilih === (string) $jabatan->id)>
                                    {{ $jabatan->nama }}
                                </option>
                            @endforeach
                        </select>
                        @error('jabatan_struktural_id') <p class="form-error">{{ $message }}</p> @enderror

                        @if ($jabatanList->isEmpty())
                            <p class="mt-1.5 text-[11px] text-amber-600">
                                Belum ada data jabatan struktural.
                                <a href="{{ route('kepegawaian.jabatan-struktural.index') }}" class="font-bold underline">Tambahkan terlebih dahulu</a>.
                            </p>
                        @endif
                    </div>

                    <div>
                        <label for="user_id" class="form-label">
                            Nama Pejabat <span class="text-rose-500">*</span>
                        </label>
                        <select id="user_id" name="user_id" required
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('user_id')])>
                            <option value="">Pilih dosen</option>
                            @foreach ($dosenList as $dosen)
                                <option value="{{ $dosen->id }}" @selected((string) $dosenTerpilih === (string) $dosen->id)>
                                    {{ $dosen->name }} — {{ $dosen->nidn }} ({{ $dosen->program_studi }})
                                </option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="form-error">{{ $message }}</p> @enderror

                        @if ($dosenList->isEmpty())
                            <p class="mt-1.5 text-[11px] text-amber-600">
                                Belum ada dosen aktif yang dapat dipilih.
                            </p>
                        @endif
                    </div>

                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M12 7.5V13M12 16.5h.01" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-slate-700">Sumber data</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                                Jabatan struktural diambil dari menu
                                <span class="font-semibold text-slate-700">Kepegawaian → Jabatan Struktural</span>,
                                sedangkan nama pejabat diambil dari data dosen aktif.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Data langsung tampil pada tabel struktur instansi.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('instansi.struktur.index') }}" class="btn-secondary flex-1 whitespace-nowrap sm:flex-none">Batal</a>
                    <button type="submit" class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                            <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pejabat' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
