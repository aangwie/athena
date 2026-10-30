@extends('layouts.app')

@section('title', 'Panduan Pengajuan')
@section('breadcrumb', 'Panduan Pengajuan')
@section('page-title', 'Panduan Pengajuan')

@section('content')
    @php
        $alur = ['Pilih periode', 'Isi 4 bidang', 'Review', 'TIM PAK', 'Ajukan DUPAK', 'Verifikasi admin'];

        $langkah = [
            [
                'judul' => 'Pilih periode dan semester',
                'poin' => [
                    'Buka menu Pengajuan Baru pada sidebar, atau tombol Pengajuan Baru di dashboard.',
                    'Tentukan Tahun Akademik (lima tahun terakhir) dan Semester: Ganjil atau Genap. Pilihan awal mengikuti tanggal hari ini.',
                    'Klik Lanjut Isi Komponen. Periode yang sama dipakai untuk semua tab; klik Ubah Periode bila keliru.',
                ],
            ],
            [
                'judul' => 'Isi tab Pendidikan (Bidang A dan B)',
                'poin' => [
                    'Bidang A memuat Pendidikan Formal dan Diklat Prajabatan. Pilih komponen, tulis nama kegiatan, lalu tempel link bukti.',
                    'Di tab yang sama ada Bidang B — Pelaksanaan Pendidikan: perkuliahan, membimbing seminar/PKL/KKN/skripsi, penguji ujian akhir, membina kegiatan mahasiswa, pengembangan program kuliah dan bahan ajar.',
                    'Beberapa komponen punya rincian wajib, misalnya Pembimbing Utama/Pendamping atau Ketua/Anggota Penguji.',
                    'Klik Tambah untuk menyimpan. Entri tampil di tabel bawah tab dan bisa diubah atau dihapus selama belum disetujui admin.',
                ],
            ],
            [
                'judul' => 'Isi tab Penelitian (Bidang C)',
                'poin' => [
                    'Komponen: menghasilkan karya ilmiah, menerjemahkan/menyadur buku, menyunting buku, paten, rancangan karya teknologi, dan karya seni.',
                    'Untuk karya ilmiah, lengkapi rincian publikasi (dipublikasikan atau tidak dipublikasikan) beserta link bukti artikel/jurnal.',
                    'Satu luaran satu entri. Bila satu penelitian menghasilkan dua luaran berbeda, buat dua entri terpisah.',
                ],
            ],
            [
                'judul' => 'Isi tab Pengabdian kepada Masyarakat (Bidang D)',
                'poin' => [
                    'Komponen: jabatan pimpinan, pengembangan hasil pendidikan/penelitian, latihan–penyuluhan–penataran, pelayanan kepada masyarakat, dan karya pengabdian.',
                    'Beberapa komponen meminta rincian jangka waktu (satu semester atau lebih / kurang dari satu semester) atau dasar pelaksanaan.',
                    'Sertakan link bukti: surat tugas, laporan, atau dokumentasi kegiatan.',
                ],
            ],
            [
                'judul' => 'Isi tab Penunjang (Bidang E)',
                'poin' => [
                    'Komponen penunjang: organisasi profesi, penghargaan, dan tugas tambahan yang mendukung pelaksanaan tugas dosen.',
                    'Nama kegiatan sebaiknya menyebut peran dan tingkatannya, contoh: Anggota Panitia Seminar Nasional Teknologi Pendidikan.',
                    'Link bukti berupa SK, sertifikat, atau surat undangan.',
                ],
            ],
            [
                'judul' => 'Periksa tab Review',
                'poin' => [
                    'Tab Review berada paling kanan dan merangkum seluruh entri dari keempat bidang dalam satu formulir.',
                    'Periksa kesesuaian unsur, sub unsur, nama kegiatan, dan link bukti. Bila ada yang salah, kembali ke tab bidangnya lalu klik ubah pada entri tersebut.',
                    'Panel bawah menampilkan status usulan periode ini. Belum diajukan berarti usulan belum dikirim ke TIM PAK.',
                ],
            ],
            [
                'judul' => 'Ajukan ke TIM PAK',
                'poin' => [
                    'Tekan Ajukan ke TIM PAK pada panel Review, lalu setujui konfirmasi yang muncul.',
                    'Status berubah menjadi Menunggu penilaian dan tanggal pengajuan tercatat.',
                    'Periode tanpa entri tidak dapat diajukan — sistem menolak dengan pesan “Belum ada kegiatan pada periode ini yang bisa diajukan”.',
                ],
            ],
            [
                'judul' => 'TIM PAK menilai angka kredit',
                'poin' => [
                    'TIM PAK membuka menu Penilaian → Pengajuan Masuk, lalu memilih usulan Anda.',
                    'Setiap entri dinilai pada kolom Baru (angka kredit), kemudian disimpan.',
                    'Status berubah menjadi Sudah dinilai dan tanggal dinilai muncul di tab Review serta Riwayat Pengajuan. Total nilai PAK adalah jumlah kolom Baru periode tersebut.',
                ],
            ],
            [
                'judul' => 'Ajukan DUPAK',
                'poin' => [
                    'Setelah status Sudah dinilai, tombol Ajukan Dupak pada panel Review menjadi aktif.',
                    'Klik Ajukan Dupak. Status berubah menjadi Diajukan DUPAK — berkas diteruskan ke admin untuk verifikasi.',
                    'DUPAK memakai nilai dari TIM PAK; dosen tidak mengisi ulang angka kredit.',
                ],
            ],
            [
                'judul' => 'Verifikasi oleh admin',
                'poin' => [
                    'Admin membuka menu Verifikasi DUPAK dan memeriksa rekap periode tersebut (read-only, nilai tidak diubah).',
                    'Admin memilih keputusan: Setujui, Butuh Perbaikan, atau Tolak. Alasan wajib diisi untuk dua keputusan terakhir.',
                    'Hasil keputusan tampil pada badge status di tab Review dan Riwayat Ajuan DUPAK.',
                ],
            ],
            [
                'judul' => 'Tindak lanjut hasil penilaian',
                'poin' => [
                    'Disetujui — nilai PAK terkunci: TIM PAK tidak bisa menilai ulang dan dosen tidak bisa mengajukan ulang. DUPAK siap dicetak.',
                    'Butuh Perbaikan / Ditolak — buka Riwayat Pengajuan → tab Riwayat Nilai PAK, klik Perbaikan untuk membetulkan entri, lalu Ajukan Ulang agar dinilai kembali oleh TIM PAK.',
                    'Setelah perbaikan dinilai, ajukan DUPAK kembali seperti langkah 9.',
                    'Cetak PDF tersedia lewat tombol Cetak Dupak ke PDF di tab Review dan tombol Cetak PDF di Riwayat Ajuan DUPAK.',
                ],
            ],
        ];
    @endphp

    <div class="mx-auto max-w-4xl">
        <div class="mb-6">
            <span class="mb-2 inline-flex items-center gap-2 text-[11px] font-bold tracking-[0.14em] text-teal-700 uppercase">
                <span class="h-px w-6 bg-teal-500"></span>
                Pengajuan Jabatan Akademik
            </span>
            <h2 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl">Panduan Pengajuan Jabatan Akademik</h2>
            <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                Alur lengkap dari pemilihan periode dan semester, pengisian komponen tiap bidang, sampai usulan dinilai TIM PAK dan diajukan ke admin.
            </p>
        </div>

        {{-- Alur ringkas dalam satu baris. --}}
        <div class="section-card mb-5 flex flex-wrap items-center gap-2 p-4 sm:p-5">
            @foreach ($alur as $tahap)
                <span class="inline-flex items-center gap-2 rounded-full bg-teal-50 px-3 py-1.5 text-[11px] font-bold text-teal-700 ring-1 ring-teal-100 ring-inset">
                    <span class="flex size-4 items-center justify-center rounded-full bg-teal-600 text-[9px] text-white">{{ $loop->iteration }}</span>
                    {{ $tahap }}
                </span>
                @unless ($loop->last)
                    <span class="text-slate-300" aria-hidden="true">→</span>
                @endunless
            @endforeach
        </div>

        <div class="space-y-4">
            @foreach ($langkah as $item)
                <section class="section-card p-5 sm:p-6">
                    <div class="mb-3 flex items-start gap-3">
                        <span class="section-number">{{ $loop->iteration }}</span>
                        <h3 class="mt-1 text-sm font-extrabold tracking-tight text-slate-900 sm:text-base">{{ $item['judul'] }}</h3>
                    </div>
                    <ul class="space-y-2">
                        @foreach ($item['poin'] as $poin)
                            <li class="flex gap-2 text-sm leading-relaxed text-slate-600">
                                <span class="mt-2 size-1.5 shrink-0 rounded-full bg-teal-400" aria-hidden="true"></span>
                                <span>{{ $poin }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        {{-- Acuan badge status yang tampil di tab Review dan Riwayat Pengajuan. --}}
        <section class="section-card mt-4 overflow-hidden">
            <div class="section-heading">
                <span class="section-number">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M4.5 6.5h15M4.5 12h15M4.5 17.5h9" stroke-linecap="round" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Arti status usulan</h3>
                    <p class="mt-0.5 text-xs text-slate-400">Badge status muncul di tab Review, dashboard, dan Riwayat Pengajuan.</p>
                </div>
            </div>

            <div class="overflow-x-auto p-2 sm:p-4">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr>
                            <th scope="col" class="w-52">Status</th>
                            <th scope="col">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Models\PengajuanDupak::STATUS as $status => $label)
                            <tr>
                                <td class="whitespace-nowrap">
                                    <span class="badge-status {{ (new \App\Models\PengajuanDupak(['status' => $status]))->statusClasses() }}">{{ $label }}</span>
                                </td>
                                <td class="text-xs leading-relaxed text-slate-600">
                                    {{ match ($status) {
                                        'diajukan' => 'Usulan sudah dikirim ke TIM PAK dan menunggu penilaian angka kredit.',
                                        'dinilai' => 'Nilai PAK sudah disimpan TIM PAK. DUPAK siap diajukan ke admin.',
                                        'diajukan_dupak' => 'DUPAK sudah diajukan dan menunggu keputusan admin.',
                                        'disetujui' => 'DUPAK disetujui. Nilai PAK terkunci, DUPAK siap dicetak.',
                                        'perlu_perbaikan' => 'Admin meminta perbaikan. Betulkan entri, lalu ajukan ulang ke TIM PAK.',
                                        'ditolak' => 'DUPAK ditolak. Perbaiki entri, lalu ajukan ulang ke TIM PAK.',
                                        default => '—',
                                    } }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Catatan yang paling sering jadi penyebab usulan dikembalikan. --}}
        <section class="section-card mt-4 p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Catatan penting</h3>
            <ul class="mt-3 space-y-2">
                @foreach ([
                    'Link bukti wajib bisa dibuka tanpa login (mis. Google Drive dengan akses “siapa saja yang memiliki link”) agar TIM PAK dan admin dapat memeriksa.',
                    'Satu periode (tahun akademik + semester) hanya punya satu usulan. Seluruh entri periode tersebut masuk ke usulan yang sama.',
                    'Entri masih bisa diubah atau dihapus selama admin belum menyetujui DUPAK periode tersebut.',
                    'Periode yang sudah disetujui tidak bisa diajukan ulang; gunakan periode berikutnya untuk kegiatan baru.',
                ] as $catatan)
                    <li class="flex gap-2 text-sm leading-relaxed text-slate-600">
                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-teal-400" aria-hidden="true"></span>
                        <span>{{ $catatan }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                <a href="{{ route('profil.pengajuan.mulai') }}" class="btn-primary w-auto justify-center px-5">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                    </svg>
                    Mulai Pengajuan Baru
                </a>
                <a href="{{ route('profil.pengajuan.riwayat') }}" class="btn-secondary justify-center">Lihat Riwayat Pengajuan</a>
            </div>
        </section>
    </div>
@endsection
