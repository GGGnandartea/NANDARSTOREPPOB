<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ResellerOpenApiController;
use App\Http\Middleware\AuthenticateResellerOpenApi;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook Callback Premku API v3.8
Route::post('/webhook/premku', [\App\Http\Controllers\WebhookController::class, 'handlePremku']);

// =========================================================================
// RESELLER OPEN API v1 (KHUSUS MITRA VIP RESELLER - SALDO AKUN)
// Cakupan Ketat: Aplikasi Premium Lokal, Premku, dan Preflix
// =========================================================================
Route::prefix('v1')
    ->middleware([AuthenticateResellerOpenApi::class])
    ->group(function () {
        Route::get('/ping', [ResellerOpenApiController::class, 'ping'])->name('open_api.v1.ping');
        Route::get('/profile', [ResellerOpenApiController::class, 'profile'])->name('open_api.v1.profile');
        Route::get('/balance', [ResellerOpenApiController::class, 'profile'])->name('open_api.v1.balance');

        Route::get('/products', [ResellerOpenApiController::class, 'products'])->name('open_api.v1.products');
        Route::get('/products/{productId}', [ResellerOpenApiController::class, 'productDetail'])
            ->where('productId', '[A-Za-z0-9\-_]+')
            ->name('open_api.v1.product_detail');

        Route::post('/orders', [ResellerOpenApiController::class, 'createOrder'])->name('open_api.v1.orders.create');
        Route::get('/orders', [ResellerOpenApiController::class, 'orders'])->name('open_api.v1.orders.index');
        Route::post('/orders/status', [ResellerOpenApiController::class, 'orderStatus'])->name('open_api.v1.orders.status_post');
        Route::get('/orders/{orderRef}', [ResellerOpenApiController::class, 'orderStatus'])
            ->where('orderRef', '[A-Za-z0-9\-_.:]+')
            ->name('open_api.v1.orders.status');
    });
