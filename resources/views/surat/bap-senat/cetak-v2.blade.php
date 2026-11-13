{{-- Format cetak V2 (lampiran): berita acara persetujuan senat dengan tanda tangan --}}
{{-- sekretaris & ketua senat, daftar anggota senat bernomor sesuai urutan pilihan --}}
{{-- pada formulir, lalu daftar hadir rapat pada halaman berikutnya. --}}
{{-- Ukuran huruf sama dengan V1 (16px); bila naskah melebihi satu halaman A4, --}}
{{-- isinya otomatis berlanjut ke halaman berikutnya. --}}
<section class="cetak-halaman text-[16px] leading-snug" data-halaman="1">
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
         samping tiap nama. Ketua & sekretaris tidak diulang di sini karena tanda
         tangannya sudah ada pada berita acara di atas; keduanya tetap tercatat pada
         daftar hadir. Bila naskah melebihi satu halaman A4, daftar ini otomatis
         berlanjut ke halaman berikutnya. --}}
    @if ($anggota->isEmpty())
        <p class="mt-2 text-center text-[16px] italic">Belum ada anggota senat yang dipilih.</p>
    @elseif ($anggotaKiri->isNotEmpty())
        <p class="cetak-anggota-judul mt-2 text-center text-[16px] font-bold">Anggota Senat</p>

        <table class="cetak-anggota mt-2 w-full border-collapse text-[16px] leading-tight">
            <caption class="sr-only">Anggota senat sesuai urutan pilihan</caption>
            <colgroup>
                <col style="width: 4%">
                <col style="width: 33%">
                <col style="width: 13%">
                <col style="width: 4%">
                <col style="width: 33%">
                <col style="width: 13%">
            </colgroup>
            <tbody>
                @for ($i = 0; $i < $anggotaKiri->count(); $i++)
                    @php
                        $kiri = $anggotaKiri->get($i);
                        $kanan = $anggotaKanan->get($i);
                    @endphp
                    <tr>
                        <td class="py-1 pr-2 align-top whitespace-nowrap">{{ $i + 1 }}.</td>
                        <td class="py-1 align-top">{{ $kiri?->user?->name }}</td>
                        {{-- Garis tanda tangan anggota senat (kolom kiri); tingginya tetap
                             supaya nama yang memakai dua baris tidak menggeser garis. --}}
                        <td class="py-1 align-top">
                            <div class="h-[0.6cm] border-b border-slate-900" aria-hidden="true"></div>
                        </td>
                        <td class="py-1 pr-2 align-top whitespace-nowrap">
                            {{ $kanan ? ($anggotaKiri->count() + $i + 1).'.' : '' }}
                        </td>
                        <td class="py-1 align-top">{{ $kanan?->user?->name }}</td>
                        {{-- Garis tanda tangan anggota senat (kolom kanan). --}}
                        <td class="py-1 align-top">
                            @if ($kanan)
                                <div class="h-[0.6cm] border-b border-slate-900" aria-hidden="true"></div>
                            @endif
                        </td>
                    </tr>
                @endfor
            </tbody>
        </table>
    @endif
</section>

{{-- Halaman 2 (V2): daftar hadir rapat seperti V1. Ketua & sekretaris tetap tercatat
     di sini walau tanda tangannya sudah ada pada berita acara. Kolom tanda tangan
     dibagi dua bagian bergaris (kiri & kanan): baris ganjil menandatangani bagian
     kiri, baris genap bagian kanan, dan nomor baris ditulis pada bagian tersebut
     supaya tanda tangan basah yang tinggi tidak saling menimpa. --}}
<section class="cetak-halaman cetak-pisah text-[16px] leading-snug" data-halaman="2">
    @include('surat.partials.kop-surat', ['senatAkademik' => true])

    <header class="mt-2 text-center">
        <h1 class="text-[16px] font-bold">DAFTAR HADIR RAPAT</h1>
        <h2 class="text-[16px] font-bold">
            SENAT AKADEMIK {{ mb_strtoupper($instansi?->nama_instansi ?: '') }}
        </h2>
    </header>

    <table class="mt-2 w-full border-collapse text-[16px] leading-tight">
        <caption class="sr-only">Waktu, tempat, dan materi rapat</caption>
        <tbody>
            @foreach ([
                'Hari, tanggal' => (string) $bap->tanggal_bap?->locale('id')->translatedFormat('l, d F Y'),
                'Tempat pelaksanaan' => (string) $bap->tempat_pelaksanaan,
                'Materi rapat' => (string) $bap->materi_rapat,
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
        <caption class="sr-only">Daftar hadir anggota senat</caption>
        <colgroup>
            <col style="width: 6%">
            <col style="width: 38%">
            <col style="width: 22%">
            {{-- Kolom tanda tangan dipecah dua bagian sama lebar (kiri & kanan). --}}
            <col style="width: 17%">
            <col style="width: 17%">
        </colgroup>
        <thead>
            <tr>
                <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">NO</th>
                <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">NAMA</th>
                <th class="border border-slate-900 px-2 py-1.5 text-center font-bold">JABATAN</th>
                {{-- Satu judul untuk dua bagian tanda tangan seperti lampiran: garis
                     pemisahnya baru tampak pada baris isi di bawahnya. --}}
                <th colspan="2" class="border border-slate-900 px-2 py-1.5 text-center font-bold">TANDA TANGAN</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $jumlahBaris; $i++)
                @php
                    $hadir = $anggota->get($i);
                    // Baris ganjil menandatangani bagian kiri dan baris genap bagian
                    // kanan, sehingga tanda tangan basah yang tinggi tidak saling
                    // menimpa. Nomor baris ditulis pada bagian yang ditandatangani.
                    $ttdKiri = $i % 2 === 0;
                @endphp
                <tr>
                    <td class="border border-slate-900 px-2 py-1 text-center align-middle">{{ $i + 1 }}</td>
                    <td class="border border-slate-900 px-2 py-1 align-middle">{{ $hadir?->user?->name ?? '' }}</td>
                    <td class="border border-slate-900 px-2 py-1 align-middle">{{ $hadir?->jabatanLengkap() ?? '' }}</td>
                    {{-- Dua bagian tanda tangan yang dipisahkan garis, seperti lampiran. --}}
                    <td class="cetak-ttd border border-slate-900">{{ $ttdKiri ? $i + 1 : '' }}</td>
                    <td class="cetak-ttd border border-slate-900">{{ $ttdKiri ? '' : $i + 1 }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
</section>
