<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DUPAK {{ \App\Models\KegiatanDupak::labelPeriode($tahun, $semester) }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    <style>
        /* Cetak ke PDF lewat dialog cetak peramban (A4 portrait/landscape). */
        @media print {
            /* Margin halaman sengaja 0: margin cetak 1,5 cm digambar sendiri lewat padding
               .cetak-kertas, karena @page { margin } diabaikan peramban saat dialog cetak
               memakai "Margins: None". */
            @page { size: A4 landscape; margin: 0; }

            .cetak-aksi { display: none !important; }
            /* Padding body dilepas supaya seluruh lebar kertas terpakai. */
            body { background: #fff !important; padding: 0 !important; }
            /* 1,5 cm = margin atas, bawah, kiri, kanan kertas.
               ponytail: hanya potongan pertama/terakhir kotak ini yang dapat padding, jadi
               halaman lanjutan (DUPAK > 1 lembar) mulai dari tepi atas kertas. Pecah tiap
               halaman jadi .cetak-halaman seperti SKP kalau itu jadi masalah. */
            .cetak-kertas {
                padding: 1.5cm !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            table { font-size: 9px; }

            /* Blok "Menyetujui": offset 180px + separuh halaman terlalu lebar untuk area
               cetak (18 cm), jadi di kertas cukup separuh kanan seperti lampiran. */
            .cetak-ttd { padding-left: 0 !important; break-inside: avoid; }

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
    {{-- Diisi JavaScript sebelum window.print() untuk memilih portrait/landscape. --}}
    <style id="cetak-ukuran"></style>
</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
    <div class="mx-auto max-w-6xl">
        <div class="cetak-aksi mb-4 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('profil.pengajuan.create', ['tahun' => $tahun, 'semester' => $semester]) }}#review"
               class="btn-secondary">
                Kembali ke Review
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <label for="cetak-orientasi" class="text-sm font-semibold text-slate-600">Orientasi</label>
                <div class="w-40">
                    <select id="cetak-orientasi" class="form-select">
                        <option value="portrait">Portrait</option>
                        <option value="landscape" selected>Landscape</option>
                    </select>
                </div>
                <button type="button" onclick="cetakDupak()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
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

            {{-- Blok "Menyetujui" di luar tabel (seperti lampiran DUPAK): identitas Kepala
                 LLDIKTI diambil dari menu Instansi → bagian LLDIKTI. --}}
            <div class="cetak-ttd mt-6 w-full text-xs text-slate-700 sm:ml-auto sm:w-1/2 sm:pl-[180px]">
                <p>Menyetujui,</p>
                <p class="mt-0.5 font-semibold">Kepala LLDIKTI Wilayah {{ $instansi?->wilayah_lldikti ?: '................' }}</p>
                <div class="h-16"></div>
                <p class="font-semibold text-slate-800">{{ $instansi?->nama_pimpinan_lldikti ?: '................' }}</p>
                <p class="text-[11px] text-slate-600">NIP. {{ $instansi?->nip_pimpinan_lldikti ?: '................' }}</p>
            </div>
        </div>
    </div>
    <script>
        // Pilihan orientasi dipasang sebagai @page sebelum dialog cetak dibuka, karena ukuran
        // kertas tidak bisa diubah dari CSS statis saat runtime. Margin tetap 0; jarak 1,5 cm
        // ke tepi kertas berasal dari padding .cetak-kertas.
        function cetakDupak(orientasi = document.getElementById('cetak-orientasi')?.value ?? 'landscape') {
            document.getElementById('cetak-ukuran').textContent = `@page { size: A4 ${orientasi}; margin: 0; }`;
            window.print();
        }

        @if (request()->boolean('print'))
            // ?print=1 dari tombol "Cetak PDF" di Riwayat Pengajuan: langsung buka dialog cetak.
            window.addEventListener('load', () => cetakDupak('landscape'));
        @endif
    </script>
</body>
</html>
