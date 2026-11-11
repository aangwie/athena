{{-- Kop surat bersama (BAP Senat & BAKI): logo instansi, nama badan penyelenggara, nama instansi, kontak. --}}
{{-- $senatAkademik: dokumen BAP Senat memakai logo senat, awalan "Senat Akademik", dan huruf Times New Roman. --}}
@php
    $senatAkademik = $senatAkademik ?? false;
    $namaKop = $instansi?->nama_instansi ?: 'Nama Instansi Belum Diisi';

    // Dokumen senat memakai logo yang diunggah di halaman Instansi > Senat;
    // bila belum diunggah, kop memakai logo instansi supaya tidak kosong.
    $logoSrc = $senatAkademik
        ? ($instansi?->logoSenatDataUri() ?: $instansi?->logoDataUri())
        : $instansi?->logoDataUri();

    // Naskah dinas senat memakai Times New Roman; dokumen lain tetap memakai font aplikasi.
    $gayaKop = $senatAkademik ? 'font-family: Times New Roman, Times, serif;' : null;
@endphp

<div class="flex items-center gap-3" @if ($gayaKop) style="{{ $gayaKop }}" @endif>
    @if ($logoSrc)
        <img src="{{ $logoSrc }}" alt="Logo {{ $namaKop }}"
             class="size-[2.4cm] shrink-0 object-contain">
    @endif

    <div class="flex-1 text-center leading-snug">
        <p class="text-[12px] font-bold uppercase">Perkumpulan Penyelenggara Lembaga Pendidikan</p>
        <p class="text-[12px] font-bold uppercase">Perguruan Tinggi PGRI Pacitan</p>
        <p class="text-[15px] font-bold uppercase">{{ $senatAkademik ? 'Senat Akademik '.$namaKop : $namaKop }}</p>
        <p class="text-[9px]">
            {{ collect([$instansi?->alamat, $instansi?->website, $instansi?->no_telp])->filter()->implode(' · ') }}
        </p>
    </div>
</div>

{{-- Garis ganda pemisah kop. --}}
<div class="mt-1.5 border-t-2 border-slate-900"></div>
<div class="mt-0.5 border-t border-slate-900"></div>
