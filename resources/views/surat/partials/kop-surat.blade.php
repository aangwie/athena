{{-- Kop surat bersama seluruh naskah cetak halaman Surat: logo instansi, nama badan --}}
{{-- penyelenggara, nama instansi, dan kontak. Huruf mengikuti naskah cetak (Times New --}}
{{-- Roman) dengan ukuran baris: 16px badan penyelenggara, 18px nama instansi, 10px alamat. --}}
{{-- $senatAkademik: dokumen BAP Senat memakai logo senat dan awalan "Senat Akademik". --}}
@php
    $senatAkademik = $senatAkademik ?? false;
    $namaKop = $instansi?->nama_instansi ?: 'Nama Instansi Belum Diisi';

    // Dokumen senat memakai logo yang diunggah di halaman Instansi > Senat;
    // bila belum diunggah, kop memakai logo instansi supaya tidak kosong.
    $logoSrc = $senatAkademik
        ? ($instansi?->logoSenatDataUri() ?: $instansi?->logoDataUri())
        : $instansi?->logoDataUri();
@endphp

<div class="flex items-center gap-3">
    @if ($logoSrc)
        <img src="{{ $logoSrc }}" alt="Logo {{ $namaKop }}"
             class="size-[2.4cm] shrink-0 object-contain">
    @endif

    <div class="flex-1 text-center leading-snug">
        <p class="text-[16px] font-bold uppercase">Perkumpulan Penyelenggara Lembaga Pendidikan</p>
        <p class="text-[16px] font-bold uppercase">Perguruan Tinggi PGRI Pacitan</p>
        <p class="text-[18px] font-bold uppercase">{{ $senatAkademik ? 'Senat Akademik '.$namaKop : $namaKop }}</p>
        <p class="text-[10px]">
            {{ collect([$instansi?->alamat, $instansi?->website, $instansi?->no_telp])->filter()->implode(' · ') }}
        </p>
    </div>
</div>

{{-- Garis ganda pemisah kop. --}}
<div class="mt-1.5 border-t-2 border-slate-900"></div>
<div class="mt-0.5 border-t border-slate-900"></div>
