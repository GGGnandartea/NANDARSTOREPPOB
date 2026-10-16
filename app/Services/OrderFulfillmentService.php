<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\LocalAccount;
use App\Models\Setting;
use App\Services\KiosbankService;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    /**
     * Memproses fulfillment pesanan setelah pembayaran terkonfirmasi (Saldo atau QRIS)
     * Untuk produk API (Preflix / Premku), server akan menunggu hingga provider memberikan akun.
     */
    public static function fulfillOrder(Order $order): array
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
            $nominal = (int) ($order->total_amount ?: $order->price);

            $kiosbank = new KiosbankService();
            // Kiosbank mengharuskan referenceID berupa 12 digit angka unik
            $refId = $order->api_trx_id;
            if (empty($refId) || !ctype_digit((string)$refId) || strlen((string)$refId) !== 12) {
                $refId = now()->format('ymd') . str_pad((string) mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
                $order->api_trx_id = $refId;
                $order->save();
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
                    WebhookController::sendOrderNotification($order);
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

            // PRODUK OTOMATIS: Ambil dari stok akun otomatis
            $order->provider = 'Otomatis';
            $account = LocalAccount::where('product_id', $product->id)
                ->where('is_sold', false)
                ->first();

            if ($account) {
                $account->update(['is_sold' => true]);
                $snAkun = "Email: " . $account->email . "\nPassword: " . $account->password;
                $product->update(['stock' => $product->accounts()->where('is_sold', false)->count()]);

                $order->sn_or_account  = $snAkun;
                $order->account_details = $snAkun;
                $order->order_status   = 'completed';
                $order->payment_status = 'paid';
                $order->save();

                // Kirim notifikasi WA
                WebhookController::sendOrderNotification($order);

                return [
                    'status'        => 'completed',
                    'is_ready'      => true,
                    'sn_or_account' => $snAkun,
                    'message'       => 'Akun lokal berhasil dialokasikan.',
                ];
            } else {
                $snAkun = "Stok akun otomatis saat ini sedang kosong. Admin akan segera mengirimkan akun pengganti via WhatsApp/Email.";
                $order->sn_or_account  = $snAkun;
                $order->account_details = $snAkun;
                $order->order_status   = 'processing';
                $order->payment_status = 'paid';
                $order->save();

                return [
                    'status'        => 'processing',
                    'is_ready'      => false,
                    'sn_or_account' => $snAkun,
                    'message'       => 'Stok lokal habis, pesanan dialihkan ke admin.',
                ];
            }
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
                    // Hanya lakukan request pemesanan jika order belum pernah ditembak ke Preflix
                    if (empty($apiTrxId)) {
                        $resOrder = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                            'koneksi'   => 'order',
                            'id_produk' => $idProdukAsli,
                            'id_user'   => $idUser,
                            'key_user'  => $keyUser,
                            'email'     => $order->email,
                            'catatan'   => $order->invoice_number,
                        ])->json();

                        Log::info("[PREFLIX-ORDER-INIT] Invoice: {$order->invoice_number} | Response: " . json_encode($resOrder));

                        // Preflix mengembalikan:
                        // {"status":"success","message":"Order berhasil","data":"{\"id_stok\":187414,\"id_produk\":\"894\",\"serial_number\":\"...\"}"}
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
                            $apiTrxId = $preflixData['id_stok'] 
                                     ?? $preflixData['id_order'] 
                                     ?? $preflixData['id_riwayat'] 
                                     ?? ('PREFLIX-' . time());

                            // Simpan api_trx_id secepatnya agar tidak pernah terulang
                            $order->api_trx_id = (string) $apiTrxId;
                            $order->save();

                            // Ekstrak serial number instan dari Preflix
                            $immediateSn = $preflixData['serial_number'] 
                                        ?? $preflixData['sn'] 
                                        ?? self::extractSnFromData($preflixData ?: $resOrder);

                            if (!empty($immediateSn) && !self::isWaitingText($immediateSn)) {
                                $snAkun = $immediateSn;
                                $isReady = true;
                            }
                        } else {
                            $failMsg = $resOrder['message'] ?? $resOrder['data']['status'] ?? 'Gagal dari provider';
                            Log::warning("[PREFLIX-ORDER-FAILED] Invoice: {$order->invoice_number} | Message: {$failMsg}");

                            // Cek apakah di riwayat Preflix sebenarnya sudah tercatat
                            sleep(1);
                            $riwayatRes = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                                'koneksi'  => 'get_riwayat',
                                'id_user'  => $idUser,
                                'key_user' => $keyUser,
                            ])->json();

                            if (is_array($riwayatRes)) {
                                foreach ($riwayatRes as $item) {
                                    if (isset($item['id_produk']) && (string)$item['id_produk'] === (string)$idProdukAsli) {
                                        if (!empty($item['sn']) && !self::isWaitingText($item['sn'])) {
                                            $snAkun = $item['sn'];
                                            $apiTrxId = $item['id_stok'] ?? $item['id_riwayat'] ?? ('PREFLIX-' . time());
                                            $order->api_trx_id = (string) $apiTrxId;
                                            $order->save();
                                            $isReady = true;
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!$isReady && (str_contains(strtolower($failMsg), 'saldo') || str_contains(strtolower($failMsg), 'forbidden'))) {
                                $order->api_trx_id = 'ERR-BALANCE-' . time();
                                $order->notes = 'Saldo akun Preflix provider sedang tidak mencukupi.';
                                $order->save();
                            }
                        }
                    } else {
                        // Jika sudah ada api_trx_id, ambil dari riwayat
                        $riwayatRes = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                            'koneksi'  => 'get_riwayat',
                            'id_user'  => $idUser,
                            'key_user' => $keyUser,
                        ])->json();

                        if (is_array($riwayatRes)) {
                            foreach ($riwayatRes as $item) {
                                $matchesStok = isset($item['id_stok']) && (string)$item['id_stok'] === (string)$apiTrxId;
                                $matchesProduk = isset($item['id_produk']) && (string)$item['id_produk'] === (string)$idProdukAsli;
                                if ($matchesStok || $matchesProduk) {
                                    if (!empty($item['sn']) && !self::isWaitingText($item['sn'])) {
                                        $snAkun = $item['sn'];
                                        $isReady = true;
                                        break;
                                    }
                                }
                            }
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
                    $resOrder = $premkuService->createOrder($idProdukAsli, 1, $order->invoice_number);
                    Log::info("[PREMKU-ORDER-INIT] Invoice: {$order->invoice_number} | Response: " . json_encode($resOrder));

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

        // JIKA BELUM MEMILIKI api_trx_id KARENA SEBELUMNYA GAGAL TEMBAK (CONTOH: API KEY SALAH SEBELUM DIPERBAIKI), COBA FULFILL LAGI
        if (empty($apiTrxId)) {
            $retry = self::fulfillOrder($order);
            $order->refresh();
            if ($order->order_status === 'completed' || !empty($order->api_trx_id)) {
                return $retry;
            }
            $apiTrxId = $order->api_trx_id;
        }

        // CEK STATUS KE PREFLIX MENGGUNAKAN get_riwayat
        if (str_starts_with($apiProductId, 'PREFLIX-')) {
            $idUser  = Setting::where('key', 'preflix_id_user')->value('value');
            $keyUser = Setting::where('key', 'preflix_key_user')->value('value');
            $idProdukAsli = str_replace('PREFLIX-', '', $apiProductId);

            try {
                $statusRes = Http::withoutVerifying()->asForm()->post('https://h2h.preflix.my.id/app/', [
                    'koneksi'  => 'get_riwayat',
                    'id_user'  => $idUser,
                    'key_user' => $keyUser,
                ])->json();

                if (is_array($statusRes)) {
                    foreach ($statusRes as $item) {
                        $match = false;
                        if (!empty($apiTrxId) && isset($item['id_stok']) && (string)$item['id_stok'] === (string)$apiTrxId) {
                            $match = true;
                        } elseif (isset($item['id_produk']) && (string)$item['id_produk'] === (string)$idProdukAsli) {
                            $match = true;
                        }

                        if ($match && !empty($item['sn']) && !self::isWaitingText($item['sn'])) {
                            $snAkun = $item['sn'];
                            if (empty($order->api_trx_id) && isset($item['id_stok'])) {
                                $order->api_trx_id = (string) $item['id_stok'];
                            }
                            break;
                        }
                    }
                }
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
            $order->sn_or_account   = $snAkun;
            $order->account_details = $snAkun;
            $order->order_status    = 'completed';
            $order->payment_status  = 'paid';
            $order->save();

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
}
