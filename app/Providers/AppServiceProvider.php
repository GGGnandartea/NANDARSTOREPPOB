<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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

        // --- KONFIGURASI RATE LIMITING SISTEM ---
        
        // 1. Rate Limiter Form Login (Maks 5 percobaan per menit per IP + Akun)
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(Str::lower($request->input('email', '')));
            return Limit::perMinute(5)->by($request->ip() . '|' . $email);
        });

        // 2. Rate Limiter Pendaftaran / Register (Maks 5 pendaftaran per menit per IP)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // 3. Rate Limiter Permintaan OTP Reset Password via WA (Maks 3 per menit per IP & No HP)
        RateLimiter::for('auth_otp_request', function (Request $request) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('whatsapp', ''));
            return Limit::perMinute(3)->by($request->ip() . '|' . $phone);
        });

        // 4. Rate Limiter Verifikasi Kode OTP Reset Password (Maks 5 percobaan per menit per IP & No HP)
        RateLimiter::for('auth_otp_reset', function (Request $request) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('whatsapp', ''));
            return Limit::perMinute(5)->by($request->ip() . '|' . $phone);
        });

        // 5. Rate Limiter Transaksi & Pembayaran (Checkout, Topup, Buy with Balance, Upgrade Reseller)
        // Maks 10 transaksi per menit per User / IP untuk mencegah spamming payment gateway & race conditions
        RateLimiter::for('payments', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(10)->by($key);
        });

        // 6. Rate Limiter Inquiry Tagihan PPOB (Maks 30 pengecekan per menit)
        RateLimiter::for('ppob_inquiry', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(30)->by($key);
        });

        // 7. Rate Limiter Pengecekan Voucher (Maks 15 percobaan per menit per IP untuk mencegah kupon brute force)
        RateLimiter::for('voucher_check', function (Request $request) {
            return Limit::perMinute(15)->by($request->ip());
        });

        // 8. Rate Limiter Pengiriman Ulang WhatsApp (Maks 3 kali per menit per IP)
        RateLimiter::for('resend_wa', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // 9. Rate Limiter Fitur Keamanan Profil (Ubah WA, Verifikasi WA, Ganti Password)
        RateLimiter::for('profile_security', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(5)->by($key);
        });

        // 10. Rate Limiter Polling Status Transaksi (Maks 60 request per menit per IP)
        RateLimiter::for('polling', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }
}
