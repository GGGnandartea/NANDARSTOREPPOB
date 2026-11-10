<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RealtimeApiPricingAndSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false]);
        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_api_product_price_reseller_price_and_description_auto_adjust_realtime_when_provider_price_increases(): void
    {
        // Kasus 1: Produk yang sebelumnya harga jual 34.000 namun api_price belum pernah tercatat (NULL),
        // lalu harga modal API naik menjadi 34.000 -> otomatis naik jadi 44.000 (margin default +10.000)
        $legacyApiProduct = Product::create([
            'name'           => 'Canva Pro 1 Tahun',
            'price'          => 34000,
            'reseller_price' => 30000,
            'api_price'      => null,
            'api_margin'     => null,
            'stock'          => 5,
            'type'           => 'api',
            'process_type'   => 'auto',
            'api_product_id' => 'PREMKU-101',
            'description'    => 'Deskripsi lama',
            'status'         => 'available',
        ]);

        // Kasus 2: Produk dengan modal awal 20.000 dan harga jual 32.000 (margin 12.000),
        // lalu modal API naik jadi 35.000 -> harga jual otomatis naik jadi 47.000 (tetap menjaga margin 12.000)
        $trackedApiProduct = Product::create([
            'name'                => 'Netflix Private 1 Bulan',
            'price'               => 32000,
            'reseller_price'      => 26000,
            'api_price'           => 20000,
            'api_margin'          => 12000,
            'api_reseller_margin' => 6000,
            'stock'               => 2,
            'type'                => 'api',
            'process_type'        => 'auto',
            'api_product_id'      => 'PREMKU-102',
            'description'         => 'Garansi lama',
            'status'              => 'available',
        ]);

        Setting::updateOrCreate(['key' => 'premku_api_key'], ['value' => 'test-key']);
        Setting::updateOrCreate(['key' => 'premku_api_secret'], ['value' => 'test-secret']);

        Http::fake([
            'https://premku.com/api/*' => Http::response([
                'ok'       => true,
                'products' => [
                    [
                        'id'          => 101,
                        'name'        => 'Canva Pro 1 Tahun',
                        'price'       => 34000,
                        'stock'       => 14,
                        'description' => 'Garansi Full 1 Tahun Resmi Terbaru',
                    ],
                    [
                        'id'          => 102,
                        'name'        => 'Netflix Private 1 Bulan',
                        'price'       => 35000,
                        'stock'       => 9,
                        'description' => 'Ultra HD 4K Private Resmi Terbaru',
                    ],
                ],
            ]),
        ]);

        $this->get('/product/' . $legacyApiProduct->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('product.price', 44000)
                ->where('product.stock', 14)
                ->where('product.description', 'Garansi Full 1 Tahun Resmi Terbaru')
            );

        $legacyFresh = $legacyApiProduct->fresh();
        $this->assertSame(34000, (int) $legacyFresh->api_price);
        $this->assertSame(10000, (int) $legacyFresh->api_margin);
        $this->assertSame(44000, (int) $legacyFresh->price);
        $this->assertSame(39000, (int) $legacyFresh->reseller_price);
        $this->assertSame('Garansi Full 1 Tahun Resmi Terbaru', $legacyFresh->description);

        $trackedFresh = $trackedApiProduct->fresh();
        $this->assertSame(35000, (int) $trackedFresh->api_price);
        $this->assertSame(12000, (int) $trackedFresh->api_margin);
        $this->assertSame(47000, (int) $trackedFresh->price);
        $this->assertSame(41000, (int) $trackedFresh->reseller_price);
        $this->assertSame(9, (int) $trackedFresh->stock);
        $this->assertSame('Ultra HD 4K Private Resmi Terbaru', $trackedFresh->description);
    }

    public function test_checkout_charges_realtime_updated_price_when_api_modal_rises(): void
    {
        $product = Product::create([
            'name'           => 'Spotify Individual 1 Bulan',
            'price'          => 34000,
            'api_price'      => 24000,
            'api_margin'     => 10000,
            'stock'          => 5,
            'type'           => 'api',
            'process_type'   => 'auto',
            'api_product_id' => 'PREMKU-555',
            'status'         => 'available',
        ]);

        // Simulasikan cache data live terbaru di mana harga modal API naik dari 24.000 menjadi 34.000
        Cache::put('api_full_data', [
            'PREMKU-555' => [
                'stock'       => 8,
                'price'       => 34000,
                'description' => 'Spotify Full Garansi Update Realtime',
            ],
        ], 60);

        Http::fake([
            '127.0.0.1:4061/*' => Http::response([
                'data' => [
                    'orderId'       => 'ORD-REALTIME-1',
                    'total_nominal' => 44000,
                    'url_qris'      => 'https://example.test/qris.png',
                ],
            ]),
        ]);

        $this->postJson('/checkout/process', [
            'product_id' => $product->id,
            'quantity'   => 1,
            'buyer_name' => 'Budi',
        ])->assertOk()->assertJsonPath('status', 'success');

        // Pastikan harga satuan yang tercatat di order adalah 44.000 (bukan 34.000)
        $this->assertDatabaseHas('orders', [
            'invoice_number' => 'ORD-REALTIME-1',
            'product_id'     => $product->id,
            'price'          => 44000,
        ]);

        $this->assertSame(44000, (int) $product->fresh()->price);
        $this->assertSame('Spotify Full Garansi Update Realtime', $product->fresh()->description);
    }

    public function test_seo_sitemap_robots_and_google_verification_endpoints_work_securely(): void
    {
        $product = Product::create([
            'name'        => 'CapCut Pro 1 Bulan',
            'price'       => 25000,
            'stock'       => 10,
            'type'        => 'local',
            'status'      => 'available',
            'description' => 'Deskripsi aman </script><script>alert(1)</script>',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        // Pastikan Firewall memblokir payload XSS <script> secara langsung dengan HTTP 403
        $this->actingAs($admin)->post('/admin/seo', [
            'store_name' => 'Test <script>alert(1)</script>',
        ])->assertForbidden();

        $this->actingAs($admin)->post('/admin/seo', [
            'store_name'                   => 'PremiumKita Official',
            'store_tagline'                => 'Pusat Akun Premium & PPOB Termurah 24 Jam',
            'seo_secondary_tagline'        => 'Order Otomatis Detik Itu Juga',
            'store_description'            => 'Beli akun aplikasi premium Netflix, Canva, Spotify dan pulsa PPOB otomatis 24 jam termurah dan bergaransi resmi di Indonesia.',
            'seo_meta_keywords'            => 'akun premium murah, netflix murah, canva pro murah, top up pulsa murah, ppob 24 jam, premiumkita',
            'seo_google_verification'      => '<meta name="google-site-verification" content="GSC_TOKEN_XYZ123" />',
            'seo_google_html_file'         => 'googleabcdef987654321',
            'seo_ga4_id'                   => 'G-TEST12345" onclick="evil()',
            'seo_sitemap_enabled'          => '1',
            'seo_sitemap_include_products' => '1',
            'seo_sitemap_include_images'   => '1',
            'seo_schema_enabled'           => '1',
        ])->assertRedirect();

        $cfg = SeoService::getConfig();
        $this->assertSame('GSC_TOKEN_XYZ123', $cfg['seo_google_verification']);
        $this->assertSame('googleabcdef987654321.html', $cfg['seo_google_html_file']);
        $this->assertSame('G-TEST12345onclickevil', $cfg['seo_ga4_id']);

        // Sitemap XML memuat halaman utama dan produk aktif (tanpa halaman form login/register)
        $sitemapResponse = $this->get('/sitemap.xml');
        $sitemapResponse->assertOk();
        $this->assertStringContainsString('<loc>', $sitemapResponse->getContent());
        $this->assertStringContainsString('/product/' . $product->id, $sitemapResponse->getContent());
        $this->assertStringNotContainsString('/login</loc>', $sitemapResponse->getContent());
        $this->assertStringNotContainsString('/register</loc>', $sitemapResponse->getContent());

        // Alias /terms melakukan redirect permanen 301 ke /syarat-ketentuan agar tidak duplikat di Google Search Console
        $this->get('/terms')->assertRedirect('/syarat-ketentuan')->assertStatus(301);

        // Googlebot tidak terhalang layar preloader saat merender halaman
        $botHomeHtml = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
            ->get('/')
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('id="ns-initial-preloader"', $botHomeHtml);

        // Robots.txt menunjuk ke Sitemap
        $robotsResponse = $this->get('/robots.txt');
        $robotsResponse->assertOk();
        $this->assertStringContainsString('Sitemap:', $robotsResponse->getContent());

        // File verifikasi HTML Google Search Console merespons valid
        $this->get('/googleabcdef987654321.html')
            ->assertOk()
            ->assertSee('google-site-verification: googleabcdef987654321.html', false);

        // Halaman detail produk bebas dari breakout </script> pada JSON-LD
        $detailHtml = $this->get('/product/' . $product->id)->assertOk()->getContent();
        $this->assertStringNotContainsString('</script><script>alert(1)</script>', $detailHtml);
        $this->assertStringContainsString('GSC_TOKEN_XYZ123', $detailHtml);

        if (file_exists(public_path('googleabcdef987654321.html'))) {
            @unlink(public_path('googleabcdef987654321.html'));
        }
    }
}
