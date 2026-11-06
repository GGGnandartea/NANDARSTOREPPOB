<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Daftar seluruh key pengaturan SEO beserta nilai defaultnya.
     */
    public static function defaults(): array
    {
        return [
            'store_name'                 => 'PremiumKita',
            'store_tagline'              => 'Platform Digital, Akun Premium & Layanan PPOB Otomatis 24 Jam',
            'seo_secondary_tagline'      => 'Proses Instan Detik Itu Juga • Bergaransi Resmi • Harga Reseller Termurah',
            'seo_title_separator'        => '-',
            'seo_home_title'             => '',
            'store_description'          => 'Pusat belanja akun aplikasi premium murah bergaransi (Netflix, Spotify, Canva Pro, YouTube Premium, CapCut Pro, ChatGPT Plus), isi pulsa murah semua operator, paket data, token listrik PLN, top up game & e-wallet otomatis 24 jam.',
            'seo_meta_keywords'          => 'akun premium murah, jual netflix murah, spotify premium, canva pro murah, capcut pro, youtube premium, chatgpt plus murah, top up game murah, pulsa murah otomatis, token listrik pln, ppob termurah, reseller aplikasi premium, premiumkita',
            'seo_ppob_title'             => 'Pusat Layanan PPOB, Pulsa, Token PLN, Top Up Game & E-Wallet Otomatis',
            'seo_ppob_description'       => 'Beli pulsa murah semua operator, paket data internet, token listrik PLN, saldo DANA/OVO/GoPay/ShopeePay, voucher game MLBB/Free Fire, dan bayar tagihan PDAM/BPJS/PLN Pascabayar otomatis 24 jam.',
            'seo_product_title_template' => 'Jual {product_name} Murah Bergaransi ({price}) {separator} {store_name}',
            'seo_product_desc_template'  => 'Beli {product_name} ({category}) harga promo {price}. Proses pengiriman akun otomatis 24 jam, legal, aman, dan bergaransi penuh hanya di {store_name} {separator} {tagline}.',
            'seo_faq_title'              => 'Pusat Bantuan, Cara Order & Klaim Garansi (FAQ)',
            'seo_faq_description'        => 'Panduan lengkap cara pembelian akun aplikasi premium, transaksi pulsa & PPOB otomatis, pembayaran QRIS instan, serta klaim garansi resmi di {store_name}.',
            'seo_terms_title'            => 'Syarat & Ketentuan Layanan & Kebijakan Garansi',
            'seo_terms_description'      => 'Syarat ketentuan layanan, kebijakan privasi pengguna, aturan garansi produk digital, dan ketentuan refund otomatis di {store_name}.',
            'seo_google_verification'    => '',
            'seo_google_html_file'       => '',
            'seo_bing_verification'      => '',
            'seo_yandex_verification'    => '',
            'seo_ga4_id'                 => '',
            'seo_gtm_id'                 => '',
            'seo_fb_pixel_id'            => '',
            'seo_robots_index'           => 'index, follow',
            'seo_canonical_domain'       => '',
            'seo_sitemap_enabled'        => '1',
            'seo_sitemap_include_products' => '1',
            'seo_sitemap_include_images' => '1',
            'seo_robots_custom'          => '',
            'seo_schema_enabled'         => '1',
            'seo_schema_type'            => 'OnlineStore',
            'seo_schema_price_range'     => 'Rp 1.000 - Rp 500.000',
            'seo_schema_rating_value'    => '4.9',
            'seo_schema_rating_count'    => '1480',
            'seo_social_instagram'       => '',
            'seo_social_tiktok'          => '',
            'seo_social_telegram'        => '',
            'seo_social_youtube'         => '',
            'seo_og_image'               => '',
            'seo_theme_color'            => '#f97316',
        ];
    }

    /**
     * Ekstrak nilai token dari input tag meta HTML penuh (jika admin menempelkan <meta name="..." content="...">).
     */
    public static function extractVerificationToken(?string $raw): string
    {
        $val = trim((string) $raw);
        if ($val === '') {
            return '';
        }

        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $val, $matches)) {
            return trim($matches[1]);
        }

        // Jika menempelkan google-site-verification=XXXX
        if (str_contains($val, 'google-site-verification=')) {
            $parts = explode('google-site-verification=', $val, 2);
            return trim($parts[1] ?? '');
        }

        return strip_tags($val);
    }

    /**
     * Normalisasi nama file verifikasi HTML Google (contoh: google1234567890abcdef.html).
     */
    public static function normalizeGoogleHtmlFilename(?string $raw): string
    {
        $val = trim((string) $raw);
        if ($val === '') {
            return '';
        }

        $val = basename(str_replace('\\', '/', $val));
        if (str_ends_with(strtolower($val), '.html')) {
            $val = substr($val, 0, -5);
        }
        if (str_starts_with(strtolower($val), 'google')) {
            $val = substr($val, 6);
        }
        $cleanToken = preg_replace('/[^a-zA-Z0-9_-]/', '', $val);
        if ($cleanToken === '') {
            return '';
        }

        return 'google' . $cleanToken . '.html';
    }

    /**
     * Ambil seluruh konfigurasi SEO yang telah digabung dengan nilai default.
     */
    public static function getConfig(): array
    {
        $defaults = self::defaults();

        $saved = Cache::remember('seo_settings_config_v1', 30, function () use ($defaults) {
            try {
                return Setting::whereIn('key', array_merge(array_keys($defaults), [
                    'store_logo',
                    'store_favicon',
                    'admin_whatsapp',
                    'whatsapp_group_link',
                ]))->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });

        $config = [];
        foreach ($defaults as $key => $defaultVal) {
            $val = $saved[$key] ?? null;
            $config[$key] = ($val !== null && trim((string) $val) !== '') ? trim((string) $val) : $defaultVal;
        }

        $config['store_logo']          = $saved['store_logo'] ?? null;
        $config['store_favicon']       = $saved['store_favicon'] ?? ($config['store_logo'] ?: null);
        $config['admin_whatsapp']      = $saved['admin_whatsapp'] ?? '082317458496';
        $config['whatsapp_group_link'] = $saved['whatsapp_group_link'] ?? '';

        // Pastikan kode verifikasi bersih
        $config['seo_google_verification'] = self::extractVerificationToken($config['seo_google_verification']);
        $config['seo_bing_verification']   = self::extractVerificationToken($config['seo_bing_verification']);
        $config['seo_yandex_verification'] = self::extractVerificationToken($config['seo_yandex_verification']);
        $config['seo_google_html_file']    = self::normalizeGoogleHtmlFilename($config['seo_google_html_file']);

        return $config;
    }

    /**
     * Hapus cache SEO setelah penyimpanan di Admin Panel.
     */
    public static function clearCache(): void
    {
        Cache::forget('seo_settings_config_v1');
        Cache::forget('inertia_shared_settings');
        Cache::forget('seo_sitemap_xml_v1');
    }

    /**
     * Hitung judul utama halaman depan (Home Title).
     */
    public static function resolveHomeTitle(?array $cfg = null): string
    {
        $cfg = $cfg ?: self::getConfig();
        if (!empty($cfg['seo_home_title'])) {
            return self::replaceVariables($cfg['seo_home_title'], $cfg);
        }

        return trim($cfg['store_name'] . ' ' . $cfg['seo_title_separator'] . ' ' . $cfg['store_tagline']);
    }

    /**
     * Ganti placeholder variabel SEO seperti {store_name}, {tagline}, {separator}, {product_name}, {price}, {category}.
     */
    public static function replaceVariables(string $template, array $cfg, array $extra = []): string
    {
        $replacements = array_merge([
            '{store_name}' => $cfg['store_name'] ?? 'PremiumKita',
            '{tagline}'    => $cfg['store_tagline'] ?? '',
            '{separator}'  => $cfg['seo_title_separator'] ?? '-',
        ], $extra);

        return trim(preg_replace('/\s+/', ' ', strtr($template, $replacements)));
    }

    /**
     * Resolusi metadata lengkap, Open Graph, dan JSON-LD Structured Data untuk request saat ini.
     */
    public static function resolveForRequest(Request $request): array
    {
        $cfg = self::getConfig();
        $storeName = $cfg['store_name'];
        $tagline   = $cfg['store_tagline'];
        $sep       = $cfg['seo_title_separator'];

        $baseUrl = !empty($cfg['seo_canonical_domain'])
            ? rtrim($cfg['seo_canonical_domain'], '/')
            : rtrim($request->getSchemeAndHttpHost(), '/');

        $path = ltrim($request->path(), '/');
        $canonicalUrl = $path === '' ? $baseUrl : ($baseUrl . '/' . $path);

        $title = self::resolveHomeTitle($cfg);
        $description = self::replaceVariables($cfg['store_description'], $cfg);
        $keywords = $cfg['seo_meta_keywords'];
        $ogType = 'website';
        $robots = $cfg['seo_robots_index'] ?: 'index, follow';

        // Resolusi OG Image
        $ogImage = !empty($cfg['seo_og_image'])
            ? $cfg['seo_og_image']
            : (!empty($cfg['store_logo']) ? $cfg['store_logo'] : asset('images/og-preview.png'));
        if ($ogImage && !str_starts_with($ogImage, 'http')) {
            $ogImage = $baseUrl . '/' . ltrim($ogImage, '/');
        }

        $productModel = null;
        $breadcrumbs = [
            ['name' => 'Beranda', 'url' => $baseUrl],
        ];

        // 1. Halaman Detail Produk (/product/{id})
        if ($request->is('product/*')) {
            $productId = $request->segment(2);
            if (is_numeric($productId)) {
                try {
                    $productModel = Product::find($productId);
                } catch (\Throwable $e) {
                    $productModel = null;
                }
            }
            if ($productModel) {
                $formattedPrice = 'Rp ' . number_format((int) $productModel->price, 0, ',', '.');
                $categoryName = $productModel->category ?: 'Aplikasi Premium';
                $vars = [
                    '{product_name}' => $productModel->name,
                    '{price}'        => $formattedPrice,
                    '{category}'     => $categoryName,
                ];
                $title = self::replaceVariables($cfg['seo_product_title_template'], $cfg, $vars);
                $description = self::replaceVariables($cfg['seo_product_desc_template'], $cfg, $vars);
                $keywords = $productModel->name . ', jual ' . strtolower($productModel->name) . ' murah, beli ' . strtolower($productModel->name) . ', ' . strtolower($categoryName) . ', ' . $keywords;
                $ogType = 'product';
                if (!empty($productModel->image) && !str_starts_with($productModel->image, 'data:')) {
                    $ogImage = str_starts_with($productModel->image, 'http')
                        ? $productModel->image
                        : ($baseUrl . '/' . ltrim($productModel->image, '/'));
                }
                $breadcrumbs[] = ['name' => $categoryName, 'url' => $baseUrl . '/?category=' . urlencode($categoryName)];
                $breadcrumbs[] = ['name' => $productModel->name, 'url' => $canonicalUrl];
            }
        }
        // 2. Halaman Layanan PPOB (/ppob)
        elseif ($request->is('ppob*')) {
            $title = self::replaceVariables($cfg['seo_ppob_title'], $cfg) . ' ' . $sep . ' ' . $storeName;
            $description = self::replaceVariables($cfg['seo_ppob_description'], $cfg);
            $breadcrumbs[] = ['name' => 'Layanan PPOB & Pulsa', 'url' => $canonicalUrl];
        }
        // 3. Halaman FAQ (/faq)
        elseif ($request->is('faq*')) {
            $title = self::replaceVariables($cfg['seo_faq_title'], $cfg) . ' ' . $sep . ' ' . $storeName;
            $description = self::replaceVariables($cfg['seo_faq_description'], $cfg);
            $breadcrumbs[] = ['name' => 'Pusat Bantuan (FAQ)', 'url' => $canonicalUrl];
        }
        // 4. Halaman Syarat & Ketentuan (/syarat-ketentuan, /terms)
        elseif ($request->is('terms*') || $request->is('syarat-ketentuan*')) {
            $title = self::replaceVariables($cfg['seo_terms_title'], $cfg) . ' ' . $sep . ' ' . $storeName;
            $description = self::replaceVariables($cfg['seo_terms_description'], $cfg);
            $breadcrumbs[] = ['name' => 'Syarat & Ketentuan', 'url' => $canonicalUrl];
        }
        // 5. Halaman Private / Transaksional (Jangan di-index oleh Google)
        elseif ($request->is('admin*') || $request->is('profile*') || $request->is('checkout*') || $request->is('order/invoice*') || $request->is('invoice*')) {
            $robots = 'noindex, nofollow';
        }

        if (str_starts_with($robots, 'index')) {
            $robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        }

        $schemas = [];
        if (($cfg['seo_schema_enabled'] ?? '1') === '1') {
            $schemas = self::buildJsonLdSchemas($cfg, $baseUrl, $canonicalUrl, $ogImage, $productModel, $breadcrumbs, $request);
        }

        return [
            'config'        => $cfg,
            'title'         => $title,
            'description'   => Str::limit($description, 165, ''),
            'og_description'=> Str::limit($description, 220, ''),
            'keywords'      => $keywords,
            'canonical_url' => $canonicalUrl,
            'base_url'      => $baseUrl,
            'og_type'       => $ogType,
            'og_image'      => $ogImage,
            'robots'        => $robots,
            'product'       => $productModel,
            'schemas'       => $schemas,
        ];
    }

    /**
     * Bangun grafik JSON-LD Schema.org lengkap untuk Google Rich Snippets & Sitelinks.
     */
    public static function buildJsonLdSchemas(
        array $cfg,
        string $baseUrl,
        string $canonicalUrl,
        ?string $ogImage,
        ?Product $productModel,
        array $breadcrumbs,
        Request $request
    ): array {
        $storeName = $cfg['store_name'];
        $tagline   = $cfg['store_tagline'];
        $desc      = self::replaceVariables($cfg['store_description'], $cfg);
        $logoUrl   = !empty($cfg['store_logo'])
            ? (str_starts_with($cfg['store_logo'], 'http') ? $cfg['store_logo'] : ($baseUrl . '/' . ltrim($cfg['store_logo'], '/')))
            : $ogImage;

        $sameAs = array_values(array_filter([
            $cfg['seo_social_instagram'] ?? '',
            $cfg['seo_social_tiktok'] ?? '',
            $cfg['seo_social_telegram'] ?? '',
            $cfg['seo_social_youtube'] ?? '',
            $cfg['whatsapp_group_link'] ?? '',
        ]));

        $waClean = preg_replace('/[^0-9]/', '', (string) ($cfg['admin_whatsapp'] ?? '082317458496'));
        if (str_starts_with($waClean, '0')) {
            $waClean = '62' . substr($waClean, 1);
        }

        $ratingValue = is_numeric($cfg['seo_schema_rating_value'] ?? null)
            ? (float) $cfg['seo_schema_rating_value']
            : 4.9;
        $ratingCount = is_numeric($cfg['seo_schema_rating_count'] ?? null)
            ? (int) $cfg['seo_schema_rating_count']
            : 1480;

        $schemas = [];

        // 1. Organization / OnlineStore Schema
        $orgSchema = [
            '@context'     => 'https://schema.org',
            '@type'        => $cfg['seo_schema_type'] ?: 'OnlineStore',
            '@id'          => $baseUrl . '/#organization',
            'name'         => $storeName,
            'alternateName'=> $storeName . ' - ' . $tagline,
            'slogan'       => $tagline,
            'description'  => $desc,
            'url'          => $baseUrl,
            'logo'         => $logoUrl,
            'image'        => $ogImage ?: $logoUrl,
            'priceRange'   => $cfg['seo_schema_price_range'] ?: 'Rp 1.000 - Rp 500.000',
            'currenciesAccepted' => 'IDR',
            'paymentAccepted'    => 'QRIS, Saldo Akun, E-Wallet (DANA, OVO, GoPay, ShopeePay), Transfer Bank',
            'openingHoursSpecification' => [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                'opens'     => '00:00',
                'closes'    => '23:59',
            ],
            'contactPoint' => [
                '@type'             => 'ContactPoint',
                'telephone'         => '+' . $waClean,
                'contactType'       => 'customer service',
                'areaServed'        => 'ID',
                'availableLanguage' => ['Indonesian'],
            ],
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $ratingValue,
                'bestRating'  => '5',
                'worstRating' => '1',
                'ratingCount' => (string) max(1, $ratingCount),
            ],
        ];
        if (!empty($sameAs)) {
            $orgSchema['sameAs'] = $sameAs;
        }
        $schemas[] = $orgSchema;

        // 2. WebSite + Sitelinks Searchbox Schema
        $schemas[] = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => $baseUrl . '/#website',
            'name'            => $storeName,
            'alternateName'   => $tagline,
            'url'             => $baseUrl,
            'inLanguage'      => 'id-ID',
            'publisher'       => ['@id' => $baseUrl . '/#organization'],
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $baseUrl . '/?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        // 3. SiteNavigationElement Schema (Membantu Google memunculkan Sitelinks di bawah hasil pencarian utama)
        $schemas[] = [
            '@context' => 'https://schema.org',
            '@type'    => 'ItemList',
            'name'     => 'Navigasi Utama ' . $storeName,
            'itemListElement' => [
                [
                    '@type'    => 'SiteNavigationElement',
                    'position' => 1,
                    'name'     => 'Katalog Akun Premium',
                    'description' => 'Beli akun aplikasi premium Netflix, Spotify, Canva Pro, YouTube, CapCut otomatis & bergaransi.',
                    'url'      => $baseUrl . '/',
                ],
                [
                    '@type'    => 'SiteNavigationElement',
                    'position' => 2,
                    'name'     => 'Layanan PPOB, Pulsa & Top Up Game',
                    'description' => 'Isi pulsa semua operator, paket data, token listrik PLN, voucher game & e-wallet 24 jam.',
                    'url'      => $baseUrl . '/ppob',
                ],
                [
                    '@type'    => 'SiteNavigationElement',
                    'position' => 3,
                    'name'     => 'Daftar Reseller VIP',
                    'description' => 'Gabung Reseller VIP dan dapatkan harga modal termurah untuk semua produk digital.',
                    'url'      => $baseUrl . '/register',
                ],
                [
                    '@type'    => 'SiteNavigationElement',
                    'position' => 4,
                    'name'     => 'Pusat Bantuan & FAQ',
                    'description' => 'Panduan cara order, pembayaran QRIS otomatis, dan klaim garansi layanan.',
                    'url'      => $baseUrl . '/faq',
                ],
                [
                    '@type'    => 'SiteNavigationElement',
                    'position' => 5,
                    'name'     => 'Syarat & Ketentuan Garansi',
                    'description' => 'Ketentuan penggunaan layanan, garansi akun, dan pengembalian dana otomatis.',
                    'url'      => $baseUrl . '/syarat-ketentuan',
                ],
            ],
        ];

        // 4. BreadcrumbList Schema
        if (count($breadcrumbs) > 1) {
            $breadcrumbItems = [];
            foreach ($breadcrumbs as $idx => $crumb) {
                $breadcrumbItems[] = [
                    '@type'    => 'ListItem',
                    'position' => $idx + 1,
                    'name'     => $crumb['name'],
                    'item'     => $crumb['url'],
                ];
            }
            $schemas[] = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $breadcrumbItems,
            ];
        }

        // 5. Product Schema (Halaman Detail Produk)
        if ($productModel) {
            $inStock = ((int) $productModel->stock > 0) || ($productModel->type === 'local' && $productModel->process_type === 'manual');
            $schemas[] = [
                '@context'    => 'https://schema.org',
                '@type'       => 'Product',
                'name'        => $productModel->name,
                'description' => Str::limit(strip_tags((string) ($productModel->description ?: $desc)), 300),
                'image'       => [$ogImage ?: $logoUrl],
                'sku'         => 'PK-PROD-' . $productModel->id,
                'mpn'         => 'PK-' . $productModel->id,
                'category'    => $productModel->category ?: 'Aplikasi Premium',
                'brand'       => [
                    '@type' => 'Brand',
                    'name'  => $storeName,
                ],
                'offers'      => [
                    '@type'           => 'Offer',
                    'url'             => $canonicalUrl,
                    'priceCurrency'   => 'IDR',
                    'price'           => (string) ((int) $productModel->price),
                    'priceValidUntil' => now()->addMonths(6)->toDateString(),
                    'availability'    => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition'   => 'https://schema.org/NewCondition',
                    'seller'          => [
                        '@type' => 'Organization',
                        'name'  => $storeName,
                    ],
                ],
                'aggregateRating' => [
                    '@type'       => 'AggregateRating',
                    'ratingValue' => (string) $ratingValue,
                    'bestRating'  => '5',
                    'worstRating' => '1',
                    'ratingCount' => (string) max(12, (int) round($ratingCount / 8)),
                ],
            ];
        }

        // 6. FAQPage Schema (Di Beranda dan Halaman /faq agar muncul Rich FAQ di Google)
        if ($request->is('/') || $request->is('faq*')) {
            $schemas[] = [
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => [
                    [
                        '@type' => 'Question',
                        'name'  => 'Apakah pembelian akun premium dan PPOB di ' . $storeName . ' diproses otomatis?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Ya, seluruh pesanan akun aplikasi premium, pulsa, paket data, token PLN, dan top up di ' . $storeName . ' diproses secara otomatis 24 jam nonstop segera setelah pembayaran QRIS atau Saldo Akun terkonfirmasi.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name'  => 'Metode pembayaran apa saja yang tersedia di ' . $storeName . '?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Kami mendukung pembayaran instan via QRIS Otomatis (bisa di-scan dari DANA, OVO, GoPay, ShopeePay, LinkAja, BCA Mobile, Livin Mandiri, BRImo, SeaBank, dll) serta pembayaran instan menggunakan Saldo Akun.',
                        ],
                    ],
                    [
                        '@type' => 'Question',
                        'name'  => 'Apakah produk akun premium di ' . $storeName . ' bergaransi?',
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => 'Ya, semua produk akun premium di ' . $storeName . ' dilengkapi garansi resmi sesuai durasi paket. Jika transaksi PPOB atau pesanan gagal dari server, dana di-refund 100%.',
                        ],
                    ],
                ],
            ];
        }

        return $schemas;
    }

    /**
     * Generate isi XML Sitemap lengkap untuk Google Search Console (/sitemap.xml).
     */
    public static function generateSitemapXml(?string $requestHost = null): string
    {
        $cfg = self::getConfig();
        $baseUrl = !empty($cfg['seo_canonical_domain'])
            ? rtrim($cfg['seo_canonical_domain'], '/')
            : rtrim($requestHost ?: config('app.url', 'https://premiumkita.com'), '/');

        $includeProducts = ($cfg['seo_sitemap_include_products'] ?? '1') === '1';
        $includeImages   = ($cfg['seo_sitemap_include_images'] ?? '1') === '1';
        $today = now()->toAtomString();

        $urls = [
            [
                'loc'        => $baseUrl . '/',
                'lastmod'    => $today,
                'changefreq' => 'daily',
                'priority'   => '1.0',
                'image'      => null,
            ],
            [
                'loc'        => $baseUrl . '/ppob',
                'lastmod'    => $today,
                'changefreq' => 'daily',
                'priority'   => '0.9',
                'image'      => null,
            ],
            [
                'loc'        => $baseUrl . '/faq',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.7',
                'image'      => null,
            ],
            [
                'loc'        => $baseUrl . '/syarat-ketentuan',
                'lastmod'    => $today,
                'changefreq' => 'monthly',
                'priority'   => '0.6',
                'image'      => null,
            ],
            [
                'loc'        => $baseUrl . '/register',
                'lastmod'    => $today,
                'changefreq' => 'weekly',
                'priority'   => '0.7',
                'image'      => null,
            ],
            [
                'loc'        => $baseUrl . '/login',
                'lastmod'    => $today,
                'changefreq' => 'monthly',
                'priority'   => '0.5',
                'image'      => null,
            ],
        ];

        if ($includeProducts) {
            try {
                $products = Product::where('status', 'available')
                    ->where(function ($q) {
                        $q->whereNull('type')->orWhere('type', '!=', 'ppob');
                    })
                    ->orderBy('id', 'desc')
                    ->get(['id', 'name', 'category', 'image', 'updated_at']);

                foreach ($products as $prod) {
                    $imgUrl = null;
                    if ($includeImages && !empty($prod->image) && !str_starts_with($prod->image, 'data:')) {
                        $imgUrl = str_starts_with($prod->image, 'http')
                            ? $prod->image
                            : ($baseUrl . '/' . ltrim($prod->image, '/'));
                    }

                    $urls[] = [
                        'loc'        => $baseUrl . '/product/' . $prod->id,
                        'lastmod'    => $prod->updated_at ? $prod->updated_at->toAtomString() : $today,
                        'changefreq' => 'daily',
                        'priority'   => '0.8',
                        'image'      => $imgUrl ? [
                            'loc'   => $imgUrl,
                            'title' => $prod->name . ' - ' . $cfg['store_name'],
                        ] : null,
                    ];
                }
            } catch (\Throwable $e) {
                // Abaikan jika tabel produk belum siap
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= '    <lastmod>' . htmlspecialchars($u['lastmod'], ENT_XML1, 'UTF-8') . "</lastmod>\n";
            $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            if (!empty($u['image']['loc'])) {
                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . htmlspecialchars($u['image']['loc'], ENT_XML1, 'UTF-8') . "</image:loc>\n";
                $xml .= '      <image:title>' . htmlspecialchars($u['image']['title'], ENT_XML1, 'UTF-8') . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate isi robots.txt yang ramah Googlebot & menunjuk ke Sitemap XML.
     */
    public static function generateRobotsTxt(?string $requestHost = null): string
    {
        $cfg = self::getConfig();
        $baseUrl = !empty($cfg['seo_canonical_domain'])
            ? rtrim($cfg['seo_canonical_domain'], '/')
            : rtrim($requestHost ?: config('app.url', 'https://premiumkita.com'), '/');

        $isNoIndex = str_contains(strtolower($cfg['seo_robots_index'] ?? ''), 'noindex');

        $lines = [
            '# Robots.txt for ' . ($cfg['store_name'] ?? 'PremiumKita') . ' - Optimized for Google Search Console',
            'User-agent: *',
        ];

        if ($isNoIndex) {
            $lines[] = 'Disallow: /';
        } else {
            $lines[] = 'Allow: /';
            $lines[] = 'Allow: /ppob';
            $lines[] = 'Allow: /product/';
            $lines[] = 'Allow: /faq';
            $lines[] = 'Allow: /syarat-ketentuan';
            $lines[] = 'Disallow: /admin/';
            $lines[] = 'Disallow: /profile';
            $lines[] = 'Disallow: /checkout/';
            $lines[] = 'Disallow: /order/invoice/';
            $lines[] = 'Disallow: /invoice/';
            $lines[] = 'Disallow: /api/';
        }

        if (!empty($cfg['seo_robots_custom'])) {
            $lines[] = '';
            $lines[] = '# Custom Rules';
            $lines[] = trim($cfg['seo_robots_custom']);
        }

        if (($cfg['seo_sitemap_enabled'] ?? '1') === '1') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $baseUrl . '/sitemap.xml';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Hitung Skor Kesehatan SEO (0 - 100) beserta detail checklist untuk Admin Panel.
     */
    public static function calculateSeoAudit(?array $cfg = null): array
    {
        $cfg = $cfg ?: self::getConfig();
        $checks = [];
        $score = 0;

        // 1. Tagline & Judul Toko (15 poin)
        $taglineLen = mb_strlen($cfg['store_tagline'] ?? '');
        $hasGoodTagline = $taglineLen >= 15 && $taglineLen <= 120;
        if ($hasGoodTagline) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Tagline & Judul Utama SEO',
            'passed' => $hasGoodTagline,
            'points' => 15,
            'detail' => $hasGoodTagline
                ? "Tagline aktif ({$taglineLen} karakter) — optimal untuk judul Google SERP."
                : 'Tagline terlalu pendek atau belum diisi (ideal: 20–90 karakter).',
        ];

        // 2. Meta Description (15 poin)
        $descLen = mb_strlen($cfg['store_description'] ?? '');
        $hasGoodDesc = $descLen >= 80 && $descLen <= 250;
        if ($hasGoodDesc) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Meta Description Utama Google',
            'passed' => $hasGoodDesc,
            'points' => 15,
            'detail' => $hasGoodDesc
                ? "Panjang deskripsi {$descLen} karakter — sangat ideal untuk cuplikan Google."
                : "Panjang deskripsi {$descLen} karakter (rekomendasi: 110–165 karakter).",
        ];

        // 3. Kata Kunci / Meta Keywords (10 poin)
        $keywordList = array_filter(array_map('trim', explode(',', (string) ($cfg['seo_meta_keywords'] ?? ''))));
        $keywordCount = count($keywordList);
        $hasGoodKeywords = $keywordCount >= 5;
        if ($hasGoodKeywords) {
            $score += 10;
        }
        $checks[] = [
            'label'  => 'Target Kata Kunci (Meta Keywords)',
            'passed' => $hasGoodKeywords,
            'points' => 10,
            'detail' => "{$keywordCount} kata kunci target terdaftar.",
        ];

        // 4. Status Robots Indexing (15 poin)
        $isIndexable = !str_contains(strtolower($cfg['seo_robots_index'] ?? 'index, follow'), 'noindex');
        if ($isIndexable) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Izin Perayapan Googlebot (Index & Follow)',
            'passed' => $isIndexable,
            'points' => 15,
            'detail' => $isIndexable
                ? 'Aktif (index, follow) — Googlebot diizinkan mengindeks semua halaman publik.'
                : 'PERINGATAN: Mode noindex aktif! Website tidak akan muncul di Google.',
        ];

        // 5. XML Sitemap Otomatis (15 poin)
        $sitemapOn = ($cfg['seo_sitemap_enabled'] ?? '1') === '1';
        if ($sitemapOn) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Peta Situs Dinamis (/sitemap.xml)',
            'passed' => $sitemapOn,
            'points' => 15,
            'detail' => $sitemapOn
                ? 'Aktif — seluruh halaman & produk aktif otomatis masuk ke /sitemap.xml.'
                : 'Sitemap XML dinonaktifkan.',
        ];

        // 6. Schema.org JSON-LD Rich Snippets (15 poin)
        $schemaOn = ($cfg['seo_schema_enabled'] ?? '1') === '1';
        if ($schemaOn) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Structured Data JSON-LD (Schema.org & Sitelinks)',
            'passed' => $schemaOn,
            'points' => 15,
            'detail' => $schemaOn
                ? 'Aktif — Schema OnlineStore, Sitelinks Searchbox, Harga Produk & FAQ aktif.'
                : 'Schema JSON-LD dinonaktifkan.',
        ];

        // 7. Verifikasi Google Search Console (15 poin)
        $hasGsc = !empty($cfg['seo_google_verification']) || !empty($cfg['seo_google_html_file']);
        if ($hasGsc) {
            $score += 15;
        }
        $checks[] = [
            'label'  => 'Verifikasi Kepemilikan Google Search Console',
            'passed' => $hasGsc,
            'points' => 15,
            'detail' => $hasGsc
                ? 'Kode verifikasi Google Search Console sudah terpasang.'
                : 'Belum diisi — masukkan kode meta google-site-verification dari Google Search Console.',
        ];

        return [
            'score'  => $score,
            'checks' => $checks,
        ];
    }
}

