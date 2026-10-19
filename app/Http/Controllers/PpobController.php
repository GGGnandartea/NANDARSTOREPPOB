<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Models\TransactionLog;
use App\Models\Voucher;
use App\Services\KiosbankService;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PpobController extends Controller
{
    protected KiosbankService $kiosbank;

    public function __construct(KiosbankService $kiosbank)
    {
        $this->kiosbank = $kiosbank;
    }

    /**
     * Dashboard Khusus PPOB (Inertia View)
     */
    public function index()
    {
        $storeName     = Setting::where('key', 'store_name')->value('value') ?: 'NandarStore';
        $storeLogo     = Setting::where('key', 'store_logo')->value('value') ?: null;
        $storeTagline  = Setting::where('key', 'store_tagline')->value('value') ?: 'Pusat Layanan PPOB & Produk Digital Otomatis';
        $adminWhatsapp = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';

        $user = Auth::user();

        // Default initial items untuk pulsa Telkomsel agar saat pertama kali buka langsung tampil cepat
        $initialPulsa = $this->kiosbank->getPulsaPrabayar('21');

        return Inertia::render('Ppob', [
            'store' => [
                'name'     => $storeName,
                'logo'     => $storeLogo,
                'tagline'  => $storeTagline,
                'whatsapp' => $adminWhatsapp,
            ],
            'auth' => [
                'user' => $user ? [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'balance'     => (int) ($user->balance ?? 0),
                    'whatsapp'    => $user->whatsapp,
                    'is_verified' => (bool) ($user->is_whatsapp_verified ?? false),
                ] : null,
            ],
            'initialPulsa' => $initialPulsa,
        ]);
    }

    /**
     * Mengambil daftar produk berdasarkan kategori / operator / prefix
     */
    public function getProducts(Request $request)
    {
        $category = $request->query('category', 'pulsa'); // pulsa, pln, game, ewallet, tagihan
        $prefix   = $request->query('prefix', '21'); // 21 (Telkomsel), 11 (Indosat), etc.

        try {
            if ($category === 'pulsa') {
                $products = $this->kiosbank->getPulsaPrabayar($prefix);
            } else {
                $products = $this->kiosbank->getHargaByPrefix($prefix);
            }

            return response()->json([
                'status'   => 'success',
                'category' => $category,
                'prefix'   => $prefix,
                'data'     => $products,
            ]);
        } catch (\Exception $e) {
            Log::error('[PPOB-GET-PRODUCTS-ERROR] ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data produk: ' . $e->getMessage(),
                'data'    => []
            ], 500);
        }
    }

    /**
     * Cek Tagihan / Inquiry (PLN Pascabayar, PDAM, atau Validasi User ID Game)
     */
    public function inquiry(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|string',
            'customer_id' => 'required|string',
        ]);

        $productId   = $request->input('product_id');
        $customerId  = trim($request->input('customer_id'));
        $zoneId      = $request->input('zone_id'); // opsional (misal MLBB zone)
        $referenceId = $request->input('reference_id');

        try {
            $result = $this->kiosbank->inquiry($productId, $customerId, $referenceId, $zoneId);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('[PPOB-INQUIRY-ERROR] ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal melakukan inquiry tagihan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Proses Checkout / Pembelian PPOB
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'product_code'   => 'required|string',
            'product_name'   => 'required|string',
            'customer_id'    => 'required|string',
            'amount'         => 'required|numeric|min:1000',
            'payment_method' => 'required|in:balance,qris',
        ]);

        $productCode   = $request->input('product_code');
        $productName   = $request->input('product_name');
        $customerId    = trim($request->input('customer_id'));
        $nominal       = (int) $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        $buyerName     = $request->input('buyer_name') ?: (Auth::user()?->name ?: 'Pelanggan PPOB');
        $whatsapp      = $request->input('whatsapp') ?: (Auth::user()?->whatsapp ?: $customerId);
        $email         = $request->input('email') ?: (Auth::user()?->email ?: 'user@nandarstore.com');

        // Validasi integritas harga dari database PpobProduct jika produk terdaftar
        $officialProduct = \App\Models\PpobProduct::where('code', $productCode)->first();
        if ($officialProduct && $officialProduct->price > 0) {
            $isReseller = Auth::check() && Auth::user()->role === 'reseller' && !empty($officialProduct->reseller_price);
            $baseExpectedPrice = (int) ($isReseller ? $officialProduct->reseller_price : $officialProduct->price);
            if ($nominal < $baseExpectedPrice) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validasi keamanan gagal: Nominal transaksi tidak valid atau tidak sesuai katalog resmi.'
                ], 400);
            }
        }

        // Diskon voucher jika ada
        $discount = 0;
        $voucherCode = $request->input('voucher_code');
        if ($voucherCode) {
            $voucher = Voucher::where('code', $voucherCode)->where('is_active', true)->first();
            if ($voucher) {
                $discount = ($voucher->type === 'nominal') ? $voucher->value : ($nominal * $voucher->value) / 100;
                if ($discount >= $nominal) $discount = $nominal - 1;
                $nominal = $nominal - $discount;
                $voucher->increment('used_count');
            }
        }

        // ─── OPSI 1: PEMBAYARAN DENGAN SALDO AKUN ─────────────────────────────
        if ($paymentMethod === 'balance') {
            if (!Auth::check()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Silakan login terlebih dahulu untuk menggunakan pembayaran saldo akun.'
                ], 401);
            }

            $user = Auth::user();
            if ((int) $user->balance < $nominal) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Saldo akun Anda tidak mencukupi (Rp ' . number_format($user->balance, 0, ',', '.') . '). Silakan lakukan top up terlebih dahulu atau gunakan pembayaran QRIS.'
                ], 400);
            }

            // Potong saldo pengguna
            $user->decrement('balance', $nominal);

            $invoiceNumber = 'PPOB-' . strtoupper(Str::random(6)) . '-' . time();

            // Catat log transaksi
            TransactionLog::create([
                'user_id'     => $user->id,
                'type'        => 'debit',
                'amount'      => $nominal,
                'description' => 'Pembelian PPOB: ' . $productName . ' (' . $customerId . ')',
            ]);

            // Buat record Order
            $order = Order::create([
                'user_id'        => $user->id,
                'buyer_name'     => $buyerName,
                'invoice_number' => $invoiceNumber,
                'product_id'     => null,
                'product_name'   => $productName,
                'product_type'   => 'ppob',
                'provider'       => 'Kiosbank',
                'price'          => $nominal + $discount,
                'discount'       => (int) round($discount),
                'total_amount'   => $nominal,
                'payment_status' => 'paid',
                'order_status'   => 'processing',
                'email'          => $email,
                'whatsapp'       => $whatsapp,
                'voucher_code'   => $voucherCode,
                'discount_amount'=> (int) round($discount),
                'notes'          => "No Tujuan: " . $customerId . " | Kode: " . $productCode,
            ]);

            // Eksekusi Fulfillment ke KIOSBANK
            try {
                $fulfillResult = OrderFulfillmentService::fulfillOrder($order);
                $order->refresh();
            } catch (\Exception $e) {
                Log::error('[PPOB-FULFILL-ERROR] ' . $e->getMessage());
            }

            return response()->json([
                'status'         => 'success',
                'invoice_number' => $order->invoice_number,
                'order_status'   => $order->order_status,
                'sn_or_account'  => $order->sn_or_account,
                'message'        => 'Transaksi berhasil! ' . ($order->sn_or_account ? 'SN: ' . $order->sn_or_account : 'Pesanan sedang diproses.'),
                'redirect_url'   => route('order.invoice', $order->invoice_number),
            ]);
        }

        // ─── OPSI 2: PEMBAYARAN VIA QRIS OTOMATIS ─────────────────────────────
        $invoiceNumber = 'PPOB-QRIS-' . strtoupper(Str::random(6)) . '-' . time();
        $callbackUrl   = url('/api/webhook/qris');

        try {
            $qrisRes = Http::timeout(6)->post('http://127.0.0.1:4061/api/order', [
                'nominal'      => (int) round($nominal),
                'callback_url' => $callbackUrl,
            ]);
            $qrisData = $qrisRes->json();
        } catch (\Exception $e) {
            Log::error('[PPOB-QRIS-CONNECTION-ERROR] ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal membuat tagihan QRIS. Pastikan server pembayaran aktif.'
            ], 500);
        }

        $orderIdGateway = $qrisData['data']['orderId'] ?? null;
        $urlQris        = $qrisData['data']['url_qris'] ?? null;
        $totalNominal   = $qrisData['data']['total_nominal'] ?? $nominal;

        if (!$orderIdGateway) {
            return response()->json([
                'status'  => 'error',
                'message' => $qrisData['message'] ?? 'Gagal membuat tagihan QRIS.'
            ], 400);
        }

        $order = Order::create([
            'user_id'        => Auth::id(),
            'buyer_name'     => $buyerName,
            'invoice_number' => $orderIdGateway,
            'product_id'     => null,
            'product_name'   => $productName,
            'product_type'   => 'ppob',
            'provider'       => 'Kiosbank',
            'price'          => $nominal + $discount,
            'discount'       => (int) round($discount),
            'total_amount'   => $totalNominal,
            'payment_status' => 'pending',
            'order_status'   => 'pending',
            'email'          => $email,
            'whatsapp'       => $whatsapp,
            'voucher_code'   => $voucherCode,
            'discount_amount'=> (int) round($discount),
            'notes'          => "No Tujuan: " . $customerId . " | Kode: " . $productCode,
        ]);

        return response()->json([
            'status'         => 'success',
            'invoice_number' => $orderIdGateway,
            'total_nominal'  => $totalNominal,
            'url_qris'       => $urlQris,
            'order_id'       => $orderIdGateway,
            'message'        => 'Tagihan QRIS berhasil dibuat. Silakan selesaikan pembayaran.',
        ]);
    }

    /**
     * Cek status pembayaran dan status pesanan PPOB secara realtime
     */
    public function checkOrderStatus($orderId)
    {
        $order = Order::where('invoice_number', $orderId)->first();

        if (!$order) {
            return response()->json(['status' => 'NOT_FOUND'], 404);
        }

        // Otomatis tandai pesanan QRIS pending lebih dari 15 menit sebagai expired
        if ($order->payment_status === 'pending' && $order->created_at && $order->created_at->diffInMinutes(now()) >= 15) {
            $order->update([
                'payment_status' => 'expired',
                'order_status'   => 'expired',
            ]);
        }

        return response()->json([
            'status'         => $order->order_status,
            'payment_status' => $order->payment_status,
            'invoice_number' => $order->invoice_number,
            'sn_or_account'  => $order->sn_or_account,
            'notes'          => $order->notes,
        ]);
    }
}

