import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';

export default function ForgotPassword({ botNumber = '08990738216' }) {
    const { flash, store } = usePage().props;

    // State Multi-Step
    const [step, setStep] = useState(flash?.step || 'input_phone'); // 'input_phone' | 'awaiting_otp'
    const [whatsapp, setWhatsapp] = useState(flash?.whatsapp || '082116847292');
    const [savedWhatsapp, setSavedWhatsapp] = useState(flash?.whatsapp || '');
    const [triggerCode, setTriggerCode] = useState(flash?.trigger_code || '');
    const [botWaUrl, setBotWaUrl] = useState(flash?.bot_wa_url || '');
    const [userName, setUserName] = useState(flash?.user_name || '');

    // State Form Step 1
    const [loading, setLoading] = useState(false);
    const [errorMsg, setErrorMsg] = useState('');
    const [successMsg, setSuccessMsg] = useState('');

    // State Form Step 2
    const [otpCode, setOtpCode] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);
    const [resetLoading, setResetLoading] = useState(false);
    const [resetError, setResetError] = useState('');
    const [isSuccess, setIsSuccess] = useState(false);
    const [countdown, setCountdown] = useState(3);

    // Countdown redirect saat sukses
    useEffect(() => {
        let timer;
        if (isSuccess) {
            if (countdown > 0) {
                timer = setTimeout(() => setCountdown(c => c - 1), 1000);
            } else {
                window.location.href = '/login?reset=success';
            }
        }
        return () => clearTimeout(timer);
    }, [isSuccess, countdown]);

    // Ambil CSRF Token
    const getCsrf = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
    };

    // Sinkronisasi dari Inertia flash jika ada
    useEffect(() => {
        if (flash?.step === 'awaiting_otp') {
            setStep('awaiting_otp');
            setSavedWhatsapp(flash.whatsapp || '');
            setTriggerCode(flash.trigger_code || '');
            setBotWaUrl(flash.bot_wa_url || '');
            setUserName(flash.user_name || '');
        }
    }, [flash]);

    // Handle Minta OTP (Step 1)
    const handleRequestOtp = async (e) => {
        e.preventDefault();
        setErrorMsg('');
        setSuccessMsg('');

        if (!whatsapp || whatsapp.trim().length < 8) {
            setErrorMsg('Masukkan nomor WhatsApp yang valid (minimal 8 digit).');
            return;
        }

        setLoading(true);

        try {
            const res = await fetch('/forgot-password/request', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({ whatsapp: whatsapp.trim() }),
            });

            const data = await res.json();

            if (res.ok && (data.status === 'success' || data.step === 'awaiting_otp')) {
                setStep('awaiting_otp');
                setSavedWhatsapp(data.whatsapp || whatsapp);
                setTriggerCode(data.trigger_code);
                setBotWaUrl(data.bot_wa_url);
                setUserName(data.user_name || '');
                setSuccessMsg('Kode verifikasi berhasil dibuat! Silakan hubungi Bot WhatsApp.');
            } else {
                setErrorMsg(
                    data.message || 
                    data.error || 
                    (data.errors ? Object.values(data.errors)[0]?.[0] : null) || 
                    'Nomor WhatsApp tidak terdaftar di sistem kami.'
                );
            }
        } catch (err) {
            console.error(err);
            setErrorMsg('Gagal terhubung ke server. Pastikan koneksi internet aktif.');
        } finally {
            setLoading(false);
        }
    };

    // Handle Reset Password (Step 2)
    const handleResetPassword = async (e) => {
        e.preventDefault();
        setResetError('');

        if (!otpCode || otpCode.trim().length < 4) {
            setResetError('Masukkan kode OTP dengan benar.');
            return;
        }

        if (password.length < 8) {
            setResetError('Kata sandi minimal 8 karakter.');
            return;
        }

        if (password !== passwordConfirmation) {
            setResetError('Konfirmasi kata sandi baru tidak cocok.');
            return;
        }

        setResetLoading(true);

        try {
            const res = await fetch('/forgot-password/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrf(),
                },
                body: JSON.stringify({
                    whatsapp: savedWhatsapp,
                    otp_code: otpCode.trim(),
                    password: password,
                    password_confirmation: passwordConfirmation,
                }),
            });

            const data = await res.json();

            if (res.ok && data.status === 'success') {
                setIsSuccess(true);
            } else {
                setResetError(
                    data.message || 
                    data.error || 
                    (data.errors ? Object.values(data.errors)[0]?.[0] : null) || 
                    'Kode OTP salah atau telah kadaluarsa.'
                );
            }
        } catch (err) {
            console.error(err);
            setResetError('Terjadi kesalahan jaringan.');
        } finally {
            setResetLoading(false);
        }
    };

    return (
        <div className="min-h-[100dvh] bg-[#0b1121] flex flex-col justify-start sm:justify-center items-center py-3 px-3.5 sm:p-6 font-jakarta relative overflow-x-hidden overflow-y-auto">
            <Head>
                <title>{`Lupa Kata Sandi - ${store?.name || 'PremiumKita'}`}</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
                <style>{`.font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }`}</style>
            </Head>

            {/* Glowing background */}
            <div className="absolute top-[-10%] left-[20%] w-[300px] sm:w-[400px] h-[300px] sm:h-[400px] bg-gradient-to-br from-orange-600/30 to-transparent rounded-full mix-blend-screen filter blur-[100px] pointer-events-none"></div>
            <div className="absolute bottom-[-10%] right-[10%] w-[240px] sm:w-[300px] h-[240px] sm:h-[300px] bg-gradient-to-tl from-cyan-600/20 to-transparent rounded-full mix-blend-screen filter blur-[80px] pointer-events-none"></div>

            {/* Top Back Link (Desktop) */}
            <div className="hidden sm:block absolute top-6 left-6 z-50">
                <Link href="/login" className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs sm:text-sm font-bold text-gray-300 hover:text-white backdrop-blur-md transition-all group">
                    <svg className="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Login
                </Link>
            </div>

            <div className="w-full max-w-[440px] relative z-10 animate-[fadeIn_0.5s_ease-out] my-auto">
                
                {/* Top Bar Ringkas untuk Mobile (HP) */}
                <div className="flex sm:hidden items-center justify-between gap-2 mb-2.5">
                    <Link href="/login" className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs font-bold text-gray-300 hover:text-white backdrop-blur-md transition-all">
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        <span>Login</span>
                    </Link>
                    <div className="inline-flex items-center gap-2">
                        {store?.logo ? (
                            <img src={store.logo} alt={store?.name || 'Logo'} className="w-7 h-7 object-contain rounded-lg p-0.5 bg-white/10" />
                        ) : (
                            <div className="w-7 h-7 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-black text-xs shadow-md shadow-orange-500/30">PK</div>
                        )}
                        <span className="text-base font-black text-white tracking-tight">{store?.name || 'PremiumKita'}</span>
                    </div>
                </div>

                {/* Header Logo */}
                <div className="text-center mb-3.5 sm:mb-6">
                    <div className="hidden sm:inline-flex items-center justify-center gap-2.5 mb-3">
                        {store?.logo ? (
                            <img src={store.logo} alt={store?.name || 'Logo'} className="w-9 h-9 object-contain rounded-xl p-0.5 bg-white/10" />
                        ) : (
                            <div className="w-9 h-9 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl text-white flex items-center justify-center font-black text-sm shadow-lg shadow-orange-500/30">PK</div>
                        )}
                        <span className="text-xl font-black text-white tracking-tight">{store?.name || 'PremiumKita'}</span>
                    </div>
                    <h2 className="text-xl sm:text-2xl font-black text-white tracking-tight mb-1 sm:mb-1.5">
                        {isSuccess ? 'Reset Berhasil!' : (step === 'input_phone' ? 'Reset Kata Sandi' : 'Verifikasi OTP WhatsApp')}
                    </h2>
                    <p className="text-gray-400 text-[11px] sm:text-xs font-medium px-2 sm:px-4">
                        {isSuccess 
                            ? 'Kata sandi akun Anda telah berhasil diperbarui.' 
                            : (step === 'input_phone' 
                                ? 'Masukkan nomor WhatsApp terdaftar Anda untuk memverifikasi identitas akun.' 
                                : 'Kirim pesan verifikasi ke WhatsApp Bot kami untuk menerima 6 digit kode OTP.')}
                    </p>
                </div>

                {/* Card Container */}
                <div className="bg-gray-800/60 backdrop-blur-xl rounded-3xl sm:rounded-[2rem] shadow-2xl border border-gray-700/50 overflow-hidden">
                    
                    <div className="p-4 sm:p-8">

                        {/* ═══ SUKSES RESET PASSWORD ═══ */}
                        {isSuccess ? (
                            <div className="text-center py-4 space-y-5 animate-[fadeIn_0.4s_ease-out]">
                                <div className="w-20 h-20 mx-auto rounded-full bg-emerald-500/20 border-2 border-emerald-500/60 flex items-center justify-center text-emerald-400 text-3xl font-black shadow-xl shadow-emerald-500/25">
                                    ✓
                                </div>
                                
                                <div className="space-y-1.5">
                                    <h3 className="text-xl font-black text-white tracking-tight">
                                        Kata Sandi Berhasil Direset!
                                    </h3>
                                    <p className="text-xs text-gray-300 font-medium leading-relaxed max-w-xs mx-auto">
                                        Kata sandi baru Anda telah aktif. Kode OTP sebelumnya telah otomatis hangus demi keamanan akun Anda.
                                    </p>
                                </div>

                                <div className="p-3.5 bg-emerald-500/10 border border-emerald-500/25 rounded-2xl flex items-center justify-center gap-2 text-xs font-bold text-emerald-400">
                                    <span className="text-sm">⏱️</span>
                                    <span>Mengarahkan ke Login dalam <strong>{countdown} detik</strong>...</span>
                                </div>

                                <a
                                    href="/login?reset=success"
                                    className="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white font-black py-4 px-6 rounded-2xl shadow-lg shadow-orange-500/20 transition-all transform active:scale-[0.98] cursor-pointer text-sm"
                                >
                                    <span>Masuk Sekarang ke Akun Saya →</span>
                                </a>
                            </div>
                        ) : (
                            <>
                                {/* ═══ STEP 1: INPUT NO WHATSAPP ═══ */}
                                {step === 'input_phone' && (
                                    <form onSubmit={handleRequestOtp} className="space-y-5">
                                        {errorMsg && (
                                            <div className="p-3.5 bg-red-500/10 border border-red-500/30 rounded-2xl text-xs font-bold text-red-400 flex items-start gap-2.5">
                                                <span className="text-base leading-none">⚠️</span>
                                                <span>{errorMsg}</span>
                                            </div>
                                        )}

                                <div>
                                    <label className="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">
                                        Nomor WhatsApp Terdaftar
                                    </label>
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                            <span className="text-base">📱</span>
                                        </div>
                                        <input 
                                            type="tel" 
                                            name="whatsapp" 
                                            value={whatsapp} 
                                            onChange={(e) => setWhatsapp(e.target.value)} 
                                            required 
                                            placeholder="Contoh: 08123456789 atau 628xxx"
                                            className="w-full pl-11 pr-4 py-3.5 bg-gray-900/60 border border-gray-700 focus:border-orange-500 rounded-2xl text-sm font-bold text-white focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-all placeholder-gray-600"
                                        />
                                    </div>
                                </div>

                                <div className="p-3.5 rounded-2xl bg-orange-500/10 border border-orange-500/20 text-xs text-orange-300 font-medium leading-relaxed">
                                    💡 <strong>Info:</strong> Kami akan memvalidasi nomor ini dengan akun Anda. Selanjutnya Anda akan mengirim pesan cepat ke WhatsApp Bot resmi kami untuk mengklaim kode OTP.
                                </div>

                                <button 
                                    type="submit" 
                                    disabled={loading} 
                                    className="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-500/20 transition-all transform active:scale-[0.98] mt-2 flex justify-center items-center gap-2 disabled:opacity-70 cursor-pointer text-sm"
                                >
                                    {loading ? (
                                        <>
                                            <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            <span>Memeriksa Akun...</span>
                                        </>
                                    ) : (
                                        <>Minta Kode OTP WhatsApp →</>
                                    )}
                                </button>
                            </form>
                        )}

                        {/* ═══ STEP 2: VERIFIKASI VIA WHATSAPP BOT & INPUT OTP ═══ */}
                        {step === 'awaiting_otp' && (
                            <div className="space-y-5">
                                
                                {successMsg && (
                                    <div className="p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-xs font-bold text-emerald-400 flex items-center gap-2">
                                        <span>✓</span>
                                        <span>{successMsg}</span>
                                    </div>
                                )}

                                {/* Info Box */}
                                <div className="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 space-y-1.5">
                                    <div className="flex items-center justify-between">
                                        <span className="text-[10px] font-black uppercase tracking-wider text-emerald-400">Nomor Terdaftar</span>
                                        <span className="text-xs font-mono font-black text-white">{savedWhatsapp}</span>
                                    </div>
                                    {userName && (
                                        <p className="text-xs text-gray-300">
                                            Pemilik Akun: <strong>{userName}</strong>
                                        </p>
                                    )}
                                </div>

                                {/* Step Action 1: Hubungi Bot */}
                                <div className="p-4 rounded-2xl bg-gray-900/60 border border-gray-700/60 space-y-3">
                                    <div className="flex items-center gap-2">
                                        <span className="w-5 h-5 rounded-full bg-emerald-500 text-white font-black text-[10px] flex items-center justify-center shrink-0">1</span>
                                        <p className="text-xs font-bold text-white">Dapatkan Kode OTP dari Bot WA</p>
                                    </div>
                                    <p className="text-[11px] text-gray-400 leading-relaxed">
                                        Klik tombol hijau di bawah ini untuk mengirim pesan verifikasi ke WhatsApp Bot kami (<span className="text-emerald-400 font-bold">{botNumber}</span>):
                                    </p>

                                    {/* Tombol Kirim Chat ke Bot */}
                                    <a 
                                        href={botWaUrl || `https://wa.me/628990738216?text=${encodeURIComponent(`Halo Bot PremiumKita, saya meminta kode OTP reset password akun saya. Kode Verifikasi: ${triggerCode}`)}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="w-full flex items-center justify-center gap-2.5 py-3.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-emerald-600/25 transition cursor-pointer"
                                    >
                                        <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.664-.698c.974.532 1.905.813 2.796.813 3.179 0 5.767-2.587 5.767-5.766.001-3.182-2.585-5.8-5.767-5.8zm0 10.609c-.83 0-1.637-.222-2.355-.647l-.169-.1-1.748.458.467-1.704-.111-.176c-.469-.747-.716-1.611-.716-2.484 0-2.613 2.126-4.739 4.632-4.739 2.505 0 4.632 2.126 4.632 4.739 0 2.613-2.127 4.653-4.632 4.653z"/></svg>
                                        <span>Buka WhatsApp &amp; Minta OTP</span>
                                    </a>

                                    <div className="flex items-center justify-between text-[10px] text-gray-500 pt-1">
                                        <span>Kode Pemicu Bot:</span>
                                        <span className="font-mono font-bold text-orange-400 select-all">{triggerCode}</span>
                                    </div>
                                </div>

                                {/* Step Action 2: Input OTP & Password Baru */}
                                <form onSubmit={handleResetPassword} className="space-y-4">
                                    <div className="flex items-center gap-2 pt-1">
                                        <span className="w-5 h-5 rounded-full bg-orange-500 text-white font-black text-[10px] flex items-center justify-center shrink-0">2</span>
                                        <p className="text-xs font-bold text-white">Masukkan OTP &amp; Kata Sandi Baru</p>
                                    </div>

                                {resetError && (
                                    <div className="p-3.5 bg-red-500/10 border border-red-500/30 rounded-2xl text-xs font-bold text-red-400 flex flex-col gap-2">
                                        <div className="flex items-start gap-2">
                                            <span className="text-base leading-none">⚠️</span>
                                            <span>{resetError}</span>
                                        </div>
                                        {(resetError.toLowerCase().includes('hangus') || resetError.toLowerCase().includes('kadaluarsa')) && (
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    setStep('input_phone');
                                                    setResetError('');
                                                    setOtpCode('');
                                                    setPassword('');
                                                    setPasswordConfirmation('');
                                                }}
                                                className="self-start text-[11px] underline font-extrabold text-orange-400 hover:text-orange-300 cursor-pointer pt-0.5"
                                            >
                                                🔄 Minta Kode Baru Sekarang →
                                            </button>
                                        )}
                                    </div>
                                )}

                                {/* Expiry badge info */}
                                <div className="flex items-center gap-2 px-3 py-2 rounded-xl bg-orange-500/5 border border-orange-500/10 text-[11px] text-orange-300/80">
                                    <span>⏱️</span>
                                    <span>Kode OTP hanya berlaku <strong>15 menit</strong>. Setelah 15 menit atau jika sudah berhasil digunakan, kode akan otomatis <strong>hangus</strong>.</span>
                                </div>

                                    {/* Input OTP */}
                                    <div>
                                        <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 px-1">
                                            6 Digit Kode OTP dari Bot
                                        </label>
                                        <input 
                                            type="text" 
                                            name="otp_code"
                                            maxLength={6}
                                            value={otpCode} 
                                            onChange={(e) => setOtpCode(e.target.value.replace(/[^0-9]/g, ''))} 
                                            required 
                                            placeholder="Contoh: 123456"
                                            className="w-full px-4 py-3 bg-gray-900/60 border border-gray-700 focus:border-orange-500 rounded-2xl text-center text-lg font-mono font-black tracking-widest text-white focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-all placeholder-gray-600"
                                        />
                                    </div>

                                    {/* Input Password Baru */}
                                    <div>
                                        <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 px-1">
                                            Kata Sandi Baru (Min. 8 Karakter)
                                        </label>
                                        <div className="relative">
                                            <input 
                                                type={showPassword ? 'text' : 'password'} 
                                                name="password" 
                                                value={password} 
                                                onChange={(e) => setPassword(e.target.value)} 
                                                required 
                                                placeholder="••••••••"
                                                className="w-full pl-4 pr-11 py-3 bg-gray-900/60 border border-gray-700 focus:border-orange-500 rounded-2xl text-sm font-bold text-white focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-all placeholder-gray-600"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition cursor-pointer"
                                            >
                                                {showPassword ? (
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                ) : (
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                )}
                                            </button>
                                        </div>
                                    </div>

                                    {/* Input Konfirmasi Password */}
                                    <div>
                                        <label className="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 px-1">
                                            Konfirmasi Kata Sandi Baru
                                        </label>
                                        <div className="relative">
                                            <input 
                                                type={showPasswordConfirm ? 'text' : 'password'} 
                                                name="password_confirmation" 
                                                value={passwordConfirmation} 
                                                onChange={(e) => setPasswordConfirmation(e.target.value)} 
                                                required 
                                                placeholder="••••••••"
                                                className="w-full pl-4 pr-11 py-3 bg-gray-900/60 border border-gray-700 focus:border-orange-500 rounded-2xl text-sm font-bold text-white focus:ring-2 focus:ring-orange-500/20 focus:outline-none transition-all placeholder-gray-600"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                                                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition cursor-pointer"
                                            >
                                                {showPasswordConfirm ? (
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                                ) : (
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                )}
                                            </button>
                                        </div>
                                    </div>

                                    <button 
                                        type="submit" 
                                        disabled={resetLoading || !otpCode || !password} 
                                        className="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-500/20 transition-all transform active:scale-[0.98] mt-4 flex justify-center items-center gap-2 disabled:opacity-60 cursor-pointer text-sm"
                                    >
                                        {resetLoading ? (
                                            <>
                                                <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                <span>Memperbarui Password...</span>
                                            </>
                                        ) : (
                                            <>Simpan Kata Sandi Baru →</>
                                        )}
                                    </button>

                                    <button
                                        type="button"
                                        onClick={() => {
                                            setStep('input_phone');
                                            setErrorMsg('');
                                            setSuccessMsg('');
                                        }}
                                        className="w-full text-center text-xs font-bold text-gray-400 hover:text-white transition py-1 cursor-pointer"
                                    >
                                        ← Ganti Nomor WhatsApp Lain
                                    </button>
                                </form>
                            </div>
                        )}
                            </>
                        )}

                    </div>

                    {/* Footer Box */}
                    <div className="bg-gray-900/50 p-5 border-t border-gray-700/50 flex flex-col items-center justify-center gap-2.5">
                        <p className="text-xs text-gray-400 font-medium">
                            Ingat kata sandi Anda?{' '}
                            <Link href="/login" className="font-extrabold text-orange-500 hover:text-orange-400 hover:underline transition-colors">
                                Masuk Sekarang
                            </Link>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    );
}

