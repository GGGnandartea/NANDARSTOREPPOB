<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontController;
use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerApiClient;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use App\Services\ResellerOpenApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResellerOpenApiController extends Controller
{
    /**
     * GET /api/v1/ping
     * Cek konektivitas dan validitas autentikasi Open API.
     */
    public function ping(Request $request): JsonResponse
    {
        $cfg = ResellerOpenApiService::getGlobalConfig();

        return response()->json([
            'success'     => true,
            'message'     => 'Koneksi Open API PremiumKita aktif dan terautentikasi.',
            'data'        => [
                'brand'       => 'PremiumKita',
                'service'     => 'PremiumKita Open API',
                'version'     => 'v1',
                'auth_mode'   => $cfg['auth_mode'],
                'server_time' => time(),
                'iso_time'    => now()->toIso8601String(),
            ],
            'request_id'  => $request->attributes->get('open_api_request_id'),
        ]);
    }

    /**
     * GET /api/v1/profile & GET /api/v1/balance
     * Cek profil mitra Reseller dan saldo aktif penentu transaksi API.
     */
    public function profile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('reseller_user');
        /** @var ResellerApiClient $client */
        $client = $request->attributes->get('reseller_api_client');

        $freshUser = User::find($user->id) ?? $user;

        return response()->json([
            'success'    => true,
            'message'    => 'Data profil dan saldo VIP Reseller PremiumKita berhasil diambil.',
            'data'       => [
                'brand'             => 'PremiumKita',
                'name'              => $freshUser->name,
                'email'             => $freshUser->email,
                'role'              => $freshUser->role,
                'balance'           => (int) $freshUser->balance,
                'currency'          => 'IDR',
                'webhook_url'       => $client->webhook_url,
                'ip_whitelist_mode' => count($client->parsedIpWhitelist()) > 0 ? 'restricted' : 'any',
                'total_api_orders'  => (int) ($client->total_orders ?? 0),
            ],
            'request_id' => $request->attributes->get('open_api_request_id'),
        ]);
    }

    /**
     * GET /api/v1/products
     * Daftar katalog Aplikasi Premium resmi PremiumKita.
     * Tidak pernah menyertakan produk PPOB maupun layanan di luar cakupan Aplikasi Premium.
     */
    public function products(Request $request, FrontController $frontController): JsonResponse
    {
        $cfg = ResellerOpenApiService::getGlobalConfig();
        $apiLiveMap = $frontController->getApiStockData();

        $query = ResellerOpenApiService::eligibleProductsQuery($cfg)->orderBy('name', 'asc');

        if ($request->filled('delivery_type')) {
            $deliveryType = strtolower(trim((string) $request->query('delivery_type')));
            if (!in_array($deliveryType, ['instant', 'manual'], true)) {
                return response()->json([
                    'success'    => false,
                    'error_code' => 'INVALID_DELIVERY_TYPE',
                    'message'    => 'Filter delivery_type hanya mendukung: instant atau manual.',
                    'request_id' => $request->attributes->get('open_api_request_id'),
                ], 422);
            }

            if ($deliveryType === 'manual') {
                $query->where('type', 'local')->where('process_type', 'manual');
            } else {
                $query->where(function ($sub) {
                    $sub->where('type', 'api')
                        ->orWhere(function ($qLocal) {
                            $qLocal->where('type', 'local')
                                ->where(function ($p) {
                                    $p->whereNull('process_type')->orWhere('process_type', '!=', 'manual');
                                });
                        });
                });
            }
        }

        if ($request->filled('category')) {
            $cat = substr(trim((string) $request->query('category')), 0, 80);
            if ($cat !== '') {
                $query->where('category', $cat);
            }
        }

        if ($request->filled('search')) {
            $q = substr(trim((string) $request->query('search')), 0, 80);
            if ($q !== '') {
                $escaped = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $q);
                $query->where('name', 'like', "%{$escaped}%");
            }
        }

        $onlyInStock = in_array(strtolower((string) $request->query('in_stock', '')), ['1', 'true', 'yes'], true);

        $items = [];
        foreach ($query->get() as $product) {
            // Lapisan verifikasi ganda agar produk non-aplikasi / TG-* tidak pernah bocor
            if (!ResellerOpenApiService::isProductEligible($product, $cfg)) {
                continue;
            }

            $frontController->resolveProductStock($product, $apiLiveMap);
            $formatted = ResellerOpenApiService::formatProductForApi($product, $cfg);

            if ($onlyInStock && empty($formatted['in_stock'])) {
                continue;
            }

            $items[] = $formatted;
        }

        return response()->json([
            'success'    => true,
            'message'    => 'Daftar katalog produk Aplikasi Premium PremiumKita berhasil diambil.',
            'meta'       => [
                'total'         => count($items),
                'currency'      => 'IDR',
                'brand'         => 'PremiumKita',
                'provider'      => 'PremiumKita',
                'catalog_scope' => 'Aplikasi Premium PremiumKita',
            ],
            'data'       => $items,
            'request_id' => $request->attributes->get('open_api_request_id'),
        ]);
    }

    /**
     * GET /api/v1/products/{productId}
     * Detail 1 produk Aplikasi Premium beserta stok dan harga Reseller realtime.
     */
    public function productDetail(Request $request, string $productId, FrontController $frontController): JsonResponse
    {
        $cfg = ResellerOpenApiService::getGlobalConfig();
        $resolvedId = ResellerOpenApiService::parseProductIdentifier($productId);

        if (!$resolvedId) {
            return response()->json([
                'success'    => false,
                'error_code' => 'INVALID_PRODUCT_ID',
                'message'    => 'ID atau kode produk tidak valid.',
                'request_id' => $request->attributes->get('open_api_request_id'),
            ], 422);
        }

        $product = Product::withAvailableAccountStock()->find($resolvedId);
        if (!$product || !ResellerOpenApiService::isProductEligible($product, $cfg)) {
            return response()->json([
                'success'    => false,
                'error_code' => 'PRODUCT_NOT_AVAILABLE',
                'message'    => 'Produk tidak ditemukan atau tidak tersedia pada katalog Aplikasi Premium resmi PremiumKita.',
                'request_id' => $request->attributes->get('open_api_request_id'),
            ], 404);
        }

        $frontController->resolveProductStock($product);

        return response()->json([
            'success'    => true,
            'message'    => 'Detail produk Aplikasi Premium berhasil diambil.',
            'data'       => ResellerOpenApiService::formatProductForApi($product, $cfg),
            'request_id' => $request->attributes->get('open_api_request_id'),
        ]);
    }

    /**
     * POST /api/v1/orders
     * Buat pesanan pembelian Aplikasi Premium menggunakan Saldo Reseller (Idempotent dengan ref_id).
     */
    public function createOrder(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('reseller_user');
        /** @var ResellerApiClient $client */
        $client = $request->attributes->get('reseller_api_client');

        $result = ResellerOpenApiService::placeOrder($user, $client, $request->all());
        $httpCode = (int) ($result['http_code'] ?? 200);

        if ($httpCode >= 400) {
            $errPayload = [
                'success'    => false,
                'error_code' => $result['error_code'] ?? 'ORDER_FAILED',
                'message'    => $result['message'] ?? 'Gagal memproses transaksi Open API.',
                'request_id' => $request->attributes->get('open_api_request_id'),
            ];
            if (!empty($result['meta'])) {
                $errPayload['meta'] = $result['meta'];
            }

            return response()->json($errPayload, $httpCode);
        }

        return response()->json([
            'success'    => true,
            'message'    => $result['message'],
            'data'       => $result['data'],
            'request_id' => $request->attributes->get('open_api_request_id'),
        ], $httpCode);
    }

    /**
     * GET /api/v1/orders
     * Daftar riwayat transaksi Open API milik Reseller.
     */
    public function orders(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('reseller_user');

        $limit = max(1, min(50, (int) $request->query('limit', 20)));
        $query = Order::where('user_id', $user->id)
            ->where('is_open_api', true)
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $st = strtolower(trim((string) $request->query('status')));
            if ($st === 'completed') {
                $query->whereIn('order_status', ['completed', 'success', 'lunas']);
            } elseif ($st === 'processing') {
                $query->whereIn('order_status', ['processing', 'pending']);
            } elseif ($st === 'refunded') {
                $query->whereIn('order_status', ['refunded', 'canceled', 'cancelled', 'failed']);
            }
        }

        if ($request->filled('ref_id')) {
            $query->where('api_client_ref_id', trim((string) $request->query('ref_id')));
        }

        $orders = $query->limit($limit)->get();
        $data = $orders->map(fn (Order $o) => ResellerOpenApiService::formatOrderForApi($o))->values();

        return response()->json([
            'success'    => true,
            'message'    => 'Riwayat pesanan Open API berhasil diambil.',
            'meta'       => [
                'count' => $data->count(),
                'limit' => $limit,
            ],
            'data'       => $data,
            'request_id' => $request->attributes->get('open_api_request_id'),
        ]);
    }

    /**
     * GET /api/v1/orders/{orderRef} atau POST /api/v1/orders/status
     * Cek status detail pesanan & kredensial akun berdasarkan invoice_number atau ref_id klien.
     */
    public function orderStatus(Request $request, ?string $orderRef = null): JsonResponse
    {
        /** @var User $user */
        $user = $request->attributes->get('reseller_user');

        $lookup = $orderRef ?: trim((string) ($request->input('invoice_number') ?: $request->input('ref_id') ?: ''));
        if ($lookup === '') {
            return response()->json([
                'success'    => false,
                'error_code' => 'MISSING_ORDER_REFERENCE',
                'message'    => 'Sertakan invoice_number atau ref_id untuk mengecek status pesanan.',
                'request_id' => $request->attributes->get('open_api_request_id'),
            ], 422);
        }

        $order = Order::where('user_id', $user->id)
            ->where('is_open_api', true)
            ->where(function ($q) use ($lookup) {
                $q->where('invoice_number', $lookup)
                  ->orWhere('api_client_ref_id', $lookup);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success'    => false,
                'error_code' => 'ORDER_NOT_FOUND',
                'message'    => 'Pesanan Open API dengan nomor referensi tersebut tidak ditemukan pada akun Anda.',
                'request_id' => $request->attributes->get('open_api_request_id'),
            ], 404);
        }

        // Sinkronisasi otomatis ke provider apabila status masih processing
        $rawStatus = strtolower((string) $order->order_status);
        if (
            $order->product_type === 'api'
            && in_array($rawStatus, ['processing', 'pending'], true)
            && OrderFulfillmentService::isWaitingText($order->sn_or_account)
        ) {
            try {
                OrderFulfillmentService::checkAndSyncApiOrder($order);
                $order->refresh();
            } catch (\Throwable $e) {}
        }

        // Rekonsiliasi refund otomatis jika dibatalkan namun belum ter-refund
        if (
            in_array(strtolower((string) $order->payment_status), ['paid', 'success', 'settlement', 'lunas'], true)
            && in_array(strtolower((string) $order->order_status), ['canceled', 'cancelled', 'failed', 'refunded'], true)
        ) {
            OrderFulfillmentService::cancelOrFailOrderWithRefund($order, $order->order_status ?: 'canceled');
            $order->refresh();
        }

        $freshBalance = (int) (User::whereKey($user->id)->value('balance') ?? 0);

        return response()->json([
            'success'    => true,
            'message'    => 'Status pesanan Open API berhasil diambil.',
            'data'       => ResellerOpenApiService::formatOrderForApi($order, $freshBalance, false),
            'request_id' => $request->attributes->get('open_api_request_id'),
        ]);
    }
}
