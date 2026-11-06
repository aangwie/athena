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

            {{-- Satu tabel gabungan: "Keterangan Perorangan", "Unsur yang Dinilai", dan kolom
                 penandatangan. Tiap partial hanya menyumbang barisnya saja, sehingga garis
                 antar bagian menyambung tanpa jeda. --}}
            <div class="overflow-x-auto">
                <table class="tabel-rekap tabel-cetak w-full min-w-[68rem] border-collapse text-xs">
                    <caption class="sr-only">Formulir DUPAK: keterangan perorangan, unsur yang dinilai, dan pengesahan</caption>
                    <colgroup>
                        {{-- Lebar sama dengan rekap DUPAK: 4+3+3+3+27 + (6 x 10) = 100%. --}}
                        @foreach ([4, 3, 3, 3, 27, 10, 10, 10, 10, 10, 10] as $lebar)
                            <col style="width: {{ $lebar }}%">
                        @endforeach
                    </colgroup>
                    <thead>
                        @include('profil.pengajuan.partials.identitas', ['bagian' => 'kepala'])
                        @include('profil.pengajuan.partials.review', ['kolomPenilai' => true, 'kolomLinkBukti' => false, 'bagian' => 'kepala'])
                    </thead>
                    <tbody>
                        @include('profil.pengajuan.partials.identitas', ['bagian' => 'isi'])
                        @include('profil.pengajuan.partials.review', ['kolomPenilai' => true, 'kolomLinkBukti' => false, 'bagian' => 'isi'])
                        @include('profil.pengajuan.partials.pengesahan')
                    </tbody>
                </table>
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
