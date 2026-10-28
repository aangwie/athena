@extends('layouts.app')

@section('title', $riwayat ? 'Edit Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', $riwayat ? 'Edit Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan')

@section('content')
    @php
        $isEdit = $riwayat !== null;
        $jenjang = old('jenjang', $riwayat->jenjang ?? '');
        $isPerguruanTinggi = in_array($jenjang, \App\Models\RiwayatPendidikan::PERGURUAN_TINGGI, true);
    @endphp

    <div class="mx-auto max-w-3xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">
                    {{ $isEdit ? 'Edit Riwayat Pendidikan' : 'Tambah Riwayat Pendidikan' }}
                </h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    {{ $isEdit
                        ? 'Perbarui data pendidikan atau ganti berkas bukti jika diperlukan.'
                        : 'Lengkapi data pendidikan formal dan unggah berkas bukti yang diperlukan.' }}
                </p>
            </div>
            <a href="{{ route('profil.pendidikan.index') }}" class="btn-secondary shrink-0">
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
              action="{{ $isEdit ? route('profil.pendidikan.update', $riwayat) : route('profil.pendidikan.store') }}"
              enctype="multipart/form-data"
              class="space-y-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            {{-- Data pendidikan --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Data Pendidikan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Isi data jenjang, institusi, dan periode studi.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                    <div class="sm:col-span-2">
                        <label for="nama_institusi" class="form-label">Nama Institusi / Sekolah <span class="text-rose-500">*</span></label>
                        <input type="text" id="nama_institusi" name="nama_institusi"
                               value="{{ old('nama_institusi', $riwayat->nama_institusi ?? '') }}"
                               placeholder="Contoh: SD Negeri 1 Bandung"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nama_institusi')])>
                        @error('nama_institusi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="jenjang" class="form-label">Jenjang Pendidikan <span class="text-rose-500">*</span></label>
                        <select id="jenjang" name="jenjang" data-jenjang-select
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jenjang')])>
                            <option value="">Pilih jenjang</option>
                            @foreach (\App\Models\RiwayatPendidikan::JENJANG as $value => $label)
                                <option value="{{ $value }}" @selected($jenjang === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenjang') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Jenjang D3–S3 wajib melampirkan ijazah dan transkrip.
                        </p>
                    </div>

                    <div>
                        <label for="jurusan" class="form-label">Jurusan / Program Studi <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="text" id="jurusan" name="jurusan"
                               value="{{ old('jurusan', $riwayat->jurusan ?? '') }}"
                               placeholder="Contoh: Pendidikan Informatika"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('jurusan')])>
                        @error('jurusan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tahun_masuk" class="form-label">Tahun Masuk <span class="text-rose-500">*</span></label>
                        <input type="number" id="tahun_masuk" name="tahun_masuk"
                               value="{{ old('tahun_masuk', $riwayat->tahun_masuk ?? '') }}"
                               min="1900" max="{{ now()->year }}" inputmode="numeric" placeholder="{{ now()->year - 10 }}"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tahun_masuk')])>
                        @error('tahun_masuk') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="tahun_lulus" class="form-label">Tahun Lulus <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input type="number" id="tahun_lulus" name="tahun_lulus"
                               value="{{ old('tahun_lulus', $riwayat->tahun_lulus ?? '') }}"
                               min="1900" max="{{ now()->year }}" inputmode="numeric" placeholder="{{ now()->year }}"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tahun_lulus')])>
                        @error('tahun_lulus') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">Kosongkan jika pendidikan masih berjalan.</p>
                    </div>
                </div>
            </section>

            {{-- Berkas --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Berkas Bukti</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Unggah bukti ijazah, dan transkrip untuk perguruan tinggi.</p>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                    <div data-file-field>
                        <label for="ijazah" class="form-label">
                            Bukti Ijazah <span class="text-rose-500">*</span>
                        </label>

                        @if ($riwayat?->ijazah_path)
                            <div class="mb-2.5 flex items-center justify-between gap-3 rounded-xl border border-teal-100 bg-teal-50/50 px-3.5 py-2.5">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-teal-600 shadow-sm ring-1 ring-teal-100">
                                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-mono text-[11px] font-semibold text-teal-800">{{ basename($riwayat->ijazah_path) }}</p>
                                        <p class="text-[10px] text-teal-700/60">Berkas saat ini</p>
                                    </div>
                                </div>
                                <a href="{{ route('profil.pendidikan.berkas', [$riwayat, 'ijazah']) }}" target="_blank" rel="noopener"
                                   class="shrink-0 rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-bold text-teal-700 shadow-sm ring-1 ring-teal-100 transition hover:bg-teal-50">
                                    Lihat
                                </a>
                            </div>
                        @endif

                        <input type="file" id="ijazah" name="ijazah" accept="application/pdf,.pdf" data-pdf-input data-max-kb="500"
                               @class([
                                   'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                   'border-rose-300 bg-rose-50/40' => $errors->has('ijazah'),
                                   'border-slate-200' => ! $errors->has('ijazah'),
                               ])>
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Format PDF, maksimal 500 KB.
                            @if ($isEdit || $riwayat?->ijazah_path)
                                Kosongkan jika tidak ingin mengganti berkas.
                            @else
                                Wajib diunggah.
                            @endif
                        </p>
                        <p data-file-error class="form-error hidden" role="alert"></p>
                        @error('ijazah') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div data-transkrip-field @class(['hidden' => ! $isPerguruanTinggi])>
                        <label for="transkrip" class="form-label">
                            Bukti Transkrip <span class="text-rose-500">*</span>
                        </label>

                        @if ($riwayat?->transkrip_path)
                            <div class="mb-2.5 flex items-center justify-between gap-3 rounded-xl border border-indigo-100 bg-indigo-50/50 px-3.5 py-2.5">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-indigo-600 shadow-sm ring-1 ring-indigo-100">
                                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-mono text-[11px] font-semibold text-indigo-800">{{ basename($riwayat->transkrip_path) }}</p>
                                        <p class="text-[10px] text-indigo-700/60">Berkas saat ini</p>
                                    </div>
                                </div>
                                <a href="{{ route('profil.pendidikan.berkas', [$riwayat, 'transkrip']) }}" target="_blank" rel="noopener"
                                   class="shrink-0 rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-bold text-indigo-700 shadow-sm ring-1 ring-indigo-100 transition hover:bg-indigo-50">
                                    Lihat
                                </a>
                            </div>
                        @endif

                        <input type="file" id="transkrip" name="transkrip" accept="application/pdf,.pdf" data-pdf-input data-max-kb="500"
                               @disabled(! $isPerguruanTinggi)
                               @class([
                                   'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-indigo-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-indigo-300 focus:outline-none',
                                   'border-rose-300 bg-rose-50/40' => $errors->has('transkrip'),
                                   'border-slate-200' => ! $errors->has('transkrip'),
                               ])>
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Wajib untuk jenjang D3–S3. Format PDF, maksimal 500 KB.
                            @if ($riwayat?->transkrip_path)
                                Kosongkan jika tidak ingin mengganti berkas.
                            @endif
                        </p>
                        <p data-file-error class="form-error hidden" role="alert"></p>
                        @error('transkrip') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5">
                        <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm ring-1 ring-slate-200">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M12 7.5V13M12 16.5h.01" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-xs font-bold text-slate-700">Informasi penyimpanan berkas</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                                Berkas disimpan otomatis dengan nama
                                <span class="font-mono text-[10px] font-semibold text-slate-700">jenjang_nidn_kode_acak.pdf</span>,
                                misalnya <span class="font-mono text-[10px] font-semibold text-slate-700">sd_0708048703_stkip46dtghjstA5d.pdf</span>.
                                Berkas hanya dapat diakses oleh pemilik akun.
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
                    Data akan langsung tampil pada portofolio setelah disimpan.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('profil.pendidikan.index') }}" class="btn-secondary flex-1 whitespace-nowrap sm:flex-none">Batal</a>
                    <button type="submit" class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                            <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan Riwayat' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
