<?php

namespace App\Support;

use App\Models\PengajuanDupak;
use App\Models\SyaratKhusus;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Isi lonceng notifikasi navbar, dihitung langsung dari data pengajuan.
 *
 * ponytail: tanpa tabel notifikasi — "sudah dibaca" cukup satu penanda waktu di
 * users.notifikasi_dibaca_at. Ganti ke tabel notifikasi bila nanti perlu tanda
 * baca per item atau riwayat permanen.
 */
class Notifikasi
{
    /** Jumlah maksimum item yang ditampilkan pada panel lonceng. */
    private const MAKS = 10;

    /**
     * Notifikasi untuk role yang sedang aktif, terbaru lebih dulu.
     *
     * @return Collection<int, array{judul: string, keterangan: string, url: string, waktu: \Illuminate\Support\Carbon, baru: bool}>
     */
    public static function untuk(User $user): Collection
    {
        $daftar = match (true) {
            $user->isAdmin() => self::untukAdmin(),
            $user->isTimPak() => self::untukTimPak(),
            default => self::untukDosen($user),
        };

        $dibaca = $user->notifikasi_dibaca_at;

        return $daftar
            ->sortByDesc('waktu')
            ->take(self::MAKS)
            ->map(fn (array $item) => $item + ['baru' => $dibaca === null || $item['waktu']->gt($dibaca)])
            ->values();
    }

    /**
     * Admin: DUPAK yang diajukan dosen dan berkas BKD/IKD yang menunggu verifikasi.
     */
    private static function untukAdmin(): Collection
    {
        $dupak = PengajuanDupak::query()
            ->with('user')
            ->where('status', 'diajukan_dupak')
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (PengajuanDupak $row) => [
                'judul' => 'Pengajuan DUPAK masuk',
                'keterangan' => $row->user->name.' · '.$row->periodeLabel(),
                'url' => route('verifikasi-dupak.show', $row),
                'waktu' => $row->updated_at,
            ]);

        $berkas = SyaratKhusus::query()
            ->with('user')
            ->where(fn ($query) => $query->where('bkd_status', 'diajukan')->orWhere('ikd_status', 'diajukan'))
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (SyaratKhusus $row) => [
                'judul' => 'Pengajuan '.self::jenisBerkas($row, 'diajukan').' masuk',
                'keterangan' => $row->user->name.' · '.$row->periodeLabel(),
                'url' => route('verifikasi-berkas.index'),
                'waktu' => $row->updated_at,
            ]);

        return $dupak->concat($berkas);
    }

    /**
     * TIM PAK: ajuan penilaian PAK dari dosen yang belum dinilai.
     */
    private static function untukTimPak(): Collection
    {
        return PengajuanDupak::query()
            ->with('user')
            ->where('status', 'diajukan')
            ->latest('diajukan_at')
            ->latest('id')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (PengajuanDupak $row) => [
                'judul' => 'Ajuan penilaian PAK',
                'keterangan' => $row->user->name.' · '.$row->periodeLabel(),
                'url' => route('tim-pak.pengajuan.show', $row),
                'waktu' => $row->diajukan_at ?? $row->updated_at,
            ]);
    }

    /**
     * Dosen: ajuan yang sudah direspon baik TIM PAK (dinilai) atau admin
     * kepegawaian (DUPAK disetujui, berkas diterima).
     */
    private static function untukDosen(User $user): Collection
    {
        $dupak = PengajuanDupak::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['dinilai', 'disetujui'])
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (PengajuanDupak $row) => [
                'judul' => $row->status === 'disetujui'
                    ? 'DUPAK disetujui admin kepegawaian'
                    : 'Nilai PAK sudah dinilai TIM PAK',
                'keterangan' => $row->periodeLabel().' · '.$row->statusLabel(),
                'url' => route('profil.pengajuan.riwayat'),
                'waktu' => $row->status === 'dinilai' ? ($row->dinilai_at ?? $row->updated_at) : $row->updated_at,
            ]);

        $berkas = SyaratKhusus::query()
            ->where('user_id', $user->id)
            ->where(fn ($query) => $query->where('bkd_status', 'diterima')->orWhere('ikd_status', 'diterima'))
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (SyaratKhusus $row) => [
                'judul' => 'Berkas '.self::jenisBerkas($row, 'diterima').' diterima',
                'keterangan' => $row->periodeLabel().' · '.$row->statusLabel(),
                'url' => route('profil.pengajuan.riwayat-ikd'),
                'waktu' => $row->updated_at,
            ]);

        return $dupak->concat($berkas);
    }

    /**
     * Jenis berkas (BKD/IKD) yang sedang berstatus tertentu pada satu periode.
     */
    private static function jenisBerkas(SyaratKhusus $row, string $status): string
    {
        return collect(SyaratKhusus::JENIS)
            ->filter(fn (string $jenis) => $row->statusJenis($jenis) === $status)
            ->map(fn (string $jenis) => strtoupper($jenis))
            ->implode(' & ');
    }
}