<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SapaanDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Batas waktu sapaan dashboard:
     * pagi 03.01–10.00, siang 10.01–15.00, sore 15.01–19.00, malam 19.01–03.00.
     *
     * @var array<string, string>
     */
    private const SAPAAN_PER_JAM = [
        '00:00' => 'Selamat malam',
        '03:00' => 'Selamat malam',
        '03:01' => 'Selamat pagi',
        '07:30' => 'Selamat pagi',
        '10:00' => 'Selamat pagi',
        '10:01' => 'Selamat siang',
        '12:00' => 'Selamat siang',
        '15:00' => 'Selamat siang',
        '15:01' => 'Selamat sore',
        '18:30' => 'Selamat sore',
        '19:00' => 'Selamat sore',
        '19:01' => 'Selamat malam',
        '23:59' => 'Selamat malam',
    ];

    private function makeUser(string $role = 'dosen', string $suffix = '1'): User
    {
        return User::create([
            'nidn' => '9'.str_pad($suffix, 9, '0', STR_PAD_LEFT),
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => $role.$suffix.'@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => $role,
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function padaJam(string $jam): void
    {
        $this->travelTo(Carbon::parse('2026-05-01 '.$jam.':00', config('app.timezone')));
    }

    public function test_sapaan_dashboard_mengikuti_batas_waktu(): void
    {
        $this->actingAs($this->makeUser());

        foreach (self::SAPAAN_PER_JAM as $jam => $harapan) {
            $this->padaJam($jam);

            $response = $this->get('/dashboard')->assertOk();

            // Hanya satu sapaan yang tampil, sesuai jam saat itu.
            $response->assertSee($harapan.', Ayu!');

            foreach (array_unique(array_values(self::SAPAAN_PER_JAM)) as $sapaan) {
                if ($sapaan !== $harapan) {
                    $response->assertDontSee($sapaan);
                }
            }
        }
    }

    public function test_sapaan_dashboard_sama_untuk_semua_role(): void
    {
        $this->padaJam('16:30');

        foreach (['dosen', 'admin', 'tim_pak', 'tendik'] as $index => $role) {
            $this->actingAs($this->makeUser($role, (string) ($index + 1)))
                ->get('/dashboard')
                ->assertOk()
                ->assertSee('Selamat sore, Ayu!');
        }
    }
}
