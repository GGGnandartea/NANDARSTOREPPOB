<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PopularCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function sale(Product $product, int $quantity, array $attributes = [], int $daysAgo = 1): void
    {
        $order = Order::create(array_replace([
            'invoice_number' => 'SALE-'.fake()->uuid(),
            'product_id' => $product->id, 'product_name' => $product->name, 'product_type' => 'local',
            'quantity' => $quantity, 'price' => 10000, 'total_amount' => $quantity * 10000,
            'payment_status' => 'paid', 'order_status' => 'completed',
        ], $attributes));
        $order->created_at = now()->subDays($daysAgo);
        $order->save();
    }

    public function test_brands_rank_by_completed_units_in_last_30_days_across_variants(): void
    {
        $this->travelTo(now()->startOfDay());
        config(['inertia.ssr.enabled' => false]);
        Http::preventStrayRequests();
        Cache::put('api_full_data', [], 60);
        $adobe = Product::create(['name' => 'Adobe Premium', 'price' => 10000]);
        $canva = Product::create(['name' => 'Canva Premium', 'price' => 10000]);
        $canvaTeam = Product::create(['name' => 'Canva Team', 'price' => 10000]);
        $netflix = Product::create(['name' => 'Netflix Premium', 'price' => 10000]);
        Product::create(['name' => 'Zoom Premium', 'price' => 10000]);
        $this->sale($canva, 3);
        $this->sale($canvaTeam, 4, [], 30);
        $this->sale($netflix, 6);
        $this->sale($adobe, 100, [], 31);
        $this->sale($adobe, 100, ['order_status' => 'refunded']);
        $this->sale($adobe, 100, ['payment_status' => 'pending']);
        $this->sale($adobe, 100, ['order_status' => 'processing']);
        $this->sale($adobe, 100, ['product_type' => 'ppob']);
        $this->sale($adobe, 100, [], -1);

        foreach (['/', '/?category=Canva'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('categories', 4)
                ->where('categories.0.name', 'Canva')
                ->where('categories.0.sales_last_30_days', 7)
                ->where('categories.1.name', 'Netflix')
                ->where('categories.1.sales_last_30_days', 6)
                ->where('categories.2.name', 'Adobe')
                ->where('categories.2.sales_last_30_days', 0)
                ->where('categories.3.name', 'Zoom')
            );
        }
        Http::assertNothingSent();
    }
}
