<?php

namespace App\Support;

use App\Models\PengajuanDupak;
use App\Models\Skp;
use App\Models\SyaratKhusus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
     * @return Collection<int, array{judul: string, keterangan: string, url: string, waktu: Carbon, baru: bool}>
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

        $skp = Skp::query()
            ->with(['dosen', 'pejabatPenilai'])
            ->whereNotNull('nilai_orientasi')
            ->whereNotNull('nilai_integritas')
            ->whereNotNull('nilai_komitmen')
            ->whereNotNull('nilai_disiplin')
            ->whereNotNull('nilai_kerjasama')
            ->whereNotNull('nilai_kepemimpinan')
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (Skp $row) => [
                'judul' => 'Nilai perilaku kerja SKP diperbarui',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · '.($row->pejabatPenilai?->name ?? '—'),
                'url' => route('skp.index'),
                'waktu' => $row->updated_at,
            ]);

        // Keberatan dosen, tanggapan pejabat penilai, dan persetujuan dosen ikut dipantau admin.
        $keberatan = self::skpDenganKeberatan()
            ->map(fn (Skp $row) => [
                'judul' => 'Ajuan keberatan SKP',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · diajukan '.self::tanggal($row->tanggal_keberatan),
                'url' => route('skp.index'),
                'waktu' => $row->updated_at,
            ]);

        $tanggapan = self::skpDenganTanggapan()
            ->map(fn (Skp $row) => [
                'judul' => 'Tanggapan keberatan SKP',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · ditanggapi '.self::tanggal($row->tanggal_tanggapan),
                'url' => route('skp.index'),
                'waktu' => $row->updated_at,
            ]);

        $persetujuan = self::skpDisetujui()
            ->map(fn (Skp $row) => [
                'judul' => 'Persetujuan penilaian SKP',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · disetujui '.self::tanggal($row->disetujui_pada),
                'url' => route('skp.index'),
                'waktu' => $row->updated_at,
            ]);

        return $dupak->concat($berkas)->concat($skp)->concat($keberatan)->concat($tanggapan)->concat($persetujuan);
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
            ->where(fn ($query) => $query
                ->whereIn('bkd_status', ['diterima', 'ditolak'])
                ->orWhereIn('ikd_status', ['diterima', 'ditolak']))
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get()
            ->flatMap(fn (SyaratKhusus $row) => self::notifikasiBerkas($row));

        $skp = Skp::query()
            ->with(['dosen'])
            ->where('pejabat_penilai_id', $user->id)
            ->latest('created_at')
            ->limit(self::MAKS)
            ->get()
            ->map(fn (Skp $row) => [
                'judul' => 'Usulan SKP untuk dinilai',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel(),
                'url' => route('skp-usulan.index'),
                'waktu' => $row->created_at,
            ]);

        // Pejabat penilai: dosen yang dinilai mengajukan keberatan atas nilainya.
        $keberatan = self::skpDenganKeberatan(fn ($query) => $query->where('pejabat_penilai_id', $user->id))
            ->map(fn (Skp $row) => [
                'judul' => 'Ajuan keberatan SKP masuk',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · diajukan '.self::tanggal($row->tanggal_keberatan),
                // Langsung menuju form tanggapan selama belum ditanggapi.
                'url' => $row->sudahDitanggapi()
                    ? route('skp-usulan.index')
                    : route('skp-usulan.tanggapan.edit', $row),
                'waktu' => $row->updated_at,
            ]);

        // Pejabat penilai: dosen menyetujui hasil penilaian.
        $persetujuan = self::skpDisetujui(fn ($query) => $query->where('pejabat_penilai_id', $user->id))
            ->map(fn (Skp $row) => [
                'judul' => 'Penilaian SKP disetujui dosen',
                'keterangan' => ($row->dosen?->name ?? '—').' · '.$row->periodeLabel().' · disetujui '.self::tanggal($row->disetujui_pada),
                'url' => route('skp-usulan.index'),
                'waktu' => $row->updated_at,
            ]);

        // Dosen yang dinilai: pejabat penilai menanggapi keberatan yang diajukan.
        $tanggapan = self::skpDenganTanggapan(fn ($query) => $query->where('dosen_id', $user->id))
            ->map(fn (Skp $row) => [
                'judul' => 'Tanggapan keberatan SKP',
                'keterangan' => $row->periodeLabel().' · ditanggapi '.self::tanggal($row->tanggal_tanggapan),
                'url' => route('skp-dosen.index'),
                'waktu' => $row->updated_at,
            ]);

        return $dupak->concat($berkas)->concat($skp)->concat($keberatan)->concat($persetujuan)->concat($tanggapan);
    }

    /**
     * Notifikasi dosen untuk satu baris berkas syarat khusus.
     *
     * Berkas yang diterima digabung per periode ("BKD & IKD diterima") seperti
     * sebelumnya, sedangkan berkas yang ditolak atau dihapus admin dipecah per
     * jenis supaya dosen tahu berkas mana yang harus diunggah ulang.
     *
     * @return Collection<int, array{judul: string, keterangan: string, url: string, waktu: Carbon}>
     */
    private static function notifikasiBerkas(SyaratKhusus $row): Collection
    {
        $daftar = collect();

        $diterima = self::jenisBerkas($row, 'diterima');

        if ($diterima !== '') {
            $daftar->push([
                'judul' => 'Berkas '.$diterima.' diterima',
                'keterangan' => $row->periodeLabel().' · '.$row->statusLabel(),
                'url' => route('profil.pengajuan.riwayat-ikd'),
                'waktu' => $row->updated_at,
            ]);
        }

        foreach (SyaratKhusus::JENIS as $jenis) {
            if ($row->statusJenis($jenis) !== 'ditolak') {
                continue;
            }

            $label = strtoupper($jenis);
            // Path kosong berarti berkas sudah dihapus admin, bukan sekadar ditolak.
            $dihapus = $row->{$jenis.'_path'} === null;
            $catatan = $row->catatanJenis($jenis);

            $daftar->push([
                'judul' => 'Berkas '.$label.($dihapus ? ' dihapus admin' : ' ditolak'),
                'keterangan' => $row->periodeLabel().' · '.$row->statusLabel($jenis)
                    .(filled($catatan) ? ' · '.$catatan : ''),
                'url' => route('profil.pengajuan.riwayat-ikd'),
                'waktu' => $row->updated_at,
            ]);
        }

        return $daftar;
    }

    /**
     * SKP yang dosennya sudah mengajukan keberatan, terbaru lebih dulu.
     *
     * @param  (callable(Builder<Skp>): void)|null  $saring
     * @return Collection<int, Skp>
     */
    private static function skpDenganKeberatan(?callable $saring = null): Collection
    {
        return self::skpDenganSyarat(
            fn ($query) => $query->whereNotNull('keberatan')->orWhereNotNull('tanggal_keberatan'),
            $saring,
        );
    }

    /**
     * SKP yang keberatannya sudah ditanggapi pejabat penilai, terbaru lebih dulu.
     *
     * @param  (callable(Builder<Skp>): void)|null  $saring
     * @return Collection<int, Skp>
     */
    private static function skpDenganTanggapan(?callable $saring = null): Collection
    {
        return self::skpDenganSyarat(
            fn ($query) => $query->whereNotNull('tanggapan_keberatan')->orWhereNotNull('tanggal_tanggapan'),
            $saring,
        );
    }

    /**
     * SKP yang sudah disetujui dosen yang dinilai, terbaru lebih dulu.
     *
     * @param  (callable(Builder<Skp>): void)|null  $saring
     * @return Collection<int, Skp>
     */
    private static function skpDisetujui(?callable $saring = null): Collection
    {
        return self::skpDenganSyarat(fn ($query) => $query->whereNotNull('disetujui_pada'), $saring);
    }

    /**
     * Query dasar notifikasi SKP (dosen + pejabat penilai) dengan saringan tambahan.
     *
     * @param  callable(Builder<Skp>): void  $syarat
     * @param  (callable(Builder<Skp>): void)|null  $saring
     * @return Collection<int, Skp>
     */
    private static function skpDenganSyarat(callable $syarat, ?callable $saring = null): Collection
    {
        $query = Skp::query()
            ->with(['dosen', 'pejabatPenilai'])
            ->where($syarat);

        if ($saring !== null) {
            $saring($query);
        }

        return $query
            ->latest('updated_at')
            ->limit(self::MAKS)
            ->get();
    }

    /**
     * Tanggal Indonesia untuk keterangan notifikasi (mis. "7 November 2026").
     */
    private static function tanggal(?Carbon $tanggal): string
    {
        return $tanggal?->locale('id')->translatedFormat('j F Y') ?? '—';
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
