@extends('layouts.app')

@section('title', 'Riwayat Pekerjaan')
@section('breadcrumb', 'Profil Dosen')
@section('page-title', 'Riwayat Pekerjaan')

@section('content')
    @php
        $skList = [
            'pplp' => 'SK Dosen Tetap',
            'jafa' => 'SK Jafa Terakhir',
            'impasing' => 'SK Impasing Terakhir',
        ];
    @endphp

    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Profil Dosen
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Riwayat Pekerjaan</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Kelola identitas kepegawaian, masa kerja SK, masa kerja golongan, riwayat kepangkatan,
                    riwayat angka kredit, dan riwayat jabatan struktural.
                </p>
            </div>

            <a href="{{ route('profil.show') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Portofolio
            </a>
        </div>

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

        {{-- Identitas kepegawaian + masa kerja --}}
        <form method="POST" action="{{ route('profil.pekerjaan.update') }}" class="mb-5 space-y-5">
            @csrf
            @method('PUT')

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Identitas Kepegawaian</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Nomor induk kepegawaian dan prodi tempat mengajar saat ini.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-3">
                    <div>
                        <label for="nidn" class="form-label">NIDN</label>
                        <input type="text" id="nidn" value="{{ $user->nidn }}" readonly
                               class="form-input cursor-not-allowed bg-slate-100 text-slate-500">
                        <p class="mt-1.5 text-[11px] text-slate-400">Mengikuti akun, tidak dapat diubah di sini.</p>
                    </div>

                    <div>
                        <label for="nuptk" class="form-label">NUPTK</label>
                        <input type="text" id="nuptk" name="nuptk" value="{{ old('nuptk', $profil->nuptk ?? '') }}"
                               placeholder="Contoh: 1234567890123456"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nuptk')])>
                        @error('nuptk') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="program_studi" class="form-label">Prodi Tempat Mengajar</label>
                        <select id="program_studi" name="program_studi"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('program_studi')])>
                            <option value="">Pilih prodi</option>
                            @foreach ($prodiList as $prodi)
                                <option value="{{ $prodi->nama_prodi }}" @selected(old('program_studi', $user->program_studi) === $prodi->nama_prodi)>
                                    {{ $prodi->nama_prodi }}
                                </option>
                            @endforeach
                            {{-- Nilai lama yang sudah tidak ada di data master tetap dimunculkan agar tidak hilang. --}}
                            @if ($user->program_studi && ! $prodiList->contains('nama_prodi', $user->program_studi))
                                <option value="{{ $user->program_studi }}" @selected(! old('program_studi') || old('program_studi') === $user->program_studi)>
                                    {{ $user->program_studi }} (tidak ada di data master)
                                </option>
                            @endif
                        </select>
                        @error('program_studi') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">Pilihan diambil dari data master Prodi instansi.</p>
                    </div>
                </div>
            </section>

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">02</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Masa Kerja</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Masa kerja dihitung otomatis dari Tanggal SK (hari ini − tanggal SK).</p>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5 sm:px-6">
                    @foreach ($skList as $sk => $label)
                        @php
                            [$tahun, $bulan] = $profil?->masaKerja($sk) ?? [null, null];
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4" data-sk-group>
                            <p class="text-xs font-bold text-slate-700">{{ $label }}</p>

                            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="sk_{{ $sk }}_tanggal" class="form-label">Tanggal SK</label>
                                    <input type="date" id="sk_{{ $sk }}_tanggal" name="sk_{{ $sk }}_tanggal"
                                           data-sk-tanggal
                                           value="{{ old("sk_{$sk}_tanggal", $profil?->{"sk_{$sk}_tanggal"}?->format('Y-m-d')) }}"
                                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has("sk_{$sk}_tanggal")])>
                                    @error("sk_{$sk}_tanggal") <p class="form-error">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="sk_{{ $sk }}_tahun" class="form-label">Masa Kerja (Tahun)</label>
                                    <input type="number" id="sk_{{ $sk }}_tahun" value="{{ $tahun }}" readonly tabindex="-1" data-sk-tahun
                                           class="form-input cursor-not-allowed bg-slate-100 text-slate-500">
                                </div>

                                <div>
                                    <label for="sk_{{ $sk }}_bulan" class="form-label">Masa Kerja (Bulan)</label>
                                    <input type="number" id="sk_{{ $sk }}_bulan" value="{{ $bulan }}" readonly tabindex="-1" data-sk-bulan
                                           class="form-input cursor-not-allowed bg-slate-100 text-slate-500">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Masa kerja golongan lama & baru --}}
            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">03</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Masa Kerja Golongan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Masa kerja golongan lama dan golongan baru, diisi dalam tahun dan bulan.</p>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5 sm:px-6">
                    @foreach (['lama' => 'Masa Kerja Golongan Lama', 'baru' => 'Masa Kerja Golongan Baru'] as $jenis => $label)
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-bold text-slate-700">{{ $label }}</p>
                                <p class="text-[11px] text-slate-400">
                                    Tersimpan:
                                    <span class="font-semibold text-slate-600">{{ $profil?->masaKerjaGolonganLabel($jenis) ?? '—' }}</span>
                                </p>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <div class="flex items-center gap-1.5">
                                    <label for="masa_kerja_golongan_{{ $jenis }}_tahun" class="sr-only">Masa kerja golongan {{ $jenis }} (tahun)</label>
                                    <input type="number" min="0" max="60" id="masa_kerja_golongan_{{ $jenis }}_tahun"
                                           name="masa_kerja_golongan_{{ $jenis }}_tahun"
                                           value="{{ old("masa_kerja_golongan_{$jenis}_tahun", $profil?->{"masa_kerja_golongan_{$jenis}_tahun"}) }}"
                                           placeholder="11" inputmode="numeric"
                                           @class(['form-input w-20 text-center', 'border-rose-300 bg-rose-50/40' => $errors->has("masa_kerja_golongan_{$jenis}_tahun")])>
                                    <span class="text-xs font-semibold text-slate-500">tahun</span>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <label for="masa_kerja_golongan_{{ $jenis }}_bulan" class="sr-only">Masa kerja golongan {{ $jenis }} (bulan)</label>
                                    <input type="number" min="0" max="11" id="masa_kerja_golongan_{{ $jenis }}_bulan"
                                           name="masa_kerja_golongan_{{ $jenis }}_bulan"
                                           value="{{ old("masa_kerja_golongan_{$jenis}_bulan", $profil?->{"masa_kerja_golongan_{$jenis}_bulan"}) }}"
                                           placeholder="2" inputmode="numeric"
                                           @class(['form-input w-20 text-center', 'border-rose-300 bg-rose-50/40' => $errors->has("masa_kerja_golongan_{$jenis}_bulan")])>
                                    <span class="text-xs font-semibold text-slate-500">bulan</span>
                                </div>
                            </div>

                            @error("masa_kerja_golongan_{$jenis}_tahun") <p class="form-error">{{ $message }}</p> @enderror
                            @error("masa_kerja_golongan_{$jenis}_bulan") <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="flex justify-end rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:px-6">
                <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                        <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                    </svg>
                    Simpan Kepegawaian
                </button>
            </div>
        </form>

        {{-- Riwayat kepangkatan --}}
        <section class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-sm" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Kepangkatan</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Pangkat dan golongan diambil dari data kepegawaian instansi.</p>
                </div>
                @if ($editKepangkatan)
                    <a href="{{ route('profil.pekerjaan.index') }}" class="btn-secondary w-auto shrink-0 px-4">Batal Edit</a>
                @endif
            </div>

            <form method="POST"
                  action="{{ $editKepangkatan ? route('profil.pekerjaan.kepangkatan.update', $editKepangkatan) : route('profil.pekerjaan.kepangkatan.store') }}"
                  class="grid gap-4 border-b border-slate-100 bg-slate-50/50 px-5 py-5 sm:grid-cols-[1fr_14rem_auto] sm:items-end sm:px-6">
                @csrf
                @if ($editKepangkatan)
                    @method('PUT')
                @endif

                <div>
                    <label for="kepangkatan_id" class="form-label">Pangkat & Golongan <span class="text-rose-500">*</span></label>
                    <select id="kepangkatan_id" name="kepangkatan_id" required
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('kepangkatan_id')])>
                        <option value="">Pilih pangkat & golongan</option>
                        @foreach ($kepangkatanList as $pangkat)
                            <option value="{{ $pangkat->id }}" @selected((int) old('kepangkatan_id', $editKepangkatan?->kepangkatan_id) === $pangkat->id)>
                                {{ $pangkat->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('kepangkatan_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="kepangkatan_tmt" class="form-label">TMT <span class="text-rose-500">*</span></label>
                    <input type="date" id="kepangkatan_tmt" name="tmt" required
                           value="{{ old('tmt', $editKepangkatan?->tmt?->format('Y-m-d')) }}"
                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tmt')])>
                    @error('tmt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary h-11 w-auto px-5 whitespace-nowrap">
                    {{ $editKepangkatan ? 'Simpan Perubahan' : 'Tambah' }}
                </button>
            </form>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Pangkat & Golongan</th>
                            <th scope="col" class="w-48">TMT</th>
                            <th scope="col" class="no-sort w-40 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatKepangkatanList as $item)
                            <tr>
                                <td class="font-bold text-slate-700">{{ $item->pangkatLabel() }}</td>
                                <td data-order="{{ $item->tmt?->format('Y-m-d') }}">{{ $item->tmtLabel() }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('profil.pekerjaan.index', ['edit-kepangkatan' => $item->id]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('profil.pekerjaan.kepangkatan.destroy', $item) }}"
                                              data-confirm="Hapus riwayat kepangkatan {{ $item->pangkatLabel() }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-12 text-center text-xs text-slate-400">
                                    Belum ada riwayat kepangkatan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Riwayat angka kredit --}}
        <section class="mb-5 rounded-2xl border border-slate-200/80 bg-white shadow-sm" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Angka Kredit</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Angka kredit yang diperoleh beserta tahun pengajuannya.</p>
                </div>
                @if ($editAngkaKredit)
                    <a href="{{ route('profil.pekerjaan.index') }}" class="btn-secondary w-auto shrink-0 px-4">Batal Edit</a>
                @endif
            </div>

            <form method="POST"
                  action="{{ $editAngkaKredit ? route('profil.pekerjaan.angka-kredit.update', $editAngkaKredit) : route('profil.pekerjaan.angka-kredit.store') }}"
                  class="grid gap-4 border-b border-slate-100 bg-slate-50/50 px-5 py-5 sm:grid-cols-[1fr_12rem_auto] sm:items-end sm:px-6">
                @csrf
                @if ($editAngkaKredit)
                    @method('PUT')
                @endif

                <div>
                    <label for="angka_kredit" class="form-label">Angka Kredit <span class="text-rose-500">*</span></label>
                    <input type="number" step="0.01" min="0" max="9999.99" id="angka_kredit" name="angka_kredit" required
                           value="{{ old('angka_kredit', $editAngkaKredit?->angka_kredit) }}" placeholder="Contoh: 152.50"
                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('angka_kredit')])>
                    @error('angka_kredit') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="angka_kredit_tahun" class="form-label">Tahun Pengajuan <span class="text-rose-500">*</span></label>
                    <input type="number" min="1980" max="{{ now()->year + 1 }}" id="angka_kredit_tahun" name="tahun" required
                           value="{{ old('tahun', $editAngkaKredit?->tahun) }}" placeholder="{{ now()->year }}"
                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tahun')])>
                    @error('tahun') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary h-11 w-auto px-5 whitespace-nowrap">
                    {{ $editAngkaKredit ? 'Simpan Perubahan' : 'Tambah' }}
                </button>
            </form>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="w-44">Tahun Pengajuan</th>
                            <th scope="col">Angka Kredit</th>
                            <th scope="col" class="no-sort w-40 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatAngkaKreditList as $item)
                            <tr>
                                <td data-order="{{ $item->tahun }}" class="font-bold text-slate-700">{{ $item->tahun }}</td>
                                <td class="text-slate-600">{{ $item->angkaKreditLabel() }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('profil.pekerjaan.index', ['edit-angka-kredit' => $item->id]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('profil.pekerjaan.angka-kredit.destroy', $item) }}"
                                              data-confirm="Hapus riwayat angka kredit tahun {{ $item->tahun }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-12 text-center text-xs text-slate-400">
                                    Belum ada riwayat angka kredit.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Riwayat jabatan struktural --}}
        <section class="rounded-2xl border border-slate-200/80 bg-white shadow-sm" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Riwayat Jabatan Struktural</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Jabatan struktural dan fungsional diambil dari data kepegawaian instansi.</p>
                </div>
                @if ($editJabatan)
                    <a href="{{ route('profil.pekerjaan.index') }}" class="btn-secondary w-auto shrink-0 px-4">Batal Edit</a>
                @endif
            </div>

            <form method="POST"
                  action="{{ $editJabatan ? route('profil.pekerjaan.jabatan.update', $editJabatan) : route('profil.pekerjaan.jabatan.store') }}"
                  class="grid gap-4 border-b border-slate-100 bg-slate-50/50 px-5 py-5 sm:grid-cols-[1fr_1fr_12rem_auto] sm:items-end sm:px-6">
                @csrf
                @if ($editJabatan)
                    @method('PUT')
                @endif

                <div>
                    <label for="jabatan_struktural_id" class="form-label">Jabatan Struktural <span class="text-rose-500">*</span></label>
                    <select id="jabatan_struktural_id" name="jabatan_struktural_id" required
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan_struktural_id')])>
                        <option value="">Pilih jabatan struktural</option>
                        @foreach ($jabatanStrukturalList as $jabatan)
                            <option value="{{ $jabatan->id }}" @selected((int) old('jabatan_struktural_id', $editJabatan?->jabatan_struktural_id) === $jabatan->id)>
                                {{ $jabatan->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jabatan_struktural_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="jabatan_fungsional_id" class="form-label">Jabatan Fungsional <span class="text-rose-500">*</span></label>
                    <select id="jabatan_fungsional_id" name="jabatan_fungsional_id" required
                            @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan_fungsional_id')])>
                        <option value="">Pilih jabatan fungsional</option>
                        @foreach ($jabatanFungsionalList as $jabatan)
                            <option value="{{ $jabatan->id }}" @selected((int) old('jabatan_fungsional_id', $editJabatan?->jabatan_fungsional_id) === $jabatan->id)>
                                {{ $jabatan->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('jabatan_fungsional_id') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="jabatan_tmt" class="form-label">TMT <span class="text-rose-500">*</span></label>
                    <input type="date" id="jabatan_tmt" name="tmt" required
                           value="{{ old('tmt', $editJabatan?->tmt?->format('Y-m-d')) }}"
                           @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tmt')])>
                    @error('tmt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primary h-11 w-auto px-5 whitespace-nowrap">
                    {{ $editJabatan ? 'Simpan Perubahan' : 'Tambah' }}
                </button>
            </form>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Jabatan Struktural</th>
                            <th scope="col">Jabatan Fungsional</th>
                            <th scope="col" class="w-44">TMT</th>
                            <th scope="col" class="no-sort w-40 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($riwayatJabatanList as $item)
                            <tr>
                                <td class="font-bold text-slate-700">{{ $item->strukturalLabel() }}</td>
                                <td class="text-slate-600">{{ $item->fungsionalLabel() }}</td>
                                <td data-order="{{ $item->tmt?->format('Y-m-d') }}">{{ $item->tmtLabel() }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('profil.pekerjaan.index', ['edit-jabatan' => $item->id]) }}"
                                           class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('profil.pekerjaan.jabatan.destroy', $item) }}"
                                              data-confirm="Hapus riwayat jabatan {{ $item->strukturalLabel() }}?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-12 text-center text-xs text-slate-400">
                                    Belum ada riwayat jabatan struktural.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
