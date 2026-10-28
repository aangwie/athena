<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f766e">
    <meta name="description" content="Athena — Sistem Pengajuan Jabatan Akademik Dosen.">

    <script>
        // Terapkan preferensi sidebar sebelum halaman dirender agar tidak berkedip.
        try {
            if (localStorage.getItem('athena-sidebar') === 'collapsed') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // localStorage tidak tersedia, abaikan.
        }
    </script>

    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'Athena') }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 font-sans">
    <div class="min-h-screen lg:flex">
        {{-- Overlay sidebar mobile --}}
        <div data-sidebar-overlay class="fixed inset-0 z-40 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

        {{-- Sidebar --}}
        <aside id="app-sidebar" data-sidebar
               class="sidebar-panel fixed inset-y-0 left-0 z-50 flex w-[17.5rem] -translate-x-full flex-col bg-gradient-to-b from-teal-950 via-teal-950 to-emerald-950 shadow-2xl transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none">
            <div class="sidebar-header flex items-center justify-between gap-3 px-5 pt-6 pb-5">
                <a href="{{ route('dashboard') }}" class="sidebar-brand flex items-center gap-3">
                    <x-brand-mark class="size-10" />
                    <span class="sidebar-label">
                        <span class="block text-lg leading-tight font-extrabold tracking-tight text-white">Athena</span>
                        <span class="block text-[9.5px] font-semibold tracking-[0.16em] text-teal-300/80 uppercase">Jabatan Akademik</span>
                    </span>
                </a>
                <button type="button" data-sidebar-toggle
                        class="flex size-8 items-center justify-center rounded-lg text-teal-200/70 transition hover:bg-white/10 hover:text-white lg:hidden"
                        aria-label="Tutup menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" />
                    </svg>
                </button>
                <button type="button" data-sidebar-collapse
                        class="hidden size-8 shrink-0 items-center justify-center rounded-lg text-teal-200/70 transition hover:bg-white/10 hover:text-white lg:flex"
                        aria-controls="app-sidebar" aria-expanded="true"
                        aria-label="Minimalkan sidebar" title="Minimalkan sidebar">
                    <svg data-collapse-icon-open class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m14.5 6-5.5 6 5.5 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg data-collapse-icon-closed class="hidden size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="m9.5 6 5.5 6-5.5 6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav flex-1 space-y-6 overflow-y-auto px-4 pt-1 pb-5">
                <div>
                    <p class="sidebar-section-title sidebar-collapse-hide mb-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase">Menu Utama</p>
                    <div class="space-y-1">
                        <a href="{{ route('dashboard') }}" title="Dashboard" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('dashboard')])>
                            <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3.5" y="3.5" width="7" height="7" rx="2" />
                                <rect x="13.5" y="3.5" width="7" height="7" rx="2" />
                                <rect x="3.5" y="13.5" width="7" height="7" rx="2" />
                                <rect x="13.5" y="13.5" width="7" height="7" rx="2" />
                            </svg>
                            <span class="sidebar-label">Dashboard</span>
                        </a>
                        <a href="{{ route('dashboard') }}#riwayat" class="sidebar-link" title="Riwayat Pengajuan">
                            <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v13A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                                <rect x="9" y="3" width="6" height="4" rx="1.5" />
                                <path d="M9 12h6M9 16h4" stroke-linecap="round" />
                            </svg>
                            <span class="sidebar-label">Riwayat Pengajuan</span>
                        </a>
                        @if (auth()->user()->isDosen())
                            <a href="{{ route('profil.show') }}" title="Portofolio Saya" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('profil.show')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Portofolio Saya</span>
                            </a>
                        @endif
                    </div>
                </div>

                @if (auth()->user()->isDosen())
                    <div>
                        <p class="sidebar-section-title sidebar-collapse-hide mb-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase">Profil Dosen</p>
                        <div class="space-y-1">
                            <a href="{{ route('profil.data-pribadi.edit') }}" title="Data Pribadi" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('profil.data-pribadi.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" />
                                    <circle cx="9" cy="10" r="2.2" />
                                    <path d="M5.8 16.5a3.7 3.7 0 0 1 6.4 0M14.5 9h3.2M14.5 12.5h3.2" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Data Pribadi</span>
                            </a>
                            <a href="{{ route('profil.pendidikan.index') }}" title="Riwayat Pendidikan" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('profil.pendidikan.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                                    <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Riwayat Pendidikan</span>
                            </a>
                        </div>
                    </div>
                @endif
                @if (auth()->user()->isAdmin())
                    <div>
                        <p class="sidebar-section-title sidebar-collapse-hide mb-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase">Administrasi</p>
                        <div class="space-y-1">
                            <span class="sidebar-link cursor-not-allowed opacity-45" title="Verifikasi Berkas (segera hadir)">
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M9 12.5 11 14.5 15.5 10" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M12 3.5 5.5 6v5.2c0 4.2 2.8 7.9 6.5 9.3 3.7-1.4 6.5-5.1 6.5-9.3V6L12 3.5Z" stroke-linejoin="round" />
                                </svg>
                                <span class="sidebar-label">Verifikasi Berkas</span>
                                <span class="sidebar-badge sidebar-collapse-hide ml-auto rounded-md bg-white/10 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-teal-200/70 uppercase">Soon</span>
                            </span>
                            <span class="sidebar-link cursor-not-allowed opacity-45" title="Data Dosen (segera hadir)">
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="9" cy="8" r="3" />
                                    <path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.3a3 3 0 0 1 0 5.4M18.5 19a5.5 5.5 0 0 0-2.6-4.6" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Data Dosen</span>
                                <span class="sidebar-badge sidebar-collapse-hide ml-auto rounded-md bg-white/10 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-teal-200/70 uppercase">Soon</span>
                            </span>
                        </div>
                    </div>

                    <details class="group" @if (request()->routeIs('instansi.*')) open @endif>
                        <summary class="sidebar-collapse-hide mb-2 flex cursor-pointer list-none items-center gap-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase transition select-none hover:text-teal-200/80 [&::-webkit-details-marker]:hidden">
                            <span class="flex-1">Instansi</span>
                            <svg class="size-3.5 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.2 4.2a.75.75 0 0 1 1.06 0l5.3 5.3a.75.75 0 0 1 0 1.06l-5.3 5.3a.75.75 0 1 1-1.06-1.06L11.7 10 7.2 5.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </summary>
                        <div class="space-y-1">
                            <a href="{{ route('instansi.profil.edit') }}" title="Data Instansi" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('instansi.profil.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M4.5 20.5V5.5A1.5 1.5 0 0 1 6 4h7.5a1.5 1.5 0 0 1 1.5 1.5v15" stroke-linecap="round" />
                                    <path d="M15 10h3.5A1.5 1.5 0 0 1 20 11.5v9M3 20.5h18" stroke-linecap="round" />
                                    <path d="M8 8h3.5M8 11.5h3.5M8 15h3.5" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Data Instansi</span>
                            </a>
                            <a href="{{ route('instansi.prodi.index') }}" title="Prodi" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('instansi.prodi.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                                    <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Prodi</span>
                            </a>
                            <a href="{{ route('instansi.struktur.index') }}" title="Struktur Instansi" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('instansi.struktur.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                                    <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                                    <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                                    <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="sidebar-label">Struktur Instansi</span>
                            </a>
                        </div>
                    </details>

                    <details class="group" @if (request()->routeIs('kepegawaian.*')) open @endif>
                        <summary class="sidebar-collapse-hide mb-2 flex cursor-pointer list-none items-center gap-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase transition select-none hover:text-teal-200/80 [&::-webkit-details-marker]:hidden">
                            <span class="flex-1">Kepegawaian</span>
                            <svg class="size-3.5 transition-transform duration-200 group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.2 4.2a.75.75 0 0 1 1.06 0l5.3 5.3a.75.75 0 0 1 0 1.06l-5.3 5.3a.75.75 0 1 1-1.06-1.06L11.7 10 7.2 5.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </summary>
                        <div class="space-y-1">
                            <a href="{{ route('kepegawaian.jabatan-fungsional.index') }}" title="Jabatan Fungsional" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('kepegawaian.jabatan-fungsional.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 9.4 12 5l9 4.4-9 4.4L3 9.4Z" stroke-linejoin="round" />
                                    <path d="M6.4 11.3v4.2c0 .5.3 1 .8 1.2 1.5.7 3 1 4.8 1s3.3-.3 4.8-1c.5-.2.8-.7.8-1.2v-4.2" stroke-linecap="round" />
                                </svg>
                                <span class="sidebar-label">Jabatan Fungsional</span>
                            </a>
                            <a href="{{ route('kepegawaian.jabatan-struktural.index') }}" title="Jabatan Struktural" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('kepegawaian.jabatan-struktural.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <rect x="9" y="3.5" width="6" height="4.5" rx="1.5" />
                                    <rect x="3.5" y="16" width="6" height="4.5" rx="1.5" />
                                    <rect x="14.5" y="16" width="6" height="4.5" rx="1.5" />
                                    <path d="M12 8v3.5M6.5 16v-4.5h11V16" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="sidebar-label">Jabatan Struktural</span>
                            </a>
                            <a href="{{ route('kepegawaian.kepangkatan.index') }}" title="Kepangkatan" @class(['sidebar-link', 'sidebar-link-active' => request()->routeIs('kepegawaian.kepangkatan.*')])>
                                <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="m5 15 7-4 7 4-7 4-7-4Z" stroke-linejoin="round" />
                                    <path d="m5 10 7-4 7 4M5 5l7-4 7 4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="sidebar-label">Kepangkatan</span>
                            </a>
                        </div>
                    </details>
                @endif

                <div>
                    <p class="sidebar-section-title sidebar-collapse-hide mb-2 px-3 text-[10px] font-bold tracking-[0.14em] text-teal-300/50 uppercase">Bantuan</p>
                    <div class="space-y-1">
                        <span class="sidebar-link cursor-not-allowed opacity-45" title="Panduan Pengajuan (segera hadir)">
                            <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.5" />
                                <path d="M9.8 9.3A2.3 2.3 0 0 1 12 7.5c1.3 0 2.3.9 2.3 2.1 0 1.8-2.3 1.8-2.3 3.6" stroke-linecap="round" />
                                <circle cx="12" cy="16.5" r=".9" fill="currentColor" stroke="none" />
                            </svg>
                            <span class="sidebar-label">Panduan Pengajuan</span>
                            <span class="sidebar-badge sidebar-collapse-hide ml-auto rounded-md bg-white/10 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-teal-200/70 uppercase">Soon</span>
                        </span>
                    </div>
                </div>
            </nav>

            <div class="sidebar-help sidebar-collapse-hide mx-4 mb-4 rounded-2xl border border-teal-400/15 bg-gradient-to-br from-teal-500/15 to-emerald-400/5 p-4">
                <div class="mb-2 flex size-8 items-center justify-center rounded-lg bg-teal-400/15 text-teal-200">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 3v2M12 19v2M4.2 7.2l1.4 1.4M18.4 15.4l1.4 1.4M3 12h2M19 12h2M4.2 16.8l1.4-1.4M18.4 8.6l1.4-1.4" stroke-linecap="round" />
                        <circle cx="12" cy="12" r="3.5" />
                    </svg>
                </div>
                <p class="text-xs leading-relaxed font-medium text-teal-50/80">
                    Butuh bantuan teknis? Hubungi admin kepegawaian kampus.
                </p>
                <a href="mailto:admin@athena.test" class="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-teal-300 transition hover:text-teal-200">
                    Hubungi Admin
                    <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </aside>

        {{-- Area konten --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/85 backdrop-blur-lg">
                <div class="flex h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" data-sidebar-toggle
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:border-teal-300 hover:text-teal-700 lg:hidden"
                                aria-label="Buka menu">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                                <span>Athena</span>
                                <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M7.2 4.2a.75.75 0 0 1 1.06 0l5.3 5.3a.75.75 0 0 1 0 1.06l-5.3 5.3a.75.75 0 1 1-1.06-1.06L11.7 10 7.2 5.26a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                                <span class="truncate text-slate-600">@yield('breadcrumb', 'Dashboard')</span>
                            </div>
                            <h1 class="truncate text-[15px] font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <span class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-500 md:inline-flex">
                            <svg class="size-4 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3.5" y="5" width="17" height="15" rx="2.5" />
                                <path d="M8 3v4M16 3v4M3.5 10h17" stroke-linecap="round" />
                            </svg>
                            {{ now()->translatedFormat('d F Y') }}
                        </span>

                        <button type="button" class="relative flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-teal-300 hover:text-teal-700" aria-label="Notifikasi">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7Z" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10 19a2.2 2.2 0 0 0 4 0" stroke-linecap="round" />
                            </svg>
                            <span class="absolute top-2.5 right-2.5 size-1.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
                        </button>

                        <div class="relative">
                            <button type="button" data-user-menu-button aria-expanded="false" aria-haspopup="true"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white py-1.5 pr-2 pl-1.5 transition hover:border-teal-300 sm:pr-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-teal-600 text-[11px] font-bold text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                                <span class="hidden text-left sm:block">
                                    <span class="block max-w-36 truncate text-xs leading-tight font-bold text-slate-800">{{ auth()->user()->name }}</span>
                                    <span class="block text-[10px] leading-tight text-slate-400">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Dosen' }}</span>
                                </span>
                                <svg class="hidden size-3.5 text-slate-400 sm:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div data-user-menu class="absolute top-[calc(100%+0.5rem)] right-0 hidden w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
                                <div class="border-b border-slate-100 bg-slate-50/60 px-4 py-3">
                                    <p class="truncate text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500">NIDN {{ auth()->user()->nidn }}</p>
                                </div>
                                <div class="p-1.5">
                                    <span class="flex cursor-not-allowed items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-400">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <circle cx="12" cy="8" r="3.5" />
                                            <path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />
                                        </svg>
                                        Pengaturan profil
                                        <span class="ml-auto text-[9px] font-bold uppercase">Soon</span>
                                    </span>
                                </div>
                                <div class="border-t border-slate-100 p-1.5">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M15 12H4.5M8 8.5 4.5 12 8 15.5" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M11 7.5V6A1.5 1.5 0 0 1 12.5 4.5h6A1.5 1.5 0 0 1 20 6v12a1.5 1.5 0 0 1-1.5 1.5h-6A1.5 1.5 0 0 1 11 18v-1.5" stroke-linecap="round" />
                                            </svg>
                                            Keluar dari sistem
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                @if (session('status'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3.5 text-sm text-teal-800 shadow-sm" role="status">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                        </svg>
                        <p>{{ session('status') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700 shadow-sm" role="alert">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="border-t border-slate-200/80 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-2 text-[11px] text-slate-400 sm:flex-row">
                    <p>&copy; {{ date('Y') }} Athena · Sistem Pengajuan Jabatan Akademik Dosen</p>
                    <p class="flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-teal-500"></span>
                        Versi 0.1.0 · Lingkungan {{ app()->environment() }}
                    </p>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
