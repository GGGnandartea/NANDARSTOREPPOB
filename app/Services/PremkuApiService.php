<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class PremkuApiService
{
    protected $baseUrl = 'https://premku.com/api/';
    protected $apiKey;

    public function __construct()
    {
        // Mengambil API Key dari database (diatur via panel Admin)
        $this->apiKey = Setting::where('key', 'premku_api_key')->value('value');
    }

    protected function resolveApiKey()
    {
        if (!$this->apiKey) {
            $this->apiKey = Setting::where('key', 'premku_api_key')->value('value');
        }
        return $this->apiKey;
    }

    /**
     * HTTP Client helper dengan paksaan IPv4 (CURL_IPRESOLVE_V4) agar tidak gagal
     * melewati IP Whitelist Premku pada server VPS Dual-Stack (IPv4 + IPv6).
     */
    protected function http(int $timeout = 15, int $connectTimeout = 8)
    {
        $client = Http::withoutVerifying()
            ->connectTimeout(min($connectTimeout, $timeout))
            ->timeout($timeout);

        if (defined('CURL_IPRESOLVE_V4')) {
            $client = $client->withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ]);
        }

        return $client;
    }

    public function profile()
    {
        return $this->http(15, 5)->post($this->baseUrl . 'profile', [
            'api_key' => $this->resolveApiKey()
        ])->json();
    }

    /**
     * Ambil informasi saldo & profil Premku secara terstruktur
     */
    public function checkDeposit(): array
    {
        $key = $this->resolveApiKey();
        if (empty($key)) {
            return [
                'ok'            => false,
                'saldo'         => 0,
                'username'      => '-',
                'whatsapp'      => '-',
                'registered_at' => '-',
                'message'       => 'API Key Premku belum dikonfigurasi.',
            ];
        }

        try {
            $res = $this->profile();
            if (is_array($res) && ($res['success'] ?? false) === true) {
                $data = $res['data'] ?? $res['user'] ?? [];
                $rawSaldo = (int) ($data['saldo'] ?? $data['balance'] ?? $res['saldo'] ?? $res['balance'] ?? 0);
                $username = (string) ($data['username'] ?? $res['username'] ?? 'User Premku');
                $whatsapp = (string) ($data['whatsapp'] ?? $res['whatsapp'] ?? '-');
                $registeredAt = isset($data['registered_at'])
                    ? \Carbon\Carbon::parse($data['registered_at'])->format('d M Y, H:i')
                    : '-';

                return [
                    'ok'            => true,
                    'saldo'         => $rawSaldo,
                    'username'      => $username,
                    'whatsapp'      => $whatsapp,
                    'registered_at' => $registeredAt,
                    'message'       => 'Terhubung! Saldo: Rp ' . number_format($rawSaldo, 0, ',', '.'),
                ];
            }

            return [
                'ok'            => false,
                'saldo'         => 0,
                'username'      => '-',
                'whatsapp'      => '-',
                'registered_at' => '-',
                'message'       => $res['message'] ?? 'API Key ditolak oleh Premku.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok'            => false,
                'saldo'         => 0,
                'username'      => '-',
                'whatsapp'      => '-',
                'registered_at' => '-',
                'message'       => 'Gagal koneksi ke Premku: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Periksa apakah saldo deposit Premku berada di bawah atau sama dengan batas minimum (threshold)
     * dan kirimkan Notifikasi Peringatan Otomatis ke WhatsApp Admin (Low Balance Alert).
     */
    public function checkAndNotifyLowBalance(?int $currentSaldo = null, bool $forceSend = false, ?string $contextInvoice = null): array
    {
        $rawThreshold = Setting::where('key', 'premku_low_balance_threshold')->value('value');
        if (app()->runningUnitTests() && !$forceSend && $rawThreshold === null) {
            return [
                'ok'      => false,
                'sent'    => false,
                'reason'  => 'test_environment',
                'message' => 'Skipped in unit test without explicit threshold.',
            ];
        }

        $enabled   = (string) (Setting::where('key', 'premku_low_balance_alert_enabled')->value('value') ?? '1');
        $threshold = max(0, (int) ($rawThreshold ?? 50000));
        $adminWa   = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';

        if (!$forceSend && $enabled !== '1') {
            return [
                'ok'      => false,
                'sent'    => false,
                'reason'  => 'disabled',
                'message' => 'Notifikasi Low Balance Alert Premku dinonaktifkan.',
            ];
        }

        $username = 'User Premku';
        if ($currentSaldo === null) {
            $dep = $this->checkDeposit();
            if (!($dep['ok'] ?? false)) {
                return [
                    'ok'      => false,
                    'sent'    => false,
                    'reason'  => 'deposit_unavailable',
                    'message' => $dep['message'] ?? 'Saldo Premku belum dapat diperiksa.',
                ];
            }
            $currentSaldo = (int) ($dep['saldo'] ?? 0);
            $username = (string) ($dep['username'] ?? 'User Premku');
        }

        $isLow = $currentSaldo <= $threshold;
        $cacheKey = 'premku_low_balance_alert_sent';

        if (!$isLow && !$forceSend) {
            Cache::forget($cacheKey);
            return [
                'ok'        => true,
                'sent'      => false,
                'is_low'    => false,
                'saldo'     => $currentSaldo,
                'threshold' => $threshold,
                'message'   => 'Saldo deposit Premku aman di atas batas minimum.',
            ];
        }

        if (!$forceSend && Cache::has($cacheKey)) {
            return [
                'ok'        => true,
                'sent'      => false,
                'is_low'    => $isLow,
                'reason'    => 'cooldown',
                'saldo'     => $currentSaldo,
                'threshold' => $threshold,
                'message'   => 'Peringatan WhatsApp saldo Premku sudah dikirim sebelumnya (masa jeda anti-spam aktif).',
            ];
        }

        $targetWa = preg_replace('/[^0-9]/', '', (string) $adminWa);
        if (str_starts_with($targetWa, '0')) {
            $targetWa = '62' . substr($targetWa, 1);
        }

        $saldoFmt     = number_format($currentSaldo, 0, ',', '.');
        $thresholdFmt = number_format($threshold, 0, ',', '.');
        $timeWib      = now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB';
        $settingsUrl  = url('/admin/settings?section=api');

        $headerBadge = $isLow
            ? "🚨 *PERINGATAN DARURAT: SALDO PREMKU MENIPIS*"
            : "✅ *UJI COBA NOTIFIKASI MONITORING SALDO PREMKU*";

        $statusExplain = $isLow
            ? "⚠️ Saldo deposit *Premku API* Anda saat ini telah berada *di bawah atau sama dengan batas minimum peringatan* yang Anda tetapkan. Segera lakukan pengisian ulang (Top Up QRIS Premku) agar pesanan produk otomatis pelanggan tidak tertahan."
            : "ℹ️ Ini adalah pesan uji coba peringatan saldo Premku. Saat ini saldo deposit Premku Anda masih berada di atas batas minimum aman.";

        $invoiceLine = $contextInvoice ? "• Pesanan Terkait: *#{$contextInvoice}*\n" : "";

        $msg = "{$headerBadge}\n\n"
             . "Halo Admin *PremiumKita*,\n"
             . "{$statusExplain}\n\n"
             . "📊 *RINCIAN SALDO DEPOSIT PREMKU:*\n"
             . "• Provider: *Premku Digital Store*\n"
             . "• Akun Premku: *{$username}*\n"
             . $invoiceLine
             . "• Sisa Saldo Saat Ini: *Rp {$saldoFmt}*\n"
             . "• Batas Minimum Alert: *Rp {$thresholdFmt}*\n"
             . "• Waktu Pengecekan: *{$timeWib}*\n\n"
             . "💳 *TINDAKAN CEPAT ADMIN:*\n"
             . "Top Up Saldo Premku via QRIS langsung di panel admin:\n"
             . "👉 {$settingsUrl}\n\n"
             . "_Pesan otomatis dari Sistem Monitoring Saldo Premku PremiumKita 2.0._";

        $sent = \App\Http\Controllers\WebhookController::sendWaMessage($targetWa, $msg);

        if ($sent && !$forceSend) {
            Cache::put($cacheKey, now()->toIso8601String(), now()->addHours(4));
        }

        if ($sent) {
            Setting::updateOrCreate(
                ['key' => 'premku_low_balance_last_alert_at'],
                ['value' => $timeWib]
            );
        }

        return [
            'ok'        => true,
            'sent'      => (bool) $sent,
            'is_low'    => $isLow,
            'target'    => $targetWa,
            'saldo'     => $currentSaldo,
            'threshold' => $threshold,
            'message'   => $sent
                ? "Notifikasi peringatan saldo Premku (Rp {$saldoFmt}) berhasil dikirim ke WhatsApp Admin ({$adminWa})!"
                : "Gagal mengirim ke WhatsApp Admin ({$adminWa}). Pastikan Bot WhatsApp Gateway aktif.",
        ];
    }

    public function getProducts(int $timeout = 30)
    {
        $key = $this->resolveApiKey();
        if (!$key) {
            return [];
        }

        return $this->http($timeout, min(5, $timeout))->post($this->baseUrl . 'products', [
            'api_key' => $key
        ])->json();
    }

    public function createOrder($productId, $qty, $refId)
    {
        $cleanProductId = is_numeric($productId) ? (int) $productId : trim((string) $productId);
        return $this->http(30, 10)->post($this->baseUrl . 'order', [
            'api_key'    => $this->resolveApiKey(),
            'product_id' => $cleanProductId,
            'qty'        => max(1, (int) $qty),
            'ref_id'     => trim((string) $refId),
        ])->json();
    }

    public function checkStatus($invoice)
    {
        return $this->http(15, 6)->post($this->baseUrl . 'status', [
            'api_key' => $this->resolveApiKey(),
            'invoice' => trim((string) $invoice),
        ])->json();
    }

    public function checkStock($productId)
    {
        return $this->http(15, 6)->post($this->baseUrl . 'stock', [
            'api_key' => $this->resolveApiKey(),
            'product_id' => $productId
        ])->json();
    }

    public function createDeposit($amount)
    {
        return $this->http(20, 6)->post($this->baseUrl . 'pay', [
            'api_key' => $this->resolveApiKey(),
            'amount' => (int) $amount
        ])->json();
    }

    public function checkDepositStatus($invoice)
    {
        return $this->http(15, 6)->post($this->baseUrl . 'pay_status', [
            'api_key' => $this->resolveApiKey(),
            'invoice' => $invoice
        ])->json();
    }

    public function cancelDeposit($invoice)
    {
        return $this->http(15, 6)->post($this->baseUrl . 'cancel_pay', [
            'api_key' => $this->resolveApiKey(),
            'invoice' => $invoice
        ])->json();
    }

    /**
     * Alight Motion API - Buy Bulk (Generate masal, max 200)
     */
    public function amBuyBulk($qty, $refId = null)
    {
        $payload = [
            'api_key' => $this->resolveApiKey(),
            'action'  => 'buy_bulk',
            'qty'     => (int) $qty,
        ];
        if ($refId) {
            $payload['ref_id'] = $refId;
        }

        return $this->http(30, 8)->post($this->baseUrl . 'am', $payload)->json();
    }

    /**
     * Alight Motion API - Buy Single (Kirim link verifikasi ke email)
     */
    public function amBuySingle($email)
    {
        return $this->http(30, 8)->post($this->baseUrl . 'am', [
            'api_key' => $this->resolveApiKey(),
            'action'  => 'buy_single',
            'email'   => $email,
        ])->json();
    }

    /**
     * Alight Motion API - Verify Single (Verifikasi link aktivasi email)
     */
    public function amVerifySingle($invoice, $email, $link)
    {
        return $this->http(30, 8)->post($this->baseUrl . 'am', [
            'api_key' => $this->resolveApiKey(),
            'action'  => 'verify_single',
            'invoice' => $invoice,
            'email'   => $email,
            'link'    => $link,
        ])->json();
    }

    /**
     * Alight Motion API - Cancel Single (Batalkan order pending)
     */
    public function amCancelSingle($invoice)
    {
        return $this->http(20, 6)->post($this->baseUrl . 'am', [
            'api_key' => $this->resolveApiKey(),
            'action'  => 'cancel_single',
            'invoice' => $invoice,
        ])->json();
    }
}
