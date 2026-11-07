<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotApiService
{
    private string $baseUrl;
    private string $apiKey;
    private float $usdRate;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null, ?float $usdRate = null)
    {
        $settings = Setting::whereIn('key', [
            'telegram_api_key',
            'telegram_api_base_url',
            'telegram_usd_rate',
        ])->pluck('value', 'key');

        $this->apiKey = trim((string) ($apiKey ?? ($settings['telegram_api_key'] ?? env('TELEGRAM_BOT_API_KEY', ''))));
        $rawBase = trim((string) ($baseUrl ?? ($settings['telegram_api_base_url'] ?? env('TELEGRAM_BOT_API_URL', 'https://www.quantumvault.me/api/v1'))));
        $this->baseUrl = rtrim($rawBase !== '' ? $rawBase : 'https://www.quantumvault.me/api/v1', '/');

        $rawRate = $usdRate ?? ($settings['telegram_usd_rate'] ?? 16500);
        $this->usdRate = max(1, (float) $rawRate);
    }

    /**
     * Cek apakah API Key sudah diatur
     */
    public function hasApiKey(): bool
    {
        return $this->apiKey !== '';
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getUsdRate(): float
    {
        return $this->usdRate;
    }

    /**
     * Cek apakah provider Telegram Bot API diaktifkan untuk di-order oleh user.
     * Default: OFF / Nonaktif ('1' pada provider_telegram_disabled) sesuai permintaan sebelum rilis.
     */
    public static function isEnabledForUsers(): bool
    {
        $disabled = Setting::where('key', 'provider_telegram_disabled')->value('value');
        if ($disabled === null) {
            return false; // Default sementara mati sebelum rilis
        }
        return (string) $disabled === '0';
    }

    /**
     * HTTP Client helper dengan IPv4 resolution & X-API-Key header
     */
    private function client(int $timeout = 15, int $connectTimeout = 8)
    {
        $client = Http::withoutVerifying()
            ->connectTimeout(min($connectTimeout, $timeout))
            ->timeout($timeout)
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ]);

        if (defined('CURL_IPRESOLVE_V4')) {
            $client = $client->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ]);
        }

        return $client;
    }

    /**
     * GET /me — Account summary (username, balance, credit, currency, memberSince)
     */
    public function me(int $timeout = 10): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.'];
        }

        try {
            $res = $this->client($timeout)->get($this->baseUrl . '/me');
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                return ['ok' => true, 'status' => $res->status(), 'data' => $json];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * GET /balance — Wallet balance & referral credit
     */
    public function balance(int $timeout = 10): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.'];
        }

        try {
            $res = $this->client($timeout)->get($this->baseUrl . '/balance');
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                return ['ok' => true, 'status' => $res->status(), 'data' => $json];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * GET /products — List all products & variants
     */
    public function getProducts(int $timeout = 15): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.', 'products' => []];
        }

        try {
            $res = $this->client($timeout)->get($this->baseUrl . '/products');
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                $products = $json['products'] ?? (isset($json[0]) ? $json : []);
                return [
                    'ok' => true,
                    'status' => $res->status(),
                    'count' => $json['count'] ?? count($products),
                    'products' => is_array($products) ? $products : [],
                    'data' => $json,
                ];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'products' => [],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage(), 'products' => []];
        }
    }

    /**
     * GET /products/:productKey — Full detail for one product
     */
    public function getProductDetail(string $productKey, int $timeout = 12): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.'];
        }

        try {
            $res = $this->client($timeout)->get($this->baseUrl . '/products/' . rawurlencode(trim($productKey)));
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                return [
                    'ok' => true,
                    'status' => $res->status(),
                    'product' => $json['product'] ?? $json,
                    'data' => $json,
                ];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * POST /purchase — Buy one or more units of a product (and optional variantKey)
     */
    public function purchase(string $productKey, ?string $variantKey = null, int $quantity = 1, int $timeout = 30): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.'];
        }

        $payload = [
            'productKey' => trim($productKey),
            'quantity' => max(1, min(50, $quantity)),
        ];
        if ($variantKey !== null && trim($variantKey) !== '') {
            $payload['variantKey'] = trim($variantKey);
        }

        $res = $this->client($timeout, 10)->post($this->baseUrl . '/purchase', $payload);
        $json = $res->json() ?? [];

        if ($res->successful() && (!empty($json['ok']) || !empty($json['order']))) {
            return [
                'ok' => true,
                'status' => $res->status(),
                'order' => $json['order'] ?? [],
                'balanceAfter' => $json['balanceAfter'] ?? null,
                'creditAfter' => $json['creditAfter'] ?? null,
                'data' => $json,
            ];
        }

        return [
            'ok' => false,
            'status' => $res->status(),
            'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
            'data' => $json,
        ];
    }

    /**
     * GET /orders — Order history & summary
     */
    public function getOrders(int $limit = 50, int $timeout = 12): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.', 'orders' => []];
        }

        try {
            $limit = max(1, min(100, $limit));
            $res = $this->client($timeout)->get($this->baseUrl . '/orders', ['limit' => $limit]);
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                return [
                    'ok' => true,
                    'status' => $res->status(),
                    'summary' => $json['summary'] ?? null,
                    'orders' => $json['orders'] ?? [],
                    'data' => $json,
                ];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'orders' => [],
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage(), 'orders' => []];
        }
    }

    /**
     * GET /orders/:orderId — Strictly read-only recovery of delivered fields/data
     */
    public function getOrderDetail(string $orderId, int $timeout = 12): array
    {
        if (!$this->hasApiKey()) {
            return ['ok' => false, 'status' => 401, 'error' => 'API Key belum diisi.'];
        }

        try {
            $res = $this->client($timeout)->get($this->baseUrl . '/orders/' . rawurlencode(trim($orderId)));
            $json = $res->json() ?? [];
            if ($res->successful() && (!isset($json['ok']) || $json['ok'] === true)) {
                return [
                    'ok' => true,
                    'status' => $res->status(),
                    'order' => $json['order'] ?? $json,
                    'data' => $json,
                ];
            }
            return [
                'ok' => false,
                'status' => $res->status(),
                'error' => $json['error'] ?? $json['message'] ?? ('HTTP ' . $res->status()),
                'data' => $json,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Parse api_product_id ("TG-outlook_mail" atau "TG-netflix_link::1_month")
     * menjadi ['productKey' => ..., 'variantKey' => ...]
     */
    public static function parseApiProductId(string $apiProductId): array
    {
        $raw = preg_replace('/^TG-/i', '', trim($apiProductId));
        if (str_contains($raw, '::')) {
            [$productKey, $variantKey] = explode('::', $raw, 2);
            return [
                'productKey' => trim($productKey),
                'variantKey' => trim($variantKey) !== '' ? trim($variantKey) : null,
            ];
        }

        return [
            'productKey' => trim($raw),
            'variantKey' => null,
        ];
    }

    /**
     * Konversi harga dari mata uang API (USD / IDR) ke Rupiah (IDR)
     */
    public function convertPriceToIdr($rawPrice, string $currency = 'USD'): int
    {
        $val = (float) ($rawPrice ?? 0);
        if ($val <= 0) {
            return 0;
        }
        if (strtoupper(trim($currency)) === 'IDR') {
            return (int) ceil($val);
        }
        return (int) ceil($val * $this->usdRate);
    }

    /**
     * Bangun deskripsi bersih beserta informasi garansi (warranty) jika tersedia
     */
    public static function buildDescription(?string $rawDesc, ?array $warranty = null): string
    {
        $parts = [];
        $cleaned = Product::cleanText($rawDesc);
        if (!empty($cleaned)) {
            $parts[] = $cleaned;
        }

        if (is_array($warranty)) {
            $days = isset($warranty['durationDays']) ? (int) $warranty['durationDays'] : 0;
            $details = trim((string) ($warranty['details'] ?? ''));
            if ($days > 0 || $details !== '') {
                $warrantyText = 'Garansi';
                if ($days > 0) {
                    $warrantyText .= ' ' . $days . ' Hari';
                }
                if ($details !== '') {
                    $warrantyText .= ': ' . $details;
                }
                $parts[] = $warrantyText;
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Mengambil katalog dari GET /products dan menormalisasi setiap produk / varian
     * menjadi daftar item siap-pakai dengan ID unik TG-{productKey} atau TG-{productKey}::{variantKey}.
     */
    public function getNormalizedCatalog(int $timeout = 12): array
    {
        $res = $this->getProducts($timeout);
        if (empty($res['ok']) || empty($res['products']) || !is_array($res['products'])) {
            return [];
        }

        $catalog = [];
        foreach ($res['products'] as $prod) {
            $productKey = trim((string) ($prod['productKey'] ?? $prod['key'] ?? ''));
            if ($productKey === '') {
                continue;
            }

            $baseName = trim((string) ($prod['name'] ?? $productKey));
            $currency = strtoupper(trim((string) ($prod['currency'] ?? 'USD')));
            $baseDesc = (string) ($prod['description'] ?? '');
            $baseWarranty = is_array($prod['warranty'] ?? null) ? $prod['warranty'] : null;
            $variants = (!empty($prod['variants']) && is_array($prod['variants'])) ? $prod['variants'] : [];

            if (!empty($variants)) {
                foreach ($variants as $var) {
                    $varKey = trim((string) ($var['key'] ?? $var['variantKey'] ?? ''));
                    if ($varKey === '') {
                        continue;
                    }

                    $apiId = 'TG-' . $productKey . '::' . $varKey;
                    $varName = trim((string) ($var['name'] ?? $varKey));
                    $fullName = $baseName . ' - ' . $varName;

                    $rawPrice = $var['price'] ?? $prod['price'] ?? 0;
                    $idrPrice = $this->convertPriceToIdr($rawPrice, $currency);

                    $unlimited = !empty($var['unlimited']);
                    $inStock = array_key_exists('inStock', $var) ? (bool) $var['inStock'] : (bool) ($prod['inStock'] ?? true);
                    $rawStock = $var['stock'] ?? $prod['stock'] ?? null;

                    if (!$inStock) {
                        $stockCount = 0;
                    } elseif ($unlimited) {
                        $stockCount = 999;
                    } else {
                        $stockCount = $rawStock !== null ? max(0, (int) $rawStock) : 99;
                    }

                    $varDesc = !empty($var['description']) ? (string) $var['description'] : $baseDesc;
                    $varWarranty = is_array($var['warranty'] ?? null) ? $var['warranty'] : $baseWarranty;
                    $finalDesc = self::buildDescription($varDesc, $varWarranty);

                    $catalog[$apiId] = [
                        'api_id' => $apiId,
                        'product_key' => $productKey,
                        'variant_key' => $varKey,
                        'name' => $fullName,
                        'original_name' => $fullName,
                        'price' => $idrPrice,
                        'usd_price' => (float) $rawPrice,
                        'currency' => $currency,
                        'stock' => $stockCount,
                        'in_stock' => $stockCount > 0,
                        'unlimited' => $unlimited,
                        'description' => $finalDesc,
                        'warranty' => $varWarranty,
                        'provider' => 'Telegram API',
                        'image' => $prod['image'] ?? $prod['icon'] ?? null,
                    ];
                }
            } else {
                $apiId = 'TG-' . $productKey;
                $rawPrice = $prod['price'] ?? 0;
                $idrPrice = $this->convertPriceToIdr($rawPrice, $currency);

                $unlimited = !empty($prod['unlimited']);
                $inStock = array_key_exists('inStock', $prod) ? (bool) $prod['inStock'] : true;
                $rawStock = $prod['stock'] ?? null;

                if (!$inStock) {
                    $stockCount = 0;
                } elseif ($unlimited) {
                    $stockCount = 999;
                } else {
                    $stockCount = $rawStock !== null ? max(0, (int) $rawStock) : 99;
                }

                $finalDesc = self::buildDescription($baseDesc, $baseWarranty);

                $catalog[$apiId] = [
                    'api_id' => $apiId,
                    'product_key' => $productKey,
                    'variant_key' => null,
                    'name' => $baseName,
                    'original_name' => $baseName,
                    'price' => $idrPrice,
                    'usd_price' => (float) $rawPrice,
                    'currency' => $currency,
                    'stock' => $stockCount,
                    'in_stock' => $stockCount > 0,
                    'unlimited' => $unlimited,
                    'description' => $finalDesc,
                    'warranty' => $baseWarranty,
                    'provider' => 'Telegram API',
                    'image' => $prod['image'] ?? $prod['icon'] ?? null,
                ];
            }
        }

        return $catalog;
    }

    /**
     * Format kredensial dinamis dari order.items[] (POST /purchase) atau order (GET /orders/:orderId).
     * Mengikuti spesifikasi QuantumVault: membaca fields[] ({name, label, value}) dan/atau data object secara dinamis!
     */
    public static function formatDeliveredItems(array $items, int $requestedQty = 1): ?string
    {
        if (empty($items)) {
            return null;
        }

        $total = count($items);
        $blocks = [];

        foreach (array_values($items) as $idx => $item) {
            if (!is_array($item)) {
                continue;
            }

            $lines = [];
            if ($total > 1 || $requestedQty > 1) {
                $unitNum = $item['unit'] ?? ($idx + 1);
                $lines[] = '=== AKUN / LISENSI #' . $unitNum . ' ===';
            }

            // 1. Utamakan membaca fields[] ({name, label, value}) sesuai dokumentasi QuantumVault
            if (!empty($item['fields']) && is_array($item['fields'])) {
                foreach ($item['fields'] as $field) {
                    if (!is_array($field)) {
                        continue;
                    }
                    $label = trim((string) ($field['label'] ?? $field['name'] ?? 'Data'));
                    $value = trim((string) ($field['value'] ?? ''));
                    if ($value !== '') {
                        $lines[] = ($label !== '' ? $label : 'Data') . ' : ' . $value;
                    }
                }
            }

            // 2. Fallback ke object `data` ({key: value}) apabila fields[] kosong
            if (count($lines) <= (($total > 1 || $requestedQty > 1) ? 1 : 0) && !empty($item['data']) && is_array($item['data'])) {
                foreach ($item['data'] as $key => $val) {
                    if (is_scalar($val) && trim((string) $val) !== '') {
                        $prettyKey = ucwords(str_replace(['_', '-'], ' ', (string) $key));
                        $lines[] = $prettyKey . ' : ' . trim((string) $val);
                    }
                }
            }

            // 3. Tambahkan informasi garansi aktif jika dikembalikan oleh API
            if (!empty($item['warranty']) && is_array($item['warranty'])) {
                $wDays = $item['warranty']['durationDays'] ?? null;
                $wDetails = trim((string) ($item['warranty']['details'] ?? ''));
                $wExp = trim((string) ($item['warranty']['expiresAt'] ?? ''));
                $wParts = [];
                if ($wDays) {
                    $wParts[] = $wDays . ' Hari';
                }
                if ($wDetails !== '') {
                    $wParts[] = $wDetails;
                }
                if ($wExp !== '') {
                    $wParts[] = 'Exp: ' . substr($wExp, 0, 10);
                }
                if (!empty($wParts)) {
                    $lines[] = 'Garansi : ' . implode(' | ', $wParts);
                }
            }

            // Hanya masukkan blok jika benar-benar memiliki isi kredensial
            $minLines = ($total > 1 || $requestedQty > 1) ? 2 : 1;
            if (count($lines) >= $minLines) {
                $blocks[] = implode("\n", $lines);
            }
        }

        if (empty($blocks)) {
            return null;
        }

        return implode("\n\n", $blocks);
    }
}
