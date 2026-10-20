<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\LocalAccount;
use App\Models\Setting;
use App\Models\User;
use App\Models\TransactionLog;
use App\Services\KiosbankService;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
    /**
     * Memproses fulfillment pesanan setelah pembayaran terkonfirmasi (Saldo atau QRIS)
     * Untuk produk API (Preflix / Premku), server akan menunggu hingga provider memberikan akun.
     */
    public static function fulfillOrder(Order $order): array
    {
        $order->refresh();
        if (!in_array(strtolower($order->payment_status), ['paid', 'success', 'settlement'], true)) {
            return ['status' => 'error', 'is_ready' => false, 'message' => 'Pembayaran belum dikonfirmasi.'];
        }

        if ($order->fulfillment_started_at || $order->api_trx_id
            || in_array(strtolower($order->order_status), ['completed', 'success', 'lunas', 'refunded', 'canceled', 'cancelled', 'failed'], true)) {
            return self::existingResult($order);
        }

        if ($order->product_type === 'ppob' && !$order->fulfillment_started_at
            && !$order->api_trx_id && (int) $order->provider_amount <= 0) {
            return ['status' => 'error', 'is_ready' => false, 'message' => 'Nominal provider belum tercatat. Admin perlu memverifikasi nominal provider pesanan lama sebelum memprosesnya.'];
        }

        // Persist the claim before contacting a provider. A timeout or crash must
        // never clear it: the provider might already have accepted the purchase.
        $claimed = Order::whereKey($order->id)->whereNull('fulfillment_started_at')
            ->where(function ($query) {
                $query->whereNull('api_trx_id')->orWhere('api_trx_id', '');
            })
            ->whereRaw("LOWER(payment_status) IN ('paid', 'success', 'settlement')")
            ->whereRaw("LOWER(order_status) NOT IN ('completed', 'success', 'lunas', 'refunded', 'canceled', 'cancelled', 'expired', 'failed')")
            ->update(['fulfillment_started_at' => now()]);

        $order->refresh();
        if (!$claimed) {
            return self::existingResult($order);
        }

        try {
            return self::fulfillClaimedOrder($order);
        } catch (\Throwable $e) {
            Log::error('[FULFILLMENT-RECONCILE] Order '.$order->invoice_number.': '.$e->getMessage());
            return ['status' => 'processing', 'is_ready' => false, 'message' => 'Proses telah dimulai. Periksa status provider sebelum tindakan berikutnya; pesanan tidak dikirim ulang otomatis.'];
        }
    }

    private static function existingResult(Order $order): array
    {
        return [
            'status' => $order->order_status,
            'is_ready' => in_array(strtolower($order->order_status), ['completed', 'success', 'lunas'], true),
            'is_refunded' => $order->order_status === 'refunded',
            'sn_or_account' => $order->sn_or_account,
            'message' => 'Pesanan sudah pernah diproses. Gunakan pemeriksaan status provider untuk melanjutkan pemantauan.',
        ];
    }

    private static function fulfillClaimedOrder(Order $order): array
    {
        // ─── 0. FULFILLMENT PRODUK PPOB KIOSBANK ─────────────────────────────────
        if ($order->product_type === 'ppob') {
            $targetNumber = $order->whatsapp ?: '08123456789';
            if (!empty($order->notes) && preg_match('/No Tujuan:\s*([^|]+)/i', $order->notes, $m)) {
                $targetNumber = trim($m[1]);
            }
            $prodCode = $order->product_id ?: 'PPOB';
            if (!empty($order->notes) && preg_match('/Kode:\s*([^\s|]+)/i', $order->notes, $m2)) {
                $prodCode = trim($m2[1]);
            }
            $targetNumber = $order->provider_customer_id ?: $targetNumber;
            $prodCode = $order->provider_product_code ?: $prodCode;
            $nominal = (int) $order->provider_amount;

            $kiosbank = new KiosbankService($order->kiosbank_mode);
            // Kiosbank mengharuskan referenceID berupa 12 digit angka unik.
            // Untuk produk tagihan, pakai referenceID inquiry agar pembayaran
            // merujuk pada data inquiry yang sama (berlaku maksimal 10 menit).
            $refId = $order->bill_reference ?: $order->api_trx_id;
            if (empty($refId) || !ctype_digit((string)$refId) || strlen((string)$refId) !== 12) {
                $refId = now()->format('ymd') . str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            }
            $order->api_trx_id = $refId;
            $order->save();

            // Produk tagihan (PDAM / PLN pascabayar / BPJS) memakai endpoint Payment
            // dengan nominal hasil inquiry, bukan SinglePayment seperti pulsa.
            if ($order->bill_total) {
                $res = $kiosbank->payBill(
                    (string) $prodCode,
                    (string) $targetNumber,
                    (int) $order->bill_amount,
                    (int) $order->bill_admin_fee,
                    (int) $order->bill_total,
                    $refId,
                    $order->whatsapp
                );

                if (($res['status'] ?? '') === 'success') {
                    $order->order_status   = 'completed';
                    $order->payment_status = 'paid';
                    $order->provider       = 'Kiosbank';
                    $sn = $res['sn'] ?? ($res['reference_id'] ?? 'BERHASIL');
                    $order->sn_or_account  = "No. Referensi: " . $sn;
                    $order->account_details= "Pembayaran Tagihan: " . $order->product_name . "\nNo. Pelanggan: " . $targetNumber . "\nTotal: Rp " . number_format((int) $order->bill_total, 0, ',', '.') . "\nNo. Referensi: " . $sn;
                    $order->notes          = ($order->notes ? $order->notes . ' | ' : '') . "Pembayaran tagihan Kiosbank sukses. Ref: " . ($res['reference_id'] ?? $refId);
                    $order->save();

                    try {
                        WebhookController::sendOrderNotification($order);
                    } catch (\Exception $e) {
                        Log::error('[PPOB-WA-NOTIF-ERROR] ' . $e->getMessage());
                    }

                    return [
                        'status'        => 'success',
                        'is_ready'      => true,
                        'sn_or_account' => $order->sn_or_account,
                        'message'       => 'Tagihan berhasil dibayarkan melalui Kiosbank!',
                    ];
                }

                $order->order_status   = 'processing';
                $order->payment_status = 'paid';
                $order->provider       = 'Kiosbank';
                $order->notes          = 'Pembayaran tagihan sedang diproses Kiosbank: ' . ($res['message'] ?? 'Pending');
                $order->save();

                return [
                    'status'   => 'processing',
                    'is_ready' => false,
                    'message'  => 'Pembayaran tagihan sedang diproses oleh provider Kiosbank.',
                ];
            }

            $res = $kiosbank->singlePayment(
                (string) $prodCode,
                (string) $targetNumber,
                $nominal,
                0,
                $nominal,
                $refId
            );

            if (($res['status'] ?? '') === 'success') {
                $order->order_status   = 'completed';
                $order->payment_status = 'paid';
                $order->provider       = 'Kiosbank';
                $sn = $res['sn'] ?? ($res['reference_id'] ?? 'BERHASIL');
                $order->sn_or_account  = "SN/Token: " . $sn;
                $order->account_details= "Layanan PPOB: " . $order->product_name . "\nTujuan: " . $targetNumber . "\nSN/Token: " . $sn;
                $order->notes          = ($order->notes ? $order->notes . ' | ' : '') . "Transaksi PPOB Kiosbank Sukses. Ref: " . ($res['reference_id'] ?? $refId);
                $order->save();

                // Notifikasi WhatsApp jika akun pembeli terverifikasi WA
                try {
                    DB::afterCommit(function () use ($order) {
                        try {
                            WebhookController::sendOrderNotification($order);
                        } catch (\Throwable $e) {
                            Log::warning('[ORDER-NOTIFICATION] '.$order->invoice_number.': '.$e->getMessage());
                        }
                    });
                } catch (\Exception $e) {
                    Log::error('[PPOB-WA-NOTIF-ERROR] ' . $e->getMessage());
                }

                return [
                    'status'        => 'success',
                    'is_ready'      => true,
                    'sn_or_account' => $order->sn_or_account,
                    'message'       => 'Layanan PPOB berhasil diproses oleh Kiosbank!',
                ];
            } else {
                $order->order_status   = 'processing';
                $order->payment_status = 'paid';
                $order->provider       = 'Kiosbank';
                $order->notes          = 'Sedang diproses Kiosbank: ' . ($res['message'] ?? 'Pending');
                $order->save();

                return [
                    'status'   => 'processing',
                    'is_ready' => false,
                    'message'  => 'Transaksi PPOB sedang diproses oleh provider Kiosbank.',
                ];
            }
        }

        $product = Product::find($order->product_id);
        if (!$product) {
            return [
                'status'  => 'error',
                'message' => 'Produk tidak ditemukan.',
            ];
        }

        // ─── 1. FULFILLMENT PRODUK LOKAL ───────────────────────────────────────
        if ($product->type === 'local') {
            if ($product->process_type === 'manual') {
                $snAkun = "Pesanan sedang diproses manual oleh Admin. Akun/layanan akan dikirim via WhatsApp/Email.";
                $order->sn_or_account   = $snAkun;
                $order->account_details = $snAkun;
                $order->order_status    = 'processing';
                $order->payment_status  = 'paid';
                $order->provider        = 'Manual Admin';
                $order->notes           = 'Menunggu admin memproses akun / layanan secara manual.';
                $order->save();

                return [
                    'status'        => 'processing',
                    'is_ready'      => false,
                    'sn_or_account' => $snAkun,
                    'message'       => 'Pesanan manual berhasil diterima. Menunggu proses oleh admin.',
                ];
            }

            // PRODUK OTOMATIS: Ambil dari stok akun otomatis dengan Pessimistic Locking (Atomic)
            $order->provider = 'Otomatis';

            return DB::transaction(function () use ($order, $product) {
                $qty = max(1, (int) ($order->quantity ?? 1));

                // Lock $qty akun yang belum terjual dengan lockForUpdate untuk mencegah race condition
                $accounts = LocalAccount::where('product_id', $product->id)
                    ->where('is_sold', false)
                    ->lockForUpdate()
                    ->limit($qty)
                    ->get();

                if ($accounts->count() === $qty) {
                    // Pemenang rebutan stok: tandai terjual & alokasikan akun
                    $snParts = [];
                    foreach ($accounts as $index => $acc) {
                        $acc->update(['is_sold' => true]);
                        $prefix = ($qty > 1) ? ("=== AKUN #" . ($index + 1) . " ===\n") : "";
                        $snParts[] = $prefix . "Email: " . $acc->email . "\nPassword: " . $acc->password;
                    }

                    $snAkun = implode("\n\n", $snParts);
                    $product->update(['stock' => $product->accounts()->where('is_sold', false)->count()]);

                    $order->sn_or_account   = $snAkun;
                    $order->account_details = $snAkun;
                    $order->order_status    = 'completed';
                    $order->payment_status  = 'paid';
                    $order->save();

                    // Deliver credentials only after allocation has committed.
                    DB::afterCommit(function () use ($order) {
                        try {
                            WebhookController::sendOrderNotification($order);
                        } catch (\Throwable $e) {
                            Log::warning('[ORDER-NOTIFICATION] '.$order->invoice_number.': '.$e->getMessage());
                        }
                    });

                    return [
                        'status'        => 'completed',
                        'is_ready'      => true,
                        'sn_or_account' => $snAkun,
                        'message'       => 'Akun lokal (' . $qty . ' pcs) berhasil dialokasikan.',
                    ];
                } else {
                    // KALAH REBUTAN STOK (Stok sisa tidak cukup untuk memenuhi pesanan!)
                    // Jalankan Logika Auto-Refund ke Saldo Akun Pembeli
                    return self::handleOutOfStockAutoRefund($order, $product);
                }
            });
        }

        // ─── 2. FULFILLMENT PRODUK API (PREFLIX & PREMKU) ──────────────────────
        if ($product->type === 'api') {
            $apiProductId = $product->api_product_id;
            $order->provider = str_starts_with($apiProductId, 'PREFLIX-') ? 'Preflix' : (str_starts_with($apiProductId, 'PREMKU-') ? 'Premku' : 'API');
            $snAkun = null;
            $apiTrxId = null;
            $isReady = false;

            // A. PROVIDER PREFLIX
            if (str_starts_with($apiProductId, 'PREFLIX-')) {
                $idProdukAsli = str_replace('PREFLIX-', '', $apiProductId);
                $idUser  = Setting::where('key', 'preflix_id_user')->value('value');
                $keyUser = Setting::where('key', 'preflix_key_user')->value('value');

                try {
                    $qty = max(1, (int) ($order->quantity ?: 1));

                    // Hanya lakukan request pemesanan jika order belum pernah ditembak ke Preflix
                    if (empty($apiTrxId)) {
                        $snList = [];
                        $apiTrxIds = [];

                        for ($i = 0; $i < $qty; $i++) {
                            $itemCatatan = $order->invoice_number . ($qty > 1 ? "-ITEM" . ($i + 1) : "");
                            $resOrder = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                                'koneksi'   => 'order',
                                'id_produk' => $idProdukAsli,
                                'id_user'   => $idUser,
                                'key_user'  => $keyUser,
                                'email'     => $order->email,
                                'catatan'   => $itemCatatan,
                            ])->json();

                            Log::info("[PREFLIX-ORDER-INIT] Invoice: {$order->invoice_number} | Item: " . ($i + 1) . " | Response: " . json_encode($resOrder));

                            $preflixData = null;
                            if (isset($resOrder['data'])) {
                                if (is_array($resOrder['data'])) {
                                    $preflixData = $resOrder['data'];
                                } elseif (is_string($resOrder['data'])) {
                                    $decoded = json_decode($resOrder['data'], true);
                                    if (is_array($decoded)) {
                                        $preflixData = $decoded;
                                    }
                                }
                            }

                            if (isset($resOrder['status']) && strtolower($resOrder['status']) === 'success') {
                                $itemTrxId = $preflixData['id_stok'] 
                                          ?? $preflixData['id_order'] 
                                          ?? $preflixData['id_riwayat'] 
                                          ?? ('PREFLIX-' . time() . '-' . $i);
                                $apiTrxIds[] = (string) $itemTrxId;

                                $immediateSn = $preflixData['serial_number'] 
                                            ?? $preflixData['sn'] 
                                            ?? self::extractSnFromData($preflixData ?: $resOrder);

                                if (!empty($immediateSn) && !self::isWaitingText($immediateSn)) {
                                    $snList[] = ($qty > 1 ? "[Akun " . ($i + 1) . "]:\n" : "") . $immediateSn;
                                }
                            } else {
                                $failMsg = $resOrder['message'] ?? $resOrder['data']['status'] ?? 'Gagal dari provider';
                                Log::warning("[PREFLIX-ORDER-FAILED] Invoice: {$order->invoice_number} Item: " . ($i + 1) . " | Message: {$failMsg}");

                                if (str_contains(strtolower($failMsg), 'saldo') || str_contains(strtolower($failMsg), 'forbidden')) {
                                    $order->notes = 'Saldo akun Preflix provider sedang tidak mencukupi.';
                                }
                            }
                        }

                        if (!empty($apiTrxIds)) {
                            $apiTrxId = implode(',', $apiTrxIds);
                            $order->api_trx_id = $apiTrxId;
                            $order->save();
                        }

                        if (!empty($snList) && count($snList) === $qty) {
                            $snAkun = implode("\n\n---\n\n", $snList);
                            $isReady = true;
                        } elseif (!empty($snList)) {
                            $snAkun = implode("\n\n---\n\n", $snList);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("[PREFLIX-ORDER-ERROR] " . $e->getMessage());
                }
            }

            // B. PROVIDER PREMKU
            elseif (str_starts_with($apiProductId, 'PREMKU-')) {
                $idProdukAsli = str_replace('PREMKU-', '', $apiProductId);
                $apiKey = Setting::where('key', 'premku_api_key')->value('value');
                $premkuService = new PremkuApiService();

                try {
                    $qty = max(1, (int) ($order->quantity ?: 1));
                    $resOrder = $premkuService->createOrder($idProdukAsli, $qty, $order->invoice_number);
                    Log::info("[PREMKU-ORDER-INIT] Invoice: {$order->invoice_number} (Qty: {$qty}) | Response: " . json_encode($resOrder));

                    if (isset($resOrder['success']) && $resOrder['success'] === false) {
                        $errMsg = $resOrder['message'] ?? 'Gagal membuat pesanan';
                        $order->notes = "[INTERNAL] " . $errMsg;
                        $order->save();
                        Log::error("[PREMKU-ORDER-FAIL] Invoice: {$order->invoice_number} | {$errMsg}");
                    }

                    $apiTrxId = $resOrder['invoice'] 
                             ?? $resOrder['data']['invoice'] 
                             ?? $resOrder['trx_id'] 
                             ?? $resOrder['data']['id'] 
                             ?? null;

                    $immediateSn = self::extractSnFromData($resOrder['data'] ?? $resOrder);
                    if (!empty($immediateSn)) {
                        $snAkun = $immediateSn;
                        $isReady = true;
                    }

                    if ($apiTrxId) {
                        $order->api_trx_id = (string) $apiTrxId;
                        $order->save();
                    }

                    // SERVER WAITING / POLLING LOOP PREMKU
                    if (!$isReady && $apiTrxId) {
                        for ($attempt = 1; $attempt <= 5; $attempt++) {
                            sleep(2); // Menunggu 2 detik per iterasi

                            $statusRes = $premkuService->checkStatus($apiTrxId);
                            Log::info("[PREMKU-ORDER-WAIT-{$attempt}] Invoice: {$order->invoice_number} | Trx: {$apiTrxId} | Result: " . json_encode($statusRes));

                            $polledSn = self::extractSnFromData($statusRes['data'] ?? $statusRes);
                            if (!empty($polledSn)) {
                                $snAkun = $polledSn;
                                $isReady = true;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("[PREMKU-ORDER-ERROR] " . $e->getMessage());
                }
            }

            // C. SIMPAN HASIL STATUS AKHIR ORDER API KE DATABASE
            if ($isReady && !empty($snAkun)) {
                $order->sn_or_account   = $snAkun;
                $order->account_details = $snAkun;
                $order->order_status    = 'completed';
                $order->payment_status  = 'paid';
                if ($apiTrxId) $order->api_trx_id = (string) $apiTrxId;
                $order->save();

                // Kirim pesan WhatsApp otomatis ke pembeli jika nomor terverifikasi
                WebhookController::sendOrderNotification($order);

                return [
                    'status'        => 'completed',
                    'is_ready'      => true,
                    'sn_or_account' => $snAkun,
                    'message'       => 'Akun berhasil diterima dari server API provider!',
                ];
            } else {
                // Server masih memproses akun di background
                $waitMessage = 'Sedang diproses otomatis oleh server... Mohon tunggu beberapa detik, akun akan tampil otomatis.';
                $order->sn_or_account   = $waitMessage;
                $order->account_details = $waitMessage;
                $order->order_status    = 'processing';
                $order->payment_status  = 'paid';
                if ($apiTrxId) $order->api_trx_id = (string) $apiTrxId;
                $order->save();

                return [
                    'status'        => 'processing',
                    'is_ready'      => false,
                    'sn_or_account' => $waitMessage,
                    'message'       => 'Server sedang memproses akun secara otomatis.',
                ];
            }
        }

        return [
            'status'  => 'unknown_type',
            'message' => 'Tipe produk tidak dikenali.',
        ];
    }

    /**
     * Memeriksa dan memperbarui status pesanan API jika sebelumnya masih dalam status 'processing'
     * Dipanggil secara realtime oleh polling di halaman Invoice / Checkout / Profile.
     */
    public static function checkAndSyncApiOrder(Order $order): array
    {
        $order->refresh();
        if (!in_array(strtolower($order->payment_status), ['paid', 'success', 'settlement'], true)
            || in_array(strtolower($order->order_status), ['refunded', 'canceled', 'cancelled', 'failed'], true)) {
            return self::existingResult($order);
        }
        // Jika sudah ada akun yang sah dan status sudah selesai, langsung kembalikan
        if ($order->order_status === 'completed' && !empty($order->sn_or_account) && !self::isWaitingText($order->sn_or_account)) {
            return [
                'status'        => 'completed',
                'is_ready'      => true,
                'sn_or_account' => $order->sn_or_account,
                'is_updated'    => false,
            ];
        }

        $product = Product::find($order->product_id);
        if (!$product || $product->type !== 'api') {
            return [
                'status'        => $order->order_status,
                'is_ready'      => !self::isWaitingText($order->sn_or_account),
                'sn_or_account' => $order->sn_or_account,
                'is_updated'    => false,
            ];
        }

        $apiProductId = $product->api_product_id;
        $apiTrxId = $order->api_trx_id;
        $snAkun = null;

        // A missing provider ID after a timeout is ambiguous. Status checks must
        // never place a new purchase as a side effect of reading an invoice.

        // CEK STATUS KE PREFLIX MENGGUNAKAN get_riwayat
        if (str_starts_with($apiProductId, 'PREFLIX-')) {
            $idUser  = Setting::where('key', 'preflix_id_user')->value('value');
            $keyUser = Setting::where('key', 'preflix_key_user')->value('value');

            try {
                $statusRes = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                    'koneksi'  => 'get_riwayat',
                    'id_user'  => $idUser,
                    'key_user' => $keyUser,
                ])->json();

                $snAkun = self::preflixCredentialsForOrder($order, $statusRes);
            } catch (\Exception $e) {
                Log::warning("[PREFLIX-SYNC-ERROR] " . $e->getMessage());
            }
        }

        // CEK STATUS KE PREMKU
        elseif (str_starts_with($apiProductId, 'PREMKU-')) {
            $premkuService = new PremkuApiService();

            try {
                if ($apiTrxId) {
                    $statusRes = $premkuService->checkStatus($apiTrxId);
                    $snAkun = self::extractSnFromData($statusRes['data'] ?? $statusRes);
                }
            } catch (\Exception $e) {
                Log::warning("[PREMKU-SYNC-ERROR] " . $e->getMessage());
            }
        }

        // JIKA AKUN SUDAH DISEDIAKAN OLEH PROVIDER!
        if (!empty($snAkun) && !self::isWaitingText($snAkun)) {
            $updated = Order::whereKey($order->id)
                ->whereRaw("LOWER(payment_status) IN ('paid', 'success', 'settlement')")
                ->whereRaw("LOWER(order_status) NOT IN ('completed', 'success', 'lunas', 'refunded', 'canceled', 'cancelled', 'failed')")
                ->update([
                    'sn_or_account' => $snAkun, 'account_details' => $snAkun,
                    'order_status' => 'completed', 'payment_status' => 'paid',
                ]);
            $order->refresh();
            if (!$updated) {
                return self::existingResult($order);
            }

            // Kirim pesan WhatsApp otomatis
            WebhookController::sendOrderNotification($order);

            return [
                'status'        => 'completed',
                'is_ready'      => true,
                'sn_or_account' => $snAkun,
                'is_updated'    => true,
            ];
        }

        return [
            'status'        => 'processing',
            'is_ready'      => false,
            'sn_or_account' => $order->sn_or_account,
            'is_updated'    => false,
        ];
    }

    private static function preflixCredentialsForOrder(Order $order, mixed $history): ?string
    {
        if (!is_array($history)) {
            return null;
        }
        $quantity = max(1, (int) $order->quantity);
        $ids = array_values(array_filter(explode(',', (string) $order->api_trx_id)));
        $credentials = [];
        $usedRows = [];
        for ($index = 0; $index < $quantity; $index++) {
            $reference = $order->invoice_number.($quantity > 1 ? '-ITEM'.($index + 1) : '');
            foreach ($history as $row => $item) {
                if (!is_array($item) || isset($usedRows[$row])) {
                    continue;
                }
                $matchesId = count($ids) === $quantity && isset($item['id_stok'])
                    && (string) $item['id_stok'] === $ids[$index];
                $matchesReference = isset($item['catatan']) && $item['catatan'] === $reference;
                if (($matchesId || $matchesReference) && !empty($item['sn']) && !self::isWaitingText($item['sn'])) {
                    $credentials[] = ($quantity > 1 ? '[Akun '.($index + 1)."]:\n" : '').$item['sn'];
                    $usedRows[$row] = true;
                    break;
                }
            }
        }

        return count($credentials) === $quantity ? implode("\n\n---\n\n", $credentials) : null;
    }

    /**
     * Ekstrak SN / detail akun dari berbagai kemungkinan format respon API
     */
    public static function extractSnFromData($data): ?string
    {
        if (is_string($data)) {
            $trimmed = trim($data);
            if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) || 
                (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return self::extractSnFromData($decoded);
                }
            }
            if (strlen($trimmed) >= 3 && !self::isWaitingText($trimmed)) {
                return $trimmed;
            }
            return null;
        }

        if (!is_array($data)) return null;

        // 1. Cek format khusus 'accounts' dari Premku: [{"username": "...", "password": "..."}]
        if (isset($data['accounts']) && is_array($data['accounts'])) {
            $formattedAccounts = [];
            foreach ($data['accounts'] as $acc) {
                if (is_array($acc)) {
                    $u = $acc['username'] ?? $acc['email'] ?? $acc['user'] ?? '';
                    $p = $acc['password'] ?? $acc['pass'] ?? '';
                    if (!empty($u) || !empty($p)) {
                        $formattedAccounts[] = "Email / User: {$u}\nPassword: {$p}";
                    }
                } elseif (is_string($acc) && !self::isWaitingText($acc)) {
                    $formattedAccounts[] = trim($acc);
                }
            }
            if (!empty($formattedAccounts)) {
                return implode("\n---\n", $formattedAccounts);
            }
        }

        $keys = ['serial_number', 'sn', 'akun', 'account', 'accounts', 'credentials', 'catatan', 'keterangan', 'license', 'pin', 'data'];

        foreach ($keys as $k) {
            if (isset($data[$k])) {
                $val = $data[$k];
                if (is_array($val)) {
                    $nested = self::extractSnFromData($val);
                    if (!empty($nested)) return $nested;

                    $lines = [];
                    foreach ($val as $subKey => $subVal) {
                        if (is_scalar($subVal) && !empty($subVal)) {
                            $lines[] = ucfirst((string)$subKey) . ": " . $subVal;
                        }
                    }
                    if (!empty($lines)) return implode("\n", $lines);
                } elseif (is_string($val) && strlen(trim($val)) >= 3) {
                    $clean = trim($val);
                    if ((str_starts_with($clean, '{') && str_ends_with($clean, '}')) || 
                        (str_starts_with($clean, '[') && str_ends_with($clean, ']'))) {
                        $decoded = json_decode($clean, true);
                        if (is_array($decoded)) {
                            $nested = self::extractSnFromData($decoded);
                            if (!empty($nested)) return $nested;
                        }
                    }
                    if (!self::isWaitingText($clean)) {
                        return $clean;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Cek apakah teks tersebut hanyalah status sementara/tunggu, bukan akun asli
     */
    public static function isWaitingText(?string $text): bool
    {
        if (empty($text)) return true;
        $lower = strtolower(trim($text));
        
        $waitingWords = [
            'pending',
            'process',
            'processing',
            'menunggu',
            'sedang diproses',
            'admin akan segera',
            'stok akun otomatis saat ini sedang kosong',
        ];

        foreach ($waitingWords as $w) {
            if (str_contains($lower, $w)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Menangani kondisi saat stok produk habis karena kalah rebutan dengan pembeli lain.
     * Mengembalikan dana 100% secara otomatis ke saldo akun pembeli terdaftar (Auto-Refund).
     */
    public static function handleOutOfStockAutoRefund(Order $order, Product $product): array
    {
        return DB::transaction(function () use ($order, $product) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (!in_array(strtolower($locked->payment_status), ['paid', 'success', 'settlement'], true)
                || in_array(strtolower($locked->order_status), ['refunded', 'completed', 'success', 'lunas'], true)) {
                return self::existingResult($locked);
            }

            return self::refundClaimedOrder($locked, $product);
        });
    }

    private static function refundClaimedOrder(Order $order, Product $product): array
    {
        $refundAmount = (int) ($order->total_amount ?: ($order->price ?: 0));
        $buyer = $order->user_id ? User::find($order->user_id) : null;

        if ($buyer) {
            // A. PEMBELI MEMILIKI AKUN TERDAFTAR (AUTO-REFUND KE SALDO INSTAN)
            $buyer->increment('balance', $refundAmount);

            TransactionLog::create([
                'user_id'     => $buyer->id,
                'type'        => 'refund',
                'amount'      => $refundAmount,
                'description' => 'Auto-Refund Saldo: Stok ' . $product->name . ' habis terbeli bersamaan (Invoice #' . $order->invoice_number . ')',
            ]);

            $refundNotice = "⚠️ STOK HABIS SAAT PEMBAYARAN\n"
                          . "Mohon maaf, stok produk ini baru saja habis terbeli bersamaan oleh pembeli lain yang menyelesaikan pembayaran terlebih dahulu.\n\n"
                          . "✅ DANA ANDA SEBESAR Rp " . number_format($refundAmount, 0, ',', '.') . " TELAH OTOMATIS KAMI REFUND 100% KE SALDO AKUN NANDARSTORE ANDA.\n"
                          . "Saldo dapat Anda gunakan langsung untuk membeli produk lain atau melakukan penarikan.";

            $order->sn_or_account   = $refundNotice;
            $order->account_details = $refundNotice;
            $order->order_status    = 'refunded';
            $order->payment_status  = 'paid';
            $order->notes           = ($order->notes ? $order->notes . ' | ' : '') . 'Auto-Refund 100% ke Saldo Akun (Stok habis rebutan).';
            $order->save();

            Log::info("[AUTO-REFUND-SUCCESS] Order {$order->invoice_number}: Saldo Rp {$refundAmount} telah dikembalikan ke user {$buyer->email}.");

            // Kirim notifikasi WhatsApp ke pembeli bahwa dana telah di-refund
            try {
                self::sendRefundWaNotification($order, $buyer, $refundAmount);
            } catch (\Exception $e) {
                Log::error("[AUTO-REFUND-WA-ERROR] " . $e->getMessage());
            }

            return [
                'status'        => 'refunded',
                'is_ready'      => false,
                'is_refunded'   => true,
                'refund_amount' => $refundAmount,
                'sn_or_account' => $refundNotice,
                'message'       => 'Stok produk habis terbeli bersamaan. Dana telah otomatis di-refund ke Saldo Akun Anda.',
            ];
        } else {
            // B. PEMBELI TAMU (GUEST) TANPA AKUN (MEMERLUKAN REFUND MANUAL VIA WA)
            $guestNotice = "⚠️ STOK HABIS SAAT PEMBAYARAN\n"
                         . "Mohon maaf, stok produk ini baru saja habis terbeli bersamaan oleh pembeli lain.\n\n"
                         . "Hubungi CS Admin NandarStore via WhatsApp untuk proses pengembalian dana (refund) langsung ke rekening Bank / E-Wallet Anda senilai Rp " . number_format($refundAmount, 0, ',', '.') . ".";

            $order->sn_or_account   = $guestNotice;
            $order->account_details = $guestNotice;
            $order->order_status    = 'processing';
            $order->payment_status  = 'paid';
            $order->notes           = ($order->notes ? $order->notes . ' | ' : '') . '[PERLU REFUND MANUAL] Stok habis terbeli bersamaan. Pembeli Tamu (Guest).';
            $order->save();

            // Kirim notifikasi darurat ke WhatsApp Admin
            try {
                self::sendAdminRefundAlert($order, $refundAmount);
            } catch (\Exception $e) {
                Log::error("[ADMIN-REFUND-ALERT-ERROR] " . $e->getMessage());
            }

            return [
                'status'        => 'processing',
                'is_ready'      => false,
                'is_refunded'   => false,
                'sn_or_account' => $guestNotice,
                'message'       => 'Stok produk habis. Hubungi Admin untuk pengembalian dana rekening/e-wallet.',
            ];
        }
    }

    /**
     * Kirim pesan WhatsApp otomatis ke pembeli bahwa dana di-refund ke saldo akun
     */
    public static function sendRefundWaNotification(Order $order, User $buyer, $refundAmount)
    {
        $targetWa = $order->whatsapp ?: $buyer->whatsapp;
        if (empty($targetWa)) return false;

        $nominalFormatted = number_format($refundAmount, 0, ',', '.');
        $adminWa = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';

        $msg = "⚠️ *PEMBERITAHUAN PENGEMBALIAN DANA (REFUND OTOMATIS)*\n\n"
             . "Halo *{$buyer->name}*,\n"
             . "Terima kasih telah bertransaksi di *NandarStore*.\n\n"
             . "Kami menginformasikan bahwa pesanan Anda:\n"
             . "• No. Invoice: *#{$order->invoice_number}*\n"
             . "• Layanan: *{$order->product_name}*\n\n"
             . "❌ *STATUS STOK:*\n"
             . "Stok terakhir produk tersebut baru saja habis terbeli bersamaan oleh pembeli lain yang menyelesaikan pembayaran beberapa milidetik lebih awal.\n\n"
             . "✅ *REFUND 100% TELAH BERHASIL:*\n"
             . "Dana Anda sebesar *Rp {$nominalFormatted}* telah otomatis kami kembalikan 100% ke *Saldo Akun NandarStore* Anda saat ini juga!\n\n"
             . "👉 Cek Saldo Anda di: " . url('/profile') . "\n"
             . "Saldo tersebut dapat langsung Anda gunakan kembali untuk membeli produk lain tanpa biaya tambahan.\n\n"
             . "Mohon maaf atas ketidaknyamanan ini. Jika ada pertanyaan, hubungi CS kami: https://wa.me/" . preg_replace('/[^0-9]/', '', $adminWa);

        return WebhookController::sendWaMessage($targetWa, $msg);
    }

    /**
     * Kirim notifikasi darurat ke admin jika pembeli tamu membutuhkan refund manual
     */
    public static function sendAdminRefundAlert(Order $order, $refundAmount)
    {
        $adminWa = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
        if (empty($adminWa)) return false;

        $targetWa = preg_replace('/[^0-9]/', '', $adminWa);
        if (str_starts_with($targetWa, '0')) $targetWa = '62' . substr($targetWa, 1);

        $nominalFormatted = number_format($refundAmount, 0, ',', '.');

        $msg = "🚨 *ALERT ADMIN: KEBUTUHAN REFUND MANUAL (STOK HABIS)*\n\n"
             . "Halo Admin, terjadi rebutan stok produk pada pesanan berikut:\n"
             . "• No. Invoice: *#{$order->invoice_number}*\n"
             . "• Produk: *{$order->product_name}*\n"
             . "• Nominal Terbayar: *Rp {$nominalFormatted}*\n"
             . "• Pembeli: *" . ($order->buyer_name ?: 'Tamu') . "*\n"
             . "• WhatsApp Pembeli: *" . ($order->whatsapp ?: '-') . "*\n\n"
             . "⚠️ *KETERANGAN:*\n"
             . "Pembeli ini membayar saat stok tersisa 1, namun pembeli lain menyelesaikan pembayaran terlebih dahulu. Karena pembeli ini adalah Tamu (Guest) tanpa akun saldo, mohon segera hubungi pembeli dan transfer refund manual ke rekening/e-wallet mereka.";

        return WebhookController::sendWaMessage($targetWa, $msg);
    }
}
