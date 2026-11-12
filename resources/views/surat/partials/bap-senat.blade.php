@php
    // Bidang keilmuan dikelompokkan per rumpun ilmu (optgroup pada dropdown).
    $bidangMap = \App\Models\SuratBapSenat::bidangKeilmuanOptions();
    $bidangForm = (string) old('bidang_keilmuan', $bapEdit?->bidang_keilmuan);

    // Bidang dari data lama yang belum ada pada daftar tetap ditampilkan agar tidak hilang.
    if ($bidangForm !== '' && \App\Models\SuratBapSenat::rumpunBidangKeilmuan($bidangForm) === null) {
        $bidangMap[\App\Models\SuratBapSenat::RUMPUN_LAINNYA] = [$bidangForm => $bidangForm];
    }

    // Prosentase proporsi penelitian dipilih dari daftar tetap (dropdown).
    $prosentaseList = \App\Models\SuratBapSenat::prosentasePenelitianList();
    $prosentaseForm = (string) old('prosentase_penelitian', $bapEdit?->prosentase_penelitian);

    // Nilai lama di luar daftar tetap ditampilkan agar tidak hilang saat disunting.
    if ($prosentaseForm !== '' && ! in_array((int) $prosentaseForm, $prosentaseList, true)) {
        $prosentaseList[] = (int) $prosentaseForm;
        sort($prosentaseList);
    }

    // Urutan pilihan admin dipertahankan: anggota yang sudah dipilih tampil lebih dulu
    // mengikuti urutan pilihan (termasuk saat menyunting atau setelah validasi gagal),
    // sisanya menyusul. Urutan ini pula yang dipakai tabel daftar hadir saat dicetak.
    $anggotaDipilih = array_map('strval', (array) old('anggota_senat_ids', $bapEdit?->anggota_senat_ids ?? []));
    $senatForm = $senatList
        ->filter(fn ($senat) => in_array((string) $senat->user_id, $anggotaDipilih, true))
        ->sortBy(fn ($senat) => array_search((string) $senat->user_id, $anggotaDipilih, true))
        ->concat($senatList->reject(fn ($senat) => in_array((string) $senat->user_id, $anggotaDipilih, true)))
        ->values();
@endphp

<p class="mt-1 max-w-2xl text-sm leading-relaxed text-slate-500">
    @if ($bapEdit)
        Ubah berita acara persetujuan senat nomor <span class="font-semibold text-slate-700">{{ $bapEdit->nomor_bap }}</span>.
    @else
        Catat berita acara persetujuan senat atas usulan kenaikan jabatan akademik dosen.
    @endif
</p>

@if ($errors->any())
    <div class="mt-4 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5 text-sm text-rose-700" role="alert">
        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-1.5 0v-4.5A.75.75 0 0 1 10 5Zm0 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
            <p class="font-bold">Periksa kembali data Anda.</p>
            <p class="mt-0.5 text-xs text-rose-600">
                Terdapat {{ $errors->count() }} isian yang perlu diperbaiki sebelum BAP dapat disimpan.
            </p>
        </div>
    </div>
@endif

<form method="POST"
      action="{{ $bapEdit ? route('surat.bap-senat.update', $bapEdit) : route('surat.bap-senat.store') }}"
      class="mt-4 rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
    @csrf
    @if ($bapEdit)
        @method('PUT')
    @endif

    <h4 class="text-sm font-extrabold tracking-tight text-slate-800">Data Berita Acara Persetujuan Senat</h4>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <label for="nomor_bap" class="form-label">
                Nomor BAP <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="nomor_bap" name="nomor_bap" required maxlength="100"
                   value="{{ old('nomor_bap', $bapEdit?->nomor_bap) }}" placeholder="Contoh: 001/BAP-SENAT/X/2026"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('nomor_bap')])>
            @error('nomor_bap') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="tanggal_bap" class="form-label">
                Tanggal <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="tanggal_bap" name="tanggal_bap" required
                   value="{{ old('tanggal_bap', $bapEdit?->tanggal_bap?->format('d-m-Y')) }}" placeholder="DD-MM-YYYY"
                   maxlength="10" inputmode="numeric" autocomplete="off" data-date-mask data-date-picker
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tanggal_bap')])>
            @error('tanggal_bap') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Pilih lewat kalender atau ketik format DD-MM-YYYY.</p>
        </div>

        <div>
            <label for="bap_dosen_id" class="form-label">
                Nama Dosen <span class="text-rose-500">*</span>
            </label>
            <select id="bap_dosen_id" name="dosen_id" required
                    data-select-search="Cari nama dosen…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('dosen_id')])>
                <option value="">Pilih dosen</option>
                @foreach ($dosenList as $dosen)
                    <option value="{{ $dosen->id }}" @selected((string) old('dosen_id', $bapEdit?->user_id) === (string) $dosen->id)>
                        {{ $dosen->name }} — {{ $dosen->nidn }}
                    </option>
                @endforeach
            </select>
            @error('dosen_id') <p class="form-error">{{ $message }}</p> @enderror

            @if ($dosenList->isEmpty())
                <p class="mt-1.5 text-[11px] text-amber-600">
                    Belum ada dosen aktif yang dapat dipilih.
                </p>
            @endif
        </div>

        <div>
            <label for="bidang_keilmuan" class="form-label">
                Bidang Keilmuan <span class="text-rose-500">*</span>
            </label>
            {{-- Daftar dikelompokkan per rumpun ilmu memakai <optgroup> (dropdown + sub). --}}
            <select id="bidang_keilmuan" name="bidang_keilmuan" required
                    data-select-search="Cari bidang atau rumpun ilmu…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('bidang_keilmuan')])>
                <option value="">Pilih bidang keilmuan</option>
                @foreach ($bidangMap as $rumpun => $daftar)
                    <optgroup label="{{ $rumpun }}">
                        @foreach ($daftar as $nama => $label)
                            <option value="{{ $nama }}" @selected($bidangForm === $nama)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            @error('bidang_keilmuan') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Dikelompokkan per rumpun ilmu A–D beserta kodenya.</p>
        </div>

        <div>
            <label for="prosentase_penelitian" class="form-label">
                Prosentase Proporsi Penelitian <span class="text-rose-500">*</span>
            </label>
            {{-- Nilai dipilih dari daftar tetap: 150, 200, 300, 400, 550, 700, 850, 1050. --}}
            <select id="prosentase_penelitian" name="prosentase_penelitian" required
                    data-select-search="Cari prosentase…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('prosentase_penelitian')])>
                <option value="">Pilih prosentase</option>
                @foreach ($prosentaseList as $nilai)
                    <option value="{{ $nilai }}" @selected($prosentaseForm === (string) $nilai)>{{ $nilai }}%</option>
                @endforeach
            </select>
            @error('prosentase_penelitian') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Pilih salah satu nilai proporsi penelitian.</p>
        </div>

        <div>
            <label for="tempat_pelaksanaan" class="form-label">
                Tempat Pelaksanaan <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="tempat_pelaksanaan" name="tempat_pelaksanaan" required maxlength="150"
                   value="{{ old('tempat_pelaksanaan', $bapEdit?->tempat_pelaksanaan) }}"
                   placeholder="Contoh: Ruang Rapat Senat Lt. 2"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('tempat_pelaksanaan')])>
            @error('tempat_pelaksanaan') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Dicetak pada daftar hadir rapat (halaman 2).</p>
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
            <label for="materi_rapat" class="form-label">
                Materi Rapat <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="materi_rapat" name="materi_rapat" required maxlength="255"
                   value="{{ old('materi_rapat', $bapEdit?->materi_rapat) }}"
                   placeholder="Contoh: Pemberian pertimbangan/persetujuan kenaikan jabatan akademik dosen"
                   autocomplete="off"
                   @class(['form-input', 'border-rose-300 bg-rose-50/40' => $errors->has('materi_rapat')])>
            @error('materi_rapat') <p class="form-error">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-[11px] text-slate-400">Dicetak pada daftar hadir rapat (halaman 2).</p>
        </div>

        <div class="sm:col-span-2 lg:col-span-3">
            <label for="bap_anggota_senat" class="form-label">
                Anggota Senat <span class="text-rose-500">*</span>
            </label>
            <select id="bap_anggota_senat" name="anggota_senat_ids[]" multiple required size="6"
                    data-select-search="Cari nama anggota senat…"
                    @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('anggota_senat_ids')])>
                @foreach ($senatForm as $senat)
                    <option value="{{ $senat->user_id }}"
                            @selected(in_array((string) $senat->user_id, $anggotaDipilih, true))>
                        {{ $senat->user?->name }} — {{ $senat->jabatanLengkap() }}
                    </option>
                @endforeach
            </select>
            @error('anggota_senat_ids') <p class="form-error">{{ $message }}</p> @enderror
            @error('anggota_senat_ids.*') <p class="form-error">{{ $message }}</p> @enderror

            <p class="mt-1.5 text-[11px] text-slate-400">
                Tahan Ctrl (Windows) atau Cmd (macOS) untuk memilih lebih dari satu anggota.
                Urutan pilihan menentukan urutan nama pada daftar hadir cetak: pilihan pertama
                paling atas, pilihan terakhir paling bawah.
                @if ($senatList->isEmpty())
                    Belum ada anggota senat.
                    <a href="{{ route('instansi.senat.index') }}" class="font-bold underline">Atur susunan senat</a>.
                @endif
            </p>
        </div>
    </div>

    <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
        @if ($bapEdit)
            <a href="{{ route('surat.index') }}#bap-senat" class="btn-secondary w-auto px-5 whitespace-nowrap">
                Batal
            </a>
        @endif
        <button type="submit" class="btn-primary w-auto px-5 whitespace-nowrap">
            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M4 2.75A1.75 1.75 0 0 0 2.25 4.5v11A1.75 1.75 0 0 0 4 17.25h12a1.75 1.75 0 0 0 1.75-1.75V6.56a1.75 1.75 0 0 0-.513-1.237l-2.31-2.31A1.75 1.75 0 0 0 13.69 2.5H4Z" />
                <path fill="#0f766e" d="M6 3.5h7v3.75H6V3.5ZM6 11h8v5.5H6V11Z" />
            </svg>
            {{ $bapEdit ? 'Perbarui' : 'Simpan' }}
        </button>
    </div>
</form>

<div data-table-card class="mt-5 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
            <h4 class="text-base font-extrabold tracking-tight text-slate-900">Daftar Berita Acara Persetujuan Senat</h4>
            <p class="mt-1 text-xs text-slate-500">BAP yang sudah disimpan, terbaru di atas.</p>
        </div>

        <label class="relative block">
            <span class="sr-only">Cari berita acara</span>
            <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-slate-400">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="10.5" cy="10.5" r="6.5" />
                    <path d="m16 16 4 4" stroke-linecap="round" />
                </svg>
            </span>
            <input type="search" data-table-search placeholder="Cari nomor atau dosen…"
                   class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50/60 pr-9 pl-9 text-xs text-slate-700 transition placeholder:text-slate-400 hover:border-slate-300 focus:border-teal-500 focus:bg-white focus:ring-4 focus:ring-teal-500/10 focus:outline-none sm:w-72">
            <button type="button" data-table-search-clear class="absolute top-1/2 right-2 hidden size-6 -translate-y-1/2 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600" aria-label="Bersihkan pencarian">
                <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                </svg>
            </button>
        </label>
    </div>

    <div class="overflow-x-auto px-2 pb-2 sm:px-4">
        <table data-datatable data-page-length="10" class="w-full text-left text-sm">
            <thead>
                <tr>
                    <th scope="col">Nomor BAP</th>
                    <th scope="col">Nama Dosen</th>
                    <th scope="col">Tanggal</th>
                    <th scope="col" class="no-sort text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bapList as $bap)
                    <tr>
                        <td>
                            <p class="text-sm font-bold text-slate-700">{{ $bap->nomor_bap }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $bap->bidang_keilmuan }}</p>
                        </td>
                        <td>
                            <p class="text-sm font-bold text-slate-700">{{ $bap->dosen?->name ?? '—' }}</p>
                            <p class="mt-0.5 font-mono text-[11px] text-slate-400">{{ $bap->dosen?->nidn }}</p>
                        </td>
                        <td>
                            <p class="font-semibold text-slate-600">{{ $bap->tanggal_bap?->format('d-m-Y') }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                {{ count($bap->anggota_senat_ids ?? []) }} anggota senat
                            </p>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('surat.bap-senat.cetak', $bap) }}"
                                   target="_blank" rel="noopener" title="Print" aria-label="Print"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M7 9V4h10v5" stroke-linejoin="round" />
                                        <rect x="4" y="9" width="16" height="7" rx="2" />
                                        <path d="M7 14h10v5.5H7z" stroke-linejoin="round" />
                                    </svg>
                                </a>
                                <a href="{{ route('surat.index', ['edit_bap' => $bap->id]) }}#bap-senat"
                                   title="Edit" aria-label="Edit"
                                   class="inline-flex items-center justify-center rounded-lg p-2 text-slate-500 transition hover:bg-teal-50 hover:text-teal-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4 20h4.2L19 9.2a2.1 2.1 0 0 0-3-3L5.2 17 4 20Z" stroke-linejoin="round" />
                                        <path d="m13.8 7.4 2.8 2.8" stroke-linecap="round" />
                                    </svg>
                                </a>
                                <form method="POST" action="{{ route('surat.bap-senat.destroy', $bap) }}"
                                      data-confirm="Hapus BAP senat nomor {{ $bap->nomor_bap }}? Tindakan ini tidak dapat dibatalkan.">
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
                        <td colspan="4" class="px-5 py-12 text-center">
                            <p class="text-sm font-bold text-slate-700">Belum ada berita acara persetujuan senat</p>
                            <p class="mt-1 text-xs text-slate-400">Isi formulir di atas untuk menambahkan BAP pertama.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>