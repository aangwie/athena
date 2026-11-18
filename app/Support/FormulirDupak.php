<?php

namespace App\Support;

use App\Models\RiwayatPendidikan;
use App\Models\StrukturInstansi;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Data pengusul untuk blok formulir DUPAK: "Keterangan Perorangan", "Bahan yang
 * Dinilai", dan "Pendapat Tim Penilai Jabatan Angka Kredit".
 *
 * Dipakai halaman cetak DUPAK dosen (profil/pengajuan/cetak) dan halaman
 * verifikasi DUPAK admin, supaya keduanya menampilkan identitas yang sama.
 */
class FormulirDupak
{
    /**
     * Identitas dosen pengusul untuk blok "Keterangan Perorangan" dan blok bawah.
     *
     * @return array<string, string>
     */
    public static function identitas(User $user): array
    {
        // Relasi dimuat bila belum ada, sehingga pemanggil boleh memuat sebagian saja.
        $user->loadMissing([
            'profilDosen',
            'riwayatKepangkatan.kepangkatan',
            'kepangkatanDiajukan',
            'riwayatJabatanStruktural.jabatanFungsional',
            'riwayatAngkaKredit',
            'riwayatPendidikan',
        ]);

        $profil = $user->profilDosen;
        $pangkat = $user->riwayatKepangkatan->sortByDesc('tmt')->first();
        $jabatan = $user->riwayatJabatanStruktural
            ->whereNotNull('jabatan_fungsional_id')
            ->sortByDesc('tmt')
            ->first();
        $pendidikan = $user->riwayatPendidikan
            ->sortByDesc(fn (RiwayatPendidikan $item) => $item->jenjangRank())
            ->first();
        $angkaKredit = $user->riwayatAngkaKredit->sortByDesc('tahun')->first();

        // Angka kredit terakhir + TMT jabatan akademik, mis. "150,00 / 02 Agustus 2019".
        $angkaKreditTmt = collect([
            $angkaKredit?->angkaKreditLabel(),
            $jabatan?->tmtLabel(),
        ])->filter(fn ($nilai) => filled($nilai) && $nilai !== '—')->implode(' / ');

        return [
            'nama' => $user->name ?: '—',
            'nuptk_nidn' => collect([$profil?->nuptk, $user->nidn])
                ->filter()->implode('/') ?: '—',
            'ttl' => collect([
                $profil?->tempat_lahir,
                $profil?->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
            ])->filter()->implode(', ') ?: '—',
            'jenis_kelamin' => $profil?->jenisKelaminLabel() ?? '—',
            'pendidikan_terakhir' => $pendidikan?->jenjangLabel() ?? '—',
            'jabatan_akademik' => trim(collect([
                $user->jabatan_fungsional,
                $angkaKreditTmt !== '' ? '('.$angkaKreditTmt.')' : null,
            ])->filter()->implode(' ')) ?: '—',
            // Nama jabatan & pangkat tanpa TMT, dipakai blok "Pendapat Tim Penilai".
            // Jabatan = target yang diajukan dosen; Pangkat/Gol = pangkat target yang
            // dipilih dari data kepegawaian (fallback ke pangkat terakhir riwayat).
            'jabatan_diajukan' => $user->jabatan_diajukan ?: ($user->jabatan_fungsional ?: '—'),
            'pangkat_nama' => $user->kepangkatanDiajukan?->label() ?? $pangkat?->pangkatLabel() ?? '—',
            'jurusan_prodi' => $pendidikan?->jurusan ?: ($user->program_studi ?: '—'),
            'bidang_ilmu' => $profil?->bidang_ilmu ?: '—',
            'pangkat' => trim(collect([
                $pangkat?->pangkatLabel(),
                $pangkat?->tmtLabel(),
            ])->filter(fn ($nilai) => filled($nilai) && $nilai !== '—')
                ->implode(' / ')) ?: '—',
            'masa_kerja_lama' => $profil?->masaKerjaGolonganLabel('lama') ?? '—',
            'masa_kerja_baru' => $profil?->masaKerjaGolonganLabel('baru') ?? '—',
        ];
    }

    /**
     * Pimpinan instansi yang mengesahkan DUPAK pada blok tanda tangan bawah.
     * Dipilih dari struktur instansi: jabatan Rektor/Ketua, atau pimpinan pertama.
     */
    public static function pimpinan(): ?User
    {
        $struktur = StrukturInstansi::query()->with(['jabatanStruktural', 'user'])->get();

        $pimpinan = $struktur->first(fn (StrukturInstansi $item) => in_array(
            Str::lower($item->jabatanStruktural?->nama ?? ''),
            ['ketua', 'rektor'],
            true,
        ));

        return ($pimpinan ?? $struktur->first())?->user;
    }
}
