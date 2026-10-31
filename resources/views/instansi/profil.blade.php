@extends('layouts.app')

@section('title', 'Data Instansi')
@section('breadcrumb', 'Instansi')
@section('page-title', 'Data Instansi')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Instansi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Data Instansi</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Lengkapi identitas instansi yang akan digunakan pada dokumen dan pengajuan jabatan akademik.
                </p>
            </div>

            <a href="{{ route('instansi.struktur.index') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                    <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                    <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                    <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Pejabat Struktural
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

        <form method="POST" action="{{ route('instansi.profil.update') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            {{-- Identitas instansi --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Identitas Instansi</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Nama, legalitas, alamat, dan kontak resmi instansi.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                    <div class="sm:col-span-2">
                        <label for="nama_instansi" class="form-label">Nama Instansi <span class="text-rose-500">*</span></label>
                        <input type="text" id="nama_instansi" name="nama_instansi" maxlength="150" required
                               value="{{ old('nama_instansi', $instansi->nama_instansi) }}"
                               placeholder="Contoh: Universitas Athena Nusantara"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama_instansi')])>
                        @error('nama_instansi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="nomor_sk_operasional" class="form-label">Nomor SK Operasional <span class="text-rose-500">*</span></label>
                        <input type="text" id="nomor_sk_operasional" name="nomor_sk_operasional" maxlength="100" required
                               value="{{ old('nomor_sk_operasional', $instansi->nomor_sk_operasional) }}"
                               placeholder="Contoh: SK-MENDIKBUD/001/2026"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nomor_sk_operasional')])>
                        @error('nomor_sk_operasional') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="no_telp" class="form-label">No. Telp <span class="text-rose-500">*</span></label>
                        <input type="tel" id="no_telp" name="no_telp" maxlength="30" required
                               value="{{ old('no_telp', $instansi->no_telp) }}"
                               placeholder="Contoh: (022) 1234567"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('no_telp')])>
                        @error('no_telp') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="kota" class="form-label">Kota Instansi <span class="text-rose-500">*</span></label>
                        <input type="text" id="kota" name="kota" maxlength="100" required
                               value="{{ old('kota', $instansi->kota) }}"
                               placeholder="Contoh: Bandung"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('kota')])>
                        @error('kota') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">Dipakai pada baris tanda tangan surat.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="website" class="form-label">Website <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" id="website" name="website" maxlength="150" inputmode="url"
                               value="{{ old('website', $instansi->website) }}"
                               placeholder="Contoh: https://athena.ac.id"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('website')])>
                        @error('website') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="alamat" class="form-label">Alamat <span class="text-rose-500">*</span></label>
                        <textarea id="alamat" name="alamat" rows="3" maxlength="1000" required
                                  placeholder="Alamat lengkap instansi"
                                  @class(['form-input resize-y', 'border-rose-300 bg-rose-50/40' => $errors->has('alamat')])>{{ old('alamat', $instansi->alamat) }}</textarea>
                        @error('alamat') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Logo instansi --}}
            <section class="section-card" data-image-field>
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Logo Instansi</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Unggah logo resmi instansi dalam format gambar.</p>
                    </div>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                        <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            @if ($instansi->logo_base64)
                                <img data-image-preview src="{{ $instansi->logoDataUri() }}" alt="Logo {{ $instansi->nama_instansi }}"
                                     class="size-full object-contain p-2">
                            @else
                                <img data-image-preview alt="Pratinjau logo" class="hidden size-full object-contain p-2">
                                <span data-image-placeholder class="text-slate-300">
                                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" />
                                        <circle cx="9" cy="10" r="2.2" />
                                        <path d="m5.5 17 4.2-4.2a2 2 0 0 1 2.8 0l5.5 5.2M15 8.5h3" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <label for="logo" class="form-label">
                                Berkas Logo <span class="font-normal text-slate-400">(opsional)</span>
                            </label>
                            <input type="file" id="logo" name="logo"
                                   accept="image/png,image/jpeg,image/webp"
                                   data-image-input data-max-kb="500"
                                   @class([
                                       'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                       'border-rose-300 bg-rose-50/40' => $errors->has('logo'),
                                       'border-slate-200' => ! $errors->has('logo'),
                                   ])>
                            <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                Format JPG, PNG, atau WEBP. Maksimal 500 KB.
                                @if ($instansi->logo_base64)
                                    Unggah berkas baru untuk mengganti logo saat ini.
                                @endif
                            </p>
                            <p data-image-error class="form-error hidden" role="alert"></p>
                            @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Data instansi hanya dapat dikelola oleh administrator.
                </p>
                <button type="submit" class="btn-primary w-auto shrink-0 px-5 whitespace-nowrap">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                        <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                    </svg>
                    Simpan Data Instansi
                </button>
            </div>
        </form>
    </div>
@endsection
