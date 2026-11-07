<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\TelegramBotApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiCatalogAndTelegramBotTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_deactivation_and_custom_name_are_preserved_and_new_products_auto_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::updateOrCreate(['key' => 'premku_api_key'], ['value' => 'pk_test_123']);
        Setting::updateOrCreate(['key' => 'api_auto_margin_member'], ['value' => '10000']);
        Setting::updateOrCreate(['key' => 'api_auto_margin_reseller'], ['value' => '5000']);

        // Produk 1: Sudah ada di DB, nama sudah diubah manual oleh Admin & status dinonaktifkan manual ('unavailable')
        $existingOff = Product::create([
            'api_product_id' => 'PREMKU-101',
            'type' => 'api',
            'name' => 'Canva Pro 1 Bulan Kustom Admin',
            'api_price' => 3000,
            'api_margin' => 10000,
            'price' => 13000,
            'api_reseller_margin' => 5000,
            'reseller_price' => 8000,
            'stock' => 10,
            'status' => 'unavailable',
            'category' => 'Aplikasi Premium',
        ]);

        Http::fake([
            '*premku.com/api/products*' => Http::response([
                'success' => true,
                'products' => [
                    [
                        'id' => 101,
                        'name' => 'Canva Pro 1 Bulan Original Provider',
                        'price' => 4500,
                        'stock' => 25,
                        'description' => 'Garansi penuh',
                    ],
                    [
                        'id' => 202,
                        'name' => 'Netflix Private 1 Bulan Baru',
                        'price' => 25000,
                        'stock' => 12,
                        'description' => 'Produk baru otomatis tampil',
                    ],
                ],
            ], 200),
        ]);

        // Klik "Panggil & Simpan API"
        $response = $this->actingAs($admin)->post(route('admin.products.api.sync_provider'), [
            'provider' => 'premku',
            'default_margin' => 10000,
            'reseller_margin' => 5000,
        ]);

        $response->assertRedirect();

        // 1. Pastikan produk yang dinonaktifkan manual tetap 'unavailable' dan namanya tidak ter-reset!
        $existingOff->refresh();
        $this->assertSame('unavailable', $existingOff->status);
        $this->assertSame('Canva Pro 1 Bulan Kustom Admin', $existingOff->name);
        $this->assertSame(4500, (int) $existingOff->api_price);
        $this->assertSame(14500, (int) $existingOff->price);

        // 2. Pastikan produk baru (PREMKU-202) otomatis masuk dan aktif dengan margin 10.000 & 5.000
        $newProd = Product::where('api_product_id', 'PREMKU-202')->first();
        $this->assertNotNull($newProd);
        $this->assertSame('available', $newProd->status);
        $this->assertSame('Netflix Private 1 Bulan Baru', $newProd->name);
        $this->assertSame(25000, (int) $newProd->api_price);
        $this->assertSame(35000, (int) $newProd->price);
        $this->assertSame(30000, (int) $newProd->reseller_price);
    }

    public function test_telegram_bot_api_defaults_to_disabled_and_fulfills_dynamic_fields_when_enabled(): void
    {
        // Default harus OFF sebelum rilis
        $this->assertFalse(TelegramBotApiService::isEnabledForUsers());

        Setting::updateOrCreate(['key' => 'telegram_api_key'], ['value' => 'qv_live_testkey']);
        Setting::updateOrCreate(['key' => 'telegram_api_base_url'], ['value' => 'https://www.quantumvault.me/api/v1']);
        Setting::updateOrCreate(['key' => 'telegram_usd_rate'], ['value' => '16000']);
        Setting::updateOrCreate(['key' => 'provider_telegram_disabled'], ['value' => '0']);

        $this->assertTrue(TelegramBotApiService::isEnabledForUsers());

        Http::fake([
            'https://www.quantumvault.me/api/v1/products' => Http::response([
                'ok' => true,
                'count' => 1,
                'products' => [
                    [
                        'productKey' => 'netflix_link',
                        'name' => 'Netflix Activation Link',
                        'description' => 'Instant activation link',
                        'price' => null,
                        'currency' => 'USD',
                        'inStock' => true,
                        'unlimited' => false,
                        'variants' => [
                            [
                                'key' => '1_month',
                                'name' => '1 Month',
                                'price' => 2.50,
                                'inStock' => true,
                                'stock' => 15,
                                'unlimited' => false,
                                'warranty' => ['durationDays' => 30, 'details' => 'Full replacement'],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://www.quantumvault.me/api/v1/purchase' => Http::response([
                'ok' => true,
                'order' => [
                    'productKey' => 'netflix_link',
                    'variantKey' => '1_month',
                    'fulfilled' => 1,
                    'items' => [
                        [
                            'unit' => 1,
                            'orderId' => 'QV-8F3A21C9',
                            'purchaseId' => '6640a1e8c2d4f00012ab34cd',
                            'fields' => [
                                ['name' => 'activation_url', 'label' => 'Activation link', 'value' => 'https://example.com/activate/abc123'],
                                ['name' => 'pin_code', 'label' => 'PIN', 'value' => '9988'],
                            ],
                            'data' => [
                                'activation_url' => 'https://example.com/activate/abc123',
                                'pin_code' => '9988',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $tgService = new TelegramBotApiService();
        $catalog = $tgService->getNormalizedCatalog();
        $this->assertArrayHasKey('TG-netflix_link::1_month', $catalog);
        $this->assertSame(40000, $catalog['TG-netflix_link::1_month']['price']); // 2.50 * 16000

        // Auto-discovery dari live map
        Product::syncAllApiProductsFromLiveMap($catalog);
        $tgProduct = Product::where('api_product_id', 'TG-netflix_link::1_month')->first();
        $this->assertNotNull($tgProduct);
        $this->assertSame(50000, (int) $tgProduct->price); // 40000 + 10000 default margin

        // Test auto-fulfillment order TG-*
        $order = Order::create([
            'invoice_number' => 'ORD-TG-TEST01',
            'product_id' => $tgProduct->id,
            'product_name' => $tgProduct->name,
            'product_type' => 'api',
            'provider' => 'Telegram API',
            'quantity' => 1,
            'price' => 50000,
            'total_amount' => 50000,
            'payment_status' => 'paid',
            'order_status' => 'processing',
        ]);

        $res = OrderFulfillmentService::fulfillOrder($order);
        $order->refresh();

        $this->assertTrue($res['is_ready']);
        $this->assertSame('completed', $order->order_status);
        $this->assertSame('QV-8F3A21C9', $order->api_trx_id);
        $this->assertStringContainsString('Activation link : https://example.com/activate/abc123', (string) $order->sn_or_account);
        $this->assertStringContainsString('PIN : 9988', (string) $order->sn_or_account);
    }

    public function test_premku_low_balance_whatsapp_alert_to_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Setting::updateOrCreate(['key' => 'premku_api_key'], ['value' => 'pk_live_test_alert']);
        Setting::updateOrCreate(['key' => 'premku_low_balance_alert_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'premku_low_balance_threshold'], ['value' => '50000']);
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '082317458496']);
        Setting::updateOrCreate(['key' => 'wa_gateway_url'], ['value' => 'http://127.0.0.1:5001']);

        \Illuminate\Support\Facades\Cache::forget('premku_low_balance_alert_sent');

        Http::fake([
            '*premku.com/api/profile*' => Http::response([
                'success' => true,
                'data' => [
                    'username' => 'premiumkita_store',
                    'saldo'    => 18500,
                ],
            ], 200),
            '*127.0.0.1:5001/api/send*' => Http::response([
                'status' => 'success',
            ], 200),
        ]);

        $premkuService = new \App\Services\PremkuApiService();
        $alertRes = $premkuService->checkAndNotifyLowBalance();

        $this->assertTrue($alertRes['ok']);
        $this->assertTrue($alertRes['is_low']);
        $this->assertTrue($alertRes['sent']);
        $this->assertSame(18500, $alertRes['saldo']);
        $this->assertSame(50000, $alertRes['threshold']);

        // Panggilan kedua harus terkena cooldown anti-spam
        $secondRes = $premkuService->checkAndNotifyLowBalance();
        $this->assertFalse($secondRes['sent']);
        $this->assertSame('cooldown', $secondRes['reason']);

        // Admin juga dapat menyimpan & menguji langsung dari endpoint admin
        $resp = $this->actingAs($admin)->post(route('admin.settings.premku.low_balance_alert'), [
            'enabled'        => '1',
            'threshold'      => 75000,
            'admin_whatsapp' => '082317458496',
            'action'         => 'test',
        ]);
        $resp->assertRedirect();
        $this->assertSame('75000', Setting::where('key', 'premku_low_balance_threshold')->value('value'));
    }
}
