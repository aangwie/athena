<?php

namespace Tests\Feature;

use App\Models\PengajuanDupak;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AjuanPakTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $suffix = '1'): User
    {
        $nama = match ($role) {
            'dosen' => 'Dr. Ayu Lestari, M.Kom.',
            'tim_pak' => 'Prof. Budi Santoso, M.T.',
            default => 'Admin '.ucfirst($role),
        };

        return User::create([
            'nidn' => '9'.str_pad((string) (User::max('id') + 1), 9, '0', STR_PAD_LEFT),
            'name' => $nama,
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeAjuan(User $dosen, string $status = 'menunggu_penugasan'): PengajuanDupak
    {
        return $dosen->pengajuanDupak()->create([
            'tahun' => 2026,
            'semester' => 'ganjil',
            'status' => $status,
            'diajukan_at' => now()->subDay(),
        ]);
    }

    public function test_admin_melihat_ajuan_pak_dengan_kartu_tabel_dan_form_penugasan(): void
    {
        $dosen = $this->makeUser('dosen');
        $ajuan = $this->makeAjuan($dosen);
        $timPak = $this->makeUser('tim_pak', '2');

        $this->actingAs($this->makeUser('admin'))
            ->get(route('ajuan-pak.index'))
            ->assertOk()
            ->assertSee('Ajuan PAK Masuk')
            ->assertSee('data-table-card', false)
            ->assertSee('data-table-search', false)
            ->assertSee('data-table-filter', false)
            ->assertSee($dosen->name)
            ->assertSee('Menunggu penugasan admin')
            ->assertSee('Tugaskan')
            ->assertSee(route('ajuan-pak.tugaskan', $ajuan), false)
            ->assertSee($timPak->name);
    }

    public function test_admin_meneruskan_ajuan_ke_tim_pak_terpilih(): void
    {
        $ajuan = $this->makeAjuan($this->makeUser('dosen'));
        $timPak = $this->makeUser('tim_pak', '2');

        $this->actingAs($this->makeUser('admin'))
            ->put(route('ajuan-pak.tugaskan', $ajuan), ['tim_pak_id' => $timPak->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $ajuan->refresh();

        $this->assertSame('diajukan', $ajuan->status);
        $this->assertSame($timPak->id, $ajuan->tim_pak_id);
    }

    public function test_penerima_penugasan_harus_pengguna_tim_pak_aktif(): void
    {
        $ajuan = $this->makeAjuan($this->makeUser('dosen'));
        $dosenLain = $this->makeUser('dosen', '2');

        $this->actingAs($this->makeUser('admin'))
            ->put(route('ajuan-pak.tugaskan', $ajuan), ['tim_pak_id' => $dosenLain->id])
            ->assertSessionHasErrors('tim_pak_id');

        $this->assertNull($ajuan->fresh()->tim_pak_id);
        $this->assertSame('menunggu_penugasan', $ajuan->fresh()->status);
    }

    public function test_ajuan_yang_sudah_diteruskan_tidak_dapat_ditugaskan_ulang(): void
    {
        $ajuan = $this->makeAjuan($this->makeUser('dosen'), 'diajukan');

        $this->actingAs($this->makeUser('admin'))
            ->put(route('ajuan-pak.tugaskan', $ajuan), ['tim_pak_id' => $this->makeUser('tim_pak', '2')->id])
            ->assertStatus(422);
    }

    public function test_tim_pak_hanya_dapat_membuka_ajuan_yang_ditugaskan_kepadanya(): void
    {
        $ajuan = $this->makeAjuan($this->makeUser('dosen'), 'diajukan');
        $ditugaskan = $this->makeUser('tim_pak', '2');
        $timPakLain = $this->makeUser('tim_pak', '3');

        $ajuan->update(['tim_pak_id' => $ditugaskan->id]);

        $this->actingAs($ditugaskan)
            ->get(route('tim-pak.pengajuan.show', $ajuan))
            ->assertOk();

        $this->actingAs($timPakLain)
            ->get(route('tim-pak.pengajuan.show', $ajuan))
            ->assertForbidden();
    }

    public function test_menu_ajuan_pak_tampil_di_sidebar_admin_dengan_ikon(): void
    {
        $html = $this->actingAs($this->makeUser('admin'))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('ajuan-pak.index'), false)
            ->getContent();

        // Menu Ajuan PAK wajib memakai ikon seperti menu sidebar lainnya.
        $this->assertSame(1, preg_match('/<a href="[^"]*ajuan-pak"[^>]*>(.*?)<\/a>/s', $html, $cocok));
        $this->assertStringContainsString('<svg', $cocok[1]);
        $this->assertStringContainsString('Ajuan PAK', $cocok[1]);
    }
}
