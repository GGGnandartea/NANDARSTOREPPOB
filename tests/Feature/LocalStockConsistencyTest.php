<?php

namespace Tests\Feature;

use App\Models\LocalAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalStockConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false]);
        Cache::put('api_full_data', [], 60);
        Http::preventStrayRequests();
    }

    private function product(int $storedStock = 85): Product
    {
        return Product::create(['name' => 'Stock Test', 'price' => 10000, 'stock' => $storedStock,
            'type' => 'local', 'process_type' => 'auto', 'status' => 'available']);
    }

    private function account(Product $product, bool $sold = false): LocalAccount
    {
        return LocalAccount::create(['product_id' => $product->id, 'email' => 'test@example.test',
            'password' => 'TEST-ONLY', 'is_sold' => $sold]);
    }

    public function test_empty_stock_is_zero_in_catalog_detail_checkout_and_admin(): void
    {
        $product = $this->product();
        foreach (['/product/'.$product->id, '/checkout/'.$product->id] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->where('product.stock', 0));
        }
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.0.stock', 0));
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/admin/products/local/'.$product->id.'/accounts')->assertOk()->assertViewHas('availableStock', 0);
    }

    public function test_empty_stock_does_not_create_qris_or_charge_balance(): void
    {
        $product = $this->product();
        $this->postJson('/checkout/process', ['product_id' => $product->id])->assertStatus(400);
        $user = User::factory()->create(['role' => 'user', 'balance' => 50000]);
        $this->actingAs($user)->postJson('/profile/buy-balance', ['product_id' => $product->id])->assertStatus(400);
        $this->assertEquals(50000, $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_actual_unsold_rows_allow_qris_even_when_cached_stock_is_zero(): void
    {
        $product = $this->product(0);
        $this->account($product);
        $this->account($product);
        $this->account($product, true);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(2, Product::withAvailableAccountStock()->find($product->id)->stock);
        Http::fake(['127.0.0.1:4061/*' => Http::response(['data' => [
            'orderId' => 'STOCK-QRIS', 'total_nominal' => 20000, 'url_qris' => 'https://example.test/qr.png',
        ]])]);
        $this->postJson('/checkout/process', ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseHas('orders', ['invoice_number' => 'STOCK-QRIS', 'quantity' => 2, 'payment_status' => 'pending']);
    }

    public function test_balance_purchase_consumes_actual_stock_and_prevents_another_purchase(): void
    {
        $product = $this->product();
        $this->account($product);
        $user = User::factory()->create(['role' => 'user', 'balance' => 50000]);
        $this->actingAs($user)->postJson('/profile/buy-balance', ['product_id' => $product->id])
            ->assertOk()->assertJsonPath('status', 'success')->assertJsonPath('is_ready', true);
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertEquals(40000, $user->fresh()->balance);
        $this->postJson('/profile/buy-balance', ['product_id' => $product->id])->assertStatus(400);
        $this->assertEquals(40000, $user->fresh()->balance);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_manual_and_api_stock_are_not_replaced_with_account_counts(): void
    {
        $product = $this->product();
        $product->update(['process_type' => 'manual']);
        $this->assertSame(85, $product->fresh()->stock);
        $product->update(['type' => 'api', 'process_type' => 'auto']);
        $this->assertSame(85, $product->fresh()->stock);
    }
}
