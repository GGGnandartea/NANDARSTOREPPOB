<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ResellerApiClient;
use App\Models\ResellerApiLog;
use App\Models\User;
use App\Services\ResellerOpenApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResellerApiSettingsController extends Controller
{
    /**
     * Pastikan pemanggil di menu profil adalah VIP Reseller aktif.
     */
    protected function ensureActiveReseller(Request $request): ?User
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'reseller' || !$user->is_active) {
            return null;
        }

        return $user;
    }

    /**
     * POST /profile/open-api/generate
     * Buat kredensial API Key & Secret Key pertama kali (Khusus Reseller).
     */
    public function generateCredentials(Request $request)
    {
        $user = $this->ensureActiveReseller($request);
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Fitur Open API eksklusif hanya untuk mitra VIP Reseller aktif.',
            ], 403);
        }

        $creds = ResellerOpenApiService::generateOrRotateCredentials($user, true);
        $client = $creds['client'];

        return response()->json([
            'status'         => 'success',
            'message'        => 'API Key dan Secret Key berhasil dibuat! Simpan Secret Key sekarang karena hanya ditampilkan satu kali.',
            'api_key'        => $creds['api_key'],
            'secret_key'     => $creds['secret_key'],
            'webhook_secret' => $creds['webhook_secret'],
            'client'         => $this->formatClientForProfile($client),
        ]);
    }

    /**
     * POST /profile/open-api/rotate
     * Rotasi (ganti baru) API Key & Secret Key (dan opsional Webhook Secret).
     */
    public function rotateCredentials(Request $request)
    {
        $user = $this->ensureActiveReseller($request);
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Fitur Open API eksklusif hanya untuk mitra VIP Reseller aktif.',
            ], 403);
        }

        $rotateWebhook = (bool) $request->boolean('rotate_webhook_secret', false);
        $creds = ResellerOpenApiService::generateOrRotateCredentials($user, $rotateWebhook);

        return response()->json([
            'status'         => 'success',
            'message'        => 'Kredensial API berhasil dirotasi! Kredensial lama otomatis tidak berlaku lagi.',
            'api_key'        => $creds['api_key'],
            'secret_key'     => $creds['secret_key'],
            'webhook_secret' => $creds['webhook_secret'],
            'client'         => $this->formatClientForProfile($creds['client']),
        ]);
    }

    /**
     * POST /profile/open-api/config
     * Simpan konfigurasi IP Whitelist, Webhook URL, dan status koneksi milik Reseller.
     */
    public function saveUserConfig(Request $request)
    {
        $user = $this->ensureActiveReseller($request);
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Fitur Open API eksklusif hanya untuk mitra VIP Reseller aktif.',
            ], 403);
        }

        ResellerOpenApiService::ensureSchema();
        $client = ResellerApiClient::where('user_id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Silakan klik tombol Buat API Key terlebih dahulu sebelum menyimpan konfigurasi.',
            ], 400);
        }

        $ipCheck = ResellerOpenApiService::validateAndNormalizeIpWhitelist($request->input('ip_whitelist'));
        if (!$ipCheck['ok']) {
            return response()->json([
                'status'  => 'error',
                'field'   => 'ip_whitelist',
                'message' => $ipCheck['message'],
            ], 422);
        }

        $whCheck = ResellerOpenApiService::validateWebhookUrl($request->input('webhook_url'));
        if (!$whCheck['ok']) {
            return response()->json([
                'status'  => 'error',
                'field'   => 'webhook_url',
                'message' => $whCheck['message'],
            ], 422);
        }

        $client->update([
            'ip_whitelist' => $ipCheck['normalized'],
            'webhook_url'  => $whCheck['url'],
            'is_enabled'   => $request->has('is_enabled') ? (bool) $request->boolean('is_enabled') : $client->is_enabled,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Konfigurasi keamanan Open API (IP Whitelist & Webhook URL) berhasil disimpan!',
            'client'  => $this->formatClientForProfile($client->fresh()),
        ]);
    }

    /**
     * POST /profile/open-api/toggle
     * Aktifkan / nonaktifkan akses API oleh Reseller sendiri.
     */
    public function toggleUserApi(Request $request)
    {
        $user = $this->ensureActiveReseller($request);
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Fitur Open API eksklusif hanya untuk mitra VIP Reseller aktif.',
            ], 403);
        }

        $client = ResellerApiClient::where('user_id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kredensial API belum dibuat.',
            ], 404);
        }

        $client->update(['is_enabled' => !$client->is_enabled]);

        return response()->json([
            'status'  => 'success',
            'message' => $client->is_enabled
                ? 'Koneksi Open API akun Anda berhasil diaktifkan!'
                : 'Koneksi Open API akun Anda berhasil dijeda (dinonaktifkan sementara).',
            'client'  => $this->formatClientForProfile($client->fresh()),
        ]);
    }

    /**
     * POST /profile/open-api/test-webhook
     * Kirim test ping Webhook bertanda tangan HMAC ke URL milik Reseller.
     */
    public function testUserWebhook(Request $request)
    {
        $user = $this->ensureActiveReseller($request);
        if (!$user) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'RESELLER_ONLY',
                'message'    => 'Fitur Open API eksklusif hanya untuk mitra VIP Reseller aktif.',
            ], 403);
        }

        $client = ResellerApiClient::where('user_id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Silakan buat kredensial API dan simpan Webhook URL terlebih dahulu.',
            ], 400);
        }

        $res = ResellerOpenApiService::sendTestWebhook($client);

        return response()->json([
            'status'      => $res['ok'] ? 'success' : 'error',
            'message'     => $res['message'],
            'status_code' => $res['status_code'] ?? null,
            'elapsed_ms'  => $res['elapsed_ms'] ?? null,
        ], $res['ok'] ? 200 : 400);
    }

    public static function buildProfileOpenApiPayload(User $user, Request $request): array
    {
        ResellerOpenApiService::ensureSchema();
        $cfg = ResellerOpenApiService::getGlobalConfig();
        $eligibleCount = ResellerOpenApiService::eligibleProductsQuery($cfg)->count();

        $basePayload = [
            'is_reseller'     => $user->role === 'reseller',
            'global_enabled'  => $cfg['enabled'],
            'base_url'        => url('/api/v1'),
            'caller_ip'       => $request->ip(),
            'policy'          => [
                'auth_mode'            => $cfg['auth_mode'],
                'timestamp_tolerance'  => $cfg['timestamp_tolerance'],
                'require_ip_whitelist' => $cfg['require_ip_whitelist'],
                'rate_limit_rpm'       => $cfg['rate_limit_rpm'],
                'order_rate_limit_rpm' => $cfg['order_rate_limit_rpm'],
                'max_qty_per_order'       => $cfg['max_qty_per_order'],
                'extra_fee'               => $cfg['extra_fee'],
                'webhook_enabled'         => $cfg['webhook_enabled'],
                'brand'                   => 'PremiumKita',
                'catalog_scope'           => 'Aplikasi Premium Resmi PremiumKita',
                'eligible_products_count' => $eligibleCount,
            ],
            'client'      => null,
            'recent_logs' => [],
        ];

        if ($user->role !== 'reseller') {
            return $basePayload;
        }

        $client = ResellerApiClient::where('user_id', $user->id)->first();
        if ($client) {
            $basePayload['client'] = (new self())->formatClientForProfile($client);
        }

        $logs = ResellerApiLog::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (ResellerApiLog $log) => [
                'id'               => $log->id,
                'method'           => $log->method,
                'endpoint'         => $log->endpoint,
                'ip_address'       => $log->ip_address,
                'status_code'      => $log->status_code,
                'ref_id'           => $log->ref_id,
                'invoice_number'   => $log->invoice_number,
                'error_code'       => $log->error_code,
                'response_time_ms' => $log->response_time_ms,
                'summary'          => $log->summary,
                'created_at'       => $log->created_at?->timezone('Asia/Jakarta')->format('d M Y, H:i:s'),
            ])
            ->values()
            ->all();

        $basePayload['recent_logs'] = $logs;

        return $basePayload;
    }

    protected function formatClientForProfile(ResellerApiClient $client): array
    {
        return [
            'api_key'              => $client->api_key,
            'secret_key_hint'      => $client->secret_key_hint,
            'webhook_url'          => $client->webhook_url ?? '',
            'webhook_secret_hint'  => $client->webhook_secret_hint,
            'ip_whitelist'         => $client->ip_whitelist ?? '',
            'ip_whitelist_entries' => $client->parsedIpWhitelist(),
            'is_enabled'           => (bool) $client->is_enabled,
            'admin_suspended'      => (bool) $client->admin_suspended,
            'admin_suspend_reason' => $client->admin_suspend_reason,
            'custom_rate_limit'    => $client->custom_rate_limit,
            'last_used_at'         => $client->last_used_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
            'last_used_ip'         => $client->last_used_ip,
            'total_requests'       => (int) ($client->total_requests ?? 0),
            'total_orders'         => (int) ($client->total_orders ?? 0),
            'rotated_at'           => $client->rotated_at?->timezone('Asia/Jakarta')->format('d M Y, H:i'),
        ];
    }

    // =========================================================================
    // AREA PANEL ADMIN (/admin/open-api)
    // =========================================================================

    public function adminIndex(Request $request)
    {
        ResellerOpenApiService::ensureSchema();
        $cfg = ResellerOpenApiService::getGlobalConfig(true);

        $search = trim((string) $request->query('search', ''));
        $clientsQuery = ResellerApiClient::with('user')->orderByDesc('updated_at');

        if ($search !== '') {
            $escaped = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $search);
            $clientsQuery->where(function ($q) use ($escaped) {
                $q->where('api_key', 'like', "%{$escaped}%")
                  ->orWhere('last_used_ip', 'like', "%{$escaped}%")
                  ->orWhereHas('user', function ($uq) use ($escaped) {
                      $uq->where('name', 'like', "%{$escaped}%")
                         ->orWhere('email', 'like', "%{$escaped}%")
                         ->orWhere('whatsapp', 'like', "%{$escaped}%");
                  });
            });
        }

        $clients = $clientsQuery->get();

        // Hitung statistik katalog produk yang masuk cakupan Open API
        $localAutoCount = Product::where('status', 'available')
            ->where('type', 'local')
            ->where(fn ($q) => $q->whereNull('process_type')->orWhere('process_type', '!=', 'manual'))
            ->count();
        $localManualCount = Product::where('status', 'available')
            ->where('type', 'local')
            ->where('process_type', 'manual')
            ->count();
        $premkuCount = Product::where('status', 'available')
            ->where('type', 'api')
            ->where('api_product_id', 'LIKE', 'PREMKU-%')
            ->count();
        $preflixCount = Product::where('status', 'available')
            ->where('type', 'api')
            ->where('api_product_id', 'LIKE', 'PREFLIX-%')
            ->count();
        $eligibleTotalCount = ResellerOpenApiService::eligibleProductsQuery($cfg)->count();

        $totalResellers = User::where('role', 'reseller')->count();
        $activeApiClientsCount = ResellerApiClient::where('is_enabled', true)->where('admin_suspended', false)->count();
        $totalApiOrders = Order::where('is_open_api', true)->count();
        $totalApiRevenue = (int) Order::validRevenue()->where('is_open_api', true)->sum('total_amount');
        $requests24h = ResellerApiLog::where('created_at', '>=', now()->subHours(24))->count();

        $recentLogs = ResellerApiLog::with('user:id,name,email')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $stats = [
            'total_resellers'   => $totalResellers,
            'active_clients'    => $activeApiClientsCount,
            'total_api_orders'  => $totalApiOrders,
            'total_api_revenue' => $totalApiRevenue,
            'requests_24h'      => $requests24h,
            'local_auto_count'  => $localAutoCount,
            'local_manual_count'=> $localManualCount,
            'premku_count'      => $premkuCount,
            'preflix_count'     => $preflixCount,
            'eligible_total'    => $eligibleTotalCount,
        ];

        return view('admin.open_api', compact('cfg', 'clients', 'recentLogs', 'stats', 'search'));
    }

    public function adminSaveConfig(Request $request)
    {
        $request->validate([
            'auth_mode'            => 'required|in:hmac_strict,hmac_order_only,key_secret',
            'timestamp_tolerance'  => 'required|integer|min:30|max:3600',
            'rate_limit_rpm'       => 'required|integer|min:10|max:1000',
            'order_rate_limit_rpm' => 'required|integer|min:5|max:300',
            'max_qty_per_order'    => 'required|integer|min:1|max:50',
            'extra_fee'            => 'required|integer|min:0|max:100000',
            'maintenance_message'  => 'nullable|string|max:250',
        ]);

        ResellerOpenApiService::saveGlobalConfig([
            'enabled'              => $request->boolean('enabled'),
            'auth_mode'            => $request->input('auth_mode'),
            'timestamp_tolerance'  => (int) $request->input('timestamp_tolerance'),
            'require_ip_whitelist' => $request->boolean('require_ip_whitelist'),
            'rate_limit_rpm'       => (int) $request->input('rate_limit_rpm'),
            'order_rate_limit_rpm' => (int) $request->input('order_rate_limit_rpm'),
            'max_qty_per_order'    => (int) $request->input('max_qty_per_order'),
            'allow_local'          => $request->boolean('allow_local'),
            'allow_local_manual'   => $request->boolean('allow_local_manual'),
            'allow_premku'         => $request->boolean('allow_premku'),
            'allow_preflix'        => $request->boolean('allow_preflix'),
            'extra_fee'            => (int) $request->input('extra_fee'),
            'webhook_enabled'      => $request->boolean('webhook_enabled'),
            'maintenance_message'  => $request->input('maintenance_message'),
        ]);

        return back()->with('success', 'Konfigurasi standar keamanan & cakupan produk Open API Reseller berhasil disimpan!');
    }

    public function adminToggleMaster(Request $request)
    {
        $cfg = ResellerOpenApiService::getGlobalConfig(true);
        $nextState = !$cfg['enabled'];
        $cfg['enabled'] = $nextState;

        ResellerOpenApiService::saveGlobalConfig($cfg);

        return back()->with(
            'success',
            $nextState
                ? 'Layanan Open API Reseller berhasil DIAKTIFKAN (ONLINE)!'
                : 'Layanan Open API Reseller berhasil DINONAKTIFKAN (MAINTENANCE)!'
        );
    }

    public function adminSuspendClient(Request $request, int $userId)
    {
        $client = ResellerApiClient::where('user_id', $userId)->firstOrFail();
        $nextSuspended = !$client->admin_suspended;
        $reason = $nextSuspended
            ? substr(trim((string) $request->input('reason', 'Ditangguhkan oleh Administrator')), 0, 250)
            : null;

        $client->update([
            'admin_suspended'      => $nextSuspended,
            'admin_suspend_reason' => $reason,
        ]);

        return back()->with(
            'success',
            $nextSuspended
                ? 'Akses API Reseller berhasil ditangguhkan sementara.'
                : 'Penangguhan akses API Reseller berhasil dicabut (aktif kembali).'
        );
    }

    public function adminUpdateClientRateLimit(Request $request, int $userId)
    {
        $request->validate([
            'custom_rate_limit' => 'nullable|integer|min:5|max:2000',
        ]);

        $client = ResellerApiClient::where('user_id', $userId)->firstOrFail();
        $val = $request->filled('custom_rate_limit') ? (int) $request->input('custom_rate_limit') : null;
        $client->update(['custom_rate_limit' => $val]);

        return back()->with('success', 'Batas Rate Limit khusus untuk mitra Reseller berhasil diperbarui.');
    }

    public function adminClearClientIpWhitelist(int $userId)
    {
        $client = ResellerApiClient::where('user_id', $userId)->firstOrFail();
        $client->update(['ip_whitelist' => null]);

        return back()->with('success', 'Daftar IP Whitelist mitra Reseller berhasil direset.');
    }

    public function adminRevokeClient(int $userId)
    {
        $client = ResellerApiClient::where('user_id', $userId)->first();
        if ($client) {
            $client->delete();
        }

        return back()->with('success', 'API Key & Secret Key mitra Reseller berhasil dicabut (revoke).');
    }

    public function adminClearLogs()
    {
        ResellerApiLog::query()->delete();

        return back()->with('success', 'Riwayat log audit Open API berhasil dibersihkan.');
    }
}
