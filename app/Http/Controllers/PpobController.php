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
use Illuminate\Support\Facades\Cache;
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
        $initialPulsa = []; // Load provider availability asynchronously through /ppob/products.

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
            'cutoff'       => $this->kiosbank->getCutoffStatus(),
        ]);
    }

    /**
     * Mengambil daftar produk berdasarkan kategori / operator / prefix
     */
    public function getProducts(Request $request)
    {
        $category = $request->query('category', 'pulsa'); // pulsa, pln, game, ewallet, tagihan
        $prefix   = $request->query('prefix', '21'); // 21 (Telkomsel), 11 (Indosat), etc.
        $type = $request->validate(['type' => 'sometimes|in:pulsa,data'])['type'] ?? 'pulsa';

        try {
            if ($category === 'pulsa') {
                $products = $this->kiosbank->getPulsaPrabayar($prefix, $type);
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
        if (!$this->kiosbank->isEnabled()) {
            return response()->json(['status' => 'error', 'message' => 'Layanan PPOB sedang dinonaktifkan.'], 503);
        }
        $cutoff = $this->kiosbank->getCutoffStatus();
        if (!empty($cutoff['is_cutoff'])) {
            return response()->json([
                'status'    => 'error',
                'is_cutoff' => true,
                'message'   => $cutoff['message'],
            ], 503);
        }
        $request->validate([
            'product_id'  => 'required|string',
            'customer_id' => 'required|string',
        ]);

        $productId   = $request->input('product_id');
        $customerId  = trim($request->input('customer_id'));
        $zoneId      = $request->input('zone_id'); // opsional (misal MLBB zone)
        $referenceId = $request->input('reference_id');

        try {
            $result = $this->kiosbank->inquiry($productId, $customerId, $referenceId, $zoneId, [
                'periode' => $request->input('periode'),
            ]);

            // Simpan hasil inquiry sebagai sumber nominal otoritatif. Data Kiosbank
            // hanya berlaku 10 menit, sehingga checkout wajib memakai data ini.
            if (($result['status'] ?? '') === 'success' && (int) ($result['total'] ?? 0) > 0) {
                $result['inquiry_key'] = hash('sha256', $productId . '|' . $customerId . '|' . ($result['reference_id'] ?? ''));
                Cache::put('ppob_inquiry:' . $result['inquiry_key'], [
                    'product_id'   => $productId,
                    'customer_id'  => $customerId,
                    'reference_id' => $result['reference_id'] ?? null,
                    'amount'       => (int) ($result['amount'] ?? 0),
                    'admin_fee'    => (int) ($result['admin_fee'] ?? 0),
                    'penalty'      => (int) ($result['penalty'] ?? 0),
                    'total'        => (int) $result['total'],
                    'service_fee'  => (int) ($result['service_fee'] ?? 0),
                    'grand_total'  => (int) ($result['grand_total'] ?? $result['total']),
                    'customer_name'=> $result['customer_name'] ?? '',
                    'kiosbank_mode' => $this->kiosbank->getMode(),
                    'expires_at'   => now()->addMinutes(10)->timestamp,
                ], now()->addMinutes(10));
            }

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
        if (!$this->kiosbank->isEnabled() || !$this->kiosbank->isConfigured()) {
            return response()->json(['status' => 'error', 'message' => 'Layanan PPOB belum tersedia. Hubungi admin.'], 503);
        }
        $cutoff = $this->kiosbank->getCutoffStatus();
        if (!empty($cutoff['is_cutoff'])) {
            return response()->json([
                'status'    => 'error',
                'is_cutoff' => true,
                'message'   => $cutoff['message'],
            ], 503);
        }
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

        // Data tagihan hasil inquiry (PDAM / PLN pascabayar / BPJS). Nominalnya tidak
        // diambil dari client, melainkan dari cache hasil inquiry di server.
        $billAmount    = null;
        $billAdminFee  = null;
        $billPenalty   = null;
        $billTotal     = null;
        $billReference = null;
        $inquiryKey    = $request->input('inquiry_key');
        $isBillPayment = false;

        // Jangan percaya nominal tagihan dari client. Ambil ulang hasil inquiry
        // dari cache server dan tolak bila sudah melewati batas 10 menit.
        if ($inquiryKey) {
            $cachedInquiry = Cache::get('ppob_inquiry:' . $inquiryKey);
            if (!$cachedInquiry
                || $cachedInquiry['product_id'] !== $productCode
                || $cachedInquiry['customer_id'] !== $customerId
                || ($cachedInquiry['expires_at'] ?? 0) < now()->timestamp
                || ($cachedInquiry['kiosbank_mode'] ?? null) !== $this->kiosbank->getMode()
            ) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Data inquiry sudah kedaluwarsa. Silakan lakukan inquiry ulang sebelum membayar.',
                ], 422);
            }

            $billAmount    = (int) $cachedInquiry['amount'];
            $billAdminFee  = (int) $cachedInquiry['admin_fee'];
            $billPenalty   = (int) ($cachedInquiry['penalty'] ?? 0);
            $billTotal     = (int) $cachedInquiry['total'];
            $billReference = $cachedInquiry['reference_id'];
            // Harga jual ke pelanggan = total biller + markup toko.
            $nominal       = (int) ($cachedInquiry['grand_total'] ?? $billTotal);
            $isBillPayment = true;
        }

        // Validasi integritas harga dari database PpobProduct jika produk terdaftar.
        // Produk tagihan tidak ada di katalog karena nominalnya dari inquiry biller.
        $officialProduct = \App\Models\PpobProduct::where('code', $productCode)->first();
        if ($isBillPayment && $officialProduct?->isMobileProduct()) {
            return response()->json(['status' => 'error', 'message' => 'Pilih pulsa atau paket data dari katalog produk.'], 422);
        }
        if ($isBillPayment) $officialProduct = null;
        if (!$isBillPayment && (!$officialProduct || $officialProduct->status !== 'active')) {
            return response()->json(['status' => 'error', 'message' => 'Produk tidak tersedia. Pilih ulang produk PPOB.'], 422);
        }
        $resolvedPricing = null;
        if ($officialProduct) {
            if ($officialProduct->isMobileProduct()) {
                // Recheck before creating an invoice or debiting a balance.
                $providerStatus = ($this->kiosbank->isDeveloperMode() && $officialProduct->price_synced_mode !== $this->kiosbank->getMode())
                    ? []
                    : $this->kiosbank->mobileProviderStatus(true);
                $availability = $this->kiosbank->mobileAvailability($officialProduct, $providerStatus);
                if (!$availability['can_purchase']) {
                    return response()->json(['status' => 'error', 'message' => $availability['availability_message']], 422);
                }
            }
            $resolvedPricing = $this->kiosbank->resolveProductPricing($officialProduct);
            $isReseller = Auth::check() && Auth::user()->role === 'reseller' && !empty($resolvedPricing['reseller_price']);
            $nominal = (int) ($isReseller ? $resolvedPricing['reseller_price'] : $resolvedPricing['price']);
            $productName = $officialProduct->name;
        }
        $providerAmount = $isBillPayment ? $billTotal : (int) ($resolvedPricing['base_price'] ?? $officialProduct->base_price);
        if ($nominal <= 0 || $providerAmount <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Harga produk belum tersedia. Hubungi admin.'], 422);
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
                $voucher->increment('used');
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
                'kiosbank_mode'  => $this->kiosbank->getMode(),
                'provider'       => 'Kiosbank',
                'provider_amount' => $providerAmount,
                'provider_product_code' => $productCode,
                'provider_customer_id' => $customerId,
                'price'          => $nominal + $discount,
                'discount'       => (int) round($discount),
                'total_amount'   => $nominal,
                'bill_amount'    => $isBillPayment ? $billAmount : null,
                'bill_admin_fee' => $isBillPayment ? $billAdminFee : null,
                'bill_penalty'   => $isBillPayment ? $billPenalty : null,
                'bill_total'     => $isBillPayment ? $billTotal : null,
                'bill_reference' => $isBillPayment ? $billReference : null,
                'payment_status' => 'paid',
                'order_status'   => 'processing',
                'email'          => $email,
                'whatsapp'       => $whatsapp,
                'voucher_code'   => $voucherCode,
                'discount_amount'=> (int) round($discount),
                'notes'          => "No Tujuan: " . $customerId . " | Kode: " . $productCode,
            ]);

            // Eksekusi Fulfillment ke KIOSBANK
            $fulfillResult = [];
            try {
                $fulfillResult = OrderFulfillmentService::fulfillOrder($order);
                $order->refresh();
                $user->refresh();
            } catch (\Exception $e) {
                Log::error('[PPOB-FULFILL-ERROR] ' . $e->getMessage());
            }

            if ($inquiryKey) {
                Cache::forget('ppob_inquiry:' . $inquiryKey);
            }

            $isFailed = in_array(strtolower((string) $order->order_status), ['failed', 'refunded'], true);
            $isCompleted = in_array(strtolower((string) $order->order_status), ['completed', 'success', 'lunas'], true);

            return response()->json([
                'status'         => 'success',
                'invoice_number' => $order->invoice_number,
                'invoice_url'    => $order->invoiceUrl(),
                'order_status'   => $order->order_status,
                'is_refunded'    => !empty($fulfillResult['is_refunded']) || $isFailed,
                'user_balance'   => (int) $user->balance,
                'sn_or_account'  => $order->sn_or_account,
                'notes'          => $order->notes,
                'message'        => $isCompleted
                    ? 'Layanan PPOB berhasil diproses!'
                    : ($isFailed
                        ? ($fulfillResult['message'] ?? 'Transaksi gagal diproses oleh operator. Saldo telah dikembalikan 100% ke akun Anda.')
                        : 'Transaksi sedang diproses (Pending) oleh operator.'),
                'redirect_url'   => $order->invoiceUrl(),
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
            'kiosbank_mode'  => $this->kiosbank->getMode(),
            'provider'       => 'Kiosbank',
            'provider_amount' => $providerAmount,
            'provider_product_code' => $productCode,
            'provider_customer_id' => $customerId,
            'price'          => $nominal + $discount,
            'discount'       => (int) round($discount),
            'total_amount'   => $totalNominal,
            'bill_amount'    => $isBillPayment ? $billAmount : null,
            'bill_admin_fee' => $isBillPayment ? $billAdminFee : null,
            'bill_penalty'   => $isBillPayment ? $billPenalty : null,
            'bill_total'     => $isBillPayment ? $billTotal : null,
            'bill_reference' => $isBillPayment ? $billReference : null,
            'payment_status' => 'pending',
            'order_status'   => 'pending',
            'email'          => $email,
            'whatsapp'       => $whatsapp,
            'voucher_code'   => $voucherCode,
            'discount_amount'=> (int) round($discount),
            'notes'          => "No Tujuan: " . $customerId . " | Kode: " . $productCode,
        ]);

        \App\Services\OrderAccessService::grantGuestSession($request, $order);

        return response()->json([
            'status'         => 'success',
            'invoice_number' => $orderIdGateway,
            'invoice_url' => $order->invoiceUrl(),
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
        $order = request()->attributes->get('authorized_order');

        if (!$order) {
            return response()->json(['status' => 'NOT_FOUND'], 404);
        }

        // Otomatis tandai pesanan QRIS pending lebih dari 30 menit sebagai expired
        if (in_array(strtolower((string) $order->payment_status), ['pending', 'unpaid'], true) && $order->created_at && $order->created_at->diffInMinutes(now()) >= 30) {
            Order::whereKey($order->id)
                ->whereRaw("LOWER(payment_status) IN ('pending', 'unpaid')")
                ->whereRaw("LOWER(order_status) IN ('pending', 'unpaid')")
                ->whereNull('fulfillment_started_at')
                ->update(['payment_status' => 'expired', 'order_status' => 'expired']);
            $order->refresh();
        }

        // Jika sudah dibayar tapi status masih processing/pending, cek berkala ke /Services/Check-Status Kiosbank
        if (in_array(strtolower((string) $order->payment_status), ['paid', 'success', 'settlement'], true)
            && in_array(strtolower((string) $order->order_status), ['processing', 'pending'], true)) {
            $throttleKey = 'ppob_poll_checkstatus_' . $order->id;
            if (!Cache::has($throttleKey)) {
                Cache::put($throttleKey, 1, 8);
                try {
                    OrderFulfillmentService::reconcilePendingPpobOrder($order);
                    $order->refresh();
                } catch (\Throwable $e) {
                    Log::warning('[PPOB-POLL-CHECKSTATUS] ' . $e->getMessage());
                }
            }
        }

        $isFailed = in_array(strtolower((string) $order->order_status), ['failed', 'refunded'], true);

        return response()->json([
            'status'         => $order->order_status,
            'order_status'   => $order->order_status,
            'payment_status' => $order->payment_status,
            'is_refunded'    => $isFailed && (bool) $order->user_id,
            'user_balance'   => Auth::check() ? (int) Auth::user()->fresh()->balance : null,
            'invoice_number' => $order->invoice_number,
            'invoice_url'    => $order->invoiceUrl(),
            'sn'             => $order->sn_or_account,
            'sn_or_account'  => $order->sn_or_account,
            'notes'          => $order->notes,
        ]);
    }

    /**
     * Tombol Manual "Cek Status Ulang" (/Services/Check-Status) untuk pesanan PPOB Pending
     */
    public function recheckStatus($orderId)
    {
        $order = request()->attributes->get('authorized_order');

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $res = OrderFulfillmentService::reconcilePendingPpobOrder($order);
        $order->refresh();

        $isFailed = in_array(strtolower((string) $order->order_status), ['failed', 'refunded'], true);
        $isCompleted = in_array(strtolower((string) $order->order_status), ['completed', 'success', 'lunas'], true);

        return response()->json([
            'status'         => 'success',
            'order_status'   => $order->order_status,
            'payment_status' => $order->payment_status,
            'is_ready'       => $isCompleted,
            'is_refunded'    => $isFailed && (bool) $order->user_id,
            'user_balance'   => Auth::check() ? (int) Auth::user()->fresh()->balance : null,
            'invoice_number' => $order->invoice_number,
            'invoice_url'    => $order->invoiceUrl(),
            'sn'             => $order->sn_or_account,
            'sn_or_account'  => $order->sn_or_account,
            'notes'          => $order->notes,
            'message'        => $res['message'] ?? 'Status pesanan telah diperiksa ke server Kiosbank.',
        ]);
    }
}

