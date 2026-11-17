{{-- Tabel ajuan PAK untuk admin: meneruskan ajuan dosen ke satu anggota TIM PAK. --}}
<table data-datatable data-page-length="10" class="w-full text-left text-sm">
    <thead>
        <tr>
            <th scope="col">Dosen</th>
            <th scope="col">Periode</th>
            <th scope="col">Status</th>
            <th scope="col">TIM PAK</th>
            <th scope="col">Diajukan</th>
            <th scope="col" class="no-sort text-right">Aksi</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($pengajuan as $item)
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
                <td class="whitespace-nowrap">
                    <span class="badge-status {{ $item->statusClasses() }}">{{ $item->statusLabel() }}</span>
                </td>
                <td>
                    @if ($item->timPak)
                        <div class="flex items-center gap-2">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[10px] font-extrabold text-slate-600 ring-1 ring-slate-200">
                                {{ $item->timPak->initials() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-semibold text-slate-700">{{ $item->timPak->name }}</p>
                                <p class="text-[10px] text-slate-400">TIM PAK</p>
                            </div>
                        </div>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-600">
                            <span class="size-1.5 rounded-full bg-amber-400"></span>
                            Belum ditugaskan
                        </span>
                    @endif
                </td>
                <td data-order="{{ $item->diajukan_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                    <span class="text-xs text-slate-500">{{ $item->diajukan_at?->translatedFormat('d M Y') ?? '—' }}</span>
                </td>
                <td class="text-right">
                    @if ($item->status === 'menunggu_penugasan')
                        {{-- Panel form terbuka di bawah baris agar tidak terpotong overflow tabel. --}}
                        <details name="ajuan-pak" class="group inline-block text-left">
                            <summary class="btn-small cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M4.5 12h13.5M13 6.5 18.5 12 13 17.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Tugaskan
                            </summary>

                            <form method="POST" action="{{ route('ajuan-pak.tugaskan', $item) }}"
                                  class="mt-3 w-72 rounded-xl border border-slate-200 bg-white p-3.5 shadow-sm">
                                @csrf
                                @method('PUT')

                                <p class="text-[10px] font-bold tracking-[0.12em] text-slate-400 uppercase">Teruskan ajuan</p>
                                <p class="mt-1 truncate text-xs font-bold text-slate-700">{{ $item->user->name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $item->periodeLabel() }}</p>

                                <label for="tim-pak-{{ $item->id }}" class="form-label mt-3">
                                    Anggota TIM PAK <span class="text-rose-500">*</span>
                                </label>
                                <select id="tim-pak-{{ $item->id }}" name="tim_pak_id" required
                                        data-select-search="Cari nama TIM PAK…"
                                        @class(['form-select', 'border-rose-300 bg-rose-50/40' => $errors->has('tim_pak_id')])>
                                    <option value="">Pilih TIM PAK</option>
                                    @foreach ($timPak as $pak)
                                        <option value="{{ $pak->id }}">{{ $pak->name }}</option>
                                    @endforeach
                                </select>

                                @if ($timPak->isEmpty())
                                    <p class="mt-1.5 text-[11px] font-medium text-amber-600">
                                        Belum ada pengguna TIM PAK aktif. Tambahkan peran TIM PAK pada menu Data Dosen.
                                    </p>
                                @else
                                    <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                        Setelah diteruskan, hanya TIM PAK yang dipilih yang dapat melihat dan menilai ajuan ini.
                                    </p>
                                @endif

                                <button type="submit" class="btn-primary mt-3 py-2 text-xs" @disabled($timPak->isEmpty())>
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M4.5 12h13.5M13 6.5 18.5 12 13 17.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Teruskan ke TIM PAK
                                </button>
                            </form>
                        </details>
                    @else
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600">
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
                            </svg>
                            Sudah diteruskan
                        </span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-5 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v13A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                            <rect x="9" y="3" width="6" height="4" rx="1.5" />
                            <path d="M9 12h6M9 16h4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada ajuan PAK</p>
                    <p class="mt-1 text-xs text-slate-400">Ajuan dosen akan tampil di sini setelah dikirim ke TIM PAK.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>