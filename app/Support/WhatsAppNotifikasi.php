<?php

namespace App\Support;

use App\Models\KegiatanDupak;
use App\Models\PengajuanDupak;
use App\Models\Skp;
use App\Models\SyaratKhusus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notifikasi WhatsApp untuk alur pengajuan DUPAK, berkas syarat khusus (BKD/IKD),
 * dan penilaian PAK.
 *
 * Aturannya:
 * - Dosen mengusulkan penilaian PAK  → semua akun TIM PAK.
 * - Dosen mengusulkan DUPAK/BKD/IKD  → semua akun admin.
 * - TIM PAK menyimpan nilai PAK      → dosen pengusul.
 * - Admin memutuskan DUPAK/BKD/IKD   → dosen pengusul.
 *
 * Pengiriman bersifat best-effort: kegagalan gateway tidak boleh menggagalkan
 * aksi utama (menyimpan pengajuan/keputusan), jadi semua galat hanya dicatat log.
 */
class WhatsAppNotifikasi
{
    /**
     * Dosen mengirim usulan periode ke TIM PAK untuk dinilai.
     */
    public static function ajuanPakMasuk(User $dosen, int $tahun, string $semester): void
    {
        self::kirimKe(
            User::berRole(['tim_pak']),
            'Pengajuan Penilaian PAK',
            'Usulan penilaian PAK baru dari '.$dosen->name
                .' periode '.self::periode($tahun, $semester)
                .'. Silakan buka menu TIM PAK untuk menilai.',
        );
    }

    /**
     * Dosen mengajukan DUPAK final ke admin kepegawaian.
     */
    public static function dupakMasuk(User $dosen, int $tahun, string $semester): void
    {
        self::kirimKe(
            User::berRole(['admin']),
            'Pengajuan DUPAK',
            'DUPAK baru dari '.$dosen->name
                .' periode '.self::periode($tahun, $semester)
                .' menunggu verifikasi admin.',
        );
    }

    /**
     * Dosen mengajukan berkas syarat khusus (BKD/IKD) ke admin.
     *
     * @param  list<string>  $jenis  Jenis berkas yang benar-benar diajukan (bkd/ikd).
     */
    public static function berkasMasuk(User $dosen, array $jenis, int $tahun, string $semester): void
    {
        $label = self::labelJenis($jenis);

        if ($label === '') {
            return;
        }

        self::kirimKe(
            User::berRole(['admin']),
            'Pengajuan '.$label,
            'Berkas '.$label.' baru dari '.$dosen->name
                .' periode '.self::periode($tahun, $semester)
                .' menunggu verifikasi admin.',
        );
    }

    /**
     * TIM PAK menyimpan nilai PAK: dosen pengusul diberi tahu nilainya siap.
     */
    public static function pakDinilai(PengajuanDupak $pengajuan): void
    {
        $dosen = $pengajuan->user;

        if ($dosen === null) {
            return;
        }

        self::kirimKe(
            collect([$dosen]),
            'Nilai PAK Tersimpan',
            'Nilai PAK Anda periode '.$pengajuan->periodeLabel()
                .' sudah dinilai TIM PAK. Ajukan DUPAK melalui menu Riwayat Pengajuan.',
        );
    }

    /**
     * Admin memutuskan ajuan DUPAK: dosen pengusul diberi tahu keputusannya.
     */
    public static function dupakDiputuskan(PengajuanDupak $pengajuan): void
    {
        $dosen = $pengajuan->user;

        if ($dosen === null) {
            return;
        }

        $pesan = 'Ajuan DUPAK Anda periode '.$pengajuan->periodeLabel()
            .' diputuskan admin: '.$pengajuan->statusLabel().'.';

        if (filled($pengajuan->catatan)) {
            $pesan .= ' Catatan: '.$pengajuan->catatan;
        }

        self::kirimKe(collect([$dosen]), 'Keputusan DUPAK', $pesan);
    }

    /**
     * Admin memutuskan satu berkas syarat khusus (BKD/IKD): dosen diberi tahu.
     */
    public static function berkasDiputuskan(SyaratKhusus $syaratKhusus, string $jenis, string $keputusan, ?string $catatan): void
    {
        $dosen = $syaratKhusus->user;

        if ($dosen === null) {
            return;
        }

        $label = strtoupper($jenis);

        $pesan = 'Berkas '.$label.' Anda periode '.$syaratKhusus->periodeLabel()
            .' dinyatakan "'.($keputusan === 'diterima' ? 'Diterima' : 'Ditolak').'".';

        if (filled($catatan)) {
            $pesan .= ' Catatan: '.$catatan;
        }

        self::kirimKe(collect([$dosen]), 'Keputusan Berkas '.$label, $pesan);
    }

    /**
     * Admin membuat SKP: pejabat penilai diberi tahu ada usulan yang harus dinilai.
     */
    public static function skpMasuk(Skp $skp): void
    {
        $pejabat = $skp->pejabatPenilai;

        if ($pejabat === null) {
            return;
        }

        self::kirimKe(
            collect([$pejabat]),
            'Usulan SKP',
            'Anda ditugaskan sebagai Pejabat Penilai untuk '.($skp->dosen?->name ?? '—')
                .' periode '.$skp->periodeLabel().'. Silakan isi nilai perilaku kerja pada menu Usulan SKP.',
        );
    }

    /**
     * Pejabat penilai mengisi nilai perilaku kerja: semua admin diberi tahu.
     */
    public static function skpDinilai(Skp $skp): void
    {
        self::kirimKe(
            User::berRole(['admin']),
            'Nilai Perilaku Kerja SKP',
            'Pejabat penilai '.($skp->pejabatPenilai?->name ?? '—')
                .' telah mengisi nilai perilaku kerja untuk '.($skp->dosen?->name ?? '—').' periode '.$skp->periodeLabel().'.',
        );
    }

    /**
     * Kirim pesan ke sekumpulan penerima. Bila gateway belum diatur, diamkan saja.
     * Hasil pengiriman dihitung per penerima lalu diringkas di sesi agar halaman
     * berikutnya dapat menampilkan alert berhasil/gagal.
     *
     * @param  Collection<int, User>  $penerima
     */
    private static function kirimKe(Collection $penerima, string $judul, string $isi): void
    {
        if (! WhatsAppGateway::aktif()->terkonfigurasi()) {
            return;
        }

        $pesan = '*'.$judul."*\n\n".$isi."\n\n— ".self::namaWeb();

        $terkirim = 0;
        $gagal = 0;

        // Satu nomor hanya dikirimi sekali walau punya beberapa role.
        $penerima->unique('id')->each(function (User $user) use ($judul, $pesan, &$terkirim, &$gagal): void {
            $nomor = $user->nomorWhatsapp();

            if ($nomor === null) {
                return;
            }

            try {
                $hasil = WhatsAppGateway::aktif()->kirim($nomor, $pesan);
                $sukses = (bool) ($hasil['sukses'] ?? false);

                Log::info('[whatsapp-notif] '.$judul, [
                    'penerima' => $user->nidn,
                    'nomor' => $hasil['nomor'] ?? $nomor,
                    'sukses' => $sukses,
                ]);
            } catch (Throwable $exception) {
                $sukses = false;

                Log::warning('[whatsapp-notif] Gagal mengirim '.$judul, [
                    'penerima' => $user->nidn,
                    'pesan' => $exception->getMessage(),
                ]);
            }

            $sukses ? $terkirim++ : $gagal++;
        });

        self::catatHasil($judul, $terkirim, $gagal);
    }

    /**
     * Ringkas hasil pengiriman satu permintaan ke sesi. Bila ada beberapa
     * notifikasi dalam satu permintaan, jumlahnya digabung. Tidak ada yang
     * dicatat bila tak ada penerima yang benar-benar dikirimi.
     */
    private static function catatHasil(string $judul, int $terkirim, int $gagal): void
    {
        if ($terkirim + $gagal === 0 || ! request()->hasSession()) {
            return;
        }

        // request accessor dipakai agar ringkasan tidak bocor antar permintaan.
        $ringkasan = request()->attributes->get('wa_notif_ringkasan', ['judul' => $judul, 'terkirim' => 0, 'gagal' => 0]);
        $ringkasan['judul'] = $judul;
        $ringkasan['terkirim'] += $terkirim;
        $ringkasan['gagal'] += $gagal;

        request()->attributes->set('wa_notif_ringkasan', $ringkasan);
        session()->flash('wa_notif', $ringkasan);
    }

    /**
     * Label jenis berkas, mis. ['bkd', 'ikd'] → "BKD & IKD".
     *
     * @param  list<string>  $jenis
     */
    private static function labelJenis(array $jenis): string
    {
        return collect($jenis)
            ->filter(fn (string $nilai) => in_array($nilai, SyaratKhusus::JENIS, true))
            ->map(fn (string $nilai) => strtoupper($nilai))
            ->unique()
            ->implode(' & ');
    }

    private static function periode(int $tahun, string $semester): string
    {
        return KegiatanDupak::labelPeriode($tahun, $semester);
    }

    private static function namaWeb(): string
    {
        return \App\Models\Pengaturan::namaWeb();
    }
}