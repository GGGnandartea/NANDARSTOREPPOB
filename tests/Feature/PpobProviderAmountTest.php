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
            '*/Services/SinglePayment' => Http::response(['rc' => '00', 'data' => ['status' => 'BERHASIL', 'sn' => 'test-token']]),
            '*/Services/Payment' => Http::response(['rc' => '00', 'data' => ['status' => 'BERHASIL', 'sn' => 'bill-token']]),
        ]);
    }

    private function product(): PpobProduct
    {
        return PpobProduct::create([
            'code' => 'TEST-PULSA', 'name' => 'Pulsa', 'category' => 'pulsa', 'operator' => 'Test',
            'base_price' => 5650, 'margin' => 2350, 'price' => 8000, 'reseller_price' => 7500, 'status' => 'active',
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
}
