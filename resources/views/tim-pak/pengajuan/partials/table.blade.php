{{-- Tabel pengajuan DUPAK masuk untuk TIM PAK: dipakai di menu Pengajuan Masuk dan dashboard. --}}
<table data-datatable data-page-length="10" class="w-full text-left text-sm">
    <thead>
        <tr>
            <th scope="col">Dosen</th>
            <th scope="col">Periode</th>
            <th scope="col">Status</th>
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
                <td>
                    <span class="badge-status {{ $item->statusClasses() }}">{{ $item->statusLabel() }}</span>
                </td>
                <td data-order="{{ $item->diajukan_at?->timestamp ?? 0 }}" class="whitespace-nowrap">
                    <span class="text-xs text-slate-500">{{ $item->diajukan_at?->translatedFormat('d M Y') ?? '—' }}</span>
                </td>
                <td class="text-right">
                    <a href="{{ route('tim-pak.pengajuan.show', $item) }}" class="btn-small">
                        {{ $item->nilaiTerkunci() ? 'Lihat' : ($item->status === 'diajukan' ? 'Nilai' : 'Lihat / Ubah') }}
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-5 py-16 text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-slate-50 text-slate-300">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path d="M9 5H6.5A1.5 1.5 0 0 0 5 6.5v13A1.5 1.5 0 0 0 6.5 21h11a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 17.5 5H15" stroke-linecap="round" />
                            <rect x="9" y="3" width="6" height="4" rx="1.5" />
                            <path d="M9 12h6M9 16h4" stroke-linecap="round" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-700">Belum ada pengajuan masuk</p>
                    <p class="mt-1 text-xs text-slate-400">Usulan dosen akan tampil di sini setelah dikirim ke TIM PAK.</p>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
