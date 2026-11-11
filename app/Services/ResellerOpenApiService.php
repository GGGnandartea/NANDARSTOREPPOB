<?php

namespace App\Services;

use App\Http\Controllers\FrontController;
use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerApiClient;
use App\Models\ResellerApiLog;
use App\Models\Setting;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ResellerOpenApiService
{
    public const CONFIG_CACHE_KEY = 'reseller_open_api_global_cfg_v1';

    /**
     * Pastikan skema tabel Open API tersedia (aman untuk environment baru / pengujian).
     */
    public static function ensureSchema(): void
    {
        if (Cache::get('reseller_open_api_schema_ready_v1') && Schema::hasTable('reseller_api_clients')) {
            return;
        }

        try {
            if (!Schema::hasTable('reseller_api_clients')) {
                Schema::create('reseller_api_clients', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                    $table->string('api_key', 80)->unique();
                    $table->text('secret_key_encrypted');
                    $table->string('secret_key_hint', 40);
                    $table->string('webhook_url', 500)->nullable();
                    $table->text('webhook_secret_encrypted')->nullable();
                    $table->string('webhook_secret_hint', 40)->nullable();
                    $table->text('ip_whitelist')->nullable();
                    $table->boolean('is_enabled')->default(true);
                    $table->boolean('admin_suspended')->default(false);
                    $table->string('admin_suspend_reason', 255)->nullable();
                    $table->integer('custom_rate_limit')->nullable();
                    $table->timestamp('last_used_at')->nullable();
                    $table->string('last_used_ip', 64)->nullable();
                    $table->unsignedBigInteger('total_requests')->default(0);
                    $table->unsignedBigInteger('total_orders')->default(0);
                    $table->timestamp('rotated_at')->nullable();
                    $table->timestamps();
                });
            }

            if (!Schema::hasTable('reseller_api_logs')) {
                Schema::create('reseller_api_logs', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->nullable()->index();
                    $table->string('endpoint', 180);
                    $table->string('method', 12);
                    $table->string('ip_address', 64)->nullable();
                    $table->unsignedSmallInteger('status_code');
                    $table->string('ref_id', 100)->nullable()->index();
                    $table->string('invoice_number', 100)->nullable()->index();
                    $table->string('error_code', 60)->nullable();
                    $table->unsignedInteger('response_time_ms')->default(0);
                    $table->string('summary', 255)->nullable();
                    $table->timestamp('created_at')->nullable()->index();
                });
            }

            if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'is_open_api')) {
                Schema::table('orders', function (Blueprint $table) {
                    if (!Schema::hasColumn('orders', 'is_open_api')) {
                        $table->boolean('is_open_api')->default(false)->index();
                    }
                    if (!Schema::hasColumn('orders', 'api_client_ref_id')) {
                        $table->string('api_client_ref_id', 100)->nullable();
                    }
                    if (!Schema::hasColumn('orders', 'open_api_callback_url')) {
                        $table->string('open_api_callback_url', 500)->nullable();
                    }
                    if (!Schema::hasColumn('orders', 'open_api_webhook_sent_at')) {
                        $table->timestamp('open_api_webhook_sent_at')->nullable();
                    }
                    if (!Schema::hasColumn('orders', 'open_api_webhook_event')) {
                        $table->string('open_api_webhook_event', 40)->nullable();
                    }
                });
            }

            Cache::put('reseller_open_api_schema_ready_v1', true, 3600);
        } catch (\Throwable $e) {
            Log::warning('[OPEN-API-SCHEMA] ' . $e->getMessage());
        }
    }

    /**
     * Ambil konfigurasi standar Open API global (Admin).
     */
    public static function getGlobalConfig(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CONFIG_CACHE_KEY);
        }

        return Cache::remember(self::CONFIG_CACHE_KEY, 60, function () {
            $keys = [
                'open_api_enabled',
                'open_api_auth_mode',
                'open_api_timestamp_tolerance',
                'open_api_require_ip_whitelist',
                'open_api_rate_limit_rpm',
                'open_api_order_rate_limit_rpm',
                'open_api_max_qty_per_order',
                'open_api_allow_local',
                'open_api_allow_local_manual',
                'open_api_allow_premku',
                'open_api_allow_preflix',
                'open_api_extra_fee',
                'open_api_webhook_enabled',
                'open_api_maintenance_message',
            ];

            $raw = [];
            try {
                $raw = Setting::whereIn('key', $keys)->pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                $raw = [];
            }

            $authMode = (string) ($raw['open_api_auth_mode'] ?? 'hmac_strict');
            if (!in_array($authMode, ['hmac_strict', 'hmac_order_only', 'key_secret'], true)) {
                $authMode = 'hmac_strict';
            }

            return [
                'enabled'              => ($raw['open_api_enabled'] ?? '1') === '1',
                'auth_mode'            => $authMode,
                'timestamp_tolerance'  => max(30, min(3600, (int) ($raw['open_api_timestamp_tolerance'] ?? 300))),
                'require_ip_whitelist' => ($raw['open_api_require_ip_whitelist'] ?? '0') === '1',
                'rate_limit_rpm'       => max(10, min(1000, (int) ($raw['open_api_rate_limit_rpm'] ?? 60))),
                'order_rate_limit_rpm' => max(5, min(300, (int) ($raw['open_api_order_rate_limit_rpm'] ?? 20))),
                'max_qty_per_order'    => max(1, min(50, (int) ($raw['open_api_max_qty_per_order'] ?? 10))),
                'allow_local'          => ($raw['open_api_allow_local'] ?? '1') === '1',
                'allow_local_manual'   => ($raw['open_api_allow_local_manual'] ?? '1') === '1',
                'allow_premku'         => ($raw['open_api_allow_premku'] ?? '1') === '1',
                'allow_preflix'        => ($raw['open_api_allow_preflix'] ?? '1') === '1',
                'extra_fee'            => max(0, (int) ($raw['open_api_extra_fee'] ?? 0)),
                'webhook_enabled'      => ($raw['open_api_webhook_enabled'] ?? '1') === '1',
                'maintenance_message'  => trim((string) ($raw['open_api_maintenance_message'] ?? ''))
                    ?: 'Layanan Open API Reseller sedang dalam pemeliharaan terjadwal. Silakan coba beberapa saat lagi.',
            ];
        });
    }

    public static function isEnabled(): bool
    {
        return (bool) (self::getGlobalConfig()['enabled'] ?? true);
    }

    public static function clearConfigCache(): void
    {
        Cache::forget(self::CONFIG_CACHE_KEY);
    }

    /**
     * Simpan konfigurasi Open API global oleh Admin.
     */
    public static function saveGlobalConfig(array $data): array
    {
        $map = [
            'open_api_enabled'              => !empty($data['enabled']) ? '1' : '0',
            'open_api_auth_mode'            => in_array($data['auth_mode'] ?? '', ['hmac_strict', 'hmac_order_only', 'key_secret'], true)
                ? $data['auth_mode']
                : 'hmac_strict',
            'open_api_timestamp_tolerance'  => (string) max(30, min(3600, (int) ($data['timestamp_tolerance'] ?? 300))),
            'open_api_require_ip_whitelist' => !empty($data['require_ip_whitelist']) ? '1' : '0',
            'open_api_rate_limit_rpm'       => (string) max(10, min(1000, (int) ($data['rate_limit_rpm'] ?? 60))),
            'open_api_order_rate_limit_rpm' => (string) max(5, min(300, (int) ($data['order_rate_limit_rpm'] ?? 20))),
            'open_api_max_qty_per_order'    => (string) max(1, min(50, (int) ($data['max_qty_per_order'] ?? 10))),
            'open_api_allow_local'          => !empty($data['allow_local']) ? '1' : '0',
            'open_api_allow_local_manual'   => !empty($data['allow_local_manual']) ? '1' : '0',
            'open_api_allow_premku'         => !empty($data['allow_premku']) ? '1' : '0',
            'open_api_allow_preflix'        => !empty($data['allow_preflix']) ? '1' : '0',
            'open_api_extra_fee'            => (string) max(0, (int) ($data['extra_fee'] ?? 0)),
            'open_api_webhook_enabled'      => !empty($data['webhook_enabled']) ? '1' : '0',
            'open_api_maintenance_message'  => substr(trim((string) ($data['maintenance_message'] ?? '')), 0, 250),
        ];

        foreach ($map as $key => $val) {
            Setting::updateOrCreate(['key' => $key], ['value' => $val]);
        }

        self::clearConfigCache();

        return self::getGlobalConfig(true);
    }

    /**
     * Buat atau rotasi API Key & Secret Key khusus Reseller VIP.
     */
    public static function generateOrRotateCredentials(User $user, bool $rotateWebhookSecret = false): array
    {
        self::ensureSchema();

        if ($user->role !== 'reseller' || !$user->is_active) {
            throw new \RuntimeException('Akses Open API hanya tersedia untuk akun VIP Reseller yang aktif.');
        }

        do {
            $apiKey = 'pk_live_' . bin2hex(random_bytes(16));
        } while (ResellerApiClient::where('api_key', $apiKey)->exists());

        $secretKey = 'sk_live_' . bin2hex(random_bytes(24));
        $secretHint = substr($secretKey, 0, 11) . '...' . substr($secretKey, -4);

        $existing = ResellerApiClient::where('user_id', $user->id)->first();
        $plainWebhookSecret = null;

        if (!$existing || empty($existing->webhook_secret_encrypted) || $rotateWebhookSecret) {
            $plainWebhookSecret = 'whsec_' . bin2hex(random_bytes(20));
            $webhookSecretEnc = Crypt::encryptString($plainWebhookSecret);
            $webhookSecretHint = substr($plainWebhookSecret, 0, 9) . '...' . substr($plainWebhookSecret, -4);
        } else {
            $webhookSecretEnc = $existing->webhook_secret_encrypted;
            $webhookSecretHint = $existing->webhook_secret_hint;
        }

        $client = ResellerApiClient::updateOrCreate(
            ['user_id' => $user->id],
            [
                'api_key'                  => $apiKey,
                'secret_key_encrypted'     => Crypt::encryptString($secretKey),
                'secret_key_hint'          => $secretHint,
                'webhook_secret_encrypted' => $webhookSecretEnc,
                'webhook_secret_hint'      => $webhookSecretHint,
                'is_enabled'               => $existing ? $existing->is_enabled : true,
                'rotated_at'               => now(),
            ]
        );

        return [
            'client'         => $client,
            'api_key'        => $apiKey,
            'secret_key'     => $secretKey,
            'webhook_secret' => $plainWebhookSecret,
        ];
    }

    /**
     * Validasi daftar IP Whitelist (mendukung IPv4, IPv6, dan CIDR seperti 203.0.113.0/24).
     */
    public static function validateAndNormalizeIpWhitelist(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return ['ok' => true, 'normalized' => null, 'entries' => []];
        }

        $parts = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $entries = [];

        foreach ($parts as $part) {
            $item = trim($part);
            if ($item === '') {
                continue;
            }

            if (!self::isValidIpOrCidr($item)) {
                return [
                    'ok'      => false,
                    'message' => "Format IP / CIDR tidak valid: {$item}. Gunakan alamat IPv4/IPv6 valid atau notasi CIDR (contoh: 103.150.10.25 atau 103.150.10.0/24).",
                ];
            }

            $entries[] = $item;
        }

        $entries = array_values(array_unique($entries));
        if (count($entries) > 25) {
            return [
                'ok'      => false,
                'message' => 'Maksimal 25 entri IP / CIDR yang dapat didaftarkan pada IP Whitelist.',
            ];
        }

        return [
            'ok'         => true,
            'normalized' => empty($entries) ? null : implode("\n", $entries),
            'entries'    => $entries,
        ];
    }

    public static function isValidIpOrCidr(string $entry): bool
    {
        if (filter_var($entry, FILTER_VALIDATE_IP)) {
            return true;
        }

        if (!str_contains($entry, '/')) {
            return false;
        }

        [$subnet, $prefix] = explode('/', $entry, 2);
        if (!ctype_digit($prefix)) {
            return false;
        }

        $prefixInt = (int) $prefix;
        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $prefixInt >= 8 && $prefixInt <= 32;
        }
        if (filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $prefixInt >= 32 && $prefixInt <= 128;
        }

        return false;
    }

    /**
     * Cek apakah IP pemanggil cocok dengan daftar IP Whitelist (mendukung IP tunggal & CIDR).
     */
    public static function ipMatchesWhitelist(string $clientIp, array $whitelist): bool
    {
        $clientBin = @inet_pton($clientIp);
        if ($clientBin === false) {
            return false;
        }

        foreach ($whitelist as $entry) {
            $entry = trim((string) $entry);
            if ($entry === '') {
                continue;
            }

            if (!str_contains($entry, '/')) {
                $entryBin = @inet_pton($entry);
                if ($entryBin !== false && $entryBin === $clientBin) {
                    return true;
                }
                continue;
            }

            [$subnet, $prefix] = explode('/', $entry, 2);
            $subnetBin = @inet_pton($subnet);
            if ($subnetBin === false || strlen($subnetBin) !== strlen($clientBin) || !ctype_digit($prefix)) {
                continue;
            }

            $bits = (int) $prefix;
            $maxBits = strlen($clientBin) * 8;
            if ($bits < 0 || $bits > $maxBits) {
                continue;
            }

            $fullBytes = intdiv($bits, 8);
            $remBits = $bits % 8;

            if ($fullBytes > 0 && substr($clientBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) {
                continue;
            }

            if ($remBits === 0) {
                return true;
            }

            $mask = (0xFF << (8 - $remBits)) & 0xFF;
            if ((ord($clientBin[$fullBytes]) & $mask) === (ord($subnetBin[$fullBytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validasi URL Webhook agar aman dari serangan SSRF ke jaringan internal/loopback.
     */
    public static function validateWebhookUrl(?string $url): array
    {
        if ($url === null || trim($url) === '') {
            return ['ok' => true, 'url' => null];
        }

        $clean = trim($url);
        if (strlen($clean) > 500 || !filter_var($clean, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'message' => 'Format Webhook URL tidak valid. Gunakan URL lengkap (contoh: https://domainanda.com/api/webhook/premiumkita).'];
        }

        $parsed = parse_url($clean);
        $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
        $host = strtolower(trim((string) ($parsed['host'] ?? ''), '[]'));

        if (!in_array($scheme, ['https', 'http'], true) || $host === '') {
            return ['ok' => false, 'message' => 'Webhook URL wajib menggunakan protokol HTTPS (atau HTTP valid).'];
        }

        if (app()->environment('production') && $scheme !== 'https') {
            return ['ok' => false, 'message' => 'Pada mode produksi, Webhook URL wajib menggunakan HTTPS demi keamanan data akun.'];
        }

        // Blokir akses ke loopback / jaringan internal (proteksi SSRF) kecuali di unit test jika host eksplisit eksternal
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '::1', 'ip6-localhost'];
        if (in_array($host, $blockedHosts, true) || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return ['ok' => false, 'message' => 'Webhook URL tidak boleh mengarah ke localhost atau jaringan lokal internal.'];
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $publicIp = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($publicIp === false) {
                return ['ok' => false, 'message' => 'Webhook URL tidak boleh menggunakan alamat IP privat atau internal.'];
            }
        } elseif (!app()->runningUnitTests()) {
            $resolvedIps = @gethostbynamel($host);
            if (is_array($resolvedIps)) {
                foreach ($resolvedIps as $ip) {
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                        return ['ok' => false, 'message' => 'Domain Webhook URL terdeteksi mengarah ke alamat IP internal yang dilarang.'];
                    }
                }
            }
        }

        return ['ok' => true, 'url' => $clean];
    }

    /**
     * Bangun canonical string standar HMAC-SHA256 Open API:
     * METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + SHA256(RAW_BODY)
     * Untuk metode GET/HEAD (tidak memiliki body semantik HTTP), body dinormalisasi ke string kosong "".
     */
    public static function buildSignaturePayload(string $method, string $path, string $timestamp, string $rawBody = ''): string
    {
        $normalizedMethod = strtoupper(trim($method));
        $normalizedPath = '/' . ltrim(trim($path), '/');
        $effectiveBody = in_array($normalizedMethod, ['GET', 'HEAD'], true) ? '' : $rawBody;
        $bodyHash = hash('sha256', $effectiveBody);

        return "{$normalizedMethod}\n{$normalizedPath}\n{$timestamp}\n{$bodyHash}";
    }

    public static function computeHmacSignature(string $method, string $path, string $timestamp, string $rawBody, string $secretKey): string
    {
        $payload = self::buildSignaturePayload($method, $path, $timestamp, $rawBody);

        return hash_hmac('sha256', $payload, $secretKey);
    }

    /**
     * Query produk aplikasi premium yang DIIZINKAN untuk Open API Reseller:
     * HANYA produk aplikasi premium dari:
     * 1. Produk Lokal (type = 'local')
     * 2. Premku (type = 'api' & api_product_id LIKE 'PREMKU-%')
     * 3. Preflix (type = 'api' & api_product_id LIKE 'PREFLIX-%')
     *
     * Produk PPOB Kiosbank (ppob_products) dan Telegram Bot API (TG-*) DILARANG KERAS.
     */
    public static function eligibleProductsQuery(?array $cfg = null): Builder
    {
        $cfg = $cfg ?? self::getGlobalConfig();

        $disabledPremku = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';
        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';

        $allowLocalAuto = !empty($cfg['allow_local']);
        $allowLocalManual = !empty($cfg['allow_local_manual']);
        $allowPremku = !empty($cfg['allow_premku']) && !$disabledPremku;
        $allowPreflix = !empty($cfg['allow_preflix']) && !$disabledPreflix;

        return Product::withAvailableAccountStock()
            ->where('status', 'available')
            ->where(function (Builder $outer) use ($allowLocalAuto, $allowLocalManual, $allowPremku, $allowPreflix) {
                $hasAnyBranch = false;

                if ($allowLocalAuto || $allowLocalManual) {
                    $hasAnyBranch = true;
                    $outer->orWhere(function (Builder $localQ) use ($allowLocalAuto, $allowLocalManual) {
                        $localQ->where('type', 'local');
                        if ($allowLocalAuto && !$allowLocalManual) {
                            $localQ->where(function (Builder $pt) {
                                $pt->whereNull('process_type')->orWhere('process_type', '!=', 'manual');
                            });
                        } elseif (!$allowLocalAuto && $allowLocalManual) {
                            $localQ->where('process_type', 'manual');
                        }
                    });
                }

                if ($allowPremku) {
                    $hasAnyBranch = true;
                    $outer->orWhere(function (Builder $premkuQ) {
                        $premkuQ->where('type', 'api')->where('api_product_id', 'LIKE', 'PREMKU-%');
                    });
                }

                if ($allowPreflix) {
                    $hasAnyBranch = true;
                    $outer->orWhere(function (Builder $preflixQ) {
                        $preflixQ->where('type', 'api')->where('api_product_id', 'LIKE', 'PREFLIX-%');
                    });
                }

                if (!$hasAnyBranch) {
                    $outer->whereRaw('1 = 0');
                }
            });
    }

    /**
     * Cek kelayakan 1 produk secara ketat untuk Open API.
     */
    public static function isProductEligible(?Product $product, ?array $cfg = null): bool
    {
        if (!$product || $product->status !== 'available') {
            return false;
        }

        $cfg = $cfg ?? self::getGlobalConfig();

        if ($product->type === 'local') {
            $isManual = ($product->process_type ?? 'auto') === 'manual';
            return $isManual ? !empty($cfg['allow_local_manual']) : !empty($cfg['allow_local']);
        }

        if ($product->type === 'api') {
            $apiId = (string) ($product->api_product_id ?? '');

            if (str_starts_with($apiId, 'PREMKU-')) {
                $disabledPremku = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';
                return !empty($cfg['allow_premku']) && !$disabledPremku;
            }

            if (str_starts_with($apiId, 'PREFLIX-')) {
                $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
                return !empty($cfg['allow_preflix']) && !$disabledPreflix;
            }

            // TG-* atau provider API lain ditolak keras
            return false;
        }

        return false;
    }

    public static function resolveProductSource(Product $product): string
    {
        if ($product->type === 'local') {
            return 'local';
        }
        $apiId = (string) ($product->api_product_id ?? '');
        if (str_starts_with($apiId, 'PREMKU-')) {
            return 'premku';
        }
        if (str_starts_with($apiId, 'PREFLIX-')) {
            return 'preflix';
        }

        return 'unknown';
    }

    public static function computeResellerApiPrice(Product $product, ?array $cfg = null): int
    {
        $cfg = $cfg ?? self::getGlobalConfig();
        $base = ($product->reseller_price !== null && (int) $product->reseller_price > 0)
            ? (int) $product->reseller_price
            : (int) $product->price;

        $extraFee = max(0, (int) ($cfg['extra_fee'] ?? 0));

        return max(1, $base + $extraFee);
    }

    /**
     * Format produk untuk respons publik Open API (tanpa pernah membocorkan api_price / supplier internal).
     * Seluruh produk menggunakan branding tunggal resmi PremiumKita.
     */
    public static function formatProductForApi(Product $product, ?array $cfg = null): array
    {
        $cfg = $cfg ?? self::getGlobalConfig();
        $apiPrice = self::computeResellerApiPrice($product, $cfg);
        $retailPrice = max($apiPrice, (int) ($product->price ?? $apiPrice));
        $isManual = $product->type === 'local' && ($product->process_type ?? 'auto') === 'manual';
        $stock = max(0, (int) $product->stock);

        $imageUrl = null;
        if (!empty($product->image)) {
            $img = trim((string) $product->image);
            $imageUrl = (str_starts_with($img, 'http://') || str_starts_with($img, 'https://'))
                ? $img
                : url('/' . ltrim($img, '/'));
        }

        return [
            'id'            => (int) $product->id,
            'product_code'  => 'PK-APP-' . $product->id,
            'name'          => (string) $product->name,
            'category'      => (string) ($product->category ?: 'Aplikasi Premium'),
            'provider'      => 'PremiumKita',
            'source'        => 'premiumkita',
            'delivery_type' => $isManual ? 'manual' : 'instant',
            'price'         => $apiPrice,
            'retail_price'  => $retailPrice,
            'currency'      => 'IDR',
            'stock'         => $stock,
            'in_stock'      => $isManual ? true : ($stock > 0),
            'description'   => $product->description ? trim((string) $product->description) : null,
            'image_url'     => $imageUrl,
            'updated_at'    => $product->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Parse product_id atau product_code ("PK-APP-15" / "15") menjadi ID produk integer.
     */
    public static function parseProductIdentifier(mixed $productId, mixed $productCode = null): ?int
    {
        if (is_numeric($productId) && (int) $productId > 0) {
            return (int) $productId;
        }

        $candidate = is_string($productId) && trim($productId) !== ''
            ? trim($productId)
            : (is_string($productCode) ? trim($productCode) : '');

        if ($candidate !== '') {
            if (ctype_digit($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
            if (preg_match('/^PK-APP-(\d+)$/i', $candidate, $m)) {
                return (int) $m[1];
            }
        }

        return null;
    }

    /**
     * Eksekusi pembelian produk aplikasi premium via Open API menggunakan SALDO RESELLER secara atomik & idempoten.
     */
    public static function placeOrder(User $user, ResellerApiClient $client, array $payload): array
    {
        self::ensureSchema();
        $cfg = self::getGlobalConfig();

        if ($user->role !== 'reseller' || !$user->is_active) {
            return [
                'http_code'  => 403,
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Transaksi Open API hanya dapat dilakukan oleh akun VIP Reseller yang aktif.',
            ];
        }

        $refId = trim((string) ($payload['ref_id'] ?? ''));
        if ($refId === '' || !preg_match('/^[A-Za-z0-9\-_.:]{3,100}$/', $refId)) {
            return [
                'http_code'  => 422,
                'error_code' => 'INVALID_REF_ID',
                'message'    => 'Parameter ref_id wajib diisi (3-100 karakter alfanumerik, tanda hubung, titik, atau garis bawah).',
            ];
        }

        $resolvedProductId = self::parseProductIdentifier($payload['product_id'] ?? null, $payload['product_code'] ?? null);
        if (!$resolvedProductId) {
            return [
                'http_code'  => 422,
                'error_code' => 'INVALID_PRODUCT_ID',
                'message'    => 'Parameter product_id atau product_code tidak valid.',
            ];
        }

        $maxQty = (int) ($cfg['max_qty_per_order'] ?? 10);
        $qty = isset($payload['quantity']) ? (int) $payload['quantity'] : 1;
        if ($qty < 1 || $qty > $maxQty) {
            return [
                'http_code'  => 422,
                'error_code' => 'INVALID_QUANTITY',
                'message'    => "Jumlah pembelian (quantity) harus antara 1 sampai {$maxQty} pcs per transaksi API.",
            ];
        }

        $callbackOverride = isset($payload['callback_url']) && trim((string) $payload['callback_url']) !== ''
            ? trim((string) $payload['callback_url'])
            : null;

        if ($callbackOverride !== null) {
            $cbCheck = self::validateWebhookUrl($callbackOverride);
            if (!$cbCheck['ok']) {
                return [
                    'http_code'  => 422,
                    'error_code' => 'INVALID_CALLBACK_URL',
                    'message'    => $cbCheck['message'],
                ];
            }
            $callbackOverride = $cbCheck['url'];
        }

        // 1. Cek Idempotency Key (ref_id) lebih awal agar retry jaringan klien tidak pernah memotong saldo 2x
        $existingOrder = Order::where('user_id', $user->id)
            ->where('api_client_ref_id', $refId)
            ->first();

        if ($existingOrder) {
            if ((int) $existingOrder->product_id !== $resolvedProductId || (int) ($existingOrder->quantity ?: 1) !== $qty) {
                return [
                    'http_code'  => 409,
                    'error_code' => 'REF_ID_CONFLICT',
                    'message'    => 'Nilai ref_id sudah pernah digunakan untuk produk atau jumlah pembelian yang berbeda.',
                ];
            }

            if (
                $existingOrder->product_type === 'api'
                && in_array(strtolower((string) $existingOrder->order_status), ['processing', 'pending'], true)
                && OrderFulfillmentService::isWaitingText($existingOrder->sn_or_account)
            ) {
                try {
                    OrderFulfillmentService::checkAndSyncApiOrder($existingOrder);
                    $existingOrder->refresh();
                } catch (\Throwable $e) {}
            }

            return [
                'http_code'         => 200,
                'idempotent_replay' => true,
                'message'           => 'Transaksi dengan ref_id tersebut sudah tercatat (Idempotent Response). Saldo tidak dipotong ulang.',
                'order'             => $existingOrder,
                'data'              => self::formatOrderForApi($existingOrder, (int) $user->fresh()->balance, true),
            ];
        }

        // 2. Pastikan produk ada & termasuk cakupan ketat katalog Aplikasi Premium resmi PremiumKita
        $product = Product::withAvailableAccountStock()->find($resolvedProductId);
        if (!$product || !self::isProductEligible($product, $cfg)) {
            return [
                'http_code'  => 404,
                'error_code' => 'PRODUCT_NOT_AVAILABLE',
                'message'    => 'Produk tidak ditemukan atau tidak termasuk dalam katalog Open API Aplikasi Premium resmi PremiumKita.',
            ];
        }

        // 3. Sinkronkan stok & harga live provider sebelum mengunci saldo
        $frontController = app(FrontController::class);
        $isManualLocal = ($product->type === 'local' && ($product->process_type ?? 'auto') === 'manual');
        if (!$isManualLocal) {
            $availableStock = $frontController->resolveProductStock($product);
            if ($availableStock < $qty) {
                return [
                    'http_code'  => 400,
                    'error_code' => 'OUT_OF_STOCK',
                    'message'    => "Stok produk tidak mencukupi (tersedia: {$availableStock} pcs, diminta: {$qty} pcs). Saldo Anda tidak dipotong.",
                    'meta'       => [
                        'available_stock'    => $availableStock,
                        'requested_quantity' => $qty,
                    ],
                ];
            }
        }

        $unitPrice = self::computeResellerApiPrice($product, $cfg);
        $totalAmount = $unitPrice * $qty;

        $provider = 'Otomatis';
        if ($product->type === 'api') {
            $apiId = (string) ($product->api_product_id ?? '');
            $provider = str_starts_with($apiId, 'PREFLIX-') ? 'Preflix' : 'Premku';
        } elseif ($product->type === 'local') {
            $provider = $isManualLocal ? 'Manual Admin' : 'Otomatis';
        }

        $buyerName = !empty($payload['customer_name'])
            ? substr(trim((string) $payload['customer_name']), 0, 100)
            : $user->name;
        $buyerEmail = !empty($payload['customer_email']) && filter_var($payload['customer_email'], FILTER_VALIDATE_EMAIL)
            ? trim((string) $payload['customer_email'])
            : $user->email;
        $buyerWhatsapp = $user->whatsapp;
        if (!empty($payload['customer_phone'])) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $payload['customer_phone']);
            if (strlen($cleanPhone) >= 9 && strlen($cleanPhone) <= 15 && empty($buyerWhatsapp)) {
                $buyerWhatsapp = $cleanPhone;
            }
        }

        $effectiveCallbackUrl = $callbackOverride ?: ($client->webhook_url ?: null);
        $invoiceNumber = 'API-' . strtoupper(Str::random(8)) . '-' . time();

        $txOutcome = null;

        try {
            $txOutcome = DB::transaction(function () use (
                $user,
                $client,
                $product,
                $qty,
                $unitPrice,
                $totalAmount,
                $refId,
                $invoiceNumber,
                $provider,
                $buyerName,
                $buyerEmail,
                $buyerWhatsapp,
                $effectiveCallbackUrl,
                $payload
            ) {
                $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
                if (!$lockedUser || $lockedUser->role !== 'reseller' || !$lockedUser->is_active) {
                    return [
                        'status'     => 'forbidden',
                        'http_code'  => 403,
                        'error_code' => 'RESELLER_ONLY',
                        'message'    => 'Akun Anda tidak lagi berstatus Reseller VIP aktif.',
                    ];
                }

                // Cek ulang idempotency di dalam row lock user untuk mencegah race condition paralel
                $raceExisting = Order::where('user_id', $lockedUser->id)
                    ->where('api_client_ref_id', $refId)
                    ->first();
                if ($raceExisting) {
                    return [
                        'status'  => 'idempotent',
                        'order'   => $raceExisting,
                        'balance' => (int) $lockedUser->balance,
                    ];
                }

                $currentBalance = (int) $lockedUser->balance;
                if ($currentBalance < $totalAmount) {
                    return [
                        'status'     => 'insufficient_balance',
                        'http_code'  => 402,
                        'error_code' => 'INSUFFICIENT_BALANCE',
                        'message'    => 'Saldo akun Reseller tidak mencukupi untuk transaksi API ini. Silakan lakukan Top Up Saldo terlebih dahulu.',
                        'meta'       => [
                            'current_balance' => $currentBalance,
                            'required_amount' => $totalAmount,
                            'shortfall'       => $totalAmount - $currentBalance,
                        ],
                    ];
                }

                $lockedUser->decrement('balance', $totalAmount);

                TransactionLog::create([
                    'user_id'     => $lockedUser->id,
                    'type'        => 'debit',
                    'amount'      => $totalAmount,
                    'description' => "Open API Order ({$qty} pcs): {$product->name} [Ref: {$refId}]",
                ]);

                $customNote = !empty($payload['customer_note'])
                    ? 'Customer Note: ' . substr(trim((string) $payload['customer_note']), 0, 150)
                    : null;

                $order = Order::create([
                    'user_id'               => $lockedUser->id,
                    'buyer_name'            => $buyerName,
                    'invoice_number'        => $invoiceNumber,
                    'product_id'            => $product->id,
                    'product_name'          => $product->name,
                    'quantity'              => $qty,
                    'product_type'          => $product->type ?? 'local',
                    'provider'              => $provider,
                    'price'                 => $unitPrice,
                    'discount'              => 0,
                    'total_amount'          => $totalAmount,
                    'payment_status'        => 'paid',
                    'order_status'          => 'processing',
                    'email'                 => $buyerEmail,
                    'whatsapp'              => $buyerWhatsapp,
                    'discount_amount'       => 0,
                    'notes'                 => trim('[INTERNAL][OPEN-API] Ref: ' . $refId . ($customNote ? ' | ' . $customNote : '')),
                    'is_open_api'           => true,
                    'api_client_ref_id'     => $refId,
                    'open_api_callback_url' => $effectiveCallbackUrl,
                ]);

                ResellerApiClient::whereKey($client->id)->increment('total_orders');

                return [
                    'status'  => 'created',
                    'order'   => $order,
                    'balance' => (int) $lockedUser->fresh()->balance,
                ];
            });
        } catch (QueryException $qe) {
            $dupOrder = Order::where('user_id', $user->id)->where('api_client_ref_id', $refId)->first();
            if ($dupOrder) {
                return [
                    'http_code'         => 200,
                    'idempotent_replay' => true,
                    'message'           => 'Transaksi dengan ref_id tersebut sudah tercatat (Idempotent Response). Saldo tidak dipotong ulang.',
                    'order'             => $dupOrder,
                    'data'              => self::formatOrderForApi($dupOrder, (int) $user->fresh()->balance, true),
                ];
            }
            throw $qe;
        }

        if (($txOutcome['status'] ?? '') === 'idempotent') {
            $ord = $txOutcome['order'];
            return [
                'http_code'         => 200,
                'idempotent_replay' => true,
                'message'           => 'Transaksi dengan ref_id tersebut sudah tercatat (Idempotent Response). Saldo tidak dipotong ulang.',
                'order'             => $ord,
                'data'              => self::formatOrderForApi($ord, (int) $txOutcome['balance'], true),
            ];
        }

        if (($txOutcome['status'] ?? '') !== 'created' || empty($txOutcome['order'])) {
            return [
                'http_code'  => $txOutcome['http_code'] ?? 400,
                'error_code' => $txOutcome['error_code'] ?? 'ORDER_REJECTED',
                'message'    => $txOutcome['message'] ?? 'Gagal memproses pesanan Open API.',
                'meta'       => $txOutcome['meta'] ?? null,
            ];
        }

        /** @var Order $order */
        $order = $txOutcome['order'];

        // 4. Jalankan Fulfillment Otomatis (Lokal / Premku / Preflix)
        OrderFulfillmentService::fulfillOrder($order);
        $order->refresh();

        $freshBalance = (int) (User::whereKey($user->id)->value('balance') ?? 0);

        // Pastikan webhook terkirim jika order langsung selesai atau langsung auto-refund
        self::dispatchOrderWebhookIfNeeded($order);

        $normalizedStatus = self::normalizeApiOrderStatus($order);
        $msg = match ($normalizedStatus) {
            'completed' => 'Transaksi berhasil! Detail akun aplikasi premium PremiumKita siap digunakan.',
            'refunded', 'failed', 'canceled' => 'Transaksi gagal diproses (stok habis/gangguan sistem) dan saldo Anda telah di-refund 100% secara otomatis.',
            default => 'Transaksi berhasil diterima oleh sistem PremiumKita dan sedang diproses otomatis. Gunakan endpoint cek status atau tunggu Webhook callback.',
        };

        return [
            'http_code'         => 200,
            'idempotent_replay' => false,
            'message'           => $msg,
            'order'             => $order,
            'data'              => self::formatOrderForApi($order, $freshBalance, false),
        ];
    }

    public static function normalizeApiOrderStatus(Order $order): string
    {
        $raw = strtolower(trim((string) ($order->order_status ?: 'processing')));

        if (in_array($raw, ['completed', 'success', 'lunas'], true)) {
            return 'completed';
        }
        if ($raw === 'refunded') {
            return 'refunded';
        }
        if (in_array($raw, ['canceled', 'cancelled'], true)) {
            return 'canceled';
        }
        if (in_array($raw, ['failed', 'expired'], true)) {
            return 'failed';
        }

        return 'processing';
    }

    /**
     * Format data Order untuk respons Open API & payload Webhook.
     */
    public static function formatOrderForApi(Order $order, ?int $currentBalance = null, bool $idempotentReplay = false): array
    {
        $status = self::normalizeApiOrderStatus($order);
        $rawSn = trim((string) ($order->sn_or_account ?: $order->account_details ?: ''));
        $isReady = ($status === 'completed') && $rawSn !== '' && !OrderFulfillmentService::isWaitingText($rawSn);

        $data = [
            'invoice_number'    => (string) $order->invoice_number,
            'ref_id'            => $order->api_client_ref_id ? (string) $order->api_client_ref_id : null,
            'provider'          => 'PremiumKita',
            'product_id'        => $order->product_id ? (int) $order->product_id : null,
            'product_code'      => $order->product_id ? ('PK-APP-' . $order->product_id) : null,
            'product_name'      => (string) $order->product_name,
            'quantity'          => max(1, (int) ($order->quantity ?? 1)),
            'unit_price'        => (int) ($order->price ?? 0),
            'total_amount'      => (int) ($order->total_amount ?? $order->price ?? 0),
            'currency'          => 'IDR',
            'payment_method'    => 'balance',
            'payment_status'    => (string) ($order->payment_status ?: 'paid'),
            'order_status'      => $status,
            'is_ready'          => $isReady,
            'account_details'   => $isReady ? $rawSn : null,
            'status_note'       => !$isReady && $rawSn !== '' ? $rawSn : null,
            'idempotent_replay' => $idempotentReplay,
            'created_at'        => $order->created_at?->toIso8601String(),
            'updated_at'        => $order->updated_at?->toIso8601String(),
        ];

        if ($currentBalance !== null) {
            $data['balance_remaining'] = $currentBalance;
        }

        return $data;
    }

    /**
     * Kirim Webhook Callback otomatis bertanda tangan HMAC-SHA256 ke server Reseller
     * saat pesanan Open API berubah menjadi completed atau refunded/failed/canceled.
     */
    public static function dispatchOrderWebhookIfNeeded(?Order $order, ?string $forcedEvent = null): bool
    {
        if (!$order || !$order->is_open_api || empty($order->user_id)) {
            return false;
        }

        $cfg = self::getGlobalConfig();
        if (empty($cfg['webhook_enabled'])) {
            return false;
        }

        $status = self::normalizeApiOrderStatus($order);
        $event = $forcedEvent;
        if (!$event) {
            if ($status === 'completed') {
                $event = 'order.completed';
            } elseif (in_array($status, ['refunded', 'canceled', 'failed'], true)) {
                $event = 'order.refunded';
            } else {
                return false;
            }
        }

        if ($order->open_api_webhook_event === $event) {
            return false;
        }

        $client = ResellerApiClient::where('user_id', $order->user_id)->first();
        $targetUrl = $order->open_api_callback_url ?: ($client?->webhook_url ?: null);
        if (empty($targetUrl)) {
            return false;
        }

        $urlCheck = self::validateWebhookUrl($targetUrl);
        if (!$urlCheck['ok'] || empty($urlCheck['url'])) {
            return false;
        }

        $dedupKey = "open_api_wh_sent_{$order->id}_{$event}";
        if (!Cache::add($dedupKey, true, 86400)) {
            return false;
        }

        $freshBalance = (int) (User::whereKey($order->user_id)->value('balance') ?? 0);
        $timestamp = (string) time();
        $payloadArray = [
            'event'     => $event,
            'timestamp' => (int) $timestamp,
            'data'      => self::formatOrderForApi($order, $freshBalance, false),
        ];

        $jsonBody = json_encode($payloadArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($jsonBody)) {
            return false;
        }

        $signingSecret = ($client?->plainWebhookSecret()) ?: ($client?->plainSecretKey()) ?: '';
        $signature = hash_hmac('sha256', "{$timestamp}.{$jsonBody}", $signingSecret);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(5)
                ->withHeaders([
                    'Content-Type'    => 'application/json',
                    'Accept'          => 'application/json',
                    'User-Agent'      => 'PremiumKita-OpenAPI-Webhook/1.0',
                    'X-PK-Event'      => $event,
                    'X-PK-Timestamp'  => $timestamp,
                    'X-PK-Signature'  => 'sha256=' . $signature,
                ])
                ->withBody($jsonBody, 'application/json')
                ->post($urlCheck['url']);

            Order::whereKey($order->id)->update([
                'open_api_webhook_sent_at' => now(),
                'open_api_webhook_event'   => $event,
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Cache::forget($dedupKey);
            Log::warning("[OPEN-API-WEBHOOK-ERROR] Order {$order->invoice_number}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim test ping webhook ke server Reseller dari menu Konfigurasi API.
     */
    public static function sendTestWebhook(ResellerApiClient $client): array
    {
        if (empty($client->webhook_url)) {
            return [
                'ok'      => false,
                'message' => 'Silakan isi dan simpan Webhook URL terlebih dahulu sebelum melakukan uji coba.',
            ];
        }

        $urlCheck = self::validateWebhookUrl($client->webhook_url);
        if (!$urlCheck['ok']) {
            return [
                'ok'      => false,
                'message' => $urlCheck['message'],
            ];
        }

        $timestamp = (string) time();
        $payload = [
            'event'     => 'webhook.ping',
            'timestamp' => (int) $timestamp,
            'data'      => [
                'invoice_number'  => 'API-TESTPING-001',
                'ref_id'          => 'TEST-WEBHOOK-' . time(),
                'product_id'      => 1,
                'product_code'    => 'PK-APP-1',
                'product_name'    => 'Contoh Produk Aplikasi Premium (Test Ping)',
                'quantity'        => 1,
                'unit_price'      => 15000,
                'total_amount'    => 15000,
                'currency'        => 'IDR',
                'payment_method'  => 'balance',
                'payment_status'  => 'paid',
                'order_status'    => 'completed',
                'is_ready'        => true,
                'account_details' => "Email: demo@premiumkita.com\nPassword: DemoPassword123",
            ],
        ];

        $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signingSecret = $client->plainWebhookSecret() ?: ($client->plainSecretKey() ?: '');
        $signature = hash_hmac('sha256', "{$timestamp}.{$jsonBody}", $signingSecret);

        $started = microtime(true);
        try {
            $res = Http::connectTimeout(4)
                ->timeout(6)
                ->withHeaders([
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json',
                    'User-Agent'     => 'PremiumKita-OpenAPI-Webhook/1.0',
                    'X-PK-Event'     => 'webhook.ping',
                    'X-PK-Timestamp' => $timestamp,
                    'X-PK-Signature' => 'sha256=' . $signature,
                ])
                ->withBody($jsonBody, 'application/json')
                ->post($urlCheck['url']);

            $elapsedMs = (int) round((microtime(true) - $started) * 1000);

            if ($res->successful()) {
                return [
                    'ok'          => true,
                    'status_code' => $res->status(),
                    'elapsed_ms'  => $elapsedMs,
                    'message'     => "Webhook uji coba berhasil diterima server Anda (HTTP {$res->status()} dalam {$elapsedMs} ms).",
                ];
            }

            return [
                'ok'          => false,
                'status_code' => $res->status(),
                'elapsed_ms'  => $elapsedMs,
                'message'     => "Server Anda merespons dengan status HTTP {$res->status()} ({$elapsedMs} ms). Pastikan endpoint mengembalikan HTTP 200 OK.",
            ];
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'message' => 'Gagal menghubungi server Webhook Anda: ' . substr($e->getMessage(), 0, 160),
            ];
        }
    }

    /**
     * Catat audit log Open API dengan retensi ringan otomatis.
     */
    public static function recordApiLog(
        Request $request,
        int $statusCode,
        float $startedAt,
        ?int $userId = null,
        ?string $errorCode = null,
        ?string $summary = null,
        ?string $invoiceNumber = null
    ): void {
        try {
            self::ensureSchema();
            $elapsedMs = max(0, (int) round((microtime(true) - $startedAt) * 1000));
            $refId = $request->input('ref_id');

            ResellerApiLog::create([
                'user_id'          => $userId,
                'endpoint'         => '/' . ltrim($request->path(), '/'),
                'method'           => strtoupper($request->method()),
                'ip_address'       => substr((string) $request->ip(), 0, 64),
                'status_code'      => $statusCode,
                'ref_id'           => is_string($refId) ? substr(trim($refId), 0, 100) : null,
                'invoice_number'   => $invoiceNumber ? substr($invoiceNumber, 0, 100) : null,
                'error_code'       => $errorCode ? substr($errorCode, 0, 60) : null,
                'response_time_ms' => $elapsedMs,
                'summary'          => $summary ? substr($summary, 0, 255) : null,
                'created_at'       => now(),
            ]);

            // Pangkas log lama secara probabilistik agar tabel tetap ringan
            if (random_int(1, 50) === 1) {
                $cutoffId = ResellerApiLog::orderByDesc('id')->skip(2000)->value('id');
                if ($cutoffId) {
                    ResellerApiLog::where('id', '<=', $cutoffId)->delete();
                }
            }
        } catch (\Throwable $e) {
            // Jangan ganggu alur API jika logging gagal
        }
    }
}
