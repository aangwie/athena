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

<div class="mb-3 flex flex-wrap items-end justify-between gap-x-6 gap-y-1 text-xs text-slate-700">
    <p><span class="font-bold">INSTANSI :</span> {{ $instansi?->nama_instansi ?: '—' }}</p>
    <p class="sm:text-right">
        <span class="font-bold">MASA PENILAIAN :</span>
        {{ $masaPenilaian?->label() ?? '—' }}
    </p>
</div>

<div class="overflow-x-auto">
    <table class="w-full border-collapse text-xs">
        <thead>
            <tr>
                <th scope="col" class="{{ $kepalaIdentitas }} w-14">No</th>
                <th scope="col" colspan="2" class="{{ $kepalaIdentitas }}">Keterangan Perorangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($barisIdentitas as $no => $item)
                <tr>
                    <td class="{{ $selIdentitas }} w-14 text-center align-middle font-semibold text-slate-600">{{ $no }}</td>
                    <td class="{{ $selIdentitas }} w-72 font-semibold text-slate-700">{{ $item[0] }}</td>
                    <td class="{{ $selIdentitas }} text-slate-800">{{ $item[1] }}</td>
                </tr>

                @if ($no === 6)
                    {{-- Rincian jabatan akademik: jurusan/prodi dan bidang ilmu. --}}
                    <tr>
                        <td class="{{ $selIdentitas }} w-14 text-center align-middle text-slate-600">a.</td>
                        <td class="{{ $selIdentitas }} pl-6 font-semibold text-slate-700">Jurusan/Program Studi</td>
                        <td class="{{ $selIdentitas }} text-slate-800">{{ $identitas['jurusan_prodi'] }}</td>
                    </tr>
                    <tr>
                        <td class="{{ $selIdentitas }} w-14 text-center align-middle text-slate-600">b.</td>
                        <td class="{{ $selIdentitas }} pl-6 font-semibold text-slate-700">Bidang Ilmu</td>
                        <td class="{{ $selIdentitas }} text-slate-800">{{ $identitas['bidang_ilmu'] }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
