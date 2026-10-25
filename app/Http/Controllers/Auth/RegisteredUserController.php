<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Tampilkan halaman pendaftaran.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle proses masuknya data pendaftaran.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:user,reseller', 
        ]);

        // Reseller otomatis non-aktif (false), pelanggan biasa aktif (true)
        $isActive = ($request->role === 'reseller') ? false : true;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,       
            'is_active' => $isActive,       
        ]);

        event(new Registered($user));

        // ==========================================
        // LOGIKA KHUSUS RESELLER
        // ==========================================
        if ($request->role === 'reseller') {
            // 1. JANGAN LOGIN-KAN MEREKA
            // 2. Gunakan Inertia::location untuk melempar ke web luar (WA) agar tidak BLANK
            $pesanWa = "Halo Admin PremiumKita, saya baru saja mendaftar akun Reseller dengan email: {$request->email}. Saya ingin membayar biaya pendaftaran agar akun saya segera diaktifkan.";
            $adminWa = Setting::where('key', 'admin_whatsapp')->value('value') ?? '082317458496';
            $adminWaClean = preg_replace('/[^0-9]/', '', $adminWa);
            if (str_starts_with($adminWaClean, '0')) {
                $adminWaClean = '62' . substr($adminWaClean, 1);
            }
            return Inertia::location("https://wa.me/{$adminWaClean}?text=" . urlencode($pesanWa));
        }

        // ==========================================
        // LOGIKA KHUSUS PELANGGAN BIASA
        // ==========================================
        // 1. Otomatis Login
        Auth::login($user);

        // 2. Lempar kembali ke halaman Beranda agar bisa langsung belanja
        return redirect('/');
    }
}