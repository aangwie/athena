@extends('layouts.app')

@section('title', 'Status Jabatan Akademik')
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Pengajuan Baru')

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengajuan Jabatan Akademik
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Status Jabatan Akademik</h2>
            <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-slate-500">
                Alur pengajuan baru mengikuti status jabatan akademik Anda saat ini.
            </p>
        </div>

        {{-- Mode perbaikan dari halaman riwayat: periode entri yang akan diperbaiki
             ikut terbawa ke form agar dosen bisa memastikan jabatan & pangkat dulu. --}}
        @if ($periode)
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3">
                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 8v5m0 3.5h.01M12 3l9 16H3l9-16Z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <p class="text-sm leading-relaxed text-amber-800">
                    <span class="font-bold">Perbaikan entri {{ $periodeLabel }}.</span>
                    Periksa kembali jabatan akademik dan pangkat/golongan yang akan diajukan, lalu lanjut ke entri komponen.
                </p>
            </div>
        @endif

        <section class="section-card p-5 sm:p-6">
            {{-- Dosen yang sudah punya jabatan akademik memilih jabatan tujuan
                 sebelum lanjut ke pemilihan periode; pengajuan pertama (IKD) tidak. --}}
            @if ($jabatan)
                <form method="POST" action="{{ route('profil.pengajuan.jabatan.store') }}">
                    @csrf

                    {{-- Periode perbaikan ikut dikirim agar setelah disimpan langsung
                         masuk ke entri komponen periode tersebut. --}}
                    @if ($periode)
                        <input type="hidden" name="tahun" value="{{ $periode[0] }}">
                        <input type="hidden" name="semester" value="{{ $periode[1] }}">
                    @endif
            @endif

            <div class="flex items-start gap-4">
                @if ($jabatan)
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="m5 13 4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Jabatan Akademik Saat Ini</p>
                        <p class="mt-1 text-lg font-extrabold text-slate-800">{{ $jabatan }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            Anda sudah memiliki jabatan akademik. Lanjutkan ke pemilihan periode pengajuan seperti biasa.
                        </p>
                    </div>
                @else
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 8v5m0 3.5h.01M12 3l9 16H3l9-16Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold tracking-[0.12em] text-slate-400 uppercase">Jabatan Akademik</p>
                        <p class="mt-1 text-lg font-extrabold text-slate-800">Belum ada</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            Anda belum memiliki jabatan akademik. Pengajuan pertama memakai alur khusus: pilih periode
                            terlebih dahulu, lalu lengkapi syarat khusus.
                        </p>
                    </div>
                @endif
            </div>

            @if ($jabatan)
                {{-- Jabatan akademik yang dituju pada pengajuan periode ini. --}}
                <div class="mt-5">
                    <label for="jabatan_diajukan" class="form-label">Jabatan Akademik yang akan Diajukan <span class="text-rose-500">*</span></label>
                    <select id="jabatan_diajukan" name="jabatan_diajukan" required
                            data-select-search="Cari jabatan akademik…"
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan_diajukan')])>
                        <option value="">Pilih jabatan akademik</option>
                        @foreach ($jabatanList as $opsi)
                            <option value="{{ $opsi }}" @selected(old('jabatan_diajukan', $jabatanDiajukan) === $opsi)>{{ $opsi }}</option>
                        @endforeach
                    </select>
                    @error('jabatan_diajukan') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Target jabatan akademik yang akan diusulkan, terpisah dari jabatan akademik Anda saat ini.
                    </p>
                </div>

                {{-- Pangkat/golongan target kenaikan dari data kepegawaian/kepangkatan. --}}
                <div class="mt-5">
                    <label for="kepangkatan_diajukan_id" class="form-label">Pangkat/Golongan</label>
                    <select id="kepangkatan_diajukan_id" name="kepangkatan_diajukan_id"
                            data-select-search="Cari pangkat/golongan…"
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('kepangkatan_diajukan_id')])>
                        <option value="">Pilih pangkat/golongan</option>
                        @foreach ($pangkatList as $opsi)
                            <option value="{{ $opsi->id }}" @selected((int) old('kepangkatan_diajukan_id', $pangkatDiajukanId) === $opsi->id)>{{ $opsi->label() }}</option>
                        @endforeach
                    </select>
                    @error('kepangkatan_diajukan_id') <p class="form-error">{{ $message }}</p> @enderror
                    <p class="mt-1.5 text-[11px] text-slate-400">
                        Target pangkat/golongan yang akan diajukan, diambil dari data kepegawaian/kepangkatan.
                    </p>
                </div>
            @endif

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <a href="{{ $periode ? route('profil.pengajuan.riwayat') : route('dashboard') }}" class="btn-secondary justify-center">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M9.5 5.5 4 12l5.5 6.5M4.5 12H20" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    {{ $periode ? 'Kembali ke Riwayat' : 'Kembali ke Dashboard' }}
                </a>

                @if ($jabatan)
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        {{ $periode ? 'Lanjut ke Entri Komponen' : 'Lanjut Pilih Periode' }}
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4.5 12h15M14.5 5.5 21 12l-6.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                @else
                    <a href="{{ route('profil.pengajuan.periode-pertama') }}" class="btn-primary w-auto justify-center px-5">
                        Lanjut Pilih Periode
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M4.5 12h15M14.5 5.5 21 12l-6.5 6.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                @endif
            </div>

            @if ($jabatan)
                </form>
            @endif
        </section>
    </div>
@endsection
