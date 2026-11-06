@extends('layouts.app')

@section('title', 'SKP')
@section('breadcrumb', 'SKP')
@section('page-title', 'SKP')

@section('content')
    @php
        $isEdit = $editSkp !== null;

        // Nilai jangka waktu untuk mode ubah / pengisian ulang setelah validasi gagal.
        $bulanMulaiLama = old('bulan_mulai', $editSkp?->periode_mulai?->month);
        $tahunMulaiLama = old('tahun_mulai', $editSkp?->periode_mulai?->year);
        $bulanAkhirLama = old('bulan_akhir', $editSkp?->periode_akhir?->month);
        $tahunAkhirLama = old('tahun_akhir', $editSkp?->periode_akhir?->year);
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Administrasi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">SKP</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Sasaran Kinerja Pegawai: dosen yang dinilai, pejabat penilai, atasan penilai, serta jangka waktu penilaian.
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

        {{-- Formulir tambah/edit SKP --}}
        <form id="form-skp" method="POST"
              action="{{ $isEdit ? route('skp.update', $editSkp) : route('skp.store') }}"
              class="mb-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">
                            {{ $isEdit ? 'Edit Data SKP' : 'Tambah Data SKP' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400">Lengkapi siapa yang dinilai, siapa penilainya, dan jangka waktunya.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-2">
                    <div>
                        <label for="dosen_id" class="form-label">Dosen yang Dinilai <span class="text-rose-500">*</span></label>
                        <select id="dosen_id" name="dosen_id" required
                                data-select-search="Cari nama dosen…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('dosen_id')])>
                            <option value="">Pilih dosen</option>
                            @foreach ($dosenList as $dosen)
                                <option value="{{ $dosen->id }}" @selected((string) old('dosen_id', $editSkp?->dosen_id) === (string) $dosen->id)>
                                    {{ $dosen->name }} — {{ $dosen->nidn }}
                                </option>
                            @endforeach
                        </select>
                        @error('dosen_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="prodi_id" class="form-label">Prodi Dosen yang Dinilai <span class="text-rose-500">*</span></label>
                        <select id="prodi_id" name="prodi_id" required
                                data-select-search="Cari prodi…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('prodi_id')])>
                            <option value="">Pilih prodi</option>
                            @foreach ($prodiList as $prodi)
                                <option value="{{ $prodi->id }}" @selected((string) old('prodi_id', $editSkp?->prodi_id) === (string) $prodi->id)>
                                    {{ $prodi->nama_prodi }}
                                </option>
                            @endforeach
                        </select>
                        @error('prodi_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
<div>
                        <label for="pejabat_penilai_id" class="form-label">Pejabat Penilai <span class="text-rose-500">*</span></label>
                        <select id="pejabat_penilai_id" name="pejabat_penilai_id" required
                                data-select-search="Cari pejabat penilai…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('pejabat_penilai_id')])>
                            <option value="">Pilih pejabat penilai</option>
                            @foreach ($penilaiList as $penilai)
                                <option value="{{ $penilai->user_id }}" @selected((string) old('pejabat_penilai_id', $editSkp?->pejabat_penilai_id) === (string) $penilai->user_id)>
                                    {{ $penilai->user?->name ?? '—' }} — {{ $penilai->jabatanStruktural?->nama ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                        @error('pejabat_penilai_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="atasan_penilai_id" class="form-label">Atasan Penilai <span class="text-rose-500">*</span></label>
                        <select id="atasan_penilai_id" name="atasan_penilai_id" required
                                data-select-search="Cari atasan penilai…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('atasan_penilai_id')])>
                            <option value="">Pilih atasan penilai</option>
                            @foreach ($penilaiList as $penilai)
                                <option value="{{ $penilai->user_id }}" @selected((string) old('atasan_penilai_id', $editSkp?->atasan_penilai_id) === (string) $penilai->user_id)>
                                    {{ $penilai->user?->name ?? '—' }} — {{ $penilai->jabatanStruktural?->nama ?? '—' }}
                                </option>
                            @endforeach
                        </select>
                        @error('atasan_penilai_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="form-label">Jangka Waktu Penilaian (Mulai) <span class="text-rose-500">*</span></span>
                        <div class="mt-1 grid grid-cols-2 gap-3">
                            <div>
                                <label for="bulan_mulai" class="sr-only">Bulan mulai</label>
                                <select id="bulan_mulai" name="bulan_mulai" required
                                        data-select-search="Cari bulan…"
                                        @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('bulan_mulai')])>
                                    <option value="">Bulan</option>
                                    @foreach ($bulanList as $nilai => $nama)
                                        <option value="{{ $nilai }}" @selected(old('bulan_mulai', $bulanMulaiLama) == $nilai)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="tahun_mulai" class="sr-only">Tahun mulai</label>
                                <select id="tahun_mulai" name="tahun_mulai" required
                                        data-select-search="Cari tahun…"
                                        @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('tahun_mulai')])>
                                    <option value="">Tahun</option>
                                    @foreach ($tahunList as $tahun)
                                        <option value="{{ $tahun }}" @selected(old('tahun_mulai', $tahunMulaiLama) == $tahun)>{{ $tahun }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('bulan_mulai') <p class="form-error">{{ $message }}</p> @enderror
                        @error('tahun_mulai') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <span class="form-label">Jangka Waktu Penilaian (Akhir) <span class="text-rose-500">*</span></span>
                        <div class="mt-1 grid grid-cols-2 gap-3">
                            <div>
                                <label for="bulan_akhir" class="sr-only">Bulan akhir</label>
                                <select id="bulan_akhir" name="bulan_akhir" required
                                        data-select-search="Cari bulan…"
                                        @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('bulan_akhir')])>
                                    <option value="">Bulan</option>
                                    @foreach ($bulanList as $nilai => $nama)
                                        <option value="{{ $nilai }}" @selected(old('bulan_akhir', $bulanAkhirLama) == $nilai)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="tahun_akhir" class="sr-only">Tahun akhir</label>
                                <select id="tahun_akhir" name="tahun_akhir" required
                                        data-select-search="Cari tahun…"
                                        @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('tahun_akhir')])>
                                    <option value="">Tahun</option>
                                    @foreach ($tahunList as $tahun)
                                        <option value="{{ $tahun }}" @selected(old('tahun_akhir', $tahunAkhirLama) == $tahun)>{{ $tahun }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @error('bulan_akhir') <p class="form-error">{{ $message }}</p> @enderror
                        @error('tahun_akhir') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    @if ($isEdit)
                        <a href="{{ route('skp.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                    @endif
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </section>
        </form>
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
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar SKP</h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600">{{ $items->count() }} data</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Data SKP diurutkan dari jangka waktu terbaru.</p>
                        </div>
                    </div>

                    <div class="relative w-full lg:w-72">
                        <label for="skp-search" class="sr-only">Cari SKP</label>
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.13 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                        <input type="search" id="skp-search" data-table-search placeholder="Cari dosen, status, atau penilai…"
                               class="form-input pl-9">
                    </div>
                </div>

                <div class="overflow-x-auto p-2 sm:p-4">
                    <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort w-16">No</th>
                                <th scope="col">Dosen yang Dinilai</th>
                                <th scope="col">Status</th>
                                <th scope="col">Pejabat Penilai</th>
                                <th scope="col">Atasan Penilai</th>
                                <th scope="col">Jangka Waktu Penilaian</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $item->dosen?->name ?? '—' }}</p>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $item->dosen?->nidn }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        @if ($item->sudahDinilai())
                                            <span class="badge-status bg-teal-50 text-teal-700 ring-teal-200">Dinilai</span>
                                        @else
                                            <span class="badge-status bg-amber-50 text-amber-700 ring-amber-200">Diusulkan</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3 text-slate-700">{{ $item->pejabatPenilai?->name ?? '—' }}</td>
                                    <td class="px-5 py-3 text-slate-700">{{ $item->atasanPenilai?->name ?? '—' }}</td>
                                    <td class="px-5 py-3">
                                        <span class="badge-status bg-slate-50 text-slate-600 ring-slate-200">{{ $item->periodeLabel() }}</span>
                                    </td>
<td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('skp.cetak', $item) }}" target="_blank" rel="noopener"
                                               title="Lihat" aria-label="Lihat"
                                               class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-indigo-50 hover:text-indigo-600">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('skp.cetak', ['skp' => $item, 'print' => 1]) }}" target="_blank" rel="noopener"
                                               title="Cetak" aria-label="Cetak"
                                               class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M7 9V4.5h10V9" stroke-linejoin="round" />
                                                    <path d="M7 17H5.5A1.5 1.5 0 0 1 4 15.5v-5A1.5 1.5 0 0 1 5.5 9h13a1.5 1.5 0 0 1 1.5 1.5v5a1.5 1.5 0 0 1-1.5 1.5H17" stroke-linejoin="round" />
                                                    <rect x="7" y="14.5" width="10" height="5.5" rx="1" />
                                                </svg>
                                            </a>
                                            <a href="{{ route('skp.index', ['edit' => $item->id]) }}#form-skp"
                                               title="Edit" aria-label="Edit"
                                               class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                    <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('skp.destroy', $item) }}"
                                                  data-confirm="Hapus data SKP untuk {{ $item->dosen?->name }}? Tindakan ini tidak dapat dibatalkan.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" aria-label="Hapus"
                                                        class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                                        <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                                                    </svg>
                                                </button>
                                            </form>
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
                                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada data SKP</p>
                                        <p class="mt-1 text-xs text-slate-400">Tambahkan data melalui formulir di atas.</p>
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
                        Gunakan kolom pencarian untuk menyaring data dengan cepat.
                    </p>
                    <p>Dosen dan pejabat penilai diambil dari data dosen yang aktif.</p>
                </div>
            </div>
        </section>
    </div>
@endsection