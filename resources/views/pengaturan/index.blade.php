@extends('layouts.app')

@section('title', 'Pengaturan')
@section('breadcrumb', 'Pengaturan')
@section('page-title', 'Pengaturan')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Sistem
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Pengaturan</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Kelola token GitHub untuk pembaruan aplikasi, bersihkan cache, jalankan migrasi tabel, buat symlink storage,
                serta backup dan import database. Setiap tindakan dicatat pada terminal log di bawah.
            </p>
        </div>

        {{-- 01 · Identitas web --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">01</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Nama Web</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Nama yang tampil di sidebar, judul tab, dan footer seluruh halaman.</p>
                </div>
            </div>

            <div class="px-5 py-5 sm:px-6">
                <form method="POST" action="{{ route('pengaturan.nama-web') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="nama_web" class="form-label">
                            Nama web <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" id="nama_web" name="nama_web" maxlength="10" required
                               value="{{ old('nama_web', $namaWeb) }}"
                               placeholder="Contoh: Athena"
                               @class(['form-input sm:max-w-xs', 'border-rose-300 bg-rose-50/40' => $errors->has('nama_web')])>
                        @error('nama_web') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Maksimal 10 karakter. Nama saat ini:
                            <span class="font-semibold text-slate-600">{{ $namaWeb }}</span>.
                        </p>
                    </div>

                    <button type="submit" class="btn-primary w-auto justify-center px-5">Simpan Nama Web</button>
                </form>
            </div>
        </section>

        {{-- 02 · Data update --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">02</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Data Update</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Token GitHub untuk menarik pembaruan dari repositori.</p>
                </div>
            </div>

            <div class="grid gap-5 px-5 py-5 sm:px-6">
                <form method="POST" action="{{ route('pengaturan.token') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="token_github" class="form-label">
                            Token GitHub <span class="text-rose-500">*</span>
                        </label>
                        <input type="password" id="token_github" name="token_github" autocomplete="off" spellcheck="false"
                               placeholder="github_pat_xxxxxxxxxxxxxxxxxxxx"
                               @class(['form-input font-mono', 'border-rose-300 bg-rose-50/40' => $errors->has('token_github')])>
                        @error('token_github') <p class="form-error">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Format wajib <span class="font-mono text-slate-500">github_patxxxxxxxxx</span>. Token disimpan pada berkas
                            <span class="font-mono text-slate-500">.token-gh</span> (tidak ikut ter-commit).
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-2 text-[11px] text-slate-400">
                            @if ($tokenSamar)
                                <span class="size-1.5 rounded-full bg-teal-500"></span>
                                Token tersimpan: <span class="font-mono text-slate-600">{{ $tokenSamar }}</span>
                            @else
                                <span class="size-1.5 rounded-full bg-amber-400"></span>
                                Belum ada token tersimpan.
                            @endif
                        </p>
                        <button type="submit" class="btn-secondary shrink-0">Simpan Token</button>
                    </div>
                </form>

                <div class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Perbarui aplikasi</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            Menjalankan <span class="font-mono text-slate-600">git pull --ff-only</span> pada cabang
                            <span class="font-mono text-slate-600">{{ $cabang }}</span>.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('pengaturan.update') }}" data-confirm="Tarik pembaruan aplikasi dari repositori GitHub sekarang?">
                        @csrf
                        <button type="submit" class="btn-primary w-auto justify-center px-5 whitespace-nowrap">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M12 4v10M8 10.5 12 14.5 16 10.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M4.5 16v2A1.5 1.5 0 0 0 6 19.5h12a1.5 1.5 0 0 0 1.5-1.5v-2" stroke-linecap="round" />
                            </svg>
                            Update Aplikasi
                        </button>
                    </form>
                </div>
            </div>
        </section>
        {{-- 03 · Pemeliharaan --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">03</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Pemeliharaan Sistem</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Tindakan cepat untuk cache, skema database, dan tautan penyimpanan.</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Clear cache</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            Menjalankan <span class="font-mono text-slate-600">php artisan optimize:clear</span>.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('pengaturan.clear-cache') }}">
                        @csrf
                        <button type="submit" class="btn-secondary w-full shrink-0 justify-center whitespace-nowrap sm:w-auto">Bersihkan Cache</button>
                    </form>
                </div>

                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-xs font-bold text-slate-700">Migrasi tabel</p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            Menjalankan <span class="font-mono text-slate-600">php artisan migrate --force</span>.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('pengaturan.migrate') }}" data-confirm="Jalankan migrasi tabel database sekarang?">
                        @csrf
                        <button type="submit" class="btn-secondary w-full shrink-0 justify-center whitespace-nowrap sm:w-auto">Migrasi Tabel</button>
                    </form>
                </div>

                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="flex items-center gap-2 text-xs font-bold text-slate-700">
                            Symlink storage
                            <span @class([
                                'rounded-md px-1.5 py-0.5 text-[9px] font-bold tracking-wide uppercase',
                                'bg-teal-50 text-teal-700 ring-1 ring-teal-100' => $storageTertaut,
                                'bg-amber-50 text-amber-700 ring-1 ring-amber-100' => ! $storageTertaut,
                            ])>{{ $storageTertaut ? 'Tertaut' : 'Belum' }}</span>
                        </p>
                        <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                            Menjalankan <span class="font-mono text-slate-600">php artisan storage:link --force</span>.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('pengaturan.storage-link') }}">
                        @csrf
                        <button type="submit" class="btn-secondary w-full shrink-0 justify-center whitespace-nowrap sm:w-auto">Buat Symlink</button>
                    </form>
                </div>
            </div>
        </section>

        {{-- 04 · Backup & import database --}}
        <section class="section-card mb-5">
            <div class="section-heading">
                <span class="section-number">04</span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-800">Backup &amp; Import Database</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Unduh salinan seluruh tabel, atau pulihkan database dari berkas <span class="font-mono">.sql</span>.</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                {{-- Backup --}}
                <div class="px-5 py-4 sm:px-6" data-db-panel>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-700">Backup database</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                                Menulis struktur dan isi seluruh tabel, lalu mengunduhnya sebagai berkas
                                <span class="font-mono text-slate-600">backup-{{ now()->format('Ymd-His') }}.sql</span>.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('pengaturan.db.mulai') }}"
                              data-db-job data-db-langkah="{{ route('pengaturan.db.langkah') }}"
                              data-confirm="Buat backup seluruh database sekarang?"
                              data-confirm-title="Backup database?">
                            @csrf
                            <input type="hidden" name="mode" value="backup">
                            <button type="submit" class="btn-secondary w-full shrink-0 justify-center whitespace-nowrap sm:w-auto">
                                Backup Database
                            </button>
                        </form>
                    </div>

                    <div class="mt-3 hidden" data-db-progress>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full w-0 rounded-full bg-teal-500 transition-[width] duration-300" data-db-bar></div>
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500" data-db-info></p>
                        <a href="#" class="mt-1 inline-flex hidden text-[11px] font-semibold text-teal-700 hover:underline" data-db-unduh>
                            Unduh ulang hasil backup
                        </a>
                    </div>
                </div>

                {{-- Import --}}
                <div class="px-5 py-4 sm:px-6" data-db-panel>
                    <form method="POST" action="{{ route('pengaturan.db.mulai') }}" enctype="multipart/form-data"
                          data-db-job data-db-langkah="{{ route('pengaturan.db.langkah') }}"
                          data-confirm="Import akan menimpa tabel yang namanya sama. Lanjutkan?"
                          data-confirm-title="Import database?"
                          class="space-y-3">
                        @csrf
                        <input type="hidden" name="mode" value="import">
                        <div>
                            <p class="text-xs font-bold text-slate-700">Import database</p>
                            <label for="berkas_import" class="form-label mt-2">
                                Berkas backup <span class="text-rose-500">*</span>
                            </label>
                            <input type="file" id="berkas_import" name="berkas" accept=".sql" required
                                   @class([
                                       'form-input py-2 text-xs file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-teal-50 file:px-3 file:py-1.5 file:text-[11px] file:font-semibold file:text-teal-700',
                                       'border-rose-300 bg-rose-50/40' => $errors->has('berkas'),
                                   ])>
                            @error('berkas') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                Format <span class="font-mono text-slate-500">.sql</span>, maksimal 100 MB. Berkas dijalankan bertahap
                                sehingga tabel yang namanya sama akan ditimpa.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[11px] text-slate-400">Ambil berkas dari menu Backup di atas.</p>
                            <button type="submit" class="btn-primary w-auto justify-center px-5 whitespace-nowrap">
                                Import Database
                            </button>
                        </div>

                        <div class="hidden" data-db-progress>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full w-0 rounded-full bg-teal-500 transition-[width] duration-300" data-db-bar></div>
                            </div>
                            <p class="mt-1.5 text-[11px] text-slate-500" data-db-info></p>
                        </div>
                    </form>
                </div>

                {{-- Kosongkan data --}}
                <div class="px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold text-rose-700">Hapus seluruh data</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-500">
                                Mengosongkan profil, riwayat, kegiatan, pengajuan, dan berkas syarat khusus seluruh dosen.
                                Akun pengguna, nama web, serta data master (instansi, prodi, jabatan, kepangkatan) tetap disimpan.
                                Buat backup lebih dulu — tindakan ini tidak dapat dibatalkan.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('pengaturan.db.kosongkan') }}"
                              data-confirm="Seluruh data dosen (profil, riwayat, kegiatan, pengajuan, berkas) akan dihapus permanen. Akun pengguna tetap disimpan. Lanjutkan?"
                              data-confirm-title="Hapus seluruh data?">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn-secondary w-full shrink-0 justify-center whitespace-nowrap text-rose-600 hover:bg-rose-50 hover:text-rose-600 sm:w-auto">
                                Hapus Semua Data
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        {{-- 05 · Terminal log --}}
        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-lg">
            <div class="flex items-center gap-2 border-b border-slate-800 bg-slate-900/80 px-4 py-3">
                <span class="size-2.5 rounded-full bg-rose-500/80"></span>
                <span class="size-2.5 rounded-full bg-amber-400/80"></span>
                <span class="size-2.5 rounded-full bg-emerald-500/80"></span>
                <p class="ml-2 font-mono text-[11px] text-slate-400">terminal · storage/logs/laravel.log</p>
                <span class="ml-auto rounded-md bg-white/5 px-1.5 py-0.5 font-mono text-[10px] text-slate-400">
                    {{ count($log) }} tindakan
                </span>
                <form method="POST" action="{{ route('pengaturan.log.hapus') }}" data-confirm="Hapus seluruh isi terminal log?">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="flex items-center gap-1 rounded-md bg-white/5 px-2 py-1 font-mono text-[10px] text-slate-400 transition hover:bg-rose-500/15 hover:text-rose-300 disabled:cursor-not-allowed disabled:opacity-40"
                            @disabled($log === [])>
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M5 7h14M10 7V5.5A1.5 1.5 0 0 1 11.5 4h1A1.5 1.5 0 0 1 14 5.5V7M6.5 7l.8 12a1.5 1.5 0 0 0 1.5 1.4h6.4a1.5 1.5 0 0 0 1.5-1.4l.8-12" stroke-linecap="round" />
                        </svg>
                        Hapus log
                    </button>
                </form>
            </div>

            <div class="max-h-96 space-y-4 overflow-y-auto px-4 py-4 font-mono text-[11.5px] leading-relaxed">
                @forelse ($log as $entri)
                    <div>
                        <p class="text-teal-300">
                            <span class="text-slate-500">[{{ $entri['waktu'] }}]</span>
                            {{ $entri['judul'] }}
                            <span class="{{ $entri['sukses'] ? 'text-emerald-400' : 'text-rose-400' }}">
                                · {{ $entri['sukses'] ? 'OK' : 'FAILED' }}
                            </span>
                        </p>
                        @foreach ($entri['baris'] as $baris)
                            <p @class(['whitespace-pre-wrap', 'text-slate-300' => ! str_starts_with($baris, '$'), 'text-slate-500' => str_starts_with($baris, '$')])>{{ $baris }}</p>
                        @endforeach
                    </div>
                @empty
                    <p class="text-slate-500">$ belum ada tindakan pada sesi ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
