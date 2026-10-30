@extends('layouts.app')

@section('title', 'Verifikasi Berkas')
@section('breadcrumb', 'Verifikasi Berkas')
@section('page-title', 'Verifikasi Berkas')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Administrasi
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Verifikasi Berkas Syarat Khusus</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Berkas BKD dan IKD yang diajukan dosen tanpa jabatan akademik. Keputusan langsung tampil pada tabel berkas dosen.
            </p>
        </div>

        <section class="section-card overflow-hidden" data-table-card>
            <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 class="text-sm font-extrabold tracking-tight text-slate-900">Berkas Masuk</h3>
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500">Satu baris per file; keputusan pada satu baris hanya berlaku untuk berkas tersebut.</p>
                </div>

                <label class="relative block sm:w-64">
                    <span class="sr-only">Cari berkas</span>
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="10.5" cy="10.5" r="6.5" />
                            <path d="m16 16 4 4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <input type="search" data-table-search placeholder="Cari dosen, periode…"
                           class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none">
                    <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                        </svg>
                    </button>
                </label>
            </div>

            <div class="overflow-x-auto">
                <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">Dosen</th>
                            <th scope="col">Periode</th>
                            <th scope="col">File</th>
                            <th scope="col">Status</th>
                            <th scope="col">Catatan</th>
                            <th scope="col" class="no-sort text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($daftar as $item)
                            @foreach (\App\Http\Controllers\PengajuanDosenController::SYARAT_KHUSUS as $jenis => $info)
                            @php($path = $item->{$jenis.'_path'})
                            @continue (! $path)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-[10px] font-extrabold text-teal-700 ring-1 ring-teal-100">
                                            {{ $item->user->initials() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-xs font-bold text-slate-700">{{ $item->user->name }}</p>
                                            <p class="truncate text-[10px] text-slate-400">
                                                {{ $item->user->nidn ?? '—' }} · {{ $item->user->program_studi ?? '—' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap">
                                    <span class="text-xs font-medium text-slate-600">{{ $item->periodeLabel() }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('verifikasi-berkas.berkas', ['syaratKhusus' => $item, 'jenis' => $jenis]) }}"
                                       target="_blank" rel="noopener"
                                       class="text-[11px] font-bold text-teal-700 underline decoration-teal-300 underline-offset-2 hover:text-teal-800">
                                        {{ strtoupper($jenis) }}
                                    </a>
                                </td>
                                <td>
                                    <span class="badge-status {{ $item->statusClasses($jenis) }}">{{ $item->statusLabel($jenis) }}</span>
                                </td>
                                <td class="max-w-xs">
                                    <span class="text-xs leading-relaxed text-slate-600">{{ $item->catatanJenis($jenis) ?: '—' }}</span>
                                </td>
                                <td class="text-right">
                                    @if ($item->statusJenis($jenis) === 'diajukan')
                                        {{-- Keputusan hanya berlaku untuk berkas pada baris ini. --}}
                                        @php($kunci = $item->id.'-'.$jenis)
                                        <details class="group text-left">
                                            <summary class="btn-small cursor-pointer list-none [&::-webkit-details-marker]:hidden">Verifikasi</summary>
                                            <form method="POST" action="{{ route('verifikasi-berkas.update', ['syaratKhusus' => $item, 'jenis' => $jenis]) }}"
                                                  class="mt-3 w-64 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                                                @csrf
                                                @method('PUT')

                                                <label for="keputusan-{{ $kunci }}" class="form-label">Keputusan</label>
                                                <select id="keputusan-{{ $kunci }}" name="keputusan" class="form-select">
                                                    <option value="diterima">Diterima</option>
                                                    <option value="ditolak">Ditolak</option>
                                                </select>

                                                <label for="catatan-{{ $kunci }}" class="form-label mt-3">Catatan</label>
                                                <textarea id="catatan-{{ $kunci }}" name="catatan" rows="3" class="form-input"
                                                          placeholder="Wajib diisi saat menolak berkas...">{{ old('catatan') }}</textarea>
                                                @error('catatan')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror

                                                <button type="submit" class="btn-primary mt-3 w-auto px-4 py-2 text-xs">Simpan</button>
                                            </form>
                                        </details>
                                    @else
                                        <span class="text-[11px] text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <p class="text-sm font-bold text-slate-700">Belum ada berkas masuk</p>
                                    <p class="mt-1 text-xs text-slate-400">Berkas tampil di sini setelah dosen menekan Ajukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
