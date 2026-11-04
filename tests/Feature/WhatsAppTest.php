<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'nidn' => '0000000001',
            'name' => 'Admin Kepegawaian',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function makeDosen(): User
    {
        return User::create([
            'nidn' => '1987654321',
            'name' => 'Dr. Ayu Lestari, M.Kom.',
            'email' => 'ayu@example.test',
            'role' => 'dosen',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);
    }

    private function simpanGateway(string $url = 'http://wa.test'): void
    {
        Pengaturan::simpanWhatsappGatewayUrl($url);
    }

    public function test_admin_can_open_whatsapp_api_page_with_two_tabs(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('WhatsApp API')
            ->assertSee('URL Gateway')
            ->assertSee('QR Code')
            ->assertSee('data-tab-target="konfigurasi"', false)
            ->assertSee('data-tab-panel="konfigurasi"', false)
            ->assertSee('data-tab-target="uji-kirim"', false)
            ->assertSee('data-tab-panel="uji-kirim"', false)
            ->assertSee('Uji Pengiriman')
            ->assertSee('Putuskan Koneksi')
            ->assertSee('data-wa-putus', false)
            ->assertSee(route('whatsapp.status'), false)
            ->assertSee(route('whatsapp.qr'), false)
            ->assertSee(route('whatsapp.putus'), false)
            ->assertSee(route('whatsapp.kirim'), false);
    }

    public function test_halaman_menampilkan_nama_sesi_dari_konfigurasi(): void
    {
        config()->set('whatsapp.session', 'server-lokal');

        $this->actingAs($this->makeAdmin())
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('Sesi / Device')
            ->assertSee('server-lokal');
    }

    public function test_putus_memakai_nama_sesi_terpasang_bukan_default(): void
    {
        config()->set('whatsapp.session', 'server-lokal');
        $this->simpanGateway();

        Http::fake([
            'wa.test/disconnect/server-lokal' => Http::response(['success' => true, 'message' => 'Disconnected']),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.putus'))
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('endpoint', 'http://wa.test/disconnect/server-lokal');

        // Sesi default TIDAK boleh dihubungi agar server lain tidak ikut terputus.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/disconnect/default'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/logout/default'));
    }

    public function test_status_dan_kirim_memakai_nama_sesi_terpasang(): void
    {
        config()->set('whatsapp.session', 'server-hosting');
        $this->simpanGateway();

        $admin = $this->makeAdmin();

        Http::fake([
            'wa.test/status/server-hosting' => Http::response([
                'status' => 'connected',
                'phone_number' => '6282143459930',
            ]),
            'wa.test/send' => Http::response(['success' => true, 'messageId' => 'X']),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($admin)
            ->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('whatsapp.terhubung', true)
            ->assertJsonPath('gateway.endpoint', 'http://wa.test/status/server-hosting');

        $this->actingAs($admin)
            ->postJson(route('whatsapp.kirim'), ['nomor' => '081234084994', 'pesan' => 'Halo'])
            ->assertOk()
            ->assertJsonPath('sukses', true);

        // device_id pada payload ikut nama sesi yang terpasang.
        Http::assertSent(fn ($request) => $request->url() === 'http://wa.test/send'
            && $request['device_id'] === 'server-hosting');
    }

    public function test_dua_deployment_dengan_sesi_berbeda_tidak_saling_memutus(): void
    {
        $this->simpanGateway();

        $admin = $this->makeAdmin();

        // Deployment A (lokal) memakai sesi local.
        config()->set('whatsapp.session', 'local');

        Http::fake([
            'wa.test/disconnect/local' => Http::response(['success' => true]),
            'wa.test/disconnect/hosting' => Http::response(['success' => true]),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($admin)->postJson(route('whatsapp.putus'))->assertOk();

        // Deployment B (hosting) memakai sesi hosting, tidak terpengaruh.
        config()->set('whatsapp.session', 'hosting');

        $this->actingAs($admin)->postJson(route('whatsapp.putus'))->assertOk();

        Http::assertSent(fn ($request) => $request->url() === 'http://wa.test/disconnect/local');
        Http::assertSent(fn ($request) => $request->url() === 'http://wa.test/disconnect/hosting');
    }

    public function test_dosen_cannot_open_whatsapp_api_page(): void
    {
        $this->actingAs($this->makeDosen())
            ->get(route('whatsapp.index'))
            ->assertForbidden();
    }

    public function test_admin_can_save_gateway_url_and_http_scheme_is_added(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('whatsapp.simpan'), ['url_gateway' => 'localhost:3000/'])
            ->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('pengaturan', [
            'kunci' => Pengaturan::WHATSAPP_GATEWAY,
            'nilai' => 'http://localhost:3000',
        ]);
    }

    public function test_invalid_gateway_url_is_rejected(): void
    {
        $this->actingAs($this->makeAdmin())
            ->from(route('whatsapp.index'))
            ->post(route('whatsapp.simpan'), ['url_gateway' => 'http://'])
            ->assertRedirect(route('whatsapp.index'))
            ->assertSessionHasErrors('url_gateway');

        $this->assertDatabaseMissing('pengaturan', ['kunci' => Pengaturan::WHATSAPP_GATEWAY]);
    }

    public function test_status_endpoint_reports_gateway_and_whatsapp_connection(): void
    {
        $this->simpanGateway();

        Http::fake([
            'wa.test/status/default' => Http::response([
                'connected' => true,
                'user' => ['id' => '6281234567890@c.us', 'name' => 'Athena'],
            ]),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('terkonfigurasi', true)
            ->assertJsonPath('gateway.online', true)
            ->assertJsonPath('gateway.kompatibel', true)
            ->assertJsonPath('gateway.http_status', 200)
            ->assertJsonPath('whatsapp.terhubung', true)
            ->assertJsonPath('whatsapp.diketahui', true)
            ->assertJsonPath('whatsapp.nomor', '6281234567890@c.us')
            ->assertJsonPath('whatsapp.nama', 'Athena');
    }

    public function test_status_endpoint_understands_pending_state_from_session_route(): void
    {
        $this->simpanGateway();

        Http::fake([
            'wa.test/status/default' => Http::response(['status' => 'pending']),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('gateway.online', true)
            ->assertJsonPath('gateway.endpoint', 'http://wa.test/status/default')
            ->assertJsonPath('whatsapp.diketahui', true)
            ->assertJsonPath('whatsapp.terhubung', false)
            ->assertJsonPath('whatsapp.status', 'menunggu');
    }

    public function test_status_endpoint_reports_unreachable_gateway(): void
    {
        $this->simpanGateway();

        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.status'))
            ->assertOk()
            ->assertJsonPath('gateway.online', false)
            ->assertJsonPath('whatsapp.terhubung', false)
            ->assertJsonPath('whatsapp.diketahui', false);
    }

    public function test_qr_endpoint_returns_image_from_gateway(): void
    {
        $this->simpanGateway();

        Http::fake([
            'wa.test/qr/default' => Http::response(['qr' => 'data:image/png;base64,AAAA']),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.qr'))
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('qr', 'data:image/png;base64,AAAA')
            ->assertJsonPath('qr_teks', null);
    }

    public function test_qr_endpoint_returns_raw_text_for_gateways_that_send_text_qr(): void
    {
        $this->simpanGateway();

        $teks = '2@'.str_repeat('AbCdEf', 20).'==';

        Http::fake([
            'wa.test/qr/default' => Http::response(['qr' => $teks]),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.qr'))
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('qr', null)
            ->assertJsonPath('qr_teks', $teks);
    }

    public function test_qr_endpoint_recovers_when_cached_endpoint_returns_only_status(): void
    {
        $this->simpanGateway();

        // Endpoint QR lama yang salah (dulu didapat dari rute generik /status/:sesi)
        // masih tersimpan di cache dan harus diabaikan, bukan dipercaya.
        Cache::put('wa-gateway:qr:'.md5('http://wa.test'), 'status/qr', 3600);

        Http::fake([
            'wa.test/status/qr' => Http::response(['status' => 'disconnected']),
            'wa.test/qr/default' => Http::response([
                'status' => 'pending',
                'qr' => 'data:image/png;base64,AAAA',
            ]),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($this->makeAdmin())
            ->getJson(route('whatsapp.qr'))
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('qr', 'data:image/png;base64,AAAA')
            ->assertJsonPath('endpoint', 'http://wa.test/qr/default');
    }

    public function test_kirim_falls_through_to_next_path_when_one_path_resets_connection(): void
    {
        $this->simpanGateway();

        // Jalur pertama (send-message) gagal sementara dengan koneksi di-reset,
        // sedangkan jalur berikutnya (/send) berhasil. Pengiriman tetap sukses.
        Http::fake(function ($request) {
            if (str_ends_with($request->url(), '/send-message')) {
                throw new ConnectionException('cURL error 56: Recv failure: Connection was reset');
            }

            if (str_ends_with($request->url(), '/send')) {
                return Http::response(['success' => true, 'messageId' => 'ABC123']);
            }

            return Http::response('not found', 404);
        });

        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.kirim'), [
                'nomor' => '081234084994',
                'pesan' => 'Notifikasi DUPAK',
            ])
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('http_status', 200)
            ->assertJsonPath('endpoint', 'http://wa.test/send')
            ->assertJsonPath('respon.success', true);
    }

    public function test_kirim_berhenti_mencoba_bila_host_gateway_tidak_terjangkau(): void
    {
        $this->simpanGateway();

        // Host tidak dapat dihubungi: cukup satu percobaan, tidak mencoba jalur lain.
        $percobaan = 0;

        Http::fake(function ($request) use (&$percobaan) {
            $percobaan++;

            throw new ConnectionException('cURL error 7: Failed to connect: Connection refused');
        });

        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.kirim'), [
                'nomor' => '081234084994',
                'pesan' => 'Halo',
            ])
            ->assertOk()
            ->assertJsonPath('sukses', false);

        $this->assertSame(1, $percobaan);
    }

    public function test_admin_can_send_test_message_and_see_gateway_response(): void
    {
        $this->simpanGateway();

        Http::fake([
            'wa.test/send-message' => Http::response(['status' => true, 'message' => 'Pesan terkirim']),
        ]);

        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.kirim'), [
                'nomor' => '0812-3456-7890',
                'pesan' => 'Halo dari Athena',
            ])
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('nomor', '6281234567890')
            ->assertJsonPath('http_status', 200)
            ->assertJsonPath('pesan', 'Pesan terkirim')
            ->assertJsonPath('respon.status', true);

        Http::assertSent(fn ($request) => $request->url() === 'http://wa.test/send-message'
            && $request['number'] === '6281234567890'
            && $request['message'] === 'Halo dari Athena'
            && $request['device_id'] === 'default'
            && $request['nomor_penerima'] === '6281234567890'
            && $request['pesan'] === 'Halo dari Athena');
    }

    public function test_admin_can_disconnect_whatsapp_session(): void
    {
        $this->simpanGateway();

        Http::fake([
            'wa.test/logout/default' => Http::response(['status' => 'success', 'message' => 'Sesi diputus']),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.putus'))
            ->assertOk()
            ->assertJsonPath('terkonfigurasi', true)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Sesi diputus')
            ->assertJsonPath('endpoint', 'http://wa.test/logout/default')
            ->assertJsonPath('http_status', 200);

        Http::assertSent(fn ($request) => $request->url() === 'http://wa.test/logout/default'
            && $request->method() === 'POST');
    }

    public function test_disconnect_clears_cached_endpoints_so_new_qr_is_fetched(): void
    {
        $this->simpanGateway();

        $admin = $this->makeAdmin();

        Cache::put('wa-gateway:qr:'.md5('http://wa.test'), 'qr/default', 3600);

        Http::fake([
            'wa.test/logout/default' => Http::response(['status' => 'success']),
            'wa.test/qr/default' => Http::response([
                'status' => 'pending',
                'qr' => 'data:image/png;base64,BBBB',
            ]),
            'wa.test/*' => Http::response('not found', 404),
        ]);

        $this->actingAs($admin)
            ->postJson(route('whatsapp.putus'))
            ->assertOk()
            ->assertJsonPath('sukses', true);

        // Cache endpoint QR lama harus dibuang agar QR baru diambil ulang.
        $this->assertNull(Cache::get('wa-gateway:qr:'.md5('http://wa.test')));

        $this->actingAs($admin)
            ->getJson(route('whatsapp.qr'))
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('qr', 'data:image/png;base64,BBBB');
    }

    public function test_disconnect_requires_configured_gateway(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.putus'))
            ->assertStatus(422)
            ->assertJsonPath('terkonfigurasi', false)
            ->assertJsonPath('sukses', false);
    }

    public function test_dosen_cannot_disconnect_whatsapp_session(): void
    {
        $this->simpanGateway();

        $this->actingAs($this->makeDosen())
            ->postJson(route('whatsapp.putus'))
            ->assertForbidden();
    }

    public function test_send_test_message_requires_configured_gateway(): void
    {
        $this->actingAs($this->makeAdmin())
            ->postJson(route('whatsapp.kirim'), [
                'nomor' => '081234567890',
                'pesan' => 'Halo',
            ])
            ->assertStatus(422)
            ->assertJsonPath('terkonfigurasi', false)
            ->assertJsonPath('sukses', false);
    }
}
