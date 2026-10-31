@extends('layouts.app')

@section('title', 'Portofolio Dosen')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', 'Portofolio Dosen')

@section('content')
    @php
        $riwayatList = $riwayatPendidikan ?? collect();

        $steps = [
            [
                'label' => 'Data pribadi',
                'status' => $profil ? 'Lengkap' : 'Belum lengkap',
                'state' => $profil ? 'done' : 'missing',
            ],
            [
                'label' => 'Riwayat pendidikan',
                'status' => $riwayatList->isNotEmpty() ? $riwayatList->count().' data' : 'Belum ditambahkan',
                'state' => $riwayatList->isNotEmpty() ? 'done' : 'missing',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        {{-- Header portofolio --}}
        <section class="relative mb-5 overflow-hidden rounded-2xl bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-950 p-6 shadow-card sm:p-7">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-24 -right-16 size-72 rounded-full bg-teal-400/20 blur-3xl"></div>
                <div class="absolute -bottom-24 left-1/4 size-56 rounded-full bg-emerald-300/10 blur-3xl"></div>
                <div class="absolute inset-0 opacity-[0.07]"
                     style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;"></div>
            </div>

            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 text-xl font-extrabold text-white shadow-inner backdrop-blur sm:size-20 sm:text-2xl">
                        {{ $user->initials() }}
                    </span>
                    <div class="min-w-0">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-0.5 text-[10px] font-bold tracking-wide text-teal-100 uppercase backdrop-blur">
                                Dosen
                            </span>
                            @if ($profil)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-200 ring-1 ring-emerald-300/20 ring-inset">
                                    <span class="size-1.5 rounded-full bg-emerald-300"></span>
                                    Portofolio aktif
                                </span>
                            @else
                                <span class="rounded-full bg-amber-400/15 px-2.5 py-0.5 text-[10px] font-bold text-amber-200 ring-1 ring-amber-300/20 ring-inset">
                                    Belum dilengkapi
                                </span>
                            @endif
                        </div>
                        <h2 class="truncate text-xl font-extrabold tracking-tight text-white sm:text-2xl">{{ $user->name }}</h2>
                        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-teal-100/70">
                            <span class="font-mono">NIDN {{ $user->nidn }}</span>
                            <span class="size-1 rounded-full bg-teal-300/40"></span>
                            <span>{{ $user->program_studi ?? 'Program studi belum diatur' }}</span>
                            <span class="size-1 rounded-full bg-teal-300/40"></span>
                            <span>{{ $user->jabatan_fungsional ?? 'Jabatan fungsional belum diatur' }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a href="{{ route('profil.data-pribadi.edit') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-teal-800 shadow-lg shadow-teal-950/20 transition hover:bg-teal-50">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                            <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                        </svg>
                        Edit Data Pribadi
                    </a>
                </div>
            </div>
        </section>

        @if (! $profil)
            <section class="mb-5 rounded-2xl border border-amber-200 bg-amber-50/70 p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M12 7.5V13M12 16.5h.01" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <p class="text-sm font-extrabold text-amber-900">Portofolio Anda belum lengkap</p>
                            <p class="mt-1 max-w-xl text-xs leading-relaxed text-amber-700/80">
                                Lengkapi data pribadi terlebih dahulu agar informasi profil tampil pada portofolio ini.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('profil.data-pribadi.edit') }}"
                       class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-amber-700">
                        Lengkapi Sekarang
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </div>
            </section>
        @endif

        <div class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-5 lg:col-span-2">
                {{-- Data pribadi --}}
                <section class="section-card">
                    <div class="section-heading items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800">Data Pribadi</h3>
                                <p class="mt-0.5 text-xs text-slate-400">Identitas dan informasi kontak dosen.</p>
                            </div>
                        </div>
                        <a href="{{ route('profil.data-pribadi.edit') }}" class="text-[11px] font-bold text-teal-700 transition hover:text-teal-800 hover:underline">
                            Ubah
                        </a>
                    </div>

                    @if ($profil)
                        <dl class="grid gap-x-6 gap-y-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
                            <div>
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">Nama Lengkap</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">NIDN</dt>
                                <dd class="mt-1 font-mono text-sm font-semibold text-slate-800">{{ $user->nidn }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">Tempat, Tanggal Lahir</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">
                                    {{ $profil->tempat_lahir ?? '—' }}
                                    @if ($profil->tanggal_lahir)
                                        <span class="text-slate-400">·</span> {{ $profil->tanggal_lahir->translatedFormat('d F Y') }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">No. HP</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $profil->no_hp ?? '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">Alamat</dt>
                                <dd class="mt-1 text-sm leading-relaxed font-medium text-slate-700">{{ $profil->alamat ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">Program Studi</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-800">{{ $user->program_studi ?? '—' }}</dd>
                            </div>
                        </dl>
                    @else
                        <div class="px-5 py-10 text-center sm:px-6">
                            <p class="text-sm font-semibold text-slate-500">Data pribadi belum diisi.</p>
                            <a href="{{ route('profil.data-pribadi.edit') }}" class="mt-2 inline-block text-xs font-bold text-teal-700 hover:underline">
                                Isi data pribadi sekarang
                            </a>
                        </div>
                    @endif
                </section>

                {{-- Riwayat pendidikan --}}
                <section class="section-card">
                    <div class="section-heading items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500">
                                <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                                    <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Pendidikan</h3>
                                    @if ($riwayatList->isNotEmpty())
                                        <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[9px] font-bold tracking-wide text-indigo-600 uppercase">
                                            {{ $riwayatList->count() }} riwayat
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-slate-400">Pendidikan formal dari jenjang dasar hingga perguruan tinggi.</p>
                            </div>
                        </div>
                        <a href="{{ route('profil.pendidikan.index') }}" class="text-[11px] font-bold text-teal-700 transition hover:text-teal-800 hover:underline">
                            Kelola
                        </a>
                    </div>

                    @if ($riwayatList->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach ($riwayatList as $item)
                                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                    <div class="flex min-w-0 items-start gap-3">
                                        <span @class([
                                            'badge-status shrink-0',
                                            'bg-teal-50 text-teal-700 ring-teal-200' => ! $item->isPerguruanTinggi(),
                                            'bg-indigo-50 text-indigo-700 ring-indigo-200' => $item->isPerguruanTinggi(),
                                        ])>
                                            {{ $item->jenjangLabel() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $item->nama_institusi }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-400">
                                                {{ $item->jurusan ?: '—' }}
                                                <span class="mx-1">·</span> {{ $item->tahunLabel() }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-1.5 pl-16 sm:pl-0">
                                        @if ($item->ijazah_path)
                                            <a href="{{ route('profil.pendidikan.berkas', [$item, 'ijazah']) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset transition hover:bg-teal-100">
                                                Ijazah
                                            </a>
                                        @endif
                                        @if ($item->transkrip_path)
                                            <a href="{{ route('profil.pendidikan.berkas', [$item, 'transkrip']) }}" target="_blank" rel="noopener"
                                               class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-[10px] font-bold text-indigo-700 ring-1 ring-indigo-100 ring-inset transition hover:bg-indigo-100">
                                                Transkrip
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-5 py-10 text-center sm:px-6">
                            <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-300 ring-1 ring-slate-100">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                    <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                                    <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-bold text-slate-600">Belum ada riwayat pendidikan</p>
                            <p class="mt-1 text-xs text-slate-400">Tambahkan riwayat pendidikan beserta bukti ijazah dan transkrip.</p>
                            <a href="{{ route('profil.pendidikan.create') }}" class="btn-small mt-4">
                                Tambah Riwayat
                            </a>
                        </div>
                    @endif
                </section>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                <section class="section-card p-5">
                    <h3 class="text-sm font-extrabold text-slate-800">Kelengkapan Profil</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Ringkasan status data portofolio Anda.</p>

                    <ul class="mt-4 space-y-3">
                        @foreach ($steps as $step)
                            <li class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2.5">
                                    @if ($step['state'] === 'done')
                                        <span class="flex size-6 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @elseif ($step['state'] === 'missing')
                                        <span class="flex size-6 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4.5a.75.75 0 0 0 1.5 0v-4.5Zm0 7.5a1 1 0 1 0-2 0 1 1 0 0 0 2 0Z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="flex size-6 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M5 10a.75.75 0 0 1 .75-.75h8.5a.75.75 0 0 1 0 1.5h-8.5A.75.75 0 0 1 5 10Z" />
                                            </svg>
                                        </span>
                                    @endif
                                    <span class="text-xs font-semibold text-slate-600">{{ $step['label'] }}</span>
                                </div>
                                <span @class([
                                    'text-[10px] font-bold',
                                    'text-emerald-600' => $step['state'] === 'done',
                                    'text-amber-600' => $step['state'] === 'missing',
                                    'text-slate-400' => $step['state'] === 'neutral',
                                ])>{{ $step['status'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('profil.data-pribadi.edit') }}"
                       class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-teal-50 px-3 py-2.5 text-xs font-bold text-teal-700 ring-1 ring-teal-100 ring-inset transition hover:bg-teal-100">
                        Perbarui Data Pribadi
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </section>

                <section class="section-card p-5">
                    <h3 class="text-sm font-extrabold text-slate-800">Kontak</h3>
                    <div class="mt-4 space-y-3.5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2.5" />
                                    <path d="m4.5 7 7.5 6 7.5-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold tracking-wide text-slate-400 uppercase">Email</p>
                                <p class="truncate text-xs font-semibold text-slate-700">{{ $user->email ?? 'Belum diatur' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M6.5 3.5h2.2l1.5 4-2 1.5a12 12 0 0 0 5.3 5.3l1.5-2 4 1.5v2.2a2.4 2.4 0 0 1-2.6 2.4A16.5 16.5 0 0 1 4.1 6.1a2.4 2.4 0 0 1 2.4-2.6Z" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold tracking-wide text-slate-400 uppercase">Telepon</p>
                                <p class="text-xs font-semibold text-slate-700">{{ $profil->no_hp ?? 'Belum diatur' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M12 21s6.5-5.2 6.5-11a6.5 6.5 0 1 0-13 0C5.5 15.8 12 21 12 21Z" stroke-linejoin="round" />
                                    <circle cx="12" cy="10" r="2.2" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[10px] font-bold tracking-wide text-slate-400 uppercase">Program Studi</p>
                                <p class="text-xs font-semibold text-slate-700">{{ $user->program_studi ?? 'Belum diatur' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                @if ($profil)
                    <p class="flex items-center justify-center gap-1.5 text-center text-[10px] text-slate-400">
                        <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v.5a.75.75 0 0 0 1.5 0v-.5ZM9 9a.75.75 0 0 0 0 1.5h.25v2.75H9a.75.75 0 0 0 0 1.5h2a.75.75 0 0 0 0-1.5h-.25V9.75A.75.75 0 0 0 10 9H9Z" clip-rule="evenodd" />
                        </svg>
                        Diperbarui {{ $profil->updated_at->diffForHumans() }}
                    </p>
                @endif
            </div>
        </div>
    </div>
@endsection
