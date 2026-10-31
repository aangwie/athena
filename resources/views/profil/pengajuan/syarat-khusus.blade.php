@extends('layouts.app')

@section('title', 'Syarat Khusus')
@section('breadcrumb', 'IKD')
@section('page-title', 'Indikator Kinerja Dosen')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengajuan Jabatan Akademik Pertama
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Indikator Kinerja Dosen</h2>
            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                Kelengkapan syarat khusus untuk dosen yang belum memiliki jabatan akademik.
            </p>
        </div>

        @php $terbuka = $syaratKhusus === null || $syaratKhusus->bisaDiubah(); @endphp

        @if (! $terbuka)
            {{-- Berkas sudah diajukan / diterima: unggahan dikunci sampai admin menolak. --}}
            <div class="section-card mb-6 flex items-start gap-3 p-5 sm:p-6">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700 ring-1 ring-teal-100">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9 12.5 11 14.5 15.5 10" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M12 3.5 5.5 6v5.2c0 4.2 2.8 7.9 6.5 9.3 3.7-1.4 6.5-5.1 6.5-9.3V6L12 3.5Z" stroke-linejoin="round" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-800">Berkas periode ini sudah diajukan</p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Status <span class="badge-status {{ $syaratKhusus->statusClasses() }}">{{ $syaratKhusus->statusLabel() }}</span>.
                        Berkas tidak dapat diubah selama menunggu verifikasi admin.
                    </p>
                    @if ($syaratKhusus->catatan)
                        <p class="mt-2 rounded-xl border border-amber-200 bg-amber-50/70 px-3.5 py-2.5 text-xs leading-relaxed text-amber-900">
                            <span class="font-bold">Catatan admin:</span> {{ $syaratKhusus->catatan }}
                        </p>
                    @endif
                </div>
            </div>
        @else
        @php $ditolak = collect($berkas)->keys()->filter(fn ($jenis) => $syaratKhusus?->statusJenis($jenis) === 'ditolak'); @endphp

        @if ($ditolak->isNotEmpty())
            {{-- Datang dari riwayat IKD: sebutkan berkas mana yang ditolak agar dosen tinggal unggah ulang. --}}
            <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5" role="alert">
                <p class="text-sm font-bold text-rose-700">Berkas ditolak admin — unggah ulang berkas di bawah ini.</p>
                <ul class="mt-1.5 space-y-1 text-xs leading-relaxed text-rose-600">
                    @foreach ($ditolak as $jenis)
                        <li>
                            <span class="font-bold">{{ strtoupper($jenis) }}:</span>
                            {{ $syaratKhusus->catatanJenis($jenis) ?: 'tanpa catatan.' }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Periksa kembali berkas Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">
                        Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum berkas dapat disimpan.
                    </p>
                </div>
            </div>
        @endif

        <form id="form-berkas" method="POST" action="{{ route('profil.pengajuan.syarat-khusus.store') }}"
              enctype="multipart/form-data" class="space-y-5">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-800">Periode Pengajuan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Berkas di bawah hanya berlaku untuk periode ini.</p>
                    </div>
                    <span class="ml-auto shrink-0 rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold whitespace-nowrap text-teal-700 ring-1 ring-teal-100 ring-inset">
                        {{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }}
                    </span>
                </div>

                <dl class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6">
                    <div>
                        <dt class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Tahun Akademik</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-800">{{ $tahun }}/{{ $tahun + 1 }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Semester</dt>
                        <dd class="mt-1 text-sm font-bold text-slate-800">{{ \App\Models\KegiatanDupak::SEMESTER[$semester] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Berkas Persyaratan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Unggah BKD dan IKD dalam satu file PDF.</p>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-5 sm:px-6">
                    @foreach ($berkas as $jenis => $item)
                        @php $path = $syaratKhusus?->{$jenis.'_path'}; @endphp

                        <div data-file-field>
                            <label for="{{ $jenis }}" class="form-label">
                                {{ $item['label'] }} <span class="text-rose-500">*</span>
                            </label>
                            <p class="mt-1 mb-2.5 text-[11px] leading-relaxed text-slate-400">{{ $item['deskripsi'] }}</p>

                            @if ($path)
                                <div class="mb-2.5 flex items-center justify-between gap-3 rounded-xl border border-teal-100 bg-teal-50/50 px-3.5 py-2.5">
                                    <div class="flex min-w-0 items-center gap-2">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-teal-600 shadow-sm ring-1 ring-teal-100">
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-mono text-[11px] font-semibold text-teal-800">{{ basename($path) }}</p>
                                            <p class="text-[10px] text-teal-700/60">Berkas saat ini</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('profil.pengajuan.syarat-khusus.berkas', ['jenis' => $jenis, 'tahun' => $tahun, 'semester' => $semester]) }}"
                                       target="_blank" rel="noopener"
                                       class="shrink-0 rounded-lg bg-white px-2.5 py-1.5 text-[10px] font-bold text-teal-700 shadow-sm ring-1 ring-teal-100 transition hover:bg-teal-50">
                                        Lihat
                                    </a>
                                </div>
                            @endif

                            <input type="file" id="{{ $jenis }}" name="{{ $jenis }}" accept="application/pdf,.pdf"
                                   data-pdf-input data-max-kb="{{ $item['max_mb'] * 1024 }}"
                                   @class([
                                       'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                       'border-rose-300 bg-rose-50/40' => $errors->has($jenis),
                                       'border-slate-200' => ! $errors->has($jenis),
                                   ])>
                            <p class="mt-1.5 text-[11px] text-slate-400">
                                Format PDF, maksimal {{ $item['max_mb'] }} MB.
                                @if ($path)
                                    Kosongkan jika tidak ingin mengganti berkas.
                                @endif
                            </p>
                            <p data-file-error class="form-error hidden" role="alert"></p>
                            @error($jenis) <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="flex items-center gap-2 text-[11px] text-slate-400">
                    <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Berkas terkunci setelah diajukan ke admin.
                </p>
                <div class="flex items-center gap-2">
                    <a href="{{ route('profil.pengajuan.periode-pertama') }}" class="btn-secondary flex-1 justify-center whitespace-nowrap sm:flex-none">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Ubah Periode
                    </a>
                    <button type="submit" class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                            <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                        </svg>
                        Simpan Berkas
                    </button>
                </div>
            </div>
        </form>
        @endif

        {{-- Tabel berkas tersimpan: dipakai dosen untuk mengajukan ke admin. --}}
        <section class="section-card mt-6 overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3.5">
                <h3 class="text-sm font-extrabold tracking-tight text-slate-900">Indikator Kinerja Dosen</h3>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500">
                    Ajukan berkas ke admin untuk diverifikasi. Berkas yang ditolak dapat diperbaiki lalu diajukan ulang.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Periode</th>
                            <th scope="col">File</th>
                            <th scope="col">Status</th>
                            <th scope="col">Catatan</th>
                            <th scope="col" class="no-sort text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $item)
                            {{-- Satu ajuan berisi 2 berkas (BKD & IKD): tiap file tampil sebagai baris sendiri. --}}
                            @foreach ($berkas as $jenis => $info)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="text-xs font-bold text-slate-700">{{ $item->periodeLabel() }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($item->{$jenis.'_path'})
                                        <a href="{{ route('profil.pengajuan.syarat-khusus.berkas', ['jenis' => $jenis, 'tahun' => $item->tahun, 'semester' => $item->semester]) }}"
                                           target="_blank" rel="noopener"
                                           class="text-[11px] font-bold text-teal-700 underline decoration-teal-300 underline-offset-2 hover:text-teal-800">
                                            {{ strtoupper($jenis) }}
                                        </a>
                                    @else
                                        <span class="text-[11px] text-slate-300">{{ strtoupper($jenis) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-status {{ $item->statusClasses($jenis) }}">{{ $item->statusLabel($jenis) }}</span>
                                </td>
                                <td class="max-w-xs">
                                    <span class="text-xs leading-relaxed text-slate-600">{{ $item->catatanJenis($jenis) ?: '—' }}</span>
                                </td>
                                <td class="text-right">
                                    {{-- Tombol ajuan ada di bawah tabel; di sini cukup pintasan perbaiki berkas yang ditolak. --}}
                                    @if ($item->statusJenis($jenis) === 'ditolak')
                                        <a href="{{ route('profil.pengajuan.syarat-khusus', ['tahun' => $item->tahun, 'semester' => $item->semester]) }}#form-berkas"
                                           class="btn-secondary px-3 py-1.5 text-xs">Edit</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-bold text-slate-700">Belum ada berkas tersimpan</p>
                                    <p class="mt-1 text-xs text-slate-400">Unggah BKD dan IKD pada formulir di atas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Ajuan: satu tombol untuk semua berkas lengkap (BKD & IKD) milik dosen. --}}
            @php
                $siap = $daftar->filter(fn ($item) => $item->bisaDiubah() && $item->bkd_path && $item->ikd_path);
            @endphp
            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs leading-relaxed text-slate-500">
                    @if ($siap->isEmpty())
                        Belum ada berkas lengkap (BKD &amp; IKD) yang bisa diajukan.
                    @else
                        <span class="font-bold text-slate-700">{{ $siap->count() }} periode siap diajukan.</span>
                        Berkas tidak dapat diubah sampai admin memberi keputusan.
                    @endif
                </p>
                <form method="POST" action="{{ route('profil.pengajuan.syarat-khusus.ajukan-semua') }}"
                      data-confirm="Berkas yang diajukan tidak dapat diubah sampai admin memberi keputusan."
                      data-confirm-title="Ajukan berkas?">
                    @csrf
                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                    <input type="hidden" name="semester" value="{{ $semester }}">
                    <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap" @disabled($siap->isEmpty())>Ajukan</button>
                </form>
            </div>
        </section>
    </div>
@endsection
