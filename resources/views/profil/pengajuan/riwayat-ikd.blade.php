@extends('layouts.app')

@section('title', 'Riwayat IKD')
@section('breadcrumb', 'Riwayat IKD')
@section('page-title', 'Riwayat IKD')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Pengajuan Jabatan Akademik Pertama
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat IKD</h2>
                <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                    Berkas Indikator Kinerja Dosen yang sudah diajukan beserta keputusan admin.
                </p>
            </div>

            <a href="{{ route('profil.pengajuan.syarat-khusus') }}" class="btn-secondary justify-center px-4">
                Berkas IKD
            </a>
        </div>

        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M7 3.5h6.5L18 8v12.5H7V3.5Z" stroke-linejoin="round" />
                            <path d="M13.5 3.5V8H18" stroke-linejoin="round" />
                            <path d="M9.75 12.5h5.5M9.75 16h3.5" stroke-linecap="round" />
                        </svg>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Usulan IKD</h3>
                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">{{ $daftar->count() }} data</span>
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-slate-500">Klik nama file untuk membuka berkas IKD yang sudah diajukan.</p>
                    </div>
                </div>

                <label class="relative block">
                    <span class="sr-only">Cari riwayat IKD</span>
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" />
                            <path d="m16 16 4 4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input type="search" data-table-search placeholder="Cari periode atau status…"
                           class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-64">
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
                            <th scope="col">Periode</th>
                            <th scope="col">File IKD</th>
                            <th scope="col">Status</th>
                            <th scope="col">Catatan</th>
                            <th scope="col">Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $item)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="text-xs font-bold text-slate-700">{{ $item->periodeLabel() }}</span>
                                </td>
                                <td class="whitespace-nowrap">
                                    @if ($item->ikd_path)
                                        <a href="{{ route('profil.pengajuan.syarat-khusus.berkas', ['jenis' => 'ikd', 'tahun' => $item->tahun, 'semester' => $item->semester]) }}"
                                           target="_blank" rel="noopener"
                                           class="text-[11px] font-bold text-teal-700 underline decoration-teal-300 underline-offset-2 hover:text-teal-800">
                                            Lihat IKD
                                        </a>
                                    @else
                                        <span class="text-[11px] text-slate-300">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge-status {{ $item->statusClasses() }}">{{ $item->statusLabel() }}</span>
                                </td>
                                <td class="max-w-xs">
                                    <span class="text-xs leading-relaxed text-slate-600">{{ $item->catatan ?: '—' }}</span>
                                </td>
                                <td data-order="{{ $item->updated_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                    <span class="text-xs text-slate-600">{{ $item->updated_at?->translatedFormat('d M Y') ?? '—' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <p class="text-sm font-bold text-slate-700">Belum ada usulan IKD</p>
                                    <p class="mt-1 text-xs text-slate-400">Usulan IKD muncul di sini setelah berkas diajukan ke admin.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
