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
            ->assertSee(route('whatsapp.status'), false)
            ->assertSee(route('whatsapp.qr'), false)
            ->assertSee(route('whatsapp.kirim'), false);
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
