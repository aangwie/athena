@extends('layouts.app')

@section('title', 'Riwayat Dosen')
@section('breadcrumb', auth()->user()->isAdmin() ? 'Administrasi' : 'Profil Dosen')
@section('page-title', 'Riwayat Dosen')

@php
    $isAdmin = auth()->user()->isAdmin();
    $profil = $dosen->profilDosen;
    $riwayatPendidikan = $dosen->riwayatPendidikan
        ->sortBy(fn ($item) => ($item->jenjangRank() * 10000) + $item->tahun_masuk)
        ->values();
    $warnaDokumen = [
        'ijazah_transkrip' => 'bg-teal-50 text-teal-700 ring-teal-100 hover:bg-teal-100',
        'akreditasi_prodi' => 'bg-indigo-50 text-indigo-700 ring-indigo-100 hover:bg-indigo-100',
        'disertasi_tesis' => 'bg-amber-50 text-amber-700 ring-amber-100 hover:bg-amber-100',
    ];
    $berkasUrl = fn ($item, $jenis) => $isAdmin
        ? route('riwayat-dosen.berkas', [$dosen, $item, $jenis])
        : route('profil.pendidikan.berkas', [$item, $jenis]);
    $skUrl = $isAdmin ? route('riwayat-dosen.sk', $dosen) : route('profil.pekerjaan.sk');
@endphp

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    {{ $isAdmin ? 'Administrasi' : 'Profil Dosen' }}
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $dosen->name }}</h2>
                <p class="mt-1.5 text-sm text-slate-500">NIDN {{ $dosen->nidn ?: '—' }} · {{ implode(', ', $dosen->roleLabels()) }}</p>
            </div>
            @if ($isAdmin)
                <a href="{{ route('riwayat-dosen.index') }}" class="btn-secondary shrink-0">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M19.5 12h-15M9 6.5 3.5 12 9 17.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Kembali
                </a>
            @endif
        </div>

        {{-- Profil --}}
        <section class="section-card">
            <div class="section-heading">
                <span class="section-number">01</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Profil</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Data pribadi dosen.</p>
                </div>
            </div>
            <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Tempat Lahir</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->tempat_lahir ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Tanggal Lahir</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->tanggal_lahir?->translatedFormat('d F Y') ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Jenis Kelamin</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->jenisKelaminLabel() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Agama</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->agamaLabel() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Alamat</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->alamat ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Nomor HP</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->no_hp ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Bidang Ilmu</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->bidang_ilmu ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">Program Studi</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $dosen->program_studi ?: '—' }}</p>
                </div>
            </div>
        </section>

{{-- Pendidikan --}}
        <section class="section-card mt-5">
            <div class="section-heading">
                <span class="section-number">02</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Pendidikan</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Pendidikan formal beserta berkas pendukungnya.</p>
                </div>
            </div>
            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Jenjang</th>
                            <th scope="col">Institusi / Jurusan</th>
                            <th scope="col" class="w-44">Tahun</th>
                            <th scope="col" class="w-80">Berkas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatPendidikan as $item)
                            <tr>
                                <td class="font-bold text-slate-700">{{ $item->jenjangLabel() }}</td>
                                <td>
                                    <p class="font-semibold text-slate-700">{{ $item->nama_institusi }}</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">{{ $item->jurusan ?: '—' }}</p>
                                </td>
                                <td>{{ $item->tahunLabel() }}</td>
                                <td>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        @foreach ($item->berkasList() as $jenis => $berkas)
                                            @if ($berkas['path'])
                                                <a href="{{ $berkasUrl($item, $jenis) }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-[10px] font-bold ring-1 ring-inset transition {{ $warnaDokumen[$jenis] }}">
                                                    {{ $berkas['label'] }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-xs text-slate-400">Belum ada riwayat pendidikan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

{{-- Pekerjaan --}}
        <section class="section-card mt-5">
            <div class="section-heading">
                <span class="section-number">03</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Pekerjaan</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Kepegawaian, masa kerja, pangkat, dan jabatan.</p>
                </div>
            </div>
            <div class="space-y-5 px-5 py-5 sm:px-6">
                <div>
                    <p class="text-[11px] font-bold tracking-wide text-slate-400 uppercase">NUPTK</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $profil?->nuptk ?? '—' }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <p class="text-xs font-bold text-slate-700">Masa Kerja</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr>
                                    <th scope="col">SK</th>
                                    <th scope="col">Tanggal SK</th>
                                    <th scope="col">Masa Kerja</th>
                                    <th scope="col" class="w-40">Berkas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (['pplp' => 'SK Dosen Tetap', 'jafa' => 'SK Jafa Terakhir', 'impasing' => 'SK Impasing Terakhir'] as $sk => $label)
                                    @php [$skTahun, $skBulan] = $profil?->masaKerja($sk) ?? [null, null]; @endphp
                                    <tr>
                                        <td class="font-semibold text-slate-700">{{ $label }}</td>
                                        <td>{{ $profil?->tanggalSkLabel($sk) ?? '—' }}</td>
                                        <td>
                                            @if ($skTahun !== null && $skBulan !== null)
                                                {{ $skTahun }} Tahun {{ str_pad((string) $skBulan, 2, '0', STR_PAD_LEFT) }} Bulan
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if ($sk === 'pplp' && $profil?->sk_dosen_tetap_path)
                                                <a href="{{ $skUrl }}" target="_blank" rel="noopener"
                                                   class="inline-flex items-center gap-1 rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset transition hover:bg-teal-100">
                                                    Lihat PDF
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

<div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <p class="text-xs font-bold text-slate-700">Riwayat Kepangkatan</p>
                        <ul class="mt-3 space-y-2">
                            @forelse ($dosen->riwayatKepangkatan->sortByDesc('tmt') as $item)
                                <li class="flex items-center justify-between gap-2 text-sm">
                                    <span class="font-semibold text-slate-700">{{ $item->pangkatLabel() }}</span>
                                    <span class="text-xs text-slate-400">{{ $item->tmtLabel() }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400">Belum ada riwayat kepangkatan.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                        <p class="text-xs font-bold text-slate-700">Riwayat Jabatan</p>
                        <ul class="mt-3 space-y-2">
                            @forelse ($dosen->riwayatJabatanStruktural->sortByDesc('tmt') as $item)
                                <li class="flex items-center justify-between gap-2 text-sm">
                                    <span class="font-semibold text-slate-700">
                                        {{ $item->jabatan_struktural_id ? $item->strukturalLabel() : $item->fungsionalLabel() }}
                                    </span>
                                    <span class="text-xs text-slate-400">{{ $item->tmtLabel() }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-slate-400">Belum ada riwayat jabatan.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <p class="text-xs font-bold text-slate-700">Riwayat Angka Kredit</p>
                    <ul class="mt-3 space-y-2">
                        @forelse ($dosen->riwayatAngkaKredit->sortByDesc('tahun') as $item)
                            <li class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-semibold text-slate-700">{{ $item->tahun }}</span>
                                <span class="text-xs text-slate-400">{{ $item->angkaKreditLabel() }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-slate-400">Belum ada riwayat angka kredit.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>
    </div>
@endsection