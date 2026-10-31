@extends('layouts.app')

@section('title', 'Data Pribadi')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', 'Data Pribadi')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Data Pribadi</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Lengkapi data pribadi Anda. Informasi ini akan ditampilkan pada portofolio dosen.
                </p>
            </div>
            <a href="{{ route('profil.show') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Lihat Portofolio
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

        <form method="POST" action="{{ route('profil.data-pribadi.update') }}" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            {{-- Identitas diri --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Identitas Diri</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Data utama yang tercatat pada sistem kampus.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                    <div class="sm:col-span-2">
                        <label for="nama" class="form-label">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" id="nama" name="nama" value="{{ old('nama', $user->name) }}"
                               placeholder="Nama lengkap beserta gelar"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama')])>
                        @error('nama') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="nidn" class="form-label">NIDN</label>
                        <input type="text" id="nidn" value="{{ $user->nidn }}" readonly
                               class="form-input cursor-not-allowed bg-slate-100 text-slate-500">
                        <p class="mt-1.5 text-[11px] text-slate-400">NIDN tidak dapat diubah melalui halaman ini.</p>
                    </div>

                    <div>
                        <label for="no_hp" class="form-label">No. HP <span class="text-rose-500">*</span></label>
                        <input type="tel" id="no_hp" name="no_hp" value="{{ old('no_hp', $profil->no_hp ?? '') }}"
                               placeholder="Contoh: 0812-3456-7890"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('no_hp')])>
                        @error('no_hp') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tempat_lahir" class="form-label">Tempat Lahir <span class="text-rose-500">*</span></label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" value="{{ old('tempat_lahir', $profil->tempat_lahir ?? '') }}"
                               placeholder="Kota / kabupaten kelahiran"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tempat_lahir')])>
                        @error('tempat_lahir') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir <span class="text-rose-500">*</span></label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" max="{{ now()->format('Y-m-d') }}"
                               value="{{ old('tanggal_lahir', $profil?->tanggal_lahir?->format('Y-m-d')) }}"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_lahir')])>
                        @error('tanggal_lahir') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="alamat" class="form-label">Alamat <span class="text-rose-500">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" placeholder="Alamat lengkap tempat tinggal saat ini"
                                  @class(['form-input resize-none', 'border-rose-300 bg-rose-50/40' => $errors->has('alamat')])>{{ old('alamat', $profil->alamat ?? '') }}</textarea>
                        @error('alamat') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="agama" class="form-label">Agama</label>
                        <select id="agama" name="agama"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('agama')])>
                            <option value="">Pilih agama</option>
                            @foreach (\App\Models\ProfilDosen::AGAMA as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('agama', $profil->agama ?? '') === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('agama') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Data akan langsung tampil pada portofolio setelah disimpan.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('profil.show') }}" class="btn-secondary flex-1 sm:flex-none">Batal</a>
                    <button type="submit" class="btn-primary w-auto flex-1 px-5 sm:flex-none">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                            <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                        </svg>
                        Simpan Data
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
