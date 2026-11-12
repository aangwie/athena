<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pakta Integritas {{ $pakta->dosen?->name ?? '—' }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    @php
        $tandaTangan = ($kota ?: '…').', '.($pakta->tanggal_pakta?->locale('id')->translatedFormat('j F Y') ?? '……');
        // Formulir cetak selalu menyediakan minimal tiga baris karya ilmiah.
        $barisKarya = $karya->values()->map(fn ($item) => [
            $item->karya_ilmiah,
            $item->judul,
            $item->link_bukti,
        ]);
        while ($barisKarya->count() < 3) {
            $barisKarya->push(['', '', '']);
        }
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

            /* Tabel karya: header berulang di halaman lanjutan dan baris tidak terpotong
               di tengah, sehingga potongan halaman tidak lagi acak-acakan. */
            .cetak-tabel { font-size: 16px; }
            .cetak-tabel thead { display: table-header-group; }
            .cetak-tabel tr { break-inside: avoid; }
            .cetak-tabel td { padding: 2px 4px !important; }
            /* Blok tanda tangan tetap satu blok, tidak terpisah dari nama penanda tangan. */
            .cetak-tanda-tangan { break-inside: avoid; }
        }
    </style>
    {{-- Diisi JavaScript sebelum window.print() agar margin tiap halaman ikut menyesuaikan. --}}
    <style id="cetak-ukuran"></style>
</head>
<body class="min-h-screen bg-slate-100 p-4 font-sans text-slate-900 sm:p-8">
    <div class="cetak-lembar mx-auto max-w-4xl">
        <div class="cetak-aksi mb-4 flex flex-wrap items-end justify-between gap-3">
            <a href="{{ route('surat.index') }}#pakta-integritas" class="btn-secondary">
                Kembali ke Surat
            </a>

            <div class="flex flex-wrap items-end gap-2">
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
                <button type="button" onclick="cetakPakta()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white shadow-sm"
             style="padding: var(--cetak-margin)">
            <header class="text-center">
                <h1 class="text-[16px] font-extrabold tracking-tight uppercase underline">
                    Surat Pernyataan Pakta Integritas<br>Keabsahan Karya Ilmiah
                </h1>
            </header>

            <p class="mt-4 text-[16px] leading-snug">Yang bertanda tangan di bawah ini:</p>

            <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                <caption class="sr-only">Identitas dosen pembuat pernyataan</caption>
                <tbody>
                    @foreach ([
                        'Nama' => $identitas['nama'],
                        'NIDN' => $identitas['nidn'],
                        'NUPTK' => $identitas['nuptk'] ?: '—',
                        'Status dosen tetap' => 'Non PNS',
                        'Tempat tanggal lahir' => $identitas['ttl'],
                        'Pangkat golongan ruang / TMT' => $identitas['pangkat'].' / '.$identitas['pangkat_tmt'],
                        'Jabatan / TMT' => $identitas['jabatan'].' / '.$identitas['jabatan_tmt'],
                        'Pendidikan tertinggi' => $identitas['pendidikan'],
                        'Jurusan /program studi' => $identitas['prodi'],
                    ] as $label => $nilai)
                        <tr>
                            <td class="w-[45%] align-top">{{ $label }}</td>
                            <td class="w-4 align-top">:</td>
                            <td class="align-top font-semibold">{{ $nilai }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="mt-2 text-[16px] leading-snug text-justify">
                Dengan ini menyatakan bahwa semua Karya Ilmiah yang digunakan untuk usulan kenaikan jabatan
                akademik dosen telah memenuhi integritas akademik, di antara karya ilmiah tersebut sebagai
                pemenuhan persyaratan khusus di bawah ini:
            </p>

            <table class="cetak-tabel mt-2 w-full border-collapse border border-slate-900 text-[16px] leading-tight">
                <caption class="sr-only">Daftar karya ilmiah yang dinyatakan</caption>
                <colgroup>
                    <col style="width: 8%">
                    <col style="width: 26%">
                    <col style="width: 41%">
                    <col style="width: 25%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="border border-slate-900 px-2 py-1 text-center font-bold">No</th>
                        <th class="border border-slate-900 px-2 py-1 text-left font-bold">Karya Ilmiah</th>
                        <th class="border border-slate-900 px-2 py-1 text-left font-bold">Judul</th>
                        <th class="border border-slate-900 px-2 py-1 text-left font-bold">Tautan Karya Ilmiah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($barisKarya as $index => $baris)
                        <tr>
                            <td class="border border-slate-900 px-2 py-1 text-center align-top">{{ $index + 1 }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $baris[0] }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $baris[1] }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top break-all">{{ $baris[2] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <ol class="mt-2 list-none space-y-1.5 text-[16px] leading-snug">
                <li class="flex gap-2">
                    <span class="w-4 shrink-0">1.</span>
                    <span class="text-justify">Adalah benar karya saya sendiri dan tidak dihasilkan dengan cara melanggar integritas akademik.</span>
                </li>
                <li class="flex gap-2">
                    <span class="w-4 shrink-0">2.</span>
                    <span class="text-justify">Apabila di kemudian hari terbukti bahwa karya ilmiah ini bukan karya saya sendiri atau ada pelanggaran integritas akademik maka saya bersedia menerima sanksi sesuai ketentuan perundang-undangan yang berlaku.</span>
                </li>
            </ol>

            <p class="mt-2 text-[16px] leading-snug text-justify">
                Demikian surat pernyataan ini saya buat untuk dipergunakan sebagaimana mestinya.
            </p>

            {{-- Ruang tanda tangan: 2,5 cm antara jabatan dan nama untuk tanda tangan dan materai. --}}
            <div class="cetak-tanda-tangan mt-2 flex text-[16px]">
                <div class="w-1/2"></div>
                <div class="w-1/2">
                    <p>{{ $tandaTangan }}</p>
                    <p class="mt-0.5">Yang membuat pernyataan</p>
                    <div class="h-[2.5cm]" aria-hidden="true"></div>
                    <p class="font-bold underline">{{ $identitas['nama'] }}</p>
                    <p>NIDN: {{ $identitas['nidn'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <script>
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

        Object.values(marginKontrol).forEach((input) => input?.addEventListener('input', terapkanMargin));

        function cetakPakta() {
            terapkanMargin();
            window.print();
        }

        terapkanMargin();
    </script>
</body>
</html>

