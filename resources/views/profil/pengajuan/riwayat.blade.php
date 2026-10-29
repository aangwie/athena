@extends('layouts.app')

@section('title', 'Riwayat Pengajuan')
@section('breadcrumb', 'Riwayat Pengajuan')
@section('page-title', 'Riwayat Pengajuan')

@section('content')
    @php
        // Format angka kredit: 12,5 — sama dengan rekap DUPAK.
        $angka = fn ($nilai) => $nilai === null
            ? '0'
            : rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',');
        $totalNilai = fn ($item) => $nilai->get($item->tahun.'-'.$item->semester)?->total;
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Pengajuan Jabatan Akademik
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Pengajuan</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Riwayat nilai PAK tiap periode beserta hasil ajuan DUPAK Anda.
                </p>
            </div>

            <a href="{{ route('profil.pengajuan.periode') }}" class="btn-primary w-auto justify-center px-5">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                </svg>
                Pengajuan Baru
            </a>
        </div>

        <section data-tabs class="section-card overflow-hidden">
            <div role="tablist" aria-label="Riwayat pengajuan"
                 class="flex flex-wrap gap-1.5 border-b border-slate-100 bg-slate-50/60 px-3 py-3 sm:px-4">
                <button type="button" role="tab" id="tab-nilai-pak" aria-controls="panel-nilai-pak"
                        aria-selected="true" tabindex="0" data-tab-target="nilai-pak"
                        class="tab-button tab-button-active">
                    <span class="flex size-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">1</span>
                    <span class="whitespace-nowrap">Riwayat Nilai PAK</span>
                </button>
                <button type="button" role="tab" id="tab-dupak" aria-controls="panel-dupak"
                        aria-selected="false" tabindex="-1" data-tab-target="dupak" class="tab-button">
                    <span class="flex size-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">2</span>
                    <span class="whitespace-nowrap">Riwayat Ajuan DUPAK</span>
                </button>
            </div>

            {{-- Tab 1: periode pengajuan nilai PAK beserta aksi perbaikan dan ajukan ulang. --}}
            <div role="tabpanel" id="panel-nilai-pak" aria-labelledby="tab-nilai-pak"
                 data-tab-panel="nilai-pak" tabindex="0" class="p-5 sm:p-6">
                <div class="mb-5">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900">Riwayat Nilai PAK</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
                        Periode yang sudah diajukan ke TIM PAK. Gunakan <span class="font-semibold text-slate-600">Perbaikan</span> untuk membetulkan entri,
                        lalu <span class="font-semibold text-slate-600">Ajukan Ulang</span> agar dinilai kembali.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col">Periode</th>
                                <th scope="col">Diajukan</th>
                                <th scope="col">Dinilai</th>
                                <th scope="col" class="text-right">Nilai PAK</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pengajuan as $item)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <p class="text-xs font-bold text-slate-700">{{ $item->periodeLabel() }}</p>
                                    </td>
                                    <td data-order="{{ $item->diajukan_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                        <span class="text-xs text-slate-600">{{ $item->diajukan_at?->translatedFormat('d M Y') ?? '—' }}</span>
                                    </td>
                                    <td data-order="{{ $item->dinilai_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                        <span class="text-xs text-slate-600">{{ $item->dinilai_at?->translatedFormat('d M Y') ?? '—' }}</span>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="text-xs font-extrabold text-slate-800">{{ $angka($totalNilai($item)) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $item->statusClasses() }}">{{ $item->statusLabel() }}</span>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if ($item->nilaiTerkunci())
                                                <span class="btn-secondary pointer-events-none px-3 py-1.5 text-xs opacity-50" aria-disabled="true">Perbaikan</span>
                                            @else
                                                <a href="{{ route('profil.pengajuan.create', ['tahun' => $item->tahun, 'semester' => $item->semester]) }}"
                                                   class="btn-secondary px-3 py-1.5 text-xs">
                                                    Perbaikan
                                                </a>
                                            @endif

                                            <form method="POST" action="{{ route('profil.pengajuan.ajukan-ulang') }}"
                                                  data-confirm="Nilai PAK periode {{ $item->periodeLabel() }} akan diajukan ulang ke TIM PAK untuk dinilai kembali."
                                                  data-confirm-title="Ajukan ulang nilai PAK?">
                                                @csrf
                                                <input type="hidden" name="tahun" value="{{ $item->tahun }}">
                                                <input type="hidden" name="semester" value="{{ $item->semester }}">
                                                <button type="submit" class="btn-primary w-auto px-3 py-1.5 text-xs"
                                                        @disabled($item->dinilai_at === null || $item->nilaiTerkunci())>
                                                    Ajukan Ulang
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-16 text-center">
                                        <p class="text-sm font-bold text-slate-700">Belum ada nilai PAK</p>
                                        <p class="mt-1 text-xs text-slate-400">Ajukan periode pertama Anda agar nilai dari TIM PAK tampil di sini.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Tab 2: hasil ajuan DUPAK beserta aksi lihat dan cetak PDF. --}}
            <div role="tabpanel" id="panel-dupak" aria-labelledby="tab-dupak"
                 data-tab-panel="dupak" tabindex="0" class="hidden p-5 sm:p-6">
                <div class="mb-5">
                    <h3 class="text-base font-extrabold tracking-tight text-slate-900">Riwayat Ajuan DUPAK</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
                        DUPAK yang sudah diajukan memakai nilai PAK dari TIM PAK beserta hasil pengajuannya.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col">Periode</th>
                                <th scope="col" class="text-right">Nilai PAK</th>
                                <th scope="col">Dinilai</th>
                                <th scope="col">Hasil Pengajuan</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dupak as $item)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <p class="text-xs font-bold text-slate-700">{{ $item->periodeLabel() }}</p>
                                    </td>
                                    <td class="text-right whitespace-nowrap">
                                        <span class="text-xs font-extrabold text-slate-800">{{ $angka($totalNilai($item)) }}</span>
                                    </td>
                                    <td data-order="{{ $item->dinilai_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                                        <span class="text-xs text-slate-600">{{ $item->dinilai_at?->translatedFormat('d M Y') ?? '—' }}</span>
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $item->statusClasses() }}">{{ $item->statusLabel() }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <a href="{{ route('profil.pengajuan.cetak', ['tahun' => $item->tahun, 'semester' => $item->semester]) }}"
                                               class="btn-secondary px-3 py-1.5 text-xs">
                                                Lihat
                                            </a>

                                            <a href="{{ route('profil.pengajuan.cetak', ['tahun' => $item->tahun, 'semester' => $item->semester, 'print' => 1]) }}"
                                               target="_blank" rel="noopener"
                                               class="btn-primary w-auto px-3 py-1.5 text-xs">
                                                Cetak PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-16 text-center">
                                        <p class="text-sm font-bold text-slate-700">Belum ada ajuan DUPAK</p>
                                        <p class="mt-1 text-xs text-slate-400">DUPAK bisa diajukan setelah nilai PAK periode tersebut tersedia.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection
