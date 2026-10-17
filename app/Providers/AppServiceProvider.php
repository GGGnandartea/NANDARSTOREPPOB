<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Kunci ketat parameter URL secara global (Mencegah URL Tampering & SQL Injection di Route)
        Route::pattern('id', '[0-9]+');
        Route::pattern('orderId', '[A-Za-z0-9\-_]+');
        Route::pattern('api_id', '[0-9]+');
    }
}
