<?php

namespace App\Http\Middleware;

use App\Models\ResellerApiClient;
use App\Services\ResellerOpenApiService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateResellerOpenApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $requestId = 'req_' . bin2hex(random_bytes(8));

        // Paksa seluruh respons route Open API berformat JSON murni
        $request->headers->set('Accept', 'application/json');

        ResellerOpenApiService::ensureSchema();
        $cfg = ResellerOpenApiService::getGlobalConfig();
        $clientIp = (string) ($request->ip() ?: '0.0.0.0');

        // 1. Cek Master Switch Global Open API
        if (empty($cfg['enabled'])) {
            return $this->reject(
                $request,
                503,
                'API_MAINTENANCE',
                $cfg['maintenance_message'],
                $startedAt,
                $requestId
            );
        }

        // 2. Proteksi Brute-Force Kegagalan Autentikasi per IP (Maks 20 gagal / 60 detik)
        $bruteForceKey = 'open_api_auth_fail:' . $clientIp;
        if (RateLimiter::tooManyAttempts($bruteForceKey, 20)) {
            $retryAfter = RateLimiter::availableIn($bruteForceKey);
            return $this->reject(
                $request,
                429,
                'AUTH_BRUTE_FORCE_LOCKED',
                "Terlalu banyak percobaan autentikasi gagal dari IP {$clientIp}. Coba lagi dalam {$retryAfter} detik.",
                $startedAt,
                $requestId,
                null,
                ['Retry-After' => (string) $retryAfter]
            );
        }

        // 3. Validasi Keberadaan X-API-Key
        $apiKey = trim((string) $request->header('X-API-Key', ''));
        if ($apiKey === '') {
            RateLimiter::hit($bruteForceKey, 60);
            return $this->reject(
                $request,
                401,
                'MISSING_API_KEY',
                'Header X-API-Key wajib disertakan pada setiap permintaan Open API.',
                $startedAt,
                $requestId
            );
        }

        $client = ResellerApiClient::with('user')->where('api_key', $apiKey)->first();
        if (!$client) {
            RateLimiter::hit($bruteForceKey, 60);
            return $this->reject(
                $request,
                401,
                'INVALID_API_KEY',
                'API Key tidak valid atau sudah dicabut.',
                $startedAt,
                $requestId
            );
        }

        $user = $client->user;

        // 4. Pembatasan Ketat: HANYA VIP RESELLER AKTIF (User biasa & Admin ditolak)
        if (!$user || $user->role !== 'reseller') {
            return $this->reject(
                $request,
                403,
                'RESELLER_ONLY',
                'Akses ditolak. Layanan Open API eksklusif hanya untuk mitra VIP Reseller.',
                $startedAt,
                $requestId,
                $user?->id
            );
        }

        if (!$user->is_active) {
            return $this->reject(
                $request,
                403,
                'ACCOUNT_INACTIVE',
                'Akun Reseller Anda sedang tidak aktif. Silakan hubungi Admin.',
                $startedAt,
                $requestId,
                $user->id
            );
        }

        if ($client->admin_suspended) {
            $reason = $client->admin_suspend_reason ? (' Alasan: ' . $client->admin_suspend_reason) : '';
            return $this->reject(
                $request,
                403,
                'API_SUSPENDED_BY_ADMIN',
                'Akses Open API akun Anda sedang ditangguhkan oleh Admin.' . $reason,
                $startedAt,
                $requestId,
                $user->id
            );
        }

        if (!$client->is_enabled) {
            return $this->reject(
                $request,
                403,
                'API_CLIENT_DISABLED',
                'Koneksi Open API sedang dinonaktifkan pada pengaturan akun Anda.',
                $startedAt,
                $requestId,
                $user->id
            );
        }

        // 5. Validasi IP Whitelist (Mendukung IP Tunggal & CIDR)
        $whitelist = $client->parsedIpWhitelist();
        if (!empty($cfg['require_ip_whitelist']) && empty($whitelist)) {
            return $this->reject(
                $request,
                403,
                'IP_WHITELIST_REQUIRED',
                'Kebijakan keamanan Open API mewajibkan Anda mendaftarkan IP Server pada menu IP Whitelist sebelum bertransaksi.',
                $startedAt,
                $requestId,
                $user->id
            );
        }

        if (!empty($whitelist) && !ResellerOpenApiService::ipMatchesWhitelist($clientIp, $whitelist)) {
            return $this->reject(
                $request,
                403,
                'IP_NOT_WHITELISTED',
                "Alamat IP server Anda ({$clientIp}) tidak terdaftar pada IP Whitelist akun Reseller.",
                $startedAt,
                $requestId,
                $user->id
            );
        }

        // 6. Verifikasi Kriptografi: HMAC-SHA256 + Timestamp Anti-Replay (atau Mode Auth Admin)
        $plainSecret = $client->plainSecretKey();
        if (empty($plainSecret)) {
            return $this->reject(
                $request,
                500,
                'CREDENTIAL_CORRUPTED',
                'Secret Key tidak dapat diverifikasi. Silakan lakukan Rotate API Key di menu profil Reseller.',
                $startedAt,
                $requestId,
                $user->id
            );
        }

        $authMode = $cfg['auth_mode'] ?? 'hmac_strict';
        $isOrderMutation = $request->isMethod('POST') && trim($request->path(), '/') === 'api/v1/orders';
        $requireHmac = ($authMode === 'hmac_strict') || ($authMode === 'hmac_order_only' && $isOrderMutation);

        $providedSignature = trim((string) $request->header('X-Signature', ''));
        $providedTimestamp = trim((string) $request->header('X-Timestamp', ''));
        $providedSecretHeader = trim((string) $request->header('X-API-Secret', ''));

        if ($requireHmac || $providedSignature !== '') {
            if ($providedTimestamp === '' || !ctype_digit($providedTimestamp)) {
                RateLimiter::hit($bruteForceKey, 60);
                return $this->reject(
                    $request,
                    401,
                    'MISSING_TIMESTAMP',
                    'Header X-Timestamp (Unix epoch dalam detik) wajib disertakan untuk verifikasi tanda tangan HMAC-SHA256.',
                    $startedAt,
                    $requestId,
                    $user->id
                );
            }

            $tolerance = (int) ($cfg['timestamp_tolerance'] ?? 300);
            $tsInt = (int) $providedTimestamp;
            if (abs(time() - $tsInt) > $tolerance) {
                return $this->reject(
                    $request,
                    401,
                    'EXPIRED_TIMESTAMP',
                    "X-Timestamp berada di luar batas toleransi keamanan (±{$tolerance} detik dari waktu server).",
                    $startedAt,
                    $requestId,
                    $user->id
                );
            }

            if ($providedSignature === '') {
                RateLimiter::hit($bruteForceKey, 60);
                return $this->reject(
                    $request,
                    401,
                    'MISSING_SIGNATURE',
                    'Header X-Signature (HMAC-SHA256) wajib disertakan.',
                    $startedAt,
                    $requestId,
                    $user->id
                );
            }

            $cleanProvidedSig = strtolower(str_starts_with(strtolower($providedSignature), 'sha256=')
                ? substr($providedSignature, 7)
                : $providedSignature);

            $expectedSig = ResellerOpenApiService::computeHmacSignature(
                $request->method(),
                '/' . ltrim($request->path(), '/'),
                $providedTimestamp,
                (string) $request->getContent(),
                $plainSecret
            );

            if (!hash_equals($expectedSig, $cleanProvidedSig)) {
                RateLimiter::hit($bruteForceKey, 60);
                return $this->reject(
                    $request,
                    401,
                    'INVALID_SIGNATURE',
                    'Verifikasi X-Signature HMAC-SHA256 gagal. Pastikan urutan METHOD, PATH, TIMESTAMP, dan SHA256(RAW_BODY) sesuai dokumentasi.',
                    $startedAt,
                    $requestId,
                    $user->id
                );
            }

            // Anti-Replay Nonce opsional jika klien mengirimkan X-Nonce
            $nonce = trim((string) $request->header('X-Nonce', ''));
            if ($nonce !== '') {
                if (strlen($nonce) > 128 || !preg_match('/^[A-Za-z0-9\-_.:]+$/', $nonce)) {
                    return $this->reject(
                        $request,
                        400,
                        'INVALID_NONCE',
                        'Format header X-Nonce tidak valid (maks 128 karakter alfanumerik).',
                        $startedAt,
                        $requestId,
                        $user->id
                    );
                }

                $nonceCacheKey = "open_api_nonce:{$client->id}:" . hash('sha256', $nonce);
                if (!Cache::add($nonceCacheKey, 1, max(60, $tolerance * 2))) {
                    return $this->reject(
                        $request,
                        401,
                        'REPLAY_DETECTED',
                        'Permintaan duplikat terdeteksi (X-Nonce sudah pernah digunakan).',
                        $startedAt,
                        $requestId,
                        $user->id
                    );
                }
            }
        } else {
            // Mode relaksasi (X-API-Secret)
            if ($providedSecretHeader === '' || !hash_equals($plainSecret, $providedSecretHeader)) {
                RateLimiter::hit($bruteForceKey, 60);
                return $this->reject(
                    $request,
                    401,
                    'INVALID_API_SECRET',
                    'Header X-API-Secret atau X-Signature tidak valid.',
                    $startedAt,
                    $requestId,
                    $user->id
                );
            }
        }

        // 7. Rate Limiting per Reseller Client (General RPM & Order RPM)
        $generalRpm = (int) ($client->custom_rate_limit ?: ($cfg['rate_limit_rpm'] ?? 60));
        $generalBucket = 'open_api_rpm:' . $client->id;
        if (RateLimiter::tooManyAttempts($generalBucket, $generalRpm)) {
            $retryAfter = RateLimiter::availableIn($generalBucket);
            return $this->reject(
                $request,
                429,
                'RATE_LIMIT_EXCEEDED',
                "Batas frekuensi request API ({$generalRpm} req/menit) terlampaui. Coba lagi dalam {$retryAfter} detik.",
                $startedAt,
                $requestId,
                $user->id,
                [
                    'Retry-After'           => (string) $retryAfter,
                    'X-RateLimit-Limit'     => (string) $generalRpm,
                    'X-RateLimit-Remaining' => '0',
                ]
            );
        }
        RateLimiter::hit($generalBucket, 60);

        if ($isOrderMutation) {
            $orderRpm = min($generalRpm, (int) ($cfg['order_rate_limit_rpm'] ?? 20));
            $orderBucket = 'open_api_order_rpm:' . $client->id;
            if (RateLimiter::tooManyAttempts($orderBucket, $orderRpm)) {
                $retryAfter = RateLimiter::availableIn($orderBucket);
                return $this->reject(
                    $request,
                    429,
                    'ORDER_RATE_LIMIT_EXCEEDED',
                    "Batas frekuensi pembuatan pesanan API ({$orderRpm} order/menit) terlampaui. Coba lagi dalam {$retryAfter} detik.",
                    $startedAt,
                    $requestId,
                    $user->id,
                    [
                        'Retry-After'           => (string) $retryAfter,
                        'X-RateLimit-Limit'     => (string) $orderRpm,
                        'X-RateLimit-Remaining' => '0',
                    ]
                );
            }
            RateLimiter::hit($orderBucket, 60);
        }

        $remaining = max(0, RateLimiter::remaining($generalBucket, $generalRpm));

        // Update statistik pemakaian klien secara efisien
        try {
            ResellerApiClient::whereKey($client->id)->update([
                'last_used_at'   => now(),
                'last_used_ip'   => substr($clientIp, 0, 64),
                'total_requests' => ($client->total_requests ?? 0) + 1,
            ]);
        } catch (\Throwable $e) {}

        $request->attributes->set('reseller_user', $user);
        $request->attributes->set('reseller_api_client', $client);
        $request->attributes->set('open_api_request_id', $requestId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);
        $response->headers->set('X-RateLimit-Limit', (string) $generalRpm);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('Cache-Control', 'no-store, private');

        // Ekstrak ringkasan hasil untuk log audit
        $errorCode = null;
        $summary = null;
        $invoiceNumber = null;
        $rawRespContent = $response->getContent();
        if (is_string($rawRespContent) && str_starts_with(ltrim($rawRespContent), '{')) {
            $decoded = json_decode($rawRespContent, true);
            if (is_array($decoded)) {
                $errorCode = isset($decoded['error_code']) ? (string) $decoded['error_code'] : null;
                $summary = isset($decoded['message']) ? (string) $decoded['message'] : null;
                $invoiceNumber = $decoded['data']['invoice_number'] ?? null;
            }
        }

        ResellerOpenApiService::recordApiLog(
            $request,
            $response->getStatusCode(),
            $startedAt,
            $user->id,
            $errorCode,
            $summary,
            is_string($invoiceNumber) ? $invoiceNumber : null
        );

        return $response;
    }

    protected function reject(
        Request $request,
        int $httpStatus,
        string $errorCode,
        string $message,
        float $startedAt,
        string $requestId,
        ?int $userId = null,
        array $extraHeaders = []
    ): Response {
        ResellerOpenApiService::recordApiLog(
            $request,
            $httpStatus,
            $startedAt,
            $userId,
            $errorCode,
            $message
        );

        $headers = array_merge([
            'X-Request-Id'  => $requestId,
            'Cache-Control' => 'no-store, private',
        ], $extraHeaders);

        return response()->json([
            'success'    => false,
            'error_code' => $errorCode,
            'message'    => $message,
            'request_id' => $requestId,
        ], $httpStatus, $headers);
    }
}
