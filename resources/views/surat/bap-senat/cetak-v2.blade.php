{{-- Format cetak V2 (lampiran): berita acara persetujuan senat dengan tanda tangan --}}
{{-- sekretaris & ketua senat, lalu daftar anggota senat bernomor sesuai urutan --}}
{{-- pilihan pada formulir, masing-masing dengan ruang tanda tangan. --}}
{{-- Ukuran huruf sama dengan V1 (16px); bila naskah melebihi satu halaman A4, --}}
{{-- isinya otomatis berlanjut ke halaman berikutnya. --}}
<section class="text-[16px] leading-snug">
    @include('surat.partials.kop-surat', ['senatAkademik' => true])

    <header class="mt-2 text-center">
        <h1 class="text-[16px] font-bold uppercase">Berita Acara Persetujuan Senat</h1>
        <p class="mt-0.5 text-[16px]">NOMOR: {{ $bap->nomor_bap }}</p>
    </header>

    <p class="mt-2 text-justify text-[16px] leading-snug">
        Pada hari ini {{ $tanggal?->translatedFormat('l') ?: '-' }} tanggal
        {{ $tanggal?->translatedFormat('d') ?: '-' }} bulan
        {{ $tanggal?->translatedFormat('F') ?: '-' }} tahun
        {{ $tanggal?->translatedFormat('Y') ?: '-' }}, Senat
        {{ $instansi?->nama_instansi ?: '-' }} yang dihadiri oleh sejumlah {{ $bap->jumlahKuorum($anggota->count()) }}
        orang anggota senat (dengan jumlah yang memenuhi kuorum) sebagaimana daftar hadir terlampir,
        telah melakukan rapat pemberian pertimbangan/persetujuan pengajuan Kenaikan Jabatan Akademik kepada:
    </p>

    <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
        <caption class="sr-only">Identitas dosen yang diusulkan</caption>
        <tbody>
            @foreach ([
                'Nama' => $identitas['nama'],
                // Sama seperti V1: NIDN dan NUPTK ditulis pada satu baris.
                'NIDN/NUPTK' => $identitas['nidn']." / ".$identitas['nuptk'] ?: '—',
                'Status dosen tetap' => 'Non PNS',
                'Tempat, tanggal lahir' => $identitas['ttl'],
                'Pangkat golongan ruang, TMT' => $identitas['pangkat'].' / '.$identitas['pangkat_tmt'],
                'Jabatan, TMT' => $identitas['jabatan'].' / '.$identitas['jabatan_tmt'],
                'Pendidikan tertinggi' => $identitas['pendidikan'],
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
        Untuk Kenaikan Jabatan Akademik Dosen ke {{ $bap->targetKenaikan() }}
        dalam bidang ilmu penugasan keahlian/kompetensi/kepakaran {{ $bap->bidang_keilmuan }}.
    </p>

    <p class="mt-2 text-justify text-[16px] leading-snug">
        Hasil pertimbangan diberikan berdasarkan penilaian atas <strong>integritas, kinerja, tanggung jawab,
        dan tata krama</strong> dalam kehidupan kampus yang telah ditetapkan oleh yang bersangkutan dalam
        melaksanakan tridharma Perguruan Tinggi sejak yang bersangkutan menduduki jabatan akademik
        terakhir sampai dengan rapat pemberian pertimbangan persetujuan ini dilaksanakan{{ $bap->menggunakanProsentase() ? ', dengan hasil penilaian proporsi penelitian '.$bap->prosentase_penelitian.'% dari total kebutuhan kenaikan jabatan' : '' }}.
    </p>

    <p class="mt-2 text-justify text-[16px] leading-snug">
        Apabila di kemudian hari terbukti bahwa ada pelanggaran integritas akademik maka Tim Senat
        ikut bertanggung jawab sesuai dengan Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan
        Teknologi Nomor 39 Tahun 2021 tentang Integritas Akademik dalam Menghasilkan Karya Ilmiah.
    </p>

    <p class="mt-2 text-justify text-[16px] leading-snug">
        Berita acara ini dibuat dengan sesungguhnya untuk dipergunakan sebagaimana mestinya.
    </p>

    {{-- Tanda tangan sekretaris (kiri) dan ketua senat (kanan), dengan ruang tanda
         tangan basah ±1,6 cm. --}}
    <div class="cetak-tanda-tangan mt-2 text-[16px]">
        <div class="flex">
            <div class="w-1/2"></div>
            <div class="w-1/2">
                <p>{{ $kotaTanggal }}</p>
            </div>
        </div>
        <div class="mt-0.5 flex">
            <div class="w-1/2">
                <p>Sekretaris,</p>
                <div class="h-[1.6cm]" aria-hidden="true"></div>
                <p class="font-bold underline">{{ $namaSekretaris }}</p>
                <p>{{ $nidnSekretaris }}</p>
            </div>
            <div class="w-1/2">
                <p>Ketua Senat,</p>
                <div class="h-[1.6cm]" aria-hidden="true"></div>
                <p class="font-bold underline">{{ $namaKetua }}</p>
                <p>{{ $nidnKetua }}</p>
            </div>
        </div>
    </div>

    {{-- Daftar anggota senat: nomor mengikuti urutan pilihan pada formulir, dibagi
         dua kolom (nomor berlanjut ke kolom kanan) dengan garis tanda tangan di
         samping tiap nama. Bila naskah melebihi satu halaman A4, daftar ini
         otomatis berlanjut ke halaman berikutnya. --}}
    <p class="cetak-anggota-judul mt-2 text-center text-[16px] font-bold">Anggota Senat</p>

    @if ($anggota->isEmpty())
        <p class="mt-2 text-center text-[16px] italic">Belum ada anggota senat yang dipilih.</p>
    @else
        <table class="cetak-anggota mt-2 w-full border-collapse text-[16px] leading-tight">
            <caption class="sr-only">Anggota senat sesuai urutan pilihan</caption>
            <colgroup>
                <col style="width: 4%">
                <col style="width: 32%">
                <col style="width: 14%">
                <col style="width: 4%">
                <col style="width: 32%">
                <col style="width: 14%">
            </colgroup>
            <tbody>
                @for ($i = 0; $i < $anggotaKiri->count(); $i++)
                    @php
                        $kiri = $anggotaKiri->get($i);
                        $kanan = $anggotaKanan->get($i);
                    @endphp
                    <tr>
                        <td class="py-1 pr-2 align-bottom whitespace-nowrap">{{ $i + 1 }}.</td>
                        <td class="py-1 align-bottom">{{ $kiri?->user?->name }}</td>
                        {{-- Garis tanda tangan anggota senat (kolom kiri). --}}
                        <td class="py-1 align-bottom">
                            <div class="w-full border-b border-slate-900" aria-hidden="true">&nbsp;</div>
                        </td>
                        <td class="py-1 pr-2 align-bottom whitespace-nowrap">
                            {{ $kanan ? ($anggotaKiri->count() + $i + 1).'.' : '' }}
                        </td>
                        <td class="py-1 align-bottom">{{ $kanan?->user?->name }}</td>
                        {{-- Garis tanda tangan anggota senat (kolom kanan). --}}
                        <td class="py-1 align-bottom">
                            @if ($kanan)
                                <div class="w-full border-b border-slate-900" aria-hidden="true">&nbsp;</div>
                            @endif
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    @endif
</section>
