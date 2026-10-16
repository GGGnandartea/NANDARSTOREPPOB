<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // ==========================================
        // CEK KEAMANAN: APAKAH AKUN SUDAH AKTIF?
        // ==========================================
        if ($user->is_active == false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            // Kembalikan ke halaman login dengan pesan error
            return back()->withErrors([
                'email' => 'Akun Reseller Anda belum diaktifkan. Silakan hubungi Admin untuk verifikasi pembayaran pendaftaran.',
            ]);
        }

        $request->session()->regenerate();

        // ==========================================
        // PENGATUR LALU LINTAS DASHBOARD BERDASARKAN ROLE
        // ==========================================
        if ($user->role === 'admin') {
            return redirect()->intended('/admin/products/api'); // Lempar ke admin panel
        } elseif ($user->role === 'reseller') {
            return redirect()->intended('/reseller/dashboard'); // Lempar ke dashboard reseller
        } else {
            return redirect()->intended('/'); // Pelanggan biasa dikembalikan ke Beranda
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}