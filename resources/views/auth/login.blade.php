@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <div class="relative min-h-screen lg:grid lg:grid-cols-[1.08fr_1fr]">
        {{-- Panel branding --}}
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-teal-600 via-teal-700 to-emerald-900 lg:flex lg:flex-col">
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -top-28 -left-24 size-96 rounded-full bg-teal-300/20 blur-3xl"></div>
                <div class="absolute -right-24 -bottom-32 size-[30rem] rounded-full bg-emerald-300/10 blur-3xl"></div>
                <div class="absolute top-1/3 left-1/2 size-64 -translate-x-1/2 rounded-full bg-white/5 blur-2xl"></div>
                <div class="absolute inset-0 opacity-[0.08]"
                     style="background-image: linear-gradient(to right, #fff 1px, transparent 1px), linear-gradient(to bottom, #fff 1px, transparent 1px); background-size: 56px 56px;"></div>
            </div>

            <div class="relative flex flex-1 flex-col justify-between p-10 xl:p-14">
                <a href="{{ url('/') }}" class="inline-flex w-fit items-center gap-3">
                    <x-brand-mark class="size-11" />
                    <span>
                        <span class="block text-xl font-extrabold tracking-tight text-white">Athena</span>
                        <span class="block text-[11px] font-medium tracking-[0.18em] text-teal-100/80 uppercase">Jabatan Akademik</span>
                    </span>
                </a>

                <div class="max-w-xl py-12">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-semibold text-teal-50 backdrop-blur">
                        <span class="size-1.5 rounded-full bg-emerald-300"></span>
                        Sistem Pengajuan Jabatan Akademik Dosen
                    </span>

                    <h1 class="mt-6 text-4xl leading-[1.12] font-extrabold tracking-tight text-white xl:text-[2.9rem]">
                        Karier akademik Anda,
                        <span class="text-teal-200">lebih tertata.</span>
                    </h1>

                    <p class="mt-5 max-w-lg text-[15px] leading-relaxed text-teal-50/80">
                        Ajukan, pantau, dan kelola proses jabatan akademik dalam satu platform yang aman,
                        transparan, dan terintegrasi.
                    </p>

                    <ul class="mt-9 space-y-4">
                        @foreach ([
                            'Pengajuan berkas secara digital tanpa antrean',
                            'Pelacakan status verifikasi secara real-time',
                            'Riwayat jabatan akademik tersimpan rapi',
                        ] as $feature)
                            <li class="flex items-start gap-3 text-sm font-medium text-teal-50/90">
                                <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-teal-300/20 ring-1 ring-teal-200/30">
                                    <svg class="size-3 text-teal-100" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.313a1 1 0 0 1-1.42.002L4.29 10.27a1 1 0 1 1 1.42-1.408l3.043 3.067 6.537-6.596a1 1 0 0 1 1.414-.043Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex items-center justify-between gap-6 border-t border-white/10 pt-6">
                    <p class="text-xs text-teal-100/60">&copy; {{ date('Y') }} Athena Campus Suite</p>
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-teal-100/70">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M12 3 5 6v5c0 4.4 2.9 8.4 7 9.5 4.1-1.1 7-5.1 7-9.5V6l-7-3Z" stroke-linejoin="round" />
                            <path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Akses terenkripsi
                    </span>
                </div>
            </div>
        </aside>

        {{-- Panel formulir --}}
        <main class="relative flex min-h-screen flex-col bg-slate-50">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-teal-50 to-transparent lg:bg-none" aria-hidden="true"></div>

            <div class="relative flex flex-1 items-center justify-center px-5 py-10 sm:px-8 lg:py-12">
                <div class="w-full max-w-[27rem] animate-fade-up">
                    {{-- Logo mobile --}}
                    <div class="mb-9 flex items-center gap-3 lg:hidden">
                        <x-brand-mark variant="dark" class="size-11" />
                        <span>
                            <span class="block text-lg font-extrabold tracking-tight text-slate-900">Athena</span>
                            <span class="block text-[10px] font-semibold tracking-[0.16em] text-teal-700 uppercase">Jabatan Akademik</span>
                        </span>
                    </div>

                    <div class="mb-8">
                        <span class="mb-3 inline-flex items-center gap-2 text-xs font-semibold tracking-wide text-teal-700 uppercase">
                            <span class="h-px w-6 bg-teal-500"></span>
                            Portal Dosen
                        </span>
                        <h2 class="text-[1.75rem] font-extrabold tracking-tight text-slate-900 sm:text-3xl">Selamat datang kembali</h2>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">
                            Masuk menggunakan NIDN dan password akun kampus Anda.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-800" role="status">
                            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                            </svg>
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                            </svg>
                            <div class="space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-5" novalidate>
                        @csrf

                        <div>
                            <label for="nidn" class="mb-2 block text-sm font-semibold text-slate-700">NIDN</label>
                            <div class="relative">
                                <span class="input-icon">
                                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M15 7a4 4 0 1 0-3.2 6.4L7 18.2V21H4v-3l4.8-4.8A4 4 0 0 0 15 7Z" stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="16.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
                                    </svg>
                                </span>
                                <input
                                    id="nidn"
                                    name="nidn"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="username"
                                    value="{{ old('nidn') }}"
                                    placeholder="Contoh: 1987654321"
                                    maxlength="20"
                                    required
                                    autofocus
                                    @class([
                                        'input-field',
                                        'border-rose-300 bg-rose-50/50 focus:border-rose-400 focus:ring-rose-500/10' => $errors->has('nidn'),
                                    ])
                                >
                            </div>
                            @error('nidn')
                                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                                <a href="mailto:admin@athena.test?subject=Bantuan%20akun%20Athena"
                                   class="text-xs font-semibold text-teal-700 transition hover:text-teal-800 hover:underline">
                                    Lupa password?
                                </a>
                            </div>
                            <div class="relative">
                                <span class="input-icon">
                                    <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" />
                                        <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5" stroke-linecap="round" />
                                        <circle cx="12" cy="15.5" r="1.25" fill="currentColor" stroke="none" />
                                    </svg>
                                </span>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    placeholder="Masukkan password Anda"
                                    required
                                    @class([
                                        'input-field pr-12',
                                        'border-rose-300 bg-rose-50/50 focus:border-rose-400 focus:ring-rose-500/10' => $errors->has('password'),
                                    ])
                                >
                                <button type="button"
                                        data-password-toggle="#password"
                                        aria-label="Tampilkan password"
                                        aria-pressed="false"
                                        class="absolute top-1/2 right-2.5 flex size-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:ring-2 focus-visible:ring-teal-500">
                                    <svg data-eye-open class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                    <svg data-eye-off class="hidden size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M3 3l18 18" stroke-linecap="round" />
                                        <path d="M10.6 6.1A9.8 9.8 0 0 1 12 6c6 0 9.5 6 9.5 6a17 17 0 0 1-3.1 3.8M6.2 7.2C3.9 8.9 2.5 12 2.5 12S6 18 12 18c1.4 0 2.6-.3 3.8-.8" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-rose-600">
                                    <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                            <p data-caps-warning class="mt-2 hidden items-center gap-1.5 text-xs font-medium text-amber-600">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 2a.75.75 0 0 1 .67.415l7.25 14A.75.75 0 0 1 17.25 17.5H2.75a.75.75 0 0 1-.67-1.085l7.25-14A.75.75 0 0 1 10 2Zm0 4.25a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0V7A.75.75 0 0 1 10 6.25Zm0 8.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                                </svg>
                                Caps Lock sedang aktif.
                            </p>
                        </div>

                        <label class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-slate-600 select-none">
                            <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                                   class="size-4 rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500">
                            Ingat saya di perangkat ini
                        </label>

                        <button type="submit" class="btn-primary group">
                            Masuk ke Dashboard
                            <svg class="size-4 transition-transform group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </form>

                    <div class="mt-7 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-xs font-bold tracking-wide text-slate-500 uppercase">Akun demo</p>
                            <span class="rounded-full bg-teal-50 px-2 py-0.5 text-[10px] font-bold tracking-wide text-teal-700 uppercase">Development</span>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button type="button"
                                    data-fill-nidn="1987654321"
                                    data-fill-password="password"
                                    class="group flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-left transition hover:border-teal-300 hover:bg-teal-50">
                                <span>
                                    <span class="block text-xs font-bold text-slate-700 group-hover:text-teal-800">Dosen</span>
                                    <span class="block font-mono text-[11px] text-slate-500">1987654321</span>
                                </span>
                                <svg class="size-4 text-slate-300 transition group-hover:text-teal-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button type="button"
                                    data-fill-nidn="0000000001"
                                    data-fill-password="password"
                                    class="group flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5 text-left transition hover:border-teal-300 hover:bg-teal-50">
                                <span>
                                    <span class="block text-xs font-bold text-slate-700 group-hover:text-teal-800">Admin</span>
                                    <span class="block font-mono text-[11px] text-slate-500">0000000001</span>
                                </span>
                                <svg class="size-4 text-slate-300 transition group-hover:text-teal-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-3 text-[11px] leading-relaxed text-slate-400">
                            Klik salah satu akun untuk mengisi formulir otomatis · password: <span class="font-mono text-slate-500">password</span>
                        </p>
                    </div>

                    <p class="mt-8 text-center text-[11px] text-slate-400">
                        Dengan masuk, Anda menyetujui kebijakan penggunaan sistem kampus.
                    </p>
                </div>
            </div>

            <footer class="relative border-t border-slate-200/70 px-5 py-4 text-center text-[11px] text-slate-400 sm:px-8 lg:border-t-0">
                &copy; {{ date('Y') }} Athena · Sistem Pengajuan Jabatan Akademik Dosen
            </footer>
        </main>
    </div>
@endsection
