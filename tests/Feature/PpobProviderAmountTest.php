<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\PpobProduct;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PpobProviderAmountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Http::preventStrayRequests();
        config(['kiosbank.profiles.production' => ['username' => 'test-user', 'password' => 'test-secret']]);
        Setting::create(['key' => 'kiosbank_mode', 'value' => 'production']);
        Http::fake([
            '127.0.0.1:4061/*' => fn ($request) => Http::response([
                'data' => [
                    'orderId' => Order::where('invoice_number', 'PPOB-PRIVATE')->exists() ? 'PPOB-PRIVATE-2' : 'PPOB-PRIVATE',
                    'total_nominal' => 7123,
                    'url_qris' => 'https://example.test/qr.png',
                ],
            ]),
            '*/auth/Sign-On' => Http::response(['SessionID' => 'test-session']),
            '*/Services/SinglePayment' => function ($request) {
                if (($request['customerID'] ?? '') === '081280982287') {
                    return Http::response([
                        'rc' => '67',
                        'description' => 'Terjadi kegagalan saat proses transaksi',
                    ]);
                }
                if (($request['customerID'] ?? '') === '081280982297') {
                    return Http::response([
                        'rc' => '00',
                        'data' => [
                            'status' => 'SEDANG DIPROSES',
                            'noReferensi' => '261024123456',
                        ],
                    ]);
                }

                return Http::response(['rc' => '00', 'data' => ['status' => 'BERHASIL', 'sn' => 'test-token']]);
            },
            '*/Services/Payment' => Http::response(['rc' => '00', 'data' => ['status' => 'BERHASIL', 'sn' => 'bill-token']]),
            '*/Services/Inquiry' => function ($request) {
                $pid = (string) ($request['productID'] ?? '');
                $cid = (string) ($request['customerID'] ?? '');
                $aid = (string) ($request['additionalID'] ?? '');
                if ($pid === '6707BE') {
                    return Http::response([
                        'rc' => '00',
                        'description' => 'SUCCESS',
                        'customerID' => $cid,
                        'additionalID' => $aid,
                        'productID' => $pid,
                        'data' => [
                            'noHandphone' => $cid,
                            'produk' => 'MOBILE LEGEND 170 DIAMONDS',
                            'harga' => '45950',
                        ],
                    ]);
                }
                if ($pid === '752110') {
                    return Http::response([
                        'rc' => '00',
                        'description' => 'Sukses',
                        'customerID' => $cid,
                        'productID' => $pid,
                        'data' => [
                            'adminBank' => '1500',
                            'harga' => '20000',
                            'nama' => 'TopUp DANA 20.000 ',
                            'noReferensi' => '261110123456',
                            'noHandphone' => $cid,
                            'total' => '21500',
                        ],
                    ]);
                }
                if ($pid === '754161') {
                    return Http::response([
                        'rc' => '00',
                        'description' => 'Sukses',
                        'customerID' => $cid,
                        'productID' => $pid,
                        'data' => [
                            'adminBank' => '0',
                            'harga' => '50000',
                            'nama' => 'PELANGGAN DANA / OVO',
                            'noReferensi' => '261110123456',
                            'noHandphone' => $cid,
                            'total' => '50000',
                        ],
                    ]);
                }
                if ($pid === '100302') {
                    return Http::response([
                        'rc' => '00',
                        'description' => 'Sukses',
                        'customerID' => $cid,
                        'productID' => $pid,
                        'data' => [
                            'NM' => 'PELANGGAN PLN PRABAYAR',
                            'TF' => 'R1',
                            'DY' => '000001300',
                            'ID' => $cid,
                            'AB' => '000000002750',
                        ],
                    ]);
                }

                return Http::response(['rc' => '14', 'description' => 'ID Pelanggan tidak ditemukan']);
            },
            '*/Services/get-Active-Product' => Http::response(['rc' => '00', 'active' => 'TEST-PULSA', 'maintenance' => '']),
            '*/Services/Check-Status' => function ($request) {
                if (($request['customerID'] ?? '') === '081280982297') {
                    return Http::response([
                        'rc' => '00',
                        'data' => [
                            'status' => 'BERHASIL',
                            'sn' => 'SN-RECHECK-999',
                            'noReferensi' => '261024123456',
                            'saldo' => 450000,
                        ],
                    ]);
                }
                if (($request['customerID'] ?? '') === '081280982298') {
                    return Http::response([
                        'rc' => '67',
                        'description' => 'Transaksi Gagal saat Check-Status',
                        'data' => [
                            'status' => 'GAGAL',
                        ],
                    ]);
                }

                return Http::response([
                    'rc' => '71',
                    'description' => 'Transaksi masih dalam proses',
                ]);
            },
        ]);
    }

    private function product(): PpobProduct
    {
        return PpobProduct::create([
            'code' => 'TEST-PULSA', 'name' => 'Pulsa', 'category' => 'pulsa', 'operator' => 'Test',
            'base_price' => 5650, 'margin' => 2350, 'price' => 8000, 'reseller_price' => 7500, 'status' => 'active',
            'price_synced_mode' => 'production', 'price_synced_at' => now(),
        ]);
    }

    private function checkoutPayload(array $changes = []): array
    {
        return array_replace([
            'product_code' => 'TEST-PULSA', 'product_name' => 'Untrusted name',
            'customer_id' => '081234567890', 'amount' => 999999, 'payment_method' => 'qris',
        ], $changes);
    }

    public function test_qris_discount_and_fee_do_not_change_frozen_provider_cost_even_after_catalog_changes(): void
    {
        $product = $this->product();
        Voucher::create(['code' => 'PROMO', 'type' => 'nominal', 'value' => 1000, 'is_active' => true]);
        $response = $this->postJson('/ppob/checkout', $this->checkoutPayload(['voucher_code' => 'PROMO']))
            ->assertOk()->assertJsonPath('status', 'success');
        $order = Order::firstOrFail();
        $this->assertSame($order->invoiceUrl(), $response->json('invoice_url'));
        $this->getJson('/ppob/status/'.$order->invoice_number)->assertOk();
        $this->assertSame(5650, $order->provider_amount);
        $this->assertSame(8000, (int) $order->price);
        $this->assertSame(1000, (int) $order->discount);
        $this->assertSame(7123, (int) $order->total_amount);
        Http::assertSent(fn ($r) => str_contains($r->url(), ':4061/') && (int) $r['nominal'] === 7000);

        $product->update(['base_price' => 6000, 'price' => 9000]);
        $order->update(['payment_status' => 'paid']);
        OrderFulfillmentService::fulfillOrder($order);
        OrderFulfillmentService::fulfillOrder($order);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/SinglePayment')
            && (int) $r['tagihan'] === 5650 && (int) $r['total'] === 5650
            && $r['productID'] === 'TEST-PULSA' && $r['customerID'] === '081234567890');
        $this->assertCount(1, Http::recorded(fn ($r) => str_ends_with($r->url(), '/SinglePayment')));
        $this->assertSame('completed', $order->fresh()->order_status);
    }

    public function test_reseller_balance_charge_and_provider_amount_are_independent(): void
    {
        $this->product();
        $user = User::factory()->create(['role' => 'reseller', 'balance' => 20000]);
        $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload(['payment_method' => 'balance']))
            ->assertOk()->assertJsonPath('order_status', 'completed');
        $this->assertSame(12500, (int) $user->fresh()->balance);
        $order = Order::firstOrFail();
        $this->assertSame(7500, (int) $order->total_amount);
        $this->assertSame(5650, $order->provider_amount);
        $this->assertNull($order->guest_access_token);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/SinglePayment') && (int) $r['total'] === 5650);
    }

    public function test_bill_uses_server_inquiry_total_including_biller_admin_but_excluding_store_markup(): void
    {
        Cache::put('ppob_inquiry:TEST-INQUIRY', [
            'product_id' => 'BILL', 'customer_id' => '081234567890',
            'amount' => 50000, 'admin_fee' => 2500, 'total' => 52500, 'grand_total' => 55000,
            'reference_id' => '123456789012', 'expires_at' => now()->addMinutes(10)->timestamp,
            'kiosbank_mode' => 'production',
        ]);
        $user = User::factory()->create(['balance' => 100000]);
        $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload([
            'product_code' => 'BILL', 'payment_method' => 'balance', 'inquiry_key' => 'TEST-INQUIRY',
        ]))->assertOk()->assertJsonPath('order_status', 'completed');
        $order = Order::firstOrFail();
        $this->assertSame(55000, (int) $order->total_amount);
        $this->assertSame(52500, $order->provider_amount);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/Payment')
            && (int) $r['total'] === 52500 && (int) $r['tagihan'] === 50000 && (int) $r['admin'] === 2500);
    }

    public function test_old_prepaid_order_without_verified_provider_amount_is_not_sent_at_customer_price(): void
    {
        $order = Order::create([
            'invoice_number' => 'OLD-PPOB', 'product_type' => 'ppob', 'product_name' => 'Pulsa',
            'price' => 8000, 'total_amount' => 8123, 'payment_status' => 'paid', 'order_status' => 'processing',
        ]);
        $this->assertSame('error', OrderFulfillmentService::fulfillOrder($order)['status']);
        $this->assertNull($order->fresh()->fulfillment_started_at);
        Http::assertNothingSent();
    }

    public function test_report_uses_recorded_provider_cost_and_preserves_a_loss_after_discount(): void
    {
        Order::create([
            'invoice_number' => 'DISCOUNTED-PPOB', 'product_type' => 'ppob', 'product_name' => 'Pulsa',
            'price' => 8000, 'discount' => 3000, 'total_amount' => 5000, 'provider_amount' => 5650,
            'payment_status' => 'paid', 'order_status' => 'completed',
        ]);
        Setting::create(['key' => 'kiosbank_markup_value', 'value' => '9999']);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/reports?period=7_days')->assertOk()
            ->assertViewHas('totalCost', 5650.0)
            ->assertViewHas('totalProfit', -650.0)
            ->assertViewHas('chartProfit', fn ($profits) => array_sum($profits) === -650)
            ->assertViewHas('monthlyProfitData', fn ($profits) => array_sum($profits) === -650);
    }

    public function test_failed_ppob_transaction_marks_order_failed_and_auto_refunds_user_balance(): void
    {
        $this->product();

        $user = User::factory()->create(['balance' => 20000]);
        $response = $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload([
            'customer_id' => '081280982287',
            'payment_method' => 'balance',
        ]))->assertOk();

        $response->assertJsonPath('order_status', 'failed')
            ->assertJsonPath('is_refunded', true)
            ->assertJsonPath('user_balance', 20000);

        $order = Order::firstOrFail();
        $this->assertSame('failed', $order->order_status);
        $this->assertNull($order->sn_or_account);
        $this->assertSame(20000, (int) $user->fresh()->balance);
        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => 8000,
        ]);
    }

    public function test_pending_ppob_transaction_stays_processing_and_refunds_on_failed_kiosbank_webhook(): void
    {
        $this->product();

        $user = User::factory()->create(['balance' => 20000]);
        $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload([
            'customer_id' => '081280982297',
            'payment_method' => 'balance',
        ]))->assertOk()
            ->assertJsonPath('order_status', 'processing')
            ->assertJsonPath('is_refunded', false)
            ->assertJsonPath('user_balance', 12000);

        $order = Order::firstOrFail();
        $this->assertSame('processing', $order->order_status);
        $this->assertNull($order->sn_or_account);
        $this->assertStringContainsString('Status: Pending', (string) $order->notes);

        // Simulate async Kiosbank webhook reporting GAGAL
        $this->postJson('/api/webhook/kiosbank', [
            'rc' => '67',
            'referenceID' => $order->api_trx_id,
            'description' => 'Transaksi gagal di operator',
            'data' => [
                'noReferensi' => $order->api_trx_id,
                'status' => 'GAGAL',
            ],
        ])->assertOk();

        $this->assertSame('failed', $order->fresh()->order_status);
        $this->assertSame(20000, (int) $user->fresh()->balance);
        $this->assertDatabaseHas('transaction_logs', [
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => 8000,
        ]);
    }

    public function test_pending_ppob_order_can_be_reconciled_via_check_status_endpoint_for_success_and_failure(): void
    {
        $this->product();
        $user = User::factory()->create(['balance' => 25000]);

        // 1. Order that starts pending and resolves to BERHASIL on /ppob/check-status/{orderId}
        $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload([
            'customer_id' => '081280982297',
            'payment_method' => 'balance',
        ]))->assertOk()->assertJsonPath('order_status', 'processing');

        $orderSuccess = Order::latest('id')->firstOrFail();

        $this->actingAs($user)->postJson('/ppob/check-status/'.$orderSuccess->invoice_number)
            ->assertOk()
            ->assertJsonPath('order_status', 'completed')
            ->assertJsonPath('sn', 'SN/Token: SN-RECHECK-999');

        $this->assertSame('completed', $orderSuccess->fresh()->order_status);
        $this->assertSame('SN/Token: SN-RECHECK-999', $orderSuccess->fresh()->sn_or_account);

        // 2. Order that is pending and resolves to GAGAL on /ppob/check-status/{orderId} -> auto-refunds
        $orderFail = Order::create([
            'user_id' => $user->id,
            'invoice_number' => 'PPOB-PENDING-FAIL',
            'product_type' => 'ppob',
            'product_name' => '[PULSA] Pulsa (TEST-PULSA)',
            'provider_customer_id' => '081280982298',
            'provider_product_code' => 'TEST-PULSA',
            'price' => 8000,
            'total_amount' => 8000,
            'provider_amount' => 5650,
            'payment_method' => 'balance',
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'api_trx_id' => '123456789099',
            'notes' => 'Status: Pending | Ref: 123456789099 | ProductID: TEST-PULSA',
        ]);

        $balanceBefore = (int) $user->fresh()->balance;

        $this->actingAs($user)->postJson('/ppob/check-status/'.$orderFail->invoice_number)
            ->assertOk()
            ->assertJsonPath('order_status', 'failed')
            ->assertJsonPath('is_refunded', true);

        $this->assertSame('failed', $orderFail->fresh()->order_status);
        $this->assertSame($balanceBefore + 8000, (int) $user->fresh()->balance);
    }

    public function test_low_balance_alert_sends_whatsapp_notification_when_below_threshold_with_cooldown(): void
    {
        Setting::updateOrCreate(['key' => 'kiosbank_low_balance_alert_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'kiosbank_low_balance_threshold'], ['value' => '200000']);
        Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => '081299998888']);
        Setting::updateOrCreate(['key' => 'wa_gateway_url'], ['value' => 'http://127.0.0.1:3000']);

        Http::fake([
            '127.0.0.1:3000/*' => Http::response(['status' => true]),
        ]);

        $kiosbank = new \App\Services\KiosbankService;

        // Balance is 150,000 <= 200,000 -> should send WA alert
        $res1 = $kiosbank->checkAndNotifyLowBalance(150000);
        $this->assertTrue($res1['ok']);
        $this->assertTrue($res1['sent']);
        $this->assertTrue($res1['is_low']);

        // Second immediate call without forceSend -> should be skipped due to 4h cooldown
        $res2 = $kiosbank->checkAndNotifyLowBalance(150000);
        $this->assertFalse($res2['sent']);
        $this->assertSame('cooldown', $res2['reason']);
    }

    public function test_profile_stats_exclude_refund_from_topup_income_and_guest_failed_invoice_shows_wa_refund_claim(): void
    {
        config(['inertia.ssr.enabled' => false]);
        $this->product();

        $user = User::factory()->create(['balance' => 20000]);
        \App\Models\TransactionLog::create([
            'user_id' => $user->id,
            'type' => 'topup',
            'amount' => 20000,
            'description' => 'Top Up Saldo via QRIS',
        ]);

        // Failed purchase of Rp 8,000 -> debited 8,000 then auto-refunded 8,000
        $this->actingAs($user)->postJson('/ppob/checkout', $this->checkoutPayload([
            'customer_id' => '081280982287',
            'payment_method' => 'balance',
        ]))->assertOk()->assertJsonPath('order_status', 'failed');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total_income', 20000)
                ->where('stats.total_expense', 0)
                ->where('stats.total_refund', 8000)
                ->where('stats.income_count', 1)
                ->where('stats.expense_count', 0)
                ->where('stats.refund_count', 1)
            );

        // Guest QRIS order that failed -> invoice shows WhatsApp Admin refund claim & Beli Lagi button
        $guestOrder = Order::create([
            'user_id' => null,
            'invoice_number' => 'PPOB-GUEST-FAIL',
            'product_type' => 'ppob',
            'product_name' => '[PULSA] Pulsa (TEST-PULSA)',
            'provider_customer_id' => '081280982287',
            'provider_product_code' => 'TEST-PULSA',
            'price' => 8000,
            'total_amount' => 8123,
            'provider_amount' => 5650,
            'payment_method' => 'qris',
            'payment_status' => 'paid',
            'order_status' => 'failed',
            'guest_access_token' => 'guest-secret-token-123',
        ]);

        $this->get($guestOrder->invoiceUrl())
            ->assertOk()
            ->assertSee('Panduan Klaim Refund Pembeli Publik (QRIS)')
            ->assertSee('Bawa Bukti Pembelian')
            ->assertSee('Beli Lagi');
    }

    public function test_pre_purchase_target_inquiry_and_refresh_proof_qris_checkout_recovery(): void
    {
        // 1. Pre-purchase inquiry for MLBB Game (6707BE with zone_id)
        $gameInquiry = $this->postJson('/ppob/inquiry', [
            'customer_id' => '48122786',
            'zone_id' => '2114',
            'product_code' => '6707BE',
        ])->assertOk()->json();

        $this->assertSame('success', $gameInquiry['status']);
        $this->assertStringContainsString('48122786', (string) ($gameInquiry['customer_name'] ?? ''));
        $this->assertStringContainsString('2114', (string) ($gameInquiry['customer_name'] ?? ''));

        // 2. Pre-purchase inquiry for E-Wallet Fixed Denom (752110) and Open Denom (754161)
        $ewalletInquiry = $this->postJson('/ppob/inquiry', [
            'customer_id' => '081111111111',
            'product_code' => '752110',
        ])->assertOk()->json();

        $this->assertSame('success', $ewalletInquiry['status']);
        $this->assertNotEmpty($ewalletInquiry['customer_name'] ?? '');

        $ewalletOpenInquiry = $this->postJson('/ppob/inquiry', [
            'customer_id' => '081111111111',
            'product_code' => '754161',
        ])->assertOk()->json();

        $this->assertSame('success', $ewalletOpenInquiry['status']);
        $this->assertSame('PELANGGAN DANA / OVO', $ewalletOpenInquiry['customer_name']);

        // 3. Pre-purchase inquiry for PLN Token Prabayar (100302)
        $plnInquiry = $this->postJson('/ppob/inquiry', [
            'customer_id' => '01112582460',
            'product_code' => '100302',
        ])->assertOk()->json();

        $this->assertSame('success', $plnInquiry['status']);
        $this->assertSame('PELANGGAN PLN PRABAYAR', $plnInquiry['customer_name']);
        $this->assertSame('R1 / 1300 VA', $plnInquiry['tarif_daya']);

        // 4. Refresh-proof QRIS checkout recovery & dedicated invoice URL
        $this->product();

        $checkoutRes = $this->postJson('/ppob/checkout', $this->checkoutPayload([
            'customer_id' => '081280982299',
            'payment_method' => 'qris',
            'customer_name' => 'BUDI SANTOSO',
        ]))->assertOk()->json();

        $this->assertSame('success', $checkoutRes['status']);
        $this->assertNotEmpty($checkoutRes['order_id']);
        $this->assertSame('https://example.test/qr.png', $checkoutRes['url_qris']);
        $this->assertSame('BUDI SANTOSO', $checkoutRes['customer_name']);
        $this->assertGreaterThan(1000, (int) $checkoutRes['remaining_seconds']);

        $orderId = $checkoutRes['order_id'];

        // Simulate browser refresh calling /ppob/status/{orderId}
        $statusRes = $this->getJson('/ppob/status/'.$orderId)
            ->assertOk()
            ->json();

        $this->assertSame('pending', $statusRes['payment_status']);
        $this->assertSame('https://example.test/qr.png', $statusRes['url_qris']);
        $this->assertSame('BUDI SANTOSO', $statusRes['customer_name']);
        $this->assertGreaterThan(1000, (int) $statusRes['remaining_seconds']);
        $this->assertSame((int) $checkoutRes['total_nominal'], (int) $statusRes['total_nominal']);

        // Dedicated transaction invoice page shows active QRIS & auto-polling when unpaid
        $this->get($checkoutRes['invoice_url'])
            ->assertOk()
            ->assertSee('Scan QRIS untuk Menyelesaikan Pembayaran')
            ->assertSee('https://example.test/qr.png')
            ->assertSee('BUDI SANTOSO');

        // 5. Regular store product checkout (/checkout/process & /checkout/status/{orderId}) also persists QRIS for refresh recovery
        $storeProduct = \App\Models\Product::create([
            'name' => 'Netflix Premium 1 Bulan',
            'type' => 'local',
            'process_type' => 'manual',
            'price' => 35000,
        ]);

        $regularCheckout = $this->postJson('/checkout/process', [
            'product_id' => $storeProduct->id,
        ])->assertOk()->json();

        $this->assertSame('success', $regularCheckout['status']);
        $this->assertSame('https://example.test/qr.png', $regularCheckout['url_qris']);
        $this->assertGreaterThan(1000, (int) $regularCheckout['remaining_seconds']);

        $regularStatus = $this->getJson('/checkout/status/'.$regularCheckout['order_id'])
            ->assertOk()
            ->json();

        $this->assertSame('https://example.test/qr.png', $regularStatus['url_qris']);
        $this->assertSame((int) $regularCheckout['total_nominal'], (int) $regularStatus['total_nominal']);
        $this->assertGreaterThan(1000, (int) $regularStatus['remaining_seconds']);

        // 6. Verify KiosbankDevelopmentNopel::simulateInquiry for Game & E-Wallet in development mode
        $kiosbank = new \App\Services\KiosbankService;
        $devGameSim = \App\Services\KiosbankDevelopmentNopel::simulateInquiry('6707BE', '48122786', '261110123456', ['additional_id' => '2114'], $kiosbank, 2000, 54500);
        $this->assertSame('success', $devGameSim['status']);
        $this->assertStringContainsString('48122786', $devGameSim['customer_name']);
        $this->assertStringContainsString('2114', $devGameSim['customer_name']);

        $devEwalletSim = \App\Services\KiosbankDevelopmentNopel::simulateInquiry('752110', '081111111111', '261110123457', [], $kiosbank, 2000, 54500);
        $this->assertSame('success', $devEwalletSim['status']);
        $this->assertNotEmpty($devEwalletSim['customer_name']);
    }
}
