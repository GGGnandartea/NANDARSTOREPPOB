<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FavoritesAndTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false]);
        Http::preventStrayRequests();
    }

    public function test_favorites_require_login_and_are_idempotent_and_private(): void
    {
        $product = Product::create(['name' => 'Saved product', 'price' => 10000, 'status' => 'available']);
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $this->putJson('/favorites/'.$product->id)->assertUnauthorized();
        $this->actingAs($owner)->from('/profile')->put('/favorites/'.$product->id)->assertRedirect('/profile');
        $this->put('/favorites/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('product_favorites', 1);
        $this->actingAs($other)->delete('/favorites/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('product_favorites', 1);
        $this->get('/profile?tab=favorites')->assertInertia(fn (Assert $page) => $page
            ->component('Profile')->has('favoriteProducts', 0)->has('favoriteProductIds', 0));
        $this->actingAs($owner)->get('/profile?tab=favorites')->assertInertia(fn (Assert $page) => $page
            ->has('favoriteProducts', 1)->where('favoriteProducts.0.id', $product->id)
            ->where('favoriteProductIds.0', $product->id));
        $this->delete('/favorites/'.$product->id)->assertRedirect();
        $this->assertDatabaseCount('product_favorites', 0);
    }

    public function test_missing_products_cannot_be_saved_and_deletion_cleans_favorites(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->put('/favorites/999999')->assertNotFound();
        $product = Product::create(['name' => 'Product', 'price' => 10000, 'status' => 'available']);
        $this->put('/favorites/'.$product->id)->assertRedirect();
        $product->delete();
        $this->assertDatabaseCount('product_favorites', 0);
    }

    public function test_tracking_exposes_actual_update_time_only_to_owner(): void
    {
        $owner = User::factory()->create();
        $order = Order::create([
            'invoice_number' => 'TRACK-PRIVATE', 'user_id' => $owner->id,
            'product_name' => 'Test product', 'product_type' => 'local', 'price' => 10000, 'total_amount' => 10000,
            'payment_status' => 'paid', 'order_status' => 'processing',
        ]);
        $this->actingAs($owner)->getJson('/checkout/status/'.$order->invoice_number)->assertOk()
            ->assertJsonPath('order_status', 'processing')
            ->assertJsonPath('payment_status', 'paid')
            ->assertJsonPath('updated_at', $order->updated_at->toIso8601String())
            ->assertJsonPath('placed_at', $order->created_at->toIso8601String());
        $this->actingAs(User::factory()->create())->getJson('/checkout/status/'.$order->invoice_number)->assertNotFound();
    }
}
