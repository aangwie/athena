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
            /* Padding body dilepas supaya seluruh lebar kertas terpakai. */
            body { background: #fff !important; padding: 0 !important; }
            .cetak-kertas { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
            table { font-size: 9px; }

            /* Tabel rekap DUPAK dipaksa muat satu halaman: lebar kolom tetap mengikuti
               <colgroup> dan teks dibungkus, sehingga kolom paling kanan (Lama/Baru/Jumlah)
               tidak terpotong. */
            .tabel-rekap,
            .tabel-cetak { table-layout: fixed !important; width: 100% !important; min-width: 0 !important; }

            .tabel-rekap th,
            .tabel-rekap td,
            .tabel-cetak th,
            .tabel-cetak td {
                padding: 1px 3px !important;
                overflow-wrap: anywhere;
                word-break: break-word;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
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
                    Daftar Usul Penetapan Angka Kredit<br>
                    Jabatan Akademik Dosen
                </h1>
                {{-- Nomor SK diisi admin saat keputusan verifikasi memilih "Setujui". --}}
                <p class="mt-1 text-xs font-semibold text-slate-700">
                    Nomor: {{ $pengajuan?->nomor_sk ?: str_repeat('.', 30) }}
                </p>
            </header>

            {{-- Identitas dosen pengusul ("Keterangan Perorangan") di atas kolom "Unsur yang Dinilai". --}}
            @include('profil.pengajuan.partials.identitas')

            <p class="mb-3 text-xs text-slate-500">
                Status pengajuan: <span class="font-semibold text-slate-700">{{ $pengajuan?->statusLabel() ?? 'Belum diajukan' }}</span>
                <span class="text-slate-300">·</span>
                Dinilai pada: <span class="font-semibold text-slate-700">{{ $pengajuan?->dinilai_at?->translatedFormat('d F Y') ?? '—' }}</span>
            </p>

            {{-- Formulir cetak memuat tambahan grup kolom "Tim Penilai" di samping "Instansi Pengusul". --}}
            @include('profil.pengajuan.partials.review', ['kolomPenilai' => true])

            {{-- Blok pengesahan: rekap identitas, pernyataan Ketua, dan Pendapat Tim Penilai. --}}
            @include('profil.pengajuan.partials.pengesahan', ['kolomPenilai' => true])
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
