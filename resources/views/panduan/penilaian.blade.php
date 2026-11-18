@extends('layouts.app')

@section('title', 'Panduan Penilaian')
@section('breadcrumb', 'Panduan')
@section('page-title', 'Panduan Penilaian')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Panduan
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Panduan Penilaian</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                @if ($isAdmin)
                    Acuan penilaian angka kredit yang dibaca TIM PAK. Unggah berkas PDF atau kirim link berkas, lalu periksa hasilnya pada pratinjau di bawah.
                @else
                    Acuan penilaian angka kredit yang dipakai TIM PAK. Isi panduan ditampilkan pada pratinjau di bawah halaman ini.
                @endif
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="font-bold">Panduan belum dapat disimpan.</p>
                    <p class="mt-0.5 text-xs text-rose-600">Periksa kembali berkas atau link yang dipilih.</p>
                </div>
            </div>
        @endif

        @if ($isAdmin)
            @php $tipeTerpilih = old('tipe', $panduan?->tipe ?? 'file'); @endphp

            <section class="section-card mb-6">
                <div class="section-heading">
                    <span class="section-number">01</span>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-800">Sumber Panduan</h3>
                        <p class="mt-0.5 text-xs text-slate-400">Pilih salah satu: upload file atau kirim link file.</p>
                    </div>
                    @if ($panduan)
                        <span class="ml-auto shrink-0 rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-bold whitespace-nowrap text-teal-700 ring-1 ring-teal-100 ring-inset">
                            {{ $panduan->labelTipe() }}
                        </span>
                    @endif
                </div>

                <form method="POST" action="{{ route('panduan.penilaian.simpan') }}" enctype="multipart/form-data"
                      class="space-y-5 px-5 py-5 sm:px-6" data-panduan-sumber>
                    @csrf

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 transition hover:border-teal-300 has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50/70 has-[:checked]:ring-2 has-[:checked]:ring-teal-500/15">
                            <input type="radio" name="tipe" value="file" @checked($tipeTerpilih === 'file') class="mt-0.5 size-4 shrink-0 accent-teal-600">
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-slate-800">Upload File</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">Unggah berkas PDF dari perangkat, maksimal 10 MB.</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 px-4 py-3.5 transition hover:border-teal-300 has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50/70 has-[:checked]:ring-2 has-[:checked]:ring-teal-500/15">
                            <input type="radio" name="tipe" value="link" @checked($tipeTerpilih === 'link') class="mt-0.5 size-4 shrink-0 accent-teal-600">
                            <span class="min-w-0">
                                <span class="block text-sm font-bold text-slate-800">Kirim Link File</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500">Tempel tautan berkas, misalnya Google Drive.</span>
                            </span>
                        </label>
                    </div>

                    <div data-sumber="file" data-file-field class="space-y-2">
                        <label for="berkas" class="form-label">
                            Berkas Panduan <span class="text-rose-500">*</span>
                        </label>
                        <input type="file" id="berkas" name="berkas" accept="application/pdf,.pdf"
                               data-pdf-input data-max-kb="{{ \App\Models\PanduanPenilaian::MAKS_KB }}"
                               @class([
                                   'block w-full cursor-pointer rounded-xl border bg-slate-50/60 text-sm text-slate-500 shadow-sm transition file:mr-3 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-teal-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white hover:border-teal-300 focus:outline-none',
                                   'border-rose-300 bg-rose-50/40' => $errors->has('berkas'),
                                   'border-slate-200' => ! $errors->has('berkas'),
                               ])>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Format PDF, maksimal 10 MB.
                            @if ($panduan?->memakaiBerkas())
                                Kosongkan jika tidak ingin mengganti berkas saat ini.
                            @endif
                        </p>
                        <p data-file-error class="form-error hidden" role="alert"></p>
                        @error('berkas') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div data-sumber="link" class="space-y-2">
                        <label for="tautan" class="form-label">
                            Link Berkas Panduan <span class="text-rose-500">*</span>
                        </label>
                        <input type="url" id="tautan" name="tautan" maxlength="2048"
                               value="{{ old('tautan', $panduan?->url) }}"
                               placeholder="https://drive.google.com/file/d/xxxxxxxx/view"
                               @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tautan')])>
                        <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                            Tautan <span class="font-mono text-slate-500">/view</span> Google Drive otomatis diubah menjadi
                            <span class="font-mono text-slate-500">/preview</span> agar tampil pada pratinjau.
                        </p>
                        @error('tautan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-2 text-[11px] text-slate-400">
                            <svg class="size-4 shrink-0 text-teal-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v2.5h-2.5a.75.75 0 0 0 0 1.5h2.5v2.5a.75.75 0 0 0 1.5 0v-2.5h2.5a.75.75 0 0 0 0-1.5h-2.5v-2.5Z" clip-rule="evenodd" />
                            </svg>
                            @if ($panduan)
                                Terakhir diperbarui {{ $panduan->updated_at->translatedFormat('d F Y, H:i') }} WIB
                                @if ($panduan->user)
                                    oleh {{ $panduan->user->name }}
                                @endif
                            @else
                                Belum ada panduan yang disimpan.
                            @endif
                        </p>
                        <button type="submit" class="btn-primary w-auto justify-center px-5 whitespace-nowrap">
                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                                <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
                            </svg>
                            Simpan Panduan
                        </button>
                    </div>
                </form>
            </section>
        @endif

        <section class="section-card">
            <div class="section-heading">
                <span class="section-number">{{ $isAdmin ? '02' : '01' }}</span>
                <div class="min-w-0">
                    <h3 class="text-sm font-extrabold text-slate-800">Isi Panduan</h3>
                    <p class="mt-0.5 truncate text-xs text-slate-400">
                        @if ($panduan)
                            {{ $panduan->memakaiBerkas() ? $panduan->nama_asli : $panduan->url }}
                        @else
                            Panduan belum tersedia.
                        @endif
                    </p>
                </div>
            </div>

            <div class="px-4 py-4 sm:px-5 sm:py-5">
                @if (! $panduan)
                    <div class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-14 text-center">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-white text-slate-300 shadow-sm ring-1 ring-slate-100">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                                <path d="M6 3.5h8.5L19 8v12.5H6V3.5Z" stroke-linejoin="round" />
                                <path d="M14 3.5V8h4.5" stroke-linejoin="round" />
                                <path d="M9 12.5h7M9 16h4.5" stroke-linecap="round" />
                            </svg>
                        </span>
                        <p class="text-sm font-bold text-slate-700">Panduan penilaian belum tersedia</p>
                        <p class="max-w-sm text-xs leading-relaxed text-slate-500">
                            @if ($isAdmin)
                                Unggah berkas PDF atau kirim link berkas pada formulir di atas agar TIM PAK dapat membacanya.
                            @else
                                Hubungi admin kepegawaian untuk melengkapi panduan penilaian.
                            @endif
                        </p>
                    </div>
                @else
                    @php $sumberUrl = $panduan->memakaiBerkas() ? route('panduan.penilaian.berkas') : $panduan->url; @endphp

                    <iframe src="{{ $panduan->memakaiBerkas() ? $sumberUrl : $tautanPratinjau }}"
                            title="Isi Panduan Penilaian" referrerpolicy="no-referrer"
                            class="h-[70vh] min-h-[420px] w-full rounded-xl border border-slate-200 bg-slate-100"></iframe>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[11px] leading-relaxed text-slate-400">
                            @if ($panduan->memakaiBerkas())
                                {{ $panduan->nama_asli }}@if ($panduan->ukuranBerkas()) &middot; {{ $panduan->ukuranBerkas() }}@endif
                            @else
                                Sebagian tautan membatasi tampilan di dalam halaman. Bila pratinjau kosong, buka tautannya di tab baru.
                            @endif
                        </p>
                        <div class="flex items-center gap-2">
                            <a href="{{ $sumberUrl }}" target="_blank" rel="noopener" class="btn-small">
                                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M11 3.75a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0V5.56l-6.22 6.22a.75.75 0 1 1-1.06-1.06L14.44 4.5h-2.69a.75.75 0 0 1-.75-.75Z" />
                                    <path d="M4.75 6A1.75 1.75 0 0 0 3 7.75v7.5C3 16.216 3.784 17 4.75 17h7.5A1.75 1.75 0 0 0 14 15.25v-3a.75.75 0 0 0-1.5 0v3a.25.25 0 0 1-.25.25h-7.5a.25.25 0 0 1-.25-.25v-7.5a.25.25 0 0 1 .25-.25h3a.75.75 0 0 0 0-1.5h-3Z" />
                                </svg>
                                Buka di tab baru
                            </a>

                            @if ($isAdmin)
                                <form method="POST" action="{{ route('panduan.penilaian.hapus') }}"
                                      data-confirm="TIM PAK tidak dapat membaca panduan sampai admin menyimpan yang baru. Lanjutkan?"
                                      data-confirm-title="Hapus panduan penilaian?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-600 shadow-sm transition hover:bg-rose-50 hover:text-rose-700">
                                        <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482 41.03 41.03 0 0 0-2.365-.298V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" />
                                        </svg>
                                        Hapus Panduan
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection

