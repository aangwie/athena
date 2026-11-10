@extends('layouts.app')

@section('title', 'SKP-Dosen')
@section('breadcrumb', 'SKP-Dosen')
@section('page-title', 'SKP-Dosen')

@section('content')
    @php
        $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Penilaian
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">SKP-Dosen</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Hasil penilaian SKP dari Pejabat Penilai untuk Anda. Ajukan keberatan bila ada nilai yang tidak sesuai,
                    atau setujui bila sudah sesuai.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                                <rect x="9" y="3" width="6" height="4" rx="1.5" />
                                <path d="m9 13 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Penilaian SKP</h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600">{{ $skpList->count() }} penilaian</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Hanya penilaian atas nama Anda yang ditampilkan.</p>
                        </div>
                    </div>

                    <label class="relative block">
                        <span class="sr-only">Cari penilaian SKP</span>
                        <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="6.5" />
                                <path d="m16 16 4 4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="search" data-table-search placeholder="Cari jangka waktu…"
                               class="form-input pl-9 sm:w-64">
                    </label>
                </div>

                <div class="overflow-x-auto p-2 sm:p-4">
                    <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort w-16">No</th>
                                <th scope="col">Nama Dosen yang Dinilai</th>
                                <th scope="col">Jangka Waktu Penilaian</th>
                                <th scope="col">Jumlah</th>
                                <th scope="col">Nilai Perilaku Kerja</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($skpList as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $item->dosen?->name ?? '—' }}</p>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $item->dosen?->nidn }}</p>
                                        <p class="mt-0.5 text-[11px] text-slate-400">
                                            Penilai: {{ $item->pejabatPenilai?->name ?? '—' }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="badge-status bg-slate-50 text-slate-600 ring-slate-200">{{ $item->periodeLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-slate-700">{{ $fmt($item->jumlahNilai()) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($item->sudahDinilai())
                                            <span class="badge-status bg-teal-50 text-teal-700 ring-teal-200">{{ $fmt($item->nilaiPerilakuKerja()) }}</span>
                                        @else
                                            <span class="badge-status bg-amber-50 text-amber-700 ring-amber-200">Belum dinilai</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-col items-end gap-1.5">
                                            @if ($item->sudahDisetujui())
                                                <span class="badge-status bg-emerald-50 text-emerald-700 ring-emerald-200">
                                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.2 7.3a1 1 0 0 1-1.4 0L4.3 10.3a1 1 0 0 1 1.4-1.4l3.1 3 6.5-6.6a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                                                    </svg>
                                                    Disetujui
                                                </span>

                                                {{-- Pembatalan persetujuan hanya selama atasan penilai belum menyetujui. --}}
                                                @if ($item->bolehBatalPersetujuan())
                                                    <form method="POST" action="{{ route('skp-dosen.batal-setujui', $item) }}"
                                                          data-confirm="Batalkan persetujuan penilaian SKP periode {{ $item->periodeLabel() }}? Nilai dapat diubah kembali oleh Pejabat Penilai.">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-50">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                                                            </svg>
                                                            Batalkan Persetujuan
                                                        </button>
                                                    </form>
                                                @else
                                                    <p class="text-[11px] text-slate-400">Sudah disetujui atasan penilai</p>
                                                @endif
                                            @elseif ($item->sudahDinilai())
                                                <div class="flex flex-wrap items-center justify-end gap-1">
                                                    <a href="{{ route('skp-dosen.keberatan.edit', $item) }}"
                                                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-50">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M12 9v4M12 16.5v.5" stroke-linecap="round" />
                                                            <path d="M10.3 4.3 2.8 17.5A1.6 1.6 0 0 0 4.2 20h15.6a1.6 1.6 0 0 0 1.4-2.5L13.7 4.3a1.9 1.9 0 0 0-3.4 0Z" stroke-linejoin="round" />
                                                        </svg>
                                                        {{ $item->mengajukanKeberatan() ? 'Ubah Keberatan' : 'Ajukan Keberatan' }}
                                                    </a>

                                                    <form method="POST" action="{{ route('skp-dosen.setujui', $item) }}"
                                                          data-confirm="Setujui penilaian SKP periode {{ $item->periodeLabel() }} tanpa keberatan?">
                                                        @csrf
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-50">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                <path d="m5 12.5 4.5 4.5L19 7.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                            Setujui
                                                        </button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400">Menunggu penilaian</span>
                                            @endif

                                            @if ($item->mengajukanKeberatan())
                                                <p class="text-[11px] text-slate-400">
                                                    Keberatan diajukan {{ $item->tanggal_keberatan?->locale('id')->translatedFormat('j F Y') ?? '' }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center">
                                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                                                <rect x="9" y="3" width="6" height="4" rx="1.5" />
                                                <path d="M9 13h6M9 16h4" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada penilaian SKP</p>
                                        <p class="mt-1 text-xs text-slate-400">Penilaian tampil setelah admin menugaskan Pejabat Penilai SKP.</p>
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
                        Nilai perilaku kerja = rata-rata nilai keenam unsur.
                    </p>
                    <p>Keberatan yang diajukan tercetak pada blok 5 halaman cetak SKP.</p>
                </div>
            </div>
        </section>
    </div>
@endsection
