<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // 1. VERIFIKASI KEAMANAN (Sama persis seperti PHP Native Anda)
        $webhook_secret = 'nandar-nandar-nandar-tea-tea';
        $signature_header = $request->header('X-Callback-Signature');
        $event_header = $request->header('X-Callback-Event');

        $payload_raw = $request->getContent();
        $payload = json_decode($payload_raw, true);

        // Abaikan jika tidak valid
        if (!$payload) return response()->json(['error' => 'Invalid Payload'], 400);

        $expected_signature = hash_hmac('sha256', $payload_raw, $webhook_secret);
        if (!is_string($signature_header) || !hash_equals($expected_signature, $signature_header)) {
            return response()->json(['error' => 'Akses Ditolak (Signature Salah)'], 403);
        }

        if ($event_header === 'payment.success' && ($payload['status'] ?? '') === 'terbayar') {
            $orderId = $payload['orderId'] ?? null;
            if (!is_string($orderId) || $orderId === '') {
                return response()->json(['error' => 'Invalid orderId'], 400);
            }
            $order = Order::where('invoice_number', $orderId)->first();
            if ($order) {
                // Only the first payment confirmation changes payment state.
                // A replay must never reset a completed/refunded order.
                Order::whereKey($order->id)
                    ->whereRaw("LOWER(payment_status) IN ('pending', 'unpaid', 'expired')")
                    ->whereRaw("LOWER(order_status) NOT IN ('completed', 'success', 'lunas', 'refunded', 'canceled', 'cancelled')")
                    ->update(['payment_status' => 'paid', 'order_status' => 'processing']);
                \App\Services\OrderFulfillmentService::fulfillOrder($order);
            }
        }

        // Selalu kembalikan respon 200 OK ke payment gateway agar tidak dianggap timeout/error
        return response()->json(['message' => 'Webhook Berhasil Diterima'], 200);
    }

    public function handleTopup(Request $request) {
        $payload = $request->all();

        // Cari order ID dari payload
        $orderId = $payload['orderId'] ?? $payload['order_id'] ?? $payload['reference'] ?? null;
        $statusRaw = strtoupper($payload['status'] ?? $payload['payment_status'] ?? '');

        if (!$orderId) return response()->json(['message' => 'Invalid payload'], 400);

        $topup = \App\Models\TopupRequest::where('order_id_gateway', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        if (!$topup) {
            return response()->json(['message' => 'Topup not found'], 404);
        }

        if ($topup->status === 'paid' || $topup->status === 'success') {
            return response()->json(['message' => 'OK - already processed']);
        }

        if (in_array($statusRaw, ['PAID', 'SUCCESS', 'LUNAS', 'SETTLEMENT', 'TERBAYAR'])) {
            $topup->update(['status' => 'paid']);

            // Tambah saldo user
            $user = \App\Models\User::find($topup->user_id);
            if ($user) {
                $user->increment('balance', $topup->amount);
                \App\Models\TransactionLog::create([
                    'user_id'     => $user->id,
                    'type'        => 'topup',
                    'amount'      => $topup->amount,
                    'description' => 'Top Up Saldo via QRIS - ' . $topup->invoice_number,
                ]);
            }
        } elseif (in_array($statusRaw, ['EXPIRED', 'FAILED', 'CANCELED'])) {
            $topup->update(['status' => 'expired']);
        }

        return response()->json(['message' => 'OK']);
    }

    public function handleResellerQris(Request $request) {
        $payload = $request->all();
        $orderId = $payload['orderId'] ?? $payload['order_id'] ?? $payload['reference'] ?? null;
        $statusRaw = strtoupper($payload['status'] ?? $payload['payment_status'] ?? '');

        if (!$orderId) return response()->json(['message' => 'Invalid payload'], 400);

        $reg = \App\Models\ResellerRegistration::where('order_id_gateway', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        if (!$reg || $reg->status === 'paid') {
            return response()->json(['message' => 'OK - already processed or not found']);
        }

        if (in_array($statusRaw, ['PAID', 'SUCCESS', 'LUNAS', 'SETTLEMENT', 'TERBAYAR'])) {
            $reg->update(['status' => 'paid']);

            $user = \App\Models\User::find($reg->user_id);
            if ($user) {
                $user->update([
                    'role'      => 'reseller',
                    'is_active' => true,
                ]);

                \App\Models\TransactionLog::create([
                    'user_id'     => $user->id,
                    'type'        => 'reseller_fee',
                    'amount'      => $reg->amount,
                    'description' => 'Aktivasi Akun VIP Reseller - ' . $reg->invoice_number,
                ]);
            }
        } elseif (in_array($statusRaw, ['EXPIRED', 'FAILED', 'CANCELED'])) {
            $reg->update(['status' => 'failed']);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Webhook Callback Asinkron dari KIOSBANK PPOB
     * Menerima notifikasi status akhir transaksi Single Payment
     */
    public function handleKiosbank(Request $request)
    {
        $payloadRaw = $request->getContent();
        Log::info('[KIOSBANK-CALLBACK-RECEIVED] ' . $payloadRaw);

        $payload = $request->all();
        if (empty($payload) && !empty($payloadRaw)) {
            $payload = json_decode($payloadRaw, true) ?: [];
        }

        $rc          = $payload['rc'] ?? null;
        $referenceId = trim((string) ($payload['referenceID'] ?? ($payload['reference_id'] ?? ($payload['data']['noReferensi'] ?? ''))));
        $data        = $payload['data'] ?? [];
        $status      = strtoupper(trim((string) ($data['status'] ?? ($payload['status'] ?? ''))));
        $sn          = $data['sn'] ?? ($data['token'] ?? ($data['noReferensi'] ?? ''));

        if (!$referenceId) {
            return response()->json(['rc' => '99', 'message' => 'Missing referenceID'], 400);
        }

        // Cari Order berdasarkan api_trx_id, invoice_number, atau di dalam catatan
        $order = Order::where('api_trx_id', $referenceId)
            ->orWhere('invoice_number', $referenceId)
            ->orWhere('notes', 'like', "%{$referenceId}%")
            ->first();

        if (!$order) {
            Log::warning("[KIOSBANK-CALLBACK] Order tidak ditemukan untuk reference: {$referenceId}");
            return response()->json(['rc' => '00', 'message' => 'Order not found, acknowledged'], 200);
        }

        if ($order->order_status === 'completed') {
            return response()->json(['rc' => '00', 'message' => 'Success'], 200);
        }

        // Transaksi Berhasil (RC 00 & status BERHASIL)
        if ($rc === '00' && ($status === 'BERHASIL' || $status === 'SUKSES' || empty($status))) {
            $order->order_status   = 'completed';
            $order->payment_status = 'paid';
            $order->provider       = 'Kiosbank';

            $targetNumber = $order->whatsapp ?: 'Pelanggan';
            if (!empty($order->notes) && preg_match('/No Tujuan:\s*([^|]+)/i', $order->notes, $m)) {
                $targetNumber = trim($m[1]);
            }

            $snValue = !empty($sn) ? $sn : ($data['noReferensi'] ?? $referenceId);
            $order->sn_or_account   = "SN/Token: " . $snValue;
            $order->account_details = "Layanan PPOB: " . $order->product_name . "\nTujuan: " . $targetNumber . "\nSN/Token: " . $snValue;
            $order->notes           = ($order->notes ? $order->notes . ' | ' : '') . "Callback Kiosbank: BERHASIL (Ref: {$referenceId})";
            $order->save();

            // Notifikasi WhatsApp jika akun pembeli terverifikasi WA
            try {
                self::sendOrderNotification($order);
            } catch (\Exception $e) {
                Log::error('[KIOSBANK-CALLBACK-WA-ERROR] ' . $e->getMessage());
            }

            Log::info("[KIOSBANK-CALLBACK] Pesanan {$order->invoice_number} berhasil diperbarui ke completed.");
        } elseif ($status === 'GAGAL' || ($rc !== null && $rc !== '00')) {
            $desc = $payload['description'] ?? ($data['description'] ?? 'Transaksi gagal dari provider');
            $order->order_status = 'failed';
            $order->notes = ($order->notes ? $order->notes . ' | ' : '') . "Callback Kiosbank GAGAL (RC {$rc}): {$desc}";
            $order->save();
            Log::warning("[KIOSBANK-CALLBACK] Pesanan {$order->invoice_number} gagal dari provider: {$desc}");
        }

        return response()->json(['rc' => '00', 'message' => 'Success'], 200);
    }

    /**
     * Webhook Callback Resmi dari Premku API (Versi 3.8 Instant + Webhook)
     * Menerima notifikasi realtime 'order.success' dari premku.com
     * Dilengkapi verifikasi keamanan HMAC-SHA256 via X-Premku-Signature
     */
    public function handlePremku(Request $request)
    {
        $rawPayload = $request->getContent();
        Log::info('[PREMKU-WEBHOOK-RECEIVED] ' . $rawPayload);

        // 1. Ambil Signature dari Header (X-Premku-Signature)
        $signature = $request->header('X-Premku-Signature')
            ?? $request->header('x-premku-signature')
            ?? ($_SERVER['HTTP_X_PREMKU_SIGNATURE'] ?? '');

        // 2. Verifikasi HMAC-SHA256 menggunakan API Secret dari panel Admin
        $apiSecret = Setting::where('key', 'premku_api_secret')->value('value');
        if (!empty($apiSecret)) {
            $expectedSig = hash_hmac('sha256', $rawPayload, $apiSecret);
            if (empty($signature) || !hash_equals($expectedSig, $signature)) {
                Log::warning('[PREMKU-WEBHOOK] Akses ditolak: Signature HMAC tidak cocok! Header: ' . $signature);
                return response()->json(['status' => false, 'message' => 'Invalid Signature'], 403);
            }
        } else {
            Log::info('[PREMKU-WEBHOOK] Catatan: premku_api_secret belum diatur di panel admin. Pemeriksaan signature dilewati.');
        }

        // 3. Decode Payload JSON
        $body = json_decode($rawPayload, true);
        if (!$body || !is_array($body)) {
            return response()->json(['status' => false, 'message' => 'Invalid JSON payload'], 400);
        }

        $event = $body['event'] ?? '';
        $data  = $body['data'] ?? [];

        // 4. Proses Transaksi Sukses ('order.success' atau status 'success')
        if ($event === 'order.success' || strtolower($data['status'] ?? '') === 'success') {
            $invoice = $data['invoice'] ?? null; // Invoice transaksi di server Premku
            $refId   = $data['ref_id'] ?? null;  // Invoice toko NandarStore

            if (!$refId && !$invoice) {
                return response()->json(['status' => false, 'message' => 'Missing ref_id and invoice'], 400);
            }

            // Cari pesanan berdasarkan ref_id (invoice toko) atau api_trx_id
            $order = null;
            if ($refId) {
                $order = Order::where('invoice_number', $refId)->first();
            }
            if (!$order && $invoice) {
                $order = Order::where('api_trx_id', $invoice)
                    ->orWhere('invoice_number', $invoice)
                    ->first();
            }

            if (!$order) {
                Log::warning("[PREMKU-WEBHOOK] Pesanan tidak ditemukan untuk ref_id: {$refId} / invoice: {$invoice}");
                return response()->json(['status' => true, 'message' => 'Order not found, acknowledged'], 200);
            }

            // Cek Idempotency: Jika pesanan sudah completed dengan akun valid, jangan proses ulang
            if ($order->order_status === 'completed' && !empty($order->sn_or_account) && !\App\Services\OrderFulfillmentService::isWaitingText($order->sn_or_account)) {
                Log::info("[PREMKU-WEBHOOK] Pesanan {$order->invoice_number} sudah selesai (completed). Acknowledged.");
                return response()->json(['status' => true, 'message' => 'Webhook received (already completed)'], 200);
            }

            // Ekstrak Akun dari data.accounts
            $accounts = $data['accounts'] ?? [];
            $formattedAccounts = [];

            if (is_array($accounts)) {
                foreach ($accounts as $acc) {
                    if (is_array($acc)) {
                        $u = $acc['username'] ?? $acc['email'] ?? $acc['user'] ?? '';
                        $p = $acc['password'] ?? $acc['pass'] ?? '';
                        if (!empty($u) || !empty($p)) {
                            $formattedAccounts[] = "Email / User: {$u}\nPassword: {$p}";
                        }
                    } elseif (is_string($acc) && !\App\Services\OrderFulfillmentService::isWaitingText($acc)) {
                        $formattedAccounts[] = trim($acc);
                    }
                }
            }

            $snText = !empty($formattedAccounts)
                ? implode("\n---\n", $formattedAccounts)
                : (\App\Services\OrderFulfillmentService::extractSnFromData($data) ?? 'Akun berhasil dikirim oleh provider.');

            // Update status pesanan ke database
            $order->sn_or_account   = $snText;
            $order->account_details = $snText;
            $order->order_status    = 'completed';
            $order->payment_status  = 'paid';
            $order->provider        = 'Premku';
            if ($invoice) {
                $order->api_trx_id = (string) $invoice;
            }
            $order->notes = ($order->notes ? $order->notes . ' | ' : '') . "Callback Premku: SUCCESS (Inv: {$invoice})";
            $order->save();

            Log::info("[PREMKU-WEBHOOK] Pesanan {$order->invoice_number} berhasil diproses & diselesaikan via webhook Premku.");

            // Kirim notifikasi WhatsApp otomatis ke pembeli jika nomor terverifikasi
            try {
                self::sendOrderNotification($order);
            } catch (\Exception $e) {
                Log::error('[PREMKU-WEBHOOK-WA-ERROR] ' . $e->getMessage());
            }

        } elseif (in_array(strtolower($data['status'] ?? ''), ['failed', 'canceled', 'gagal'])) {
            $refId   = $data['ref_id'] ?? null;
            $invoice = $data['invoice'] ?? null;
            $order   = $refId ? Order::where('invoice_number', $refId)->first() : null;
            if (!$order && $invoice) {
                $order = Order::where('api_trx_id', $invoice)->first();
            }

            if ($order && $order->order_status !== 'completed') {
                $order->order_status = 'failed';
                $order->notes = ($order->notes ? $order->notes . ' | ' : '') . "Callback Premku GAGAL (Inv: {$invoice})";
                $order->save();
                Log::warning("[PREMKU-WEBHOOK] Pesanan {$order->invoice_number} ditandai gagal dari callback Premku.");
            }
        }

        // Response HTTP 200 OK wajib dikembalikan dalam 4 detik
        return response()->json(['status' => true, 'message' => 'Webhook received'], 200);
    }

    // Webhook Pesan Masuk WA (Bot / Gateway otomatis memverifikasi saat user kirim kode unik)
    public function handleWaIncoming(Request $request) {
        $message = $request->input('message') ?? $request->input('body') ?? $request->input('text') ?? '';
        $code = $request->input('code') ?? '';
        $sender = $request->input('sender') ?? $request->input('raw_sender') ?? '';

        if (empty($code) && !empty($message)) {
            if (preg_match('/(NS-\d{4,6}|VERIF-\d{4,6}|OTP-[A-Z0-9]+|RESET-[A-Z0-9]+)/i', $message, $matches)) {
                $code = strtoupper($matches[1]);
            }
        } else {
            if (preg_match('/(NS-\d{4,6}|VERIF-\d{4,6}|OTP-[A-Z0-9]+|RESET-[A-Z0-9]+)/i', $code, $matches)) {
                $code = strtoupper($matches[1]);
            } else {
                $code = strtoupper(trim($code));
            }
        }

        \Log::info("[WA-INCOMING] Message: '{$message}' | Code: '{$code}' | Sender: '{$sender}'");

        if (!empty($code)) {
            // 1. CEK APAKAH INI KODE PERMINTAAN OTP RESET PASSWORD
            if (str_starts_with($code, 'OTP-') || str_starts_with($code, 'RESET-')) {
                $userOtp = \App\Models\User::where('otp_trigger_code', $code)->first();

                if ($userOtp) {
                    // Cek apakah kode OTP sudah kosong (artinya sudah pernah dipakai reset sebelumnya)
                    if (empty($userOtp->otp_code)) {
                        $userOtp->update([
                            'otp_code'         => null,
                            'otp_trigger_code' => null,
                            'otp_expires_at'   => null,
                        ]);
                        return response()->json([
                            'status'    => 'otp_already_used',
                            'user_name' => $userOtp->name,
                            'whatsapp'  => $userOtp->whatsapp,
                            'message'   => 'Kode OTP ini sudah pernah digunakan sebelumnya dan telah hangus.',
                        ]);
                    }

                    // Cek apakah sudah lewat 15 menit (KADALUARSA / HANGUS)
                    $isExpired = empty($userOtp->otp_expires_at) || \Carbon\Carbon::parse($userOtp->otp_expires_at)->isPast();
                    if ($isExpired) {
                        // HANGUSKAN DI DATABASE LANGSUNG!
                        $userOtp->update([
                            'otp_code'         => null,
                            'otp_trigger_code' => null,
                            'otp_expires_at'   => null,
                        ]);

                        return response()->json([
                            'status'    => 'otp_expired',
                            'user_name' => $userOtp->name,
                            'whatsapp'  => $userOtp->whatsapp,
                            'message'   => 'Kode permintaan OTP sudah hangus karena melebihi batas waktu 15 menit.',
                        ]);
                    }

                    return response()->json([
                        'status'    => 'send_otp',
                        'user_name' => $userOtp->name,
                        'otp_code'  => $userOtp->otp_code,
                        'whatsapp'  => $userOtp->whatsapp,
                    ]);
                } else {
                    return response()->json([
                        'status'    => 'otp_used_or_invalid',
                        'message'   => 'Kode permintaan OTP tidak ditemukan, sudah hangus, atau sudah pernah digunakan.',
                    ]);
                }
            }

            // 2. CEK VERIFIKASI AKUN WHATSAPP
            $user = \App\Models\User::where('wa_verification_code', $code)->first();

            if (!$user && !empty($sender)) {
                $cleanSender = preg_replace('/[^0-9]/', '', $sender);
                $altSender = str_starts_with($cleanSender, '62') ? '0' . substr($cleanSender, 2) : ('62' . substr($cleanSender, 1));
                $user = \App\Models\User::where(function($q) use ($cleanSender, $altSender) {
                    $q->where('whatsapp', 'like', "%{$cleanSender}%")
                      ->orWhere('whatsapp', 'like', "%{$altSender}%");
                })->whereNotNull('wa_verification_code')->first();
            }

            if ($user) {
                // Jangan timpa nomor yang sudah diinput oleh user jika nomornya valid!
                $finalWhatsapp = $user->whatsapp;

                // Jika nomor di akun user kosong atau berisi LID aneh (seperti diawali 5390 atau lebih dari 14 digit)
                $isInvalidLid = empty($finalWhatsapp) || preg_match('/^5390\d+/', $finalWhatsapp) || strlen($finalWhatsapp) > 15;

                if ($isInvalidLid) {
                    // Coba cari nomor asli yang tertulis di format pesan chat ("*Nomor WA:* 08xxx")
                    if (preg_match('/\*Nomor WA:\*\s*([0-9+]+)/i', $message, $phoneMatch)) {
                        $cleanExtracted = preg_replace('/[^0-9]/', '', $phoneMatch[1]);
                        if (str_starts_with($cleanExtracted, '628')) {
                            $cleanExtracted = '08' . substr($cleanExtracted, 3);
                        } elseif (str_starts_with($cleanExtracted, '8')) {
                            $cleanExtracted = '08' . substr($cleanExtracted, 1);
                        }
                        $finalWhatsapp = $cleanExtracted;
                    } elseif (!empty($sender) && !preg_match('/^5390\d+/', $sender) && strlen($sender) <= 15) {
                        $finalWhatsapp = $sender;
                    }
                }

                $user->update([
                    'is_whatsapp_verified' => true,
                    'whatsapp_verified_at' => now(),
                    'wa_verification_code' => null,
                    'whatsapp'             => $finalWhatsapp ?: $user->whatsapp,
                ]);

                return response()->json([
                    'status'    => 'verified',
                    'message'   => 'WhatsApp berhasil diverifikasi otomatis!',
                    'user_id'   => $user->id,
                    'user_name' => $user->name,
                    'whatsapp'  => $user->whatsapp,
                ]);
            }

            // Cek apakah sudah terverifikasi sebelumnya
            if (!empty($sender) && !preg_match('/^5390\d+/', $sender)) {
                $cleanSender = preg_replace('/[^0-9]/', '', $sender);
                $already = \App\Models\User::where('is_whatsapp_verified', true)
                    ->where(function($q) use ($cleanSender) {
                        $q->where('whatsapp', 'like', "%{$cleanSender}%");
                    })->first();
                if ($already) {
                    return response()->json([
                        'status'    => 'already_verified',
                        'user_name' => $already->name,
                        'whatsapp'  => $already->whatsapp,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ignored']);
    }

    /**
     * Kirim Notifikasi Detail Akun & Link Invoice ke WhatsApp Pembeli
     * (KHUSUS PENGGUNA YANG SUDAH TERVERIFIKASI WHATSAPP)
     */
    public static function sendOrderNotification($order) {
        if (!$order) {
            return ['status' => 'error', 'message' => 'Pesanan tidak ditemukan'];
        }

        // 1. Cari data pengguna akun pembeli
        $buyerUser = $order->user_id
            ? \App\Models\User::find($order->user_id)
            : \App\Models\User::where('whatsapp', $order->whatsapp)->first();

        // 2. Cek apakah nomor WhatsApp terverifikasi (SYARAT UTAMA: KHUSUS YANG SUDAH VERIF WA)
        $isVerified = $buyerUser && (bool) $buyerUser->is_whatsapp_verified && !empty($buyerUser->whatsapp);

        if (!$isVerified) {
            Log::info("Pesanan {$order->invoice_number}: Notifikasi WA tidak dikirim karena akun belum terverifikasi WhatsApp.");
            return [
                'status'  => 'not_verified',
                'message' => 'Pengguna belum memverifikasi WhatsApp. Pesan WA otomatis hanya dikirim untuk akun yang sudah diverifikasi.'
            ];
        }

        $targetPhone = $buyerUser->whatsapp;

        // Pastikan kolom nomor WhatsApp pada pesanan sinkron dengan nomor akun terverifikasi
        if ($order->whatsapp !== $targetPhone) {
            $order->update(['whatsapp' => $targetPhone]);
        }

        // 3. Format Konten Pesan Lengkap
        $product = \App\Models\Product::find($order->product_id);
        $productName = $order->product_name ?: ($product ? $product->name : 'Pesanan');
        $nominal = $order->total_amount ?: ($order->price ?: 0);
        $totalFormatted = number_format($nominal, 0, ',', '.');
        $snAkun = $order->sn_or_account ?: ($order->account_details ?: 'Pesanan sedang diproses.');
        $invoiceUrl = $order->invoiceUrl();
        $metode = str_starts_with($order->invoice_number, 'BAL-') ? 'Saldo Akun' : 'QRIS Otomatis';
        $tanggal = $order->created_at ? $order->created_at->format('d M Y, H:i') : now()->format('d M Y, H:i');
        $qty = (int) ($order->quantity ?? 1);
        if ($qty < 1) $qty = 1;

        $isPpob = ($order->product_type === 'ppob')
            || (isset($product) && $product->type === 'ppob')
            || str_starts_with($order->invoice_number, 'PPOB-');

        $adminWa = \App\Models\Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
        $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
        if (str_starts_with($adminWaClean, '0')) {
            $adminWaClean = '62' . substr($adminWaClean, 1);
        }
        $waGroup = \App\Models\Setting::where('key', 'whatsapp_group_link')->value('value') ?: 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H';

        if ($isPpob) {
            $sectionHeader = "⚡ *SERIAL NUMBER (SN) / TOKEN / STRUK:*";
            $sectionWarning = "⚠️ _Simpan nomor SN / Token ini sebagai bukti sah pengisian._";
        } else {
            $sectionHeader = "🔑 *DETAIL AKUN / LISENSI ANDA:*";
            $sectionWarning = "⚠️ _Simpan dan amankan email & password akun ini baik-baik._";
        }

        $pesanWa = "✅ *PEMBAYARAN BERHASIL & PESANAN LUNAS*\n\n"
                 . "Halo *{$buyerUser->name}*, pesanan Anda telah berhasil diproses oleh sistem *NandarStore*.\n\n"
                 . "📋 *DETAIL PESANAN:*\n"
                 . "• No. Invoice: *{$order->invoice_number}*\n"
                 . "• Layanan: *{$productName}*\n"
                 . ($qty > 1 ? "• Jumlah: *{$qty} Pcs*\n" : "")
                 . "• Total Bayar: *Rp {$totalFormatted}*\n"
                 . "• Metode Bayar: *{$metode}*\n"
                 . "• Tanggal: *{$tanggal} WIB*\n\n"
                 . "{$sectionHeader}\n"
                 . "------------------------------------\n"
                 . "{$snAkun}\n"
                 . "------------------------------------\n"
                 . "{$sectionWarning}\n\n"
                 . "📄 *LIHAT / DOWNLOAD INVOICE (PDF):*\n"
                 . "{$invoiceUrl}\n\n"
                 . "💬 *Bantuan CS Admin:* https://wa.me/{$adminWaClean}\n"
                 . "👥 *Grup Komunitas:* {$waGroup}\n\n"
                 . "_Pesan otomatis oleh Sistem Bot WhatsApp NandarStore._";

        $sent = self::sendWaMessage($targetPhone, $pesanWa);

        return [
            'status'  => $sent ? 'success' : 'failed',
            'target'  => $targetPhone,
            'message' => $sent ? 'Invoice dan detail pesanan berhasil dikirim ke WhatsApp!' : 'Gagal mengirim pesan WhatsApp.'
        ];
    }

    // Helper Static untuk Kirim Pesan WhatsApp via Gateway Lokal / Custom
    public static function sendWaMessage($target, $message) {
        if (empty($target) || empty($message)) return false;

        $gatewayUrl = Setting::where('key', 'wa_gateway_url')->value('value') ?: 'http://127.0.0.1:5001';
        $endpoint = rtrim($gatewayUrl, '/') . '/api/send-wa';

        try {
            $response = Http::timeout(6)->post($endpoint, [
                'target'  => $target,
                'message' => $message,
            ]);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning("Gagal kirim pesan WhatsApp ke {$target}: " . $e->getMessage());
            return false;
        }
    }
}
