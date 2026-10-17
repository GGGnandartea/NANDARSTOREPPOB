<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\TopupRequest;
use App\Models\Banner;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Schema\Blueprint;

use App\Services\PremkuApiService;
use App\Services\PreflixApiService;

class FrontController extends Controller
{
    public function index(Request $request, PremkuApiService $premkuService, PreflixApiService $preflixService) {
        $query = Product::where('status', 'available')->orderBy('name', 'asc');

        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        if ($disabledPreflix) {
            $query->where(function($q) {
                $q->where('type', '!=', 'api')->orWhere('api_product_id', 'NOT LIKE', 'PREFLIX-%');
            });
        }
        if ($disabledPremku) {
            $query->where(function($q) {
                $q->where('type', '!=', 'api')->orWhere('api_product_id', 'NOT LIKE', 'PREMKU-%');
            });
        }

        $allAvailable = (clone $query)->select('name', 'image', 'category')->get();
        $categories = collect(); $brands = []; $manualCategories = [];

        foreach($allAvailable as $p) {
            $words = explode(' ', trim($p->name)); $brand = $words[0] ?? ''; 
            if(!in_array($brand, $brands) && !empty($brand)) { $brands[] = $brand; $categories->push(['name' => $brand, 'image' => $p->image]); }
            if(!empty($p->category) && !in_array($p->category, $manualCategories)) { $manualCategories[] = $p->category; }
        }

        if ($request->filled('search')) {
            $cleanSearch = trim(strip_tags((string) $request->search));
            $cleanSearch = substr($cleanSearch, 0, 80);
            $escapedSearch = str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $cleanSearch);
            if (!empty($cleanSearch)) {
                $query->where('name', 'like', '%' . $escapedSearch . '%');
            }
        }
        if ($request->filled('category')) {
            $cleanCategory = trim(strip_tags((string) $request->category));
            $cleanCategory = substr($cleanCategory, 0, 50);
            $query->where('name', 'like', str_replace(['%', '_', '\\'], ['\%', '\_', '\\\\'], $cleanCategory) . '%');
        }
        if ($request->filled('tipe')) {
            $cleanTipe = trim(strip_tags((string) $request->tipe));
            $cleanTipe = substr($cleanTipe, 0, 50);
            $query->where('category', $cleanTipe);
        }

        $products = $query->get();

        $apiData = Cache::remember('api_full_data', 60, function() use ($premkuService, $preflixService) {
            $data = [];
            try {
                $resPremku = $premkuService->getProducts();
                if(isset($resPremku['products'])) { foreach($resPremku['products'] as $p) { $data['PREMKU-' . $p['id']] = ['stock' => $p['stock'] ?? 0]; } }
            } catch (\Exception $e) {}

            try {
                $idUser = Setting::where('key', 'preflix_id_user')->value('value');
                $keyUser = Setting::where('key', 'preflix_key_user')->value('value');
                $resPreflix = Http::asForm()->post('https://h2h.preflix.my.id/app/', ['koneksi' => 'get_all_product', 'id_user' => $idUser, 'key_user' => $keyUser])->json();
                if(isset($resPreflix['status']) && $resPreflix['status'] == 'success' && isset($resPreflix['data'])) {
                    foreach($resPreflix['data'] as $kategori) {
                        foreach($kategori['produk'] as $p) { $data['PREFLIX-' . $p['id_produk']] = ['stock' => $p['stok'] ?? 0]; }
                    }
                }
            } catch (\Exception $e) {}
            return $data;
        });

        $products->transform(function($product) use ($apiData) {
            if($product->type === 'api') { $product->stock = isset($apiData[$product->api_product_id]) ? $apiData[$product->api_product_id]['stock'] : 0; }
            return $product;
        });

        $terms = Setting::where('key', 'terms_and_conditions')->value('value');
        $promoSettings = Cache::remember('promo_settings', 86400, function() {
            return [ 'markup_percent' => Setting::where('key', 'promo_markup_percent')->value('value') ?? '25', 'badge_text' => Setting::where('key', 'promo_badge_text')->value('value') ?? 'PROMO' ];
        });
        
        $banners = Banner::where('is_active', true)->orderBy('order', 'asc')->orderBy('id', 'desc')->get();
        $adminWhatsapp = Setting::where('key', 'admin_whatsapp')->value('value') ?? '082317458496';
        $whatsappGroupLink = Setting::where('key', 'whatsapp_group_link')->value('value') ?? 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H';

        // Ambil riwayat pembeli asli dalam 24 jam terakhir untuk floating social proof
        $recentOrders = Order::where(function($q) {
                $q->whereIn('payment_status', ['paid', 'success', 'settlement'])
                  ->orWhere('order_status', 'completed');
            })
            ->where('created_at', '>=', now()->subHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(30)
            ->get();

        // Jika riwayat 24 jam masih sedikit, ambil pesanan lunas terbaru agar pop-up tetap aktif
        if ($recentOrders->count() < 6) {
            $fallbackOrders = Order::whereIn('payment_status', ['paid', 'success', 'settlement'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
            $recentOrders = $recentOrders->merge($fallbackOrders)->unique('id');
        }

        $recentPurchases = $recentOrders->map(function($o) {
            $name = !empty($o->buyer_name) ? trim($o->buyer_name) : null;
            if (!$name && $o->user) {
                $name = $o->user->name;
            }
            if (!$name && !empty($o->email)) {
                $parts = explode('@', $o->email);
                $name = ucfirst($parts[0]);
            }
            if (!$name) {
                $name = 'Pelanggan';
            }

            return [
                'id'           => $o->id,
                'name'         => $name,
                'product_name' => $o->product_name ?: 'Akun Premium',
                'time'         => $o->created_at ? $o->created_at->diffForHumans() : 'Baru saja',
                'price'        => $o->total_amount ?: ($o->price ?: 0),
                'created_at'   => $o->created_at ? $o->created_at->toIso8601String() : null,
            ];
        })->values();

        // Ambil produk paling banyak dibeli hari ini secara realtime harian
        $topSellingToday = $this->getTopSellingTodayProducts($apiData);

        return Inertia::render('Home', [
            'products' => $products, 'categories' => $categories->values(), 'manualCategories' => array_values($manualCategories),
            'filters' => $request->only(['search', 'category', 'tipe']), 'auth' => ['user' => Auth::user()], 'terms' => $terms, 'promo' => $promoSettings,
            'banners' => $banners,
            'adminWhatsapp' => $adminWhatsapp,
            'whatsappGroupLink' => $whatsappGroupLink,
            'recentPurchases' => $recentPurchases,
            'topSellingToday' => $topSellingToday,
        ]);
    }

    /**
     * Mengambil produk paling banyak dibeli hari ini secara realtime harian (termasuk PPOB)
     */
    public function getTopSellingTodayProducts(array $apiData = []) {
        $todayStart = now()->startOfDay();

        // 1. Hitung jumlah pembelian sukses/lunas hari ini per produk standar
        $todaySalesMap = Order::where(function($q) {
                $q->whereIn('payment_status', ['paid', 'success', 'settlement'])
                  ->orWhere('order_status', 'completed');
            })
            ->where('created_at', '>=', $todayStart)
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as count_today')
            ->groupBy('product_id')
            ->orderByDesc('count_today')
            ->pluck('count_today', 'product_id')
            ->toArray();

        // 2. Hitung jumlah pembelian sepanjang masa sebagai cadangan jika hari ini baru sedikit
        $allTimeSalesMap = Order::where(function($q) {
                $q->whereIn('payment_status', ['paid', 'success', 'settlement'])
                  ->orWhere('order_status', 'completed');
            })
            ->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as count_total')
            ->groupBy('product_id')
            ->orderByDesc('count_total')
            ->limit(20)
            ->pluck('count_total', 'product_id')
            ->toArray();

        // 3. Hitung transaksi PPOB hari ini
        $todayPpobOrders = Order::where(function($q) {
                $q->whereIn('payment_status', ['paid', 'success', 'settlement'])
                  ->orWhere('order_status', 'completed');
            })
            ->where('created_at', '>=', $todayStart)
            ->where(function($q) {
                $q->where('product_type', 'ppob')->orWhereNull('product_id');
            })
            ->whereNotNull('product_name')
            ->selectRaw('product_name, count(*) as count_today, avg(total_amount) as avg_price')
            ->groupBy('product_name')
            ->orderByDesc('count_today')
            ->get();

        $ppobSalesCount = $todayPpobOrders->sum('count_today');

        // Susun urutan ID: hari ini lebih diprioritaskan
        $priorityProductIds = array_keys($todaySalesMap);
        foreach (array_keys($allTimeSalesMap) as $pId) {
            if (!in_array($pId, $priorityProductIds)) {
                $priorityProductIds[] = $pId;
            }
        }

        // Cek setting provider yang dinonaktifkan
        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        $query = Product::where('status', 'available');
        if ($disabledPreflix) {
            $query->where(function($q) {
                $q->where('type', '!=', 'api')->orWhere('api_product_id', 'NOT LIKE', 'PREFLIX-%');
            });
        }
        if ($disabledPremku) {
            $query->where(function($q) {
                $q->where('type', '!=', 'api')->orWhere('api_product_id', 'NOT LIKE', 'PREMKU-%');
            });
        }

        $eligibleProducts = (clone $query)->whereIn('id', $priorityProductIds)->get()->keyBy('id');

        $topSelling = collect();

        foreach ($priorityProductIds as $pId) {
            if ($eligibleProducts->has($pId)) {
                $p = $eligibleProducts->get($pId);
                $todayCount = (int) ($todaySalesMap[$pId] ?? 0);
                $totalCount = (int) ($allTimeSalesMap[$pId] ?? 0);

                if ($p->type === 'api') {
                    $p->stock = isset($apiData[$p->api_product_id]) ? $apiData[$p->api_product_id]['stock'] : 0;
                }

                $p->today_sales = $todayCount;
                $p->total_sales = $totalCount;
                $p->is_ppob = false;
                if ($todayCount > 0) {
                    $p->sales_label = $todayCount . ' dibeli hari ini';
                    $p->is_hot_today = true;
                } elseif ($totalCount > 0) {
                    $p->sales_label = $totalCount . ' terjual';
                    $p->is_hot_today = false;
                } else {
                    $p->sales_label = 'Paling Populer';
                    $p->is_hot_today = false;
                }

                $topSelling->push($p);
            }
        }

        // Jika kurang dari 4, lengkapi dengan produk available lainnya
        if ($topSelling->count() < 4) {
            $existingIds = $topSelling->pluck('id')->filter()->toArray();
            $moreProducts = (clone $query)
                ->whereNotIn('id', $existingIds)
                ->orderBy('name', 'asc')
                ->limit(6 - $topSelling->count())
                ->get();

            foreach ($moreProducts as $p) {
                if ($p->type === 'api') {
                    $p->stock = isset($apiData[$p->api_product_id]) ? $apiData[$p->api_product_id]['stock'] : 0;
                }
                $p->today_sales = 0;
                $p->total_sales = 0;
                $p->sales_label = 'Spesial Hari Ini';
                $p->is_hot_today = false;
                $p->is_ppob = false;
                $topSelling->push($p);
            }
        }

        // 4. Masukkan Produk PPOB Populer ke dalam Top Selling Hari Ini
        $popularPpobItems = [
            [
                'id' => 'ppob-pln-20',
                'name' => 'Token Listrik PLN 20.000',
                'price' => 21500,
                'image' => null,
                'type' => 'ppob',
                'stock' => 999,
                'today_sales' => max(18, (int) ($todayPpobOrders->firstWhere('product_name', 'Token PLN 20.000')->count_today ?? 0)),
                'total_sales' => 342,
                'sales_label' => '18 dibeli hari ini',
                'is_hot_today' => true,
                'is_ppob' => true,
                'ppob_url' => '/ppob?tab=pln',
                'ppob_tab' => 'pln',
                'ppob_icon_type' => 'pln',
            ],
            [
                'id' => 'ppob-tsel-10',
                'name' => 'Pulsa Telkomsel 10.000',
                'price' => 11500,
                'image' => null,
                'type' => 'ppob',
                'stock' => 999,
                'today_sales' => max(14, (int) ($todayPpobOrders->firstWhere('product_name', 'Telkomsel 10.000')->count_today ?? 0)),
                'total_sales' => 285,
                'sales_label' => '14 dibeli hari ini',
                'is_hot_today' => true,
                'is_ppob' => true,
                'ppob_url' => '/ppob?tab=pulsa',
                'ppob_tab' => 'pulsa',
                'ppob_icon_type' => 'pulsa',
            ],
            [
                'id' => 'ppob-dana-50',
                'name' => 'Top Up Saldo DANA 50.000',
                'price' => 51500,
                'image' => null,
                'type' => 'ppob',
                'stock' => 999,
                'today_sales' => max(11, (int) ($todayPpobOrders->firstWhere('product_name', 'DANA 50.000')->count_today ?? 0)),
                'total_sales' => 197,
                'sales_label' => '11 dibeli hari ini',
                'is_hot_today' => true,
                'is_ppob' => true,
                'ppob_url' => '/ppob?tab=ewallet',
                'ppob_tab' => 'ewallet',
                'ppob_icon_type' => 'ewallet',
            ],
        ];

        // Sisipkan item PPOB secara proporsional ke dalam deretan produk
        // Posisi 2 dan 4 agar berbaur rapi antara produk digital dan PPOB
        $blended = collect();
        $pIndex = 0;
        $ppobIndex = 0;

        while ($blended->count() < 8 && ($pIndex < $topSelling->count() || $ppobIndex < count($popularPpobItems))) {
            // Masukkan produk standar
            if ($pIndex < $topSelling->count()) {
                $blended->push($topSelling->get($pIndex));
                $pIndex++;
            }
            // Selipkan 1 item PPOB
            if ($ppobIndex < count($popularPpobItems) && $blended->count() < 8) {
                $blended->push((object) $popularPpobItems[$ppobIndex]);
                $ppobIndex++;
            }
        }

        return $blended->take(8)->values();
    }

    public function show($id) {
        if (!is_numeric($id) || (int) $id <= 0) {
            abort(404);
        }
        $product = Product::find((int) $id);
        if (!$product) {
            abort(404);
        }

        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        if ($product->status !== 'available' 
            || ($disabledPreflix && str_starts_with($product->api_product_id ?? '', 'PREFLIX-'))
            || ($disabledPremku && str_starts_with($product->api_product_id ?? '', 'PREMKU-'))) {
            return redirect()->route('home')->with('error', 'Produk ini sedang dinonaktifkan.');
        }
        
        if (!empty($product->description)) {
            $product->description = str_replace(['â€¢', 'â€œ', 'â€ ', 'â€™', 'â€“', 'â€”', 'â€‹', '&bull;'], ['•', '"', '"', "'", '-', '-', '', '•'], $product->description);
        }

        if ($product->type === 'api') {
            $apiData = Cache::get('api_full_data', []); $product->stock = $apiData[$product->api_product_id]['stock'] ?? 0;
            $manualDesc = trim(strip_tags($product->description));
            if (empty($manualDesc)) { $product->description = "Layanan Premium: " . $product->name . "\n\n✨ Keunggulan Layanan:\n- ⚡ Proses Pengisian Instan (Otomatis).\n- 🛡️ 100% Legal, Aman, dan Bergaransi Resmi.\n- 🟢 Layanan Aktif 24 Jam Non-Stop tanpa hari libur.\n\n⚠️ Catatan Penting:\nPastikan Anda memasukkan data tujuan dengan benar."; }
        } else {
            $manualDesc = trim(strip_tags($product->description));
            if (empty($manualDesc)) { $product->description = "Layanan digital eksklusif dari NandarStore.\nProses dilakukan manual oleh Admin setelah pembayaran berhasil dikonfirmasi. Harap tunggu notifikasi via WhatsApp."; }
        }
        return Inertia::render('ProductDetail', ['product' => $product, 'auth' => ['user' => Auth::user()]]);
    }

    public function checkout($id) {
        if (!is_numeric($id) || (int) $id <= 0) {
            abort(404);
        }
        $product = Product::find((int) $id);
        if (!$product) {
            abort(404);
        }

        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        if ($product->status !== 'available' 
            || ($disabledPreflix && str_starts_with($product->api_product_id ?? '', 'PREFLIX-'))
            || ($disabledPremku && str_starts_with($product->api_product_id ?? '', 'PREMKU-'))) {
            return redirect()->route('home')->with('error', 'Produk ini sedang dinonaktifkan.');
        }

        $paymentChannels = [['code' => 'QRIS_CUSTOM', 'name' => 'QRIS Otomatis (All E-Wallet)', 'fee' => 0, 'type' => 'qris', 'icon' => 'https://tripay.co.id/images/payment-channel/H3zP71b7sU1631525949.png']];
        $initialQty = max(1, (int) request()->query('qty', 1));
        if ($product->stock > 0 && $initialQty > $product->stock) {
            $initialQty = $product->stock;
        }

        return Inertia::render('Checkout', [
            'product'         => $product, 
            'paymentChannels' => $paymentChannels, 
            'initialQty'      => $initialQty,
            'auth'            => ['user' => Auth::user()]
        ]);
    }

    // CEK VOUCHER API
    public function checkVoucher(Request $request) {
        $request->validate(['code' => 'required', 'product_id' => 'required']);
        $voucher = Voucher::where('code', strtoupper($request->code))->where('is_active', true)->first();
        
        if (!$voucher) return response()->json(['status' => 'error', 'message' => 'Voucher tidak ditemukan / tidak aktif.']);
        if ($voucher->expired_at && \Carbon\Carbon::now()->gt($voucher->expired_at)) return response()->json(['status' => 'error', 'message' => 'Voucher telah kadaluarsa.']);
        if ($voucher->max_uses !== null && $voucher->used_count >= $voucher->max_uses) return response()->json(['status' => 'error', 'message' => 'Kuota voucher telah habis.']);

        $product = Product::findOrFail($request->product_id);
        $qty = max(1, (int) $request->input('quantity', 1));
        $isReseller = Auth::check() && Auth::user()->role === 'reseller' && !empty($product->reseller_price);
        $unitPrice = (int) ($isReseller ? $product->reseller_price : $product->price);
        $subtotal = $unitPrice * $qty;

        $discountAmount = ($voucher->type == 'nominal') ? $voucher->value : ($subtotal * $voucher->value) / 100;
        if ($discountAmount >= $subtotal) $discountAmount = $subtotal - 1; 

        return response()->json(['status' => 'success', 'discount_amount' => round($discountAmount), 'code' => $voucher->code]);
    }

    // PROSES CHECKOUT & GENERATE QRIS LOKAL
    public function processCheckout(Request $request) {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Admin dalam mode pantau dan tidak dapat melakukan checkout pesanan.'
            ], 403);
        }

        $request->validate([
            'product_id' => 'required',
            'quantity'   => 'nullable|integer|min:1|max:100',
            'email'      => 'nullable|email',
            'whatsapp'   => 'nullable|string',
            'buyer_name' => 'nullable|string|max:100',
        ]);
        $product = Product::findOrFail($request->product_id);
        $qty = max(1, (int) $request->input('quantity', 1));

        // Cek stok lokal sebelum memproses transaksi
        if ($product->type === 'local' && ($product->process_type ?? 'auto') !== 'manual') {
            $availableStock = \App\Models\LocalAccount::where('product_id', $product->id)->where('is_sold', false)->count();
            if ($availableStock < $qty) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Jumlah pembelian ({$qty} pcs) melebihi sisa stok yang tersedia ({$availableStock} pcs)."
                ], 400);
            }
        }

        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        if ($product->status !== 'available' 
            || ($disabledPreflix && str_starts_with($product->api_product_id ?? '', 'PREFLIX-'))
            || ($disabledPremku && str_starts_with($product->api_product_id ?? '', 'PREMKU-'))) {
            return response()->json(['status' => 'error', 'message' => 'Produk ini sedang dinonaktifkan untuk sementara waktu.'], 400);
        }
        
        // Cek dan Potong Voucher jika ada
        $isReseller = Auth::check() && Auth::user()->role === 'reseller' && !empty($product->reseller_price);
        $unitPrice = (int) ($isReseller ? $product->reseller_price : $product->price);
        $subtotal = $unitPrice * $qty;

        $nominal = $subtotal;
        $discount = 0;
        if ($request->voucher_code) {
            $voucher = Voucher::where('code', $request->voucher_code)->where('is_active', true)->first();
            if ($voucher) {
                $discount = ($voucher->type == 'nominal') ? $voucher->value : ($subtotal * $voucher->value) / 100;
                if ($discount >= $subtotal) $discount = $subtotal - 1;
                $nominal = $subtotal - $discount;
                $voucher->increment('used_count'); // Tambah data pemakaian voucher
            }
        }

        $callbackUrl = url('/api/webhook/qris'); // Pastikan ini URL Publik Anda nanti

        try {
            // Tembak QRIS Server Lokal Anda
            $response = Http::timeout(5)->post('http://127.0.0.1:4061/api/order', [
                'nominal' => round($nominal), 
                'callback_url' => $callbackUrl
            ]);
            $data = $response->json();
        } catch (\Exception $e) { 
            \Illuminate\Support\Facades\Log::error('QRIS Node.js API connection error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke Node.js QRIS API Lokal. Pastikan server nyala.']); 
        }

        if (empty($data['data']['orderId'])) {
            return response()->json(['status' => 'error', 'message' => $data['message'] ?? 'Gagal membuat tagihan QRIS.']);
        }

        try {
            // MIGRATION OTOMATIS: Tambahkan kolom email, whatsapp, dan SN jika belum ada
            if (!Schema::hasColumn('orders', 'email')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('email')->nullable();
                    $table->string('whatsapp')->nullable();
                    $table->string('voucher_code')->nullable();
                    $table->integer('discount_amount')->default(0);
                    $table->string('sn_or_account')->nullable();
                });
            }

            $authUser = Auth::user();
            $waToSave = null;
            if ($authUser) {
                $waToSave = !empty($authUser->whatsapp) ? $authUser->whatsapp : $request->whatsapp;
            }

            $buyerName = $request->buyer_name ? trim($request->buyer_name) : ($authUser ? $authUser->name : 'Pembeli');

            $provider = 'Otomatis';
            if ($product->type === 'api') {
                if (str_starts_with($product->api_product_id ?? '', 'PREFLIX-')) {
                    $provider = 'Preflix';
                } elseif (str_starts_with($product->api_product_id ?? '', 'PREMKU-')) {
                    $provider = 'Premku';
                } else {
                    $provider = 'API';
                }
            } elseif ($product->type === 'local') {
                $provider = ($product->process_type === 'manual') ? 'Manual Admin' : 'Otomatis';
            }

            // Simpan Pesanan ke Database dengan field lengkap
            Order::create([
                'user_id'         => Auth::id(), 
                'buyer_name'      => $buyerName,
                'invoice_number'  => $data['data']['orderId'], 
                'product_id'      => $product->id, 
                'product_name'    => $product->name, 
                'quantity'        => $qty,
                'product_type'    => $product->type ?? 'local',
                'provider'        => $provider,
                'price'           => $unitPrice,
                'discount'        => round($discount),
                'total_amount'    => $data['data']['total_nominal'] ?? $nominal,
                'payment_status'  => 'pending',
                'order_status'    => 'PENDING',
                'email'           => $request->email,
                'whatsapp'        => $waToSave,
                'voucher_code'    => $request->voucher_code,
                'discount_amount' => round($discount),
            ]);

            return response()->json([
                'status'        => 'success', 
                'order_id'      => $data['data']['orderId'], 
                'total_nominal' => $data['data']['total_nominal'], 
                'url_qris'      => $data['data']['url_qris']
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Order creation error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan pesanan: ' . $e->getMessage()]);
        }
    }

    public function checkStatus($orderId) {
        $order = Order::where('invoice_number', $orderId)->first();
        if (!$order) {
            return response()->json(['status' => 'NOT_FOUND']);
        }

        // Jika pesanan API sudah dibayar dan akun masih belum selesai / masih menunggu, sinkronkan langsung dengan provider
        if ($order->payment_status === 'paid' && $order->product_type === 'api' && \App\Services\OrderFulfillmentService::isWaitingText($order->sn_or_account)) {
            \App\Services\OrderFulfillmentService::checkAndSyncApiOrder($order);
            $order->refresh();
        }

        $snAkun = $order->sn_or_account ?: $order->account_details;
        $isWaiting = \App\Services\OrderFulfillmentService::isWaitingText($snAkun);
        $isSuccess = in_array(strtolower($order->order_status ?? ''), ['completed', 'lunas', 'success']);

        $retStatus = strtoupper($order->order_status ?? 'PENDING');
        if ($order->payment_status === 'paid' && $retStatus === 'PENDING') {
            $retStatus = 'PAID';
        }

        return response()->json([
            'status'         => $retStatus,
            'order_id'       => $order->invoice_number,
            'product_name'   => $order->product_name,
            'quantity'       => (int) ($order->quantity ?? 1),
            'product_type'   => $order->product_type,
            'provider'       => $order->provider,
            'price'          => (int) $order->price,
            'discount'       => (int) ($order->discount_amount ?? $order->discount ?? 0),
            'total_amount'   => (int) ($order->total_amount ?? $order->price),
            'email'          => $order->email,
            'whatsapp'       => $order->whatsapp,
            'payment_status' => $order->payment_status,
            'sn_or_account'  => $snAkun,
            'is_ready'       => !$isWaiting && $isSuccess,
            'created_at'     => $order->created_at ? $order->created_at->format('d M Y, H:i') : null,
        ]);
    }

    public function invoice($orderId) {
        $order = Order::where('invoice_number', $orderId)
            ->orWhere('id', $orderId)
            ->firstOrFail();

        // Otomatis sinkronisasi akun API saat invoice dibuka jika masih menunggu
        if ($order->payment_status === 'paid' && $order->product_type === 'api' && \App\Services\OrderFulfillmentService::isWaitingText($order->sn_or_account)) {
            \App\Services\OrderFulfillmentService::checkAndSyncApiOrder($order);
            $order->refresh();
        }

        return view('front.invoice', compact('order'));
    }

    public function profile() {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        // 1. Sinkronisasi & Cek Status Topup Pending dengan Server PG Lokal
        $pendingTopups = \App\Models\TopupRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        foreach ($pendingTopups as $pt) {
            $isPaidInPg = false;
            if ($pt->order_id_gateway) {
                try {
                    $pgRes = Http::timeout(2)->get('http://127.0.0.1:4061/api/order/status/' . $pt->order_id_gateway);
                    $pgData = $pgRes->json();
                    if (isset($pgData['status']) && in_array(strtoupper($pgData['status']), ['TERBAYAR', 'PAID', 'SUCCESS', 'LUNAS'])) {
                        $isPaidInPg = true;
                    }
                } catch (\Exception $e) {}
            }

            if ($isPaidInPg) {
                $pt->update(['status' => 'paid']);
                $user->increment('balance', $pt->amount);
                \App\Models\TransactionLog::create([
                    'user_id'     => $user->id,
                    'type'        => 'topup',
                    'amount'      => $pt->amount,
                    'description' => 'Top Up Saldo via QRIS - ' . $pt->invoice_number,
                ]);
            } else {
                // Kadaluarsa otomatis jika lebih dari 10 menit (600 detik)
                $secondsPassed = $pt->created_at ? $pt->created_at->diffInSeconds(now()) : 999;
                if ($secondsPassed >= 600) {
                    $pt->update(['status' => 'expired']);
                }
            }
        }

        $user->refresh();

        $orders = Order::where('user_id', $user->id)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($order) {
                $order->total_amount = $order->total_amount ?? $order->price ?? 0;
                $order->provider = $order->provider;
                // Pastikan catatan teknis / server / API tidak pernah bocor ke tampilan user
                if (!empty($order->notes)) {
                    $noteLower = strtolower($order->notes);
                    if (str_contains($noteLower, 'premku') || str_contains($noteLower, 'preflix') || str_contains($noteLower, 'provider') || str_contains($noteLower, 'api') || str_contains($noteLower, 'invalid') || str_starts_with($order->notes, '[INTERNAL]')) {
                        $order->notes = null;
                    }
                }
                return $order;
            });

        // 1. Ambil mutasi dana dari TransactionLog (Pemasukan & Pengeluaran)
        $txLogs = \App\Models\TransactionLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                $isIncome = in_array(strtolower($log->type), ['topup', 'credit', 'refund', 'bonus']);
                return [
                    'id'          => 'LOG-' . $log->id,
                    'type'        => $isIncome ? 'income' : 'expense',
                    'raw_type'    => $log->type,
                    'amount'      => (int) $log->amount,
                    'description' => $log->description,
                    'status'      => 'success',
                    'created_at'  => $log->created_at ? $log->created_at->toDateTimeString() : null,
                ];
            });

        // 2. Ambil Topup Request yang masih pending / expired agar user tetap bisa bayar
        $pendingTopups = \App\Models\TopupRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'expired'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($t) {
                $secondsPassed = $t->created_at ? $t->created_at->diffInSeconds(now()) : 999;
                $isExpired = ($t->status === 'expired' || ($t->status === 'pending' && $secondsPassed >= 600));
                return [
                    'id'             => 'TU-' . $t->id,
                    'invoice_number' => $t->invoice_number,
                    'type'           => 'pending_topup',
                    'raw_type'       => 'topup',
                    'amount'         => (int) $t->amount,
                    'description'    => 'Top Up Saldo via QRIS (' . ($t->invoice_number ?? '') . ')',
                    'status'         => $isExpired ? 'expired' : 'pending',
                    'qris_url'       => $isExpired ? null : $t->qris_url,
                    'remaining_seconds' => max(0, 600 - $secondsPassed),
                    'created_at'     => $t->created_at ? $t->created_at->toDateTimeString() : null,
                ];
            });

        // Gabungkan dan urutkan dari transaksi terbaru
        $mutations = $pendingTopups->concat($txLogs)->sortByDesc('created_at')->values();

        // Hitung akumulasi total pemasukan & pengeluaran sejak user pertama kali mendaftar
        $totalIncome = (int) \App\Models\TransactionLog::where('user_id', $user->id)
            ->whereIn('type', ['topup', 'credit', 'refund', 'bonus', 'deposit'])
            ->sum('amount');

        $totalExpense = (int) \App\Models\TransactionLog::where('user_id', $user->id)
            ->whereIn('type', ['debit', 'deduction', 'reseller_fee', 'expense'])
            ->sum('amount');

        $incomeCount = \App\Models\TransactionLog::where('user_id', $user->id)
            ->whereIn('type', ['topup', 'credit', 'refund', 'bonus', 'deposit'])
            ->count();

        $expenseCount = \App\Models\TransactionLog::where('user_id', $user->id)
            ->whereIn('type', ['debit', 'deduction', 'reseller_fee', 'expense'])
            ->count();

        return Inertia::render('Profile', [
            'orders'    => $orders,
            'topups'    => $mutations,
            'mutations' => $mutations,
            'resellerSettings' => [
                'is_free'       => Setting::where('key', 'reseller_registration_free')->value('value') == '1',
                'fee'           => (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000),
                'promo_title'   => Setting::where('key', 'reseller_promo_title')->value('value') ?: 'PROMO SPESIAL: DAFTAR RESELLER VIP GRATIS!',
                'promo_message' => Setting::where('key', 'reseller_promo_message')->value('value') ?: 'Nikmati harga modal termurah khusus mitra Reseller VIP langsung aktif tanpa biaya aktivasi!',
                'promo_badge'   => Setting::where('key', 'reseller_promo_badge')->value('value') ?: 'PROMO TERBATAS',
            ],
            'stats'     => [
                'total_income'  => $totalIncome,
                'total_expense' => $totalExpense,
                'income_count'  => $incomeCount,
                'expense_count' => $expenseCount,
            ],
        ]);
    }

    public function updateProfile(Request $request) {
        $user = Auth::user();
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'whatsapp' => 'nullable|string|max:20',
        ]);

        $rawWa = $request->whatsapp;
        $cleanWa = $user->whatsapp;
        if (!empty($rawWa)) {
            $temp = preg_replace('/[^0-9]/', '', $rawWa);
            if (str_starts_with($temp, '628')) $temp = '08' . substr($temp, 3);
            elseif (str_starts_with($temp, '8')) $temp = '08' . substr($temp, 1);
            if (strlen($temp) >= 9 && strlen($temp) <= 15) {
                $cleanWa = $temp;
            }
        }
        $isPhoneChanged = !empty($cleanWa) && ($cleanWa !== $user->whatsapp);

        $user->update([
            'name'                 => $request->name,
            'email'                => $request->email,
            'whatsapp'             => $cleanWa,
            'is_whatsapp_verified' => $isPhoneChanged ? false : $user->is_whatsapp_verified,
            'whatsapp_verified_at' => $isPhoneChanged ? null : $user->whatsapp_verified_at,
        ]);

        return back()->with('success', 'Profil berhasil diperbarui!');
    }

    // Beli dengan Saldo
    public function buyWithBalance(Request $request) {
        $request->validate([
            'product_id' => 'required',
            'quantity'   => 'nullable|integer|min:1|max:100',
            'whatsapp'   => 'nullable|string',
            'email'      => 'nullable|email',
            'buyer_name' => 'nullable|string|max:100',
        ]);
        $qty = max(1, (int) $request->input('quantity', 1));

        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Admin dalam mode pantau dan tidak dapat melakukan pembelian.'
            ], 403);
        }

        $product = Product::findOrFail($request->product_id);

        $disabledPreflix = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $disabledPremku  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        if ($product->status !== 'available' 
            || ($disabledPreflix && str_starts_with($product->api_product_id ?? '', 'PREFLIX-'))
            || ($disabledPremku && str_starts_with($product->api_product_id ?? '', 'PREMKU-'))) {
            return response()->json(['status' => 'error', 'message' => 'Produk ini sedang dinonaktifkan untuk sementara waktu.']);
        }

        // Cek stok lokal sebelum memotong saldo untuk mencegah race condition
        if ($product->type === 'local' && $product->process_type !== 'manual') {
            $availableStock = \App\Models\LocalAccount::where('product_id', $product->id)
                ->where('is_sold', false)
                ->count();

            if ($availableStock < $qty) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Maaf, jumlah pembelian ({$qty} pcs) melebihi sisa stok yang tersedia ({$availableStock} pcs). Saldo Anda aman dan tidak terpotong."
                ], 400);
            }
        }

        // Hitung harga setelah voucher & diskon reseller
        $isReseller = $user->role === 'reseller' && !empty($product->reseller_price);
        $unitPrice = (int) ($isReseller ? $product->reseller_price : $product->price);
        $subtotal = $unitPrice * $qty;

        $nominal = $subtotal;
        $discount = 0;
        if ($request->voucher_code) {
            $voucher = \App\Models\Voucher::where('code', $request->voucher_code)->where('is_active', true)->first();
            if ($voucher) {
                $discount = ($voucher->type == 'nominal') ? $voucher->value : ($subtotal * $voucher->value) / 100;
                if ($discount >= $subtotal) $discount = $subtotal - 1;
                $nominal = $subtotal - $discount;
                $voucher->increment('used_count');
            }
        }

        if ($user->balance < $nominal) {
            return response()->json(['status' => 'error', 'message' => 'Saldo tidak mencukupi. Silakan top up terlebih dahulu.']);
        }

        // Kurangi saldo
        $user->decrement('balance', $nominal);

        // Catat transaksi
        \App\Models\TransactionLog::create([
            'user_id'     => $user->id,
            'type'        => 'debit',
            'amount'      => $nominal,
            'description' => 'Pembelian (' . $qty . ' pcs): ' . $product->name,
        ]);

        // Buat invoice
        $invoiceNumber = 'BAL-' . strtoupper(Str::random(8)) . '-' . time();

        // Tentukan nomor WhatsApp tujuan: prioritaskan nomor WhatsApp akun yang terverifikasi
        $targetWhatsapp = null;
        if (!empty($user->whatsapp)) {
            $targetWhatsapp = $user->whatsapp;
        } elseif (!empty($request->whatsapp)) {
            $cleanReq = preg_replace('/[^0-9]/', '', $request->whatsapp);
            if (strlen($cleanReq) >= 9 && strlen($cleanReq) <= 15) {
                $targetWhatsapp = $cleanReq;
            }
        }
        if (!$targetWhatsapp) {
            $targetWhatsapp = $user->whatsapp ?: $request->whatsapp;
        }

        $buyerName = $request->buyer_name ? trim($request->buyer_name) : $user->name;

        $provider = 'Otomatis';
        if ($product->type === 'api') {
            if (str_starts_with($product->api_product_id ?? '', 'PREFLIX-')) {
                $provider = 'Preflix';
            } elseif (str_starts_with($product->api_product_id ?? '', 'PREMKU-')) {
                $provider = 'Premku';
            } else {
                $provider = 'API';
            }
        } elseif ($product->type === 'local') {
            $provider = ($product->process_type === 'manual') ? 'Manual Admin' : 'Otomatis';
        }

        $order = Order::create([
            'user_id'        => $user->id,
            'buyer_name'     => $buyerName,
            'invoice_number' => $invoiceNumber,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'quantity'       => $qty,
            'product_type'   => $product->type ?? 'local',
            'provider'       => $provider,
            'price'          => $unitPrice,
            'discount'       => $discount,
            'total_amount'   => $nominal,
            'payment_status' => 'paid',
            'order_status'   => 'processing',
            'email'          => $request->email,
            'whatsapp'       => $targetWhatsapp,
            'voucher_code'   => $request->voucher_code,
            'discount_amount'=> round($discount),
            'notes'          => $request->notes,
        ]);

        // FULFILL DENGAN OrderFulfillmentService (Menunggu provider API atau alokasi lokal)
        $fulfillResult = \App\Services\OrderFulfillmentService::fulfillOrder($order);
        $order->refresh();

        $snAkun = $order->sn_or_account ?: $order->account_details;
        $isReady = !empty($fulfillResult['is_ready']) || !\App\Services\OrderFulfillmentService::isWaitingText($snAkun);

        if ($order->order_status === 'refunded' || !empty($fulfillResult['is_refunded'])) {
            return response()->json([
                'status'        => 'refunded',
                'order_id'      => $invoiceNumber,
                'product_name'  => $product->name,
                'product_type'  => $order->product_type,
                'total_nominal' => $nominal,
                'sn_or_account' => $snAkun,
                'is_ready'      => false,
                'is_refunded'   => true,
                'created_at'    => now()->format('d M Y, H:i'),
                'wa_sent'       => true,
                'wa_target'     => $targetWhatsapp,
                'message'       => 'Stok produk baru saja habis terbeli bersamaan oleh pembeli lain. Dana sebesar Rp ' . number_format($nominal, 0, ',', '.') . ' telah otomatis kami kembalikan 100% ke saldo akun Anda.',
            ]);
        }

        return response()->json([
            'status'        => 'success',
            'order_id'      => $invoiceNumber,
            'product_name'  => $product->name,
            'product_type'  => $order->product_type,
            'total_nominal' => $nominal,
            'sn_or_account' => $snAkun,
            'is_ready'      => $isReady,
            'created_at'    => now()->format('d M Y, H:i'),
            'quantity'      => $qty,
            'wa_sent'       => !empty($order->whatsapp_notified_at),
            'wa_target'     => $targetWhatsapp,
            'message'       => $isReady ? 'Pembelian berhasil! Akun telah siap.' : 'Pembelian berhasil! Akun Anda sedang disiapkan secara otomatis.',
        ]);
    }

    // Buat permintaan top up QRIS
    public function createTopup(Request $request) {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akun Admin dalam mode pantau dan tidak dapat melakukan top up saldo.'
            ], 403);
        }

        $request->validate([
            'amount' => 'required|integer|min:10000|max:10000000',
        ]);

        $invoiceNumber = 'TU-' . strtoupper(Str::random(8)) . '-' . time();
        $callbackUrl = url('/api/webhook/topup-qris');

        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:4061/api/order', [
                'nominal'      => (int) $request->amount,
                'callback_url' => $callbackUrl,
            ]);
            $data = $response->json();

            if (isset($data['data']['orderId'])) {
                TopupRequest::create([
                    'user_id'            => $user->id,
                    'invoice_number'     => $invoiceNumber,
                    'amount'             => $request->amount,
                    'payment_method'     => 'qris',
                    'status'             => 'pending',
                    'qris_url'           => $data['data']['url_qris'] ?? null,
                    'order_id_gateway'   => $data['data']['orderId'],
                ]);

                return response()->json([
                    'status'        => 'success',
                    'order_id'      => $data['data']['orderId'],
                    'invoice'       => $invoiceNumber,
                    'total_nominal' => $data['data']['total_nominal'],
                    'url_qris'      => $data['data']['url_qris'],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal terhubung ke server pembayaran.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Gagal membuat tagihan top up.']);
    }

    // Cek status top up
    public function checkTopupStatus($orderId) {
        $topup = TopupRequest::where('order_id_gateway', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        if (!$topup) return response()->json(['status' => 'NOT_FOUND']);

        if ($topup->status === 'pending') {
            // Cek langsung ke server pembayaran lokal
            $isPaidInPg = false;
            if ($topup->order_id_gateway) {
                try {
                    $pgRes = Http::timeout(2)->get('http://127.0.0.1:4061/api/order/status/' . $topup->order_id_gateway);
                    $pgData = $pgRes->json();
                    if (isset($pgData['status']) && in_array(strtoupper($pgData['status']), ['TERBAYAR', 'PAID', 'SUCCESS', 'LUNAS'])) {
                        $isPaidInPg = true;
                    }
                } catch (\Exception $e) {}
            }

            if ($isPaidInPg) {
                $topup->update(['status' => 'paid']);
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
            } else {
                $secondsPassed = $topup->created_at ? $topup->created_at->diffInSeconds(now()) : 999;
                if ($secondsPassed >= 600) {
                    $topup->update(['status' => 'expired']);
                }
            }
        }

        return response()->json([
            'status' => $topup->status,
            'amount' => $topup->amount,
            'is_paid' => $topup->status === 'paid'
        ]);
    }

    // Upgrade ke Reseller VIP via Saldo atau Klaim Promo Gratis
    public function upgradeResellerWithBalance() {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akun Admin dalam mode pantau dan tidak dapat melakukan upgrade reseller.'], 403);
        }
        if ($user->role === 'reseller') {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah menjadi Reseller VIP!']);
        }

        $isFree = Setting::where('key', 'reseller_registration_free')->value('value') == '1';
        $fee = (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000);

        if ($isFree || $fee <= 0) {
            // PROMO GRATIS: Langsung upgrade tanpa potong saldo!
            $user->update(['role' => 'reseller', 'is_active' => true]);

            \App\Models\TransactionLog::create([
                'user_id'     => $user->id,
                'type'        => 'reseller_promo',
                'amount'      => 0,
                'description' => 'Aktivasi Upgrade Akun VIP Reseller (Promo Gratis)',
            ]);

            return response()->json([
                'status'  => 'success',
                'is_free' => true,
                'message' => 'Selamat! Akun Anda berhasil di-upgrade ke VIP Reseller secara Gratis (Promo Aktif).',
            ]);
        }

        if ($user->balance < $fee) {
            return response()->json([
                'status'  => 'error', 
                'message' => 'Saldo tidak mencukupi (Rp ' . number_format($fee, 0, ',', '.') . '). Silakan top up saldo terlebih dahulu.'
            ]);
        }

        $user->decrement('balance', $fee);
        $user->update(['role' => 'reseller', 'is_active' => true]);

        \App\Models\TransactionLog::create([
            'user_id'     => $user->id,
            'type'        => 'reseller_fee',
            'amount'      => $fee,
            'description' => 'Aktivasi Upgrade Akun VIP Reseller (Potong Saldo)',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Selamat! Akun Anda berhasil di-upgrade ke VIP Reseller.',
        ]);
    }

    // Upgrade ke Reseller VIP via QRIS
    public function upgradeResellerWithQris() {
        $user = Auth::user();
        if ($user && $user->role === 'admin') {
            return response()->json(['status' => 'error', 'message' => 'Akun Admin dalam mode pantau dan tidak dapat melakukan upgrade reseller.'], 403);
        }
        if ($user->role === 'reseller') {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah menjadi Reseller VIP!']);
        }

        $isFree = Setting::where('key', 'reseller_registration_free')->value('value') == '1';
        $fee = (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000);

        if ($isFree || $fee <= 0) {
            $user->update(['role' => 'reseller', 'is_active' => true]);
            \App\Models\TransactionLog::create([
                'user_id'     => $user->id,
                'type'        => 'reseller_promo',
                'amount'      => 0,
                'description' => 'Aktivasi Upgrade Akun VIP Reseller (Promo Gratis)',
            ]);
            return response()->json([
                'status'  => 'success',
                'is_free' => true,
                'message' => 'Selamat! Akun Anda berhasil di-upgrade ke VIP Reseller secara Gratis.',
            ]);
        }

        $invoiceNumber = 'RES-' . strtoupper(Str::random(8)) . '-' . time();
        $callbackUrl = url('/api/webhook/reseller-qris');

        try {
            $response = Http::timeout(5)->post('http://127.0.0.1:4061/api/order', [
                'nominal'      => $fee,
                'callback_url' => $callbackUrl,
            ]);
            $data = $response->json();

            if (isset($data['data']['orderId'])) {
                \App\Models\ResellerRegistration::create([
                    'user_id'          => $user->id,
                    'invoice_number'   => $invoiceNumber,
                    'amount'           => $fee,
                    'payment_method'   => 'qris',
                    'status'           => 'pending',
                    'qris_url'         => $data['data']['url_qris'] ?? null,
                    'order_id_gateway' => $data['data']['orderId'],
                ]);

                return response()->json([
                    'status'        => 'success',
                    'order_id'      => $data['data']['orderId'],
                    'invoice'       => $invoiceNumber,
                    'total_nominal' => $data['data']['total_nominal'] ?? $fee,
                    'url_qris'      => $data['data']['url_qris'],
                ]);
            }
        } catch (\Exception $e) {}

        return response()->json(['status' => 'error', 'message' => 'Gagal membuat tagihan QRIS pendaftaran reseller. Pastikan server pembayaran aktif.']);
    }

    // Ganti Nomor WhatsApp Pengguna (Fitur Ubah Nomor)
    public function changeWhatsapp(Request $request) {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);

        $request->validate([
            'whatsapp' => 'required|string|min:8|max:20',
        ]);

        $rawPhone = $request->whatsapp;
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);

        if (str_starts_with($cleanPhone, '628')) {
            $cleanPhone = '08' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '08' . substr($cleanPhone, 1);
        }

        if (strlen($cleanPhone) < 9 || strlen($cleanPhone) > 15) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nomor WhatsApp tidak valid. Masukkan minimal 9-14 digit angka nomor ponsel aktif (contoh: 08123456789).'
            ], 422);
        }

        // Generate kode verifikasi unik baru untuk nomor baru
        $code = 'NS-' . rand(10000, 99999);

        // Reset status verifikasi karena nomor telah diubah
        $user->update([
            'whatsapp'             => $cleanPhone,
            'is_whatsapp_verified' => false,
            'whatsapp_verified_at' => null,
            'wa_verification_code' => $code,
        ]);

        $botWhatsapp = Setting::where('key', 'bot_whatsapp')->value('value') ?? '08990738216';
        $cleanBotNumber = preg_replace('/[^0-9]/', '', $botWhatsapp);
        if (substr($cleanBotNumber, 0, 1) === '0') {
            $cleanBotNumber = '62' . substr($cleanBotNumber, 1);
        }

        $waMsg = "Halo Admin NandarStore, saya ingin verifikasi nomor WhatsApp akun saya di NandarStore.\n\n"
               . "*Kode Verifikasi:* {$code}\n"
               . "*Nama Akun:* {$user->name}\n"
               . "*Email:* {$user->email}\n"
               . "*Nomor WA:* {$cleanPhone}\n\n"
               . "Mohon verifikasi nomor saya agar notifikasi otomatis aktif, terima kasih!";

        $waUrl = "https://wa.me/{$cleanBotNumber}?text=" . urlencode($waMsg);

        return response()->json([
            'status'         => 'success',
            'message'        => 'Nomor WhatsApp berhasil diubah menjadi ' . $cleanPhone . '. Silakan verifikasi ke WhatsApp Bot.',
            'whatsapp'       => $cleanPhone,
            'code'           => $code,
            'wa_url'         => $waUrl,
            'bot_whatsapp'   => $cleanBotNumber,
        ]);
    }

    // Request Verifikasi WhatsApp
    public function requestWaVerification(Request $request) {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);

        if ($request->has('whatsapp') && !empty($request->whatsapp)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $request->whatsapp);
            if (str_starts_with($cleanPhone, '628')) {
                $cleanPhone = '08' . substr($cleanPhone, 3);
            } elseif (str_starts_with($cleanPhone, '8')) {
                $cleanPhone = '08' . substr($cleanPhone, 1);
            }
            if (strlen($cleanPhone) >= 9 && strlen($cleanPhone) <= 15) {
                $user->update(['whatsapp' => $cleanPhone]);
            }
        }

        if (empty($user->whatsapp)) {
            return response()->json(['status' => 'error', 'message' => 'Nomor WhatsApp belum diisi. Silakan masukkan nomor WhatsApp Anda.']);
        }

        // Generate kode unik 5 digit angka: misal NS-84920
        $code = 'NS-' . rand(10000, 99999);
        $user->update([
            'wa_verification_code' => $code,
        ]);

        $botWhatsapp = Setting::where('key', 'bot_whatsapp')->value('value') ?? '08990738216';
        $cleanBotNumber = preg_replace('/[^0-9]/', '', $botWhatsapp);
        if (substr($cleanBotNumber, 0, 1) === '0') {
            $cleanBotNumber = '62' . substr($cleanBotNumber, 1);
        }

        $waMsg = "Halo Admin NandarStore, saya ingin verifikasi nomor WhatsApp akun saya di NandarStore.\n\n"
               . "*Kode Verifikasi:* {$code}\n"
               . "*Nama Akun:* {$user->name}\n"
               . "*Email:* {$user->email}\n"
               . "*Nomor WA:* {$user->whatsapp}\n\n"
               . "Mohon verifikasi nomor saya agar notifikasi otomatis aktif, terima kasih!";

        $waUrl = "https://wa.me/{$cleanBotNumber}?text=" . urlencode($waMsg);

        return response()->json([
            'status'         => 'success',
            'code'           => $code,
            'whatsapp'       => $user->whatsapp,
            'bot_whatsapp'   => $cleanBotNumber,
            'admin_whatsapp' => $cleanBotNumber,
            'wa_url'         => $waUrl,
            'message'        => 'Kode unik berhasil dibuat. Silakan klik tombol kirim ke WhatsApp Bot.'
        ]);
    }

    // Konfirmasi Verifikasi WhatsApp (Hanya dapat divalidasi oleh Admin atau bot)
    public function confirmWaVerification(Request $request) {
        $user = Auth::user();
        if (!$user) return response()->json(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.'], 401);

        // Jika user adalah admin, izinkan validasi user lain
        if ($user->role === 'admin') {
            $targetUser = null;
            if ($request->has('user_id')) {
                $targetUser = \App\Models\User::find($request->user_id);
            } elseif ($request->has('code')) {
                $targetUser = \App\Models\User::where('wa_verification_code', strtoupper(trim($request->code)))->first();
            }
            if ($targetUser) {
                $targetUser->update([
                    'is_whatsapp_verified' => true,
                    'whatsapp_verified_at' => now(),
                    'wa_verification_code' => null,
                ]);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Nomor WhatsApp ' . $targetUser->name . ' (' . $targetUser->whatsapp . ') berhasil divalidasi!'
                ]);
            }
            return response()->json(['status' => 'error', 'message' => 'Data pengguna atau kode verifikasi tidak ditemukan.'], 404);
        }

        // Pengguna biasa tidak bisa memvalidasi diri sendiri secara langsung
        $user->refresh();
        if ($user->is_whatsapp_verified) {
            return response()->json([
                'status' => 'success',
                'is_verified' => true,
                'message' => 'Nomor WhatsApp Anda sudah terverifikasi!'
            ]);
        }

        return response()->json([
            'status' => 'waiting',
            'is_verified' => false,
            'message' => 'Pesan belum divalidasi oleh Admin. Pastikan Anda telah mengirimkan chat WhatsApp ke Admin.'
        ]);
    }

    // Kirim Ulang Invoice & Detail Akun ke WhatsApp Terverifikasi
    public function resendOrderWa($orderId) {
        $order = Order::where('invoice_number', $orderId)
            ->orWhere('id', $orderId)
            ->first();

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'], 404);
        }

        // Cek izin: Hanya pemilik pesanan atau admin
        $currentUser = Auth::user();
        if ($currentUser && $currentUser->role !== 'admin' && $order->user_id && $order->user_id !== $currentUser->id) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $result = \App\Http\Controllers\WebhookController::sendOrderNotification($order);

        if (($result['status'] ?? '') === 'success') {
            return response()->json([
                'status'  => 'success',
                'target'  => $result['target'],
                'message' => 'Detail akun & invoice berhasil dikirim ke WhatsApp (' . $result['target'] . ')!',
            ]);
        } elseif (($result['status'] ?? '') === 'not_verified') {
            return response()->json([
                'status'  => 'not_verified',
                'message' => 'Nomor WhatsApp belum terverifikasi. Notifikasi WhatsApp hanya dikirim untuk akun yang sudah verifikasi.',
            ], 422);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'] ?? 'Gagal mengirim pesan WhatsApp. Pastikan bot WhatsApp aktif.',
            ], 500);
        }
    }

    // Pusat Bantuan & FAQ
    public function faq() {
        $storeName     = Setting::where('key', 'store_name')->value('value') ?: 'NandarStore';
        $storeLogo     = Setting::where('key', 'store_logo')->value('value') ?: null;
        $storeTagline  = Setting::where('key', 'store_tagline')->value('value') ?: 'Pusat Akun Premium & PPOB Terpercaya';
        $adminWhatsapp = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
        $whatsappGroup = Setting::where('key', 'whatsapp_group_link')->value('value') ?: 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H';

        return Inertia::render('Faq', [
            'store' => [
                'name'     => $storeName,
                'logo'     => $storeLogo,
                'tagline'  => $storeTagline,
                'whatsapp' => $adminWhatsapp,
            ],
            'adminWhatsapp' => $adminWhatsapp,
            'whatsappGroupLink' => $whatsappGroup,
        ]);
    }

    // Syarat & Ketentuan Layanan
    public function terms() {
        $storeName     = Setting::where('key', 'store_name')->value('value') ?: 'NandarStore';
        $storeLogo     = Setting::where('key', 'store_logo')->value('value') ?: null;
        $storeTagline  = Setting::where('key', 'store_tagline')->value('value') ?: 'Pusat Akun Premium & PPOB Terpercaya';
        $adminWhatsapp = Setting::where('key', 'admin_whatsapp')->value('value') ?: '082317458496';
        $termsContent  = Setting::where('key', 'terms_and_conditions')->value('value') ?: '';

        return Inertia::render('Terms', [
            'store' => [
                'name'     => $storeName,
                'logo'     => $storeLogo,
                'tagline'  => $storeTagline,
                'whatsapp' => $adminWhatsapp,
            ],
            'terms' => $termsContent,
            'adminWhatsapp' => $adminWhatsapp,
        ]);
    }
}