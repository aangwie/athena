@extends('layouts.app')

@section('title', 'Data Dosen')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Data Dosen')

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Administrasi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Data Dosen</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">Kelola data dosen: nama, NIDN, password, pangkat, dan role akses.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('data-dosen.import.create') }}" class="btn-secondary w-auto px-5 whitespace-nowrap">Import Excel</a>
                <a href="{{ route('data-dosen.create') }}" class="btn-primary w-auto px-5 whitespace-nowrap">Tambah Data</a>
            </div>
        </div>

        <section data-table-card>
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600 ring-1 ring-teal-100">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="9" cy="8" r="3.25" />
                                <path d="M3.5 19.25a5.5 5.5 0 0 1 11 0" stroke-linecap="round" />
                                <path d="M16 5.4a3.25 3.25 0 0 1 0 6.2M17.6 14.2a5.5 5.5 0 0 1 2.9 5.05" stroke-linecap="round" />
                            </svg>
                        </span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Data Dosen</h3>
                                <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">{{ $items->count() }} data</span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Nama, NIDN, pangkat, dan role akses tiap dosen.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <button type="submit" form="bulk-form" data-bulk-submit disabled
                                class="btn-secondary w-auto justify-center px-4 whitespace-nowrap text-rose-600 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-slate-200 disabled:hover:bg-white disabled:hover:text-rose-600">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                            </svg>
                            <span data-bulk-label>Hapus terpilih</span>
                        </button>

                        <label class="relative block">
                            <span class="sr-only">Cari data dosen</span>
                            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="10.5" cy="10.5" r="6.5" />
                                    <path d="m16 16 4 4" stroke-linecap="round" />
                                </svg>
                            </span>
                            <input type="search" data-table-search placeholder="Cari nama atau NIDN…"
                                   class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-60">
                            <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                    <table data-datatable data-page-length="10" class="w-full text-left text-sm">
                        <thead>
                            <tr>
                                <th scope="col" class="no-sort all w-12">
                                    <input type="checkbox" data-bulk-all aria-label="Pilih semua data dosen"
                                           class="size-4 cursor-pointer accent-teal-600">
                                </th>
                                <th scope="col">Nama Dosen</th>
                                <th scope="col">NIDN</th>
                                <th scope="col">Pangkat</th>
                                <th scope="col">Role</th>
                                <th scope="col" class="no-sort text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr class="border-b border-slate-100">
                                    <td class="px-5 py-3">
                                        <input type="checkbox" form="bulk-form" name="ids[]" value="{{ $item->id }}" data-bulk-item
                                               @disabled($item->id === auth()->id())
                                               aria-label="Pilih {{ $item->name }}"
                                               title="{{ $item->id === auth()->id() ? 'Akun sendiri tidak dapat dihapus' : 'Pilih '.$item->name }}"
                                               class="size-4 cursor-pointer accent-teal-600 disabled:cursor-not-allowed disabled:opacity-40">
                                    </td>
                                    <td class="px-5 py-3 font-semibold text-slate-800">{{ $item->name }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $item->nidn }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $item->pangkat }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach ($item->roleList() as $role)
                                                <span @class([
                                                    'badge-status',
                                                    'bg-teal-50 text-teal-700 ring-teal-200' => $role === 'dosen',
                                                    'bg-amber-50 text-amber-700 ring-amber-200' => $role === 'tim_pak',
                                                    'bg-indigo-50 text-indigo-700 ring-indigo-200' => $role === 'admin',
                                                ])>{{ \App\Models\User::ROLES[$role] }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('data-dosen.edit', $item->id) }}"
                                               title="Edit" aria-label="Edit data {{ $item->name }}"
                                               class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-teal-50 hover:text-teal-700">
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                                    <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                                </svg>
                                            </a>
                                            @if ($item->id !== auth()->id())
                                                <form method="POST" action="{{ route('data-dosen.destroy', $item->id) }}"
                                                      data-confirm-title="Hapus data dosen dan semua datanya?"
                                                      data-confirm="Data dosen {{ $item->name }} (NIDN {{ $item->nidn }}) akan dihapus permanen beserta seluruh data terkait: profil dan biodata, riwayat pendidikan dengan berkas ijazah &amp; transkrip, riwayat kepangkatan, jabatan struktural, angka kredit, kegiatan dan pengajuan DUPAK, karya tulis, berkas syarat khusus (BKD/IKD), surat pernyataan pimpinan, pakta integritas, BAP senat beserta BAKI, keanggotaan senat/tim komite/struktur instansi, akun login, serta semua berkas yang pernah diunggah. Tindakan ini tidak dapat dibatalkan.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Hapus" aria-label="Hapus data {{ $item->name }}"
                                                            class="inline-flex size-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600">
                                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path d="M4.5 7h15M9.5 7V5.5A1.5 1.5 0 0 1 11 4h2a1.5 1.5 0 0 1 1.5 1.5V7M6.5 7l.8 12A1.5 1.5 0 0 0 8.8 20.5h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M10 11v5.5M14 11v5.5" stroke-linecap="round" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Belum ada data dosen.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <form id="bulk-form" method="POST" action="{{ route('data-dosen.bulk-destroy') }}" data-bulk-form class="hidden"
                      data-confirm-title="Hapus data dosen terpilih?"
                      data-confirm="Semua data dosen yang dipilih akan dihapus permanen beserta seluruh data terkait (profil, riwayat pendidikan &amp; berkas, DUPAK, karya tulis, syarat khusus, surat, keanggotaan, akun login) dan semua berkas unggahannya. Tindakan ini tidak dapat dibatalkan.">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-bulk-form]');
            const semua = document.querySelector('[data-bulk-all]');
            const tombol = document.querySelector('[data-bulk-submit]');

            if (!form || !semua || !tombol) return;

            const label = tombol.querySelector('[data-bulk-label]');
            const kotak = () => Array.from(document.querySelectorAll('[data-bulk-item]'));

            const segarkan = () => {
                const terpilih = kotak().filter((el) => el.checked).length;
                const tampil = kotak().filter((el) => el.offsetParent !== null && !el.disabled);

                tombol.disabled = terpilih === 0;

                if (label) {
                    label.textContent = terpilih ? `Hapus (${terpilih})` : 'Hapus terpilih';
                }

                semua.checked = tampil.length > 0 && tampil.every((el) => el.checked);
            };

            document.addEventListener('change', (event) => {
                if (event.target === semua) {
                    kotak().forEach((el) => {
                        if (!el.disabled && el.offsetParent !== null) {
                            el.checked = semua.checked;
                        }
                    });
                }

                if (event.target === semua || event.target.matches('[data-bulk-item]')) {
                    segarkan();
                }
            });

            // DataTables mengganti baris per halaman tanpa memicu event change.
            document.addEventListener('DOMContentLoaded', () => {
                window.jQuery?.(document).on('draw.dt', segarkan);
                segarkan();
            });
        })();
    </script>
@endpush