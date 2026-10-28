@extends('layouts.app')

@section('title', 'Data Pribadi')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', 'Data Pribadi')

@section('content')
    @php
        $statusPernikahan = old('status_pernikahan', $profil->status_pernikahan ?? 'lajang');
        $pekerjaanPasangan = old('pekerjaan_pasangan', $profil->pekerjaan_pasangan ?? '');
        $nextAnakIndex = empty($anakList) ? 0 : max(array_map('intval', array_keys($anakList))) + 1;
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Data Pribadi</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Lengkapi data pribadi dan keluarga Anda. Informasi ini akan ditampilkan pada portofolio dosen.
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
                </div>
            </section>

            {{-- Status pernikahan --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Status Pernikahan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Pilih status yang sesuai dengan kondisi Anda saat ini.</p>
                    </div>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="choice-card">
                            <input type="radio" name="status_pernikahan" value="lajang" data-marital-status class="sr-only"
                                   @checked($statusPernikahan === 'lajang')>
                            <span class="choice-icon">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-800">Lajang</span>
                                <span class="mt-0.5 block text-xs text-slate-400">Belum menikah / belum memiliki pasangan.</span>
                            </span>
                            <span class="choice-indicator">
                                <svg class="size-3 opacity-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </label>

                        <label class="choice-card">
                            <input type="radio" name="status_pernikahan" value="menikah" data-marital-status class="sr-only"
                                   @checked($statusPernikahan === 'menikah')>
                            <span class="choice-icon">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M12 20.5s-7.5-4.6-7.5-10A4.5 4.5 0 0 1 12 7.6a4.5 4.5 0 0 1 7.5 2.9c0 5.4-7.5 10-7.5 10Z" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-800">Menikah</span>
                                <span class="mt-0.5 block text-xs text-slate-400">Lengkapi data pasangan dan anak.</span>
                            </span>
                            <span class="choice-indicator">
                                <svg class="size-3 opacity-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </label>
                    </div>
                    @error('status_pernikahan') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </section>

            {{-- Data pasangan --}}
            <section data-married-section @class(['section-card', 'hidden' => $statusPernikahan !== 'menikah'])>
                <div class="section-heading">
                    <span class="section-number">03</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Data Pasangan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Isian wajib dilengkapi jika status Anda menikah.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                    <div>
                        <label for="nama_pasangan" class="form-label">Nama Pasangan <span class="text-rose-500">*</span></label>
                        <input type="text" id="nama_pasangan" name="nama_pasangan"
                               value="{{ old('nama_pasangan', $profil->nama_pasangan ?? '') }}"
                               placeholder="Nama lengkap pasangan"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama_pasangan')])>
                        @error('nama_pasangan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="pekerjaan_pasangan" class="form-label">Pekerjaan Pasangan <span class="text-rose-500">*</span></label>
                        <select id="pekerjaan_pasangan" name="pekerjaan_pasangan" data-spouse-occupation
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('pekerjaan_pasangan')])>
                            <option value="">Pilih pekerjaan</option>
                            <option value="asn" @selected($pekerjaanPasangan === 'asn')>ASN</option>
                            <option value="non_asn" @selected($pekerjaanPasangan === 'non_asn')>Non ASN</option>
                        </select>
                        @error('pekerjaan_pasangan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div data-nip-wrapper @class(['hidden' => $pekerjaanPasangan !== 'asn'])>
                        <label for="nip_pasangan" class="form-label">NIP Pasangan <span class="text-rose-500">*</span></label>
                        <input type="text" id="nip_pasangan" name="nip_pasangan" inputmode="numeric"
                               value="{{ old('nip_pasangan', $profil->nip_pasangan ?? '') }}"
                               placeholder="NIP 18 digit"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nip_pasangan')])>
                        @error('nip_pasangan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tempat_lahir_pasangan" class="form-label">Tempat Lahir Pasangan <span class="text-rose-500">*</span></label>
                        <input type="text" id="tempat_lahir_pasangan" name="tempat_lahir_pasangan"
                               value="{{ old('tempat_lahir_pasangan', $profil->tempat_lahir_pasangan ?? '') }}"
                               placeholder="Kota / kabupaten kelahiran pasangan"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tempat_lahir_pasangan')])>
                        @error('tempat_lahir_pasangan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tanggal_lahir_pasangan" class="form-label">Tanggal Lahir Pasangan <span class="text-rose-500">*</span></label>
                        <input type="date" id="tanggal_lahir_pasangan" name="tanggal_lahir_pasangan" max="{{ now()->format('Y-m-d') }}"
                               value="{{ old('tanggal_lahir_pasangan', $profil?->tanggal_lahir_pasangan?->format('Y-m-d')) }}"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_lahir_pasangan')])>
                        @error('tanggal_lahir_pasangan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tanggal_nikah" class="form-label">Tanggal Nikah <span class="text-rose-500">*</span></label>
                        <input type="date" id="tanggal_nikah" name="tanggal_nikah" max="{{ now()->format('Y-m-d') }}"
                               value="{{ old('tanggal_nikah', $profil?->tanggal_nikah?->format('Y-m-d')) }}"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_nikah')])>
                        @error('tanggal_nikah') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            {{-- Data anak --}}
            <section data-married-section @class(['section-card', 'hidden' => $statusPernikahan !== 'menikah'])>
                <div class="section-heading flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="section-number">04</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800">Data Anak</h3>
                            <p class="mt-0.5 text-xs text-slate-400">Anda dapat menambahkan lebih dari satu data anak.</p>
                        </div>
                    </div>
                    <button type="button" data-add-anak class="btn-small shrink-0 self-start sm:self-auto">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        Tambah Anak
                    </button>
                </div>

                <div class="px-5 py-5 sm:px-6">
                    @error('children') <p class="form-error mb-3">{{ $message }}</p> @enderror

                    <div data-anak-container data-next-index="{{ $nextAnakIndex }}" class="space-y-3">
                        @foreach ($anakList as $index => $anak)
                            @include('profil.partials.anak-row', ['index' => $index, 'anak' => $anak])
                        @endforeach

                        <div data-anak-empty @class(['rounded-xl border border-dashed border-slate-300 bg-slate-50/50 px-5 py-8 text-center', 'hidden' => count($anakList) > 0])>
                            <span class="mx-auto flex size-11 items-center justify-center rounded-xl bg-white text-slate-300 shadow-sm ring-1 ring-slate-200">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <circle cx="9" cy="8" r="3" />
                                    <circle cx="17" cy="9" r="2.2" />
                                    <path d="M3.5 19a5.5 5.5 0 0 1 11 0M14.5 14.2a4.3 4.3 0 0 1 6 3.8" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="mt-2.5 text-sm font-bold text-slate-600">Belum ada data anak</p>
                            <p class="mt-1 text-xs text-slate-400">Klik tombol <span class="font-semibold">Tambah Anak</span> untuk menambahkan data.</p>
                        </div>
                    </div>

                    <template data-anak-template>
                        @include('profil.partials.anak-row', ['index' => '__INDEX__', 'anak' => []])
                    </template>
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
