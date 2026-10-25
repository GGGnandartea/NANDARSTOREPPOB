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
            '127.0.0.1:4061/*' => Http::response([
                'data' => ['orderId' => 'PPOB-PRIVATE', 'total_nominal' => 7123, 'url_qris' => 'https://example.test/qr.png'],
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
}
