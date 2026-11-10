<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Validasi Karya Ilmiah {{ $pakta->dosen?->name ?? '—' }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    @php
        // Tanggal pengesahan pada baris "Kota, tanggal": isian Tanggal Pengesahan Validasi,
        // atau tanggal pakta bila belum diisi (perilaku sebelumnya).
        $tanggalPengesahan = $pakta->tanggal_validasi ?? $pakta->tanggal_pakta;
        $kotaTanggal = ($kota ?: '…').', '.($tanggalPengesahan?->locale('id')->translatedFormat('j F Y') ?? '……');
        $nomorValidasi = trim((string) $pakta->nomor_validasi) ?: str_repeat('.', 30);

        // Identitas ketua & dosen pada blok surat (label, nilai, TMT opsional).
        $identitasKetua = [
            ['Nama', $ketua['nama'] ?? '—', null],
            ['NIDN / NUPTK', ($ketua['nidn'] ?? '—').' / '.($ketua['nuptk'] ?: '—'), null],
            ['Status Ikatan Kerja', 'Dosen Tetap', null],
            ['Tempat, tanggal lahir', $ketua['ttl'] ?? '—', null],
            ['Pangkat, Golongan Ruang, TMT', ($ketua['pangkat'] ?? '—').' / '.($ketua['pangkat_tmt'] ?? '—'), $ketua['pangkat_tmt'] ?? null],
            ['Jabatan, TMT', $ketua['jabatan'] ?? '—', $ketua['jabatan_tmt'] ?? null],
        ];
        $identitasDosen = [
            ['Nama', $identitas['nama'], null],
            ['NIDN / NUPTK', $identitas['nidn'].' / '.($identitas['nuptk'] ?: '—'), null],
            ['Status Ikatan Kerja', 'Dosen Tetap', null],
            ['Tempat, tanggal lahir', $identitas['ttl'], null],
            ['Pangkat, Golongan Ruang, TMT', $identitas['pangkat'], $identitas['pangkat_tmt']],
            ['Jabatan, TMT', $identitas['jabatan'], $identitas['jabatan_tmt']],
            ['Pendidikan tertinggi', $identitas['pendidikan'], null],
            ['Fakultas/Sekolah Tinggi/Akademi', $instansi?->nama_instansi ?: '—', null],
            ['Program Studi', $identitas['prodi'], null],
        ];

        // Baris tabel validasi: setiap karya ilmiah menampilkan identitasnya sendiri.
        $barisKarya = $karya->values()->map(fn ($item) => [
            'jenis' => $item->karya_ilmiah,
            'judul' => $item->judul,
            'identitas' => $item->isProsiding()
                ? [
                    ['label' => 'Nama Prosiding', 'nilai' => $item->nama_prosiding, 'url' => null],
                    ['label' => 'ISSN', 'nilai' => $item->issn, 'url' => null],
                    ['label' => 'URL', 'nilai' => $item->url_prosiding, 'url' => $item->url_prosiding],
                    ['label' => 'URL Plagiasi', 'nilai' => $item->url_plagiasi, 'url' => $item->url_plagiasi],
                ]
                : [
                    ['label' => 'Nama Jurnal', 'nilai' => $item->nama_jurnal, 'url' => null],
                    ['label' => 'ISSN', 'nilai' => $item->issn, 'url' => null],
                    ['label' => 'Web Jurnal', 'nilai' => $item->url_jurnal, 'url' => $item->url_jurnal],
                    ['label' => 'URL Dokumen', 'nilai' => $item->link_bukti, 'url' => $item->link_bukti],
                ],
        ]);
        while ($barisKarya->count() < 3) {
            $barisKarya->push(['jenis' => '', 'judul' => '', 'identitas' => []]);
        }
    @endphp

    <style>
        /* Cetak ke PDF lewat dialog cetak peramban (A4 portrait), mengikuti formulir SKP. */
        /* Margin awal 1,5 cm semua sisi; JS memperbarui nilainya sebelum dicetak. */
        :root { --cetak-margin: 1.5cm; }

        /* Pratinjau layar: jarak ke tepi kertas digambar sendiri lewat padding. */
        .cetak-kertas { padding: var(--cetak-margin); }

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
               di tengah, sehingga isi tabel menyesuaikan halaman tanpa terpotong acak. */
            .cetak-tabel { font-size: 10px; }
            .cetak-tabel thead { display: table-header-group; }
            .cetak-tabel tr { break-inside: avoid; }
            .cetak-tabel td { padding: 2px 4px !important; }
            /* Blok tanda tangan tetap satu blok, tidak terpisah dari nama penanda tangan. */
            .cetak-tanda-tangan { break-inside: avoid; }
            /* Tautan pada lembar validasi tercetak berwarna biru bergaris bawah. */
            .cetak-tautan { color: #1d4ed8 !important; text-decoration: underline; }
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
                <button type="button" onclick="cetakValidasi()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white shadow-sm"
             style="padding: var(--cetak-margin)">
            @include('surat.partials.kop-surat')

            <header class="mt-3 text-center">
                <h1 class="text-sm font-extrabold tracking-tight uppercase underline">
                    Surat Pernyataan Pakta Integritas
                </h1>
                <h2 class="text-sm font-extrabold tracking-tight uppercase underline">
                    Pengesahan Hasil Validasi Karya Ilmiah
                </h2>
                <p class="mt-0.5 text-[11px] font-bold">Nomor : {{ $nomorValidasi }}</p>
            </header>

            <p class="mt-3 text-[11px] leading-relaxed">
                Yang bertandatangan di bawah ini, Ketua {{ $instansi?->nama_instansi ?: '…' }} :
            </p>

            @include('surat.pakta-integritas.partials.identitas-validasi', ['baris' => $identitasKetua])

            <p class="mt-3 text-[11px] leading-relaxed text-justify">
                Menyatakan dengan sebenarnya bahwa seluruh dokumen persyaratan yang diajukan sebagai bahan
                pertimbangan kelayakan kenaikan jabatan akademik dosen, atas nama :
            </p>

            @include('surat.pakta-integritas.partials.identitas-validasi', ['baris' => $identitasDosen])

            <p class="mt-3 text-[11px] leading-relaxed text-justify">
                Dengan data karya ilmiah, seperti di bawah ini :
            </p>


            <table class="cetak-tabel mt-2 w-full border-collapse border border-slate-900 text-[11px]">
                <caption class="sr-only">Daftar karya ilmiah beserta identitasnya</caption>
                <colgroup>
                    <col style="width: 6%">
                    <col style="width: 20%">
                    <col style="width: 31%">
                    <col style="width: 43%">
                </colgroup>
                <thead>
                    <tr>
                        <th class="border border-slate-900 px-2 py-1 text-center font-bold">No</th>
                        <th class="border border-slate-900 px-2 py-1 text-center font-bold">Karya Ilmiah</th>
                        <th class="border border-slate-900 px-2 py-1 text-center font-bold">Judul</th>
                        <th class="border border-slate-900 px-2 py-1 text-center font-bold">Identitas Karya Ilmiah</th>
                    </tr>
                    <tr class="text-[10px]">
                        <td class="border border-slate-900 px-2 py-0.5 text-center">1</td>
                        <td class="border border-slate-900 px-2 py-0.5 text-center">2</td>
                        <td class="border border-slate-900 px-2 py-0.5 text-center">3</td>
                        <td class="border border-slate-900 px-2 py-0.5 text-center">4</td>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($barisKarya as $index => $baris)
                        <tr>
                            <td class="border border-slate-900 px-2 py-1 text-center align-top">{{ $index + 1 }}.</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $baris['jenis'] }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $baris['judul'] }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">
                                @forelse ($baris['identitas'] as $barisIdentitas)
                                    <p class="{{ $loop->first ? '' : 'mt-1.5' }}">
                                        <span class="font-bold">{{ $barisIdentitas['label'] }}:</span>
                                        @if ($barisIdentitas['url'])
                                            <a href="{{ $barisIdentitas['url'] }}" target="_blank" rel="noopener noreferrer"
                                               class="cetak-tautan break-all">{{ $barisIdentitas['nilai'] }}</a>
                                        @else
                                            <span class="break-words">{{ $barisIdentitas['nilai'] ?: '—' }}</span>
                                        @endif
                                    </p>
                                @empty
                                    <p>—</p>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="mt-3 text-[11px] leading-relaxed text-justify">
                Telah diperiksa dan divalidasi dengan baik bahwa karya ilmiah tersebut <span class="font-bold">telah
                memenuhi syarat kaidah ilmiah, norma akademik, dan norma hukum (tidak ada pelanggaran integritas
                akademik)</span> sesuai dengan Peraturan Menteri Pendidikan, Kebudayaan, Riset dan Teknologi Nomor 39
                Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah.
            </p>

            <p class="mt-2 text-[11px] leading-relaxed text-justify">
                Apabila dikemudian hari terbukti ada pelanggaran integritas akademik dalam karya ilmiah tersebut maka
                kami ikut bertanggung jawab sesuai dengan Peraturan Menteri Pendidikan, Kebudayaan, Riset dan Teknologi
                Nomor 39 Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah.
            </p>

            <p class="mt-2 text-[11px] leading-relaxed text-justify">
                Demikian surat pernyataan ini saya buat untuk dipergunakan sebagaimana mestinya.
            </p>

            {{-- Tanda tangan ketua instansi: ruang 1,8 cm untuk tanda tangan dan stempel. --}}
            <div class="cetak-tanda-tangan mt-6 flex text-[11px]">
                <div class="w-1/2"></div>
                <div class="w-1/2">
                    <p>{{ $kotaTanggal }}</p>
                    <p class="mt-0.5">Ketua,</p>
                    <div class="h-[1.8cm]" aria-hidden="true"></div>
                    <p class="font-bold underline">{{ $ketua['nama'] ?? '—' }}</p>
                    <p>NIDN. {{ $ketua['nidn'] ?? '—' }}</p>
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

        function cetakValidasi() {
            terapkanMargin();
            window.print();
        }

        terapkanMargin();
    </script>
</body>
</html>

