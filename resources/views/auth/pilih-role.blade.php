@extends('layouts.auth')

@section('title', 'Pilih Peran')

@section('content')
    @php
        $descriptions = [
            'dosen' => 'Kelola profil, portofolio, dan ajukan jabatan akademik Anda.',
            'tim_pak' => 'Tinjau dan nilai usulan angka kredit dosen.',
            'admin' => 'Kelola data dosen, instansi, dan kepegawaian.',
        ];

        $icons = [
            'dosen' => '<circle cx="12" cy="8" r="3.5" /><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke-linecap="round" />',
            'tim_pak' => '<path d="M9 4.5h6M7.5 7h9A1.5 1.5 0 0 1 18 8.5v11a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 6 19.5v-11A1.5 1.5 0 0 1 7.5 7Z" stroke-linejoin="round" /><path d="m9.5 14 2 2 3.5-4" stroke-linecap="round" stroke-linejoin="round" />',
            'admin' => '<path d="M12 3.5 5.5 6v5.2c0 4.2 2.8 7.9 6.5 9.3 3.7-1.4 6.5-5.1 6.5-9.3V6L12 3.5Z" stroke-linejoin="round" /><path d="m9 12 2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />',
        ];
    @endphp

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-teal-700 via-teal-800 to-emerald-950 px-4 py-10">
        <div class="pointer-events-none absolute inset-0" aria-hidden="true">
            <div class="absolute -top-24 -left-20 size-80 rounded-full bg-teal-400/20 blur-3xl"></div>
            <div class="absolute -right-24 -bottom-28 size-96 rounded-full bg-emerald-300/10 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[0.07]"
                 style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 24px 24px;"></div>
        </div>

        <div class="relative w-full max-w-lg">
            <div class="rounded-3xl border border-white/40 bg-white p-6 shadow-2xl shadow-teal-950/30 sm:p-8">
                <div class="flex items-center gap-3">
                    <x-brand-mark class="size-10" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-extrabold tracking-tight text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="font-mono text-[11px] text-slate-500">NIDN {{ auth()->user()->nidn }}</p>
                    </div>
                    <span class="ml-auto rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold tracking-wide text-teal-700 uppercase ring-1 ring-teal-100 ring-inset">
                        Multi Peran
                    </span>
                </div>

                <h1 class="mt-6 text-xl font-extrabold tracking-tight text-slate-900">Masuk sebagai siapa?</h1>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                    Akun Anda memiliki lebih dari satu peran. Klik salah satu peran untuk langsung masuk — menu dan
                    hak akses akan mengikuti peran yang dipilih.
                </p>

                <div class="mt-5 space-y-2.5">
                    @foreach ($roles as $role)
                        <form method="POST" action="{{ route('role.select.store') }}">
                            @csrf
                            <input type="hidden" name="role" value="{{ $role }}">
                            <button type="submit" class="choice-card group w-full items-center text-left">
                                <span class="choice-icon">
                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        {!! $icons[$role] ?? '' !!}
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-slate-800">{{ \App\Models\User::ROLES[$role] }}</span>
                                    <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">{{ $descriptions[$role] ?? '' }}</span>
                                </span>
                                <svg class="size-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M5 12h14m-5-5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                        </form>
                    @endforeach
                </div>

                @error('role')
                    <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                    @csrf
                    <button type="submit" class="text-[11px] font-semibold text-slate-400 transition hover:text-rose-600">
                        Keluar dari sistem
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
