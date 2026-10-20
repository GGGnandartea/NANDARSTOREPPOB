<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\LocalAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\TransactionLog;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Http::preventStrayRequests();
    }

    private function order(Product $product, array $attributes = []): Order
    {
        return Order::create(array_replace([
            'invoice_number' => 'TEST-ORDER',
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_type' => $product->type,
            'price' => 10000,
            'total_amount' => 10000,
            'payment_status' => 'pending',
            'order_status' => 'pending',
        ], $attributes));
    }

    private function deliverCallback(Order $order): void
    {
        $body = json_encode(['orderId' => $order->invoice_number, 'status' => 'terbayar']);
        $this->call('POST', '/api/webhook/qris', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_CALLBACK_EVENT' => 'payment.success',
            'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $body, 'nandar-nandar-nandar-tea-tea'),
        ], $body)->assertOk();
    }

    public function test_replayed_callbacks_allocate_stock_once_and_preserve_credentials(): void
    {
        $product = Product::create(['name' => 'Local account', 'type' => 'local', 'price' => 10000]);
        for ($i = 0; $i < 3; $i++) {
            LocalAccount::create([
                'product_id' => $product->id, 'email' => "buyer{$i}@example.test",
                'password' => "secret{$i}", 'is_sold' => false,
            ]);
        }
        $order = $this->order($product, ['quantity' => 2, 'total_amount' => 20000]);
        $this->deliverCallback($order);
        $credentials = $order->fresh()->sn_or_account;
        $this->deliverCallback($order);
        OrderFulfillmentService::fulfillOrder($order);

        $this->assertSame('completed', $order->fresh()->order_status);
        $this->assertNotNull($order->fresh()->fulfillment_started_at);
        $this->assertSame($credentials, $order->fresh()->sn_or_account);
        $this->assertSame(2, LocalAccount::where('is_sold', true)->count());
        $this->assertSame(1, (int) $product->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_replayed_callbacks_and_direct_refund_requests_credit_balance_once(): void
    {
        $user = User::factory()->create(['balance' => 1000]);
        $product = Product::create(['name' => 'Sold out', 'type' => 'local', 'price' => 10000]);
        $order = $this->order($product, ['user_id' => $user->id]);
        $this->deliverCallback($order);
        $this->deliverCallback($order);
        OrderFulfillmentService::handleOutOfStockAutoRefund($order, $product);

        $this->assertSame('refunded', $order->fresh()->order_status);
        $this->assertSame(11000, (int) $user->fresh()->balance);
        $this->assertSame(1, TransactionLog::where('type', 'refund')->count());
    }

    public function test_in_flight_callback_and_admin_retry_cannot_place_another_provider_order(): void
    {
        $product = Product::create(['name' => 'API account', 'type' => 'api', 'price' => 10000, 'api_product_id' => 'PREFLIX-123']);
        $order = $this->order($product);
        $purchases = 0;
        Http::fake(function ($request) use ($order, &$purchases) {
            if ($request['koneksi'] === 'order') {
                $purchases++;
                $this->assertSame(1, $purchases, 'A concurrent callback placed a second purchase.');
                // Deliver the duplicate while the first provider request is in flight.
                $this->deliverCallback($order->fresh());
                OrderFulfillmentService::fulfillOrder($order->fresh());
            }
            return Http::response(['status' => 'success', 'data' => ['id_stok' => 'stock-123', 'sn' => 'owned-credentials']]);
        });

        $this->deliverCallback($order);
        $this->deliverCallback($order);
        $this->assertSame(1, $purchases);
        $this->assertSame('completed', $order->fresh()->order_status);
    }

    public function test_ambiguous_timeout_keeps_claim_and_polling_does_not_repurchase_or_take_another_accounts_credentials(): void
    {
        $product = Product::create(['name' => 'API account', 'type' => 'api', 'price' => 10000, 'api_product_id' => 'PREFLIX-123']);
        $order = $this->order($product);
        $purchases = 0;
        Http::fake(function ($request) use (&$purchases) {
            if ($request['koneksi'] === 'order') {
                $purchases++;
                throw new ConnectionException('Timed out after provider accepted the purchase');
            }
            return Http::response([['id_produk' => '123', 'id_stok' => 'someone-elses', 'sn' => 'FOREIGN-CREDENTIALS']]);
        });

        $this->deliverCallback($order);
        $this->deliverCallback($order);
        $result = OrderFulfillmentService::checkAndSyncApiOrder($order);
        $this->assertFalse($result['is_ready']);
        $this->assertSame(1, $purchases);
        $this->assertNotNull($order->fresh()->fulfillment_started_at);
        $this->assertStringNotContainsString('FOREIGN-CREDENTIALS', $order->fresh()->sn_or_account);
    }

    public function test_history_matches_exact_transaction_ids_and_requires_all_purchased_accounts(): void
    {
        $product = Product::create(['name' => 'API account', 'type' => 'api', 'price' => 10000, 'api_product_id' => 'PREFLIX-123']);
        $order = $this->order($product, [
            'payment_status' => 'paid', 'order_status' => 'processing', 'quantity' => 2,
            'api_trx_id' => 'mine-1,mine-2',
        ]);
        Http::fakeSequence()
            ->push([
                ['id_produk' => '123', 'id_stok' => 'other', 'sn' => 'FOREIGN'],
                ['id_stok' => 'mine-1', 'sn' => 'FIRST-CREDENTIALS'],
            ])
            ->push([
                ['id_stok' => 'mine-2', 'sn' => 'SECOND-CREDENTIALS'],
                ['id_produk' => '123', 'id_stok' => 'other', 'sn' => 'FOREIGN'],
                ['id_stok' => 'mine-1', 'sn' => 'FIRST-CREDENTIALS'],
            ]);

        $this->assertFalse(OrderFulfillmentService::checkAndSyncApiOrder($order)['is_ready']);
        $this->assertTrue(OrderFulfillmentService::checkAndSyncApiOrder($order)['is_ready']);
        $this->assertStringContainsString('FIRST-CREDENTIALS', $order->fresh()->sn_or_account);
        $this->assertStringContainsString('SECOND-CREDENTIALS', $order->fresh()->sn_or_account);
        $this->assertStringNotContainsString('FOREIGN', $order->fresh()->sn_or_account);
    }

    public function test_unpaid_order_and_unsigned_callback_cannot_fulfill(): void
    {
        $product = Product::create(['name' => 'API account', 'type' => 'api', 'price' => 10000, 'api_product_id' => 'PREFLIX-123']);
        $order = $this->order($product);
        $this->assertSame('error', OrderFulfillmentService::fulfillOrder($order)['status']);
        $this->postJson('/api/webhook/qris', ['orderId' => $order->invoice_number, 'status' => 'terbayar'])->assertForbidden();
        $this->assertNull($order->fresh()->fulfillment_started_at);
        Http::assertNothingSent();
    }
}
