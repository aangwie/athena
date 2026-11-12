@extends('layouts.app')

@section('title', 'Riwayat Dosen')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Riwayat Dosen')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Administrasi
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Dosen</h2>
            <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Lihat ringkasan profil, pendidikan, dan pekerjaan dosen beserta berkas unggahannya.</p>
        </div>

        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-extrabold tracking-tight text-slate-900">Daftar Dosen</h3>
                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">{{ $items->count() }} data</span>
                    </div>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">Pilih dosen untuk melihat riwayat dan mengunduh berkasnya.</p>
                </div>

                <label class="relative block sm:w-64">
                    <span class="sr-only">Cari dosen</span>
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" />
                            <path d="m16 16 4 4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input type="search" data-table-search placeholder="Cari nama, NIDN, prodi…"
                           class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none">
                    <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </label>
            </div>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                    <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">NIDN</th>
                                <th scope="col">Program Studi</th>
                                <th scope="col">Jabatan Akademik</th>
                                <th scope="col" class="no-sort w-40 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td class="font-bold text-slate-700">{{ $item->name }}</td>
                                    <td>{{ $item->nidn ?: '—' }}</td>
                                    <td>{{ $item->program_studi ?: '—' }}</td>
                                    <td>{{ $item->jabatan_fungsional ?: '—' }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('riwayat-dosen.show', $item) }}"
                                               class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                                Lihat Riwayat
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-xs text-slate-400">Belum ada data dosen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
        </section>
    </div>
@endsection