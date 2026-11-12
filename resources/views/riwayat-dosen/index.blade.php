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

        <section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="9" cy="8" r="3.25" />
                                <path d="M3.5 19.25a5.5 5.5 0 0 1 11 0" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Dosen</h3>
                            <p class="mt-1 text-xs text-slate-500">Pilih dosen untuk melihat riwayat dan mengunduh berkasnya.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
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
            </div>
        </section>
    </div>
@endsection