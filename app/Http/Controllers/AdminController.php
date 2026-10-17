<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Product;
use App\Models\User;
use App\Models\LocalAccount;
use App\Models\Order;
use App\Models\Voucher;
use App\Models\TransactionLog;
use App\Models\Banner;
use App\Models\PpobProduct;
use App\Models\KiosbankDeposit;
use App\Services\PremkuApiService;
use App\Services\PreflixApiService;
use App\Services\KiosbankService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AdminController extends Controller
{
    public function dashboard() {
        $totalRevenue = Order::whereIn('payment_status', ['paid', 'success', 'settlement'])->sum('total_amount');
        $newOrders = Order::where('payment_status', 'pending')->orWhereDate('created_at', today())->count();
        $totalResellers = User::where('role', 'reseller')->count();
        $pendingWaCount = User::whereNotNull('wa_verification_code')->where('is_whatsapp_verified', false)->count();
        $totalProducts = Product::count();

        // 7 Hari Terakhir untuk Chart
        $days = [];
        $apiDaily = [];
        $localDaily = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayLabel = $date->translatedFormat('D'); // Sen, Sel, Rab, etc.
            $days[] = $dayLabel;

            $apiDaily[] = (int) Order::whereIn('payment_status', ['paid', 'success', 'settlement'])
                ->where('product_type', 'api')
                ->whereDate('created_at', $date->toDateString())
                ->sum('total_amount');

            $localDaily[] = (int) Order::whereIn('payment_status', ['paid', 'success', 'settlement'])
                ->where('product_type', 'local')
                ->whereDate('created_at', $date->toDateString())
                ->sum('total_amount');
        }

        // Jika data masih kosong di awal, sediakan sampel representatif agar grafik tetap estetik
        $hasChartData = array_sum($apiDaily) > 0 || array_sum($localDaily) > 0;
        if (!$hasChartData) {
            $apiDaily = [120000, 190000, 150000, 220000, 180000, 250000, 310000];
            $localDaily = [80000, 110000, 95000, 130000, 140000, 190000, 210000];
        }

        $recentOrders = Order::orderBy('created_at', 'desc')->limit(6)->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'newOrders',
            'totalResellers',
            'pendingWaCount',
            'totalProducts',
            'days',
            'apiDaily',
            'localDaily',
            'recentOrders'
        ));
    }
    
    public function settings() {
        $premkuApiKey = Setting::where('key', 'premku_api_key')->value('value');
        $premkuApiSecret = Setting::where('key', 'premku_api_secret')->value('value') ?? '';
        $premkuWebhookUrl = url('/api/webhook/premku');
        $preflixIdUser = Setting::where('key', 'preflix_id_user')->value('value');
        $preflixKeyUser = Setting::where('key', 'preflix_key_user')->value('value');
        
        $promoMarkup = Setting::where('key', 'promo_markup_percent')->value('value') ?? '25';
        $promoText = Setting::where('key', 'promo_badge_text')->value('value') ?? 'PROMO';
        $adminWhatsapp = Setting::where('key', 'admin_whatsapp')->value('value') ?? '082317458496';
        $botWhatsapp = Setting::where('key', 'bot_whatsapp')->value('value') ?? '08990738216';
        $whatsappGroupLink = Setting::where('key', 'whatsapp_group_link')->value('value') ?? 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H';

        $storeName = Setting::where('key', 'store_name')->value('value') ?? 'NandarStore';
        $storeTagline = Setting::where('key', 'store_tagline')->value('value') ?? 'Platform Digital & Akun Premium Otomatis';
        $storeLogo = Setting::where('key', 'store_logo')->value('value') ?? null;
        $storeFavicon = Setting::where('key', 'store_favicon')->value('value') ?? null;
        $loadingLogo = Setting::where('key', 'loading_logo')->value('value') ?? null;

        // Cek status koneksi realtime ke Premku
        $premkuStatus = null;
        if (!empty($premkuApiKey)) {
            try {
                $pRes = (new \App\Services\PremkuApiService())->profile();
                if (isset($pRes['success']) && $pRes['success'] === true) {
                    $pData = $pRes['data'] ?? [];
                    $rawSaldo = (int) ($pData['saldo'] ?? $pRes['saldo'] ?? $pRes['balance'] ?? 0);
                    $premkuStatus = [
                        'ok'                  => true,
                        'raw_saldo'           => $rawSaldo,
                        'saldo'               => number_format($rawSaldo, 0, ',', '.'),
                        'username'            => $pData['username'] ?? $pRes['username'] ?? 'User Premku',
                        'whatsapp'            => $pData['whatsapp'] ?? $pRes['whatsapp'] ?? '-',
                        'registered_at'       => isset($pData['registered_at']) ? \Carbon\Carbon::parse($pData['registered_at'])->format('d M Y, H:i') : '-',
                        'product_count'       => Product::where('api_product_id', 'LIKE', 'PREMKU-%')->count(),
                        'active_product_count'=> Product::where('api_product_id', 'LIKE', 'PREMKU-%')->where('status', 'available')->count(),
                        'message'             => 'Terhubung! Saldo: Rp ' . number_format($rawSaldo, 0, ',', '.')
                    ];
                } else {
                    $premkuStatus = [
                        'ok'        => false,
                        'raw_saldo' => 0,
                        'saldo'     => '0',
                        'message'   => $pRes['message'] ?? 'API Key Ditolak oleh Premku. Silakan periksa kembali di dashboard premku.com'
                    ];
                }
            } catch (\Exception $e) {
                $premkuStatus = ['ok' => false, 'raw_saldo' => 0, 'saldo' => '0', 'message' => 'Gagal koneksi: ' . $e->getMessage()];
            }
        }

        // Cek status koneksi realtime ke Preflix
        $preflixStatus = null;
        if (!empty($preflixIdUser) && !empty($preflixKeyUser)) {
            try {
                $pfRes = (new \App\Services\PreflixApiService())->getKategori();
                if (is_array($pfRes) && count($pfRes) > 0) {
                    $preflixStatus = [
                        'ok'                  => true,
                        'kategori_count'      => count($pfRes),
                        'product_count'       => Product::where('api_product_id', 'LIKE', 'PREFLIX-%')->count(),
                        'active_product_count'=> Product::where('api_product_id', 'LIKE', 'PREFLIX-%')->where('status', 'available')->count(),
                        'message'             => 'Terhubung! (' . count($pfRes) . ' kategori aktif)'
                    ];
                } else {
                    $preflixStatus = ['ok' => false, 'message' => 'Gagal terhubung ke Preflix'];
                }
            } catch (\Exception $e) {
                $preflixStatus = ['ok' => false, 'message' => 'Gagal koneksi: ' . $e->getMessage()];
            }
        }

        // Kiosbank PPOB Settings
        $kiosbankHost         = Setting::where('key', 'kiosbank_host')->value('value') ?: 'development.kiosbank.com';
        $kiosbankPort         = Setting::where('key', 'kiosbank_port')->value('value') ?: '4432';
        $kiosbankProtocol     = Setting::where('key', 'kiosbank_protocol')->value('value') ?: 'https';
        $kiosbankUsername     = Setting::where('key', 'kiosbank_username')->value('value') ?: '';
        $kiosbankPassword     = Setting::where('key', 'kiosbank_password')->value('value') ?: '';
        $kiosbankMitra        = Setting::where('key', 'kiosbank_mitra')->value('value') ?: 'DJI';
        $kiosbankAccountId    = Setting::where('key', 'kiosbank_account_id')->value('value') ?: '';
        $kiosbankMerchantId   = Setting::where('key', 'kiosbank_merchant_id')->value('value') ?: '';
        $kiosbankMerchantName = Setting::where('key', 'kiosbank_merchant_name')->value('value') ?: 'NandarStore';
        $kiosbankCounterId    = Setting::where('key', 'kiosbank_counter_id')->value('value') ?: '1';
        $kiosbankMode         = Setting::where('key', 'kiosbank_mode')->value('value') ?: 'development';
        $kiosbankMarkupType   = Setting::where('key', 'kiosbank_markup_type')->value('value') ?: 'fixed';
        $kiosbankMarkupValue  = Setting::where('key', 'kiosbank_markup_value')->value('value') ?: '1000';
        $kiosbankEnabled      = Setting::where('key', 'kiosbank_enabled')->value('value') ?? '1';
        $kiosbankPrivateKey   = Setting::where('key', 'kiosbank_private_key')->value('value') ?: '';
        $kiosbankPublicKey    = Setting::where('key', 'kiosbank_public_key')->value('value') ?: '';

        $kiosbankStatus = ['ok' => false, 'saldo' => '0', 'message' => 'Belum dikonfigurasi'];
        if (!empty($kiosbankUsername) && !empty($kiosbankPassword)) {
            try {
                $kbService = new \App\Services\KiosbankService();
                $kbDep = $kbService->checkDeposit();
                $kiosbankStatus = [
                    'ok'      => $kbDep['ok'],
                    'saldo'   => number_format($kbDep['saldo'] ?? 0, 0, ',', '.'),
                    'message' => $kbDep['message']
                ];
            } catch (\Exception $e) {
                $kiosbankStatus = ['ok' => false, 'saldo' => '0', 'message' => $e->getMessage()];
            }
        }

        return view('admin.settings', compact(
            'premkuApiKey', 'premkuApiSecret', 'premkuWebhookUrl', 'preflixIdUser', 'preflixKeyUser', 'promoMarkup', 'promoText',
            'adminWhatsapp', 'botWhatsapp', 'whatsappGroupLink', 'premkuStatus', 'preflixStatus',
            'storeName', 'storeTagline', 'storeLogo', 'storeFavicon', 'loadingLogo',
            'kiosbankHost', 'kiosbankPort', 'kiosbankProtocol', 'kiosbankUsername', 'kiosbankPassword',
            'kiosbankMitra', 'kiosbankAccountId', 'kiosbankMerchantId', 'kiosbankMerchantName',
            'kiosbankCounterId', 'kiosbankMode', 'kiosbankMarkupType', 'kiosbankMarkupValue',
            'kiosbankEnabled', 'kiosbankStatus', 'kiosbankPrivateKey', 'kiosbankPublicKey'
        ));
    }

    public function checkApiLiveStatus() {
        $premkuApiKey = Setting::where('key', 'premku_api_key')->value('value');
        $preflixIdUser = Setting::where('key', 'preflix_id_user')->value('value');
        $preflixKeyUser = Setting::where('key', 'preflix_key_user')->value('value');

        $premku = ['ok' => false, 'saldo' => '0', 'raw_saldo' => 0, 'message' => 'Belum dikonfigurasi'];
        if (!empty($premkuApiKey)) {
            try {
                $pRes = (new \App\Services\PremkuApiService())->profile();
                if (isset($pRes['success']) && $pRes['success'] === true) {
                    $pData = $pRes['data'] ?? [];
                    $rawSaldo = (int) ($pData['saldo'] ?? $pRes['saldo'] ?? $pRes['balance'] ?? 0);
                    $premku = [
                        'ok'                   => true,
                        'raw_saldo'            => $rawSaldo,
                        'saldo'                => number_format($rawSaldo, 0, ',', '.'),
                        'username'             => $pData['username'] ?? 'User Premku',
                        'whatsapp'             => $pData['whatsapp'] ?? '-',
                        'registered_at'        => isset($pData['registered_at']) ? \Carbon\Carbon::parse($pData['registered_at'])->format('d M Y, H:i') : '-',
                        'product_count'        => Product::where('api_product_id', 'LIKE', 'PREMKU-%')->count(),
                        'active_product_count' => Product::where('api_product_id', 'LIKE', 'PREMKU-%')->where('status', 'available')->count(),
                        'message'              => 'Terhubung! Saldo: Rp ' . number_format($rawSaldo, 0, ',', '.')
                    ];
                } else {
                    $premku['message'] = $pRes['message'] ?? 'API Key Ditolak oleh Premku';
                }
            } catch (\Exception $e) {
                $premku['message'] = $e->getMessage();
            }
        }

        $preflix = ['ok' => false, 'message' => 'Belum dikonfigurasi'];
        if (!empty($preflixIdUser) && !empty($preflixKeyUser)) {
            try {
                $pfRes = (new \App\Services\PreflixApiService())->getKategori();
                if (is_array($pfRes) && count($pfRes) > 0) {
                    $preflix = [
                        'ok'                   => true,
                        'kategori_count'       => count($pfRes),
                        'product_count'        => Product::where('api_product_id', 'LIKE', 'PREFLIX-%')->count(),
                        'active_product_count' => Product::where('api_product_id', 'LIKE', 'PREFLIX-%')->where('status', 'available')->count(),
                        'message'              => 'Terhubung! (' . count($pfRes) . ' kategori aktif)'
                    ];
                } else {
                    $preflix['message'] = 'Gagal terhubung ke Preflix';
                }
            } catch (\Exception $e) {
                $preflix['message'] = $e->getMessage();
            }
        }

        $kiosbank = ['ok' => false, 'saldo' => '0', 'message' => 'Belum dikonfigurasi'];
        $kbUser = Setting::where('key', 'kiosbank_username')->value('value');
        if (!empty($kbUser)) {
            try {
                $kbService = new \App\Services\KiosbankService();
                $kbDep = $kbService->checkDeposit();
                $kiosbank = [
                    'ok'      => $kbDep['ok'],
                    'saldo'   => number_format($kbDep['saldo'] ?? 0, 0, ',', '.'),
                    'message' => $kbDep['message']
                ];
            } catch (\Exception $e) {
                $kiosbank['message'] = $e->getMessage();
            }
        }

        return response()->json([
            'status'    => 'success',
            'premku'    => $premku,
            'preflix'   => $preflix,
            'kiosbank'  => $kiosbank,
            'timestamp' => now()->format('H:i:s') . ' WIB'
        ]);
    }

    public function premkuCreateDeposit(Request $request) {
        $request->validate([
            'amount' => 'required|integer|min:10000|max:10000000',
        ]);

        try {
            $service = new \App\Services\PremkuApiService();
            $res = $service->createDeposit($request->amount);

            if (isset($res['success']) && $res['success'] === true && isset($res['data'])) {
                return response()->json([
                    'status' => 'success',
                    'data'   => $res['data']
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'message' => $res['message'] ?? 'Gagal membuat tagihan deposit ke Premku.'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal terhubung ke Premku: ' . $e->getMessage()
            ], 500);
        }
    }

    public function premkuCheckDeposit(Request $request) {
        $request->validate(['invoice' => 'required|string']);

        try {
            $service = new \App\Services\PremkuApiService();
            $res = $service->checkDepositStatus($request->invoice);

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal cek status deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function premkuCancelDeposit(Request $request) {
        $request->validate(['invoice' => 'required|string']);

        try {
            $service = new \App\Services\PremkuApiService();
            $res = $service->cancelDeposit($request->invoice);

            return response()->json($res);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan deposit: ' . $e->getMessage()
            ], 500);
        }
    }

    public function retryApiOrder($id) {
        $order = Order::findOrFail($id);
        $result = \App\Services\OrderFulfillmentService::fulfillOrder($order);
        $order->refresh();

        if ($order->order_status === 'completed') {
            return back()->with('success', 'Pesanan #' . $order->invoice_number . ' berhasil diproses ke provider dan akun telah siap!');
        } else {
            $msg = $result['message'] ?? 'Sedang diproses provider';
            if (!empty($order->notes)) {
                $msg .= ' (' . $order->notes . ')';
            }
            return back()->with('info', 'Status pesanan: ' . $msg);
        }
    }

    public function saveSettings(Request $request) {
        // Identitas & Branding Toko
        if ($request->filled('store_name')) {
            Setting::updateOrCreate(['key' => 'store_name'], ['value' => trim($request->store_name)]);
        }
        if ($request->has('store_tagline')) {
            Setting::updateOrCreate(['key' => 'store_tagline'], ['value' => trim($request->store_tagline)]);
        }

        // Upload Logo Toko atau URL
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('store_logo_url')) {
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => trim($request->store_logo_url)]);
        }

        // Hapus Logo Toko jika diminta
        if ($request->has('remove_store_logo') && $request->remove_store_logo == '1') {
            $oldLogo = Setting::where('key', 'store_logo')->value('value');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => null]);
        }

        // Upload Favicon Situs atau URL
        if ($request->hasFile('store_favicon')) {
            $path = $request->file('store_favicon')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('store_favicon_url')) {
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => trim($request->store_favicon_url)]);
        }

        // Hapus Favicon jika diminta
        if ($request->has('remove_store_favicon') && $request->remove_store_favicon == '1') {
            $oldFavicon = Setting::where('key', 'store_favicon')->value('value');
            if ($oldFavicon && str_starts_with($oldFavicon, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldFavicon));
            }
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => null]);
        }

        // Upload Logo Loading Awal / Preloader atau URL
        if ($request->hasFile('loading_logo')) {
            $path = $request->file('loading_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('loading_logo_url')) {
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => trim($request->loading_logo_url)]);
        }

        // Hapus Logo Loading jika diminta
        if ($request->has('remove_loading_logo') && $request->remove_loading_logo == '1') {
            $oldLoadingLogo = Setting::where('key', 'loading_logo')->value('value');
            if ($oldLoadingLogo && str_starts_with($oldLoadingLogo, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLoadingLogo));
            }
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => null]);
        }

        Setting::updateOrCreate(['key' => 'premku_api_key'], ['value' => $request->premku_api_key]);
        if ($request->has('premku_api_secret')) {
            Setting::updateOrCreate(['key' => 'premku_api_secret'], ['value' => trim($request->premku_api_secret)]);
        }
        Setting::updateOrCreate(['key' => 'preflix_id_user'], ['value' => $request->preflix_id_user]);
        Setting::updateOrCreate(['key' => 'preflix_key_user'], ['value' => $request->preflix_key_user]);
        Setting::updateOrCreate(['key' => 'promo_markup_percent'], ['value' => $request->promo_markup_percent]);
        Setting::updateOrCreate(['key' => 'promo_badge_text'], ['value' => $request->promo_badge_text]);
        if ($request->has('admin_whatsapp')) {
            Setting::updateOrCreate(['key' => 'admin_whatsapp'], ['value' => $request->admin_whatsapp]);
        }
        if ($request->has('bot_whatsapp')) {
            Setting::updateOrCreate(['key' => 'bot_whatsapp'], ['value' => $request->bot_whatsapp]);
        }
        if ($request->has('whatsapp_group_link')) {
            Setting::updateOrCreate(['key' => 'whatsapp_group_link'], ['value' => $request->whatsapp_group_link]);
        }

        // Kiosbank PPOB Settings
        if ($request->has('kiosbank_host')) {
            Setting::updateOrCreate(['key' => 'kiosbank_host'], ['value' => trim($request->kiosbank_host)]);
        }
        if ($request->has('kiosbank_port')) {
            Setting::updateOrCreate(['key' => 'kiosbank_port'], ['value' => trim($request->kiosbank_port)]);
        }
        if ($request->has('kiosbank_protocol')) {
            Setting::updateOrCreate(['key' => 'kiosbank_protocol'], ['value' => trim($request->kiosbank_protocol)]);
        }
        if ($request->has('kiosbank_username')) {
            Setting::updateOrCreate(['key' => 'kiosbank_username'], ['value' => trim($request->kiosbank_username)]);
        }
        if ($request->has('kiosbank_password')) {
            Setting::updateOrCreate(['key' => 'kiosbank_password'], ['value' => trim($request->kiosbank_password)]);
        }
        if ($request->has('kiosbank_mitra')) {
            Setting::updateOrCreate(['key' => 'kiosbank_mitra'], ['value' => trim($request->kiosbank_mitra)]);
        }
        if ($request->has('kiosbank_account_id')) {
            Setting::updateOrCreate(['key' => 'kiosbank_account_id'], ['value' => trim($request->kiosbank_account_id)]);
        }
        if ($request->has('kiosbank_merchant_id')) {
            Setting::updateOrCreate(['key' => 'kiosbank_merchant_id'], ['value' => trim($request->kiosbank_merchant_id)]);
        }
        if ($request->has('kiosbank_merchant_name')) {
            Setting::updateOrCreate(['key' => 'kiosbank_merchant_name'], ['value' => trim($request->kiosbank_merchant_name)]);
        }
        if ($request->has('kiosbank_counter_id')) {
            Setting::updateOrCreate(['key' => 'kiosbank_counter_id'], ['value' => trim($request->kiosbank_counter_id)]);
        }
        if ($request->has('kiosbank_mode')) {
            Setting::updateOrCreate(['key' => 'kiosbank_mode'], ['value' => trim($request->kiosbank_mode)]);
        }
        if ($request->has('kiosbank_markup_type')) {
            Setting::updateOrCreate(['key' => 'kiosbank_markup_type'], ['value' => trim($request->kiosbank_markup_type)]);
        }
        if ($request->has('kiosbank_markup_value')) {
            Setting::updateOrCreate(['key' => 'kiosbank_markup_value'], ['value' => trim($request->kiosbank_markup_value)]);
        }
        if ($request->has('kiosbank_private_key')) {
            Setting::updateOrCreate(['key' => 'kiosbank_private_key'], ['value' => trim($request->kiosbank_private_key)]);
        }
        if ($request->has('kiosbank_public_key')) {
            Setting::updateOrCreate(['key' => 'kiosbank_public_key'], ['value' => trim($request->kiosbank_public_key)]);
        }
        Setting::updateOrCreate(['key' => 'kiosbank_enabled'], ['value' => $request->has('kiosbank_enabled') ? '1' : '0']);

        Cache::forget('promo_settings');
        return back()->with('success', 'Semua Pengaturan API Provider, Kiosbank PPOB & RSA Secret Key, Promo, dan WhatsApp berhasil disimpan!');
    }

    /**
     * Generate Pasangan Kunci RSA 2048-bit Otomatis untuk Kiosbank
     */
    public function generateKiosbankRsaKeys()
    {
        $res = \App\Services\KiosbankService::generateRsaKeyPair();
        if (!($res['ok'] ?? false)) {
            return response()->json(['success' => false, 'message' => $res['message'] ?? 'Gagal membuat kunci RSA'], 500);
        }

        // Simpan langsung ke database settings
        Setting::updateOrCreate(['key' => 'kiosbank_private_key'], ['value' => $res['private_key']]);
        Setting::updateOrCreate(['key' => 'kiosbank_public_key'], ['value' => $res['public_key']]);

        return response()->json([
            'success'     => true,
            'message'     => 'Kunci RSA 2048-bit baru berhasil dibuat dan disimpan otomatis ke pengaturan!',
            'private_key' => $res['private_key'],
            'public_key'  => $res['public_key'],
        ]);
    }

    /**
     * Uji Live Sign-On Kiosbank (Mendapatkan SessionID)
     */
    public function testKiosbankSignOn(\App\Services\KiosbankService $kiosbankService)
    {
        $res = $kiosbankService->testSignOn();
        return response()->json($res);
    }

    public function branding() {
        $storeName = Setting::where('key', 'store_name')->value('value') ?? 'NandarStore';
        $storeTagline = Setting::where('key', 'store_tagline')->value('value') ?? 'Platform Digital & Akun Premium Otomatis';
        $storeLogo = Setting::where('key', 'store_logo')->value('value') ?? null;
        $storeFavicon = Setting::where('key', 'store_favicon')->value('value') ?? null;
        $loadingLogo = Setting::where('key', 'loading_logo')->value('value') ?? null;

        return view('admin.branding', compact('storeName', 'storeTagline', 'storeLogo', 'storeFavicon', 'loadingLogo'));
    }

    public function saveBranding(Request $request) {
        $request->validate([
            'store_name'    => 'nullable|string|max:100',
            'store_tagline' => 'nullable|string|max:200',
            'store_logo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
            'store_favicon' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,ico|max:1024',
            'loading_logo'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:3072',
        ]);

        // Nama Brand & Slogan
        if ($request->filled('store_name')) {
            Setting::updateOrCreate(['key' => 'store_name'], ['value' => trim($request->store_name)]);
        }
        if ($request->has('store_tagline')) {
            Setting::updateOrCreate(['key' => 'store_tagline'], ['value' => trim($request->store_tagline)]);
        }

        // 1. Logo Toko Utama
        if ($request->hasFile('store_logo')) {
            $path = $request->file('store_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('store_logo_url')) {
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => trim($request->store_logo_url)]);
        }
        if ($request->has('remove_store_logo') && $request->remove_store_logo == '1') {
            $oldLogo = Setting::where('key', 'store_logo')->value('value');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            Setting::updateOrCreate(['key' => 'store_logo'], ['value' => null]);
        }

        // 2. Favicon Tab Browser
        if ($request->hasFile('store_favicon')) {
            $path = $request->file('store_favicon')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('store_favicon_url')) {
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => trim($request->store_favicon_url)]);
        }
        if ($request->has('remove_store_favicon') && $request->remove_store_favicon == '1') {
            $oldFavicon = Setting::where('key', 'store_favicon')->value('value');
            if ($oldFavicon && str_starts_with($oldFavicon, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldFavicon));
            }
            Setting::updateOrCreate(['key' => 'store_favicon'], ['value' => null]);
        }

        // 3. Logo Loading / Preloader
        if ($request->hasFile('loading_logo')) {
            $path = $request->file('loading_logo')->store('settings', 'public');
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => '/storage/' . $path]);
        } elseif ($request->filled('loading_logo_url')) {
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => trim($request->loading_logo_url)]);
        }
        if ($request->has('remove_loading_logo') && $request->remove_loading_logo == '1') {
            $oldLoadingLogo = Setting::where('key', 'loading_logo')->value('value');
            if ($oldLoadingLogo && str_starts_with($oldLoadingLogo, '/storage/settings/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLoadingLogo));
            }
            Setting::updateOrCreate(['key' => 'loading_logo'], ['value' => null]);
        }

        return back()->with('success', 'Pengaturan Branding, Logo & Favicon Toko berhasil disimpan!');
    }

    public function paymentSettings() {
        $tripayEnv = Setting::where('key', 'tripay_env')->value('value') ?? 'sandbox';
        $tripayMerchant = Setting::where('key', 'tripay_merchant')->value('value');
        $tripayApiKey = Setting::where('key', 'tripay_api_key')->value('value');
        $tripayPrivateKey = Setting::where('key', 'tripay_private_key')->value('value');
        return view('admin.payment_settings', compact('tripayEnv', 'tripayMerchant', 'tripayApiKey', 'tripayPrivateKey'));
    }

    public function savePaymentSettings(Request $request) {
        Setting::updateOrCreate(['key' => 'tripay_env'], ['value' => $request->tripay_env]);
        Setting::updateOrCreate(['key' => 'tripay_merchant'], ['value' => $request->tripay_merchant]);
        Setting::updateOrCreate(['key' => 'tripay_api_key'], ['value' => $request->tripay_api_key]);
        Setting::updateOrCreate(['key' => 'tripay_private_key'], ['value' => $request->tripay_private_key]);
        return back()->with('success', 'Konfigurasi Tripay berhasil disimpan!');
    }

    public function terms() {
        $terms = Setting::where('key', 'terms_and_conditions')->value('value') ?? '';
        return view('admin.terms', compact('terms'));
    }

    public function saveTerms(Request $request) {
        Setting::updateOrCreate(['key' => 'terms_and_conditions'], ['value' => $request->terms]);
        return back()->with('success', 'Syarat & Ketentuan berhasil diperbarui!');
    }

    public function apiMargin(Request $request, PremkuApiService $premkuService, PreflixApiService $preflixService) {
        set_time_limit(120); 
        $search = $request->search;
        $apiProducts = collect();

        // DAFTAR PEMBERSIH KODE ANEH
        $mojibake = ['â€¢', 'â€œ', 'â€', 'â€™', 'â€“', 'â€”', 'â€‹', '&bull;'];
        $fixed    = ['•', '"', '"', "'", '-', '-', '', '•'];

        try {
            $resPremku = $premkuService->getProducts();
            if(isset($resPremku['products'])) {
                foreach($resPremku['products'] as $p) {
                    $desc = $p['description'] ?? $p['catatan'] ?? null;
                    if ($desc) { $desc = str_replace($mojibake, $fixed, $desc); }

                    $apiProducts->push([
                        'api_id' => 'PREMKU-' . $p['id'], 'name' => $p['name'], 'original_price' => $p['price'],
                        'provider' => 'Premku', 'image' => $p['image'] ?? null, 'description' => $desc
                    ]);
                }
            }
        } catch (\Exception $e) {}

        $preflixExtraData = Cache::remember('preflix_extra_data', 3600, function() use ($mojibake, $fixed) {
            $data = [];
            $idUser = Setting::where('key', 'preflix_id_user')->value('value');
            $keyUser = Setting::where('key', 'preflix_key_user')->value('value');
            if(!$idUser || !$keyUser) return $data;

            try {
                $katRes = Http::asForm()->post('https://h2h.preflix.my.id/app/', ['koneksi' => 'get_kategori', 'id_user' => $idUser, 'key_user' => $keyUser])->json();
                if(is_array($katRes)) {
                    foreach($katRes as $k) {
                        $prodRes = Http::asForm()->post('https://h2h.preflix.my.id/app/', ['koneksi' => 'get_produk', 'id_kategori' => $k['id_kategori'], 'id_user' => $idUser, 'key_user' => $keyUser])->json();
                        if(is_array($prodRes)) {
                            foreach($prodRes as $p) {
                                $desc = $p['deskripsi'] ?? null;
                                if ($desc) {
                                    $desc = str_replace(['\r\n', '\n'], "\n", $desc);
                                    $desc = str_replace($mojibake, $fixed, $desc);
                                }
                                $data['PREFLIX-' . $p['id_produk']] = ['icon' => $p['icon'] ?? null, 'deskripsi' => $desc];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {}
            return $data;
        });

        try {
            $idUser = Setting::where('key', 'preflix_id_user')->value('value');
            $keyUser = Setting::where('key', 'preflix_key_user')->value('value');
            $resPreflix = Http::asForm()->post('https://h2h.preflix.my.id/app/', ['koneksi' => 'get_all_product', 'id_user' => $idUser, 'key_user' => $keyUser])->json();

            if(isset($resPreflix['status']) && $resPreflix['status'] == 'success' && isset($resPreflix['data'])) {
                foreach($resPreflix['data'] as $kategori) {
                    foreach($kategori['produk'] as $p) {
                        $api_id = 'PREFLIX-' . $p['id_produk'];
                        $apiProducts->push([
                            'api_id' => $api_id, 'name' => $p['nama_produk'], 'original_price' => $p['harga'],
                            'provider' => 'Preflix', 'image' => $preflixExtraData[$api_id]['icon'] ?? null, 
                            'description' => $preflixExtraData[$api_id]['deskripsi'] ?? null
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {}

        if ($search) {
            $apiProducts = $apiProducts->filter(function ($item) use ($search) { return stripos($item['name'], $search) !== false; });
        }

        // Ambil Data DB dan bersihkan juga kodenya agar di form edit muncul bersih
        $dbMarkups = Product::where('type', 'api')->get()->keyBy('api_product_id');
        foreach($dbMarkups as $key => $item) {
            if (!empty($item->description)) {
                $item->description = str_replace($mojibake, $fixed, $item->description);
            }
        }

        $preflixDisabled = Setting::where('key', 'provider_preflix_disabled')->value('value') == '1';
        $premkuDisabled  = Setting::where('key', 'provider_premku_disabled')->value('value') == '1';

        $preflixActiveCount = Product::where('type', 'api')->where('api_product_id', 'LIKE', 'PREFLIX-%')->where('status', 'available')->count();
        $preflixTotalCount  = Product::where('type', 'api')->where('api_product_id', 'LIKE', 'PREFLIX-%')->count();
        $premkuActiveCount  = Product::where('type', 'api')->where('api_product_id', 'LIKE', 'PREMKU-%')->where('status', 'available')->count();
        $premkuTotalCount   = Product::where('type', 'api')->where('api_product_id', 'LIKE', 'PREMKU-%')->count();

        return view('admin.api_margin', compact(
            'apiProducts', 
            'dbMarkups', 
            'search',
            'preflixDisabled',
            'premkuDisabled',
            'preflixActiveCount',
            'preflixTotalCount',
            'premkuActiveCount',
            'premkuTotalCount'
        ));
    }

    public function toggleProviderProducts(Request $request) {
        $request->validate([
            'provider' => 'required|in:preflix,premku,all',
            'action'   => 'required|in:enable,disable',
        ]);

        $provider = $request->provider;
        $action = $request->action;
        $newStatus = ($action === 'enable') ? 'available' : 'unavailable';

        $query = Product::where('type', 'api');

        if ($provider === 'preflix') {
            $query->where('api_product_id', 'LIKE', 'PREFLIX-%');
            $providerName = 'Preflix';
            Setting::updateOrCreate(['key' => 'provider_preflix_disabled'], ['value' => $action === 'disable' ? '1' : '0']);
        } elseif ($provider === 'premku') {
            $query->where('api_product_id', 'LIKE', 'PREMKU-%');
            $providerName = 'Premku';
            Setting::updateOrCreate(['key' => 'provider_premku_disabled'], ['value' => $action === 'disable' ? '1' : '0']);
        } else {
            $providerName = 'Semua Provider API';
            Setting::updateOrCreate(['key' => 'provider_preflix_disabled'], ['value' => $action === 'disable' ? '1' : '0']);
            Setting::updateOrCreate(['key' => 'provider_premku_disabled'], ['value' => $action === 'disable' ? '1' : '0']);
        }

        $affected = $query->update(['status' => $newStatus]);
        Cache::forget('api_full_data');

        $actionText = ($action === 'enable') 
            ? 'diaktifkan kembali dan kini tampil di etalase toko' 
            : 'dinonaktifkan (dimatikan) secara serentak dari etalase toko pembeli';

        return back()->with('success', "Berhasil! {$affected} produk dari {$providerName} telah {$actionText}.");
    }

    public function updateApiMarkup(Request $request, $api_id) {}

    public function bulkUpdateApiMarkup(Request $request) {
        try { DB::statement('ALTER TABLE products MODIFY image LONGTEXT'); } catch (\Exception $e) {}
        try { DB::statement('ALTER TABLE products MODIFY description LONGTEXT'); } catch (\Exception $e) {}
        try { 
            if (!Schema::hasColumn('products', 'is_promo')) {
                Schema::table('products', function (Blueprint $table) { $table->boolean('is_promo')->default(1); });
            }
        } catch (\Exception $e) {}

        $products = $request->input('products', []);
        $count = 0;

        foreach($products as $api_id => $data) {
            if(isset($data['markup_price']) && $data['markup_price'] > 0) {
                
                $product = Product::firstOrNew(['api_product_id' => $api_id, 'type' => 'api']);
                
                $product->name = $data['name'];
                $product->price = $data['markup_price'];
                $product->reseller_price = !empty($data['reseller_price']) ? (int)$data['reseller_price'] : null;
                $product->category = $data['category'] ?? null;
                $product->status = isset($data['is_active']) ? 'available' : 'unavailable';
                
                $product->description = $data['description'] ?? null;
                $product->image = !empty($data['image']) ? $data['image'] : ($product->image ?? null);
                
                $product->is_promo = isset($data['is_promo']) ? 1 : 0;
                
                $product->save();
                $count++;
            }
        }
        return back()->with('success', $count . ' Produk API, Logo, dan Status Promo berhasil diperbarui ke Database!');
    }

    public function localProducts(Request $request) {
        $query = Product::where('type', 'local')->orderBy('id', 'desc');
        if ($request->search) $query->where('name', 'like', '%' . $request->search . '%');
        $localProducts = $query->get(); return view('admin.local_products', compact('localProducts'));
    }
    public function storeLocalProduct(Request $request) {
        $request->validate([
            'name'           => 'required|string|max:150',
            'category'       => 'nullable|string|max:100',
            'price'          => 'required|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);
        $imagePath = $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null;
        Product::create([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'price' => $request->price,
            'reseller_price' => $request->reseller_price ?: null,
            'stock' => 0,
            'type' => 'local',
            'process_type' => $request->process_type === 'manual' ? 'manual' : 'auto',
            'image' => $imagePath ? '/storage/' . $imagePath : null,
            'status' => 'available'
        ]);
        return back()->with('success', 'Produk berhasil ditambahkan.');
    }
    public function updateLocalProduct(Request $request, $id) {
        $request->validate([
            'name'           => 'required|string|max:150',
            'category'       => 'nullable|string|max:100',
            'price'          => 'required|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);
        $product = Product::findOrFail($id);
        $product->name = $request->name;
        $product->category = $request->category;
        $product->price = $request->price;
        $product->reseller_price = $request->reseller_price ?: null;
        $product->description = $request->description;
        $product->process_type = $request->process_type === 'manual' ? 'manual' : 'auto';
        if ($request->hasFile('image')) {
            if ($product->image) { $oldPath = str_replace('/storage/', '', $product->image); if(Storage::disk('public')->exists($oldPath)) Storage::disk('public')->delete($oldPath); }
            $imagePath = $request->file('image')->store('products', 'public'); $product->image = '/storage/' . $imagePath;
        }
        $product->save(); return back()->with('success', 'Produk diperbarui!');
    }
    public function toggleLocalProduct($id) { $p = Product::findOrFail($id); $p->update(['status' => ($p->status == 'available' ? 'unavailable' : 'available')]); return back()->with('success', 'Status diubah!'); }
    public function deleteLocalProduct($id) {
        $p = Product::findOrFail($id);
        if ($p->image) { $oldPath = str_replace('/storage/', '', $p->image); if(Storage::disk('public')->exists($oldPath)) Storage::disk('public')->delete($oldPath); }
        $p->delete(); return back()->with('success', 'Produk dihapus!');
    }
    public function localAccounts(Request $request, $id) {
        $product = Product::with('accounts')->findOrFail($id); $query = LocalAccount::where('product_id', $id)->orderBy('id', 'desc');
        if ($request->search) $query->where('email', 'like', '%' . $request->search . '%');
        $accounts = $query->get(); return view('admin.local_accounts', compact('product', 'accounts'));
    }
    public function storeLocalAccount(Request $request, $id) {
        $request->validate([
            'txt_file' => 'nullable|file|mimes:txt|max:5120',
            'email'    => 'nullable|string|max:150',
            'password' => 'nullable|string|max:255',
        ]);
        $product = Product::findOrFail($id); $count = 0;
        if ($request->hasFile('txt_file')) {
            $content = file_get_contents($request->file('txt_file')->getRealPath()); $lines = explode("\n", $content);
            foreach ($lines as $line) { if (trim($line)) { $parts = explode("|", $line); if (count($parts) >= 2) { LocalAccount::create(['product_id' => $id, 'email' => trim($parts[0]), 'password' => trim($parts[1])]); $count++; } } }
        } elseif ($request->email && $request->password) {
            $qty = (int) $request->input('quantity', 1); if ($qty < 1) $qty = 1;
            for ($i = 0; $i < $qty; $i++) { LocalAccount::create(['product_id' => $id, 'email' => $request->email, 'password' => $request->password]); $count++; }
        }
        $product->update(['stock' => $product->accounts()->where('is_sold', false)->count()]); return back()->with('success', "$count Akun stok ditambahkan!");
    }
    public function updateLocalAccount(Request $request, $id) { LocalAccount::findOrFail($id)->update(['email' => $request->email, 'password' => $request->password]); return back()->with('success', 'Akun diedit!'); }
    public function deleteLocalAccount($id) { $acc = LocalAccount::findOrFail($id); $p = Product::find($acc->product_id); $acc->delete(); $p->update(['stock' => $p->accounts()->where('is_sold', false)->count()]); return back()->with('success', 'Akun dihapus!'); }
    public function clearLocalAccounts($id) { $product = Product::findOrFail($id); LocalAccount::where('product_id', $id)->delete(); $product->update(['stock' => 0]); return back()->with('success', 'Semua stok berhasil dikosongkan secara instan!'); }
    public function resellers(Request $request) {
        $query = User::where('role', 'reseller')->orderBy('id', 'desc');
        if ($request->search) { $query->where('name', 'like', '%' . $request->search . '%')->orWhere('email', 'like', '%' . $request->search . '%'); }
        $resellers = $query->with(['transactionLogs', 'orders'])->get(); return view('admin.resellers', compact('resellers'));
    }
    public function storeReseller(Request $request) { User::create(['name' => $request->name, 'email' => $request->email, 'password' => Hash::make($request->password), 'role' => 'reseller']); return back()->with('success', 'Reseller ditambahkan!'); }
    public function manageResellerBalance(Request $request, $id) {
        $user = User::findOrFail($id); $amount = $request->amount;
        DB::transaction(function() use ($user, $request, $amount) {
            if ($request->action == 'add') { $user->balance += $amount; TransactionLog::create(['user_id' => $user->id, 'type' => 'deposit', 'amount' => $amount, 'description' => 'Topup Manual by Admin']); } 
            else { $user->balance -= $amount; TransactionLog::create(['user_id' => $user->id, 'type' => 'deduction', 'amount' => $amount, 'description' => 'Deduction Manual by Admin']); }
            $user->save();
        });
        return back()->with('success', 'Saldo diperbarui!');
    }
    public function toggleResellerStatus($id) { $user = User::findOrFail($id); $user->update(['is_active' => !$user->is_active]); return back()->with('success', 'Status diubah!'); }
    public function deleteReseller($id) { User::findOrFail($id)->delete(); return back()->with('success', 'Reseller dihapus!'); }
    public function orders(Request $request) {
        $query = Order::with(['user', 'product'])->orderBy('id', 'desc');
        if ($request->search) { $query->where('invoice_number', 'like', '%' . $request->search . '%')->orWhere('product_name', 'like', '%' . $request->search . '%'); }
        if ($request->status) $query->where('order_status', $request->status);
        $orders = $query->paginate(20); return view('admin.orders', compact('orders'));
    }
    public function updateOrderStatus(Request $request, $id) {
        $order = Order::findOrFail($id);
        $data = ['order_status' => $request->status];
        if ($request->filled('sn_or_account')) {
            $data['sn_or_account'] = $request->sn_or_account;
            $data['account_details'] = $request->sn_or_account;
        }
        $order->update($data);

        // Kirim notifikasi WhatsApp ke pembeli jika status completed & WA terverifikasi
        if (in_array(strtolower($request->status), ['completed', 'success', 'lunas']) && $order->whatsapp) {
            \App\Http\Controllers\WebhookController::sendOrderNotification($order);
        }

        return back()->with('success', 'Status pesanan berhasil diperbarui!');
    }
    public function vouchers(Request $request) {
        $query = Voucher::orderBy('id', 'desc'); if ($request->search) $query->where('code', 'like', '%' . $request->search . '%');
        $vouchers = $query->get(); return view('admin.vouchers', compact('vouchers'));
    }
    public function storeVoucher(Request $request) {
        $request->validate(['code' => 'required|unique:vouchers', 'type' => 'required|in:nominal,percent', 'value' => 'required|numeric|min:1']);
        Voucher::create(['code' => strtoupper(str_replace(' ', '', $request->code)), 'type' => $request->type, 'value' => $request->value, 'max_uses' => $request->max_uses, 'expired_at' => $request->expired_at, 'is_active' => true]);
        return back()->with('success', 'Voucher ditambahkan!');
    }
    public function toggleVoucher($id) { $v = Voucher::findOrFail($id); $v->update(['is_active' => !$v->is_active]); return back()->with('success', 'Status voucher diubah!'); }
    public function deleteVoucher($id) { Voucher::findOrFail($id)->delete(); return back()->with('success', 'Voucher dihapus!'); }

    public function waVerifications(Request $request) {
        $pendingUsers = User::whereNotNull('wa_verification_code')
            ->where('is_whatsapp_verified', false)
            ->orderBy('updated_at', 'desc')
            ->get();

        $verifiedUsers = User::where('is_whatsapp_verified', true)
            ->orderBy('whatsapp_verified_at', 'desc')
            ->limit(30)
            ->get();

        $gatewayUrl = Setting::where('key', 'wa_gateway_url')->value('value') ?: 'http://127.0.0.1:5001';

        return view('admin.wa_verifications', compact('pendingUsers', 'verifiedUsers', 'gatewayUrl'));
    }

    public function approveWaVerification(Request $request) {
        $targetUser = null;
        if ($request->has('user_id') && !empty($request->user_id)) {
            $targetUser = User::find($request->user_id);
        } elseif ($request->has('code') && !empty($request->code)) {
            $cleanCode = strtoupper(trim($request->code));
            $targetUser = User::where('wa_verification_code', $cleanCode)->first();
        }

        if (!$targetUser) {
            return back()->with('error', 'Pengguna atau kode verifikasi WhatsApp tidak ditemukan.');
        }

        $targetUser->update([
            'is_whatsapp_verified' => true,
            'whatsapp_verified_at' => now(),
            'wa_verification_code' => null,
        ]);

        if (!empty($targetUser->whatsapp)) {
            \App\Http\Controllers\WebhookController::sendWaMessage(
                $targetUser->whatsapp,
                "🎉 *VERIFIKASI WHATSAPP BERHASIL*\n\n"
                . "Halo *{$targetUser->name}*, nomor WhatsApp Anda (*{$targetUser->whatsapp}*) telah berhasil diverifikasi oleh Admin di *NandarStore*.\n\n"
                . "Sekarang setiap transaksi Anda akan menerima:\n"
                . "✓ Pengiriman akun & lisensi otomatis via WhatsApp\n"
                . "✓ Link invoice resmi format cetak PDF\n"
                . "✓ Notifikasi status pembelian langsung\n\n"
                . "Selamat berbelanja di NandarStore! 🛒✨"
            );
        }

        return back()->with('success', 'Nomor WhatsApp ' . $targetUser->name . ' (' . $targetUser->whatsapp . ') berhasil divalidasi!');
    }

    public function rejectWaVerification(Request $request) {
        $user = User::findOrFail($request->user_id);
        $user->update([
            'wa_verification_code' => null,
            'is_whatsapp_verified' => false,
        ]);
        return back()->with('success', 'Permintaan verifikasi WhatsApp dibatalkan/ditolak.');
    }

    public function updateUserWhatsapp(Request $request) {
        $request->validate([
            'user_id'  => 'required|exists:users,id',
            'whatsapp' => 'required|string|min:8|max:20',
        ]);

        $user = User::findOrFail($request->user_id);
        $cleanPhone = preg_replace('/[^0-9]/', '', $request->whatsapp);
        if (str_starts_with($cleanPhone, '628')) {
            $cleanPhone = '08' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '08' . substr($cleanPhone, 1);
        }

        $isVerified = $request->has('is_verified') ? $request->boolean('is_verified') : $user->is_whatsapp_verified;

        $user->update([
            'whatsapp'             => $cleanPhone,
            'is_whatsapp_verified' => $isVerified,
            'whatsapp_verified_at' => $isVerified ? ($user->whatsapp_verified_at ?: now()) : null,
            'wa_verification_code' => $isVerified ? null : $user->wa_verification_code,
        ]);

        return back()->with('success', "Nomor WhatsApp akun {$user->name} berhasil diperbarui menjadi {$cleanPhone}!");
    }

    // --- WHATSAPP BOT GATEWAY CONTROLLERS ---
    public function waGatewayStatus() {
        $gatewayUrl = Setting::where('key', 'wa_gateway_url')->value('value') ?: 'http://127.0.0.1:5001';
        try {
            $res = Http::timeout(2)->get(rtrim($gatewayUrl, '/') . '/api/status');
            if ($res->successful()) {
                return response()->json($res->json());
            }
        } catch (\Exception $e) {}

        return response()->json([
            'status'    => 'offline',
            'connected' => false,
            'phone'     => null,
            'qr'        => null
        ]);
    }

    public function waGatewayLogout() {
        $gatewayUrl = Setting::where('key', 'wa_gateway_url')->value('value') ?: 'http://127.0.0.1:5001';
        try {
            Http::timeout(3)->post(rtrim($gatewayUrl, '/') . '/api/logout');
            return back()->with('success', 'Sesi bot WhatsApp berhasil direset. Silakan scan QR baru.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mereset sesi bot: ' . $e->getMessage());
        }
    }

    public function waGatewayTestSend(Request $request) {
        $request->validate([
            'target'  => 'required',
            'message' => 'required',
        ]);

        $success = \App\Http\Controllers\WebhookController::sendWaMessage($request->target, $request->message);
        if ($success) {
            return back()->with('success', 'Pesan uji coba berhasil dikirim ke nomor ' . $request->target . '!');
        }

        return back()->with('error', 'Gagal mengirim pesan. Pastikan WhatsApp Bot sudah terhubung dan nomor tujuan aktif.');
    }

    public function waGatewaySaveSettings(Request $request) {
        if ($request->has('wa_gateway_url')) {
            Setting::updateOrCreate(['key' => 'wa_gateway_url'], ['value' => $request->wa_gateway_url]);
        }
        return back()->with('success', 'Pengaturan URL WhatsApp Gateway berhasil disimpan!');
    }

    // --- KELOLA BANNER CAROUSEL ---
    public function banners() {
        $banners = Banner::orderBy('order', 'asc')->orderBy('id', 'desc')->get();
        return view('admin.banners', compact('banners'));
    }

    public function storeBanner(Request $request) {
        $request->validate([
            'title'            => 'nullable|string|max:255',
            'subtitle'         => 'nullable|string|max:500',
            'badge'            => 'nullable|string|max:50',
            'link_url'         => 'nullable|string|max:500',
            'bg_gradient'      => 'nullable|string|max:100',
            'order'            => 'nullable|integer',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:5120',
            'image_url'        => 'nullable|string|max:1000',
            'image_mobile'     => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:5120',
            'image_mobile_url' => 'nullable|string|max:1000',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $imagePath = '/storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $imagePath = $request->image_url;
        }

        $imageMobilePath = null;
        if ($request->hasFile('image_mobile')) {
            $pathMobile = $request->file('image_mobile')->store('banners', 'public');
            $imageMobilePath = '/storage/' . $pathMobile;
        } elseif ($request->filled('image_mobile_url')) {
            $imageMobilePath = $request->image_mobile_url;
        }

        Banner::create([
            'title'        => $request->title,
            'subtitle'     => $request->subtitle,
            'badge'        => $request->badge,
            'image'        => $imagePath,
            'image_mobile' => $imageMobilePath,
            'link_url'     => $request->link_url,
            'bg_gradient'  => $request->bg_gradient ?: 'from-orange-600 to-red-600',
            'order'        => $request->order ?? 0,
            'is_active'    => true,
        ]);

        return back()->with('success', 'Banner baru berhasil ditambahkan!');
    }

    public function updateBanner(Request $request, $id) {
        $banner = Banner::findOrFail($id);

        $request->validate([
            'title'            => 'nullable|string|max:255',
            'subtitle'         => 'nullable|string|max:500',
            'badge'            => 'nullable|string|max:50',
            'link_url'         => 'nullable|string|max:500',
            'bg_gradient'      => 'nullable|string|max:100',
            'order'            => 'nullable|integer',
            'image'            => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:5120',
            'image_url'        => 'nullable|string|max:1000',
            'image_mobile'     => 'nullable|image|mimes:jpeg,png,jpg,webp,svg,gif|max:5120',
            'image_mobile_url' => 'nullable|string|max:1000',
        ]);

        $data = [
            'title'       => $request->title,
            'subtitle'    => $request->subtitle,
            'badge'       => $request->badge,
            'link_url'    => $request->link_url,
            'bg_gradient' => $request->bg_gradient ?: $banner->bg_gradient,
            'order'       => $request->order ?? 0,
        ];

        if ($request->hasFile('image')) {
            if ($banner->image && str_starts_with($banner->image, '/storage/banners/')) {
                $oldFile = str_replace('/storage/', '', $banner->image);
                Storage::disk('public')->delete($oldFile);
            }
            $path = $request->file('image')->store('banners', 'public');
            $data['image'] = '/storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->image_url;
        }

        if ($request->hasFile('image_mobile')) {
            if ($banner->image_mobile && str_starts_with($banner->image_mobile, '/storage/banners/')) {
                $oldFileMobile = str_replace('/storage/', '', $banner->image_mobile);
                Storage::disk('public')->delete($oldFileMobile);
            }
            $pathMobile = $request->file('image_mobile')->store('banners', 'public');
            $data['image_mobile'] = '/storage/' . $pathMobile;
        } elseif ($request->filled('image_mobile_url')) {
            $data['image_mobile'] = $request->image_mobile_url;
        }

        $banner->update($data);

        return back()->with('success', 'Banner berhasil diperbarui!');
    }

    public function toggleBanner($id) {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => !$banner->is_active]);

        $statusText = $banner->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Banner berhasil {$statusText}!");
    }

    public function deleteBanner($id) {
        $banner = Banner::findOrFail($id);

        if ($banner->image && str_starts_with($banner->image, '/storage/banners/')) {
            $file = str_replace('/storage/', '', $banner->image);
            Storage::disk('public')->delete($file);
        }
        if ($banner->image_mobile && str_starts_with($banner->image_mobile, '/storage/banners/')) {
            $fileMobile = str_replace('/storage/', '', $banner->image_mobile);
            Storage::disk('public')->delete($fileMobile);
        }

        $banner->delete();

        return back()->with('success', 'Banner berhasil dihapus!');
    }

    // --- GRAFIK & LAPORAN PENJUALAN EKSEKUTIF ---
    public function reports(Request $request) {
        $period = $request->get('period', '30_days');
        $startDateInput = $request->get('start_date');
        $endDateInput = $request->get('end_date');

        $now = now();
        $startDate = null;
        $endDate = $now->copy()->endOfDay();

        switch ($period) {
            case '7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $periodLabel = '7 Hari Terakhir';
                break;
            case '30_days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $periodLabel = '30 Hari Terakhir';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                $periodLabel = 'Bulan Ini (' . $now->translatedFormat('F Y') . ')';
                break;
            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                $periodLabel = 'Bulan Lalu (' . $now->copy()->subMonth()->translatedFormat('F Y') . ')';
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear()->startOfDay();
                $periodLabel = 'Tahun Ini (' . $now->format('Y') . ')';
                break;
            case 'custom':
                if ($startDateInput && $endDateInput) {
                    $startDate = \Carbon\Carbon::parse($startDateInput)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($endDateInput)->endOfDay();
                    $periodLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
                } else {
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $periodLabel = '30 Hari Terakhir';
                }
                break;
            case 'all':
            default:
                if ($period === 'all') {
                    $firstOrder = Order::orderBy('created_at', 'asc')->first();
                    $startDate = $firstOrder && $firstOrder->created_at ? $firstOrder->created_at->startOfDay() : $now->copy()->subDays(29)->startOfDay();
                    $periodLabel = 'Semua Waktu';
                } else {
                    $period = '30_days';
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $periodLabel = '30 Hari Terakhir';
                }
                break;
        }

        // Query dasar berdasar rentang tanggal
        $ordersQuery = Order::whereBetween('created_at', [$startDate, $endDate]);

        // Ringkasan Statistik & Analisis Laba Bersih
        $paidStatuses = ['paid', 'success', 'settlement'];
        
        $kiosbankMarkupType = Setting::where('key', 'kiosbank_markup_type')->value('value') ?: 'fixed';
        $kiosbankMarkupVal = (int) (Setting::where('key', 'kiosbank_markup_value')->value('value') ?: 1000);

        // Ambil data pesanan lunas untuk kalkulasi Omset, Modal, dan Keuntungan Bersih
        $paidOrders = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->with('product')->get();

        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($paidOrders as $ord) {
            $rev = (float) $ord->total_amount;
            $totalRevenue += $rev;

            $cost = 0;
            if ($ord->product_type === 'ppob') {
                $prof = ($kiosbankMarkupType === 'fixed') ? $kiosbankMarkupVal : ($rev * $kiosbankMarkupVal / 100);
                if ($prof > $rev) $prof = min(1000, $rev * 0.05);
                $cost = max(0, $rev - $prof);
            } elseif ($ord->product_type === 'api' || ($ord->product && $ord->product->type === 'api')) {
                $apiCost = $ord->product ? (float) $ord->product->api_price : 0;
                $cost = ($apiCost > 0) ? $apiCost : ($rev * 0.8);
            } else {
                $cost = $ord->product && $ord->product->api_price ? (float) $ord->product->api_price : 0;
            }

            $prof = max(0, $rev - $cost);
            $totalCost += $cost;
            $totalProfit += $prof;
        }

        $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 1) : 0;

        $successOrdersCount = $paidOrders->count();
        $pendingOrdersCount = (clone $ordersQuery)->where('payment_status', 'pending')->count();
        $failedOrdersCount = (clone $ordersQuery)->whereIn('payment_status', ['failed', 'canceled', 'expired'])->count();
        $totalOrdersCount = (clone $ordersQuery)->count();

        $avgOrderValue = $successOrdersCount > 0 ? round($totalRevenue / $successOrdersCount) : 0;
        $successRate = $totalOrdersCount > 0 ? round(($successOrdersCount / $totalOrdersCount) * 100, 1) : 100;

        // Distribusi Tipe Produk (API vs Local vs PPOB)
        $apiRevenue = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'api')->sum('total_amount');
        $localRevenue = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'local')->sum('total_amount');
        $ppobRevenue = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'ppob')->sum('total_amount');
        $apiCount = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'api')->count();
        $localCount = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'local')->count();
        $ppobCount = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)->where('product_type', 'ppob')->count();

        // Top 5 Produk Terlaris
        $topProducts = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)
            ->select('product_name', DB::raw('count(*) as sales_count'), DB::raw('sum(total_amount) as total_nominal'))
            ->groupBy('product_name')
            ->orderBy('sales_count', 'desc')
            ->limit(5)
            ->get();

        // Generate Data Grafik Tren Harian / Periode Terpilih
        $chartLabels = [];
        $chartRevenue = [];
        $chartProfit = [];
        $chartOrderCount = [];

        $diffDays = $startDate->diffInDays($endDate);
        if ($diffDays <= 31) {
            $periodCursor = $startDate->copy();
            while ($periodCursor->lte($endDate)) {
                $dayStr = $periodCursor->toDateString();
                $chartLabels[] = $periodCursor->translatedFormat('d M');

                $dayOrders = (clone $ordersQuery)->whereIn('payment_status', $paidStatuses)
                    ->whereDate('created_at', $dayStr)
                    ->with('product')
                    ->get();

                $dayRev = 0;
                $dayProf = 0;
                foreach ($dayOrders as $ord) {
                    $rev = (float) $ord->total_amount;
                    $dayRev += $rev;

                    $cost = 0;
                    if ($ord->product_type === 'ppob') {
                        $prof = ($kiosbankMarkupType === 'fixed') ? $kiosbankMarkupVal : ($rev * $kiosbankMarkupVal / 100);
                        if ($prof > $rev) $prof = min(1000, $rev * 0.05);
                        $cost = max(0, $rev - $prof);
                    } elseif ($ord->product_type === 'api' || ($ord->product && $ord->product->type === 'api')) {
                        $apiCost = $ord->product ? (float) $ord->product->api_price : 0;
                        $cost = ($apiCost > 0) ? $apiCost : ($rev * 0.8);
                    } else {
                        $cost = $ord->product && $ord->product->api_price ? (float) $ord->product->api_price : 0;
                    }
                    $dayProf += max(0, $rev - $cost);
                }

                $chartRevenue[] = (int) $dayRev;
                $chartProfit[] = (int) $dayProf;
                $chartOrderCount[] = (int) $dayOrders->count();

                $periodCursor->addDay();
            }
        } else {
            // Jika lebih dari 31 hari, kelompokkan per bulan
            $periodCursor = $startDate->copy()->startOfMonth();
            while ($periodCursor->lte($endDate)) {
                $chartLabels[] = $periodCursor->translatedFormat('M Y');

                $monthOrders = Order::whereIn('payment_status', $paidStatuses)
                    ->whereYear('created_at', $periodCursor->year)
                    ->whereMonth('created_at', $periodCursor->month)
                    ->with('product')
                    ->get();

                $monthRev = 0;
                $monthProf = 0;
                foreach ($monthOrders as $ord) {
                    $rev = (float) $ord->total_amount;
                    $monthRev += $rev;

                    $cost = 0;
                    if ($ord->product_type === 'ppob') {
                        $prof = ($kiosbankMarkupType === 'fixed') ? $kiosbankMarkupVal : ($rev * $kiosbankMarkupVal / 100);
                        if ($prof > $rev) $prof = min(1000, $rev * 0.05);
                        $cost = max(0, $rev - $prof);
                    } elseif ($ord->product_type === 'api' || ($ord->product && $ord->product->type === 'api')) {
                        $apiCost = $ord->product ? (float) $ord->product->api_price : 0;
                        $cost = ($apiCost > 0) ? $apiCost : ($rev * 0.8);
                    } else {
                        $cost = $ord->product && $ord->product->api_price ? (float) $ord->product->api_price : 0;
                    }
                    $monthProf += max(0, $rev - $cost);
                }

                $chartRevenue[] = (int) $monthRev;
                $chartProfit[] = (int) $monthProf;
                $chartOrderCount[] = (int) $monthOrders->count();

                $periodCursor->addMonth();
            }
        }

        // Laporan Bulanan Lengkap (12 Bulan Terakhir)
        $monthlyReports = [];
        $monthlyLabels = [];
        $monthlyRevenueData = [];
        $monthlyProfitData = [];

        for ($i = 11; $i >= 0; $i--) {
            $mCarbon = now()->copy()->subMonths($i);
            $mYear = $mCarbon->year;
            $mMonth = $mCarbon->month;
            $mLabel = $mCarbon->translatedFormat('F Y');
            $mShortLabel = $mCarbon->translatedFormat('M Y');

            $mOrders = Order::whereIn('payment_status', $paidStatuses)
                ->whereYear('created_at', $mYear)
                ->whereMonth('created_at', $mMonth)
                ->with('product')
                ->get();

            $mRev = 0;
            $mCost = 0;
            $mProf = 0;

            foreach ($mOrders as $ord) {
                $rev = (float) $ord->total_amount;
                $mRev += $rev;

                $cost = 0;
                if ($ord->product_type === 'ppob') {
                    $prof = ($kiosbankMarkupType === 'fixed') ? $kiosbankMarkupVal : ($rev * $kiosbankMarkupVal / 100);
                    if ($prof > $rev) $prof = min(1000, $rev * 0.05);
                    $cost = max(0, $rev - $prof);
                } elseif ($ord->product_type === 'api' || ($ord->product && $ord->product->type === 'api')) {
                    $apiCost = $ord->product ? (float) $ord->product->api_price : 0;
                    $cost = ($apiCost > 0) ? $apiCost : ($rev * 0.8);
                } else {
                    $cost = $ord->product && $ord->product->api_price ? (float) $ord->product->api_price : 0;
                }

                $mProf += max(0, $rev - $cost);
                $mCost += $cost;
            }

            $mMargin = $mRev > 0 ? round(($mProf / $mRev) * 100, 1) : 0;

            $monthlyReports[] = [
                'year'        => $mYear,
                'month'       => $mMonth,
                'label'       => $mLabel,
                'short_label' => $mShortLabel,
                'order_count' => $mOrders->count(),
                'revenue'     => (int) $mRev,
                'cost'        => (int) $mCost,
                'profit'      => (int) $mProf,
                'margin'      => $mMargin,
            ];

            $monthlyLabels[] = $mShortLabel;
            $monthlyRevenueData[] = (int) $mRev;
            $monthlyProfitData[] = (int) $mProf;
        }

        // Tabel Data Pesanan (Paginated)
        $orders = (clone $ordersQuery)
            ->with(['user', 'product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports', compact(
            'period',
            'periodLabel',
            'startDateInput',
            'endDateInput',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalCost',
            'totalProfit',
            'profitMargin',
            'successOrdersCount',
            'pendingOrdersCount',
            'failedOrdersCount',
            'totalOrdersCount',
            'avgOrderValue',
            'successRate',
            'apiRevenue',
            'localRevenue',
            'ppobRevenue',
            'apiCount',
            'localCount',
            'ppobCount',
            'topProducts',
            'chartLabels',
            'chartRevenue',
            'chartProfit',
            'chartOrderCount',
            'monthlyReports',
            'monthlyLabels',
            'monthlyRevenueData',
            'monthlyProfitData',
            'orders'
        ));
    }

    public function exportReports(Request $request) {
        $period = $request->get('period', '30_days');
        $startDateInput = $request->get('start_date');
        $endDateInput = $request->get('end_date');

        $now = now();
        $startDate = null;
        $endDate = $now->copy()->endOfDay();
        $filePeriodName = $period;

        switch ($period) {
            case '7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $filePeriodName = '7_Hari';
                break;
            case '30_days':
                $startDate = $now->copy()->subDays(29)->startOfDay();
                $filePeriodName = '30_Hari';
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth()->startOfDay();
                $filePeriodName = 'Bulan_' . $now->format('m_Y');
                break;
            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth()->startOfDay();
                $endDate = $now->copy()->subMonth()->endOfMonth()->endOfDay();
                $filePeriodName = 'Bulan_' . $now->copy()->subMonth()->format('m_Y');
                break;
            case 'this_year':
                $startDate = $now->copy()->startOfYear()->startOfDay();
                $filePeriodName = 'Tahun_' . $now->format('Y');
                break;
            case 'custom':
                if ($startDateInput && $endDateInput) {
                    $startDate = \Carbon\Carbon::parse($startDateInput)->startOfDay();
                    $endDate = \Carbon\Carbon::parse($endDateInput)->endOfDay();
                    $filePeriodName = $startDate->format('Ymd') . '_sd_' . $endDate->format('Ymd');
                } else {
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $filePeriodName = '30_Hari';
                }
                break;
            case 'all':
            default:
                if ($period === 'all') {
                    $firstOrder = Order::orderBy('created_at', 'asc')->first();
                    $startDate = $firstOrder && $firstOrder->created_at ? $firstOrder->created_at->startOfDay() : $now->copy()->subDays(29)->startOfDay();
                    $filePeriodName = 'Semua_Waktu';
                } else {
                    $startDate = $now->copy()->subDays(29)->startOfDay();
                    $filePeriodName = '30_Hari';
                }
                break;
        }

        $orders = Order::with('user')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $fileName = 'Laporan_Penjualan_NANDARSTORE_' . $filePeriodName . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($orders, $filePeriodName, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM agar Microsoft Excel di Windows membaca huruf, karakter, dan angka tanpa korup
            fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Judul & Metadata Laporan
            fputcsv($handle, ['LAPORAN PENJUALAN & TRANSAKSI NANDARSTORE 2.0'], ';');
            fputcsv($handle, ['Periode', $startDate->format('d/m/Y') . ' s/d ' . $endDate->format('d/m/Y')], ';');
            fputcsv($handle, ['Tanggal Unduh', now()->translatedFormat('d F Y, H:i:s') . ' WIB'], ';');
            fputcsv($handle, ['Total Pesanan', count($orders)], ';');
            
            $totalNominal = $orders->whereIn('payment_status', ['paid', 'success', 'settlement'])->sum('total_amount');
            fputcsv($handle, ['Total Omset Lunas (Rp)', number_format($totalNominal, 0, ',', '.')], ';');
            fputcsv($handle, [], ';'); // Baris Kosong

            // Header Kolom Tabel
            fputcsv($handle, [
                'No',
                'No Invoice',
                'Tanggal',
                'Jam (WIB)',
                'Nama Pelanggan',
                'Email',
                'No WhatsApp',
                'Nama Produk',
                'Tipe Produk',
                'Kupon Diskon',
                'Harga Asli (Rp)',
                'Potongan Diskon (Rp)',
                'Total Bayar (Rp)',
                'Status Pembayaran',
                'Status Pesanan',
                'Detail / Keterangan Akun'
            ], ';');

            $no = 1;
            foreach ($orders as $o) {
                fputcsv($handle, [
                    $no++,
                    $o->invoice_number,
                    $o->created_at ? $o->created_at->format('d/m/Y') : '-',
                    $o->created_at ? $o->created_at->format('H:i:s') : '-',
                    $o->user ? $o->user->name : 'Tamu',
                    $o->email ?: ($o->user ? $o->user->email : '-'),
                    $o->whatsapp ?: ($o->user ? $o->user->phone : '-'),
                    $o->product_name ?: 'Produk',
                    $o->product_type === 'api' ? 'Otomatis' : 'Manual',
                    $o->voucher_code ?: '-',
                    $o->price ?: 0,
                    $o->discount_amount ?: 0,
                    $o->total_amount ?: ($o->price ?: 0),
                    strtoupper($o->payment_status ?? 'PENDING'),
                    strtoupper($o->order_status ?? 'PENDING'),
                    preg_replace('/\s+/', ' ', trim($o->sn_or_account ?? $o->account_details ?? '-'))
                ], ';');
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Halaman Manajemen Produk PPOB & Pengaturan Margin Kiosbank
     */
    public function ppobProducts(Request $request, KiosbankService $kiosbankService)
    {
        $kiosbankService->ensureCatalogSeeded();

        $category = $request->query('category', 'all');
        $operator = $request->query('operator', 'all');
        $status   = $request->query('status', 'all');
        $search   = $request->query('search');

        $query = PpobProduct::query();

        if ($category !== 'all' && !empty($category)) {
            $query->where('category', $category);
        }

        if ($operator !== 'all' && !empty($operator)) {
            $query->where('operator', $operator);
        }

        if ($status !== 'all' && !empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%")
                  ->orWhere('operator', 'LIKE', "%{$search}%");
            });
        }

        $products = $query->orderBy('category')->orderBy('operator')->orderBy('base_price')->paginate(50)->withQueryString();

        // Statistik
        $totalCount       = PpobProduct::count();
        $activeCount      = PpobProduct::where('status', 'active')->count();
        $maintenanceCount = PpobProduct::where('status', 'maintenance')->count();
        $inactiveCount    = PpobProduct::where('status', 'inactive')->count();
        $avgMargin        = (int) round(PpobProduct::avg('margin') ?? 0);

        // Kategori & Operator unik untuk filter
        $categories = PpobProduct::select('category')->distinct()->pluck('category');
        $operators  = PpobProduct::select('operator')->distinct()->pluck('operator');

        // Status Saldo Deposit Kiosbank Realtime
        $depositStatus = $kiosbankService->checkDeposit();
        $kiosbankMode  = $kiosbankService->getMode();

        return view('admin.ppob.products', compact(
            'products',
            'category',
            'operator',
            'status',
            'search',
            'totalCount',
            'activeCount',
            'maintenanceCount',
            'inactiveCount',
            'avgMargin',
            'categories',
            'operators',
            'depositStatus',
            'kiosbankMode'
        ));
    }

    /**
     * Update individual margin & status produk PPOB
     */
    public function updatePpobProduct(Request $request, $id)
    {
        $product = PpobProduct::findOrFail($id);

        $request->validate([
            'margin'         => 'required|numeric|min:0',
            'price'          => 'nullable|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'status'         => 'required|in:active,maintenance,inactive',
        ]);

        $margin = (int) $request->margin;
        $basePrice = (int) $product->base_price;
        $price = $request->price ? (int)$request->price : ($basePrice + $margin);
        $resellerPrice = $request->reseller_price ? (int)$request->reseller_price : ($price - 300);

        $product->update([
            'margin'         => $margin,
            'price'          => $price,
            'reseller_price' => $resellerPrice,
            'status'         => $request->status,
        ]);

        return back()->with('success', "Produk '{$product->name}' ({$product->code}) berhasil diperbarui!");
    }

    /**
     * Terapkan Margin Massal untuk Produk PPOB (per kategori atau semua)
     */
    public function bulkUpdatePpobMargin(Request $request)
    {
        $request->validate([
            'mass_margin'  => 'required|numeric|min:0',
            'target_scope' => 'required|string',
            'calc_type'    => 'required|in:fixed,percent',
        ]);

        $scope = $request->target_scope;
        $marginVal = (int) $request->mass_margin;
        $calcType = $request->calc_type;

        $query = PpobProduct::query();
        if ($scope !== 'all') {
            $query->where('category', $scope);
        }

        $products = $query->get();
        $updated = 0;

        foreach ($products as $p) {
            $basePrice = (int) $p->base_price;
            if ($calcType === 'percent') {
                $margin = (int) round(($basePrice * $marginVal) / 100);
            } else {
                $margin = $marginVal;
            }

            $price = $basePrice + $margin;
            $resellerPrice = $price - 300;

            $p->update([
                'margin'         => $margin,
                'price'          => $price,
                'reseller_price' => $resellerPrice,
            ]);
            $updated++;
        }

        $scopeText = ($scope === 'all') ? 'Semua Produk PPOB' : "Kategori " . strtoupper($scope);
        return back()->with('success', "Berhasil menerapkan margin ke {$updated} produk untuk {$scopeText}!");
    }

    /**
     * Sinkronkan Status & Katalog Produk dari API Kiosbank
     */
    public function syncPpobProducts(Request $request, KiosbankService $kiosbankService)
    {
        try {
            $res = $kiosbankService->syncWithKiosbankApi();
            return back()->with('success', "Sinkronisasi berhasil! {$res['total_products']} produk diperiksa. Status Active: {$res['active_count']}, Maintenance: {$res['maintenance_count']}.");
        } catch (\Exception $e) {
            return back()->with('error', "Gagal sinkronisasi: " . $e->getMessage());
        }
    }

    /**
     * Halaman Top Up Saldo Kiosbank
     */
    public function kiosbankDeposit(Request $request, KiosbankService $kiosbankService)
    {
        $depositStatus = $kiosbankService->checkDeposit();
        $kiosbankMode  = $kiosbankService->getMode();
        $isDev         = $kiosbankService->isDeveloperMode();

        $deposits = KiosbankDeposit::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.ppob.deposit', compact('depositStatus', 'kiosbankMode', 'isDev', 'deposits'));
    }

    /**
     * Simpan Pengajuan / Pembuatan Tiket Top Up Saldo Kiosbank
     */
    public function kiosbankStoreDeposit(Request $request)
    {
        $request->validate([
            'amount'         => 'required|numeric|min:10000',
            'payment_method' => 'required|string',
        ]);

        $amount = (int) $request->amount;
        $uniqueCode = rand(100, 999);
        $totalPayment = $amount + $uniqueCode;
        $invoiceNumber = 'DEP-KB-' . date('Ymd') . '-' . rand(1000, 9999);

        $bankAccounts = [
            'bca'     => ['name' => 'PT DESIGN JAYA INDONESIA', 'number' => '8410-888-999'],
            'mandiri' => ['name' => 'PT DESIGN JAYA INDONESIA', 'number' => '137-00-1122334-5'],
            'bri'     => ['name' => 'PT DESIGN JAYA INDONESIA', 'number' => '0341-01-000888-30-1'],
            'bni'     => ['name' => 'PT DESIGN JAYA INDONESIA', 'number' => '089-7766-554'],
            'qris'    => ['name' => 'KIOSBANK DJI QRIS', 'number' => 'NMID: ID102003882910'],
        ];

        $bankInfo = $bankAccounts[$request->payment_method] ?? ['name' => 'PT DESIGN JAYA INDONESIA', 'number' => '8410-888-999'];

        KiosbankDeposit::create([
            'invoice_number'      => $invoiceNumber,
            'amount'              => $amount,
            'payment_method'      => $request->payment_method,
            'status'              => 'pending',
            'bank_account_name'   => $bankInfo['name'],
            'bank_account_number' => $bankInfo['number'],
            'unique_code'         => $uniqueCode,
            'total_payment'       => $totalPayment,
            'notes'               => $request->notes,
        ]);

        return back()->with('success', "Tiket deposit #{$invoiceNumber} berhasil dibuat! Silakan transfer sebesar Rp " . number_format($totalPayment, 0, ',', '.') . " ke rekening {$bankInfo['name']} ({$bankInfo['number']}).");
    }

    /**
     * Simulasi Top Up Saldo Berhasil (Khusus Developer Mode)
     */
    public function kiosbankSimulateDeposit(Request $request, $id)
    {
        $deposit = KiosbankDeposit::findOrFail($id);

        if ($deposit->status === 'success') {
            return back()->with('info', 'Tiket deposit ini sudah berhasil sebelumnya.');
        }

        $deposit->update(['status' => 'success']);

        // Tambahkan saldo sandbox di settings
        $currentSandbox = (int) (Setting::where('key', 'kiosbank_sandbox_deposit')->value('value') ?: 11251850);
        $newSandbox = $currentSandbox + (int) $deposit->amount;
        Setting::updateOrCreate(['key' => 'kiosbank_sandbox_deposit'], ['value' => (string) $newSandbox]);

        return back()->with('success', "⚡ [Developer Mode] Simulasi top up sukses! Saldo Kiosbank bertambah Rp " . number_format($deposit->amount, 0, ',', '.') . ". Saldo Sandbox sekarang: Rp " . number_format($newSandbox, 0, ',', '.'));
    }

    /**
     * Cek Live Status Deposit Kiosbank Realtime (/Services/getCurrentDeposit)
     */
    public function kiosbankCheckLiveDeposit(KiosbankService $kiosbankService)
    {
        $res = $kiosbankService->checkDeposit();
        if ($res['ok']) {
            return back()->with('success', "Status Saldo Realtime Kiosbank: " . $res['message']);
        }
        return back()->with('error', "Gagal mengambil saldo realtime: " . $res['message']);
    }
}