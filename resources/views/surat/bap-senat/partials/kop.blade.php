{{-- Kop surat BAP Senat: logo instansi, nama badan penyelenggara, nama instansi, kontak. --}}
<div class="flex items-center gap-3">
    @if ($instansi?->logo_base64)
        <img src="{{ $instansi->logoDataUri() }}" alt="Logo {{ $instansi->nama_instansi }}"
             class="size-[2.4cm] shrink-0 object-contain">
    @endif

    <div class="flex-1 text-center leading-snug">
        <p class="text-[12px] font-bold uppercase">Perkumpulan Penyelenggara Lembaga Pendidikan</p>
        <p class="text-[12px] font-bold uppercase">Perguruan Tinggi PGRI Pacitan</p>
        <p class="text-[15px] font-bold uppercase">{{ $instansi?->nama_instansi ?: 'Nama Instansi Belum Diisi' }}</p>
        <p class="text-[9px]">
            {{ collect([$instansi?->alamat, $instansi?->website, $instansi?->no_telp])->filter()->implode(' · ') }}
        </p>
    </div>
</div>

{{-- Garis ganda pemisah kop. --}}
<div class="mt-1.5 border-t-2 border-slate-900"></div>
<div class="mt-0.5 border-t border-slate-900"></div>
