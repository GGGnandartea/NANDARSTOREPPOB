<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\PpobProduct;
use App\Models\Setting;
use App\Models\User;
use App\Services\KiosbankService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PulsaDataCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Http::preventStrayRequests();
        config(['kiosbank' => [
            'profiles' => [
                'production' => ['username' => 'test-live', 'password' => 'test-secret'],
                'development' => ['username' => 'test-dev', 'password' => 'test-secret'],
            ],
            'merchant_id' => 'test-merchant', 'account_id' => 'test-account',
        ]]);
        Setting::create(['key' => 'kiosbank_mode', 'value' => 'production']);
    }

    private function priced(string $code, array $extra = []): void
    {
        PpobProduct::where('code', $code)->update(array_merge([
            'base_price' => 5000, 'price' => 6000, 'margin' => 1000,
            'price_synced_mode' => 'production', 'price_synced_at' => now(),
        ], $extra));
    }

    private function provider(string $active = '', string $maintenance = ''): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => $active, 'maintenance' => $maintenance]),
        ]);
    }

    public function test_catalog_imports_all_documented_codes_without_inventing_prices(): void
    {
        $this->assertDatabaseCount('ppob_products', 511);
        foreach ([
            '500521' => ['pulsa', 'Telkomsel'], '50A121' => ['pulsa', 'by.U'],
            '5D4511' => ['data', 'Indosat'], '500501' => ['data', 'WiFi ID'],
            '5DC481' => ['data', 'Smartfren'], '5DM121' => ['masa_aktif', 'Telkomsel'],
            '5DX521' => ['telepon', 'Telkomsel'],
        ] as $code => [$category, $operator]) {
            $this->assertDatabaseHas('ppob_products', compact('code', 'category', 'operator') + ['base_price' => 0, 'price_synced_mode' => null]);
        }
        $items = $this->getJson('/ppob/products?category=pulsa&prefix=11&type=data')->assertOk()->json('data');
        $this->assertNotEmpty($items);
        $this->assertFalse(collect($items)->contains('can_purchase', true));
        Http::assertNothingSent();
    }

    public function test_menu_filters_operator_and_type_and_only_enables_verified_products(): void
    {
        foreach (['500511', '5D4511', '5D6411', '5D6711', '5D7011'] as $code) $this->priced($code);
        $this->priced('5D7011', ['status' => 'inactive']);
        $this->provider('500511, 5D4511,5D7011', '5D6411');
        $data = collect($this->getJson('/ppob/products?category=pulsa&prefix=11&type=data')->assertOk()->json('data'))->keyBy('code');
        $this->assertTrue($data['5D4511']['can_purchase']);
        $this->assertSame('maintenance', $data['5D6411']['status']);
        $this->assertFalse($data['5D6711']['can_purchase']);
        $this->assertFalse($data['5D7011']['can_purchase']);
        $this->assertFalse($data->has('500511'));
        $this->assertFalse($data->has('5DM111'));
        $this->assertFalse($data->has('5D5221'));
        $pulsa = collect($this->getJson('/ppob/products?category=pulsa&prefix=11&type=pulsa')->assertOk()->json('data'))->keyBy('code');
        $this->assertTrue($pulsa['500511']['can_purchase']);
        $this->assertFalse($pulsa->has('5D4511'));
        $this->getJson('/ppob/products?category=pulsa&type=invalid')->assertUnprocessable();
    }

    public function test_checkout_rechecks_status_before_debiting_or_creating_an_order(): void
    {
        $this->priced('5D4511');
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/get-Active-Product' => Http::sequence()
                ->push(['rc' => '00', 'active' => '5D4511', 'maintenance' => ''])
                ->push(['rc' => '00', 'active' => '', 'maintenance' => '5D4511']),
        ]);
        $this->getJson('/ppob/products?category=pulsa&prefix=11&type=data')->assertOk();
        $user = User::factory()->create(['balance' => 50000]);
        $this->actingAs($user)->postJson('/ppob/checkout', [
            'product_code' => '5D4511', 'product_name' => 'Yellow', 'customer_id' => '085870000755',
            'amount' => 6000, 'payment_method' => 'balance',
        ])->assertUnprocessable()->assertJsonPath('message', 'Produk sedang gangguan.');
        $this->assertSame(50000, (int) $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/SinglePayment'));
    }

    public function test_development_prices_and_unknown_provider_status_cannot_enable_production_sales(): void
    {
        $this->priced('5D4511', ['price_synced_mode' => 'development']);
        $service = new KiosbankService('production');
        $product = PpobProduct::where('code', '5D4511')->firstOrFail();
        $this->assertFalse($service->mobileAvailability($product, ['status' => 'success', 'active' => ['5D4511']])['can_purchase']);
        $this->priced('5D4511');
        Http::fake(fn () => throw new ConnectionException('offline'));
        $items = collect($this->getJson('/ppob/products?category=pulsa&prefix=11&type=data')->assertOk()->json('data'))->keyBy('code');
        $this->assertFalse($items['5D4511']['can_purchase']);
        $this->assertStringContainsString('belum dapat dikonfirmasi', $items['5D4511']['availability_message']);
    }

    public function test_price_sync_classifies_data_and_records_the_environment(): void
    {
        $this->priced('5D6411');
        PpobProduct::where('code', '5D4511')->update(['category' => 'pulsa', 'status' => 'inactive']);
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/getHargaByProductID' => Http::response(['rc' => '00', 'record' => [
                ['code' => '5D4511', 'name' => 'INDOSAT YELLOW 1GB 3 HARI', 'price' => 4500],
                ['code' => '5D6411', 'name' => 'FREEDOM INTERNET 3GB', 'price' => 0],
            ]]),
        ]);
        (new KiosbankService('production'))->syncPricesFromKiosbank('11');
        $this->assertDatabaseHas('ppob_products', ['code' => '5D4511', 'category' => 'data', 'status' => 'inactive', 'base_price' => 4500, 'price_synced_mode' => 'production']);
        $this->assertDatabaseHas('ppob_products', ['code' => '5D6411', 'base_price' => 0, 'price' => 0]);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/getHargaByProductID') && $r['prefixID'] === 'AAE.AAAB');
    }

    public function test_admin_sync_recovers_from_a_cached_development_mock_and_syncs_only_selected_operator(): void
    {
        Setting::where('key', 'kiosbank_mode')->update(['value' => 'development']);
        Http::fake([
            '*/auth/Sign-On' => Http::sequence()->push(['rc' => '99'])->push(['SessionID' => 'recovered-session']),
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => '50A121', 'maintenance' => '']),
            '*/Services/getHargaByProductID' => Http::response(['rc' => '00', 'record' => [
                ['code' => '50A121', 'name' => 'by.U Reguler 3.000', 'price' => 3100],
            ]]),
        ]);
        $this->assertStringStartsWith('DEV-SESSION-', (new KiosbankService())->getSessionId());
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/ppob/products/sync', ['prefix' => 'byu'])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('ppob_products', ['code' => '50A121', 'operator' => 'by.U', 'base_price' => 3100, 'price_synced_mode' => 'development']);
        $this->assertDatabaseHas('ppob_products', ['code' => '500521', 'price_synced_mode' => null]);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/getHargaByProductID') && $r['prefixID'] === 'AAE.AAAH');
        $items = collect($this->getJson('/ppob/products?category=pulsa&prefix=byu&type=pulsa')->assertOk()->json('data'))->keyBy('code');
        $this->assertTrue($items['50A121']['can_purchase']);
    }

    public function test_admin_sync_reports_tls_failure_without_marking_prices_as_synced(): void
    {
        Setting::where('key', 'kiosbank_mode')->update(['value' => 'development']);
        Http::fake(fn () => throw new ConnectionException('cURL error 60: SSL certificate problem: self-signed certificate'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/ppob/products/sync', ['prefix' => '21'])->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Sertifikat TLS Kiosbank'));
        $this->assertDatabaseHas('ppob_products', ['code' => '500521', 'price_synced_mode' => null, 'base_price' => 0]);
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/getHargaByProductID'));
    }

    public function test_admin_sync_names_the_operator_whose_prices_could_not_be_loaded(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => '500521', 'maintenance' => '']),
            '*/Services/getHargaByProductID' => Http::response(['rc' => '99']),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/ppob/products/sync', ['prefix' => '21'])->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Harga belum berhasil diambil: Telkomsel'));
        $this->post('/admin/ppob/products/sync', ['prefix' => 'unknown'])->assertSessionHasErrors('prefix');
    }

    public function test_provider_account_error_is_reported_without_fabricating_availability(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/get-Active-Product' => Http::response(['rc' => '19', 'description' => ' data deposit tidak ditemukan ']),
            '*/Services/getHargaByProductID' => Http::response(['rc' => '19', 'description' => ' data tidak ditemukan ']),
        ]);
        $service = new KiosbankService();
        $this->assertTrue($service->testSignOn()['ok']);
        $this->assertSame('19', $service->mobileProviderStatus(true)['rc']);
        $sync = $service->syncPricesFromKiosbank('21');
        $this->assertSame(0, $sync['updated_count']);
        $this->assertStringContainsString('accountID dan merchantID', $sync['failed_reasons']['21']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/ppob/products/sync', ['prefix' => '21'])->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'rc 19'));
        Http::assertSentCount(4); // One Sign-On reused by the following price/status calls.
    }

    public function test_documented_development_numbers_are_forwarded_without_losing_leading_zeros(): void
    {
        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'dev-session']),
            '*/Services/SinglePayment' => Http::response(['rc' => '0000', 'data' => ['status' => 'BERHASIL', 'sn' => 'fixture-only']]),
        ]);
        $service = new KiosbankService('development');
        foreach ([
            ['000000005000', '500501'], ['000000005000true', '500501'],
            ['087873771162', '500501'], ['500501500501', '500501'],
            ['089679988755', '500541'], ['08813253755', '501081'],
            ['085641045755', '502511'], ['085870000755', '5D4511'],
        ] as [$number, $code]) {
            $this->assertSame('success', $service->singlePayment($code, $number, 5000)['status']);
            Http::assertSent(fn ($r) => str_contains($r->url(), 'development.kiosbank.com') && str_ends_with($r->url(), '/SinglePayment') && $r['productID'] === $code && $r['customerID'] === $number);
        }
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'transaksi.kiosbank.com'));
    }

    public function test_development_mode_enables_local_catalog_and_sandbox_fallback_when_dev_biller_returns_rc_17(): void
    {
        Setting::where('key', 'kiosbank_mode')->update(['value' => 'development']);
        $items = collect($this->getJson('/ppob/products?category=pulsa&prefix=21&type=pulsa')->assertOk()->json('data'))->keyBy('code');
        $this->assertTrue($items['500521']['can_purchase']);
        $this->assertGreaterThan(0, $items['500521']['price']);

        Http::fake([
            '*/auth/Sign-On' => Http::response(['SessionID' => 'dev-session']),
            '*/Services/SinglePayment' => Http::response(['rc' => '17', 'description' => 'Gangguan']),
        ]);
        $service = new KiosbankService('development');
        $resSuccess = $service->singlePayment('500521', '085870000755', 5000);
        $this->assertSame('success', $resSuccess['status']);
        $this->assertTrue($resSuccess['is_sandbox'] ?? false);

        $resPending = $service->singlePayment('500521', '081280982297', 5000);
        $this->assertSame('processing', $resPending['status']);

        $resFailed = $service->singlePayment('500521', '081280982287', 5000);
        $this->assertSame('failed', $resFailed['status']);
    }
}
