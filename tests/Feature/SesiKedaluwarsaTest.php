<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SesiKedaluwarsaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Middleware CSRF otomatis dilewati pada environment "testing"; ubah sementara
        // agar jalur 419 Page Expired benar-benar diuji seperti saat di hosting.
        $this->app['env'] = 'production';
    }

    private function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'program_studi' => 'Pendidikan Informatika',
            'jabatan_fungsional' => 'Lektor',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ], $attributes));
    }

    public function test_form_tanpa_token_csrf_kembali_ke_halaman_form_dengan_pesan_dan_isian(): void
    {
        $response = $this->from('/login')->post('/login', [
            'nidn' => '1987654321',
            'password' => 'password',
        ]);

        $response->assertRedirect(url('/login'));
        $response->assertSessionHas('error', fn (string $pesan) => str_contains($pesan, 'Sesi Anda telah berakhir'));
        $response->assertSessionHasInput('nidn', '1987654321');
        $response->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_tanpa_referer_tetap_diarahkan_ke_halaman_login(): void
    {
        $this->post('/login', [
            'nidn' => '1987654321',
            'password' => 'password',
        ])->assertRedirect(url('/login'));
    }

    public function test_permintaan_ajax_tanpa_token_csrf_mendapat_respons_json_419(): void
    {
        $this->postJson('/login', [
            'nidn' => '1987654321',
            'password' => 'password',
        ])
            ->assertStatus(419)
            ->assertJson(['sesi_berakhir' => true])
            ->assertJsonStructure(['message', 'sesi_berakhir']);
    }

    public function test_halaman_419_menampilkan_petunjuk_muat_ulang(): void
    {
        $html = view('errors.419')->render();

        $this->assertStringContainsString('419', $html);
        $this->assertStringContainsString('Sesi Anda telah berakhir', $html);
        $this->assertStringContainsString('Muat ulang halaman', $html);
    }

    public function test_route_csrf_token_mengembalikan_token_yang_dapat_dipakai(): void
    {
        $user = $this->makeUser();

        $token = $this->getJson('/csrf-token')
            ->assertOk()
            ->assertJsonStructure(['token'])
            ->json('token');

        $this->assertIsString($token);
        $this->assertSame(40, strlen($token));

        $this->post('/login', [
            '_token' => $token,
            'nidn' => $user->nidn,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_halaman_html_tidak_disimpan_di_cache_browser(): void
    {
        $response = $this->get('/login')->assertOk()->assertHeader('Pragma', 'no-cache');

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_halaman_login_menampilkan_pesan_sesi_berakhir(): void
    {
        $this->withSession(['error' => 'Sesi Anda telah berakhir karena halaman dibiarkan terlalu lama.'])
            ->get('/login')
            ->assertOk()
            ->assertSee('Sesi Anda telah berakhir karena halaman dibiarkan terlalu lama.');
    }

    public function test_header_proxy_https_dipercaya_untuk_url_aplikasi(): void
    {
        // Hosting di belakang Cloudflare/LiteSpeed mengirim X-Forwarded-Proto. Tanpa
        // mempercayai proxy, URL absolut aplikasi tetap http dan cookie/sesi bisa ditolak.
        $html = $this->withServerVariables(['HTTP_X_FORWARDED_PROTO' => 'https'])
            ->get('/login')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://localhost/csrf-token', $html);
    }
}
