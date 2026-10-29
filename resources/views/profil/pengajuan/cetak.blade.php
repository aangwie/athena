<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DUPAK {{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    <style>
        /* Cetak ke PDF lewat dialog cetak peramban (A4 landscape). */
        @media print {
            @page { size: A4 landscape; margin: 10mm; }

            .cetak-aksi { display: none !important; }
            body { background: #fff !important; }
            .cetak-kertas { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
            table { font-size: 9px; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
    @php($dosen = auth()->user())

    <div class="mx-auto max-w-6xl">
        <div class="cetak-aksi mb-4 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('profil.pengajuan.create', ['tahun' => $tahun, 'semester' => $semester]) }}#review"
               class="btn-secondary">
                Kembali ke Review
            </a>
            <button type="button" onclick="window.print()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                Cetak / Simpan PDF
            </button>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <header class="mb-5 border-b border-slate-200 pb-4 text-center">
                <h1 class="text-base font-extrabold tracking-tight text-slate-900 uppercase">
                    Daftar Usulan Penetapan Angka Kredit (DUPAK)
                </h1>
                <p class="mt-1 text-xs text-slate-500">Periode {{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }}</p>
            </header>

            <dl class="mb-5 grid grid-cols-1 gap-x-6 gap-y-1.5 text-xs sm:grid-cols-2">
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">Nama</dt>
                    <dd class="font-bold text-slate-800">{{ $dosen->name }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">NIDN</dt>
                    <dd class="font-semibold text-slate-700">{{ $dosen->nidn ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">Program Studi</dt>
                    <dd class="font-semibold text-slate-700">{{ $dosen->program_studi ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">Jabatan Fungsional</dt>
                    <dd class="font-semibold text-slate-700">{{ $dosen->jabatan_fungsional ?? '—' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">Status</dt>
                    <dd class="font-semibold text-slate-700">{{ $pengajuan?->statusLabel() ?? 'Belum diajukan' }}</dd>
                </div>
                <div class="flex gap-2">
                    <dt class="w-28 shrink-0 text-slate-500">Dinilai pada</dt>
                    <dd class="font-semibold text-slate-700">{{ $pengajuan?->dinilai_at?->translatedFormat('d F Y') ?? '—' }}</dd>
                </div>
            </dl>

            @include('profil.pengajuan.partials.review')

            <div class="mt-10 grid grid-cols-2 gap-8 text-xs text-slate-600">
                <div class="text-center">
                    <p>Dosen yang mengusulkan</p>
                    <div class="h-16"></div>
                    <p class="font-bold text-slate-800 underline underline-offset-4">{{ $dosen->name }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">NIDN {{ $dosen->nidn ?? '—' }}</p>
                </div>
                <div class="text-center">
                    <p>Tim Penilai Angka Kredit</p>
                    <div class="h-16"></div>
                    <p class="font-bold text-slate-800 underline underline-offset-4">{{ $penilai?->name ?? '—' }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">NIDN {{ $penilai?->nidn ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>
    @if (request()->boolean('print'))
        <script>
            // ?print=1 dari tombol "Cetak PDF" di Riwayat Pengajuan: langsung buka dialog cetak.
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
