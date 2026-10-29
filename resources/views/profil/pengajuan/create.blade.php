@extends('layouts.app')

@section('title', 'Pengajuan Baru')
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Pengajuan Baru')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Pengajuan Jabatan Akademik
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Pengajuan Baru</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Pilih komponen kegiatan yang akan diusulkan. Setiap komponen diisi pada tabnya masing-masing.
                </p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-2.5">
                    <p class="text-[10px] font-bold tracking-wide text-teal-700 uppercase">Periode pengajuan</p>
                    <p class="text-sm font-extrabold text-teal-900">{{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }}</p>
                </div>
                <a href="{{ route('profil.pengajuan.periode') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M16.5 4.5 19.5 7.5 8 19H5v-3L16.5 4.5Z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Ubah Periode
                </a>
                <a href="{{ route('dashboard') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>

        <section data-tabs class="section-card overflow-hidden">
            <div role="tablist" aria-label="Komponen kegiatan"
                 class="flex gap-1.5 overflow-x-auto border-b border-slate-100 bg-slate-50/60 px-3 py-3 sm:px-4">
                @foreach ($komponen as $key => $item)
                    <button type="button" role="tab" id="tab-{{ $key }}"
                            aria-controls="panel-{{ $key }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                            tabindex="{{ $loop->first ? '0' : '-1' }}"
                            data-tab-target="{{ $key }}"
                            @class(['tab-button', 'tab-button-active' => $loop->first])>
                        <span class="flex size-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">{{ $loop->iteration }}</span>
                        <span class="whitespace-nowrap">{{ $item['label'] }}</span>
                    </button>
                @endforeach
            </div>

            @foreach ($komponen as $key => $item)
                <div role="tabpanel" id="panel-{{ $key }}" aria-labelledby="tab-{{ $key }}"
                     data-tab-panel="{{ $key }}" tabindex="0"
                     @class(['p-5 sm:p-6', 'hidden' => ! $loop->first])>
                    <div class="mb-5">
                        <h3 class="text-base font-extrabold tracking-tight text-slate-900">{{ $item['label'] }}</h3>
                        <p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">{{ $item['deskripsi'] }}</p>
                    </div>

                    @if ($key === 'pendidikan')
                        @include('profil.pengajuan.partials.pendidikan')
                        @include('profil.pengajuan.partials.bidang', ['bidang' => [
                            'kode' => 'B',
                            'nama' => 'Pelaksanaan Pendidikan',
                            'suffix' => 'pelaksanaan',
                            'tab' => 'pendidikan',
                            'route' => 'profil.pengajuan.pelaksanaan',
                            'jenis' => 'JENIS_PELAKSANAAN',
                            'sub' => 'SUB_PELAKSANAAN',
                            'edit' => $entriEditPelaksanaan,
                            'entri' => $kegiatanPelaksanaan,
                            'placeholderKomponen' => 'Pilih komponen pelaksanaan pendidikan',
                            'placeholderNama' => 'Contoh: Perkuliahan Pemrograman Web Kelas A Semester Ganjil',
                            'kelas' => 'mt-6',
                        ]])
                    @elseif ($key === 'penelitian')
                        @include('profil.pengajuan.partials.bidang', ['bidang' => [
                            'kode' => 'C',
                            'nama' => 'Penelitian',
                            'suffix' => 'penelitian',
                            'tab' => 'penelitian',
                            'route' => 'profil.pengajuan.penelitian',
                            'jenis' => 'JENIS_PENELITIAN',
                            'sub' => 'SUB_PENELITIAN',
                            'rincian' => 'RINCIAN_PENELITIAN',
                            'edit' => $entriEditPenelitian,
                            'entri' => $kegiatanPenelitian,
                            'placeholderKomponen' => 'Pilih komponen penelitian',
                            'placeholderNama' => 'Contoh: Publikasi Artikel pada Jurnal Ilmiah Internasional Bereputasi',
                        ]])
                    @else
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 px-5 py-12 text-center">
                            <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-white text-slate-300 shadow-sm ring-1 ring-slate-100">
                                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M6 3.5h8l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5Z" stroke-linejoin="round" />
                                    <path d="M14 3.5v4.5h4M9 13h6M9 16.5h4" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-bold text-slate-700">Formulir {{ $item['label'] }} belum tersedia</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $item['singkat'] }} Rincian isian akan tampil di bagian ini.</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
    </div>
@endsection
