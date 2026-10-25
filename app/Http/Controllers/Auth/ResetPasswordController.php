<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ResetPasswordController extends Controller
{
    // Halaman form reset password (user harus login)
    public function showForm() {
        return Inertia::render('ResetPassword');
    }

    // Proses ganti password
    public function update(Request $request) {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
        ]);

        $user = $request->user();
        $storedPassword = (string) $user->getAuthPassword();
        $plainCurrent = (string) $request->current_password;
        $algoName = password_get_info($storedPassword)['algoName'] ?? 'unknown';

        $isValidCurrent = false;
        if ($algoName === 'bcrypt') {
            try {
                $isValidCurrent = Hash::check($plainCurrent, $storedPassword);
            } catch (\RuntimeException $e) {
                $isValidCurrent = false;
            }
        } else {
            $isValidCurrent = ($storedPassword !== '' && hash_equals($storedPassword, $plainCurrent))
                || (strlen($storedPassword) === 32 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), md5($plainCurrent)))
                || (strlen($storedPassword) === 40 && ctype_xdigit($storedPassword) && hash_equals(strtolower($storedPassword), sha1($plainCurrent)));
        }

        if (!$isValidCurrent) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'Password berhasil diubah!');
    }
}

