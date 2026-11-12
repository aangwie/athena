<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Pernyataan Pimpinan {{ $dosen['nama'] ?? '—' }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    @php
        $tandaTangan = ($kota ?: '…').', '.($pernyataan->tanggal_surat?->locale('id')->translatedFormat('j F Y') ?? '……');
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
            <a href="{{ route('surat.index') }}#pernyataan-pimpinan" class="btn-secondary">
                Kembali ke Surat
            </a>

            <div class="flex flex-wrap items-end gap-3">
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

                <button type="button" onclick="cetakSurat()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white shadow-sm"
             style="padding: var(--cetak-margin)">
            @include('surat.partials.kop-surat')

            <header class="mt-2 text-center">
                <h1 class="text-[16px] font-extrabold uppercase underline">
                    Surat Pernyataan Pimpinan Perguruan Tinggi
                </h1>
                <p class="mt-0.5 text-[16px]">Nomor: {{ $pernyataan->nomor_surat ?: '…' }}</p>
            </header>

            <p class="mt-2 text-justify text-[16px] leading-snug">
                Yang bertandatangan di bawah ini, Pimpinan {{ $instansi?->nama_instansi ?: '…' }}:
            </p>

            <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                <caption class="sr-only">Identitas pimpinan</caption>
                <tbody>
                    @foreach ([
                        'Nama' => $pimpinan['nama'],
                        'NIDN/NUPTK' => $pimpinan['nidn']." / ".$pimpinan['nuptk'] ?: '—',
                        'Status dosen tetap' => 'Non PNS',
                        'Tempat, tanggal lahir' => $pimpinan['ttl'],
                        'Pangkat golongan ruang / TMT' => $pimpinan['pangkat'].' / '.$pimpinan['pangkat_tmt'],
                        'Jabatan / TMT' => $pimpinan['jabatan'].' / '.$pimpinan['jabatan_tmt'],
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
                Menyatakan dengan sebenarnya bahwa seluruh dokumen persyaratan yang diajukan sebagai bahan
                pertimbangan kelayakan kenaikan jabatan akademik dosen, atas nama:
            </p>

            <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                <caption class="sr-only">Identitas dosen yang diusulkan</caption>
                <tbody>
                    @foreach ([
                        'Nama' => $dosen['nama'],
                        'NIDN/NUPTK' => $dosen['nidn']." / ".$dosen['nuptk'] ?: '—',
                        'Status dosen tetap' => 'Non PNS',
                        'Tempat, tanggal lahir' => $dosen['ttl'],
                        'Pangkat golongan ruang / TMT' => $dosen['pangkat'].' / '.$dosen['pangkat_tmt'],
                        'Jabatan / TMT' => $dosen['jabatan'].' / '.$dosen['jabatan_tmt'],
                        'Pendidikan tertinggi' => $dosen['pendidikan'],
                        'Jurusan/program studi' => $dosen['prodi'],
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
                Telah diperiksa dan divalidasi dengan baik bahwa seluruh dokumen yang diajukan adalah benar dan
                dapat kami pertanggungjawabkan sesuai ketentuan yang berlaku, baik dalam proses perolehannya
                maupun hal-hal yang berkaitan dengan dokumen yang bersangkutan.
            </p>

            <p class="mt-2 text-justify text-[16px] leading-snug">
                Apabila di kemudian hari terbukti ada pelanggaran integritas akademik terhadap dokumen tersebut,
                maka kami bersedia menerima sanksi sesuai dengan ketentuan peraturan perundang-undangan, dan
                bersedia mempertanggungjawabkan secara hukum sesuai dengan peraturan yang berlaku, serta
                menerima bahwa proses kenaikan jabatan akademik di Perguruan Tinggi akan ditangguhkan hingga
                periode kenaikan jabatan berikutnya.
            </p>

            <p class="mt-2 text-justify text-[16px] leading-snug">
                Demikian surat pernyataan ini saya buat untuk dipergunakan sebagaimana mestinya.
            </p>

            {{-- Tanda tangan pimpinan: ruang 2,5 cm untuk tanda tangan dan stempel. --}}
            <div class="cetak-tanda-tangan mt-2 flex text-[16px]">
                <div class="w-1/2"></div>
                <div class="w-1/2">
                    <p>{{ $tandaTangan }}</p>
                    <p class="mt-0.5">Pimpinan</p>
                    <div class="h-[2.5cm]" aria-hidden="true"></div>
                    <p class="font-bold underline">{{ $pimpinan['nama'] }}</p>
                    <p>NIDN: {{ $pimpinan['nidn'] }}</p>
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

        function cetakSurat() {
            terapkanMargin();
            window.print();
        }

        terapkanMargin();
    </script>
</body>
</html>

