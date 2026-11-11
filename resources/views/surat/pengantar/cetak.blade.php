<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Pengantar {{ $pengantar->nomor_surat }} · {{ $namaWeb }}</title>

    @vite(['resources/css/app.css'])

    @php
        $kotaTanggal = ($kota ?: str_repeat('.', 15)).', '.($pengantar->tanggal_surat?->locale('id')->translatedFormat('j F Y') ?? str_repeat('.', 10));
        $kepada = trim((string) $pengantar->tujuan);
        $kotaTujuan = trim((string) $pengantar->kota_tujuan);
        $diusulkan = trim((string) $pengantar->jabatan_diusulkan);
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

            /* Tabel dosen (kolektif): header berulang & baris tidak terpotong antar halaman. */
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
            <a href="{{ route('surat.index') }}#surat-pengantar" class="btn-secondary">
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

                <button type="button" onclick="cetakPengantar()" class="btn-primary w-auto px-5 py-2.5 text-sm">
                    Cetak / Simpan PDF
                </button>
            </div>
        </div>

        <div class="cetak-kertas rounded-2xl border border-slate-200 bg-white shadow-sm"
             style="padding: var(--cetak-margin)">
            @include('surat.partials.kop-surat')

            <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                <caption class="sr-only">Kepala surat</caption>
                <tbody>
                    @foreach ([
                        'No' => $pengantar->nomor_surat,
                        'Lampiran' => (string) $pengantar->lampiran,
                        'Hal' => $pengantar->perihal,
                    ] as $label => $nilai)
                        <tr>
                            <td class="w-20 align-top">{{ $label }}</td>
                            <td class="w-3 align-top">:</td>
                            <td class="align-top">{{ $nilai !== '' ? $nilai : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Blok tujuan: "Kepada. Yth. <nama>" / "di" / "<kota tujuan>". --}}
            <div class="mt-2 text-[16px]">
                <p class="font-bold">Kepada. Yth. {{ $kepada !== '' ? $kepada : str_repeat('.', 50) }}</p>
                <p>di</p>
                <p>{{ $kotaTujuan !== '' ? $kotaTujuan : str_repeat('.', 30) }}</p>
            </div>

            <p class="mt-2 text-[16px]">Dengan hormat,</p>


            @if ($pengantar->kolektif)
                {{-- Badan surat kolektif: tabel dosen. --}}
                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Bersama ini kami kirimkan data dosen {{ $instansi?->nama_instansi ?: '-' }} yang belum masuk
                    dalam aplikasi SIAP Jafa, sebagai berikut:
                </p>

                <table class="cetak-tabel mt-2 w-full border-collapse border border-slate-900 text-[16px]">
                    <caption class="sr-only">Daftar dosen</caption>
                    <colgroup>
                        <col style="width: 7%">
                        <col style="width: 20%">
                        <col style="width: 20%">
                        <col style="width: 31%">
                        <col style="width: 22%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="border border-slate-900 px-2 py-1 text-center font-bold">No</th>
                            <th class="border border-slate-900 px-2 py-1 text-center font-bold">NUPTK</th>
                            <th class="border border-slate-900 px-2 py-1 text-center font-bold">NIDN</th>
                            <th class="border border-slate-900 px-2 py-1 text-center font-bold">Nama</th>
                            <th class="border border-slate-900 px-2 py-1 text-center font-bold">Jabatan Fungsional</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dosen as $index => $item)
                            <tr>
                                <td class="border border-slate-900 px-2 py-1 text-center align-top">{{ $index + 1 }}</td>
                                <td class="border border-slate-900 px-2 py-1 align-top">{{ $item['nuptk'] !== '' ? $item['nuptk'] : '—' }}</td>
                                <td class="border border-slate-900 px-2 py-1 align-top">{{ $item['nidn'] }}</td>
                                <td class="border border-slate-900 px-2 py-1 align-top">{{ $item['nama'] }}</td>
                                <td class="border border-slate-900 px-2 py-1 align-top">{{ $item['jabatan'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Adapun kelengkapan data dosen di atas, sebagaimana terlampir.
                </p>
                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Demikian surat pengantar ini dibuat, atas perhatiannya diucapkan terima kasih.
                </p>
            @else
                {{-- Badan surat pribadi: blok identitas satu dosen. --}}
                @php $item = $dosen[0] ?? ['nama' => '—', 'nidn' => '—', 'nuptk' => '', 'jabatan' => '—']; @endphp

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Bersama ini kami kirimkan berkas usulan Jabatan Akademik Dosen {{ $instansi?->nama_instansi ?: '-' }},
                    sebagai berikut:
                </p>

                <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
                    <caption class="sr-only">Identitas dosen yang diusulkan</caption>
                    <tbody>
                        @php
                            $barisPribadi = [
                                'Nama' => $item['nama'],
                                'NIDN / NUPTK' => $item['nuptk'] !== '' ? $item['nidn'].' / '.$item['nuptk'] : $item['nidn'],
                                'Jabatan Akademik' => $item['jabatan'],
                            ];
                            // Baris usulan hanya muncul bila jabatan yang diusulkan diisi.
                            if ($diusulkan !== '') {
                                $barisPribadi['Diusulkan'] = $diusulkan;
                            }
                        @endphp
                        @foreach ($barisPribadi as $label => $nilai)
                            <tr>
                                <td class="w-[9rem] align-top">{{ $label }}</td>
                                <td class="w-4 align-top">:</td>
                                <td class="align-top font-semibold">{{ $nilai !== '' ? $nilai : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <p class="mt-2 text-justify text-[16px] leading-snug">
                    Demikian surat pengantar ini dibuat, atas kerjasamanya kami ucapkan terima kasih.
                </p>
            @endif

            {{-- Tanda tangan ketua instansi: ruang kosong 2,5 cm antara jabatan dan nama. --}}
            <div class="cetak-tanda-tangan mt-2 flex text-[16px]">
                <div class="w-1/2"></div>
                <div class="w-1/2">
                    <p>{{ $kotaTanggal }}</p>
                    <p class="mt-0.5">Ketua,</p>
                    <div class="h-[2.5cm]" aria-hidden="true"></div>
                    <p class="font-bold underline">{{ $pimpinan['nama'] }}</p>
                    <p>NIDN. {{ $pimpinan['nidn'] }}</p>
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

        function cetakPengantar() {
            terapkanMargin();
            window.print();
        }

        terapkanMargin();
    </script>
</body>
</html>

