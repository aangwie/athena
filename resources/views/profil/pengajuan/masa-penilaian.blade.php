@extends('layouts.app')

@section('title', 'Masa Penilaian')
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Pengajuan Baru')

@section('content')
    @php
        $tahunSekarang = now()->year;

        $mulaiBulan = (int) old('mulai_bulan', $masaPenilaian?->mulai?->month ?? 1);
        $mulaiTahun = (int) old('mulai_tahun', $masaPenilaian?->mulai?->year ?? $tahunSekarang);
        $akhirBulan = (int) old('akhir_bulan', $masaPenilaian?->akhir?->month ?? 12);
        $akhirTahun = (int) old('akhir_tahun', $masaPenilaian?->akhir?->year ?? $tahunSekarang);

        // Hitung mundur 10 tahun dari tahun berjalan. Tahun yang sudah tersimpan
        // tetap disertakan agar tidak hilang dari pilihan saat menyimpan ulang.
        $pilihanTahun = collect(range($tahunSekarang, $tahunSekarang - 10))
            ->push($mulaiTahun, $akhirTahun)
            ->unique()
            ->sortDesc()
            ->values();
    @endphp

    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengajuan Jabatan Akademik
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Masa Penilaian</h2>
            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                Tentukan rentang masa penilaian DUPAK yang diajukan. Setelah diisi, Anda melanjutkan ke halaman
                Pengajuan Jabatan Akademik periode {{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }}.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <div>
                    <p class="font-bold">Periksa kembali masa penilaian Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">{{ $errors->first() }}</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('profil.pengajuan.masa-penilaian.store') }}" class="section-card p-5 sm:p-6">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <div class="grid gap-5 sm:grid-cols-2">
                <fieldset>
                    <legend class="form-label">Dari <span class="text-rose-500">*</span></legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="mulai_bulan" class="sr-only">Bulan awal</label>
                            <select id="mulai_bulan" name="mulai_bulan" class="form-select" required data-select-search="Cari bulan…">
                                @foreach (\App\Models\MasaPenilaian::BULAN as $nilai => $nama)
                                    <option value="{{ $nilai }}" @selected($nilai === $mulaiBulan)>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="mulai_tahun" class="sr-only">Tahun awal</label>
                            <select id="mulai_tahun" name="mulai_tahun" class="form-select" required data-select-search="Cari tahun…">
                                @foreach ($pilihanTahun as $opsi)
                                    <option value="{{ $opsi }}" @selected($opsi === $mulaiTahun)>{{ $opsi }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="form-label">Sampai dengan <span class="text-rose-500">*</span></legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="akhir_bulan" class="sr-only">Bulan akhir</label>
                            <select id="akhir_bulan" name="akhir_bulan" class="form-select" required data-select-search="Cari bulan…">
                                @foreach (\App\Models\MasaPenilaian::BULAN as $nilai => $nama)
                                    <option value="{{ $nilai }}" @selected($nilai === $akhirBulan)>{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="akhir_tahun" class="sr-only">Tahun akhir</label>
                            <select id="akhir_tahun" name="akhir_tahun" class="form-select" required data-select-search="Cari tahun…">
                                @foreach ($pilihanTahun as $opsi)
                                    <option value="{{ $opsi }}" @selected($opsi === $akhirTahun)>{{ $opsi }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>

            <p class="mt-4 rounded-xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-xs leading-relaxed text-teal-900">
                Masa penilaian:
                <span class="font-bold">{{ \App\Models\MasaPenilaian::BULAN[$mulaiBulan] }} {{ $mulaiTahun }}</span>
                s.d.
                <span class="font-bold">{{ \App\Models\MasaPenilaian::BULAN[$akhirBulan] }} {{ $akhirTahun }}</span>
            </p>

            <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ route('profil.pengajuan.periode') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Ubah Periode
                </a>
                <button type="submit" class="btn-primary w-auto px-5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4.5 12h15M14.5 5.5 21 12l-6.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Lanjut Isi Komponen
                </button>
            </div>
        </form>
    </div>
@endsection

