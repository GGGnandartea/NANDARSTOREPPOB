<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ResellerRegistration;
use App\Models\Setting;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected function getTurnstileConfig(): array
    {
        $enabled    = Setting::where('key', 'turnstile_enabled')->value('value') === '1';
        $siteKey    = trim((string) (Setting::where('key', 'turnstile_site_key')->value('value') ?: env('TURNSTILE_SITE_KEY', '')));
        $secretKey  = trim((string) (Setting::where('key', 'turnstile_secret_key')->value('value') ?: env('TURNSTILE_SECRET_KEY', '')));
        $onRegister = (Setting::where('key', 'turnstile_on_register')->value('value') ?? '1') === '1';
        $onLogin    = (Setting::where('key', 'turnstile_on_login')->value('value') ?? '0') === '1';

        $isConfigured = $enabled && $siteKey !== '' && $secretKey !== '';

        return [
            'enabled'              => $isConfigured,
            'site_key'             => $siteKey,
            'secret_key'           => $secretKey,
            'enabled_for_register' => $isConfigured && $onRegister,
            'enabled_for_login'    => $isConfigured && $onLogin,
        ];
    }

    protected function verifyTurnstileToken(Request $request, string $context = 'register'): void
    {
        $config = $this->getTurnstileConfig();
        $shouldVerify = $context === 'login'
            ? $config['enabled_for_login']
            : $config['enabled_for_register'];

        if (!$shouldVerify) {
            return;
        }

        $token = trim((string) ($request->input('cf-turnstile-response') ?: $request->input('turnstile_token', '')));
        if ($token === '') {
            throw ValidationException::withMessages([
                'turnstile' => 'Silakan selesaikan verifikasi keamanan Cloudflare terlebih dahulu.',
            ]);
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => $config['secret_key'],
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

            $result = $response->json();
            if (!$response->successful() || empty($result['success'])) {
                throw ValidationException::withMessages([
                    'turnstile' => 'Verifikasi keamanan Cloudflare gagal atau sudah kedaluwarsa. Silakan ulangi centang verifikasi.',
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[Turnstile] Gagal menghubungi siteverify Cloudflare: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'turnstile' => 'Gagal memvalidasi keamanan Cloudflare. Silakan periksa koneksi dan coba lagi.',
            ]);
        }
    }

    public function session(Request $request) {
        return response()->json(['token' => $request->session()->token()])
            ->header('Cache-Control', 'private, no-store, max-age=0');
    }

    public function showLoginForm() {
        $ts = $this->getTurnstileConfig();
        return Inertia::render('Login', [
            'turnstile' => [
                'enabled'  => $ts['enabled_for_login'],
                'site_key' => $ts['site_key'],
            ],
        ]);
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $this->verifyTurnstileToken($request, 'login');

        $emailInput = trim($credentials['email']);
        $plainPassword = (string) $credentials['password'];

        $user = User::where('email', $emailInput)->first();
        if (!$user && in_array(strtolower($emailInput), ['admin@nandarstore.com', 'admin@premiumkita.com'], true)) {
            $altEmail = strtolower($emailInput) === 'admin@nandarstore.com' ? 'admin@premiumkita.com' : 'admin@nandarstore.com';
            $user = User::where('email', $altEmail)->first()
                ?: User::where('role', 'admin')->first();
        }

        if ($user) {
            $storedPassword = (string) $user->getAuthPassword();
            $algoName = password_get_info($storedPassword)['algoName'] ?? 'unknown';

            // Otomatis perbaiki jika password di database diubah lewat phpMyAdmin (plain text / MD5 / SHA1 / non-bcrypt)
            if ($algoName !== 'bcrypt') {
                $isLegacyMatch = false;
                if ($storedPassword !== '' && hash_equals($storedPassword, $plainPassword)) {
                    $isLegacyMatch = true;
                } elseif (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), md5($plainPassword))) {
                    $isLegacyMatch = true;
                } elseif (strlen($storedPassword) === 40 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), sha1($plainPassword))) {
                    $isLegacyMatch = true;
                } elseif ($algoName !== 'unknown' && password_verify($plainPassword, $storedPassword)) {
                    $isLegacyMatch = true;
                }

                if ($isLegacyMatch) {
                    $newHash = Hash::make($plainPassword);
                    \Illuminate\Support\Facades\DB::table('users')
                        ->where('id', $user->id)
                        ->update(['password' => $newHash]);
                    $user->password = $newHash;
                } else {
                    throw ValidationException::withMessages([
                        'email' => 'Email atau password yang Anda masukkan salah.',
                    ]);
                }
            }

            $credentials['email'] = $user->email;
        }

        try {
            $authenticated = Auth::attempt($credentials, $request->boolean('remember'));
        } catch (\RuntimeException $e) {
            $authenticated = false;
        }

        if ($authenticated) {
            $user = Auth::user();

            // CEK APAKAH RESELLER BELUM LUNAS / BELUM AKTIF
            if ($user->role === 'reseller' && !$user->is_active) {
                $isFree = Setting::where('key', 'reseller_registration_free')->value('value') == '1';
                if ($isFree) {
                    $user->update(['is_active' => true]);
                } else {
                    $fee = (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000);
                    Auth::logout();
                    if ($request->wantsJson()) {
                        throw ValidationException::withMessages([
                            'email' => 'Akun Reseller Anda belum aktif karena belum menyelesaikan pembayaran pendaftaran Rp ' . number_format($fee, 0, ',', '.') . '.',
                        ]);
                    }
                    $pendingReg = ResellerRegistration::where('user_id', $user->id)->where('status', 'pending')->latest()->first();
                    return back()->withErrors([
                        'email' => 'Akun Reseller Anda belum aktif karena belum menyelesaikan pembayaran pendaftaran Rp ' . number_format($fee, 0, ',', '.') . '.',
                    ])->with('pending_order_id', $pendingReg ? $pendingReg->order_id_gateway : null);
                }
            }

            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'redirect' => route($user->role === 'admin' ? 'admin.dashboard' : 'home', [], false),
                ]);
            }
            
            if ($user->role === 'admin') {
                return Inertia::location(route('admin.dashboard', [], false));
            }

            // Seluruh pengguna (user dan reseller) langsung diarahkan ke halaman katalog/dashboard produk
            return redirect(route('home', [], false));
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    public function showRegisterForm() {
        $isFree = Setting::where('key', 'reseller_registration_free')->value('value') == '1';
        $fee = (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000);
        $promoTitle = Setting::where('key', 'reseller_promo_title')->value('value') ?: 'PROMO SPESIAL: DAFTAR RESELLER VIP GRATIS!';
        $promoMessage = Setting::where('key', 'reseller_promo_message')->value('value') ?: 'Nikmati harga modal termurah khusus mitra Reseller VIP langsung aktif tanpa biaya aktivasi!';
        $promoBadge = Setting::where('key', 'reseller_promo_badge')->value('value') ?: 'PROMO TERBATAS';
        $ts = $this->getTurnstileConfig();

        return Inertia::render('Register', [
            'resellerSettings' => [
                'is_free'       => $isFree,
                'fee'           => $fee,
                'promo_title'   => $promoTitle,
                'promo_message' => $promoMessage,
                'promo_badge'   => $promoBadge,
            ],
            'turnstile' => [
                'enabled'  => $ts['enabled_for_register'],
                'site_key' => $ts['site_key'],
            ],
        ]);
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'whatsapp' => 'nullable|string|max:20',
            'role' => 'nullable|in:user,reseller',
        ], [
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.'
        ]);

        $this->verifyTurnstileToken($request, 'register');

        $isReseller = $request->role === 'reseller';
        $isFree = Setting::where('key', 'reseller_registration_free')->value('value') == '1';
        $fee = (int) (Setting::where('key', 'reseller_registration_fee')->value('value') ?: 20000);

        // Jika pendaftaran reseller gratis, akun langsung aktif
        $isActive = $isReseller ? ($isFree || $fee <= 0) : true;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp ?? '',
            'password' => Hash::make($request->password),
            'role' => $isReseller ? 'reseller' : 'user',
            'is_active' => $isActive,
            'balance' => 0,
        ]);

        if ($isReseller) {
            if ($isFree || $fee <= 0) {
                // Catat log histori aktivasi promo
                TransactionLog::create([
                    'user_id'     => $user->id,
                    'type'        => 'reseller_promo',
                    'amount'      => 0,
                    'description' => 'Aktivasi Kemitraan VIP Reseller (Promo Gratis)',
                ]);

                Auth::login($user);
                $request->session()->regenerate();

                return response()->json([
                    'status'   => 'success',
                    'is_free'  => true,
                    'redirect' => route('home', [], false),
                    'message'  => 'Selamat! Akun Reseller VIP Anda berhasil terdaftar dan langsung aktif (Promo Gratis).',
                ]);
            }

            // Mode Berbayar (QRIS Nominal Sesuai Pengaturan Admin)
            $invoiceNumber = 'RES-' . strtoupper(Str::random(8)) . '-' . time();
            $callbackUrl = url('/api/webhook/reseller-qris');
            $qrisUrl = null;
            $orderIdGateway = $invoiceNumber;
            $totalNominal = $fee;

            try {
                $response = Http::timeout(5)->post('http://127.0.0.1:4061/api/order', [
                    'nominal'      => $fee,
                    'callback_url' => $callbackUrl,
                ]);
                $data = $response->json();
                if (isset($data['data']['orderId'])) {
                    $orderIdGateway = $data['data']['orderId'];
                    $totalNominal = $data['data']['total_nominal'] ?? $fee;
                    $qrisUrl = $data['data']['url_qris'] ?? null;
                }
            } catch (\Exception $e) {}

            ResellerRegistration::create([
                'user_id'          => $user->id,
                'invoice_number'   => $invoiceNumber,
                'order_id_gateway' => $orderIdGateway,
                'amount'           => $fee,
                'status'           => 'pending',
                'qris_url'         => $qrisUrl,
                'payment_method'   => 'qris',
            ]);

            return response()->json([
                'status'        => 'reseller_payment_required',
                'user_id'       => $user->id,
                'order_id'      => $orderIdGateway,
                'invoice'       => $invoiceNumber,
                'amount'        => $fee,
                'total_nominal' => $totalNominal,
                'url_qris'      => $qrisUrl,
                'message'       => 'Pendaftaran reseller berhasil disimpan. Silakan lakukan pembayaran Rp ' . number_format($totalNominal, 0, ',', '.') . ' untuk aktivasi akun.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect' => route('home', [], false)]);
        }

        return redirect(route('home', [], false));
    }

    public function checkResellerPaymentStatus($orderId) {
        $reg = ResellerRegistration::where('order_id_gateway', $orderId)
            ->orWhere('invoice_number', $orderId)
            ->first();

        if (!$reg) return response()->json(['status' => 'NOT_FOUND']);

        return response()->json([
            'status' => $reg->status,
            'amount' => $reg->amount,
        ]);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return Inertia::location(route('home'));
    }

    // --- FITUR RESET PASSWORD VIA WHATSAPP OTP ---
    public function showForgotPasswordForm() {
        return Inertia::render('ForgotPassword', [
            'botNumber' => '08990738216',
        ]);
    }

    public function requestPasswordResetOtp(Request $request) {
        $request->validate([
            'whatsapp' => 'required|string|min:8|max:20',
        ], [
            'whatsapp.required' => 'Nomor WhatsApp terdaftar wajib diisi.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->whatsapp);
        if (str_starts_with($cleanPhone, '628')) {
            $cleanPhone = '08' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '08' . substr($cleanPhone, 1);
        }
        $altPhone = str_starts_with($cleanPhone, '0') ? ('62' . substr($cleanPhone, 1)) : ('0' . substr($cleanPhone, 2));

        $user = User::where(function($q) use ($cleanPhone, $altPhone) {
            $q->where('whatsapp', $cleanPhone)
              ->orWhere('whatsapp', $altPhone);
        })->first();

        if (!$user) {
            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di PremiumKita.',
                    'errors'  => ['whatsapp' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di PremiumKita.'],
                ], 422);
            }
            return back()->withErrors([
                'whatsapp' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di PremiumKita.',
            ]);
        }

        // Generate 6 digit numeric OTP & unique trigger code
        $otp = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $triggerCode = 'OTP-' . strtoupper(Str::random(5));

        $user->update([
            'otp_code'         => $otp,
            'otp_trigger_code' => $triggerCode,
            'otp_expires_at'   => now()->addMinutes(15),
        ]);

        $botPhone = '08990738216';
        $botWaUrl = 'https://wa.me/628990738216?text=' . urlencode("Halo Bot PremiumKita, saya meminta kode OTP reset password akun saya. Kode Verifikasi: {$triggerCode}");

        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'status'       => 'success',
                'message'      => 'Kode verifikasi telah dibuat! Kirimkan pesan verifikasi ke WhatsApp Bot untuk mendapatkan 6 digit OTP.',
                'step'         => 'awaiting_otp',
                'whatsapp'     => $cleanPhone,
                'user_name'    => $user->name,
                'trigger_code' => $triggerCode,
                'bot_number'   => $botPhone,
                'bot_wa_url'   => $botWaUrl,
            ]);
        }

        return back()->with([
            'success'      => 'Kode verifikasi telah dibuat! Kirimkan pesan verifikasi ke WhatsApp Bot untuk mendapatkan 6 digit OTP.',
            'step'         => 'awaiting_otp',
            'whatsapp'     => $cleanPhone,
            'user_name'    => $user->name,
            'trigger_code' => $triggerCode,
            'bot_number'   => $botPhone,
            'bot_wa_url'   => $botWaUrl,
        ]);
    }

    public function resetPasswordWithOtp(Request $request) {
        $request->validate([
            'whatsapp' => 'required|string',
            'otp_code' => 'required|string|min:4|max:10',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'otp_code.required' => 'Kode OTP wajib diisi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $cleanPhone = preg_replace('/[^0-9]/', '', $request->whatsapp);
        if (str_starts_with($cleanPhone, '628')) {
            $cleanPhone = '08' . substr($cleanPhone, 3);
        } elseif (str_starts_with($cleanPhone, '8')) {
            $cleanPhone = '08' . substr($cleanPhone, 1);
        }
        $altPhone = str_starts_with($cleanPhone, '0') ? ('62' . substr($cleanPhone, 1)) : ('0' . substr($cleanPhone, 2));

        $user = User::where(function($q) use ($cleanPhone, $altPhone) {
            $q->where('whatsapp', $cleanPhone)
              ->orWhere('whatsapp', $altPhone);
        })->first();

        if (!$user) {
            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Pengguna dengan nomor WhatsApp ini tidak ditemukan.'], 422);
            }
            return back()->withErrors(['otp_code' => 'Pengguna dengan nomor WhatsApp ini tidak ditemukan.']);
        }

        // Cek apakah kode OTP sudah hangus atau sudah pernah digunakan
        if (empty($user->otp_code) || empty($user->otp_trigger_code)) {
            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP ini sudah hangus atau sudah pernah digunakan sebelumnya. Silakan buat permintaan kode baru.',
                ], 422);
            }
            return back()->withErrors(['otp_code' => 'Kode OTP ini sudah hangus atau sudah pernah digunakan sebelumnya. Silakan buat permintaan kode baru.']);
        }

        // Cek apakah waktu melebihi 15 menit (KADALUARSA / HANGUS)
        $isExpired = empty($user->otp_expires_at) || \Carbon\Carbon::parse($user->otp_expires_at)->isPast();
        if ($isExpired) {
            // HANGUSKAN KODE DI DATABASE SECARA PERMANEN
            $user->update([
                'otp_code'         => null,
                'otp_trigger_code' => null,
                'otp_expires_at'   => null,
            ]);

            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP telah hangus karena melewati batas waktu 15 menit. Silakan buat permintaan kode baru.',
                ], 422);
            }
            return back()->withErrors(['otp_code' => 'Kode OTP telah hangus karena melewati batas waktu 15 menit. Silakan buat permintaan kode baru.']);
        }

        if (trim($user->otp_code) !== trim($request->otp_code)) {
            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['status' => 'error', 'message' => 'Kode OTP salah! Silakan periksa kembali pesan dari Bot WhatsApp.'], 422);
            }
            return back()->withErrors(['otp_code' => 'Kode OTP salah! Silakan periksa kembali pesan dari Bot WhatsApp.']);
        }

        // Sukses! Update password dan bersihkan OTP
        $user->update([
            'password'         => Hash::make($request->password),
            'otp_code'         => null,
            'otp_trigger_code' => null,
            'otp_expires_at'   => null,
        ]);

        // Notifikasi konfirmasi berhasil ke WA
        try {
            \App\Http\Controllers\WebhookController::sendWaMessage(
                $user->whatsapp,
                "✅ *KATA SANDI BERHASIL DIRESET*\n\nHalo *{$user->name}*, kata sandi akun PremiumKita Anda telah berhasil diperbarui. Silakan login menggunakan kata sandi baru Anda."
            );
        } catch (\Exception $e) {}

        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'status'   => 'success',
                'message'  => 'Kata sandi berhasil direset! Silakan login dengan kata sandi baru Anda.',
                'redirect' => route('login'),
            ]);
        }

        return redirect()->route('login')->with('success', 'Kata sandi akun Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.');
    }
}
