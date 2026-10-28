@extends('layouts.app')

@section('title', 'Struktur Instansi')
@section('breadcrumb', 'Instansi')
@section('page-title', 'Struktur Instansi')

@section('content')
    @php
        $total = $items->count();
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Instansi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Struktur Instansi</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Susunan pejabat struktural instansi berdasarkan jabatan struktural dan data dosen.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('instansi.profil.edit') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4.5 20.5V5.5A1.5 1.5 0 0 1 6 4h7.5a1.5 1.5 0 0 1 1.5 1.5v15" stroke-linecap="round" />
                        <path d="M15 10h3.5A1.5 1.5 0 0 1 20 11.5v9M3 20.5h18" stroke-linecap="round" />
                        <path d="M8 8h3.5M8 11.5h3.5M8 15h3.5" stroke-linecap="round" />
                    </svg>
                    Data Instansi
                </a>
                <a href="{{ route('instansi.struktur.create') }}" class="btn-primary w-auto justify-center px-5">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                    </svg>
                    Tambah Pejabat
                </a>
            </div>
        </div>

        <section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                                <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                                <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                                <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Pejabat Struktural</h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 ring-1 ring-indigo-100 ring-inset">{{ $total }} data</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Jabatan diambil dari data master jabatan struktural dan pejabat dari data dosen.</p>
                        </div>
                    </div>

                    <label class="relative block">
                        <span class="sr-only">Cari struktur instansi</span>
                        <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="6.5" />
                                <path d="m16 16 4 4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="search" data-table-search placeholder="Cari jabatan atau nama…"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-72">
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
                                <th scope="col">Jabatan Struktural</th>
                                <th scope="col">Nama Pejabat</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>
                                        <span class="badge-status bg-indigo-50 text-indigo-700 ring-indigo-200">
                                            <span class="size-1.5 rounded-full bg-indigo-500"></span>
                                            {{ $item->jabatanStruktural?->nama ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <p class="text-sm font-bold text-slate-700">{{ $item->user?->name ?? '—' }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            <span class="font-mono">{{ $item->user?->nidn }}</span>
                                            <span class="mx-1">·</span>{{ $item->user?->program_studi }}
                                        </p>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('instansi.struktur.edit', $item) }}"
                                               class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                    <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                                </svg>
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('instansi.struktur.destroy', $item) }}"
                                                  data-confirm="Hapus {{ $item->jabatanStruktural?->nama }} atas nama {{ $item->user?->name }} dari struktur instansi?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-16 text-center">
                                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                                                <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                                                <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                                                <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada data struktur instansi</p>
                                        <p class="mt-1 text-xs text-slate-400">Tambahkan pejabat struktural beserta jabatannya.</p>
                                        <a href="{{ route('instansi.struktur.create') }}" class="btn-small mt-4">
                                            Tambah Pejabat
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3 text-[11px] text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="flex items-center gap-1.5">
                        <svg class="size-3.5 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.25v3h-.25a.75.75 0 0 0 0 1.5h2a.75.75 0 0 0 0-1.5h-.25v-3.75A.75.75 0 0 0 10.25 9H9Z" clip-rule="evenodd" />
                        </svg>
                        Klik judul kolom untuk mengurutkan data.
                    </p>
                    <p>Pejabat diambil dari dosen aktif.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
