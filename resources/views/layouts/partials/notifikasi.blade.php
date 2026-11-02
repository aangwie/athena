@php
    $notifikasi = \App\Support\Notifikasi::untuk(auth()->user());
    $belumDibaca = $notifikasi->where('baru', true)->count();
@endphp

<div class="relative">
    <button type="button" data-notif-button data-notif-url="{{ route('notifikasi.baca') }}" aria-expanded="false" aria-haspopup="true"
            aria-label="Notifikasi ({{ $belumDibaca }} baru)"
            class="relative flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-teal-300 hover:text-teal-700">
        <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M18 8.5a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7Z" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M10 19a2.2 2.2 0 0 0 4 0" stroke-linecap="round" />
        </svg>
        <span data-notif-badge @class(['absolute -top-1 -right-1 flex min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 py-0.5 text-[9px] leading-none font-bold text-white ring-2 ring-white', 'hidden' => $belumDibaca === 0])>
            {{ $belumDibaca > 9 ? '9+' : $belumDibaca }}
        </span>
    </button>

    <div data-notif-menu class="absolute top-[calc(100%+0.5rem)] right-0 hidden w-80 max-w-[calc(100vw-2rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-4 py-3">
            <p class="text-sm font-bold text-slate-800">Notifikasi</p>
            <span data-notif-summary class="rounded-md bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-700">{{ $belumDibaca }} baru</span>
        </div>

        <div class="max-h-80 overflow-y-auto">
            @forelse ($notifikasi as $item)
                <a href="{{ $item['url'] }}" class="flex gap-3 border-b border-slate-100 px-4 py-3 transition last:border-0 hover:bg-slate-50">
                    <span @class(['mt-1 size-2 shrink-0 rounded-full', 'bg-rose-500' => $item['baru'], 'bg-slate-200' => ! $item['baru']])></span>
                    <span class="min-w-0">
                        <span class="block text-xs font-bold text-slate-800">{{ $item['judul'] }}</span>
                        <span class="mt-0.5 block truncate text-[11px] text-slate-500">{{ $item['keterangan'] }}</span>
                        <span class="mt-0.5 block text-[10px] text-slate-400">{{ $item['waktu']->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <p class="px-4 py-6 text-center text-xs text-slate-400">Belum ada notifikasi.</p>
            @endforelse
        </div>
    </div>
</div>