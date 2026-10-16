<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Webhook Callback Premku API v3.8
Route::post('/webhook/premku', [\App\Http\Controllers\WebhookController::class, 'handlePremku']);
