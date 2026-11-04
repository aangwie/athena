@extends('layouts.app')

@section('title', 'WhatsApp API')
@section('breadcrumb', 'Pengaturan · WhatsApp API')
@section('page-title', 'WhatsApp API')

@section('content')
    <div class="mx-auto max-w-5xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengaturan
            </span>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">WhatsApp API</h2>
                    <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                        Hubungkan aplikasi ke WhatsApp gateway, pantau status koneksi secara realtime, dan uji pengiriman pesan.
                    </p>
                </div>
                <span @class([
                    'inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-[11px] font-bold',
                    'bg-teal-50 text-teal-700 ring-1 ring-teal-200' => $gatewayUrl,
                    'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => ! $gatewayUrl,
                ])>
                    <span class="size-1.5 rounded-full {{ $gatewayUrl ? 'bg-teal-500' : 'bg-amber-400' }}"></span>
                    {{ $gatewayUrl ? 'Gateway tersimpan' : 'Gateway belum diatur' }}
                </span>
            </div>
        </div>

        <section data-tabs data-wa-page
                 data-status-url="{{ route('whatsapp.status') }}"
                 data-qr-url="{{ route('whatsapp.qr') }}"
                 data-putus-url="{{ route('whatsapp.putus') }}"
                 data-kirim-url="{{ route('whatsapp.kirim') }}"
                 class="section-card overflow-hidden">
            <div role="tablist" aria-label="Menu WhatsApp API"
                 class="flex flex-wrap gap-1.5 border-b border-slate-100 bg-slate-50/60 px-3 py-3 sm:px-4">
                <button type="button" role="tab" id="tab-konfigurasi" aria-controls="panel-konfigurasi"
                        aria-selected="true" tabindex="0" data-tab-target="konfigurasi"
                        class="tab-button tab-button-active">
                    <span class="flex size-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">1</span>
                    <span class="whitespace-nowrap">Konfigurasi Gateway</span>
                </button>
                <button type="button" role="tab" id="tab-uji-kirim" aria-controls="panel-uji-kirim"
                        aria-selected="false" tabindex="-1" data-tab-target="uji-kirim"
                        class="tab-button">
                    <span class="flex size-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">2</span>
                    <span class="whitespace-nowrap">Uji Pengiriman</span>
                </button>
            </div>

            {{-- Panel 1: konfigurasi gateway, status, dan QR --}}
            <div role="tabpanel" id="panel-konfigurasi" aria-labelledby="tab-konfigurasi"
                 data-tab-panel="konfigurasi" tabindex="0" class="p-5 sm:p-6">
                <div class="space-y-5">
                    {{-- URL gateway --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 ring-1 ring-teal-100">
                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8.5" />
                                    <path d="M3.8 12h16.4M12 3.5c2.1 2.3 3.2 5.3 3.2 8.5S14.1 18.2 12 20.5C9.9 18.2 8.8 15.2 8.8 12S9.9 5.8 12 3.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-sm font-extrabold text-slate-800">URL Gateway</h3>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                    Alamat dasar REST API gateway, tanpa garis miring di akhir. Skema <span class="font-mono">http://</span>
                                    ditambahkan otomatis bila tidak ditulis.
                                </p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('whatsapp.simpan') }}" class="mt-4 space-y-2">
                            @csrf
                            <label for="url_gateway" class="form-label">
                                URL gateway <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <input type="text" id="url_gateway" name="url_gateway" required
                                       value="{{ old('url_gateway', $gatewayUrl) }}"
                                       placeholder="http://localhost:3000"
                                       autocomplete="off" spellcheck="false"
                                       @class([
                                           'form-input font-mono sm:flex-1',
                                           'border-rose-300 bg-rose-50/40' => $errors->has('url_gateway'),
                                       ])>
                                <button type="submit" class="btn-primary w-auto shrink-0 justify-center px-5 whitespace-nowrap">
                                    Simpan URL
                                </button>
                            </div>
                            @error('url_gateway') <p class="form-error">{{ $message }}</p> @enderror
                            <p class="text-[11px] leading-relaxed text-slate-400">
                                Contoh: <span class="font-mono text-slate-500">http://localhost:3000</span> atau
                                <span class="font-mono text-slate-500">https://wa.domain.com</span>.
                                Halaman ini memakai endpoint <span class="font-mono text-slate-500">/status</span>,
                                <span class="font-mono text-slate-500">/qr</span>, dan
                                <span class="font-mono text-slate-500">/send-message</span>; variasi jalur umum
                                (termasuk <span class="font-mono text-slate-500">/status/default</span> dan
                                <span class="font-mono text-slate-500">/qr/default</span> untuk gateway multi-sesi)
                                dicoba otomatis. Nama sesi/device aktif: <span class="font-mono font-semibold text-slate-500">{{ $sesi }}</span>
                                (diatur lewat <span class="font-mono text-slate-500">WHATSAPP_SESSION</span> pada berkas
                                <span class="font-mono text-slate-500">.env</span>).
                            </p>
                        </form>
                    </div>

                    {{-- Kartu status realtime --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{-- Status URL gateway --}}
                        <article class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-white text-slate-500 ring-1 ring-slate-200">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M4.5 9.5h15M4.5 14.5h15M7 5.5h10a2.5 2.5 0 0 1 2.5 2.5v8a2.5 2.5 0 0 1-2.5 2.5H7A2.5 2.5 0 0 1 4.5 16V8A2.5 2.5 0 0 1 7 5.5Z" stroke-linecap="round" />
                                            <circle cx="8" cy="12" r=".9" fill="currentColor" stroke="none" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700">Koneksi Gateway</p>
                                        <p class="text-[10px] text-slate-400">Realtime · 10 detik</p>
                                    </div>
                                </div>
                                <span data-wa-gateway-badge class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                    Memeriksa…
                                </span>
                            </div>

                            <dl class="mt-4 space-y-2 text-[11px]">
                                <div class="flex items-start justify-between gap-3">
                                    <dt class="shrink-0 text-slate-400">Alamat</dt>
                                    <dd data-wa-gateway-endpoint class="truncate text-right font-mono font-medium text-slate-600">—</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Latensi</dt>
                                    <dd data-wa-gateway-latency class="font-semibold text-slate-600">—</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Diperiksa</dt>
                                    <dd data-wa-gateway-time class="font-semibold text-slate-600">—</dd>
                                </div>
                            </dl>

                            <p data-wa-gateway-message class="mt-3 line-clamp-2 text-[11px] leading-relaxed text-slate-400">
                                Menunggu pemeriksaan pertama…
                            </p>
                        </article>

                        {{-- Status perangkat WhatsApp --}}
                        <article class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-white text-emerald-600 ring-1 ring-slate-200">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path d="M20.5 11.6a8.5 8.5 0 0 1-12.5 7.6L3.5 20.5l1.4-4.4A8.5 8.5 0 1 1 20.5 11.6Z" stroke-linejoin="round" />
                                            <path d="M8.6 8.4c.2-.4.4-.4.7-.4h.5c.2 0 .4.1.5.4l.7 1.6c.1.3 0 .5-.2.7l-.5.6c.5 1 1.3 1.8 2.3 2.3l.6-.5c.2-.2.4-.3.7-.2l1.6.7c.3.1.4.3.4.5v.5c0 .3 0 .5-.4.7-.5.3-1.1.4-1.7.3-2.9-.6-5.1-2.8-5.7-5.7-.1-.6 0-1.2.3-1.7Z" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="text-xs font-bold text-slate-700">Perangkat WhatsApp</p>
                                        <p class="text-[10px] text-slate-400">Status sesi gateway</p>
                                    </div>
                                </div>
                                <span data-wa-connection-badge class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                    Memeriksa…
                                </span>
                            </div>

                            <dl class="mt-4 space-y-2 text-[11px]">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Sesi / Device</dt>
                                    <dd class="font-mono font-semibold text-slate-600">{{ $sesi }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Nomor</dt>
                                    <dd data-wa-connection-number class="font-mono font-semibold text-slate-600">—</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Nama</dt>
                                    <dd data-wa-connection-name class="font-semibold text-slate-600">—</dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-slate-400">Diperiksa</dt>
                                    <dd data-wa-connection-time class="font-semibold text-slate-600">—</dd>
                                </div>
                            </dl>

                            <p data-wa-connection-message class="mt-3 line-clamp-2 text-[11px] leading-relaxed text-slate-400">
                                Menunggu pemeriksaan pertama…
                            </p>
                        </article>
                    </div>

                    {{-- QR code --}}
                    <div class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-slate-200">
                                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <rect x="3.5" y="3.5" width="6" height="6" rx="1" />
                                        <rect x="14.5" y="3.5" width="6" height="6" rx="1" />
                                        <rect x="3.5" y="14.5" width="6" height="6" rx="1" />
                                        <path d="M14.5 14.5h2.5v2.5h-2.5zM20.5 14.5v.01M14.5 20.5v.01M20.5 20.5v.01M17.5 20.5v.01M20.5 17.5v.01" stroke-linecap="round" />
                                    </svg>
                                </span>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-800">QR Code</h3>
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                        Pindai untuk menautkan perangkat WhatsApp ke gateway. Gunakan "Putuskan Koneksi" untuk melepas perangkat dan menampilkan QR baru.
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" data-wa-qr-refresh class="btn-secondary shrink-0">
                                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M20 12a8 8 0 1 1-2.5-5.8M20 4v4h-4" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Muat Ulang QR
                                </button>
                                <button type="button" data-wa-putus class="btn-secondary shrink-0 text-rose-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700">
                                    <svg data-wa-putus-icon class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path d="M12 3.5V11M7.4 6.2a7.5 7.5 0 1 0 9.2 0" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <svg data-wa-putus-spinner class="hidden size-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-25" />
                                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                    </svg>
                                    <span data-wa-putus-label>Putuskan Koneksi</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 grid items-center gap-5 sm:grid-cols-[minmax(0,18rem)_minmax(0,1fr)]">
                            <div class="relative mx-auto flex aspect-square w-full max-w-72 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-3">
                                <img data-wa-qr-image src="" alt="QR code WhatsApp" class="hidden h-full w-full rounded-lg bg-white object-contain">
                                <div data-wa-qr-placeholder class="flex h-full flex-col items-center justify-center px-4 text-center">
                                    <svg class="mb-2 size-9 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <rect x="3.5" y="3.5" width="6" height="6" rx="1" />
                                        <rect x="14.5" y="3.5" width="6" height="6" rx="1" />
                                        <rect x="3.5" y="14.5" width="6" height="6" rx="1" />
                                        <path d="M14.5 14.5h2.5v2.5h-2.5zM20.5 14.5v.01M14.5 20.5v.01M20.5 20.5v.01" stroke-linecap="round" />
                                    </svg>
                                    <p data-wa-qr-placeholder-text class="text-[11px] leading-relaxed font-medium text-slate-400">
                                        QR code akan tampil setelah status gateway diperiksa.
                                    </p>
                                </div>
                                <div data-wa-qr-loading class="absolute inset-0 hidden items-center justify-center bg-white/80 backdrop-blur-sm">
                                    <svg class="size-6 animate-spin text-teal-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-20" />
                                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                    </svg>
                                </div>
                            </div>

                            <div class="min-w-0">
                                <p data-wa-qr-status class="text-sm leading-relaxed font-semibold text-slate-700">
                                    Menunggu status koneksi…
                                </p>
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-400">
                                    QR diperbarui otomatis setiap 20 detik selama WhatsApp belum terhubung. Setelah berhasil
                                    dipindai, blok ini akan berubah menjadi status terhubung.
                                </p>

                                <dl class="mt-4 space-y-2 text-[11px]">
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="shrink-0 text-slate-400">Endpoint QR</dt>
                                        <dd data-wa-qr-endpoint class="truncate text-right font-mono font-medium text-slate-600">—</dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <dt class="text-slate-400">Diperbarui</dt>
                                        <dd data-wa-qr-time class="font-semibold text-slate-600">—</dd>
                                    </div>
                                </dl>

                                <a data-wa-qr-link href="#" target="_blank" rel="noopener"
                                   class="mt-3 hidden items-center gap-1 text-[11px] font-bold text-teal-700 transition hover:text-teal-800">
                                    Buka QR langsung di gateway
                                    <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <details class="mt-4 rounded-lg border border-slate-200 bg-slate-50/60">
                            <summary class="cursor-pointer list-none px-3.5 py-2.5 text-[11px] font-bold text-slate-500 select-none transition hover:text-slate-700 [&::-webkit-details-marker]:hidden">
                                Lihat respons status mentah
                            </summary>
                            <pre data-wa-raw class="max-h-60 overflow-auto border-t border-slate-200 px-3.5 py-3 font-mono text-[10.5px] leading-relaxed whitespace-pre-wrap text-slate-600">Belum ada respons.</pre>
                        </details>
                    </div>
                </div>
            </div>

            {{-- Panel 2: uji pengiriman --}}
            <div role="tabpanel" id="panel-uji-kirim" aria-labelledby="tab-uji-kirim"
                 data-tab-panel="uji-kirim" tabindex="0" class="hidden p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <form data-wa-test-form class="rounded-xl border border-slate-200 bg-white p-4 sm:p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700 ring-1 ring-teal-100">
                                <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M20.5 11.6a8.5 8.5 0 0 1-12.5 7.6L3.5 20.5l1.4-4.4A8.5 8.5 0 1 1 20.5 11.6Z" stroke-linejoin="round" />
                                    <path d="M8.5 12h7M12 8.5v7" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-800">Kirim Pesan Uji</h3>
                                <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
                                    Pesan benar-benar dikirim ke nomor tujuan melalui gateway yang tersimpan.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div>
                                <label for="wa_nomor" class="form-label">Nomor tujuan <span class="text-rose-500">*</span></label>
                                <input type="text" id="wa_nomor" name="nomor" required inputmode="tel"
                                       placeholder="0812-3456-7890"
                                       class="form-input font-mono">
                                <p class="mt-1.5 text-[11px] leading-relaxed text-slate-400">
                                    Awalan <span class="font-mono">0</span> otomatis diubah menjadi
                                    <span class="font-mono">62</span> sebelum dikirim.
                                </p>
                            </div>

                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <label for="wa_pesan" class="form-label mb-0">Pesan <span class="text-rose-500">*</span></label>
                                    <span data-wa-counter class="text-[10px] font-semibold text-slate-400">0/4096</span>
                                </div>
                                <textarea id="wa_pesan" name="pesan" rows="5" required maxlength="4096"
                                          placeholder="Contoh: Uji koneksi WhatsApp API Athena berhasil."
                                          class="form-input resize-y">Uji koneksi WhatsApp API Athena berhasil.</textarea>
                            </div>

                            <button type="submit" data-wa-test-submit class="btn-primary w-full justify-center">
                                <svg data-wa-test-icon class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="m4.5 12 15-7-5 15-3.5-6.5L4.5 12Z" stroke-linejoin="round" />
                                </svg>
                                <svg data-wa-test-spinner class="hidden size-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2.5" class="opacity-25" />
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
                                </svg>
                                <span data-wa-test-label>Kirim Pesan Uji</span>
                            </button>

                            <p data-wa-test-error class="hidden rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] leading-relaxed font-medium text-rose-600"></p>
                        </div>
                    </form>

                    <div data-wa-test-result class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50/50">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-white px-4 py-3">
                            <div>
                                <p class="text-xs font-bold text-slate-700">Respons Gateway</p>
                                <p class="text-[10px] text-slate-400">Hasil permintaan terakhir</p>
                            </div>
                            <span data-wa-test-badge class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-500">
                                Belum ada
                            </span>
                        </div>

                        <div class="space-y-3 p-4">
                            <dl class="grid grid-cols-3 gap-2 text-[11px]">
                                <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                    <dt class="text-slate-400">HTTP</dt>
                                    <dd data-wa-test-http class="mt-0.5 font-mono font-bold text-slate-700">—</dd>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                    <dt class="text-slate-400">Durasi</dt>
                                    <dd data-wa-test-duration class="mt-0.5 font-mono font-bold text-slate-700">—</dd>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                    <dt class="text-slate-400">Nomor</dt>
                                    <dd data-wa-test-number class="mt-0.5 truncate font-mono font-bold text-slate-700">—</dd>
                                </div>
                            </dl>

                            <p data-wa-test-message class="text-xs leading-relaxed font-semibold text-slate-600">
                                Kirim pesan uji untuk melihat respons dari gateway di sini.
                            </p>

                            <div>
                                <p class="mb-1.5 text-[10px] font-bold tracking-wide text-slate-400 uppercase">Endpoint</p>
                                <p data-wa-test-endpoint class="truncate rounded-lg border border-slate-200 bg-white px-2.5 py-2 font-mono text-[10.5px] text-slate-500">—</p>
                            </div>

                            <div>
                                <p class="mb-1.5 text-[10px] font-bold tracking-wide text-slate-400 uppercase">Isi respons</p>
                                <pre data-wa-test-raw class="max-h-72 overflow-auto rounded-lg border border-slate-800 bg-slate-950 px-3 py-2.5 font-mono text-[10.5px] leading-relaxed whitespace-pre-wrap text-slate-300">Belum ada respons.</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/whatsapp.js')
@endpush
