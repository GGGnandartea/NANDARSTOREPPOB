<?php

namespace Tests\Feature;

use App\Models\LocalAccount;
use App\Models\Order;
use App\Models\PpobProduct;
use App\Models\Product;
use App\Models\ResellerApiClient;
use App\Models\Setting;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\ResellerOpenApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResellerOpenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        ResellerOpenApiService::clearConfigCache();
    }

    protected function makeSignedHeaders(
        string $apiKey,
        string $secretKey,
        string $method,
        string $path,
        string $rawBody = '',
        ?int $timestamp = null,
        array $extra = []
    ): array {
        $ts = (string) ($timestamp ?? time());
        $sig = ResellerOpenApiService::computeHmacSignature($method, $path, $ts, $rawBody, $secretKey);

        return array_merge([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'X-API-Key'    => $apiKey,
            'X-Timestamp'  => $ts,
            'X-Signature'  => $sig,
        ], $extra);
    }

    public function test_regular_user_and_admin_cannot_generate_credentials_or_access_open_api(): void
    {
        $regularUser = User::create([
            'name'      => 'Regular Member',
            'email'     => 'member@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'user',
            'balance'   => 200000,
            'is_active' => true,
        ]);

        $adminUser = User::create([
            'name'      => 'Super Admin',
            'email'     => 'admin@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'balance'   => 500000,
            'is_active' => true,
        ]);

        // 1. User biasa ditolak saat mencoba generate API Key di menu profil
        $this->actingAs($regularUser)
            ->postJson('/profile/open-api/generate')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'RESELLER_ONLY');

        // 2. Admin ditolak saat mencoba generate API Key Reseller
        $this->actingAs($adminUser)
            ->postJson('/profile/open-api/generate')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'RESELLER_ONLY');

        // 3. Jika mantan reseller diturunkan role-nya menjadi user biasa, kunci API miliknya langsung ditolak 403 RESELLER_ONLY
        $formerReseller = User::create([
            'name'      => 'Former Reseller',
            'email'     => 'former@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'reseller',
            'balance'   => 100000,
            'is_active' => true,
        ]);

        $creds = ResellerOpenApiService::generateOrRotateCredentials($formerReseller, true);
        $formerReseller->update(['role' => 'user']);

        $headers = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', '/api/v1/balance');
        $this->withHeaders($headers)
            ->getJson('/api/v1/balance')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'RESELLER_ONLY');
    }

    public function test_hmac_security_timestamp_nonce_ip_whitelist_cidr_and_ssrf_protection(): void
    {
        $reseller = User::create([
            'name'      => 'VIP Reseller Satu',
            'email'     => 'vip1@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'reseller',
            'balance'   => 150000,
            'is_active' => true,
        ]);

        // Generate via endpoint profil Reseller
        $genRes = $this->actingAs($reseller)
            ->postJson('/profile/open-api/generate')
            ->assertOk()
            ->json();

        $apiKey = $genRes['api_key'];
        $secretKey = $genRes['secret_key'];
        $this->assertStringStartsWith('pk_live_', $apiKey);
        $this->assertStringStartsWith('sk_live_', $secretKey);

        // Proteksi SSRF: menolak localhost / port lokal internal (mis. 127.0.0.1:4061)
        $this->actingAs($reseller)
            ->postJson('/profile/open-api/config', [
                'ip_whitelist' => '203.0.113.0/24',
                'webhook_url'  => 'http://127.0.0.1:4061/api/order',
            ])
            ->assertStatus(422)
            ->assertJsonPath('field', 'webhook_url');

        // Simpan IP Whitelist CIDR & Webhook URL eksternal valid
        $this->actingAs($reseller)
            ->postJson('/profile/open-api/config', [
                'ip_whitelist' => "203.0.113.0/24\n198.51.100.77",
                'webhook_url'  => 'https://partner-store.example.com/webhook/pk',
            ])
            ->assertOk();

        // 1. IP tidak masuk whitelist (default 127.0.0.1) -> 403 IP_NOT_WHITELISTED
        $validHeaders = $this->makeSignedHeaders($apiKey, $secretKey, 'GET', '/api/v1/ping');
        $this->withHeaders($validHeaders)
            ->getJson('/api/v1/ping')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'IP_NOT_WHITELISTED');

        // 2. IP masuk subnet CIDR 203.0.113.0/24 (misal 203.0.113.42) -> 200 OK
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($validHeaders)
            ->getJson('/api/v1/ping')
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit')
            ->assertJsonPath('success', true);

        // 3. Signature salah -> 401 INVALID_SIGNATURE
        $badSigHeaders = $validHeaders;
        $badSigHeaders['X-Signature'] = str_repeat('a', 64);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($badSigHeaders)
            ->getJson('/api/v1/ping')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_SIGNATURE');

        // 4. Timestamp kadaluarsa (> 300 detik) -> 401 EXPIRED_TIMESTAMP
        $expiredHeaders = $this->makeSignedHeaders($apiKey, $secretKey, 'GET', '/api/v1/ping', '', time() - 900);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($expiredHeaders)
            ->getJson('/api/v1/ping')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'EXPIRED_TIMESTAMP');

        // 5. Anti-Replay X-Nonce: pakai nonce yang sama 2x -> kedua ditolak 401 REPLAY_DETECTED
        $nonceHeaders = $this->makeSignedHeaders($apiKey, $secretKey, 'GET', '/api/v1/ping', '', null, [
            'X-Nonce' => 'NONCE-UNIK-001',
        ]);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($nonceHeaders)
            ->getJson('/api/v1/ping')
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($nonceHeaders)
            ->getJson('/api/v1/ping')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'REPLAY_DETECTED');

        $this->withoutHeader('X-Nonce');

        // 5b. Anti-Tamper Body POST: tanda tangan dibuat untuk qty=1 namun payload diubah ke qty=5 -> 401 INVALID_SIGNATURE
        $signedPayload = json_encode(['product_id' => 1, 'qty' => 1, 'ref_id' => 'TAMPER-001']);
        $tamperedBodyArr = ['product_id' => 1, 'qty' => 5, 'ref_id' => 'TAMPER-001'];
        $tamperHeaders = $this->makeSignedHeaders($apiKey, $secretKey, 'POST', '/api/v1/orders', $signedPayload);
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($tamperHeaders)
            ->postJson('/api/v1/orders', $tamperedBodyArr)
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_SIGNATURE');

        // 6. Rotasi API Key -> kunci lama langsung tidak berlaku (401 INVALID_API_KEY)
        $rotRes = $this->actingAs($reseller)
            ->postJson('/profile/open-api/rotate')
            ->assertOk()
            ->json();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($this->makeSignedHeaders($apiKey, $secretKey, 'GET', '/api/v1/ping'))
            ->getJson('/api/v1/ping')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'INVALID_API_KEY');

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders($this->makeSignedHeaders($rotRes['api_key'], $rotRes['secret_key'], 'GET', '/api/v1/ping'))
            ->getJson('/api/v1/ping')
            ->assertOk();
    }

    public function test_strict_product_scope_exposes_and_sells_only_local_premku_and_preflix_apps(): void
    {
        Http::fake();

        $reseller = User::create([
            'name'      => 'VIP Catalog Tester',
            'email'     => 'catalog@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'reseller',
            'balance'   => 500000,
            'is_active' => true,
        ]);

        $creds = ResellerOpenApiService::generateOrRotateCredentials($reseller, true);

        $localProd = Product::create([
            'name'           => 'Canva Pro 1 Bulan Lokal',
            'price'          => 20000,
            'reseller_price' => 14000,
            'type'           => 'local',
            'process_type'   => 'auto',
            'stock'          => 2,
            'status'         => 'available',
            'category'       => 'Desain',
        ]);
        LocalAccount::create(['product_id' => $localProd->id, 'email' => 'canva1@test.com', 'password' => 'pass1', 'is_sold' => false]);

        $premkuProd = Product::create([
            'name'           => 'Netflix Sharing 1 Bulan',
            'price'          => 35000,
            'reseller_price' => 27000,
            'api_price'      => 22000,
            'type'           => 'api',
            'api_product_id' => 'PREMKU-555',
            'stock'          => 8,
            'status'         => 'available',
            'category'       => 'Streaming',
        ]);

        $preflixProd = Product::create([
            'name'           => 'Spotify Family 1 Bulan',
            'price'          => 25000,
            'reseller_price' => 19000,
            'api_price'      => 15000,
            'type'           => 'api',
            'api_product_id' => 'PREFLIX-777',
            'stock'          => 5,
            'status'         => 'available',
            'category'       => 'Musik',
        ]);

        // Produk yang HARUS DIKECUALIKAN:
        Setting::updateOrCreate(['key' => 'provider_telegram_disabled'], ['value' => '0']);
        $telegramProd = Product::create([
            'name'           => 'ChatGPT Telegram Bot',
            'price'          => 40000,
            'reseller_price' => 32000,
            'api_price'      => 28000,
            'type'           => 'api',
            'api_product_id' => 'TG-999',
            'stock'          => 10,
            'status'         => 'available',
        ]);

        $unavailableLocal = Product::create([
            'name'           => 'Vidio Nonaktif',
            'price'          => 20000,
            'reseller_price' => 15000,
            'type'           => 'local',
            'status'         => 'unavailable',
        ]);

        PpobProduct::create([
            'code'          => 'XL10',
            'name'          => 'Pulsa XL 10.000',
            'category'      => 'pulsa',
            'operator'      => 'XL',
            'base_price'    => 10100,
            'selling_price' => 11500,
            'is_active'     => true,
        ]);

        // Panggil GET /api/v1/products
        $headers = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', '/api/v1/products');
        $res = $this->withHeaders($headers)
            ->getJson('/api/v1/products')
            ->assertOk()
            ->json();

        $returnedIds = collect($res['data'])->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$localProd->id, $premkuProd->id, $preflixProd->id], $returnedIds);
        $this->assertNotContains($telegramProd->id, $returnedIds);
        $this->assertNotContains($unavailableLocal->id, $returnedIds);

        // Pastikan harga modal internal (api_price), api_product_id, & nama supplier (premku/preflix) tidak pernah bocor; semua ber-branding PremiumKita
        $premkuItem = collect($res['data'])->firstWhere('id', $premkuProd->id);
        $this->assertSame(27000, $premkuItem['price']);
        $this->assertSame('PremiumKita', $premkuItem['provider']);
        $this->assertSame('premiumkita', $premkuItem['source']);
        $this->assertArrayNotHasKey('api_price', $premkuItem);
        $this->assertArrayNotHasKey('api_product_id', $premkuItem);
        $this->assertStringNotContainsStringIgnoringCase('premku', json_encode($res));
        $this->assertStringNotContainsStringIgnoringCase('preflix', json_encode($res));

        // Percobaan paksa melihat / membeli produk Telegram Bot API ditolak 404 PRODUCT_NOT_AVAILABLE
        $detailHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', "/api/v1/products/{$telegramProd->id}");
        $this->withHeaders($detailHeaders)
            ->getJson("/api/v1/products/{$telegramProd->id}")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'PRODUCT_NOT_AVAILABLE');

        $orderBody = json_encode(['ref_id' => 'TRY-TG-001', 'product_id' => $telegramProd->id, 'quantity' => 1]);
        $orderHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'POST', '/api/v1/orders', $orderBody);
        $this->call('POST', '/api/v1/orders', [], [], [], $this->transformHeadersToServerVars($orderHeaders), $orderBody)
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'PRODUCT_NOT_AVAILABLE');

        // Saldo tetap utuh 500.000
        $this->assertSame(500000, (int) $reseller->fresh()->balance);
    }

    public function test_balance_only_order_idempotency_signed_webhook_and_auto_refund(): void
    {
        Http::fake([
            'https://partner-webhook.example.com/*' => Http::response(['ok' => true], 200),
            '*'                                     => Http::response(['ok' => true], 200),
        ]);

        $reseller = User::create([
            'name'      => 'VIP Buyer H2H',
            'email'     => 'h2h@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'reseller',
            'balance'   => 30000,
            'is_active' => true,
        ]);

        $creds = ResellerOpenApiService::generateOrRotateCredentials($reseller, true);
        ResellerApiClient::where('user_id', $reseller->id)->update([
            'webhook_url' => 'https://partner-webhook.example.com/callback',
        ]);

        $product = Product::create([
            'name'           => 'Youtube Premium 1 Bulan',
            'price'          => 25000,
            'reseller_price' => 18000,
            'type'           => 'local',
            'process_type'   => 'auto',
            'stock'          => 2,
            'status'         => 'available',
        ]);
        LocalAccount::create([
            'product_id' => $product->id,
            'email'      => 'yt.vip@premiumkita.com',
            'password'   => 'Rahasia999',
            'is_sold'    => false,
        ]);
        LocalAccount::create([
            'product_id' => $product->id,
            'email'      => 'yt.vip2@premiumkita.com',
            'password'   => 'Rahasia888',
            'is_sold'    => false,
        ]);

        // 1. Jika saldo kurang (beli 2 pcs = Rp 36.000 > saldo Rp 30.000) -> ditolak 402 INSUFFICIENT_BALANCE
        $insufficientBody = json_encode([
            'ref_id'     => 'REF-KURANG-SALDO',
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
        $insHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'POST', '/api/v1/orders', $insufficientBody);
        $this->call('POST', '/api/v1/orders', [], [], [], $this->transformHeadersToServerVars($insHeaders), $insufficientBody)
            ->assertStatus(402)
            ->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');

        $this->assertSame(30000, (int) $reseller->fresh()->balance);

        // 2. Order valid 1 pcs (Rp 18.000) -> sukses potong saldo jadi Rp 12.000, akun langsung dikirim & webhook terkirim
        $validBody = json_encode([
            'ref_id'         => 'REF-SUKSES-001',
            'product_code'   => 'PK-APP-' . $product->id,
            'quantity'       => 1,
            'customer_name'  => 'Pelanggan Toko Reseller',
            'customer_email' => 'buyer@tokoreseller.id',
        ]);
        $ordHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'POST', '/api/v1/orders', $validBody);
        $ordRes = $this->call('POST', '/api/v1/orders', [], [], [], $this->transformHeadersToServerVars($ordHeaders), $validBody)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_status', 'completed')
            ->assertJsonPath('data.total_amount', 18000)
            ->assertJsonPath('data.balance_remaining', 12000)
            ->assertJsonPath('data.idempotent_replay', false)
            ->json();

        $invoiceNumber = $ordRes['data']['invoice_number'];
        $this->assertStringContainsString('yt.vip@premiumkita.com', $ordRes['data']['account_details']);
        $this->assertSame(12000, (int) $reseller->fresh()->balance);

        // Verifikasi Webhook order.completed terkirim dengan X-PK-Signature valid
        Http::assertSent(function (HttpClientRequest $req) use ($creds) {
            if ($req->url() !== 'https://partner-webhook.example.com/callback') {
                return false;
            }
            $ts = $req->header('X-PK-Timestamp')[0] ?? '';
            $sig = $req->header('X-PK-Signature')[0] ?? '';
            $expected = 'sha256=' . hash_hmac('sha256', $ts . '.' . $req->body(), $creds['webhook_secret']);

            return ($req->header('X-PK-Event')[0] ?? '') === 'order.completed'
                && hash_equals($expected, $sig);
        });

        // 3. Retry jaringan dengan ref_id SAMA -> Idempotent Replay (saldo TIDAK terpotong lagi!)
        $retryHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'POST', '/api/v1/orders', $validBody);
        $this->call('POST', '/api/v1/orders', [], [], [], $this->transformHeadersToServerVars($retryHeaders), $validBody)
            ->assertOk()
            ->assertJsonPath('data.invoice_number', $invoiceNumber)
            ->assertJsonPath('data.idempotent_replay', true);

        $this->assertSame(12000, (int) $reseller->fresh()->balance);

        // 4. Konflik ref_id jika dipakai untuk quantity berbeda -> 409 REF_ID_CONFLICT
        $conflictBody = json_encode([
            'ref_id'     => 'REF-SUKSES-001',
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
        $conflictHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'POST', '/api/v1/orders', $conflictBody);
        $this->call('POST', '/api/v1/orders', [], [], [], $this->transformHeadersToServerVars($conflictHeaders), $conflictBody)
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'REF_ID_CONFLICT');

        // 5. Cek status order via GET /api/v1/orders/{ref_id}
        $statusHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', '/api/v1/orders/REF-SUKSES-001');
        $this->withHeaders($statusHeaders)
            ->getJson('/api/v1/orders/REF-SUKSES-001')
            ->assertOk()
            ->assertJsonPath('data.invoice_number', $invoiceNumber)
            ->assertJsonPath('data.order_status', 'completed');

        // 6. Jika pesanan dibatalkan / gagal -> saldo kembali 100% (12.000 + 18.000 = 30.000) & webhook order.refunded dikirim
        $orderModel = Order::where('invoice_number', $invoiceNumber)->firstOrFail();
        OrderFulfillmentService::cancelOrFailOrderWithRefund($orderModel, 'refunded', 'Pengujian refund otomatis Open API');

        $this->assertSame(30000, (int) $reseller->fresh()->balance);

        Http::assertSent(function (HttpClientRequest $req) {
            return $req->url() === 'https://partner-webhook.example.com/callback'
                && ($req->header('X-PK-Event')[0] ?? '') === 'order.refunded';
        });
    }

    public function test_admin_can_configure_open_api_scope_and_manage_clients(): void
    {
        $admin = User::create([
            'name'      => 'Admin Utama',
            'email'     => 'admin.openapi@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $reseller = User::create([
            'name'      => 'Reseller Mitra',
            'email'     => 'mitra@example.com',
            'password'  => Hash::make('password123'),
            'role'      => 'reseller',
            'balance'   => 100000,
            'is_active' => true,
        ]);

        $creds = ResellerOpenApiService::generateOrRotateCredentials($reseller, true);

        $premkuProd = Product::create([
            'name'           => 'CapCut Pro Premku',
            'price'          => 30000,
            'reseller_price' => 22000,
            'type'           => 'api',
            'api_product_id' => 'PREMKU-888',
            'stock'          => 5,
            'status'         => 'available',
        ]);

        // Halaman admin terbuka normal untuk Admin
        $this->actingAs($admin)
            ->get('/admin/open-api')
            ->assertOk()
            ->assertSee('Open API H2H Khusus VIP Reseller');

        // Admin mematikan sumber Premku dari cakupan Open API & menambah extra_fee Rp 500
        $this->actingAs($admin)
            ->post('/admin/open-api/save', [
                'enabled'              => '1',
                'auth_mode'            => 'hmac_strict',
                'timestamp_tolerance'  => 300,
                'rate_limit_rpm'       => 80,
                'order_rate_limit_rpm' => 25,
                'max_qty_per_order'    => 10,
                'allow_local'          => '1',
                'allow_local_manual'   => '1',
                'allow_preflix'        => '1',
                // allow_premku tidak dicentang -> nonaktif untuk Open API
                'extra_fee'            => 500,
                'webhook_enabled'      => '1',
                'maintenance_message'  => 'Sedang maintenance.',
            ])
            ->assertRedirect();

        // Produk Premku tidak lagi bisa diakses lewat Open API
        $headers = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', "/api/v1/products/{$premkuProd->id}");
        $this->withHeaders($headers)
            ->getJson("/api/v1/products/{$premkuProd->id}")
            ->assertStatus(404)
            ->assertJsonPath('error_code', 'PRODUCT_NOT_AVAILABLE');

        // Admin melakukan Suspend pada klien Reseller -> 403 API_SUSPENDED_BY_ADMIN
        $this->actingAs($admin)
            ->post("/admin/open-api/clients/{$reseller->id}/suspend", ['reason' => 'Audit keamanan'])
            ->assertRedirect();

        $pingHeaders = $this->makeSignedHeaders($creds['api_key'], $creds['secret_key'], 'GET', '/api/v1/ping');
        $this->withHeaders($pingHeaders)
            ->getJson('/api/v1/ping')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'API_SUSPENDED_BY_ADMIN');
    }
}
