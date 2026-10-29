{{-- Rekap bergaya formulir DUPAK: Bidang (I–V) → Komponen (A, B, …) → Rincian Komponen (1, 2, …) → Rincian Kegiatan (a, b, …) → butir kegiatan. --}}
@php
    $model = \App\Models\KegiatanDupak::class;
    $peta = fn (string $nama) => constant($model.'::'.$nama);
    $huruf = fn (int $i) => chr(65 + $i);
    $hurufKecil = fn (int $i) => strtolower($huruf($i));

    // TIM PAK mengisi kolom "Baru"; dosen hanya melihat hasilnya.
    $editable = $editable ?? false;
    $angka = fn ($nilai) => $nilai === null || $nilai === ''
        ? ''
        : rtrim(rtrim(number_format((float) $nilai, 2, ',', '.'), '0'), ',');

    $grup = [
        ['nomor' => 'I', 'judul' => 'Pendidikan (Bidang A)', 'entri' => $kegiatanPendidikan, 'jenis' => 'JENIS_PENDIDIKAN', 'sub' => null, 'rincian' => null],
        ['nomor' => 'II', 'judul' => 'Pelaksanaan Pendidikan (Bidang B)', 'entri' => $kegiatanPelaksanaan, 'jenis' => 'JENIS_PELAKSANAAN', 'sub' => 'SUB_PELAKSANAAN', 'rincian' => null],
        ['nomor' => 'III', 'judul' => 'Penelitian (Bidang C)', 'entri' => $kegiatanPenelitian, 'jenis' => 'JENIS_PENELITIAN', 'sub' => 'SUB_PENELITIAN', 'rincian' => 'RINCIAN_PENELITIAN'],
        ['nomor' => 'IV', 'judul' => 'Pengabdian kepada Masyarakat (Bidang D)', 'entri' => $kegiatanAbdimas, 'jenis' => 'JENIS_PENGABDIAN', 'sub' => 'SUB_PENGABDIAN', 'rincian' => 'RINCIAN_PENGABDIAN'],
        ['nomor' => 'V', 'judul' => 'Penunjang (Bidang E)', 'entri' => $kegiatanPenunjang, 'jenis' => 'JENIS_PENUNJANG', 'sub' => 'SUB_PENUNJANG', 'rincian' => 'RINCIAN_PENUNJANG'],
    ];

    // Semua baris diratakan (flat) supaya markup <tr> hanya ditulis sekali.
    // tingkat: 0 bidang, 1 komponen, 2 rincian komponen, 3 rincian kegiatan, 4 butir entri, 5 jumlah.
    $baris = [];
    $butir = fn ($entri, string $nomor, string $idGrup) => [
        'tingkat' => 4,
        'nomor' => $nomor,
        'label' => $entri->nama_kegiatan,
        'id' => $entri->id,
        'nilai' => $entri->nilai_baru,
        'grup' => $idGrup,
        'link' => $entri->link_bukti,
    ];

    foreach ($grup as $iGrup => $g) {
        $idGrup = 'g'.$iGrup;
        $jenisMap = $peta($g['jenis']);
        $subMap = $g['sub'] ? $peta($g['sub']) : [];
        $rincianMap = $g['rincian'] ? $peta($g['rincian']) : [];
        $urutJenis = array_flip(array_keys($jenisMap));

        $baris[] = ['tingkat' => 0, 'nomor' => $g['nomor'], 'label' => $g['judul'], 'kredit' => null, 'link' => null];

        $iKomponen = 0;
        $perJenis = $g['entri']->groupBy('jenis')
            ->sortBy(fn ($items, $jenis) => $urutJenis[$jenis] ?? PHP_INT_MAX);

        foreach ($perJenis as $jenis => $itemsJenis) {
            $baris[] = [
                'tingkat' => 1,
                'nomor' => $huruf($iKomponen++),
                'label' => $jenisMap[$jenis] ?? $itemsJenis->first()->jenisLabel(),
                'kredit' => null,
                'link' => null,
            ];

            // Komponen tanpa rincian komponen: entri langsung menjadi butir kegiatan.
            if (($subMap[$jenis] ?? []) === []) {
                foreach ($itemsJenis->values() as $i => $entri) {
                    $baris[] = $butir($entri, ($i + 1).'.', $idGrup);
                }

                continue;
            }

            $urutSub = array_flip(array_keys($subMap[$jenis]));
            $iSub = 0;
            $perSub = $itemsJenis->groupBy('sub_jenis')
                ->sortBy(fn ($items, $sub) => $urutSub[$sub] ?? PHP_INT_MAX);

            foreach ($perSub as $sub => $itemsSub) {
                $baris[] = [
                    'tingkat' => 2,
                    'nomor' => ($iSub++ + 1).'.',
                    'label' => $subMap[$jenis][$sub] ?? $itemsSub->first()->subJenisLabel(),
                    'kredit' => null,
                    'link' => null,
                ];

                // Rincian komponen tanpa rincian kegiatan: entri menjadi butir beraplikasi huruf.
                if (($rincianMap[$sub] ?? []) === []) {
                    foreach ($itemsSub->values() as $i => $entri) {
                        $baris[] = $butir($entri, $hurufKecil($i).'.', $idGrup);
                    }

                    continue;
                }

                $urutRincian = array_flip(array_keys($rincianMap[$sub]));
                $iRincian = 0;
                $perRincian = $itemsSub->groupBy('rincian')
                    ->sortBy(fn ($items, $rincian) => $urutRincian[$rincian] ?? PHP_INT_MAX);

                foreach ($perRincian as $rincian => $itemsRincian) {
                    $baris[] = [
                        'tingkat' => 3,
                        'nomor' => $hurufKecil($iRincian++).'.',
                        'label' => $rincianMap[$sub][$rincian] ?? $itemsRincian->first()->rincianLabel(),
                        'kredit' => null,
                        'link' => null,
                    ];

                    foreach ($itemsRincian->values() as $i => $entri) {
                        $baris[] = $butir($entri, ($i + 1).'.', $idGrup);
                    }
                }
            }
        }

        $baris[] = [
            'tingkat' => 5,
            'nomor' => null,
            'label' => 'Jumlah',
            'kredit' => $g['entri']->sum('nilai_baru'),
            'grup' => $idGrup,
            'link' => null,
        ];
    }

    // Akumulasi seluruh baris "Jumlah" (Bidang I–V) di paling bawah tabel.
    $baris[] = [
        'tingkat' => 6,
        'nomor' => null,
        'label' => 'Jumlah Total',
        'kredit' => collect($baris)->where('tingkat', 5)->sum('kredit'),
        'grup' => 'total',
        'link' => null,
    ];

    // Sel tabel formulir: garis penuh, padding rapat.
    $sel = 'border border-slate-300 px-2.5 py-1.5 align-top';
    $selAngka = $sel.' text-center align-middle';
    $kepala = 'border border-slate-300 bg-slate-100 px-2 py-2 text-center text-[10px] font-bold tracking-wide text-slate-600 uppercase';
@endphp


<div class="overflow-x-auto">
    <table class="w-full min-w-[54rem] border-collapse text-xs">
        <caption class="sr-only">Rekap unsur, sub unsur, dan butir kegiatan DUPAK</caption>
        <thead>
            <tr>
                <th scope="col" rowspan="4" class="{{ $kepala }} w-12 align-middle">No</th>
                <th scope="col" colspan="8" class="{{ $kepala }}">Unsur yang Dinilai</th>
            </tr>
            <tr>
                <th scope="col" rowspan="3" colspan="4" class="{{ $kepala }} align-middle">Unsur, Sub Unsur, dan Butir Kegiatan</th>
                <th scope="col" colspan="4" class="{{ $kepala }}">Angka Kredit Menurut</th>
            </tr>
            <tr>
                <th scope="col" colspan="4" class="{{ $kepala }}">Instansi Pengusul</th>
            </tr>
            <tr>
                <th scope="col" class="{{ $kepala }}">Lama</th>
                <th scope="col" class="{{ $kepala }}">Baru</th>
                <th scope="col" class="{{ $kepala }}">Jumlah</th>
                <th scope="col" class="{{ $kepala }}">Link Bukti</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($baris as $r)
                <tr @class([
                    'bg-slate-50' => $r['tingkat'] === 0,
                    'bg-slate-50/70' => $r['tingkat'] === 5,
                    'bg-teal-50' => $r['tingkat'] === 6,
                ])>
                    @if ($r['tingkat'] === 0)
                        <td class="{{ $selAngka }} font-extrabold text-slate-700">{{ $r['nomor'] }}</td>
                        <td colspan="4" class="{{ $sel }} font-extrabold tracking-wide text-slate-800 uppercase">{{ $r['label'] }}</td>
                    @elseif ($r['tingkat'] === 1)
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $selAngka }} font-bold text-slate-700">{{ $r['nomor'] }}</td>
                        <td colspan="3" class="{{ $sel }} font-bold text-slate-700">{{ $r['label'] }}</td>
                    @elseif ($r['tingkat'] === 2)
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $selAngka }} text-slate-500">{{ $r['nomor'] }}</td>
                        <td colspan="2" class="{{ $sel }} text-slate-700">{{ $r['label'] }}</td>
                    @elseif ($r['tingkat'] === 3)
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $selAngka }} text-slate-500">{{ $r['nomor'] }}</td>
                        <td class="{{ $sel }} text-slate-700">{{ $r['label'] }}</td>
                    @elseif ($r['tingkat'] === 4)
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }} text-slate-700">
                            <span class="mr-1 text-slate-400">{{ $r['nomor'] }}</span> {{ $r['label'] }}
                        </td>
                    @elseif ($r['tingkat'] === 5)
                        <td colspan="5" class="{{ $sel }} text-right font-extrabold tracking-wide text-slate-700 uppercase">{{ $r['label'] }}</td>
                    @else
                        <td colspan="5" class="{{ $sel }} text-right font-extrabold tracking-wide text-teal-800 uppercase">{{ $r['label'] }}</td>
                    @endif

                    @if ($r['tingkat'] === 4)
                        <td class="{{ $selAngka }}"></td>
                        <td class="{{ $selAngka }} text-slate-700">
                            @if ($editable)
                                <input type="number" step="0.01" min="0" max="9999.99" inputmode="decimal"
                                       name="nilai[{{ $r['id'] }}]"
                                       value="{{ $r['nilai'] !== null ? number_format((float) $r['nilai'], 2, '.', '') : '' }}"
                                       data-nilai data-nilai-grup="{{ $r['grup'] }}"
                                       aria-label="Angka kredit baru: {{ $r['label'] }}"
                                       class="w-16 rounded-md border border-slate-300 px-1.5 py-1 text-center text-xs text-slate-800 shadow-sm focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 focus:outline-none">
                            @else
                                {{ $angka($r['nilai']) }}
                            @endif
                        </td>
                        <td class="{{ $selAngka }}"></td>
                        <td class="{{ $sel }}">
                            @if ($r['link'])
                                <a href="{{ $r['link'] }}" target="_blank" rel="noopener noreferrer"
                                   title="{{ $r['link'] }}"
                                   class="inline-flex items-center gap-1 font-semibold text-teal-700 transition hover:text-teal-900">
                                    <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M10.5 13.5a3.5 3.5 0 0 0 5 0l3-3a3.54 3.54 0 0 0-5-5l-1.2 1.2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.5 10.5a3.5 3.5 0 0 0-5 0l-3 3a3.54 3.54 0 0 0 5 5l1.2-1.2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <span class="underline underline-offset-2">Bukti</span>
                                </a>
                            @else
                                <span class="text-[11px] text-rose-500 italic">Tidak ada bukti</span>
                            @endif
                        </td>
                    @elseif ($r['tingkat'] === 5)
                        <td class="{{ $selAngka }} font-extrabold text-slate-700">0</td>
                        <td class="{{ $selAngka }} font-extrabold text-slate-700" data-jumlah-total="{{ $r['grup'] }}">{{ $angka($r['kredit']) }}</td>
                        <td class="{{ $selAngka }} font-extrabold text-slate-700" data-jumlah-total="{{ $r['grup'] }}">{{ $angka($r['kredit']) }}</td>
                        <td class="{{ $sel }}"></td>
                    @elseif ($r['tingkat'] === 6)
                        <td class="{{ $selAngka }} font-extrabold text-teal-800">0</td>
                        <td class="{{ $selAngka }} font-extrabold text-teal-800" data-jumlah-grand-total>{{ $angka($r['kredit']) }}</td>
                        <td class="{{ $selAngka }} font-extrabold text-teal-800" data-jumlah-grand-total>{{ $angka($r['kredit']) }}</td>
                        <td class="{{ $sel }}"></td>
                    @else
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                        <td class="{{ $sel }}"></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<p class="mt-3 text-[11px] leading-relaxed text-slate-400">
    Kolom <span class="font-semibold">Baru</span> diisi oleh TIM PAK saat menilai usulan; baris
    <span class="font-semibold">Jumlah</span> di setiap bidang dan <span class="font-semibold">Jumlah Total</span>
    di paling bawah dihitung otomatis dari nilai tersebut.
</p>
