@extends('layouts.app')

@section('title', 'Pendukung DUPAK')
@section('breadcrumb', 'Pendukung DUPAK')
@section('page-title', 'Pendukung DUPAK')

@section('content')
    @php
        $isEdit = $editItem !== null;
        // Jenis terpilih: prioritaskan input lama (validasi gagal), lalu item edit.
        $jenisTerpilih = old('jenis', $editItem?->jenis);
        // Jenis dokumen yang memakai berkas unggahan; profil SINTA memakai tautan.
        $jenisBerkas = [
            \App\Models\PendukungDupak::JENIS_CHECK_SIMILARITY,
            \App\Models\PendukungDupak::JENIS_BUKTI_KORESPONDENSI,
            \App\Models\PendukungDupak::JENIS_ARTIKEL,
        ];
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Pendukung DUPAK</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Lengkapi dokumen pendukung DUPAK: check similarity, bukti korespondensi, tautan profil SINTA, dan artikel.
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

        {{-- Formulir tambah/edit dokumen pendukung --}}
        <form id="form-pendukung-dupak" method="POST" enctype="multipart/form-data"
              action="{{ $isEdit ? route('profil.pendukung-dupak.update', $editItem) : route('profil.pendukung-dupak.store') }}"
              class="mb-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">
                            {{ $isEdit ? 'Edit Dokumen Pendukung' : 'Tambah Dokumen Pendukung' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400">Pilih jenis dokumen, lalu unggah berkas PDF atau tempelkan tautan sesuai jenisnya.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6">
                    <div>
                        <label for="jenis" class="form-label">Jenis Dokumen <span class="text-rose-500">*</span></label>
                        <select id="jenis" name="jenis" required
                                data-select-search="Cari jenis dokumen…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jenis')])>
                            <option value="">Pilih jenis dokumen</option>
                            @foreach ($jenisList as $nilai => $label)
                                <option value="{{ $nilai }}" @selected($jenisTerpilih === $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- Isian berkas PDF untuk tiga jenis dokumen (check similarity, korespondensi, artikel). --}}
                    @foreach ($jenisBerkas as $jenisDokumen)
                        @php
                            // Berkas lama boleh dipertahankan saat menyunting jenis yang sama.
                            $berkasLama = $isEdit && $editItem->jenis === $jenisDokumen && filled($editItem->berkas_path)
                                ? $editItem
                                : null;
                            $wajibGrup = $berkasLama === null;
                        @endphp

                        <div data-pendukung-grup="{{ $jenisDokumen }}" @class(['hidden' => $jenisTerpilih !== $jenisDokumen])>
                            <label for="berkas_{{ $jenisDokumen }}" class="form-label">
                                {{ $jenisList[$jenisDokumen] }}
                                @if ($wajibGrup)
                                    <span class="text-rose-500">*</span>
                                @else
                                    <span class="text-[11px] font-medium text-slate-400">(opsional)</span>
                                @endif

                                {{-- Keterangan bukti korespondensi tampil sebagai popup saat ikon disorot. --}}
                                @if ($jenisDokumen === \App\Models\PendukungDupak::JENIS_BUKTI_KORESPONDENSI)
                                    <span class="group relative ml-1 inline-flex align-middle">
                                        <button type="button" aria-label="Keterangan Bukti Korespondensi"
                                                class="inline-flex size-5 cursor-help items-center justify-center rounded-full text-slate-400 transition hover:bg-teal-50 hover:text-teal-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/30">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <circle cx="12" cy="12" r="8.5" />
                                                <path d="M12 11v5.5M12 8v.5" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <span role="tooltip"
                                              class="pointer-events-none absolute bottom-full left-0 z-30 mb-2 hidden w-80 max-w-[calc(100vw-3rem)] rounded-xl border border-slate-200 bg-white p-3 text-left text-[11px] leading-relaxed font-normal text-slate-600 shadow-xl group-hover:block group-focus-within:block sm:left-1/2 sm:-translate-x-1/2">
                                            {{ $keteranganList[$jenisDokumen] }}
                                        </span>
                                    </span>
                                @endif
                            </label>

                            @if ($berkasLama)
                                <div class="mb-2.5 flex items-center justify-between gap-3 rounded-xl border border-teal-100 bg-teal-50/50 px-3.5 py-2.5">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-teal-600 shadow-sm ring-1 ring-teal-100">
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-mono text-[11px] font-semibold text-teal-800">{{ $berkasLama->namaBerkas() }}</p>
                                            <p class="text-[10px] text-teal-700/60">Berkas saat ini</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('pendukung-dupak.berkas', $berkasLama) }}" target="_blank" rel="noopener"
                                       class="shrink-0 rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-bold text-teal-700 shadow-sm ring-1 ring-teal-100 transition hover:bg-teal-50">
                                        Lihat
                                    </a>
                                </div>
                            @endif

                            <div data-file-field>
                                <input type="file" id="berkas_{{ $jenisDokumen }}" name="berkas"
                                       accept="application/pdf,.pdf" data-pdf-input data-max-kb="{{ $maksKb }}"
                                       @if ($wajibGrup) data-wajib @endif
                                       @required($wajibGrup && $jenisTerpilih === $jenisDokumen)
                                       @disabled($jenisTerpilih !== $jenisDokumen)
                                       @class([
                                           'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                           'border-rose-300 bg-rose-50/40' => $errors->has('berkas'),
                                           'border-slate-200' => ! $errors->has('berkas'),
                                       ])>
                                <p data-file-error class="form-error hidden" role="alert"></p>
                                @error('berkas') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                Format PDF, maksimal 10 MB.
                                {{ $keteranganList[$jenisDokumen] }}
                                @if ($berkasLama)
                                    Kosongkan jika tidak ingin mengganti berkas.
                                @endif
                            </p>
                        </div>
                    @endforeach

                    {{-- Jenis profil SINTA memakai tautan, bukan berkas unggahan. --}}
                    <div data-pendukung-grup="{{ \App\Models\PendukungDupak::JENIS_PROFIL_SINTA }}"
                         @class(['hidden' => $jenisTerpilih !== \App\Models\PendukungDupak::JENIS_PROFIL_SINTA])>
                        <label for="tautan" class="form-label">Tautan Profil SINTA <span class="text-rose-500">*</span></label>
                        <input type="url" id="tautan" name="tautan" data-wajib
                               value="{{ old('tautan', $editItem?->tautan) }}"
                               placeholder="https://sinta.kemdiktisaintek.go.id/authors/profile/…"
                               maxlength="2048" inputmode="url"
                               @required($jenisTerpilih === \App\Models\PendukungDupak::JENIS_PROFIL_SINTA)
                               @disabled($jenisTerpilih !== \App\Models\PendukungDupak::JENIS_PROFIL_SINTA)
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tautan')])>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Buka profil SINTA Anda, lalu salin tautan halaman rekam jejaknya (diawali http:// atau https://).
                        </p>
                        @error('tautan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    @if ($isEdit)
                        <a href="{{ route('profil.pendukung-dupak.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                    @endif
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah Dokumen' }}
                    </button>
                </div>
            </section>
        </form>

        {{-- Daftar dokumen pendukung DUPAK --}}
        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                            <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                            <path d="m9 14 2 2 3.5-4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Dokumen Pendukung</h3>
                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">{{ $items->count() }} data</span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">Satu baris untuk setiap dokumen pendukung DUPAK yang Anda simpan.</p>
                    </div>
                </div>

                <div class="relative w-full lg:w-72">
                    <label for="pendukung-search" class="sr-only">Cari dokumen pendukung</label>
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.13 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                    </svg>
                    <input type="search" id="pendukung-search" data-table-search placeholder="Cari jenis dokumen…"
                           class="form-input pl-9">
                </div>
            </div>

            <div class="overflow-x-auto p-2 sm:p-4">
                <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="no-sort w-16">No</th>
                            <th scope="col">Jenis Dokumen</th>
                            <th scope="col">Berkas / Tautan</th>
                            <th scope="col">Diunggah</th>
                            <th scope="col" class="no-sort text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="border-b border-slate-100">
                                <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                                        {{ $item->labelJenis() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    @if ($item->memakaiBerkas())
                                        <a href="{{ route('pendukung-dupak.berkas', $item) }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                                <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                                            </svg>
                                            Lihat Berkas
                                        </a>
                                        <p class="mt-0.5 max-w-[18rem] truncate text-[11px] text-slate-400" title="{{ $item->namaBerkas() }}">
                                            {{ $item->namaBerkas() }}
                                        </p>
                                    @else
                                        <a href="{{ $item->tautan }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-700 transition hover:text-teal-900">
                                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                            {{ $item->hostTautan() }}
                                        </a>
                                        <p class="mt-0.5 max-w-[18rem] truncate text-[11px] text-slate-400" title="{{ $item->tautan }}">
                                            {{ $item->tautan }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-xs text-slate-500">
                                    {{ $item->created_at->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('profil.pendukung-dupak.index', ['edit' => $item->id]) }}#form-pendukung-dupak"
                                           title="Edit" aria-label="Edit"
                                           class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('profil.pendukung-dupak.destroy', $item) }}"
                                              data-confirm="Hapus dokumen &quot;{{ $item->labelJenis() }}&quot;? Tindakan ini tidak dapat dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus" aria-label="Hapus"
                                                    class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                            <path d="M14 3.5V8h4.5M9 13h6M9 16.5h4" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada dokumen pendukung</p>
                                    <p class="mt-1 text-xs text-slate-400">Tambahkan melalui formulir di atas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

