{{-- Blok pengesahan di bawah baris Jumlah pada formulir cetak DUPAK, mengikuti lampiran:
     "Bahan yang Dinilai" (label identitas di kolom kiri, isinya di kolom kanan),
     pernyataan validasi TIM PAK + tanda tangan Ketua di kolom kanan, lalu
     "Pendapat Tim Penilai Jabatan Angka Kredit".
     Stempel dan tanda tangan basah sengaja tidak dicetak (diisi manual). --}}
@php
    $selPengesahan = 'border border-slate-300 px-3 py-2 align-top text-xs';
    $kepalaPengesahan = 'border border-slate-300 bg-slate-100 px-2.5 py-1.5 text-left text-[10px] font-bold tracking-wide text-slate-600 uppercase';
    $labelPengesahan = 'w-24 shrink-0 font-semibold text-slate-600';
    $isiPengesahan = 'text-slate-800';

    // Jarak baris identitas di kolom label (kiri) dan kolom isi (kanan) disamakan supaya
    // titik dua pada isi duduk sejajar dengan labelnya, seperti lampiran DUPAK.
    $barisPengesahan = 'leading-6';

    // Kota instansi + tanggal keputusan (atau tanggal cetak bila belum diputuskan).
    $kotaTanggalPengesahan = ($instansi?->kota ?: '................').', '
        .($pengajuan?->dinilai_at?->locale('id')->translatedFormat('j F Y')
            ?? now()->locale('id')->translatedFormat('j F Y'));

    // Identitas dosen yang dinilai: label di kolom kiri, isi di kolom kanan.
    $rekapPengesahan = [
        ['Nama', $identitas['nama']],
        ['NUPTK / NIDN', $identitas['nuptk_nidn']],
        ['Jabatan, tmt', $identitas['jabatan_akademik']],
        ['Pangkat, tmt', $identitas['pangkat']],
        ['Bidang Ilmu', $identitas['bidang_ilmu']],
    ];
@endphp

{{-- Baris tabel pengesahan menyatu dengan tabel gabungan halaman cetak (11 kolom):
     kolom kiri 6 kolom, kolom kanan 5 kolom. --}}
<tr>
    <td colspan="6" class="{{ $kepalaPengesahan }}">Bahan yang Dinilai :</td>
    <td colspan="5" class="{{ $kepalaPengesahan }}">&nbsp;</td>
</tr>

<tr>
    {{-- Kolom kiri: label identitas dosen yang dinilai. --}}
    <td colspan="6" class="{{ $selPengesahan }}">
        <div class="pl-6">
            @foreach ($rekapPengesahan as [$label])
                <p class="{{ $barisPengesahan }} font-semibold text-slate-700">{{ $label }}</p>
            @endforeach
        </div>
    </td>

    {{-- Kolom kanan: isi identitas, pernyataan TIM PAK, lalu tanda tangan Ketua. --}}
    <td colspan="5" class="{{ $selPengesahan }}">
        @foreach ($rekapPengesahan as [$label, $nilai])
            <p class="{{ $barisPengesahan }} {{ $isiPengesahan }}">: {{ $nilai }}</p>
        @endforeach

        <p class="mt-4 leading-relaxed text-slate-700">
            Saya menyatakan bahwa Angka kredit telah dihitung dan dokumen pendukung
            DUPAK ini telah divalidasi oleh TIM PAK Perguruan Tinggi
        </p>

        <p class="mt-6 text-slate-700">{{ $kotaTanggalPengesahan }}</p>
        <p class="mt-1 font-semibold text-slate-700">Ketua</p>
        <div class="h-16"></div>
        <p class="font-bold text-slate-800 underline underline-offset-4">
            {{ $pimpinan?->name ?? '................' }}
        </p>
        <p class="text-[11px] text-slate-500">NIDN {{ $pimpinan?->nidn ?? '................' }}</p>
    </td>
</tr>

{{-- Judul pendapat tim penilai: satu baris penuh. --}}
<tr>
    <td colspan="11" class="{{ $kepalaPengesahan }}">Pendapat Tim Penilai Jabatan Angka Kredit :</td>
</tr>

{{-- Hasil penilaian (kiri) + tanda tangan Tim Penilai Pusat (kanan). --}}
<tr>
    <td colspan="6" class="{{ $selPengesahan }}">
        <p class="text-slate-700">Telah memenuhi syarat angka kredit untuk kenaikan Jabatan dan Pangkat menjadi :</p>

        <div class="mt-3 space-y-1">
            <div class="flex gap-2">
                <span class="{{ $labelPengesahan }}">Jabatan</span>
                <span class="{{ $isiPengesahan }}">: {{ $identitas['jabatan_diajukan'] }}</span>
            </div>
            <div class="flex gap-2">
                <span class="{{ $labelPengesahan }}">Pangkat/Gol</span>
                <span class="{{ $isiPengesahan }}">: {{ $identitas['pangkat_nama'] }}</span>
            </div>
        </div>
    </td>
    <td colspan="5" class="{{ $selPengesahan }}">
        <p class="text-slate-700">................,</p>
        <p class="mt-2 font-semibold text-slate-700">Tim Penilai Pusat</p>
        <p class="mt-4 text-slate-700">Ketua</p>
        <div class="h-16"></div>
        <p class="text-slate-700">................................</p>
    </td>
</tr>
