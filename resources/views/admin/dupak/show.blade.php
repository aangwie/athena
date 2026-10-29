@extends('layouts.app')

@section('title', 'Verifikasi DUPAK')
@section('breadcrumb', 'Verifikasi DUPAK')
@section('page-title', 'Verifikasi DUPAK')

@section('content')
    @php
        // Keputusan yang sudah tersimpan dipakai sebagai pilihan awal.
        $keputusanAwal = old('keputusan', in_array($pengajuan->status, ['disetujui', 'perlu_perbaikan', 'ditolak'], true)
            ? $pengajuan->status
            : 'disetujui');

        $pilihan = [
            'disetujui' => [
                'label' => 'Setujui',
                'deskripsi' => 'DUPAK diterima dan lanjut ke penetapan angka kredit.',
                'ikon' => 'm5 12.5 4.5 4.5L19 7',
            ],
            'perlu_perbaikan' => [
                'label' => 'Butuh Perbaikan',
                'deskripsi' => 'Dikembalikan ke dosen dengan catatan perbaikan.',
                'ikon' => 'M12 7.5V13M12 16.5h.01',
            ],
            'ditolak' => [
                'label' => 'Tolak',
                'deskripsi' => 'DUPAK tidak dapat diproses.',
                'ikon' => 'm6 6 12 12M18 6 6 18',
            ],
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Verifikasi DUPAK
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">{{ $pengajuan->user->name }}</h2>
                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    <span>{{ $pengajuan->periodeLabel() }}</span>
                    <span class="text-slate-300">·</span>
                    <span>{{ $pengajuan->user->nidn ?? '—' }} · {{ $pengajuan->user->program_studi ?? '—' }}</span>
                    <span class="badge-status {{ $pengajuan->statusClasses() }}">{{ $pengajuan->statusLabel() }}</span>
                </p>
            </div>

            <a href="{{ route('dashboard') }}#dupak" class="btn-secondary">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M10.5 6 4.5 12l6 6M4.5 12h15" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>

        @if ($pengajuan->catatan)
            <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3">
                <p class="text-[11px] font-bold tracking-wide text-amber-700 uppercase">Catatan terakhir</p>
                <p class="mt-1 text-sm leading-relaxed text-amber-900">{{ $pengajuan->catatan }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('verifikasi-dupak.update', $pengajuan) }}"
              data-confirm="Keputusan verifikasi akan disimpan dan langsung terlihat oleh dosen."
              data-confirm-title="Simpan keputusan?">
            @csrf
            @method('PUT')

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:p-4">
                @include('profil.pengajuan.partials.review')
            </div>

            <div data-keputusan class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h3 class="text-sm font-extrabold tracking-tight text-slate-900">Keputusan Verifikasi</h3>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    Pilih satu keputusan, isi alasan bila diperlukan, lalu tekan Simpan.
                </p>

                <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
                    @foreach ($pilihan as $nilai => $opsi)
                        <label class="choice-card items-center">
                            <input type="radio" name="keputusan" value="{{ $nilai }}" class="sr-only" @checked($keputusanAwal === $nilai)>
                            <span class="choice-icon">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8.5" />
                                    <path d="{{ $opsi['ikon'] }}" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-slate-800">{{ $opsi['label'] }}</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">{{ $opsi['deskripsi'] }}</span>
                            </span>
                            <span class="choice-indicator">
                                <svg class="size-3 opacity-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div data-catatan class="mt-4">
                    <label for="catatan" class="form-label">Alasan / catatan untuk dosen</label>
                    <textarea id="catatan" name="catatan" rows="3" class="form-input"
                              placeholder="Tuliskan alasan perbaikan atau penolakan...">{{ old('catatan', $pengajuan->catatan) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400">Wajib diisi saat memilih Butuh Perbaikan atau Tolak.</p>
                    @error('catatan')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                @error('keputusan')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Keputusan langsung tampil pada halaman Riwayat Pengajuan dosen.</p>
                    <button type="submit" class="btn-primary w-auto px-5 py-2.5 text-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
@endsection
