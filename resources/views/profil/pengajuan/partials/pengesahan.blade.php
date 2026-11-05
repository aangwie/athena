{{-- Blok pengesahan di bawah baris Jumlah pada formulir cetak DUPAK:
     rekap identitas + pernyataan Ketua, lalu "Pendapat Tim Penilai Jabatan Angka Kredit".
     Stempel dan tanda tangan basah sengaja tidak dicetak (diisi manual). --}}
@php
    $selPengesahan = 'border border-slate-300 px-3 py-2 align-top text-xs';
    $kepalaPengesahan = 'border border-slate-300 bg-slate-100 px-2.5 py-1.5 text-left text-[10px] font-bold tracking-wide text-slate-600 uppercase';
    $labelPengesahan = 'w-24 shrink-0 font-semibold text-slate-600';
    $isiPengesahan = 'text-slate-800';

    // Kota instansi + tanggal keputusan (atau tanggal cetak bila belum diputuskan).
    $kotaTanggalPengesahan = ($instansi?->kota ?: '................').', '
        .($pengajuan?->dinilai_at?->locale('id')->translatedFormat('j F Y')
            ?? now()->locale('id')->translatedFormat('j F Y'));

    $rekapPengesahan = [
        ['Nama', $identitas['nama']],
        ['NUPTK / NIDN', $identitas['nuptk_nidn']],
        ['Jabatan, tmt', $identitas['jabatan_akademik']],
        ['Pangkat, tmt', $identitas['pangkat']],
        ['Bidang Ilmu', $identitas['bidang_ilmu']],
    ];
@endphp

<div class="overflow-x-auto">
    <table class="tabel-cetak w-full {{ $kolomPenilai ?? false ? 'min-w-[68rem]' : 'min-w-[54rem]' }} border-collapse text-xs">
        <colgroup>
            <col style="width: 55%">
            <col style="width: 45%">
        </colgroup>
        <tbody>
            {{-- Rekap identitas (kiri) + pernyataan & tanda tangan Ketua (kanan). --}}
            <tr>
                <td class="{{ $selPengesahan }}">
                    <dl class="space-y-1">
                        @foreach ($rekapPengesahan as [$label, $nilai])
                            <div class="flex gap-2">
                                <dt class="{{ $labelPengesahan }}">{{ $label }}</dt>
                                <dd class="{{ $isiPengesahan }}">: {{ $nilai }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    <p class="mt-5 leading-relaxed text-slate-700">
                        Saya menyatakan bahwa Angka kredit telah dihitung dan dokumen pendukung
                        DUPAK ini telah divalidasi oleh TIM PAK Perguruan Tinggi
                    </p>
                </td>
                <td class="{{ $selPengesahan }}">
                    <p class="text-right text-slate-700">{{ $kotaTanggalPengesahan }}</p>
                    <p class="mt-2 text-right font-semibold text-slate-700">Ketua</p>
                    <div class="h-16"></div>
                    <p class="text-right font-bold text-slate-800 underline underline-offset-4">
                        {{ $pimpinan?->name ?? '................' }}
                    </p>
                    <p class="text-right text-[11px] text-slate-500">NIDN {{ $pimpinan?->nidn ?? '................' }}</p>
                </td>
            </tr>

            {{-- Judul pendapat tim penilai: satu baris penuh. --}}
            <tr>
                <td colspan="2" class="{{ $kepalaPengesahan }}">Pendapat Tim Penilai Jabatan Angka Kredit :</td>
            </tr>

            {{-- Hasil penilaian (kiri) + tanda tangan Tim Penilai Pusat (kanan). --}}
            <tr>
                <td class="{{ $selPengesahan }}">
                    <p class="text-slate-700">Telah memenuhi syarat angka kredit untuk kenaikan Jabatan dan Pangkat menjadi :</p>

                    <div class="mt-3 space-y-1">
                        <div class="flex gap-2">
                            <span class="{{ $labelPengesahan }}">Jabatan</span>
                            <span class="{{ $isiPengesahan }}">: {{ $identitas['jabatan'] }}</span>
                        </div>
                        <div class="flex gap-2">
                            <span class="{{ $labelPengesahan }}">Pangkat/Gol</span>
                            <span class="{{ $isiPengesahan }}">: {{ $identitas['pangkat_nama'] }}</span>
                        </div>
                    </div>
                </td>
                <td class="{{ $selPengesahan }}">
                    <p class="text-slate-700">................,</p>
                    <p class="mt-2 font-semibold text-slate-700">Tim Penilai Pusat</p>
                    <p class="mt-4 text-slate-700">Ketua</p>
                    <div class="h-16"></div>
                    <p class="text-slate-700">................................</p>
                </td>
            </tr>
        </tbody>
    </table>
</div>
