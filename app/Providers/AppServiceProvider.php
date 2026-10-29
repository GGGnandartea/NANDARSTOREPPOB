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
        $uidSuffix = function_exists('posix_geteuid') ? (string) posix_geteuid() : 'app';
        $tempBase = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        $safeCacheDir = $tempBase . DIRECTORY_SEPARATOR . 'pk_cache_v2_' . $uidSuffix;
        if (!is_dir($safeCacheDir)) {
            @mkdir($safeCacheDir, 0777, true);
        }

        // Self-healing cache & rate limiter: hindari fopen Permission denied di storage/framework/cache/data
        // akibat perbedaan user root (CLI/cron) vs www (web server).
        // Jangan set 'permission' => 0666 karena FileStore::ensurePermissionsAreCorrect juga men-chmod direktori cache!
        config([
            'cache.stores.file_safe' => [
                'driver' => 'file',
                'path' => $safeCacheDir,
                'lock_path' => $safeCacheDir,
            ],
            'cache.stores.failover' => [
                'driver' => 'failover',
                'stores' => ['file_safe', 'database', 'array'],
            ],
            'cache.stores.file' => [
                'driver' => 'failover',
                'stores' => ['file_safe', 'database', 'array'],
            ],
            'cache.default' => 'failover',
            'cache.limiter' => 'failover',
            'logging.channels.stack.ignore_exceptions' => true,
        ]);

        // Self-healing session: jika SESSION_DRIVER=file tapi folder sessions terkunci oleh root, gunakan folder temp per-UID
        if (config('session.driver') === 'file') {
            $sessionDir = (string) config('session.files', storage_path('framework/sessions'));
            if (!is_dir($sessionDir) || !is_writable($sessionDir)) {
                $safeSessionDir = $tempBase . DIRECTORY_SEPARATOR . 'pk_sessions_' . $uidSuffix;
                if (!is_dir($safeSessionDir)) {
                    @mkdir($safeSessionDir, 0777, true);
                }
                config(['session.files' => $safeSessionDir]);
            }
        }

        // Self-healing Blade views: jika storage/framework/views tidak writable, gunakan folder temp per-UID
        $viewsDir = (string) config('view.compiled', storage_path('framework/views'));
        if (!is_dir($viewsDir) || !is_writable($viewsDir)) {
            $safeViewsDir = $tempBase . DIRECTORY_SEPARATOR . 'pk_views_' . $uidSuffix;
            if (!is_dir($safeViewsDir)) {
                @mkdir($safeViewsDir, 0777, true);
            }
            config(['view.compiled' => $safeViewsDir]);
        }

        // Self-healing Log file: jika storage/logs/laravel.log terkunci oleh root, arahkan ke file log per-UID
        $defaultLogFile = storage_path('logs/laravel.log');
        $logsDir = storage_path('logs');
        if ((file_exists($defaultLogFile) && !is_writable($defaultLogFile)) || (!file_exists($defaultLogFile) && !is_writable($logsDir))) {
            $fallbackLog = (is_dir($logsDir) && is_writable($logsDir))
                ? storage_path('logs/laravel-' . $uidSuffix . '.log')
                : ($tempBase . DIRECTORY_SEPARATOR . 'pk_laravel_' . $uidSuffix . '.log');
            config([
                'logging.channels.single.path' => $fallbackLog,
                'logging.channels.daily.path' => $fallbackLog,
                'logging.channels.emergency.path' => $fallbackLog,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        if (
            str_starts_with((string) config('app.url'), 'https://') ||
            $request->header('X-Forwarded-Proto') === 'https' ||
            str_contains((string) $request->header('CF-Visitor'), 'https')
        ) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Kunci ketat parameter URL secara global (Mencegah URL Tampering & SQL Injection di Route)
        Route::pattern('id', '[0-9]+');
        Route::pattern('orderId', '[A-Za-z0-9\-_]+');
        Route::pattern('api_id', '[0-9]+');

        // Helper resolusi IP asli pengunjung di balik Cloudflare / Nginx Reverse Proxy
        $resolveClientIp = static function (Request $req): string {
            $cfIp = trim((string) $req->header('CF-Connecting-IP', ''));
            if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
            $realIp = trim((string) $req->header('X-Real-IP', ''));
            if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
            return (string) ($req->ip() ?: '127.0.0.1');
        };

        // --- KONFIGURASI RATE LIMITING SISTEM ---
        
        // 1. Rate Limiter Form Login (Maks 8 percobaan per menit per IP + Akun)
        RateLimiter::for('login', function (Request $request) use ($resolveClientIp) {
            $email = Str::transliterate(Str::lower(trim((string) $request->input('email', ''))));
            return Limit::perMinute(8)->by($resolveClientIp($request) . '|' . $email);
        });

        // 2. Rate Limiter Pendaftaran / Register (Maks 5 pendaftaran per menit per IP)
        RateLimiter::for('register', function (Request $request) use ($resolveClientIp) {
            return Limit::perMinute(5)->by($resolveClientIp($request));
        });

        // 3. Rate Limiter Permintaan OTP Reset Password via WA (Maks 3 per menit per IP & No HP)
        RateLimiter::for('auth_otp_request', function (Request $request) use ($resolveClientIp) {
            $phone = preg_replace('/[^0-9]/', '', (string) $request->input('whatsapp', ''));
            return Limit::perMinute(3)->by($resolveClientIp($request) . '|' . $phone);
        });

        // 4. Rate Limiter Verifikasi Kode OTP Reset Password (Maks 5 percobaan per menit per IP & No HP)
        RateLimiter::for('auth_otp_reset', function (Request $request) use ($resolveClientIp) {
            $phone = preg_replace('/[^0-9]/', '', (string) $request->input('whatsapp', ''));
            return Limit::perMinute(5)->by($resolveClientIp($request) . '|' . $phone);
        });

        // 5. Rate Limiter Transaksi & Pembayaran (Checkout, Topup, Buy with Balance, Upgrade Reseller)
        // Maks 10 transaksi per menit per User / IP untuk mencegah spamming payment gateway & race conditions
        RateLimiter::for('payments', function (Request $request) use ($resolveClientIp) {
            $key = $request->user()?->id ?: $resolveClientIp($request);
            return Limit::perMinute(10)->by($key);
        });

        // 6. Rate Limiter Inquiry Tagihan PPOB (Maks 30 pengecekan per menit)
        RateLimiter::for('ppob_inquiry', function (Request $request) use ($resolveClientIp) {
            $key = $request->user()?->id ?: $resolveClientIp($request);
            return Limit::perMinute(30)->by($key);
        });

        // 7. Rate Limiter Pengecekan Voucher (Maks 15 percobaan per menit per IP untuk mencegah kupon brute force)
        RateLimiter::for('voucher_check', function (Request $request) use ($resolveClientIp) {
            return Limit::perMinute(15)->by($resolveClientIp($request));
        });

        // 8. Rate Limiter Pengiriman Ulang WhatsApp (Maks 3 kali per menit per IP)
        RateLimiter::for('resend_wa', function (Request $request) use ($resolveClientIp) {
            return Limit::perMinute(3)->by($resolveClientIp($request));
        });

        // 9. Rate Limiter Fitur Keamanan Profil (Ubah WA, Verifikasi WA, Ganti Password)
        RateLimiter::for('profile_security', function (Request $request) use ($resolveClientIp) {
            $key = $request->user()?->id ?: $resolveClientIp($request);
            return Limit::perMinute(5)->by($key);
        });

        // 10. Rate Limiter Polling Status Transaksi (Maks 60 request per menit per IP)
        RateLimiter::for('polling', function (Request $request) use ($resolveClientIp) {
            return Limit::perMinute(60)->by($resolveClientIp($request));
        });
    }
}
