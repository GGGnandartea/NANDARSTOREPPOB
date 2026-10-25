<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\KiosbankDeposit;
use App\Models\PpobProduct;
use App\Models\Setting;
use App\Models\User;
use App\Services\KiosbankConfiguration;
use App\Services\KiosbankService;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiosbankConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Http::preventStrayRequests();
        config(['kiosbank' => [
            'profiles' => [
                'development' => ['username' => 'dev-user', 'password' => 'dev-secret'],
                'production' => ['username' => 'live-user', 'password' => 'live-secret'],
            ],
            'merchant_id' => 'TEST-MERCHANT', 'account_id' => '08000000000',
            'mitra' => 'DJI', 'counter_id' => '1', 'merchant_name' => 'Test Store',
        ]]);
    }

    private function payload(string $mode): array
    {
        $configuration = new KiosbankConfiguration();

        return ['mode' => $mode, 'enabled' => '1', 'profiles' => [
            'development' => array_replace($configuration->profile('development'), ['password' => '']),
            'production' => array_replace($configuration->profile('production'), ['password' => '']),
        ]];
    }

    public function test_admin_can_switch_modes_without_mixing_credentials(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (['production', 'development'] as $mode) {
            $this->post('/admin/ppob/settings', $this->payload($mode))->assertSessionHasNoErrors()->assertRedirect();
            $service = new KiosbankService();
            $this->assertSame($mode, $service->getMode());
            $host = $mode === 'production' ? 'transaksi.kiosbank.com:17109' : 'development.kiosbank.com:4432';
            $this->assertSame('https://'.$host, $service->getBaseUrl());
            $profile = (new KiosbankConfiguration())->profile($mode);
            $this->assertSame($mode === 'production' ? 'live-secret' : 'dev-secret', $profile['password']);
        }
        $rawPassword = Setting::where('key', 'kiosbank_production_password')->value('value');
        $this->assertStringStartsWith('encrypted:', $rawPassword);
        $this->assertStringNotContainsString('live-secret', $rawPassword);
    }

    public function test_configuration_page_does_not_expose_saved_passwords(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get('/admin/ppob/settings')->assertOk()
            ->assertSee('17109')->assertSee('4432')
            ->assertDontSee('dev-secret')->assertDontSee('live-secret');
    }

    public function test_invalid_mode_and_missing_production_password_are_rejected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/ppob/settings', $this->payload('invalid'))->assertSessionHasErrors('mode');
        config(['kiosbank.profiles.production.password' => '']);
        $this->post('/admin/ppob/settings', $this->payload('production'))
            ->assertSessionHasErrors('profiles.production.password');
        $this->assertSame('development', (new KiosbankConfiguration())->mode());
    }

    public function test_production_never_returns_simulated_success_when_provider_is_offline(): void
    {
        Http::fake(fn () => throw new ConnectionException('Provider offline'));
        $service = new KiosbankService('production');

        $results = [
            $service->inquiry('100201', '12345678901'),
            $service->payBill('100201', '12345678901', 50000, 2500, 52500),
            $service->singlePayment('500521', '081234567890', 5650),
            $service->checkStatus('500521', '081234567890', '123456789012', 5650),
        ];
        foreach ($results as $result) {
            $this->assertNotSame('success', $result['status']);
            $this->assertNotSame('00', $result['rc']);
            $this->assertFalse($result['is_sandbox'] ?? false);
        }
        $this->assertNull(Setting::where('key', 'kiosbank_sandbox_deposit')->value('value'));
    }

    public function test_pending_provider_response_is_not_reported_as_completed(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/SinglePayment' => Http::response(['rc' => '00', 'data' => ['status' => 'SEDANG DIPROSES']]),
            '*/Services/Payment' => Http::response(['rc' => '00', 'data' => ['status' => 'SEDANG DIPROSES']]),
        ]);
        $service = new KiosbankService('production');
        $this->assertSame('processing', $service->singlePayment('500521', '081234567890', 5650)['status']);
        $this->assertSame('processing', $service->payBill('100201', '12345678901', 50000, 2500, 52500)['status']);
    }

    public function test_session_cache_is_separate_per_mode_and_invalidated_by_credential_changes(): void
    {
        Http::fake(['*/auth/Sign-On' => Http::response(['SessionID' => 'test-session'])]);
        (new KiosbankService('development'))->getSessionId();
        (new KiosbankService('production'))->getSessionId();
        (new KiosbankService('production'))->getSessionId();
        Http::assertSentCount(2);

        (new KiosbankConfiguration())->saveProfile('production', ['password' => 'changed-secret']);
        (new KiosbankService('production'))->getSessionId();
        Http::assertSentCount(3);
    }

    public function test_development_tls_compatibility_does_not_disable_production_verification(): void
    {
        config(['kiosbank.profiles.development.verify_tls' => false, 'kiosbank.profiles.production.verify_tls' => false]);
        $verification = [];
        Http::fake(function ($request, $options) use (&$verification) {
            $verification[] = $options['verify'];
            return Http::response(['rc' => '00']);
        });
        (new KiosbankService('development'))->makeDigestRequest('/Services/get-Active-Product');
        (new KiosbankService('production'))->makeDigestRequest('/Services/get-Active-Product');
        $this->assertSame([false, true], $verification);
    }

    public function test_sync_and_diagnostics_reuse_the_daily_session(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => '500521', 'maintenance' => '']),
        ]);
        $service = new KiosbankService('production');
        $this->assertTrue($service->testSignOn()['ok']);
        $this->assertTrue($service->testSignOn()['is_cached']);
        $service->syncWithKiosbankApi();
        $service->syncWithKiosbankApi();
        $signOns = Http::recorded(fn ($request) => str_ends_with($request->url(), '/auth/Sign-On'));
        $this->assertCount(1, $signOns);
    }

    public function test_sign_on_limit_and_session_expiry_follow_the_wib_calendar_day(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-23 23:58:00', 'Asia/Jakarta'));
        Http::fake(['*/auth/Sign-On' => Http::response(['SessionID' => 'test-session'])]);
        $service = new KiosbankService('production');
        for ($attempt = 0; $attempt < 99; $attempt++) {
            $this->assertSame('test-session', $service->signOn(true));
        }
        $this->assertNull($service->signOn(true));
        Http::assertSentCount(99);
        $this->assertSame('test-session', $service->getSessionId());
        Http::assertSentCount(99);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-24 00:16:00', 'Asia/Jakarta'));
        $this->assertSame('test-session', $service->getSessionId());
        Http::assertSentCount(100);
        $this->travelBack();
    }

    public function test_existing_ppob_order_keeps_its_original_mode_after_switching(): void
    {
        Setting::create(['key' => 'kiosbank_mode', 'value' => 'production']);
        Http::fake([
            'https://development.kiosbank.com:4432/auth/Sign-On' => Http::response(['SessionID' => 'dev-session']),
            'https://development.kiosbank.com:4432/Services/SinglePayment' => Http::response([
                'rc' => '00', 'data' => ['status' => 'BERHASIL', 'sn' => 'test-token'],
            ]),
        ]);
        $order = Order::create([
            'invoice_number' => 'TEST-PPOB', 'product_type' => 'ppob', 'product_name' => 'Test Pulsa',
            'kiosbank_mode' => 'development', 'price' => 5650, 'total_amount' => 5650, 'provider_amount' => 5650,
            'payment_status' => 'paid', 'order_status' => 'processing',
            'notes' => 'No Tujuan: 081234567890 | Kode: 500521',
        ]);

        $this->assertTrue(OrderFulfillmentService::fulfillOrder($order)['is_ready']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'transaksi.kiosbank.com'));
    }

    public function test_production_rejects_deposit_simulation(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Setting::create(['key' => 'kiosbank_mode', 'value' => 'production']);
        $this->post('/admin/ppob/deposit/1/simulate')->assertForbidden();
        $this->assertNull(Setting::where('key', 'kiosbank_sandbox_deposit')->value('value'));
    }

    public function test_disabled_service_rejects_checkout_before_payment_requests(): void
    {
        Setting::create(['key' => 'kiosbank_enabled', 'value' => '0']);
        $this->postJson('/ppob/checkout', [])->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_virtual_deposit_is_only_credited_once(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->post('/admin/ppob/deposit/create', [
            'amount' => 100000, 'payment_method' => 'developer_mode',
        ])->assertSessionHasNoErrors();
        $deposit = KiosbankDeposit::firstOrFail();
        $this->assertNull($deposit->bank_account_number);
        $this->post('/admin/ppob/deposit/'.$deposit->id.'/simulate')->assertRedirect();
        $balance = Setting::where('key', 'kiosbank_sandbox_deposit')->value('value');
        $this->post('/admin/ppob/deposit/'.$deposit->id.'/simulate')->assertRedirect();
        $this->assertSame($balance, Setting::where('key', 'kiosbank_sandbox_deposit')->value('value'));
        $this->assertSame('11351850', $balance);
    }

    public function test_production_deposit_page_does_not_offer_virtual_topups(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        Setting::create(['key' => 'kiosbank_mode', 'value' => 'production']);
        Http::fake(fn () => throw new ConnectionException('Provider offline'));
        $this->get('/admin/ppob/deposit')->assertOk()
            ->assertSee('Deposit Production')->assertDontSee('Buat Tiket Simulasi');
    }

    public function test_catalog_sync_imports_new_products_and_preserves_manually_disabled_products(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/getHargaByProductID' => Http::response(['rc' => '00', 'record' => [
                ['code' => '500521', 'name' => 'Existing pulsa', 'price' => 6000],
                ['code' => 'NEW521', 'name' => 'New pulsa', 'price' => 12000],
            ]]),
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => '500521', 'maintenance' => '']),
        ]);
        $service = new KiosbankService('production');
        $service->ensureCatalogSeeded();
        PpobProduct::where('code', '500521')->update(['status' => 'inactive']);

        $result = $service->syncPricesFromKiosbank('21');
        $this->assertSame(1, $result['created_count']);
        $this->assertSame(1, $result['updated_count']);
        $this->assertDatabaseHas('ppob_products', ['code' => 'NEW521', 'operator' => 'Telkomsel', 'base_price' => 12000]);
        $service->syncWithKiosbankApi();
        $this->assertDatabaseHas('ppob_products', ['code' => '500521', 'status' => 'inactive']);
    }
}
