<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BAKI {{ $baki->bapSenat?->dosen?->name ?? '—' }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    @php
        $bap = $baki->bapSenat;
        $tanggalBap = $bap?->tanggal_bap?->locale('id')->translatedFormat('j F Y') ?: '-';
        $kotaTanggal = ($kota ?: str_repeat('.', 15)).', '.($baki->tanggal_surat?->locale('id')->translatedFormat('j F Y') ?? str_repeat('.', 10));
        $namaKetua = $ketua?->user?->name ?? '';
        $nidnKetua = 'NIDN. '.($ketua?->user?->nidn ?: str_repeat('.', 20));
        // Daftar hadir selalu menyediakan minimal 12 baris.
        $jumlahBaris = max(12, $anggota->count());
    @endphp

    <style>
        /* Cetak ke PDF lewat dialog cetak peramban (A4 portrait), mengikuti formulir SKP. */
        /* Margin awal 1,5 cm semua sisi; JS memperbarui nilainya sebelum dicetak. */
        :root { --cetak-margin: 1.5cm; }

        /* Pratinjau layar: jarak ke tepi kertas digambar sendiri lewat padding.
           Seluruh naskah memakai Times New Roman 16px; kop mengatur ukurannya sendiri
           (18px nama instansi, 10px alamat instansi).
           Jarak baris dirapatkan (1,375 naskah, 1,25 tabel identitas) supaya naskah 16px
           tetap muat satu halaman A4/F4 pada margin 1,5 cm. */
        .cetak-kertas {
            padding: var(--cetak-margin);
            font-family: 'Times New Roman', Times, serif;
            font-size: 16px;
        }
        /* Tiap halaman dipisah rapi pada pratinjau layar (halaman tersembunyi dikecualikan). */
        .cetak-halaman:not(.cetak-lewati) + .cetak-halaman:not(.cetak-lewati) { margin-top: 1.5rem; }

        /* Halaman yang tidak dipilih tidak ditampilkan (layar maupun cetak). */
        .cetak-lewati { display: none !important; }

        @media print {
            /* Margin asli tiap halaman lewat @page supaya halaman lanjutan pun tetap
               bermargin (bukan hanya potongan pertama/terakhir seperti padding kotak).
               Nilai ditimpa oleh <style id="cetak-ukuran"> dari JS. */
            @page { size: A4 portrait; margin: 1.5cm; }

            .cetak-aksi { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            /* Padding kotak dilepas di kertas karena margin sudah ditangani @page. */
            .cetak-kertas {
                max-width: none !important;
                padding: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .cetak-lembar { max-width: none !important; }

            /* Hanya halaman setelah halaman tercetak pertama yang memulai lembar baru,
               sehingga memilih cetak halaman 2 saja tidak menyisakan halaman kosong. */
            .cetak-pisah { break-before: page; }
            .cetak-halaman + .cetak-halaman { margin-top: 0 !important; }

            /* Tabel daftar hadir: header berulang & baris tidak terpotong di halaman lanjutan. */
            .cetak-tabel thead { display: table-header-group; }
            .cetak-tabel tr { break-inside: avoid; }

            /* Blok tanda tangan tetap satu blok. */
            .cetak-tanda-tangan { break-inside: avoid; }
        }
    </style>
    {{-- Diisi JavaScript sebelum window.print() agar margin tiap halaman ikut menyesuaikan. --}}
    <style id="cetak-ukuran"></style>
</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
    <div class="cetak-lembar mx-auto max-w-4xl">
        <div class="cetak-aksi mb-4 flex flex-wrap items-end justify-between gap-3">
            <a href="{{ route('surat.index') }}#baki" class="btn-secondary">
                Kembali ke Surat
            </a>

            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <span class="text-sm font-semibold text-slate-600">Halaman dicetak</span>
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" id="halaman-1" checked
                                   class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30">
                            Halaman 1 — Berita Acara
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" id="halaman-2" checked
                                   class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500/30">
                            Halaman 2 — Daftar Hadir
                        </label>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-1.5">
                    <span class="pb-2.5 text-sm font-semibold text-slate-600">Margin (cm)</span>
                    @foreach (['atas' => 'Atas', 'bawah' => 'Bawah', 'kiri' => 'Kiri', 'kanan' => 'Kanan'] as $kunci => $label)
                        <label class="flex flex-col gap-0.5">
                            <span class="text-[10px] font-semibold tracking-wide text-slate-500 uppercase">{{ $label }}</span>
                            <input type="number" id="margin-{{ $kunci }}" value="1.5"
                                   min="0" max="10" step="0.1" inputmode="decimal"
                                   class="form-input w-20 py-2 text-sm">
                        </label>
                    @endforeach
                </div>

                <button type="button" onclick="cetakBaki()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white shadow-sm"
             style="padding: var(--cetak-margin)">


            {{-- Halaman 1: Berita Acara Persetujuan Tim Komite Integritas Akademik. --}}
            <section class="cetak-halaman" data-halaman="1">
                @include('surat.partials.kop-surat')

                <header class="mt-2 text-center">
                    <h1 class="text-[16px] font-bold uppercase">Berita Acara Persetujuan</h1>
                    <h2 class="text-[16px] font-bold uppercase">Tim Komite Integritas Akademik</h2>
                    <p class="mt-0.5 text-[16px] font-bold">NOMOR: {{ $baki->nomor_surat }}</p>
                </header>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Menindaklanjuti hasil Berita Acara Senat {{ $instansi?->nama_instansi ?: '-' }} Nomor
                    {{ $bap?->nomor_bap ?: '-' }} tanggal {{ $tanggalBap }} tentang persetujuan usulan
                    kenaikan jabatan akademik dosen:
                </p>

                <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                    <caption class="sr-only">Identitas dosen yang diusulkan</caption>
                    <tbody>
                        @foreach ([
                            'Nama' => $identitas['nama'],
                            'NIDN' => $identitas['nidn'],
                            'NUPTK' => $identitas['nuptk'] ?: '—',
                            'Status dosen tetap' => 'Dosen Tetap',
                            'Tempat, tanggal lahir' => $identitas['ttl'],
                            'Pangkat, golongan ruang, TMT' => $identitas['pangkat'].' / '.$identitas['pangkat_tmt'],
                            'Jabatan, TMT' => $identitas['jabatan'].' / '.$identitas['jabatan_tmt'],
                            'Pendidikan tertinggi' => $identitas['pendidikan'],
                            'Fakultas' => $identitas['fakultas'] ?? '—',
                            'Jurusan/program studi' => $identitas['prodi'],
                        ] as $label => $nilai)
                            <tr>
                                <td class="w-[45%] align-top">{{ $label }}</td>
                                <td class="w-4 align-top">:</td>
                                <td class="align-top font-semibold">{{ $nilai }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Untuk Kenaikan Jabatan Akademik Dosen ke Asisten Ahli dalam
                    bidang ilmu penugasan keahlian/kompetensi/kepakaran {{ $bap?->bidang_keilmuan }}.
                </p>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Tim Komite Integritas Akademik yang dihadiri oleh sejumlah {{ $anggota->count() }} orang tim
                    anggota Komite Integritas Akademik (<strong>dengan jumlah yang memenuhi kuorum</strong>) sebagaimana daftar
                    hadir terlampir.
                </p>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Tim Komite Integritas Akademik melakukan pertimbangan kembali berdasarkan penilaian atas
                    <strong>integritas, kinerja, tanggung jawab dan tata krama (tidak ada pelanggaran integritas
                    akademik)</strong> dalam kehidupan kampus yang telah ditunjukkan oleh yang bersangkutan dalam
                    melaksanakan Tridharma Perguruan Tinggi, sejak yang bersangkutan menduduki jabatan akademik
                    terakhir sampai dengan rapat pemberian pertimbangan/persetujuan ini dilakukan.
                </p>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Apabila di kemudian hari terbukti bahwa ada pelanggaran integritas akademik, maka Tim Komite
                    Integritas Akademik ikut bertanggung jawab sesuai dengan Peraturan Menteri Pendidikan
                    Kebudayaan, Riset dan Teknologi Nomor 39 Tahun 2021 tentang Integritas Akademik dalam
                    Menghasilkan Karya Ilmiah.
                </p>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Berita acara ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.
                </p>

                {{-- Tanda tangan Ketua Tim Komite Integritas Akademik: ruang kosong 2,5 cm
                     antara jabatan dan nama untuk tanda tangan basah. --}}
                <div class="cetak-tanda-tangan mt-2 flex text-[16px]">
                    <div class="w-1/2"></div>
                    <div class="w-1/2">
                        <p>{{ $kotaTanggal }}</p>
                        <p class="mt-0.5">Ketua Tim</p>
                        <div class="h-[2.5cm]" aria-hidden="true"></div>
                        <p class="font-bold underline">{{ $namaKetua }}</p>
                        <p>{{ $nidnKetua }}</p>
                    </div>
                </div>
            </section>


            {{-- Halaman 2: Daftar Hadir Rapat Tim Komite Integritas Akademik. --}}
            <section class="cetak-halaman cetak-pisah" data-halaman="2">
                @include('surat.partials.kop-surat')

                <header class="mt-2 text-center">
                    <h1 class="text-[16px] font-bold">DAFTAR HADIR RAPAT TIM KOMITE INTEGRITAS AKADEMIK</h1>
                    <h2 class="text-[16px] font-bold">
                        PERGURUAN TINGGI {{ mb_strtoupper($instansi?->nama_instansi ?: '') }}
                    </h2>
                </header>

                <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                    <caption class="sr-only">Waktu, tempat, dan materi rapat</caption>
                    <tbody>
                        @foreach ([
                            'Hari, tanggal' => (string) $baki->tanggal_surat?->locale('id')->translatedFormat('l, d F Y'),
                            'Tempat pelaksanaan' => (string) $baki->tempat_pelaksanaan,
                            'Materi rapat' => 'Pemberian pertimbangan/persetujuan kenaikan jabatan akademik dosen',
                        ] as $label => $nilai)
                            <tr>
                                <td class="w-[4.2cm] py-1 align-top">{{ $label }}</td>
                                <td class="w-4 py-1 align-top">:</td>
                                <td class="py-1 align-top">{{ $nilai !== '' ? $nilai : str_repeat('.', 55) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="cetak-tabel mt-2 w-full border-collapse border border-slate-900 text-[16px]">
                    <caption class="sr-only">Daftar hadir tim komite integritas akademik</caption>
                    <colgroup>
                        <col style="width: 9%">
                        <col style="width: 43%">
                        <col style="width: 24%">
                        <col style="width: 24%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">NO</th>
                            <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">NAMA</th>
                            <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">JABATAN</th>
                            <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">TANDATANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < $jumlahBaris; $i++)
                            @php $komite = $anggota->get($i); @endphp
                            <tr>
                                <td class="border border-slate-900 px-2 py-1.5 text-center align-top">{{ $i + 1 }}</td>
                                <td class="border border-slate-900 px-2 py-1.5 align-top">{{ $komite?->user?->name ?? '' }}</td>
                                <td class="border border-slate-900 px-2 py-1.5 align-top">{{ $komite?->jabatanLabel() ?? '' }}</td>
                                <td class="border border-slate-900 px-2 py-1.5 align-top"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

                {{-- Tanda tangan pemimpin rapat (Ketua Tim Komite): ruang kosong 2,5 cm. --}}
                <div class="cetak-tanda-tangan mt-2 flex text-[16px]">
                    <div class="w-1/2"></div>
                    <div class="w-1/2">
                        <p>{{ $kotaTanggal }}</p>
                        <p class="mt-0.5">Ketua Tim,</p>
                        <div class="h-[2.5cm]" aria-hidden="true"></div>
                        <p class="font-bold underline">{{ $namaKetua }}</p>
                        <p>{{ $nidnKetua }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        const halaman = {
            1: { pilih: document.getElementById('halaman-1'), bagian: document.querySelector('[data-halaman="1"]') },
            2: { pilih: document.getElementById('halaman-2'), bagian: document.querySelector('[data-halaman="2"]') },
        };

        const marginKontrol = {
            atas: document.getElementById('margin-atas'),
            bawah: document.getElementById('margin-bawah'),
            kiri: document.getElementById('margin-kiri'),
            kanan: document.getElementById('margin-kanan'),
        };

        const marginAwal = 1.5;

        function nilaiMargin(input) {
            const nilai = parseFloat(input?.value);
            return Number.isFinite(nilai) && nilai >= 0 ? nilai : marginAwal;
        }

        // Pratinjau layar memakai padding; saat cetak, margin tiap halaman ditulis
        // sebagai @page supaya halaman lanjutan juga bermargin sesuai pilihan.
        function terapkanMargin() {
            const atas = nilaiMargin(marginKontrol.atas);
            const bawah = nilaiMargin(marginKontrol.bawah);
            const kiri = nilaiMargin(marginKontrol.kiri);
            const kanan = nilaiMargin(marginKontrol.kanan);

            document.querySelector('.cetak-kertas').style.padding = `${atas}cm ${kanan}cm ${bawah}cm ${kiri}cm`;

            document.getElementById('cetak-ukuran').textContent =
                `@page { size: A4 portrait; margin: ${atas}cm ${kanan}cm ${bawah}cm ${kiri}cm; }`;
        }

        // Halaman 2 hanya dimulai di lembar baru bila halaman 1 ikut dicetak,
        // supaya memilih cetak halaman 2 saja tidak menyisakan halaman kosong.
        function terapkanHalaman() {
            const dipilih = Object.values(halaman).filter((item) => item.pilih.checked && item.bagian);
            const urut = Object.values(halaman).filter((item) => item.bagian);

            urut.forEach((item) => {
                item.bagian.classList.toggle('cetak-lewati', ! item.pilih.checked);
                item.bagian.classList.remove('cetak-pisah');
            });

            dipilih.slice(1).forEach((item) => item.bagian.classList.add('cetak-pisah'));
        }

        Object.values(halaman).forEach((item) => item.pilih?.addEventListener('change', terapkanHalaman));
        Object.values(marginKontrol).forEach((input) => input?.addEventListener('input', terapkanMargin));

        function cetakBaki() {
            terapkanHalaman();
            terapkanMargin();
            window.print();
        }

        terapkanHalaman();
        terapkanMargin();
    </script>
</body>
</html>

