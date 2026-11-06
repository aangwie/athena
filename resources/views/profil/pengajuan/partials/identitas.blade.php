{{-- Blok "Keterangan Perorangan" pada formulir cetak DUPAK: identitas dosen pengusul. --}}
@php
    // Sel tabel memakai gaya yang sama dengan rekap DUPAK agar tampak menyatu.
    $selIdentitas = 'border border-slate-300 px-2.5 py-1.5 align-top';
    $kepalaIdentitas = 'border border-slate-300 bg-slate-100 px-2 py-2 text-center text-[10px] font-bold tracking-wide text-slate-600 uppercase';

    $barisIdentitas = [
        '1' => ['Nama', $identitas['nama']],
        '2' => ['NUPTK/NIDN', $identitas['nuptk_nidn']],
        '3' => ['Tempat dan Tanggal Lahir', $identitas['ttl']],
        '4' => ['Jenis Kelamin', $identitas['jenis_kelamin']],
        '5' => ['Pendidikan Terakhir', $identitas['pendidikan_terakhir']],
        '6' => ['Jabatan Akademik Dosen, Angka Kredit/TMT', $identitas['jabatan_akademik']],
        '7' => ['Pangkat dan Golongan Ruang/TMT', $identitas['pangkat']],
        '8' => ['Masa kerja golongan lama', $identitas['masa_kerja_lama']],
        '9' => ['Masa kerja golongan baru', $identitas['masa_kerja_baru']],
        '10' => ['Unit Kerja', $instansi?->nama_instansi ?: '—'],
    ];
@endphp

{{-- Baris "Keterangan Perorangan" menempel langsung ke tabel gabungan halaman cetak
     (11 kolom): nomor 1 kolom, label 5 kolom, isi 5 kolom.
     $bagian = 'kepala' (hanya baris <thead>) atau 'isi' (hanya baris <tbody>). --}}
@php $bagianIdentitas = $bagian ?? 'semua'; @endphp

@if ($bagianIdentitas !== 'isi')
    {{-- Instansi & masa penilaian menjadi baris pembuka tabel (bukan div terpisah) agar
         formulir cetak tampil sebagai satu tabel utuh. --}}
    <tr>
        <td colspan="6" class="{{ $selIdentitas }} text-xs">
            <span class="font-bold">INSTANSI :</span> {{ $instansi?->nama_instansi ?: '—' }}
        </td>
        <td colspan="5" class="{{ $selIdentitas }} text-right text-xs">
            <span class="font-bold">MASA PENILAIAN :</span> {{ $masaPenilaian?->label() ?? '—' }}
        </td>
    </tr>

    <tr>
        <th scope="col" colspan="11" class="{{ $kepalaIdentitas }}">Keterangan Perorangan</th>
    </tr>
@endif

@if ($bagianIdentitas !== 'kepala')
    @foreach ($barisIdentitas as $no => $item)
        <tr>
            <td class="{{ $selIdentitas }} text-center align-middle font-semibold text-slate-600">{{ $no }}</td>
            <td colspan="5" class="{{ $selIdentitas }} font-semibold text-slate-700">{{ $item[0] }}</td>
            <td colspan="5" class="{{ $selIdentitas }} text-slate-800">{{ $item[1] }}</td>
        </tr>

        @if ($no === 6)
            {{-- Rincian jabatan akademik: jurusan/prodi dan bidang ilmu. --}}
            <tr>
                <td class="{{ $selIdentitas }} text-center align-middle text-slate-600">a.</td>
                <td colspan="5" class="{{ $selIdentitas }} pl-6 font-semibold text-slate-700">Jurusan/Program Studi</td>
                <td colspan="5" class="{{ $selIdentitas }} text-slate-800">{{ $identitas['jurusan_prodi'] }}</td>
            </tr>
            <tr>
                <td class="{{ $selIdentitas }} text-center align-middle text-slate-600">b.</td>
                <td colspan="5" class="{{ $selIdentitas }} pl-6 font-semibold text-slate-700">Bidang Ilmu</td>
                <td colspan="5" class="{{ $selIdentitas }} text-slate-800">{{ $identitas['bidang_ilmu'] }}</td>
            </tr>
        @endif
    @endforeach
@endif
