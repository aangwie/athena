@extends('layouts.app')

@section('title', 'Usulan SKP')
@section('breadcrumb', 'Usulan SKP')
@section('page-title', 'Usulan SKP')

@section('content')
    @php
        $isEdit = $editSkp !== null;
        $fmt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Usulan SKP</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Daftar dosen yang perlu Anda nilai perilaku kerjanya sebagai Pejabat Penilai SKP, atau yang menunggu
                    keputusan keberatan dan persetujuan Anda sebagai Atasan Pejabat Penilai.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3.5 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Periksa kembali data Anda.</p>
                    <p class="mt-0.5 text-xs text-rose-600">
                        Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum data dapat disimpan.
                    </p>
                </div>
            </div>
        @endif

        @if ($isEdit)
            <form id="form-nilai" method="POST" action="{{ route('skp-usulan.update', $editSkp) }}" class="mb-6">
                @csrf
                @method('PUT')

                <section class="section-card">
                    <div class="section-heading">
                        <span class="section-number">01</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-800">Nilai Perilaku Kerja</h3>
                            <p class="mt-0.5 text-xs text-slate-400">
                                {{ $editSkp->dosen?->name ?? '—' }} — {{ $editSkp->periodeLabel() }} (Prodi {{ $editSkp->prodi?->nama_prodi ?? '—' }})
                            </p>
                        </div>
                    </div>

                    <div class="px-5 py-4 sm:px-6">
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-500">
                            Ketentuan kategori: <span class="font-semibold text-slate-700">≥91 Sangat Baik · 76–90 Baik · 61–75 Cukup · ≤60 Kurang</span>.
                            Jumlah dan nilai perilaku kerja (rata-rata nilai) dihitung otomatis.
                        </p>
                    </div>

                    <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-2">
                        @foreach ($unsurList as $kunci => $label)
                            <div>
                                <label for="nilai_{{ $kunci }}" class="form-label">{{ $label }} <span class="text-rose-500">*</span></label>
                                <input type="number" id="nilai_{{ $kunci }}" name="nilai_{{ $kunci }}"
                                       min="0" max="100" step="1" inputmode="numeric"
                                       value="{{ old('nilai_'.$kunci, $editSkp?->{'nilai_'.$kunci}) }}"
                                       @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nilai_'.$kunci)])>
                                @error('nilai_'.$kunci) <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    {{-- Tanggal penilaian: tercetak pada blok 9 ("DIBUAT TANGGAL, ...") halaman cetak SKP. --}}
                    <div class="border-t border-slate-100 px-5 py-5 sm:px-6">
                        <label for="tanggal_penilaian" class="form-label">
                            Tanggal Penilaian <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="tanggal_penilaian" name="tanggal_penilaian" required
                               value="{{ old('tanggal_penilaian', $editSkp?->tanggal_penilaian?->format('d-m-Y')) }}"
                               placeholder="DD-MM-YYYY"
                               maxlength="10" inputmode="numeric" autocomplete="off" data-date-mask data-date-picker
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_penilaian')])>
                        @error('tanggal_penilaian') <p class="form-error">{{ $message }}</p> @enderror

                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Pilih lewat kalender atau ketik format DD-MM-YYYY. Tampil pada blok 9 halaman cetak SKP
                            (&ldquo;DIBUAT TANGGAL, ...&rdquo;).
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                        <a href="{{ route('skp-usulan.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                        <button type="submit" class="btn-primary w-auto justify-center px-5">Simpan Nilai</button>
                    </div>
                </section>
            </form>
        @endif
<section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                                <rect x="9" y="3" width="6" height="4" rx="1.5" />
                                <path d="m9 13 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Usulan SKP</h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600">{{ $skpList->count() }} dosen</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Klik "Edit Nilai" untuk mengisi perilaku kerja dosen.</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto p-2 sm:p-4">
                    <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort w-16">No</th>
                                <th scope="col">Dosen yang Dinilai</th>
                                <th scope="col">Prodi</th>
                                <th scope="col">Jangka Waktu</th>
                                <th scope="col">Jumlah</th>
                                <th scope="col">Nilai Perilaku Kerja</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($skpList as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $item->dosen?->name ?? '—' }}</p>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $item->dosen?->nidn }}</p>
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">{{ $item->prodi?->nama_prodi ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="badge-status bg-slate-50 text-slate-600 ring-slate-200">{{ $item->periodeLabel() }}</span>
                                    </td>
                                    <td class="px-5 py-3 font-mono text-slate-700">{{ $fmt($item->jumlahNilai()) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($item->sudahDinilai())
                                            {{-- Nilai perilaku kerja = rata-rata nilai keenam unsur. --}}
                                            <span class="badge-status bg-teal-50 text-teal-700 ring-teal-200">{{ $fmt($item->rataRataNilai()) }}</span>
                                        @else
                                            <span class="badge-status bg-amber-50 text-amber-700 ring-amber-200">Belum dinilai</span>
                                        @endif
                                    </td>
<td class="px-5 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-1">
                                            @if ($item->atasan_penilai_id === auth()->id())
                                                {{-- Aksi Atasan Pejabat Penilai: keputusan keberatan (opsional) + persetujuan. --}}
                                                @if ($item->sudahDisetujuiAtasan())
                                                    <span class="badge-status bg-emerald-50 text-emerald-700 ring-emerald-200">
                                                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                            <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.2 7.3a1 1 0 0 1-1.4 0L4.3 10.3a1 1 0 0 1 1.4-1.4l3.1 3 6.5-6.6a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                                                        </svg>
                                                        Disetujui
                                                    </span>
                                                    <p class="text-[11px] text-slate-400">
                                                        {{ $item->disetujui_atasan_pada?->locale('id')->translatedFormat('j F Y') }}
                                                    </p>
                                                @elseif (! $item->sudahDisetujui())
                                                    <span class="text-xs text-slate-400">Menunggu persetujuan dosen</span>
                                                @else
                                                    {{-- Keputusan atas keberatan hanya bila keberatan sudah ditinjau
                                                         pejabat penilai dan dosen sudah menyetujui (opsional). --}}
                                                    @if ($item->bolehKeputusanKeberatan())
                                                        <a href="{{ route('skp-usulan.keputusan.edit', $item) }}"
                                                           title="Keputusan atas Keberatan" aria-label="Keputusan atas Keberatan"
                                                           class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                <path d="M6 4.5h9l4 4V19.5H6V4.5Z" stroke-linejoin="round" />
                                                                <path d="m9 13 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                            {{ $item->sudahDiputuskanAtasan() ? 'Ubah Keputusan' : 'Keputusan atas Keberatan' }}
                                                        </a>
                                                    @endif

                                                    {{-- Persetujuan atasan: tanggal persetujuan tercetak pada blok 11 SKP. --}}
                                                    <form method="POST" action="{{ route('skp-usulan.setujui-atasan', $item) }}"
                                                          class="flex items-center gap-1">
                                                        @csrf
                                                        <input type="text" name="tanggal_persetujuan" required
                                                               value="{{ old('tanggal_persetujuan', now()->format('d-m-Y')) }}"
                                                               placeholder="DD-MM-YYYY" aria-label="Tanggal persetujuan"
                                                               maxlength="10" inputmode="numeric" autocomplete="off"
                                                               data-date-mask data-date-picker
                                                               class="form-input w-32 px-2 py-1.5 text-xs">
                                                        <button type="submit"
                                                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-50">
                                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                <path d="m5 12.5 4.5 4.5L19 7.5" stroke-linecap="round" stroke-linejoin="round" />
                                                            </svg>
                                                            Setujui
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- Tanggapan keberatan hanya tampil bila dosen mengajukan keberatan. --}}
                                                @if ($item->mengajukanKeberatan() && ! $item->sudahDisetujui())
                                                    <a href="{{ route('skp-usulan.tanggapan.edit', $item) }}"
                                                       title="Tanggapan Keberatan" aria-label="Tanggapan Keberatan"
                                                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-50">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M4.5 5.5h15v10h-9L6 19v-3.5H4.5v-10Z" stroke-linejoin="round" />
                                                            <path d="M8 9h8M8 12h5" stroke-linecap="round" />
                                                        </svg>
                                                        {{ $item->sudahDitanggapi() ? 'Ubah Tanggapan' : 'Tanggapan Keberatan' }}
                                                    </a>
                                                @endif

                                                {{-- Nilai terkunci setelah dosen menyetujui; terbuka lagi bila persetujuan dibatalkan. --}}
                                                @if (! $item->sudahDisetujui())
                                                    <a href="{{ route('skp-usulan.index', ['edit' => $item->id]) }}#form-nilai"
                                                       title="Input Nilai" aria-label="Input Nilai"
                                                       class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-50">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                            <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                                        </svg>
                                                         Input Nilai
                                                    </a>
                                                @else
                                                    <span class="badge-status bg-emerald-50 text-emerald-700 ring-emerald-200">Disetujui dosen</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-16 text-center">
                                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19h11a1.5 1.5 0 0 0 1.5-1.5v-11A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                                                <rect x="9" y="3" width="6" height="4" rx="1.5" />
                                                <path d="M9 13h6M9 16h4" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada dosen yang perlu dinilai</p>
                                        <p class="mt-1 text-xs text-slate-400">Admin akan menugaskan Anda sebagai Pejabat Penilai SKP.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-2 border-t border-slate-100 bg-slate-50/50 px-5 py-3 text-[11px] text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="flex items-center gap-1.5">
                        <svg class="size-3.5 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.25v3h-.25a.75.75 0 0 0 0 1.5h2a.75.75 0 0 0 0-1.5h-.25v-3.75A.75.75 0 0 0 10.25 9H9Z" clip-rule="evenodd" />
                        </svg>
                        Nilai perilaku kerja = rata-rata nilai keenam unsur.
                    </p>
                    <p>Kategori otomatis dari nilai angka.</p>
                </div>
            </div>
        </section>
    </div>
@endsection