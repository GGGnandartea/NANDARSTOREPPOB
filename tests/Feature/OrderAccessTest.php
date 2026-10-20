<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Http::preventStrayRequests();
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_replace([
            'invoice_number' => 'PRIVATE-ORDER', 'product_name' => 'Private account',
            'product_type' => 'local', 'price' => 10000, 'total_amount' => 10000,
            'payment_status' => 'paid', 'order_status' => 'completed',
            'sn_or_account' => 'PRIVATE-CREDENTIALS',
        ], $attributes));
    }

    private function assertDenied(Order $order, string $query = ''): void
    {
        foreach (['/order/invoice/', '/invoice/', '/checkout/status/', '/ppob/status/'] as $prefix) {
            $this->getJson($prefix.$order->invoice_number.$query)->assertNotFound()->assertDontSee('PRIVATE-CREDENTIALS');
        }
        $this->postJson('/order/'.$order->invoice_number.'/resend-wa'.$query)->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_registered_order_denies_guests_and_other_buyers_even_with_a_token(): void
    {
        $owner = User::factory()->create();
        $order = $this->order(['user_id' => $owner->id]);
        $this->assertDenied($order);
        $this->actingAs(User::factory()->create());
        $this->assertDenied($order, '?token=pretend-secret');
    }

    public function test_owner_and_admin_can_view_but_numeric_primary_key_is_not_an_invoice_alias(): void
    {
        $owner = User::factory()->create();
        $order = $this->order(['user_id' => $owner->id]);
        $this->actingAs($owner);
        $this->get($order->invoiceUrl())->assertOk()->assertSee('PRIVATE-CREDENTIALS')
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->getJson('/checkout/status/'.$order->invoice_number)->assertOk()->assertJsonPath('sn_or_account', 'PRIVATE-CREDENTIALS');
        $this->get('/order/invoice/'.$order->id)->assertNotFound();
        $this->get('/invoice/'.$order->id)->assertNotFound();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get($order->invoiceUrl())->assertOk();
    }

    public function test_guest_requires_valid_secret_and_tokens_cannot_access_another_order(): void
    {
        $order = $this->order();
        $other = $this->order(['invoice_number' => 'OTHER-GUEST']);
        $this->assertDenied($order);
        $this->assertDenied($order, '?token='.$other->guest_access_token);
        $this->assertDenied($order, '?token[]=invalid');

        $this->get($order->invoiceUrl())->assertOk()->assertSee('PRIVATE-CREDENTIALS')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/checkout/status/'.$order->invoice_number)->assertOk();
        $this->getJson('/ppob/status/'.$other->invoice_number)->assertNotFound();
        $this->get('/invoice/'.$order->invoice_number)->assertRedirect($order->invoiceUrl());
        $this->assertArrayNotHasKey('guest_access_token', $order->toArray());
        $encrypted = DB::table('orders')->where('id', $order->id)->value('guest_access_token');
        $this->assertStringNotContainsString($order->guest_access_token, $encrypted);
        $this->assertSame(64, strlen($order->guest_access_token));
    }

    public function test_guest_checkout_returns_private_link_and_grants_only_its_own_session_access(): void
    {
        $product = Product::create(['name' => 'Manual service', 'type' => 'local', 'process_type' => 'manual', 'price' => 10000]);
        Http::fake(['127.0.0.1:4061/*' => Http::response([
            'data' => ['orderId' => 'NEW-GUEST', 'total_nominal' => 10001, 'url_qris' => 'https://example.test/qr.png'],
        ])]);
        $response = $this->postJson('/checkout/process', ['product_id' => $product->id])->assertOk()->assertJsonPath('status', 'success');
        $order = Order::where('invoice_number', 'NEW-GUEST')->firstOrFail();
        $this->assertSame($order->invoiceUrl(), $response->json('invoice_url'));
        $this->getJson('/checkout/status/NEW-GUEST')->assertOk();
        $this->get('/order/invoice/NEW-GUEST')->assertRedirect($order->invoiceUrl());
        $other = $this->order();
        $this->getJson('/checkout/status/'.$other->invoice_number)->assertNotFound();
    }
}
