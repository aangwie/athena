@extends('layouts.app')

@section('title', 'Senat')
@section('breadcrumb', 'Instansi')
@section('page-title', 'Senat')

@section('content')
    @php
        $isEdit = $editSenat !== null;
        // Ketua senat hanya satu; opsi "Ketua" dikunci bila sudah terisi baris lain.
        $ketua = $items->firstWhere('jabatan', 'ketua');
        $ketuaBolehDipilih = $ketua === null || $ketua->id === $editSenat?->id;
    @endphp

    <div class="mx-auto max-w-5xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Instansi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Senat</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Susunan anggota senat instansi beserta jabatannya, dipilih dari data dosen aktif.
                </p>
            </div>

            <a href="{{ route('instansi.struktur.index') }}" class="btn-secondary shrink-0">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                    <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                    <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                    <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Pejabat Struktural
            </a>
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

        {{-- Formulir tambah/edit anggota senat --}}
        <form id="form-senat" method="POST"
              action="{{ $isEdit ? route('instansi.senat.update', $editSenat) : route('instansi.senat.store') }}"
              class="mb-5">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <section class="section-card">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">
                            {{ $isEdit ? 'Edit Anggota Senat' : 'Tambah Anggota Senat' }}
                        </h3>
                        <p class="mt-0.5 text-xs text-slate-400">Pilih dosen aktif, lalu tentukan jabatannya pada senat.</p>
                    </div>
                </div>

                <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-3">
                    <div>
                        <label for="user_id" class="form-label">Nama Dosen <span class="text-rose-500">*</span></label>
                        <select id="user_id" name="user_id" required
                                data-select-search="Cari nama dosen…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('user_id')])>
                            <option value="">Pilih dosen</option>
                            @foreach ($dosenList as $dosen)
                                <option value="{{ $dosen->id }}" @selected((string) old('user_id', $editSenat?->user_id) === (string) $dosen->id)>
                                    {{ $dosen->name }} — {{ $dosen->nidn }}
                                </option>
                            @endforeach
                        </select>
                        @error('user_id') <p class="form-error">{{ $message }}</p> @enderror

                        @if ($dosenList->isEmpty())
                            <p class="mt-1.5 text-[11px] text-amber-600">Belum ada dosen aktif yang dapat dipilih.</p>
                        @endif
                    </div>

                    <div>
                        <label for="jabatan" class="form-label">Jabatan <span class="text-rose-500">*</span></label>
                        <select id="jabatan" name="jabatan" required
                                data-dependent-select="#jabatan_tambahan"
                                data-sub-options="{{ json_encode($rangkapanMap, JSON_UNESCAPED_SLASHES) }}"
                                data-select-search="Cari jabatan…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan')])>
                            <option value="">Pilih jabatan</option>
                            @foreach ($jabatanList as $nilai => $label)
                                <option value="{{ $nilai }}" @selected(old('jabatan', $editSenat?->jabatan) === $nilai) @disabled($nilai === 'ketua' && ! $ketuaBolehDipilih)>{{ $label }}@if ($nilai === 'ketua' && ! $ketuaBolehDipilih) — sudah terisi @endif</option>
                            @endforeach
                        </select>
                        @error('jabatan') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Satu dosen boleh merangkap jabatan. Ketua hanya boleh satu orang;
                            @if ($ketuaBolehDipilih)
                                jabatan ketua belum terisi.
                            @else
                                ubah atau hapus dulu ketua atas nama {{ $ketua->user?->name }}.
                            @endif
                        </p>
                    </div>

                    {{-- Merangkap jabatan: opsional, pilihan mengikuti jabatan utama. --}}
                    <div data-dependent-field>
                        <label for="jabatan_tambahan" class="form-label">
                            Merangkap Jabatan <span class="font-normal text-slate-400">(opsional)</span>
                        </label>
                        <select id="jabatan_tambahan" name="jabatan_tambahan"
                                data-selected="{{ old('jabatan_tambahan', $editSenat?->jabatan_tambahan) }}"
                                data-placeholder="Tidak merangkap"
                                data-select-search="Cari jabatan rangkapan…"
                                @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('jabatan_tambahan')])>
                            <option value="">Tidak merangkap</option>
                            @foreach ($rangkapanMap[old('jabatan', $editSenat?->jabatan)] ?? $rangkapanList as $nilai => $label)
                                <option value="{{ $nilai }}" @selected((string) old('jabatan_tambahan', $editSenat?->jabatan_tambahan) === (string) $nilai)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jabatan_tambahan') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] text-slate-400">
                            Isi bila dosen merangkap jabatan lain, mis. Ketua merangkap Anggota.
                            Ketua tidak dapat dipilih sebagai jabatan rangkapan.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    @if ($isEdit)
                        <a href="{{ route('instansi.senat.index') }}" class="btn-secondary justify-center sm:w-auto">Batal</a>
                    @endif
                    <button type="submit" class="btn-primary w-auto justify-center px-5">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd" />
                        </svg>
                        {{ $isEdit ? 'Simpan Perubahan' : 'Tambah ke Senat' }}
                    </button>
                </div>
            </section>
        </form>

        {{-- Daftar anggota senat --}}
        <section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-500 ring-1 ring-indigo-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="9" cy="8.5" r="3" />
                                <path d="M3.5 19c0-2.8 2.5-5 5.5-5s5.5 2.2 5.5 5" stroke-linecap="round" />
                                <path d="M16 6.2a3 3 0 0 1 0 5.6M17.5 14.4c1.9.6 3 2.3 3 4.6" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Anggota Senat</h3>
                                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600">{{ $items->count() }} orang</span>
                            </div>
                            <p class="mt-0.5 text-xs text-slate-400">Ketua &amp; sekretaris ditampilkan lebih dulu, lalu anggota dan anggota/ex officio sesuai abjad. Dosen yang merangkap jabatan tetap tampil satu baris.</p>
                        </div>
                    </div>

                    <div class="relative w-full lg:w-72">
                        <label for="senat-search" class="sr-only">Cari anggota senat</label>
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 3.4 9.83l3.13 3.14a.75.75 0 1 0 1.06-1.06l-3.14-3.14A5.5 5.5 0 0 0 9 3.5ZM5 9a4 4 0 1 1 8 0 4 4 0 0 1-8 0Z" clip-rule="evenodd" />
                        </svg>
                        <input type="search" id="senat-search" data-table-search placeholder="Cari nama atau jabatan…"
                               class="form-input pl-9">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table data-datatable data-row-numbers data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort w-16">No</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Jabatan</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3 font-mono text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $item->user?->name ?? '—' }}</p>
                                        <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $item->user?->nidn }}</p>
                                    </td>
                                    <td class="px-5 py-3">
                                        <span @class([
                                            'badge-status',
                                            'bg-teal-50 text-teal-700 ring-teal-200' => $item->jabatan === 'ketua',
                                            'bg-indigo-50 text-indigo-700 ring-indigo-200' => $item->jabatan === 'anggota_ex_officio',
                                            'bg-slate-50 text-slate-600 ring-slate-200' => ! in_array($item->jabatan, ['ketua', 'anggota_ex_officio'], true),
                                        ])>{{ $item->jabatanLengkap() }}</span>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('instansi.senat.index', ['edit' => $item->id]) }}#form-senat"
                                               title="Edit" aria-label="Edit"
                                               class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                    <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('instansi.senat.destroy', $item) }}"
                                                  data-confirm="Hapus {{ $item->user?->name }} dari senat? Tindakan ini tidak dapat dibatalkan.">
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
                                    <td colspan="4" class="px-5 py-16 text-center">
                                        <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <circle cx="9" cy="8.5" r="3" />
                                                <path d="M3.5 19c0-2.8 2.5-5 5.5-5s5.5 2.2 5.5 5" stroke-linecap="round" />
                                                <path d="M16 6.2a3 3 0 0 1 0 5.6M17.5 14.4c1.9.6 3 2.3 3 4.6" stroke-linecap="round" />
                                            </svg>
                                        </span>
                                        <p class="mt-3 text-sm font-bold text-slate-700">Belum ada anggota senat</p>
                                        <p class="mt-1 text-xs text-slate-400">Tambahkan dosen melalui formulir di atas.</p>
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
                    <p>Dosen diambil dari data dosen aktif.</p>
                </div>
            </div>
        </section>

        {{-- Logo senat: identitas visual senat yang diunggah administrator. --}}
        <section class="section-card mt-5" data-image-field>
            <div class="section-heading">
                <span class="section-number">02</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Logo Senat</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Unggah logo resmi senat dalam format gambar.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('instansi.senat.logo') }}" enctype="multipart/form-data">
                @csrf

                <div class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-start sm:px-6">
                    <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        @if ($instansi->logo_senat_base64)
                            <img data-image-preview src="{{ $instansi->logoSenatDataUri() }}" alt="Logo senat"
                                 class="size-full object-contain p-2">
                        @else
                            <img data-image-preview alt="Pratinjau logo senat" class="hidden size-full object-contain p-2">
                            <span data-image-placeholder class="text-slate-300">
                                <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" />
                                    <circle cx="9" cy="10" r="2.2" />
                                    <path d="m5.5 17 4.2-4.2a2 2 0 0 1 2.8 0l5.5 5.2M15 8.5h3" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <label for="logo_senat" class="form-label">Berkas Logo Senat <span class="text-rose-500">*</span></label>
                        <input type="file" id="logo_senat" name="logo_senat" required
                               accept="image/png,image/jpeg,image/webp"
                               data-image-input data-max-kb="500"
                               @class([
                                   'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                   'border-rose-300 bg-rose-50/40' => $errors->has('logo_senat'),
                                   'border-slate-200' => ! $errors->has('logo_senat'),
                               ])>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Format JPG, PNG, atau WEBP. Maksimal 500 KB.
                            @if ($instansi->logo_senat_base64)
                                Unggah berkas baru untuk mengganti logo senat saat ini.
                            @endif
                        </p>
                        <p data-image-error class="form-error hidden" role="alert"></p>
                        @error('logo_senat') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="flex items-center gap-1.5 text-[11px] text-slate-400">
                        <svg class="size-3.5 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                        </svg>
                        Logo senat hanya dapat diunggah oleh administrator.
                    </p>
                    <button type="submit" class="btn-primary justify-center sm:w-auto">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 15.5V4m0 0 4 4m-4-4-4 4" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M4.5 15v3A1.5 1.5 0 0 0 6 19.5h12a1.5 1.5 0 0 0 1.5-1.5v-3" stroke-linecap="round" />
                        </svg>
                        Simpan Logo Senat
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
