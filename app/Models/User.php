<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Role yang tersedia beserta labelnya.
     *
     * @var array<string, string>
     */
    public const ROLES = [
        'dosen' => 'Dosen',
        'tendik' => 'TENDIK',
        'tim_pak' => 'TIM PAK',
        'admin' => 'Admin',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nidn',
        'name',
        'email',
        'program_studi',
        'jabatan_fungsional',
        'jabatan_diajukan',
        'pangkat',
        'no_hp',
        'role',
        'roles',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'notifikasi_dibaca_at' => 'datetime',
            'is_active' => 'boolean',
            'roles' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * Hapus seluruh turunan user saat akunnya dihapus.
     *
     * Baris tabel lain sudah memakai cascadeOnDelete pada foreign key user_id,
     * jadi yang dibereskan di sini hanya yang tidak dijangkau database:
     * rujukan id di kolom JSON, sesi login, token reset password, dan berkas
     * unggahan pada disk privat.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            // Id dosen ini pada daftar hadir rapat senat (kolom JSON tanpa FK).
            SuratBapSenat::query()
                ->whereJsonContains('anggota_senat_ids', $user->id)
                ->get()
                ->each(fn (SuratBapSenat $bap) => $bap->update([
                    'anggota_senat_ids' => array_values(array_diff($bap->anggota_senat_ids ?? [], [$user->id])),
                ]));

            DB::table('sessions')->where('user_id', $user->id)->delete();

            if (filled($user->email)) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            }
        });

        // Berkas dihapus setelah baris user benar-benar hilang, supaya kegagalan
        // penghapusan data tidak ikut melenyapkan berkas yang masih dipakai.
        static::deleted(function (User $user): void {
            // ponytail: folder dikunci per NIDN; NIDN yang pernah diubah meninggalkan folder yatim.
            if (! filled($user->nidn)) {
                return;
            }

            $disk = Storage::disk('local');
            $disk->deleteDirectory("syarat-khusus/{$user->nidn}");
            $disk->deleteDirectory("riwayat-pendidikan/{$user->nidn}");
        });
    }

    public function pengajuanJabatan()
    {
        return $this->hasMany(PengajuanJabatan::class);
    }

    public function profilDosen(): HasOne
    {
        return $this->hasOne(ProfilDosen::class);
    }

    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(RiwayatPendidikan::class);
    }

    public function karyaTulis(): HasMany
    {
        return $this->hasMany(KaryaTulis::class);
    }

    public function riwayatKepangkatan(): HasMany
    {
        return $this->hasMany(RiwayatKepangkatan::class);
    }

    public function riwayatJabatanStruktural(): HasMany
    {
        return $this->hasMany(RiwayatJabatanStruktural::class);
    }

    public function riwayatAngkaKredit(): HasMany
    {
        return $this->hasMany(RiwayatAngkaKredit::class);
    }

    public function kegiatanDupak(): HasMany
    {
        return $this->hasMany(KegiatanDupak::class);
    }

    public function pengajuanDupak(): HasMany
    {
        return $this->hasMany(PengajuanDupak::class);
    }

    public function masaPenilaian(): HasMany
    {
        return $this->hasMany(MasaPenilaian::class);
    }

    /**
     * Entri DUPAK pada satu periode, dikelompokkan per komponen kegiatan.
     *
     * @return array<string, Collection<int, KegiatanDupak>>
     */
    public function kegiatanPeriode(int $tahun, string $semester): array
    {
        $entri = $this->kegiatanDupak()
            ->periode($tahun, $semester)
            ->latest()
            ->get()
            ->groupBy('komponen');

        return [
            'kegiatanPendidikan' => $entri->get('pendidikan') ?? collect(),
            'kegiatanPelaksanaan' => $entri->get('pelaksanaan_pendidikan') ?? collect(),
            'kegiatanPenelitian' => $entri->get('penelitian') ?? collect(),
            'kegiatanAbdimas' => $entri->get('abdimas') ?? collect(),
            'kegiatanPenunjang' => $entri->get('penunjang') ?? collect(),
        ];
    }

    /**
     * Role yang dimiliki user. Kolom role lama dipakai sebagai cadangan.
     *
     * @return list<string>
     */
    public function roleList(): array
    {
        return array_values(array_intersect(
            array_keys(self::ROLES),
            $this->roles ?: [$this->role],
        ));
    }

    /**
     * Label role yang dimiliki user, siap ditampilkan.
     *
     * @return list<string>
     */
    public function roleLabels(): array
    {
        return array_map(fn (string $role) => self::ROLES[$role], $this->roleList());
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roleList(), true);
    }

    /**
     * Role yang sedang dipakai pada sesi ini. Bila user punya beberapa role
     * dan belum memilih, dipakai role pertama sebagai cadangan.
     */
    public function activeRole(): string
    {
        $roles = $this->roleList();
        $active = session('active_role');

        return in_array($active, $roles, true) ? $active : ($roles[0] ?? 'dosen');
    }

    public function activeRoleLabel(): string
    {
        return self::ROLES[$this->activeRole()];
    }

    /**
     * User dengan lebih dari satu role wajib memilih peran setelah login.
     */
    public function needsRoleSelection(): bool
    {
        return count($this->roleList()) > 1
            && ! in_array(session('active_role'), $this->roleList(), true);
    }

    public function isAdmin(): bool
    {
        return $this->activeRole() === 'admin';
    }

    public function isDosen(): bool
    {
        return $this->activeRole() === 'dosen';
    }

    public function isTendik(): bool
    {
        return $this->activeRole() === 'tendik';
    }

    public function isTimPak(): bool
    {
        return $this->activeRole() === 'tim_pak';
    }

    /**
     * Admin dan TIM PAK boleh melihat pengajuan seluruh dosen.
     */
    public function canReview(): bool
    {
        return $this->isAdmin() || $this->isTimPak();
    }

    /**
     * Semua akun aktif yang memiliki salah satu role tertentu, tanpa duplikat.
     * Pencocokan memakai kolom role lama dan kolom JSON roles, sama seperti
     * pencarian penilai TIM PAK pada halaman cetak DUPAK.
     *
     * @param  list<string>  $roles
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function berRole(array $roles): Collection
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($query) use ($roles): void {
                $query->whereIn('role', $roles);

                foreach ($roles as $role) {
                    $query->orWhereJsonContains('roles', $role);
                }
            })
            ->orderBy('name')
            ->get();
    }

    /**
     * Nomor WhatsApp yang dipakai untuk notifikasi: nomor profil dosen bila ada,
     * selain itu nomor pada akun.
     */
    public function nomorWhatsapp(): ?string
    {
        $nomor = $this->profilDosen?->no_hp ?: $this->no_hp;

        return filled($nomor) ? (string) $nomor : null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->name)) ?: [];

        $initials = collect($words)
            ->filter()
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'DS';
    }

    public function firstName(): string
    {
        $name = preg_replace('/\b(Prof|Dr|Drs|Dra|Ir|Hj|H)\.?\s+/i', '', $this->name) ?? $this->name;
        $parts = preg_split('/[\s,]+/', trim($name)) ?: [];

        return $parts[0] ?? $this->name;
    }
}
