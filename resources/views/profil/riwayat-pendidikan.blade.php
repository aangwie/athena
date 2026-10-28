@extends('layouts.app')

@section('title', 'Riwayat Pendidikan')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', 'Riwayat Pendidikan')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Pendidikan</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Riwayat pendidikan formal dosen akan ditampilkan pada halaman ini.
                </p>
            </div>
            <a href="{{ route('profil.show') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M19.5 12h-15M9 6.5 3.5 12 9 17.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Kembali ke Portofolio
            </a>
        </div>

        <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="pointer-events-none absolute -top-20 -right-16 size-64 rounded-full bg-teal-50 blur-3xl" aria-hidden="true"></div>

            <div class="relative px-6 py-12 text-center sm:px-10 sm:py-16">
                <span class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-teal-500 to-emerald-700 text-white shadow-lg shadow-teal-600/25">
                    <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                        <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                        <path d="M21 10v4.6" stroke-linecap="round" />
                    </svg>
                </span>

                <span class="mt-5 inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-[10px] font-bold tracking-wide text-amber-700 uppercase ring-1 ring-amber-200 ring-inset">
                    <span class="size-1.5 rounded-full bg-amber-500"></span>
                    Dalam pengembangan
                </span>

                <h3 class="mt-4 text-lg font-extrabold tracking-tight text-slate-900 sm:text-xl">
                    Modul Riwayat Pendidikan sedang disiapkan
                </h3>
                <p class="mx-auto mt-2 max-w-lg text-sm leading-relaxed text-slate-500">
                    Detail data pendidikan, termasuk jenjang, institusi, program studi, tahun lulus, dan berkas pendukung,
                    akan diinformasikan pada tahap pengembangan berikutnya.
                </p>

                <div class="mx-auto mt-8 max-w-md space-y-2.5 text-left">
                    @foreach ([
                        ['label' => 'Jenjang pendidikan', 'desc' => 'S1, S2, S3, dan pendidikan lainnya'],
                        ['label' => 'Institusi & program studi', 'desc' => 'Nama perguruan tinggi dan bidang studi'],
                        ['label' => 'Tahun masuk & lulus', 'desc' => 'Periode studi dan status kelulusan'],
                        ['label' => 'Berkas pendukung', 'desc' => 'Ijazah dan dokumen terkait'],
                    ] as $item)
                        <div class="flex items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/50 px-4 py-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-slate-300 shadow-sm ring-1 ring-slate-200">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="5" y="10.5" width="14" height="9.5" rx="2" />
                                    <path d="M8.5 10.5V7.5a3.5 3.5 0 1 1 7 0v3" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-xs font-bold text-slate-500">{{ $item['label'] }}</p>
                                <p class="text-[11px] text-slate-400">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-col items-center justify-center gap-2 sm:flex-row">
                    <a href="{{ route('profil.data-pribadi.edit') }}" class="btn-primary w-auto px-5">
                        Lengkapi Data Pribadi
                    </a>
                    <a href="{{ route('profil.show') }}" class="btn-secondary">
                        Lihat Portofolio
                    </a>
                </div>
            </div>
        </section>
    </div>
@endsection
