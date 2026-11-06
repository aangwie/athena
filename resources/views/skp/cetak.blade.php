<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SKP {{ $skp->dosen?->name ?? '—' }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    <style>
        /* Cetak ke PDF lewat dialog cetak peramban (A4), mengikuti formulir SKP. */
        /* Tinggi ruang tanda tangan ditulis dalam cm agar tidak bergantung kelas Tailwind hasil build. */
        .cetak-ruang-ttd { height: 2.5cm; }
        /* Tinggi kotak blok 5–8 (ruang isian manual). */
        .cetak-kotak { height: 6cm; }

        @media print {
            /* Margin halaman sengaja 0: margin 2 cm digambar sendiri lewat padding .cetak-halaman,
               karena @page { margin } diabaikan peramban saat dialog cetak memakai "Margins: None". */
            @page { size: A4 portrait; margin: 0; }

            .cetak-aksi { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .cetak-kertas { max-width: none !important; border: 0 !important; box-shadow: none !important; padding: 0 !important; }
            .cetak-tabel { font-size: 10px; break-inside: avoid; }
            .cetak-tabel td { padding: 2px 4px !important; }
            .cetak-tabel tr { break-inside: avoid; }

            /* Satu section = satu halaman A4; padding 2 cm menjadi margin cetak. */
            .cetak-halaman { padding: 2cm; break-inside: avoid; }
            .cetak-halaman + .cetak-halaman { break-before: page; }
            /* Lembar tidak boleh dibatasi max-w-4xl, kalau tidak tabel tidak selebar area cetak. */
            .cetak-lembar { max-width: none !important; }

            /* Landscape: kertas lebih pendek (21 cm) daripada kebutuhan halaman 1,
               jadi isi diperkecil agar tetap 3 halaman; padding 2 cm tetap. */
            html.cetak-landscape .cetak-isi { zoom: 0.65; }
        }
    </style>
    {{-- Diisi JavaScript sebelum window.print() untuk memilih portrait/landscape. --}}
    <style id="cetak-ukuran"></style>

</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
    @php
        $bulan = fn ($tanggal) => $tanggal?->locale('id')->translatedFormat('j F Y') ?? '……';
        $blok = [
            1 => ['YANG DINILAI', $dinilai],
            2 => ['PEJABAT PENILAI', $pejabat],
            3 => ['ATASAN PEJABAT PENILAI', $atasan],
        ];
        $baris = [
            'nama' => 'N a m a',
            'nip' => 'N I P',
            'pangkat' => 'Pangkat, Golongan ruang, TMT',
            'jabatan' => 'Jabatan/Pekerjaan',
            'unit' => 'Unit Organisasi',
        ];

        // Nilai hanya terisi bila pejabat penilai sudah mengisi keenam unsur perilaku kerja.
        $unsur = \App\Models\Skp::UNSUR;
        $angka = fn ($nilai) => $nilai === null ? '……' : (string) $nilai;
        $desimal = fn ($nilai) => $nilai === null ? '……' : number_format((float) $nilai, 2, ',', '.');
        // Predikat: dibulatkan dulu karena rata-rata bisa desimal, sedangkan kategoriNilai() menerima int.
        $kategori = fn ($nilai) => $nilai === null ? '……' : '('.$skp->kategoriNilai((int) round($nilai)).')';
    @endphp

    <div class="cetak-lembar mx-auto max-w-4xl">
        <div class="cetak-aksi mb-4 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('skp.index') }}" class="btn-secondary">
                Kembali ke Daftar SKP
            </a>
            <div class="flex flex-wrap items-center gap-2">
                <label for="cetak-orientasi" class="text-sm font-semibold text-slate-600">Orientasi</label>
                <div class="w-40">
                    <select id="cetak-orientasi" class="form-select">
                        <option value="portrait">Portrait</option>
                        <option value="landscape">Landscape</option>
                    </select>
                </div>
                <button type="button" onclick="cetakSkp()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            {{-- Halaman 1: judul sampai blok 5. Tiap .cetak-halaman = satu lembar A4. --}}
            <section class="cetak-halaman">
                <div class="cetak-isi">
                    <header class="text-center">
                        <h1 class="text-sm font-extrabold tracking-tight uppercase">
                            Penilaian Prestasi Kerja<br>Dosen
                        </h1>
                    </header>

                    <div class="mt-4 flex flex-wrap items-start justify-between gap-2 text-[11px] font-semibold text-slate-700">
                        <div>
                            <p>{{ $instansi?->nama_instansi ?? '—' }}</p>
                            <p>Prodi {{ $skp->prodi?->nama_prodi ?? '—' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold uppercase">Jangka Waktu Penilaian</p>
                            <p>BULAN : {{ $bulan($skp->periode_mulai) }} s/d {{ $bulan($skp->periode_akhir) }}</p>
                        </div>
                    </div>

                    {{-- Blok 1–3: identitas dosen yang dinilai, pejabat penilai, dan atasannya. --}}
                    <table class="cetak-tabel mt-2 w-full border-collapse border border-slate-900 text-[11px]">
                        <caption class="sr-only">Identitas yang dinilai, pejabat penilai, dan atasan pejabat penilai</caption>
                        <tbody>
                            @foreach ($blok as $nomor => [$judul, $data])
                                <tr>
                                    <td class="w-8 border border-slate-900 px-2 py-1 align-top font-bold">{{ $nomor }}.</td>
                                    <td colspan="2" class="border border-slate-900 px-2 py-1 font-bold uppercase">{{ $judul }}</td>
                                </tr>
                                @foreach ($baris as $kunci => $label)
                                    <tr>
                                        <td class="border border-slate-900"></td>
                                        <td class="w-1/2 border border-slate-900 px-2 py-1">{{ $label }}</td>
                                        <td class="border border-slate-900 px-2 py-1 font-semibold">{{ $data[$kunci] }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Blok 4: unsur yang dinilai. SKP (60%) dan Nilai Perilaku Kerja tidak lagi dicetak;
                         Nilai Prestasi Kerja langsung memakai rata-rata perilaku kerja beserta predikatnya. --}}
                    <table class="cetak-tabel w-full border-collapse border border-t-0 border-slate-900 text-[11px]">
                        <caption class="sr-only">Unsur yang dinilai beserta jumlah nilai</caption>
                        <colgroup>
                            <col style="width: 5%">
                            <col style="width: 45%">
                            <col style="width: 12%">
                            <col style="width: 18%">
                            <col style="width: 20%">
                        </colgroup>
                        <tbody>
                            <tr>
                                <td colspan="4" class="border border-slate-900 px-2 py-1 font-bold">4. UNSUR YANG DINILAI</td>
                                <td class="border border-slate-900 px-2 py-1 text-center font-bold">Jumlah</td>
                            </tr>

                            @foreach ($unsur as $kunci => $label)
                                @php
                                    $nilai = $skp->{'nilai_'.$kunci};
                                @endphp
                                <tr>
                                    @if ($loop->first)
                                        <td rowspan="{{ count($unsur) + 2 }}" class="w-24 border border-slate-900 px-2 py-1 align-top font-semibold">Perilaku Kerja</td>
                                    @endif
                                    <td class="border border-slate-900 px-2 py-1">{{ $loop->iteration }}. {{ $label }}</td>
                                    <td class="border border-slate-900 px-2 py-1 text-center font-semibold">{{ $angka($nilai) }}</td>
                                    <td class="border border-slate-900 px-2 py-1 text-center">{{ $kategori($nilai) }}</td>
                                    <td class="border border-slate-900 px-2 py-1 text-center"></td>
                                </tr>
                            @endforeach

                            <tr>
                                <td class="border border-slate-900 px-2 py-1">{{ count($unsur) + 1 }}. Jumlah</td>
                                <td class="border border-slate-900 px-2 py-1 text-center font-bold">{{ $angka($skp->jumlahNilai()) }}</td>
                                <td class="border border-slate-900 px-2 py-1 text-center"></td>
                                <td class="border border-slate-900 px-2 py-1 text-center bg-slate-100"></td>
                            </tr>
                            <tr>
                                <td class="border border-slate-900 px-2 py-1">{{ count($unsur) + 2 }}. Nilai rata - rata</td>
                                <td class="border border-slate-900 px-2 py-1 text-center font-semibold">{{ $desimal($skp->rataRataNilai()) }}</td>
                                <td class="border border-slate-900 px-2 py-1 text-center">{{ $kategori($skp->rataRataNilai()) }}</td>
                                <td class="border border-slate-900 px-2 py-1 text-center bg-slate-100"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="border border-slate-900 px-2 py-1 text-center font-bold uppercase">Nilai Prestasi Kerja</td>
                                {{-- Rata-rata perilaku kerja sekaligus predikatnya. --}}
                                <td class="border border-slate-900 px-2 py-1 text-center font-bold">
                                    {{ $desimal($skp->rataRataNilai()) }}<br>{{ $kategori($skp->rataRataNilai()) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    {{-- Blok 5: halaman 1. Ruang keberatan dosen yang dinilai (diisi manual). --}}
                    <table class="cetak-tabel w-full border-collapse border border-t-0 border-slate-900 text-[11px]">
                        <caption class="sr-only">Keberatan dari dosen yang dinilai</caption>
                        <tbody>
                            <tr>
                                {{-- Judul di atas, Tanggal di dasar kotak: sisa ruang dipakai untuk tanda tangan. --}}
                                <td class="border border-slate-900 p-0 align-top">
                                    <div class="cetak-kotak flex flex-col justify-between px-2 py-1.5">
                                        <div>
                                            <p class="font-bold uppercase">5. KEBERATAN DARI DOSEN</p>
                                            <p class="font-bold uppercase">YANG DINILAI (APABILA ADA)</p>
                                        </div>
                                        <p class="text-center font-bold">Tanggal, ............................</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </section>

            {{-- Halaman 2: blok 6–8. Tanggapan, keputusan, dan rekomendasi (diisi manual). --}}
            <section class="cetak-halaman">
                <div class="cetak-isi">
                    <table class="cetak-tabel w-full border-collapse border border-slate-900 text-[11px]">
                        <caption class="sr-only">Tanggapan, keputusan, dan rekomendasi</caption>
                        <tbody>
                            @foreach ([
                                '6. TANGGAPAN PEJABAT PENILAI|ATAS KEBERATAN',
                                '7. KEPUTUSAN ATASAN PEJABAT|PENILAI ATAS KEBERATAN',
                            ] as $judul)
                                <tr>
                                    <td class="border border-slate-900 p-0 align-top">
                                        <div class="cetak-kotak flex flex-col justify-between px-2 py-1.5">
                                            <div>
                                                @foreach (explode('|', $judul) as $barisJudul)
                                                    <p class="font-bold uppercase">{{ $barisJudul }}</p>
                                                @endforeach
                                            </div>
                                            <p class="text-center font-bold">Tanggal, ............................</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr>
                                {{-- Judul di atas, ruang kosong di bawah untuk catatan rekomendasi. --}}
                                <td class="border border-slate-900 p-0 align-top">
                                    <div class="cetak-kotak px-2 py-1.5">
                                        <p class="font-bold uppercase">8. Rekomendasi</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Halaman 3: blok 9–11. Stempel dan tanda tangan dibubuhkan manual;
                 nama & NIP di dasar kotak supaya ada ruang tanda tangan di atasnya. --}}
            <section class="cetak-halaman">
                <div class="cetak-isi">
                    <table class="cetak-tabel w-full border-collapse border border-slate-900 text-[11px]">
                        <caption class="sr-only">Pengesahan pejabat penilai, dosen yang dinilai, dan atasan pejabat penilai</caption>
                        <tbody>
                            @foreach ([
                                ['9. DIBUAT TANGGAL,', 'PEJABAT PENILAI', $pejabat, 'justify-end text-right'],
                                ['10. DITERIMA TANGGAL,', 'DOSEN YANG DINILAI', $dinilai, 'justify-start text-left'],
                                ['11. DITERIMA TANGGAL,', 'ATASAN PEJABAT PENILAI', $atasan, 'justify-end text-right'],
                            ] as [$tanggal, $pihak, $data, $posisi])
                                <tr>
                                    <td class="border border-slate-900 p-0 align-top">
                                        <div class="flex px-3 py-2 {{ $posisi }}">
                                            <div class="w-1/2">
                                                <p class="font-bold">{{ $tanggal }} ............................</p>
                                                <p class="font-bold uppercase">{{ $pihak }}</p>

                                                {{-- Ruang tanda tangan + stempel antara label jabatan dan nama. --}}
                                                <div class="cetak-ruang-ttd" aria-hidden="true"></div>

                                                <p class="font-bold underline">{{ $data['nama'] }}</p>
                                                <p>NIP. {{ $data['nip'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <script>
        // Pilihan orientasi dipasang sebagai @page sebelum dialog cetak dibuka,
        // karena ukuran kertas tidak bisa diubah dari CSS statis saat runtime.
        // Margin tetap 0; jarak 2 cm ke tepi kertas berasal dari padding .cetak-halaman.
        function cetakSkp(orientasi = document.getElementById('cetak-orientasi')?.value ?? 'portrait') {
            document.getElementById('cetak-ukuran').textContent = `@page { size: A4 ${orientasi}; margin: 0; }`;
            document.documentElement.classList.toggle('cetak-landscape', orientasi === 'landscape');
            window.print();
        }

        @if (request()->boolean('print'))
            // ?print=1 dari tombol cetak di daftar SKP: langsung buka dialog cetak (portrait).
            window.addEventListener('load', () => cetakSkp('portrait'));
        @endif
    </script>
</body>
</html>

