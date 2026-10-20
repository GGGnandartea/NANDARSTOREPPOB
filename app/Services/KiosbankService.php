<?php

namespace App\Services;

use App\Models\PpobProduct;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class KiosbankService
{
    protected string $host;
    protected string $port;
    protected string $protocol;
    protected string $username;
    protected string $password;
    protected string $mitra;
    protected string $accountId;
    protected string $merchantId;
    protected string $merchantName;
    protected string $counterId;
    protected string $mode;
    protected string $markupType;
    protected int $markupValue;
    protected bool $enabled;
    protected ?string $privateKey;
    protected ?string $publicKey;

    /**
     * Pemetaan prefix pulsa internal ke prefixID resmi Kiosbank
     * (dokumentasi Daftar Harga: AAE.* = PULSA).
     */
    public const PULSA_PREFIX_MAP = [
        '21' => 'AAE.AAAD', // Telkomsel
        '11' => 'AAE.AAAB', // Indosat
        '31' => 'AAE.AAAG', // XL
        '41' => 'AAE.AAAE', // Tri
        '51' => 'AAE.AAAA', // Axis
        '81' => 'AAE.AAAC', // Smartfren
    ];

    public function __construct(?string $mode = null)
    {
        $configuration = new KiosbankConfiguration();
        $this->mode = $mode ?? $configuration->mode();
        $profile = $configuration->profile($this->mode);
        foreach (['host', 'port', 'protocol', 'username', 'password', 'mitra'] as $field) {
            $this->{$field} = $profile[$field];
        }
        $this->accountId = $profile['account_id'];
        $this->merchantId = $profile['merchant_id'];
        $this->merchantName = $profile['merchant_name'];
        $this->counterId = $profile['counter_id'];
        $this->privateKey = $profile['private_key'] ?: null;
        $this->publicKey = $profile['public_key'] ?: null;
        $this->markupType = Setting::where('key', 'kiosbank_markup_type')->value('value') ?: 'fixed';
        $this->markupValue = (int) (Setting::where('key', 'kiosbank_markup_value')->value('value') ?? 1000);
        $this->enabled = Setting::where('key', 'kiosbank_enabled')->value('value') !== '0';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    protected function sessionCacheKey(): string
    {
        return 'kiosbank_session_id_'.hash('sha256', json_encode([
            $this->mode, $this->getBaseUrl(), $this->username, $this->password,
            $this->merchantId, $this->accountId, $this->counterId, $this->privateKey,
        ]));
    }

    protected function sessionExpiresAt(): \DateTimeInterface
    {
        return now('Asia/Jakarta')->endOfDay();
    }

    /**
     * Cek apakah mode Kiosbank adalah Developer Mode (Sandbox)
     */
    public function isDeveloperMode(): bool
    {
        return $this->mode === 'development';
    }

    /**
     * Dapatkan mode Kiosbank (development atau production)
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Cek apakah kredensial KIOSBANK sudah dikonfigurasi lengkap
     */
    public function isConfigured(): bool
    {
        return !empty($this->username) && !empty($this->password) && !empty($this->merchantId) && !empty($this->accountId);
    }

    /**
     * Hitung harga jual dengan markup default admin
     */
    public function applyMarkup(int $basePrice): int
    {
        if ($this->markupType === 'percent') {
            $margin = (int) round(($basePrice * $this->markupValue) / 100);
        } else {
            $margin = $this->markupValue;
        }

        return $basePrice + max(0, $margin);
    }

    /**
     * Base URL KIOSBANK
     */
    public function getBaseUrl(): string
    {
        $portStr = !empty($this->port) ? ':' . $this->port : '';
        return rtrim($this->protocol . '://' . $this->host . $portStr, '/');
    }

    /**
     * Request HTTP Digest Auth ke server KIOSBANK sesuai spesifikasi H2H
     */
    public function makeDigestRequest(string $path, array $params = [], string $method = 'POST'): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'Kredensial Kiosbank belum dikonfigurasi.'];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withDigestAuth($this->username, $this->password)
                ->acceptJson()->connectTimeout(8)->timeout(20)
                ->send($method, $this->getBaseUrl().$path, ['json' => $params]);

            if (!$response->successful() || !is_array($response->json())) {
                return ['status' => 'error', 'http_code' => $response->status(), 'message' => 'Respons Kiosbank tidak valid atau otentikasi ditolak.'];
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[KIOSBANK-CONNECTION] Tidak dapat terhubung ke '.$this->getBaseUrl());

            return ['status' => 'error', 'message' => 'Tidak dapat terhubung ke Kiosbank. Periksa koneksi dan whitelist IP server.'];
        }
    }

    /**
     * Parser header WWW-Authenticate Digest
     */
    protected function parseWwwAuthenticate(string $rawHeader): array
    {
        $parsed = [];
        if (preg_match('/WWW-Authenticate:\s*Digest\s+(.+?)(\r\n|\n|$)/i', $rawHeader, $matches)) {
            $line = $matches[1];
            preg_match_all('/([a-zA-Z0-9_\s]+)=["\']?([^"\',]+)["\']?/', $line, $items, PREG_SET_ORDER);
            foreach ($items as $item) {
                $k = trim($item[1]);
                $v = trim($item[2]);
                $parsed[$k] = $v;
            }
            if (isset($parsed['Digest realm']) && !isset($parsed['realm'])) {
                $parsed['realm'] = $parsed['Digest realm'];
            }
        }
        return $parsed;
    }

    /**
     * Generate RSA 2048-bit Key Pair (Private & Public Key)
     */
    public static function generateRsaKeyPair(): array
    {
        $config = [
            'digest_alg'       => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        // Path openssl.cnf di Windows / XAMPP jika env OPENSSL_CONF belum diset
        $possibleCnfPaths = [
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/conf/openssl.cnf',
            'C:/xampp/php/extras/openssl/openssl.cnf',
        ];
        foreach ($possibleCnfPaths as $cnf) {
            if (file_exists($cnf)) {
                $config['config'] = $cnf;
                break;
            }
        }

        $res = openssl_pkey_new($config);
        if (!$res) {
            return [
                'ok'      => false,
                'message' => 'Gagal membuat pasangan kunci RSA: ' . openssl_error_string()
            ];
        }

        $exportArgs = isset($config['config']) ? [null, ['config' => $config['config']]] : [];
        openssl_pkey_export($res, $privKey, ...$exportArgs);
        $details = openssl_pkey_get_details($res);
        $pubKey  = $details['key'] ?? '';

        return [
            'ok'          => true,
            'private_key' => trim($privKey),
            'public_key'  => trim($pubKey),
        ];
    }

    /**
     * Generate Signature SHA256withRSA sesuai standar Asymmetric KIOSBANK
     */
    public function generateRsaSignature(string $stringToSign): ?string
    {
        if (empty($this->privateKey)) {
            Log::warning("[KIOSBANK-RSA] Private Key RSA belum diisi di Pengaturan.");
            return null;
        }

        $privKeyResource = openssl_get_privatekey($this->privateKey);
        if (!$privKeyResource) {
            Log::error("[KIOSBANK-RSA] Format RSA Private Key tidak valid / korup.");
            return null;
        }

        $binarySignature = '';
        $success = openssl_sign($stringToSign, $binarySignature, $privKeyResource, OPENSSL_ALGO_SHA256);
        if (!$success) {
            Log::error("[KIOSBANK-RSA] openssl_sign gagal: " . openssl_error_string());
            return null;
        }

        return base64_encode($binarySignature);
    }

    /**
     * Sign-On menggunakan RSA Asymmetric Signature (Spesifikasi Resmi Kiosbank Terbaru)
     * POST /auth/Sign-On
     * Headers:
     *   Content-Type: application/json
     *   X-Merchantid: [merchantID]
     *   X-Date: [yyyy-MM-dd HH:mm:ss GMT+7]
     *   Authorization: [signature]
     * Body: minify(RequestBody)
     * stringToSign = user + minify(RequestBody) + password + X-Date
     */
    public function signOnRsa(bool $forceRefresh = false): array
    {
        $cacheKey = $this->sessionCacheKey();

        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (!empty($cached)) {
                return [
                    'ok'         => true,
                    'session_id' => $cached,
                    'is_cached'  => true,
                    'message'    => 'SessionID didapat dari cache aktif hari ini.'
                ];
            }
        }

        $body = [
            'mitra'        => $this->mitra ?: 'DJI',
            'accountID'    => $this->accountId ?: ($this->username ?: 'testAccount'),
            'merchantID'   => $this->merchantId,
            'merchantName' => $this->merchantName ?: 'PT.Testing',
            'counterID'    => (string) ($this->counterId ?: '1'),
        ];

        // Minify: JSON tanpa whitespace
        $minifiedBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        // X-Date: GMT+7 (Asia/Jakarta) format yyyy-MM-dd HH:mm:ss
        $nowWib = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
        $xDate = $nowWib->format('Y-m-d H:i:s');

        // stringToSign = user + minify(RequestBody) + password + X-Date
        $stringToSign = $this->username . $minifiedBody . $this->password . $xDate;

        $signature = $this->generateRsaSignature($stringToSign);
        if (!$signature) {
            return [
                'ok'             => false,
                'message'        => 'Gagal menghasilkan RSA Signature. Periksa kembali format RSA Private Key Anda.',
                'x_date'         => $xDate,
            ];
        }

        $url = $this->getBaseUrl() . '/auth/Sign-On';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $minifiedBody,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'X-Merchantid: ' . $this->merchantId,
                'X-Date: ' . $xDate,
                'Authorization: ' . $signature,
            ],
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::warning("[KIOSBANK-SIGNON-CURL-ERR] " . $err);
            return [
                'ok'      => false,
                'message' => 'Gagal koneksi ke server Kiosbank: ' . $err,
                'x_date'  => $xDate,
            ];
        }

        $data = json_decode($rawResponse, true);
        $sessionId = $data['SessionID'] ?? $data['sessionID'] ?? null;

        if ($sessionId) {
            // Cache sampai pukul 23:55 WIB hari ini
            $secondsUntilMidnight = $this->sessionExpiresAt();
            Cache::put($cacheKey, $sessionId, $secondsUntilMidnight);
            Log::info("[KIOSBANK-SIGNON-RSA-SUCCESS] Sesi aktif untuk mode ".$this->mode);

            return [
                'ok'         => true,
                'session_id' => $sessionId,
                'http_code'  => $httpCode,
                'message'    => "Sign-On Sukses! SessionID aktif terbit: {$sessionId}",
                'raw'        => $data,
                'x_date'     => $xDate,
            ];
        }

        Log::warning("[KIOSBANK-SIGNON-RSA-FAIL] HTTP {$httpCode} Res: " . substr($rawResponse, 0, 400));

        return [
            'ok'             => false,
            'http_code'      => $httpCode,
            'message'        => $data['description'] ?? ($data['message'] ?? 'Sign On gagal: ' . substr($rawResponse, 0, 200)),
            'raw'            => $data ?: $rawResponse,
            'x_date'         => $xDate,
        ];
    }

    /**
     * Uji Sign-On Live untuk Admin Diagnostic
     */
    public function testSignOn(): array
    {
        if (!empty($this->privateKey)) {
            $res = $this->signOnRsa(true);
            $res['method'] = 'RSA Asymmetric Signature (SHA256withRSA)';
            $res['mode']   = $this->mode;
            return $res;
        }

        // Jika RSA belum diisi, coba Digest Auth
        $params = [
            'mitra'        => $this->mitra ?: 'DJI',
            'accountID'    => $this->accountId ?: ($this->username ?: 'ujicoba'),
            'merchantID'   => $this->merchantId,
            'merchantName' => $this->merchantName,
            'counterID'    => $this->counterId ?: '1',
        ];
        $res = $this->makeDigestRequest('/auth/Sign-On', $params, 'POST');
        if (!empty($res['SessionID']) || !empty($res['sessionID'])) {
            return [
                'ok'         => true,
                'method'     => 'Digest Auth',
                'mode'       => $this->mode,
                'session_id' => $res['SessionID'] ?? $res['sessionID'],
                'message'    => 'Sign-On via Digest Auth berhasil!'
            ];
        }
        return [
            'ok'      => false,
            'method'  => 'Digest Auth',
            'mode'    => $this->mode,
            'message' => 'Sign-On gagal: ' . (is_string($res) ? $res : json_encode($res))
        ];
    }

    /**
     * Sign-On untuk mendapatkan SessionID baru dari KIOSBANK
     */
    public function signOn(bool $forceRefresh = false): ?string
    {
        $cacheKey = $this->sessionCacheKey();

        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (!empty($cached)) {
                return $cached;
            }
        }

        // 1. Prioritaskan RSA Asymmetric Signature jika Private Key telah diatur
        if (!empty($this->privateKey)) {
            $rsaResult = $this->signOnRsa($forceRefresh);
            if ($rsaResult['ok'] && !empty($rsaResult['session_id'])) {
                return $rsaResult['session_id'];
            }
        }

        // 2. Fallback: Coba Digest Auth jika RSA belum diisi atau server merespon Digest
        $params = [
            'mitra'        => $this->mitra ?: 'DJI',
            'accountID'    => $this->accountId ?: ($this->username ?: 'ujicoba'),
            'merchantID'   => $this->merchantId,
            'merchantName' => $this->merchantName,
            'counterID'    => $this->counterId ?: '1',
        ];

        $res = $this->makeDigestRequest('/auth/Sign-On', $params, 'POST');

        if (!empty($res['SessionID']) || !empty($res['sessionID'])) {
            $sessionId = $res['SessionID'] ?? $res['sessionID'];
            $secondsUntilMidnight = $this->sessionExpiresAt();
            Cache::put($cacheKey, $sessionId, $secondsUntilMidnight);
            Log::info("[KIOSBANK-SIGNON-SUCCESS] Sesi aktif untuk mode ".$this->mode);
            return $sessionId;
        }

        // 3. Jika developer mode dan API belum bisa dijangkau dari luar IP DJI, gunakan mock session
        if ($this->isDeveloperMode()) {
            $mockSession = 'DEV-SESSION-' . strtoupper(md5($this->username . date('Ymd')));
            Cache::put($cacheKey, $mockSession, 3600 * 12);
            return $mockSession;
        }

        Log::error("[KIOSBANK-SIGNON-FAILED] " . json_encode($res));
        return null;
    }

    /**
     * Ambil SessionID aktif
     */
    public function getSessionId(): ?string
    {
        return $this->signOn(false);
    }

    /**
     * Cek Saldo Deposit Mitra di KIOSBANK (/Services/getCurrentDeposit)
     * Sesuai dokumentasi resmi:
     * Request: { "sessionID": "...", "mitra": "DJI", "accountID": "ujicoba", "merchantID": "DJI000343", "counterID": "1" }
     * Response: { "rc": "00", "deposit": "11251850" }
     */
    public function checkDeposit(): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok'      => false,
                'saldo'   => 0,
                'message' => 'Kredensial Kiosbank belum diisi di Pengaturan Admin.'
            ];
        }

        $sessionId = $this->getSessionId();

        if ($sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
            $params = [
                'sessionID'  => $sessionId,
                'mitra'      => $this->mitra ?: 'DJI',
                'accountID'  => $this->accountId ?: ($this->username ?: 'ujicoba'),
                'merchantID' => $this->merchantId,
                'counterID'  => $this->counterId ?: '1',
            ];

            $res = $this->makeDigestRequest('/Services/getCurrentDeposit', $params, 'POST');

            if (isset($res['rc']) && $res['rc'] === '00') {
                $saldo = (int) ($res['deposit'] ?? $res['data']['saldo'] ?? $res['saldo'] ?? 0);
                return [
                    'ok'         => true,
                    'saldo'      => $saldo,
                    'mode'       => $this->mode,
                    'is_sandbox' => false,
                    'message'    => 'Saldo Deposit: Rp ' . number_format($saldo, 0, ',', '.')
                ];
            }
        }

        // Fallback untuk Developer Mode (Sandbox)
        if ($this->isDeveloperMode()) {
            $sandboxDeposit = (int) (Setting::where('key', 'kiosbank_sandbox_deposit')->value('value') ?? 11251850);
            return [
                'ok'             => true,
                'saldo'          => $sandboxDeposit,
                'mode'           => 'development',
                'is_sandbox'     => true,
                'message'        => 'Saldo (Dev Mode): Rp ' . number_format($sandboxDeposit, 0, ',', '.'),
                'sandbox_notice' => 'Berjalan di Developer Mode (Sandbox). Saldo dapat diuji top up dan transaksi secara virtual.'
            ];
        }

        return [
            'ok'      => false,
            'saldo'   => 0,
            'message' => 'Gagal terhubung ke Kiosbank atau IP belum di-whitelist.'
        ];
    }

    /**
     * Cek Status Produk Aktif & Maintenance (/Services/get-Active-Product)
     * Sesuai dokumentasi resmi:
     * Request: { "sessionID": "...", "mitra": "DJI", "accountID": "ujicoba", "merchantID": "DJI000343", "counterID": "1" }
     * Response: { "rc": "00", "active": "505051,505041,...", "maintenance": "770801,752061,..." }
     */
    public function getActiveProductStatus(): array
    {
        $sessionId = $this->getSessionId();

        if ($sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
            $params = [
                'sessionID'  => $sessionId,
                'mitra'      => $this->mitra ?: 'DJI',
                'accountID'  => $this->accountId ?: ($this->username ?: 'ujicoba'),
                'merchantID' => $this->merchantId,
                'counterID'  => $this->counterId ?: '1',
            ];

            $res = $this->makeDigestRequest('/Services/get-Active-Product', $params, 'POST');

            if (isset($res['rc']) && $res['rc'] === '00') {
                $activeList = array_filter(explode(',', trim($res['active'] ?? '', ',')));
                $maintList  = array_filter(explode(',', trim($res['maintenance'] ?? '', ',')));

                return [
                    'status'      => 'success',
                    'active'      => array_values($activeList),
                    'maintenance' => array_values($maintList),
                ];
            }
        }

        if (!$this->isDeveloperMode()) {
            return ['status' => 'error', 'rc' => '99', 'message' => 'Koneksi Kiosbank Production gagal. Transaksi belum dikonfirmasi.'];
        }

        // Mock response data dari dokumentasi resmi untuk Developer Mode
        return [
            'status'      => 'mock',
            'active'      => ['505051', '505041', '505031', '505021', '505011', '505001', '502581', '502551', '502541'],
            'maintenance' => ['770801', '752061', '751581', '206121', '6500AV', '751571'],
        ];
    }

    /**
     * Ambil daftar harga resmi dari API Kiosbank (/Services/getHargaByProductID).
     * Response: { rc: "00", record: [{ code, name, price }] }.
     * $prefixMap memetakan prefixID Kiosbank per prefix internal (misal 21 -> AAE.AAAD).
     */
    public function fetchOfficialPrices(string $prefixId): array
    {
        $sessionId = $this->getSessionId();
        if (!$this->isConfigured() || !$sessionId || str_starts_with($sessionId, 'DEV-SESSION-')) {
            return [];
        }

        $officialPrefix = self::PULSA_PREFIX_MAP[$prefixId] ?? $prefixId;
        $res = $this->makeDigestRequest('/Services/getHargaByProductID', [
            'sessionID'  => $sessionId,
            'merchantID' => $this->merchantId,
            'prefixID'   => $officialPrefix,
        ], 'POST');

        if (($res['rc'] ?? null) !== '00' || empty($res['record']) || !is_array($res['record'])) {
            return [];
        }

        $prices = [];
        foreach ($res['record'] as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $prices[$code] = [
                'name'  => trim((string) ($row['name'] ?? $code)),
                'price' => (int) ($row['price'] ?? 0),
            ];
        }

        return $prices;
    }

    /**
     * Terapkan harga resmi Kiosbank ke katalog lokal (base_price = harga resmi,
     * price = harga resmi + margin toko yang sudah diatur admin).
     */
    public function syncPricesFromKiosbank(?string $prefixId = null): array
    {
        $this->ensureCatalogSeeded();

        $targets = $prefixId
            ? [$prefixId]
            : PpobProduct::select('prefix_id')->distinct()->pluck('prefix_id')->filter()->values()->all();

        $updated = 0;
        $created = 0;
        $failedPrefixes = [];

        foreach ($targets as $targetPrefix) {
            try {
                $prices = $this->fetchOfficialPrices($targetPrefix);
            } catch (\Exception $e) {
                $failedPrefixes[] = $targetPrefix;
                continue;
            }

            if (empty($prices)) {
                $failedPrefixes[] = $targetPrefix;
                continue;
            }

            $template = PpobProduct::where('prefix_id', $targetPrefix)->first();
            foreach ($prices as $code => $info) {
                if ($info['price'] <= 0) {
                    continue;
                }
                $product = PpobProduct::where('code', $code)->first();
                if ($product) {
                    $margin = (int) ($product->margin ?? 0);
                    $price = $info['price'] + $margin;
                    $product->update([
                        'base_price' => $info['price'],
                        'price' => $price,
                        'reseller_price' => $product->reseller_price ? max(0, $price - 300) : null,
                    ]);
                    $updated++;
                } elseif ($template) {
                    $price = $this->applyMarkup($info['price']);
                    PpobProduct::create([
                        'code' => $code,
                        'name' => $info['name'],
                        'category' => $template->category,
                        'operator' => $template->operator,
                        'prefix_id' => $targetPrefix,
                        'base_price' => $info['price'],
                        'margin' => $price - $info['price'],
                        'price' => $price,
                        'reseller_price' => max(0, $price - 300),
                        'status' => 'active',
                        'provider' => 'kiosbank',
                    ]);
                    $created++;
                }
            }
        }

        return [
            'status' => 'success',
            'created_count' => $created,
            'updated_count' => $updated,
            'prefixes' => count($targets),
            'failed_prefixes' => $failedPrefixes,
        ];
    }

    /**
     * Ambil Daftar Pulsa Prabayar (Database First -> API / Catalog Fallback)
     */
    public function getPulsaPrabayar(string $prefixId): array
    {
        // Pastikan tabel PpobProduct sudah memiliki data
        $this->ensureCatalogSeeded();

        $operatorNames = [
            '21' => 'Telkomsel',
            '11' => 'Indosat',
            '31' => 'XL',
            '41' => 'Tri',
            '51' => 'Axis',
            '81' => 'Smartfren',
        ];
        $opName = $operatorNames[$prefixId] ?? null;

        $query = PpobProduct::where('category', 'pulsa');
        if ($opName) {
            $query->where('operator', $opName);
        } else {
            $query->where('prefix_id', $prefixId);
        }

        $dbProducts = $query->get();

        if ($dbProducts->isNotEmpty()) {
            return $dbProducts->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'code'            => $item->code,
                    'name'            => $item->name,
                    'operator'        => $item->operator,
                    'category'        => $item->category,
                    'base_price'      => (int) $item->base_price,
                    'margin'          => (int) $item->margin,
                    'price'           => (int) ($item->price ?: ($item->base_price + $item->margin)),
                    'reseller_price'  => $item->reseller_price ? (int)$item->reseller_price : null,
                    'status'          => $item->status,
                    'provider'        => 'kiosbank'
                ];
            })->toArray();
        }

        return $this->getFallbackPulsa($prefixId);
    }

    /**
     * Ambil Daftar Harga Produk Non-Pulsa (PLN, Game, E-Wallet, PDAM)
     */
    public function getHargaByPrefix(string $prefixId): array
    {
        $this->ensureCatalogSeeded();

        $dbProducts = PpobProduct::where('prefix_id', $prefixId)->get();

        if ($dbProducts->isEmpty()) {
            if ($prefixId === '100302') {
                $dbProducts = PpobProduct::where('category', 'pln')->get();
            } elseif (str_starts_with($prefixId, 'EWAL.')) {
                $walletName = str_replace('EWAL.', '', $prefixId);
                $dbProducts = PpobProduct::where('category', 'ewallet')->where('operator', 'LIKE', "%{$walletName}%")->get();
            } elseif (str_starts_with($prefixId, 'AAO.')) {
                $dbProducts = PpobProduct::where('category', 'game')->where('prefix_id', $prefixId)->get();
            } elseif (str_starts_with($prefixId, 'AAB.')) {
                $dbProducts = PpobProduct::where('category', 'streaming')->where('prefix_id', $prefixId)->get();
            } else {
                $dbProducts = PpobProduct::where('prefix_id', $prefixId)
                    ->orWhere(function($q) use ($prefixId) {
                        $q->where('category', 'streaming')->where('operator', 'LIKE', "%{$prefixId}%");
                    })
                    ->orWhere(function($q) use ($prefixId) {
                        $q->where('category', 'game')->where('operator', 'LIKE', "%{$prefixId}%");
                    })->get();
            }
        }

        if ($dbProducts->isNotEmpty()) {
            return $dbProducts->map(function ($item) {
                return [
                    'id'              => $item->id,
                    'code'            => $item->code,
                    'name'            => $item->name,
                    'operator'        => $item->operator,
                    'category'        => $item->category,
                    'base_price'      => (int) $item->base_price,
                    'margin'          => (int) $item->margin,
                    'price'           => (int) ($item->price ?: ($item->base_price + $item->margin)),
                    'reseller_price'  => $item->reseller_price ? (int)$item->reseller_price : null,
                    'status'          => $item->status,
                    'provider'        => 'kiosbank'
                ];
            })->toArray();
        }

        return $this->getFallbackByPrefix($prefixId);
    }

    /**
     * Inquiry Cek Tagihan / Cek ID Pelanggan.
     * Respons biller Kiosbank memiliki format berbeda (PDAM memakai tagihan,
     * PLN memakai TG/TT, dan BPJS memakai total), sehingga nominal dinormalisasi
     * di sini agar checkout tidak menampilkan Rp 0.
     */
    public function inquiry(string $productId, string $customerId, ?string $referenceId = null, ?string $additionalId = null, array $extraParams = []): array
    {
        // Validasi productID: hanya angka/huruf, maksimal 6 karakter (kode Kiosbank).
        // Mencegah penyalahgunaan productID arbitrer yang mengarah ke pembayaran salah.
        if (!preg_match('/^[A-Za-z0-9]{2,10}$/', $productId)) {
            return [
                'status'  => 'error',
                'rc'      => '99',
                'message' => 'Kode produk tidak valid.',
            ];
        }

        if (!preg_match('/^[0-9]{4,20}$/', $customerId)) {
            return [
                'status'  => 'error',
                'rc'      => '99',
                'message' => 'Nomor pelanggan harus berupa angka.',
            ];
        }

        $refId = $referenceId ?: $this->generate12DigitRef();

        if ($this->isConfigured()) {
            $sessionId = $this->getSessionId();
            if ($sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
                $params = [
                    'sessionID'   => $sessionId,
                    'merchantID'  => $this->merchantId,
                    'customerID'  => $customerId,
                    'productID'   => $productId,
                    'referenceID' => $refId,
                ];
                if (!empty($additionalId)) {
                    $params['additionalID'] = $additionalId;
                }
                foreach ($extraParams as $key => $value) {
                    if ($value !== null && $value !== '') {
                        $params[$key] = $value;
                    }
                }

                $res = $this->makeDigestRequest('/Services/Inquiry', $params, 'POST');

                if (isset($res['rc']) && $res['rc'] === '00') {
                    $data = $res['data'] ?? [];
                    $amount = $this->parseKiosbankAmount(
                        $data['tagihan'] ?? $data['TG'] ?? $data['harga'] ?? 0
                    );
                    $admin = $this->parseKiosbankAmount(
                        $data['admin'] ?? $data['adminBank'] ?? $data['AB'] ?? 0
                    );
                    $denda = $this->parseKiosbankAmount($data['denda'] ?? $data['DD'] ?? 0);
                    $total = $this->parseKiosbankAmount($data['total'] ?? $data['TT'] ?? 0);
                    $total = $total > 0 ? $total : ($amount + $admin + $denda);
                    $serviceFee = $this->resolveServiceFee($total);

                    return [
                        'status'        => 'success',
                        'rc'            => '00',
                        'product_id'    => $productId,
                        'customer_id'   => $data['customerID'] ?? $data['ID'] ?? $data['NR'] ?? $customerId,
                        'customer_name' => trim($data['nama'] ?? $data['NM'] ?? $data['customerName'] ?? 'Pelanggan Terverifikasi'),
                        'product_name'  => $data['produk'] ?? 'Layanan PPOB',
                        'amount'        => $amount,
                        'admin_fee'     => $admin,
                        'penalty'       => $denda,
                        'total'         => $total,
                        'service_fee'   => $serviceFee,
                        'grand_total'   => $total + $serviceFee,
                        'reference_id'  => $refId,
                        'data'          => $data,
                    ];
                }

                return [
                    'status'  => 'error',
                    'rc'      => $res['rc'] ?? '99',
                    'message' => $res['description'] ?? $this->getResponseCodeMessage($res['rc'] ?? '99'),
                ];
            }
        }

        if (!$this->isDeveloperMode()) {
            return ['status' => 'error', 'rc' => '99', 'message' => 'Koneksi Kiosbank Production gagal. Transaksi belum dikonfirmasi.'];
        }

        return [
            'status'        => 'success',
            'rc'            => '00',
            'product_id'    => $productId,
            'customer_id'   => $customerId,
            'customer_name' => 'PELANGGAN ' . strtoupper(substr($customerId, -4)),
            'product_name'  => 'Inquiry ' . $productId,
            'amount'        => 50000,
            'admin_fee'     => 2500,
            'penalty'       => 0,
            'total'         => 52500,
            'service_fee'   => $this->resolveServiceFee(52500),
            'grand_total'   => $this->applyMarkup(52500),
            'reference_id'  => $refId,
            'data'          => ['status' => 'INQUIRY_OK'],
        ];
    }

    /** Parse nominal Kiosbank yang dikirim sebagai zero-padded string. */
    protected function parseKiosbankAmount($value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        // Kiosbank memakai koma sebagai pemisah desimal: 000000091300 atau 000002440,00.
        if (str_contains($value, ',')) {
            $value = substr($value, 0, strpos($value, ','));
        }

        $normalized = preg_replace('/[^0-9-]/', '', $value);
        return $normalized !== '' ? (int) $normalized : 0;
    }

    /**
     * Potong markup admin menjadi biaya layanan terpisah agar total tagihan
     * yang dibayarkan ke biller tetap utuh.
     */
    protected function resolveServiceFee(int $billerTotal): int
    {
        return max(0, $this->applyMarkup($billerTotal) - $billerTotal);
    }

    /**
     * Payment biller (PDAM, PLN pascabayar/non-taglis, BPJS).
     * Inquiry harus dilakukan terlebih dahulu dan data nominalnya dikirim ulang.
     */
    public function payBill(string $productId, string $customerId, int $amount, int $admin, int $total, ?string $referenceId = null, ?string $phoneNumber = null): array
    {
        $refId = $referenceId ?: $this->generate12DigitRef();
        $sessionId = $this->getSessionId();
        $params = [
            'tglTransaksi' => now()->format('Y-m-d'),
            'sessionID'    => $sessionId,
            'merchantID'   => $this->merchantId,
            'productID'    => $productId,
            'customerID'   => $customerId,
            'referenceID'  => $refId,
            'tagihan'      => str_pad((string) $amount, 12, '0', STR_PAD_LEFT),
            'admin'        => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
            'total'        => str_pad((string) $total, 12, '0', STR_PAD_LEFT),
        ];
        if ($phoneNumber) {
            $params['noHanphone'] = $phoneNumber;
        }

        if ($this->isConfigured() && $sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
            $res = $this->makeDigestRequest('/Services/Payment', $params, 'POST');
            if (($res['rc'] ?? null) === '00') {
                $data = $res['data'] ?? [];
                return [
                    'status'       => in_array(strtoupper(trim($data['status'] ?? 'BERHASIL')), ['BERHASIL', 'SUKSES'], true) ? 'success' : 'processing',
                    'rc'           => '00',
                    'reference_id' => trim($data['noReferensi'] ?? $data['RF'] ?? $refId),
                    'sn'           => trim($data['token'] ?? $data['TK'] ?? $data['noReferensi'] ?? $data['RF'] ?? $refId),
                    'data'         => $data,
                ];
            }
            return [
                'status'  => 'failed',
                'rc'      => $res['rc'] ?? '99',
                'message' => $res['description'] ?? $this->getResponseCodeMessage($res['rc'] ?? '99'),
            ];
        }

        if (!$this->isDeveloperMode()) {
            return ['status' => 'error', 'rc' => '99', 'message' => 'Koneksi Kiosbank Production gagal. Transaksi belum dikonfirmasi.'];
        }

        return [
            'status'       => 'success',
            'rc'           => '00',
            'reference_id' => $refId,
            'sn'           => 'SANDBOX-' . strtoupper(bin2hex(random_bytes(6))),
            'data'         => ['status' => 'BERHASIL'],
            'is_sandbox'   => true,
        ];
    }

    /**
     * Single Payment untuk Pulsa, Token Listrik, Game, E-Money
     */
    public function singlePayment(string $productId, string $customerId, int $tagihan, int $admin = 0, ?int $total = null, ?string $referenceId = null): array
    {
        $refId = $referenceId ?: $this->generate12DigitRef();
        $totalNominal = $total ?: ($tagihan + $admin);

        if ($this->isConfigured()) {
            $sessionId = $this->getSessionId();
            if ($sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
                $params = [
                    'total'       => (string) $totalNominal,
                    'admin'       => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                    'tagihan'     => (string) $tagihan,
                    'sessionID'   => $sessionId,
                    'productID'   => $productId,
                    'referenceID' => $refId,
                    'merchantID'  => $this->merchantId,
                    'customerID'  => $customerId,
                ];

                $res = $this->makeDigestRequest('/Services/SinglePayment', $params, 'POST');

                Log::info("[KIOSBANK-PAYMENT-RESPONSE] " . json_encode($res));

                if (isset($res['rc']) && $res['rc'] === '00') {
                    $data = $res['data'] ?? [];
                    $status = strtoupper(trim($data['status'] ?? 'SEDANG DIPROSES'));

                    return [
                        'status'       => ($status === 'BERHASIL' || $status === 'SUKSES') ? 'success' : 'processing',
                        'rc'           => '00',
                        'reference_id' => $data['noReferensi'] ?? $refId,
                        'sn'           => $data['sn'] ?? $data['token'] ?? $data['noReferensi'] ?? $refId,
                        'product_name' => $data['nama'] ?? 'PPOB Kiosbank',
                        'raw_status'   => $status,
                        'data'         => $data
                    ];
                }

                // Jika dalam developer mode (sandbox), jika development server Kiosbank mengembalikan status bukan 00 (misal rc 17 gangguan / rc 15 produk uji coba), alihkan ke simulasi sandbox cerdas agar proses testing admin & user berjalan lancar
                if ($this->isDeveloperMode()) {
                    Log::info("[KIOSBANK-DEV-FALLBACK] Server dev Kiosbank merespon RC " . ($res['rc'] ?? '99') . " (" . ($res['description'] ?? '') . "). Mengaktifkan simulasi developer mode untuk pengujian sistem.");
                } else {
                    return [
                        'status'  => 'failed',
                        'rc'      => $res['rc'] ?? '99',
                        'message' => $res['description'] ?? $this->getResponseCodeMessage($res['rc'] ?? '99')
                    ];
                }
            }
        }

        if (!$this->isDeveloperMode()) {
            return ['status' => 'error', 'rc' => '99', 'message' => 'Koneksi Kiosbank Production gagal. Transaksi belum dikonfirmasi.'];
        }

        // Sandbox payment simulation untuk developer mode
        // Kurangi saldo sandbox jika cukup
        $currentSandbox = (int) (Setting::where('key', 'kiosbank_sandbox_deposit')->value('value') ?? 11251850);
        if ($currentSandbox < $totalNominal) {
            return ['status' => 'failed', 'rc' => '99', 'message' => 'Saldo simulasi Development tidak mencukupi.'];
        }
        $newSandbox = max(0, $currentSandbox - $totalNominal);
        Setting::updateOrCreate(['key' => 'kiosbank_sandbox_deposit'], ['value' => (string) $newSandbox]);

        $snMock = strtoupper(bin2hex(random_bytes(6))) . '-' . rand(1000, 9999);
        if (str_contains($productId, '100302') || str_contains($productId, 'PLN')) {
            // Format token PLN 20 digit
            $snMock = implode('-', str_split(str_pad((string) mt_rand(1000000000000000, 9999999999999999) . mt_rand(1000, 9999), 20, '0'), 4));
        }

        return [
            'status'       => 'success',
            'rc'           => '00',
            'reference_id' => $refId,
            'sn'           => $snMock,
            'product_name' => 'Layanan PPOB ' . $productId,
            'raw_status'   => 'BERHASIL',
            'is_sandbox'   => true,
            'data'         => [
                'idPelanggan' => $customerId,
                'nama'        => 'Pembelian Berhasil (Sandbox Dev Mode)',
                'noReferensi' => $refId,
                'harga'       => $totalNominal,
                'status'      => 'BERHASIL'
            ]
        ];
    }

    /**
     * Cek status transaksi pembayaran
     */
    public function checkStatus(string $productId, string $customerId, string $referenceId, int $tagihan, int $admin = 0, ?int $total = null, ?string $tglTransaksi = null): array
    {
        $sessionId = $this->getSessionId();
        $date = $tglTransaksi ?: now()->format('Y-m-d');
        $totalNominal = $total ?: ($tagihan + $admin);

        if ($this->isConfigured() && $sessionId && !str_starts_with($sessionId, 'DEV-SESSION-')) {
            $params = [
                'sessionID'    => $sessionId,
                'merchantID'   => $this->merchantId,
                'productID'    => $productId,
                'customerID'   => $customerId,
                'referenceID'  => $referenceId,
                'tagihan'      => str_pad((string) $tagihan, 12, '0', STR_PAD_LEFT),
                'admin'        => str_pad((string) $admin, 12, '0', STR_PAD_LEFT),
                'total'        => str_pad((string) $totalNominal, 12, '0', STR_PAD_LEFT),
                'tglTransaksi' => $date
            ];

            return $this->makeDigestRequest('/Services/Check-Status', $params, 'POST');
        }

        return ['status' => 'error', 'rc' => '99', 'message' => 'Status transaksi belum dapat dikonfirmasi ke Kiosbank.'];
    }

    /**
     * Generate 12-digit nomor referensi unik sesuai spesifikasi Kiosbank
     */
    protected function generate12DigitRef(): string
    {
        $prefix = now()->format('ymd'); // 6 digit
        $suffix = str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT); // 6 digit
        return $prefix . $suffix;
    }

    /**
     * Kamus pesan kode response (RC) resmi Kiosbank
     */
    public function getResponseCodeMessage(string $rc): string
    {
        $codes = [
            '00' => 'Transaksi Berhasil',
            '01' => 'Error tidak terdefinisi',
            '02' => 'Format message salah',
            '03' => 'Transaksi tidak direspon, silakan coba beberapa saat lagi',
            '04' => 'Tidak ada respon dari biller',
            '05' => 'Tidak ada respon dari host',
            '10' => 'Saat ini transaksi tidak dapat dilakukan',
            '12' => 'Saldo deposit tidak mencukupi',
            '14' => 'Kendala pada media penyimpanan provider',
            '15' => 'Produk tidak dikenal',
            '17' => 'Transaksi gagal',
            '18' => 'Switching hulu belum diregistrasi',
            '19' => 'Data tidak ditemukan',
            '27' => 'Transaksi tidak dapat dilakukan saat ini',
            '34' => 'Periode login berakhir, silakan login ulang',
            '37' => 'Kesalahan saat setting admin bank',
            '38' => 'Koneksi ke biller terputus',
            '39' => 'Timeout dari biller',
            '40' => 'Jenis message tidak dikenal',
            '41' => 'Anda tidak diperbolehkan bertransaksi produk ini',
            '42' => 'Harga jual tidak valid',
            '46' => 'Transaksi sudah kadaluwarsa',
            '60' => 'Silahkan membayar di kantor PDAM',
            '61' => 'Lakukan inquiry terlebih dahulu sebelum pembayaran',
            '62' => 'Tagihan baru belum tersedia',
            '64' => 'Tagihan sudah terbayar',
            '65' => 'Nomor yang Anda masukkan salah',
            '67' => 'Terjadi kegagalan saat proses transaksi',
            '68' => 'Pelanggan hanya boleh bertransaksi n-kali sehari',
            '69' => 'Nomor tidak diperbolehkan bertransaksi',
            '70' => 'Nominal pembayaran salah',
            '71' => 'Transaksi sedang diproses',
            '72' => 'Jumlah pembayaran melebihi batas maksimal',
            '73' => 'Gagal mengarahkan transaksi',
            '74' => 'Transaksi sudah lunas',
            '75' => 'Jeda pembelian dengan nominal sama minimal 10 menit',
            '78' => 'Nomor tujuan sudah kadaluwarsa',
            '79' => 'Jumlah tunggakan melebihi batas maksimal',
            '80' => 'Data tidak tersedia atau pelanggan sudah lunas',
            '83' => 'Nomor tujuan sedang diblokir',
            '85' => 'Sedang dalam masa cut-off sistem',
            '86' => 'Kode referensi sudah pernah digunakan',
        ];

        return $codes[$rc] ?? "Kode respon: {$rc}";
    }

    /**
     * Pastikan database katalog PPOB sudah terisi dengan data default resmi
     */
    public function ensureCatalogSeeded(): void
    {
        // Hanya return jika kategori streaming dan pulsa sudah ada di database
        if (PpobProduct::where('category', 'streaming')->exists() && PpobProduct::where('category', 'pulsa')->exists()) {
            return;
        }

        $items = [
            // Telkomsel (21)
            ['code' => '500521', 'name' => 'Telkomsel Prabayar 5.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 5650, 'margin' => 1000],
            ['code' => '501021', 'name' => 'Telkomsel Prabayar 10.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 10550, 'margin' => 1000],
            ['code' => '501521', 'name' => 'Telkomsel Prabayar 15.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 15250, 'margin' => 1000],
            ['code' => '502021', 'name' => 'Telkomsel Prabayar 20.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 20350, 'margin' => 1000],
            ['code' => '502521', 'name' => 'Telkomsel Prabayar 25.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 25250, 'margin' => 1000],
            ['code' => '505021', 'name' => 'Telkomsel Prabayar 50.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 49950, 'margin' => 1050],
            ['code' => '510021', 'name' => 'Telkomsel Prabayar 100.000', 'category' => 'pulsa', 'operator' => 'Telkomsel', 'prefix_id' => '21', 'base_price' => 98800, 'margin' => 1200],

            // Indosat (11)
            ['code' => '500511', 'name' => 'Indosat Prabayar 5.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 5750, 'margin' => 1000],
            ['code' => '501011', 'name' => 'Indosat Prabayar 10.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 10650, 'margin' => 1000],
            ['code' => '501511', 'name' => 'Indosat Prabayar 15.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 15300, 'margin' => 1000],
            ['code' => '502011', 'name' => 'Indosat Prabayar 20.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 20250, 'margin' => 1000],
            ['code' => '502511', 'name' => 'Indosat Prabayar 25.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 25150, 'margin' => 1000],
            ['code' => '505011', 'name' => 'Indosat Prabayar 50.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 49750, 'margin' => 1250],
            ['code' => '510011', 'name' => 'Indosat Prabayar 100.000', 'category' => 'pulsa', 'operator' => 'Indosat', 'prefix_id' => '11', 'base_price' => 98500, 'margin' => 1500],

            // XL (31)
            ['code' => '500531', 'name' => 'XL Prabayar 5.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 5800, 'margin' => 1000],
            ['code' => '501031', 'name' => 'XL Prabayar 10.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 10750, 'margin' => 1000],
            ['code' => '501531', 'name' => 'XL Prabayar 15.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 15350, 'margin' => 1000],
            ['code' => '502531', 'name' => 'XL Prabayar 25.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 25200, 'margin' => 1000],
            ['code' => '505031', 'name' => 'XL Prabayar 50.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 49800, 'margin' => 1200],
            ['code' => '510031', 'name' => 'XL Prabayar 100.000', 'category' => 'pulsa', 'operator' => 'XL', 'prefix_id' => '31', 'base_price' => 98700, 'margin' => 1300],

            // Tri (41)
            ['code' => '500541', 'name' => 'Tri Prabayar 5.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 5600, 'margin' => 1000],
            ['code' => '501041', 'name' => 'Tri Prabayar 10.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 10450, 'margin' => 1000],
            ['code' => '501541', 'name' => 'Tri Prabayar 15.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 15150, 'margin' => 1000],
            ['code' => '502041', 'name' => 'Tri Prabayar 20.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 20100, 'margin' => 1000],
            ['code' => '502541', 'name' => 'Tri Prabayar 25.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 25050, 'margin' => 1000],
            ['code' => '505041', 'name' => 'Tri Prabayar 50.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 49600, 'margin' => 1400],
            ['code' => '510041', 'name' => 'Tri Prabayar 100.000', 'category' => 'pulsa', 'operator' => 'Tri', 'prefix_id' => '41', 'base_price' => 98400, 'margin' => 1600],

            // Axis (51)
            ['code' => '500551', 'name' => 'Axis Prabayar 5.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 5750, 'margin' => 1000],
            ['code' => '501051', 'name' => 'Axis Prabayar 10.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 10650, 'margin' => 1000],
            ['code' => '501551', 'name' => 'Axis Prabayar 15.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 15250, 'margin' => 1000],
            ['code' => '502551', 'name' => 'Axis Prabayar 25.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 25150, 'margin' => 1000],
            ['code' => '505051', 'name' => 'Axis Prabayar 50.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 49750, 'margin' => 1250],
            ['code' => '510051', 'name' => 'Axis Prabayar 100.000', 'category' => 'pulsa', 'operator' => 'Axis', 'prefix_id' => '51', 'base_price' => 98600, 'margin' => 1400],

            // Smartfren (81)
            ['code' => '500581', 'name' => 'Smartfren 5.000', 'category' => 'pulsa', 'operator' => 'Smartfren', 'prefix_id' => '81', 'base_price' => 5500, 'margin' => 1000],
            ['code' => '501081', 'name' => 'Smartfren 10.000', 'category' => 'pulsa', 'operator' => 'Smartfren', 'prefix_id' => '81', 'base_price' => 10350, 'margin' => 1000],
            ['code' => '502081', 'name' => 'Smartfren 20.000', 'category' => 'pulsa', 'operator' => 'Smartfren', 'prefix_id' => '81', 'base_price' => 20100, 'margin' => 1000],
            ['code' => '505081', 'name' => 'Smartfren 50.000', 'category' => 'pulsa', 'operator' => 'Smartfren', 'prefix_id' => '81', 'base_price' => 49500, 'margin' => 1500],
            ['code' => '510081', 'name' => 'Smartfren 100.000', 'category' => 'pulsa', 'operator' => 'Smartfren', 'prefix_id' => '81', 'base_price' => 98000, 'margin' => 2000],

            // Listrik PLN Prepaid (100302)
            ['code' => 'PLN20', 'name' => 'Token Listrik PLN 20.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 20500, 'margin' => 1500],
            ['code' => 'PLN50', 'name' => 'Token Listrik PLN 50.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 50500, 'margin' => 2000],
            ['code' => 'PLN100', 'name' => 'Token Listrik PLN 100.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 100500, 'margin' => 2500],
            ['code' => 'PLN200', 'name' => 'Token Listrik PLN 200.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 200500, 'margin' => 3500],
            ['code' => 'PLN500', 'name' => 'Token Listrik PLN 500.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 500500, 'margin' => 5000],
            ['code' => 'PLN1000', 'name' => 'Token Listrik PLN 1.000.000', 'category' => 'pln', 'operator' => 'PLN', 'prefix_id' => '100302', 'base_price' => 1000500, 'margin' => 7500],

            // Honor of Kings (AAO.AAAJ) - Dokumen Resmi Halaman 20
            ['code' => '6001BR', 'name' => 'Honor of Kings 8 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 1644, 'margin' => 500],
            ['code' => '6002BR', 'name' => 'Honor of Kings 16 + 1 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 2825, 'margin' => 500],
            ['code' => '6003BR', 'name' => 'Honor of Kings 25 + 2 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 3834, 'margin' => 600],
            ['code' => '6004BR', 'name' => 'Honor of Kings 80 + 8 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 12842, 'margin' => 1000],
            ['code' => '6005BR', 'name' => 'Honor of Kings Weekly Card', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 12174, 'margin' => 1000],
            ['code' => '6006BR', 'name' => 'Honor of Kings 240 + 17 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 44561, 'margin' => 2000],
            ['code' => '6007BR', 'name' => 'Honor of Kings Weekly Card Plus', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 40251, 'margin' => 2000],
            ['code' => '6008BR', 'name' => 'Honor of Kings 400 + 32 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 68112, 'margin' => 2500],
            ['code' => '6009BR', 'name' => 'Honor of Kings 560 + 45 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 103747, 'margin' => 3500],
            ['code' => '6010BR', 'name' => 'Honor of Kings 800 + 95 Tokens', 'category' => 'game', 'operator' => 'Honor of Kings', 'prefix_id' => 'AAO.AAAJ', 'base_price' => 119625, 'margin' => 4000],

            // Mobile Legends (AAO.AAAL)
            ['code' => '6701BE', 'name' => 'Mobile Legends 11 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 3500, 'margin' => 500],
            ['code' => '6702BE', 'name' => 'Mobile Legends 36 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 11156, 'margin' => 1000],
            ['code' => '6703BE', 'name' => 'Mobile Legends 86 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 22800, 'margin' => 1200],
            ['code' => '6704BE', 'name' => 'Mobile Legends 172 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 45500, 'margin' => 2000],
            ['code' => '6705BE', 'name' => 'Mobile Legends 257 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 68000, 'margin' => 2500],
            ['code' => '6706BE', 'name' => 'Mobile Legends 706 Diamonds', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 179000, 'margin' => 5000],
            ['code' => '6707BE', 'name' => 'Mobile Legends Weekly Diamond Pass', 'category' => 'game', 'operator' => 'Mobile Legends', 'prefix_id' => 'AAO.AAAL', 'base_price' => 27500, 'margin' => 1500],

            // Free Fire (AAO.AAAF)
            ['code' => 'FF50', 'name' => 'Free Fire 50 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 7100, 'margin' => 700],
            ['code' => 'FF70', 'name' => 'Free Fire 70 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 9700, 'margin' => 800],
            ['code' => 'FF140', 'name' => 'Free Fire 140 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 19200, 'margin' => 1000],
            ['code' => 'FF355', 'name' => 'Free Fire 355 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 47500, 'margin' => 2000],
            ['code' => 'FF720', 'name' => 'Free Fire 720 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 94500, 'margin' => 3500],
            ['code' => 'FF1450', 'name' => 'Free Fire 1450 Diamonds', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 189000, 'margin' => 5000],
            ['code' => 'FFWEEK', 'name' => 'Free Fire Membership Mingguan', 'category' => 'game', 'operator' => 'Free Fire', 'prefix_id' => 'AAO.AAAF', 'base_price' => 29000, 'margin' => 1500],

            // Genshin Impact (AAO.AAAG)
            ['code' => 'GI60', 'name' => 'Genshin 60 Genesis Crystals', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 16000, 'margin' => 1000],
            ['code' => 'GI330', 'name' => 'Genshin 300+30 Genesis Crystals', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 78000, 'margin' => 2500],
            ['code' => 'GI1090', 'name' => 'Genshin 980+110 Genesis Crystals', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 245000, 'margin' => 5000],
            ['code' => 'GIWELK', 'name' => 'Genshin Blessing of the Welkin Moon', 'category' => 'game', 'operator' => 'Genshin Impact', 'prefix_id' => 'AAO.AAAG', 'base_price' => 78500, 'margin' => 2500],

            // DANA (EWAL.DANA)
            ['code' => 'DANA10', 'name' => 'Saldo DANA Rp 10.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 10600, 'margin' => 900],
            ['code' => 'DANA20', 'name' => 'Saldo DANA Rp 20.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 20600, 'margin' => 900],
            ['code' => 'DANA25', 'name' => 'Saldo DANA Rp 25.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 25600, 'margin' => 900],
            ['code' => 'DANA50', 'name' => 'Saldo DANA Rp 50.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 50600, 'margin' => 1200],
            ['code' => 'DANA100', 'name' => 'Saldo DANA Rp 100.000', 'category' => 'ewallet', 'operator' => 'DANA', 'prefix_id' => 'EWAL.DANA', 'base_price' => 100600, 'margin' => 1400],

            // GoPay (EWAL.GOPAY)
            ['code' => 'GOPAY10', 'name' => 'Saldo GoPay Rp 10.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 10700, 'margin' => 800],
            ['code' => 'GOPAY20', 'name' => 'Saldo GoPay Rp 20.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 20700, 'margin' => 800],
            ['code' => 'GOPAY50', 'name' => 'Saldo GoPay Rp 50.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 50700, 'margin' => 1100],
            ['code' => 'GOPAY100', 'name' => 'Saldo GoPay Rp 100.000', 'category' => 'ewallet', 'operator' => 'GoPay', 'prefix_id' => 'EWAL.GOPAY', 'base_price' => 100700, 'margin' => 1300],

            // OVO (EWAL.OVO)
            ['code' => 'OVO10', 'name' => 'Saldo OVO Rp 10.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 10800, 'margin' => 700],
            ['code' => 'OVO20', 'name' => 'Saldo OVO Rp 20.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 20800, 'margin' => 700],
            ['code' => 'OVO50', 'name' => 'Saldo OVO Rp 50.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 50800, 'margin' => 1000],
            ['code' => 'OVO100', 'name' => 'Saldo OVO Rp 100.000', 'category' => 'ewallet', 'operator' => 'OVO', 'prefix_id' => 'EWAL.OVO', 'base_price' => 100800, 'margin' => 1200],

            // ShopeePay (EWAL.SHOPEE)
            ['code' => 'SPAY10', 'name' => 'Saldo ShopeePay Rp 10.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 10600, 'margin' => 900],
            ['code' => 'SPAY20', 'name' => 'Saldo ShopeePay Rp 20.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 20600, 'margin' => 900],
            ['code' => 'SPAY50', 'name' => 'Saldo ShopeePay Rp 50.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 50600, 'margin' => 1200],
            ['code' => 'SPAY100', 'name' => 'Saldo ShopeePay Rp 100.000', 'category' => 'ewallet', 'operator' => 'ShopeePay', 'prefix_id' => 'EWAL.SHOPEE', 'base_price' => 100600, 'margin' => 1400],
        ];

        foreach ($items as $item) {
            $price = $item['base_price'] + $item['margin'];
            PpobProduct::firstOrCreate(
                ['code' => $item['code']],
                [
                    'name'           => $item['name'],
                    'category'       => $item['category'],
                    'operator'       => $item['operator'],
                    'prefix_id'      => $item['prefix_id'],
                    'base_price'     => $item['base_price'],
                    'margin'         => $item['margin'],
                    'price'          => $price,
                    'reseller_price' => $price - 300,
                    'status'         => 'active',
                    'provider'       => 'kiosbank',
                ]
            );
        }
    }

    /**
     * Sinkronkan produk dengan API Kiosbank dan sesuaikan status active / maintenance
     */
    public function syncWithKiosbankApi(): array
    {
        $this->ensureCatalogSeeded();

        $activeStatus = $this->getActiveProductStatus();
        if (($activeStatus['status'] ?? '') !== 'success') {
            throw new \RuntimeException($activeStatus['message'] ?? 'Status produk belum tersedia dari Kiosbank.');
        }
        $maintenanceCodes = $activeStatus['maintenance'] ?? [];
        $activeCodes      = $activeStatus['active'] ?? [];

        $updatedCount = 0;

        if (!empty($maintenanceCodes)) {
            $updatedCount += PpobProduct::whereIn('code', $maintenanceCodes)->where('status', '!=', 'inactive')->update(['status' => 'maintenance']);
        }

        if (!empty($activeCodes)) {
            $updatedCount += PpobProduct::whereIn('code', $activeCodes)->where('status', '!=', 'inactive')->update(['status' => 'active']);
        }

        return [
            'status'            => 'success',
            'updated_count'     => $updatedCount,
            'active_count'      => count($activeCodes),
            'maintenance_count' => count($maintenanceCodes),
            'total_products'    => PpobProduct::count()
        ];
    }

    /**
     * Fallback Data Katalog Pulsa Kiosbank
     */
    protected function getFallbackPulsa(string $prefixId): array
    {
        $this->ensureCatalogSeeded();
        return PpobProduct::where('category', 'pulsa')->where('prefix_id', $prefixId)->get()->toArray();
    }

    /**
     * Fallback Data Katalog Non-Pulsa
     */
    protected function getFallbackByPrefix(string $prefixId): array
    {
        $this->ensureCatalogSeeded();
        return PpobProduct::where('prefix_id', $prefixId)->get()->toArray();
    }
}
