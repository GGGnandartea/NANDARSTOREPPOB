<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\ResellerRegistration;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLoginForm() {
        return Inertia::render('Login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // CEK APAKAH RESELLER BELUM LUNAS / BELUM AKTIF
            if ($user->role === 'reseller' && !$user->is_active) {
                Auth::logout();
                $pendingReg = ResellerRegistration::where('user_id', $user->id)->where('status', 'pending')->latest()->first();
                return back()->withErrors([
                    'email' => 'Akun Reseller Anda belum aktif karena belum menyelesaikan pembayaran pendaftaran Rp 20.000.',
                ])->with('pending_order_id', $pendingReg ? $pendingReg->order_id_gateway : null);
            }

            $request->session()->regenerate();
            
            if ($user->role === 'admin') {
                return Inertia::location(route('admin.dashboard'));
            }

            // Seluruh pengguna (user dan reseller) langsung diarahkan ke halaman katalog/dashboard produk
            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    public function showRegisterForm() {
        return Inertia::render('Register');
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

        $isReseller = $request->role === 'reseller';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp ?? '',
            'password' => Hash::make($request->password),
            'role' => $isReseller ? 'reseller' : 'user',
            'is_active' => $isReseller ? false : true, // Reseller wajib bayar dulu
            'balance' => 0,
        ]);

        if ($isReseller) {
            $invoiceNumber = 'RES-' . strtoupper(Str::random(8)) . '-' . time();
            $callbackUrl = url('/api/webhook/reseller-qris');
            $qrisUrl = null;
            $orderIdGateway = $invoiceNumber;
            $totalNominal = 20000;

            try {
                $response = Http::timeout(5)->post('http://127.0.0.1:4061/api/order', [
                    'nominal'      => 20000,
                    'callback_url' => $callbackUrl,
                ]);
                $data = $response->json();
                if (isset($data['data']['orderId'])) {
                    $orderIdGateway = $data['data']['orderId'];
                    $totalNominal = $data['data']['total_nominal'] ?? 20000;
                    $qrisUrl = $data['data']['url_qris'] ?? null;
                }
            } catch (\Exception $e) {}

            ResellerRegistration::create([
                'user_id'          => $user->id,
                'invoice_number'   => $invoiceNumber,
                'order_id_gateway' => $orderIdGateway,
                'amount'           => 20000,
                'status'           => 'pending',
                'qris_url'         => $qrisUrl,
                'payment_method'   => 'qris',
            ]);

            return response()->json([
                'status'        => 'reseller_payment_required',
                'user_id'       => $user->id,
                'order_id'      => $orderIdGateway,
                'invoice'       => $invoiceNumber,
                'amount'        => 20000,
                'total_nominal' => $totalNominal,
                'url_qris'      => $qrisUrl,
                'message'       => 'Pendaftaran reseller berhasil disimpan. Silakan lakukan pembayaran Rp ' . number_format($totalNominal, 0, ',', '.') . ' untuk aktivasi akun.',
            ]);
        }

        Auth::login($user);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'redirect' => route('home')]);
        }

        return redirect()->route('home');
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
                    'message' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di NandarStore.',
                    'errors'  => ['whatsapp' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di NandarStore.'],
                ], 422);
            }
            return back()->withErrors([
                'whatsapp' => 'Nomor WhatsApp ini tidak terdaftar pada akun manapun di NandarStore.',
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
        $botWaUrl = 'https://wa.me/628990738216?text=' . urlencode("Halo Bot NandarStore, saya meminta kode OTP reset password akun saya. Kode Verifikasi: {$triggerCode}");

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
                "✅ *KATA SANDI BERHASIL DIRESET*\n\nHalo *{$user->name}*, kata sandi akun NandarStore Anda telah berhasil diperbarui. Silakan login menggunakan kata sandi baru Anda."
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