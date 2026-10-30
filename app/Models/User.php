<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
            'is_active' => 'boolean',
            'roles' => 'array',
            'password' => 'hashed',
        ];
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

    /**
     * Entri DUPAK pada satu periode, dikelompokkan per komponen kegiatan.
     *
     * @return array<string, \Illuminate\Support\Collection<int, KegiatanDupak>>
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
