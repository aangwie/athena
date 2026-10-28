@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    @php
        $hour = (int) now()->format('H');
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
        $firstName = auth()->user()->firstName();
    @endphp

    {{-- Sambutan --}}
    <section class="relative mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-950 p-6 shadow-card sm:p-7">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-20 -right-16 size-64 rounded-full bg-teal-400/20 blur-3xl"></div>
            <div class="absolute -bottom-24 left-1/3 size-56 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;"></div>
        </div>

        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-[10px] font-bold tracking-wide text-teal-100 uppercase backdrop-blur">
                        <span class="size-1.5 rounded-full bg-emerald-300"></span>
                        {{ auth()->user()->isAdmin() ? 'Ruang Administrasi' : 'Ruang Dosen' }}
                    </span>
                    <span class="text-[11px] font-medium text-teal-200/70">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
                <h2 class="text-xl font-extrabold tracking-tight text-white sm:text-2xl">
                    {{ $greeting }}, {{ $firstName }}!
                </h2>
                <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-teal-50/70">
                    @if (auth()->user()->isAdmin())
                        Pantau dan verifikasi pengajuan jabatan akademik dosen dalam satu tempat.
                    @else
                        Pantau status pengajuan jabatan akademik Anda dan lengkapi berkas dengan mudah.
                    @endif
                </p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <div class="hidden rounded-xl border border-white/10 bg-white/[0.07] px-4 py-3 backdrop-blur sm:block">
                    <p class="text-[10px] font-semibold tracking-wide text-teal-200/70 uppercase">Periode aktif</p>
                    <p class="mt-0.5 text-sm font-bold text-white">{{ now()->format('Y') }}</p>
                </div>
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-bold text-teal-800 opacity-90 shadow-lg shadow-teal-950/20" title="Modul pengajuan segera hadir">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                    </svg>
                    Pengajuan Baru
                    <span class="rounded-md bg-teal-50 px-1.5 py-0.5 text-[9px] font-extrabold tracking-wide text-teal-600 uppercase">Soon</span>
                </span>
            </div>
        </div>
    </section>

    {{-- Statistik --}}
    <section class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" aria-label="Ringkasan pengajuan">
        @php
            $cards = [
                [
                    'label' => 'Total Pengajuan',
                    'value' => $statistics['total'],
                    'description' => 'Seluruh pengajuan tercatat',
                    'tag' => 'Semua',
                    'icon' => 'document',
                    'iconClass' => 'bg-teal-50 text-teal-600',
                    'accent' => 'bg-teal-500',
                ],
                [
                    'label' => 'Menunggu Verifikasi',
                    'value' => $statistics['menunggu'],
                    'description' => 'Sedang diproses admin',
                    'tag' => 'Proses',
                    'icon' => 'clock',
                    'iconClass' => 'bg-amber-50 text-amber-600',
                    'accent' => 'bg-amber-500',
                ],
                [
                    'label' => 'Disetujui',
                    'value' => $statistics['disetujui'],
                    'description' => 'Pengajuan telah disetujui',
                    'tag' => 'Final',
                    'icon' => 'check',
                    'iconClass' => 'bg-emerald-50 text-emerald-600',
                    'accent' => 'bg-emerald-500',
                ],
                [
                    'label' => 'Perlu Perbaikan',
                    'value' => $statistics['perbaikan'],
                    'description' => 'Pengajuan ditolak / revisi',
                    'tag' => 'Revisi',
                    'icon' => 'alert',
                    'iconClass' => 'bg-rose-50 text-rose-600',
                    'accent' => 'bg-rose-500',
                ],
            ];
        @endphp

        @foreach ($cards as $card)
            <article class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-card sm:p-5">
                <span class="absolute inset-x-0 top-0 h-0.5 {{ $card['accent'] }} opacity-0 transition group-hover:opacity-100"></span>
                <div class="flex items-start justify-between gap-2">
                    <div class="stat-icon size-10 sm:size-11 {{ $card['iconClass'] }}">
                        @if ($card['icon'] === 'document')
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M6 3.5h8l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5Z" stroke-linejoin="round" />
                                <path d="M14 3.5v4.5h4M9 13h6M9 16.5h4" stroke-linecap="round" />
                            </svg>
                        @elseif ($card['icon'] === 'clock')
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M12 7.5V12l3 1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @elseif ($card['icon'] === 'check')
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 3.5 5.5 6v5.2c0 4.2 2.8 7.9 6.5 9.3 3.7-1.4 6.5-5.1 6.5-9.3V6L12 3.5Z" stroke-linejoin="round" />
                                <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @else
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M12 7.5V13M12 16.5h.01" stroke-linecap="round" />
                            </svg>
                        @endif
                    </div>
                    <span class="rounded-full bg-slate-50 px-2 py-1 text-[10px] font-bold text-slate-400 ring-1 ring-slate-100 ring-inset">{{ $card['tag'] }}</span>
                </div>
                <p class="mt-3 text-2xl font-extrabold tracking-tight text-slate-900 sm:mt-4 sm:text-3xl">{{ number_format($card['value'], 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-slate-700 sm:text-[13px]">{{ $card['label'] }}</p>
                <p class="mt-1 hidden text-[11px] text-slate-400 sm:block">{{ $card['description'] }}</p>
            </article>
        @endforeach
    </section>

    {{-- Tabel riwayat --}}
    <section id="riwayat" class="scroll-mt-24" data-table-card>
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-extrabold tracking-tight text-slate-900">Riwayat Pengajuan</h2>
                        <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                            {{ $pengajuan->count() }} data
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        @if (auth()->user()->isAdmin())
                            Daftar pengajuan jabatan akademik dari seluruh dosen.
                        @else
                            Daftar pengajuan jabatan akademik yang Anda ajukan.
                        @endif
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <label class="relative block">
                        <span class="sr-only">Cari pengajuan</span>
                        <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10.5" cy="10.5" r="6.5" />
                                <path d="m16 16 4 4" stroke-linecap="round" />
                            </svg>
                        </span>
                        <input type="search" data-table-search placeholder="Cari nomor, jabatan, prodi…"
                               class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-64">
                        <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                            </svg>
                        </button>
                    </label>

                    <div class="relative">
                        <label for="statusFilter" class="sr-only">Filter status</label>
                        <select id="statusFilter" data-table-filter data-column="{{ auth()->user()->isAdmin() ? 6 : 5 }}"
                                class="h-10 w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-3 text-xs font-medium text-slate-600 transition hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-40">
                            <option value="">Semua status</option>
                            <option value="Draft">Draft</option>
                            <option value="Diajukan">Diajukan</option>
                            <option value="Diverifikasi">Diverifikasi</option>
                            <option value="Disetujui">Disetujui</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                        <span class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-slate-400">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto px-2 pb-2 sm:px-4">
                <table id="riwayatTable" data-datatable data-page-length="10" class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col">No. Pengajuan</th>
                            @if (auth()->user()->isAdmin())
                                <th scope="col">Dosen</th>
                            @endif
                            <th scope="col">Jabatan</th>
                            <th scope="col">Program Studi</th>
                            <th scope="col">Periode</th>
                            <th scope="col">Tanggal</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuan as $item)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="font-mono text-xs font-bold text-teal-700">{{ $item->nomor_pengajuan }}</span>
                                    <span class="mt-0.5 block text-[10px] text-slate-400">Diperbarui {{ $item->updated_at->diffForHumans() }}</span>
                                </td>
                                @if (auth()->user()->isAdmin())
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-bold text-slate-600">
                                                {{ $item->user->initials() }}
                                            </span>
                                            <div class="min-w-0">
                                                <p class="truncate text-xs font-bold text-slate-700">{{ $item->user->name }}</p>
                                                <p class="font-mono text-[10px] text-slate-400">{{ $item->user->nidn }}</p>
                                            </div>
                                        </div>
                                    </td>
                                @endif
                                <td>
                                    <p class="text-xs font-bold whitespace-nowrap text-slate-700">{{ $item->jabatan_tujuan }}</p>
                                    <p class="text-[10px] whitespace-nowrap text-slate-400">dari {{ $item->jabatan_asal ?? '—' }}</p>
                                </td>
                                <td>
                                    <span class="text-xs whitespace-nowrap text-slate-600">{{ $item->program_studi }}</span>
                                </td>
                                <td>
                                    <span class="text-xs whitespace-nowrap text-slate-600">{{ $item->periode }}</span>
                                </td>
                                <td data-order="{{ $item->tanggal_pengajuan->timestamp }}" class="whitespace-nowrap">
                                    <span class="text-xs font-medium text-slate-600">{{ $item->tanggal_pengajuan->translatedFormat('d M Y') }}</span>
                                </td>
                                <td>
                                    <span class="badge-status {{ $item->statusClasses() }}">
                                        <span class="size-1.5 rounded-full {{ $item->statusDotClasses() }}"></span>
                                        {{ $item->statusLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->isAdmin() ? 7 : 6 }}" class="px-5 py-16 text-center">
                                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path d="M6 3.5h8l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 20V5A1.5 1.5 0 0 1 7.5 3.5Z" stroke-linejoin="round" />
                                            <path d="M14 3.5v4.5h4M9 13h6M9 16.5h4" stroke-linecap="round" />
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada pengajuan</p>
                                    <p class="mt-1 text-xs text-slate-400">Pengajuan jabatan akademik akan tampil di sini.</p>
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
                    Klik judul kolom untuk mengurutkan data.
                </p>
                <p>Data demonstrasi · Athena v0.1.0</p>
            </div>
        </div>
    </section>
@endsection
