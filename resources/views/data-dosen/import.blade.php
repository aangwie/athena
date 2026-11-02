@extends('layouts.app')

@section('title', 'Import Data Dosen')
@section('breadcrumb', 'Administrasi')
@section('page-title', 'Import Data Dosen')

@section('content')
    <div class="mx-auto max-w-2xl" data-import>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                    <span class="h-px w-6 bg-teal-500"></span>
                    Administrasi
                </span>
                <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Import Data Dosen</h2>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                    Unggah berkas Excel berisi data dosen. Berkas dihapus otomatis setelah semua baris tersimpan.
                </p>
            </div>
            <a href="{{ route('data-dosen.index') }}" class="btn-secondary shrink-0">Kembali</a>
        </div>

        <section class="section-card mb-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">1. Unduh template</h3>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Template berisi judul kolom dan satu baris contoh. Jangan ubah urutan kolom.
                    </p>
                </div>
                <a href="{{ route('data-dosen.import.template') }}" class="btn-secondary w-auto shrink-0 px-5 whitespace-nowrap">
                    Unduh Template
                </a>
            </div>

            <div class="mt-5 overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-[11px] font-bold tracking-wide text-slate-500 uppercase">
                        <tr>
                            @foreach ($kolom as $judul)
                                <th class="px-3 py-2 whitespace-nowrap">{{ $judul }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600">
                        @foreach ($contoh as $baris)
                            <tr>
                                @foreach ($baris as $sel)
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $sel !== '' ? $sel : '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <ul class="mt-4 space-y-1.5 text-xs leading-relaxed text-slate-500">
                <li>• <strong class="font-semibold text-slate-700">Memiliki Jabatan Akademik</strong> diisi <em>Ya</em> atau <em>Tidak</em>; jika kosong, disimpulkan dari terisi/tidaknya Jabatan Akademik &amp; Pangkat.</li>
                <li>• <strong class="font-semibold text-slate-700">Role</strong> dipisah koma, misalnya <code class="rounded bg-slate-100 px-1 py-0.5">dosen,tim_pak</code>. Kosong berarti <em>dosen</em>.</li>
                <li>• <strong class="font-semibold text-slate-700">Password</strong> minimal 8 karakter; kosong berarti <em>{{ $passwordBawaan }}</em>.</li>
                <li>• NIDN yang sudah terdaftar dilewati, bukan ditimpa.</li>
            </ul>
        </section>

        <form method="POST" action="{{ route('data-dosen.import.mulai') }}" enctype="multipart/form-data" data-import-form class="section-card">
            @csrf
            <h3 class="text-sm font-bold text-slate-800">2. Unggah berkas</h3>

            <div class="mt-3">
                <input type="file" name="berkas" accept=".xlsx" required
                       class="form-input file:mr-3 file:rounded-lg file:border-0 file:bg-teal-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-teal-700">
                <p class="mt-2 text-[11px] text-slate-400">Format .xlsx, maksimal 10 MB.</p>
            </div>

            <div data-progress-wrapper class="mt-5 hidden">
                <div class="mb-2 flex items-center justify-between text-xs font-semibold text-slate-600">
                    <span data-progress-label>Mengunggah…</span>
                    <span data-progress-value>0%</span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200">
                    <div data-progress-bar class="h-full w-0 rounded-full bg-teal-600 transition-[width] duration-300"></div>
                </div>
            </div>

            <p data-progress-note class="mt-3 hidden text-xs leading-relaxed text-slate-500" role="status" aria-live="polite"></p>

            <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('data-dosen.index') }}" class="btn-secondary flex-1 whitespace-nowrap sm:flex-none">Batal</a>
                <button type="submit" data-import-submit class="btn-primary w-auto flex-1 px-5 whitespace-nowrap sm:flex-none">
                    Import Sekarang
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.querySelector('[data-import-form]');
            if (!form) return;

            const wrapper = document.querySelector('[data-progress-wrapper]');
            const bar = document.querySelector('[data-progress-bar]');
            const value = document.querySelector('[data-progress-value]');
            const label = document.querySelector('[data-progress-label]');
            const note = document.querySelector('[data-progress-note]');
            const submit = document.querySelector('[data-import-submit]');
            const token = form.querySelector('input[name="_token"]').value;
            const urlLangkah = @json(route('data-dosen.import.langkah'));

            const atur = (persen, teks) => {
                wrapper.classList.remove('hidden');
                bar.style.width = persen + '%';
                value.textContent = persen + '%';
                label.textContent = teks;
            };

            const tulis = (pesan, galat) => {
                note.classList.remove('hidden');
                note.className = galat
                    ? 'mt-3 text-xs leading-relaxed text-rose-600'
                    : 'mt-3 text-xs leading-relaxed text-slate-500';
                note.textContent = pesan;
            };

            const berhenti = (pesan) => {
                tulis(pesan, true);
                submit.disabled = false;
            };

            const pesanGalat = (status, data) => {
                if (data?.errors) return Object.values(data.errors).flat().join(' ');
                if (data?.pesan) return data.pesan;
                if (data?.message) return data.message;
                return status === 419 ? 'Sesi berakhir. Muat ulang halaman lalu ulangi.' : 'Import gagal (' + status + ').';
            };

            const langkah = async () => {
                try {
                    const res = await fetch(urlLangkah, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) return berhenti(pesanGalat(res.status, data));

                    atur(data.persen ?? 0, 'Menyimpan ke database…');
                    tulis(data.pesan ?? '');

                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }

                    langkah();
                } catch {
                    berhenti('Proses import terputus. Periksa koneksi lalu ulangi.');
                }
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                note.classList.add('hidden');
                submit.disabled = true;
                atur(0, 'Mengunggah berkas…');

                const xhr = new XMLHttpRequest();
                xhr.open('POST', form.action);
                xhr.setRequestHeader('Accept', 'application/json');
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        atur(Math.round((e.loaded / e.total) * 100), 'Mengunggah berkas…');
                    }
                };

                xhr.onload = () => {
                    const data = (() => {
                        try { return JSON.parse(xhr.responseText); } catch { return {}; }
                    })();

                    if (xhr.status >= 400) return berhenti(pesanGalat(xhr.status, data));

                    atur(0, data.pesan ?? 'Menyimpan ke database…');
                    langkah();
                };

                xhr.onerror = () => berhenti('Koneksi terputus saat mengunggah berkas.');
                xhr.send(new FormData(form));
            });
        })();
    </script>
@endpush