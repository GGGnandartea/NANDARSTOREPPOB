<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PpobController;
// --- RUTE HALAMAN DEPAN & CHECKOUT ---
Route::get('/', [FrontController::class, 'index'])->name('home');
Route::get('/product/{id}', [FrontController::class, 'show'])->name('product.detail')->whereNumber('id');

Route::get('/checkout/{id}', [FrontController::class, 'checkout'])->name('checkout')->whereNumber('id');
Route::post('/checkout/process', [FrontController::class, 'processCheckout'])->name('checkout.process')->middleware('throttle:payments');
Route::get('/checkout/status/{orderId}', [FrontController::class, 'checkStatus'])->name('checkout.status')->where('orderId', '[A-Za-z0-9\-_]+')->middleware('throttle:polling')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);
Route::get('/order/invoice/{orderId}', [FrontController::class, 'invoice'])->name('order.invoice')->where('orderId', '[A-Za-z0-9\-_]+')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);
Route::get('/invoice/{orderId}', [FrontController::class, 'invoice'])->name('invoice.direct')->where('orderId', '[A-Za-z0-9\-_]+')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);
Route::post('/order/{orderId}/resend-wa', [FrontController::class, 'resendOrderWa'])->name('order.resend_wa')->where('orderId', '[A-Za-z0-9\-_]+')->middleware('throttle:resend_wa')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);

// --- RUTE LAYANAN PPOB KIOSBANK ---
Route::get('/ppob', [PpobController::class, 'index'])->name('ppob.index');
Route::get('/ppob/products', [PpobController::class, 'getProducts'])->name('ppob.products');
Route::post('/ppob/inquiry', [PpobController::class, 'inquiry'])->name('ppob.inquiry')->middleware('throttle:ppob_inquiry');
Route::post('/ppob/checkout', [PpobController::class, 'checkout'])->name('ppob.checkout')->middleware('throttle:payments');
Route::get('/ppob/status/{orderId}', [PpobController::class, 'checkOrderStatus'])->name('ppob.status')->where('orderId', '[A-Za-z0-9\-_]+')->middleware('throttle:polling')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);
Route::post('/ppob/check-status/{orderId}', [PpobController::class, 'recheckStatus'])->name('ppob.recheck_status')->where('orderId', '[A-Za-z0-9\-_]+')->middleware('throttle:polling')->middleware(\App\Http\Middleware\AuthorizeOrderAccess::class);
// ----------------------------------


// --- PUSAT BANTUAN & SYARAT KETENTUAN (HALAMAN DEDIKASI) ---
Route::get('/faq', [FrontController::class, 'faq'])->name('faq');
Route::get('/syarat-ketentuan', [FrontController::class, 'terms'])->name('terms');
Route::get('/terms', [FrontController::class, 'terms'])->name('terms.alias');
// -----------------------------------------------------------

Route::get('/auth/session', [AuthController::class, 'session']);
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Reset Password via WhatsApp OTP
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password/request', [AuthController::class, 'requestPasswordResetOtp'])->name('password.request.otp')->middleware('throttle:auth_otp_request');
Route::post('/forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp'])->name('password.reset.otp')->middleware('throttle:auth_otp_reset');

Route::middleware(['auth'])->prefix('reseller')->name('reseller.')->group(function () {
    Route::get('/dashboard', function() { return view('reseller.dashboard'); })->name('dashboard');
});

Route::get('/reseller/payment-status/{orderId}', [AuthController::class, 'checkResellerPaymentStatus'])->name('reseller.payment_status')->middleware('throttle:polling');

// Halaman Profil User (login wajib)
Route::middleware(['auth'])->group(function () {
    Route::put('/favorites/{product}', [\App\Http\Controllers\FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{product}', [\App\Http\Controllers\FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('/profile', [FrontController::class, 'profile'])->name('profile');
    Route::post('/profile/orders/sync-pending', [FrontController::class, 'syncPendingOrders'])->name('profile.orders.sync_pending')->middleware('throttle:polling');
    Route::post('/profile/buy-balance', [FrontController::class, 'buyWithBalance'])->name('buy.balance')->middleware('throttle:payments');
    Route::post('/topup/create', [FrontController::class, 'createTopup'])->name('topup.create')->middleware('throttle:payments');
    Route::get('/topup/status/{orderId}', [FrontController::class, 'checkTopupStatus'])->name('topup.status')->middleware('throttle:polling');
    Route::post('/reseller/upgrade-balance', [FrontController::class, 'upgradeResellerWithBalance'])->name('reseller.upgrade_balance')->middleware('throttle:payments');
    Route::post('/reseller/upgrade-qris', [FrontController::class, 'upgradeResellerWithQris'])->name('reseller.upgrade_qris')->middleware('throttle:payments');
    Route::get('/profile/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'showForm'])->name('password.reset.form');
    Route::post('/profile/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'update'])->name('password.reset.update')->middleware('throttle:profile_security');
    Route::post('/profile/update', [FrontController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/change-whatsapp', [FrontController::class, 'changeWhatsapp'])->name('profile.change_whatsapp')->middleware('throttle:profile_security');
    Route::post('/profile/wa-verify/request', [FrontController::class, 'requestWaVerification'])->name('profile.wa_verify.request')->middleware('throttle:profile_security');
    Route::post('/profile/wa-verify/confirm', [FrontController::class, 'confirmWaVerification'])->name('profile.wa_verify.confirm')->middleware('throttle:profile_security');
    Route::get('/profile/wa-verify/status', [FrontController::class, 'checkWaVerificationStatus'])->name('profile.wa_verify.status');
});

// Area Admin Panel
Route::middleware(['auth', \App\Http\Middleware\EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::post('/orders/{id}/status', [AdminController::class, 'updateOrderStatus'])->name('orders.update_status');
    Route::post('/orders/{id}/retry-api', [AdminController::class, 'retryApiOrder'])->name('orders.retry_api');
    Route::post('/orders/sync-ppob-pending', [AdminController::class, 'syncAllPendingPpobOrders'])->name('orders.sync_ppob_pending');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [AdminController::class, 'exportReports'])->name('reports.export');
    
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'saveSettings'])->name('settings.save');
    Route::get('/branding', [AdminController::class, 'branding'])->name('branding');
    Route::post('/branding', [AdminController::class, 'saveBranding'])->name('branding.save');
    Route::get('/settings/api-status', [AdminController::class, 'checkApiLiveStatus'])->name('settings.api_status');
    Route::post('/settings/premku/deposit', [AdminController::class, 'premkuCreateDeposit'])->name('settings.premku.deposit');
    Route::post('/settings/premku/check-deposit', [AdminController::class, 'premkuCheckDeposit'])->name('settings.premku.check_deposit');
    Route::post('/settings/premku/cancel-deposit', [AdminController::class, 'premkuCancelDeposit'])->name('settings.premku.cancel_deposit');
    Route::post('/settings/kiosbank/generate-rsa', [AdminController::class, 'generateKiosbankRsaKeys'])->name('settings.kiosbank.generate_rsa');
    Route::post('/settings/kiosbank/test-signon', [AdminController::class, 'testKiosbankSignOn'])->name('settings.kiosbank.test_signon');
    
    // --- RUTE PAYMENT GATEWAY ADMIN (BARU) ---
    Route::get('/settings/payment', [AdminController::class, 'paymentSettings'])->name('settings.payment');
    Route::post('/settings/payment', [AdminController::class, 'savePaymentSettings'])->name('settings.payment.save');
    // -----------------------------------------
    
    Route::get('/terms', [AdminController::class, 'terms'])->name('terms');
    Route::post('/terms', [AdminController::class, 'saveTerms'])->name('terms.save');
    
    Route::get('/products/api', [AdminController::class, 'apiMargin'])->name('products.api');
    Route::post('/api-markup/{api_id}', [AdminController::class, 'updateApiMarkup'])->name('products.api.markup');
    Route::post('/api-markup-bulk', [AdminController::class, 'bulkUpdateApiMarkup'])->name('products.api.bulk_markup');
    Route::post('/products/api/toggle-provider', [AdminController::class, 'toggleProviderProducts'])->name('products.api.toggle_provider');
    // --------------------------

    // --- RUTE MANAJEMEN PPOB KIOSBANK & DEPOSIT ADMIN ---
    Route::get('/ppob/settings', [\App\Http\Controllers\KiosbankSettingsController::class, 'edit'])->name('ppob.settings');
    Route::post('/ppob/settings', [\App\Http\Controllers\KiosbankSettingsController::class, 'update'])->name('ppob.settings.update');
    Route::get('/ppob/products', [AdminController::class, 'ppobProducts'])->name('ppob.products');
    Route::post('/ppob/products/bulk-margin', [AdminController::class, 'bulkUpdatePpobMargin'])->name('ppob.products.bulk_margin');
    Route::post('/ppob/products/{id}/update', [AdminController::class, 'updatePpobProduct'])->name('ppob.products.update');
    Route::post('/ppob/products/sync', [AdminController::class, 'syncPpobProducts'])->name('ppob.products.sync');
    Route::get('/ppob/deposit', [AdminController::class, 'kiosbankDeposit'])->name('ppob.deposit');
    Route::post('/ppob/deposit/create', [AdminController::class, 'kiosbankStoreDeposit'])->name('ppob.deposit.create');
    Route::post('/ppob/deposit/{id}/simulate', [AdminController::class, 'kiosbankSimulateDeposit'])->name('ppob.deposit.simulate');
    Route::post('/ppob/deposit/check-live', [AdminController::class, 'kiosbankCheckLiveDeposit'])->name('ppob.deposit.check_live');
    Route::post('/ppob/deposit/low-balance-alert', [AdminController::class, 'kiosbankSaveLowBalanceAlert'])->name('ppob.deposit.low_balance_alert');
    // ----------------------------------------------------
    
    Route::get('/products/local', [AdminController::class, 'localProducts'])->name('products.local');
    Route::post('/products/local', [AdminController::class, 'storeLocalProduct'])->name('products.local.store');
    Route::post('/products/local/{id}/update', [AdminController::class, 'updateLocalProduct'])->name('products.local.update');
    Route::post('/products/local/{id}/toggle', [AdminController::class, 'toggleLocalProduct'])->name('products.local.toggle');
    Route::post('/products/local/{id}/delete', [AdminController::class, 'deleteLocalProduct'])->name('products.local.delete');
    
    Route::get('/products/local/{id}/accounts', [AdminController::class, 'localAccounts'])->name('products.local.accounts');
    Route::post('/products/local/{id}/accounts', [AdminController::class, 'storeLocalAccount'])->name('products.local.accounts.store');
    Route::post('/products/local/{id}/accounts/clear', [AdminController::class, 'clearLocalAccounts'])->name('products.local.accounts.clear');
    Route::post('/products/local/accounts/{id}/update', [AdminController::class, 'updateLocalAccount'])->name('products.local.accounts.update');
    Route::post('/products/local/accounts/{id}/delete', [AdminController::class, 'deleteLocalAccount'])->name('products.local.accounts.delete');
    
    Route::get('/resellers', [AdminController::class, 'resellers'])->name('resellers');
    Route::get('/resellers/settings', [AdminController::class, 'resellerSettings'])->name('resellers.settings');
    Route::post('/resellers/settings', [AdminController::class, 'saveResellerSettings'])->name('resellers.settings.save');
    Route::post('/resellers/settings/toggle-free', [AdminController::class, 'toggleResellerFreePromo'])->name('resellers.settings.toggle_free');
    Route::post('/resellers', [AdminController::class, 'storeReseller'])->name('resellers.store');
    Route::post('/resellers/{id}/balance', [AdminController::class, 'manageResellerBalance'])->name('resellers.balance');
    Route::post('/resellers/{id}/toggle', [AdminController::class, 'toggleResellerStatus'])->name('resellers.toggle');
    Route::post('/resellers/{id}/delete', [AdminController::class, 'deleteReseller'])->name('resellers.delete');
    
    Route::get('/vouchers', [AdminController::class, 'vouchers'])->name('vouchers');
    Route::post('/vouchers', [AdminController::class, 'storeVoucher'])->name('vouchers.store');
    Route::post('/vouchers/{id}/toggle', [AdminController::class, 'toggleVoucher'])->name('vouchers.toggle');
    Route::post('/vouchers/{id}/delete', [AdminController::class, 'deleteVoucher'])->name('vouchers.delete');

    // --- RUTE VERIFIKASI & GATEWAY WHATSAPP ADMIN ---
    Route::get('/wa-verifications', [AdminController::class, 'waVerifications'])->name('wa_verifications');
    Route::post('/wa-verifications/approve', [AdminController::class, 'approveWaVerification'])->name('wa_verifications.approve');
    Route::post('/wa-verifications/reject', [AdminController::class, 'rejectWaVerification'])->name('wa_verifications.reject');
    Route::post('/wa-verifications/update-phone', [AdminController::class, 'updateUserWhatsapp'])->name('wa_verifications.update_phone');
    Route::get('/wa-gateway/status', [AdminController::class, 'waGatewayStatus'])->name('wa_gateway.status');
    Route::post('/wa-gateway/logout', [AdminController::class, 'waGatewayLogout'])->name('wa_gateway.logout');
    Route::post('/wa-gateway/test-send', [AdminController::class, 'waGatewayTestSend'])->name('wa_gateway.test_send');
    Route::post('/wa-gateway/settings', [AdminController::class, 'waGatewaySaveSettings'])->name('wa_gateway.settings');

    // --- RUTE KELOLA BANNER ADMIN ---
    Route::get('/banners', [AdminController::class, 'banners'])->name('banners');
    Route::post('/banners', [AdminController::class, 'storeBanner'])->name('banners.store');
    Route::post('/banners/{id}/update', [AdminController::class, 'updateBanner'])->name('banners.update');
    Route::post('/banners/{id}/toggle', [AdminController::class, 'toggleBanner'])->name('banners.toggle');
    Route::post('/banners/{id}/delete', [AdminController::class, 'deleteBanner'])->name('banners.delete');
});

// Route API untuk Frontend (Cek Voucher)
Route::post('/api/check-voucher', [\App\Http\Controllers\FrontController::class, 'checkVoucher'])->middleware('throttle:voucher_check');

// Route API untuk Produk Paling Banyak Dibeli Hari Ini (Realtime Harian)
Route::get('/api/top-selling-today', function() {
    $apiData = \Illuminate\Support\Facades\Cache::get('api_full_data', []);
    $controller = app(\App\Http\Controllers\FrontController::class);
    return response()->json([
        'status' => 'success',
        'data' => $controller->getTopSellingTodayProducts($apiData),
    ]);
})->middleware('throttle:polling');

// Route Webhook untuk Node.js Payment Gateway Lokal
// (Tanpa Middleware CSRF agar server Node.js bisa menembaknya langsung)
Route::post('/api/webhook/qris', [\App\Http\Controllers\WebhookController::class, 'handle'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Route Webhook untuk Top Up QRIS
Route::post('/api/webhook/topup-qris', [\App\Http\Controllers\WebhookController::class, 'handleTopup'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Route Webhook untuk Registrasi Reseller QRIS
Route::post('/api/webhook/reseller-qris', [\App\Http\Controllers\WebhookController::class, 'handleResellerQris'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Route Webhook Pesan Masuk WA (Otomatis Verifikasi WhatsApp via Kode Unik)
Route::post('/api/webhook/wa-incoming', [\App\Http\Controllers\WebhookController::class, 'handleWaIncoming'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Route Webhook Callback Resmi Kiosbank PPOB
Route::post('/api/webhook/kiosbank', [\App\Http\Controllers\WebhookController::class, 'handleKiosbank'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Route Webhook Callback Resmi Premku API (v3.8 Instant + Webhook)
Route::post('/api/webhook/premku', [\App\Http\Controllers\WebhookController::class, 'handlePremku'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/webhook/premku', [\App\Http\Controllers\WebhookController::class, 'handlePremku'])->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
