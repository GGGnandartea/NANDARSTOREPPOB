import React, { useEffect, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Login({ status }) {
    const { flash, store } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => {
        return () => {
            reset('password');
        };
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="min-h-screen bg-[#0b1121] flex flex-col justify-center items-center p-4 sm:p-6 font-jakarta relative overflow-hidden">
            <Head>
                <title>{`Masuk Akun - ${store?.name || 'NandarStore'}`}</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
                <style>{`.font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }`}</style>
            </Head>

            {/* Glowing Background Gradients */}
            <div className="absolute top-[-10%] left-[20%] w-[420px] h-[420px] bg-gradient-to-br from-orange-600/30 to-transparent rounded-full mix-blend-screen filter blur-[100px] pointer-events-none"></div>
            <div className="absolute bottom-[-10%] right-[10%] w-[320px] h-[320px] bg-gradient-to-tl from-cyan-600/20 to-transparent rounded-full mix-blend-screen filter blur-[80px] pointer-events-none"></div>

            {/* Top Back Link */}
            <div className="absolute top-6 left-6 z-50">
                <Link 
                    href="/" 
                    className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-xs sm:text-sm font-bold text-gray-300 hover:text-white backdrop-blur-md transition-all group"
                >
                    <svg className="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Beranda
                </Link>
            </div>

            <div className="w-full max-w-[430px] relative z-10 animate-[fadeIn_0.5s_ease-out] mt-12 sm:mt-0">
                
                {/* Header Brand */}
                <div className="text-center mb-7">
                    <div className="inline-flex items-center justify-center gap-2.5 mb-3.5">
                        {store?.logo ? (
                            <img src={store.logo} alt={store?.name || 'Logo'} className="w-10 h-10 object-contain rounded-xl p-0.5 bg-white/10" />
                        ) : (
                            <div className="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl text-white flex items-center justify-center font-black text-lg shadow-lg shadow-orange-500/30">
                                NS
                            </div>
                        )}
                        <span className="text-2xl font-black text-white tracking-tight">
                            {store?.name || 'NandarStore'}
                        </span>
                    </div>
                    <h2 className="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">
                        Selamat Datang Kembali!
                    </h2>
                    <p className="text-gray-400 text-xs sm:text-sm font-medium px-4">
                        Masuk ke akun Anda untuk bertransaksi, kelola saldo, dan cek pesanan.
                    </p>
                </div>

                {/* Card Container */}
                <div className="bg-gray-800/60 backdrop-blur-xl rounded-[2.2rem] shadow-2xl border border-gray-700/50 overflow-hidden">
                    
                    <div className="p-6 sm:p-8">

                        {/* Banner Pendaftaran Reseller Sukses */}
                        {typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('registered') === 'reseller' && (
                            <div className="mb-5 bg-gradient-to-r from-amber-500/20 to-yellow-500/20 border border-amber-500/40 p-4 rounded-2xl flex items-center gap-3">
                                <span className="text-2xl shrink-0">👑</span>
                                <div>
                                    <h4 className="text-amber-400 font-black text-xs">Pendaftaran Reseller VIP Berhasil!</h4>
                                    <p className="text-gray-300 text-[11px] font-medium leading-relaxed">Pembayaran Rp 20.000 lunas. Akun Anda telah aktif, silakan masuk.</p>
                                </div>
                            </div>
                        )}

                        {/* Banner Reset Password Sukses */}
                        {typeof window !== 'undefined' && new URLSearchParams(window.location.search).get('reset') === 'success' && (
                            <div className="mb-5 bg-gradient-to-r from-emerald-500/20 to-teal-500/20 border border-emerald-500/40 p-4 rounded-2xl flex items-center gap-3 animate-[fadeIn_0.3s_ease-out]">
                                <div className="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400 font-bold shrink-0">
                                    ✓
                                </div>
                                <div>
                                    <h4 className="text-emerald-400 font-black text-xs">Kata Sandi Berhasil Diperbarui!</h4>
                                    <p className="text-gray-300 text-[11px] font-medium leading-relaxed">Silakan masuk menggunakan kata sandi baru Anda.</p>
                                </div>
                            </div>
                        )}

                        {/* Flash Messages */}
                        {flash?.success && (
                            <div className="mb-5 font-bold text-xs text-emerald-400 bg-emerald-500/10 p-3.5 rounded-xl border border-emerald-500/30 flex items-center gap-2">
                                <span className="text-base">✓</span>
                                <span>{flash.success}</span>
                            </div>
                        )}

                        {status && (
                            <div className="mb-5 font-bold text-xs text-emerald-400 bg-emerald-500/10 p-3.5 rounded-xl border border-emerald-500/30">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-4 sm:space-y-5">
                            
                            {/* Alamat Email */}
                            <div>
                                <label className="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">
                                    Alamat Email
                                </label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                                    </div>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        value={data.email} 
                                        autoComplete="username" 
                                        onChange={(e) => setData('email', e.target.value)} 
                                        required 
                                        placeholder="nama@email.com"
                                        className={`w-full pl-11 pr-4 py-3.5 bg-gray-900/60 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${
                                            errors.email ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'
                                        }`}
                                    />
                                </div>
                                {errors.email && (
                                    <p className="text-red-400 text-xs font-bold mt-1.5 px-1">{errors.email}</p>
                                )}
                            </div>

                            {/* Kata Sandi */}
                            <div>
                                <div className="flex justify-between items-center mb-2 px-1">
                                    <label className="block text-[11px] font-black text-gray-400 uppercase tracking-widest">
                                        Kata Sandi
                                    </label>
                                    <Link 
                                        href="/forgot-password" 
                                        className="text-xs font-bold text-orange-400 hover:text-orange-300 hover:underline transition-colors cursor-pointer"
                                    >
                                        Lupa Sandi?
                                    </Link>
                                </div>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </div>
                                    <input 
                                        type={showPassword ? 'text' : 'password'} 
                                        name="password" 
                                        value={data.password} 
                                        autoComplete="current-password" 
                                        onChange={(e) => setData('password', e.target.value)} 
                                        required 
                                        placeholder="••••••••"
                                        className={`w-full pl-11 pr-11 py-3.5 bg-gray-900/60 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${
                                            errors.password ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'
                                        }`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition cursor-pointer"
                                        title={showPassword ? 'Sembunyikan password' : 'Lihat password'}
                                    >
                                        {showPassword ? (
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                        ) : (
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="text-red-400 text-xs font-bold mt-1.5 px-1">{errors.password}</p>
                                )}
                            </div>

                            {/* Ingat sesi saya */}
                            <div className="flex items-center pt-1 px-1">
                                <label className="flex items-center cursor-pointer group select-none">
                                    <input 
                                        type="checkbox" 
                                        name="remember" 
                                        checked={data.remember} 
                                        onChange={(e) => setData('remember', e.target.checked)} 
                                        className="w-4 h-4 rounded bg-gray-900 border-gray-700 text-orange-500 focus:ring-orange-500 focus:ring-2 focus:ring-offset-gray-800 transition-colors cursor-pointer"
                                    />
                                    <span className="ml-2.5 text-xs font-bold text-gray-400 group-hover:text-gray-300 transition-colors">
                                        Ingat sesi saya (Tetap Masuk)
                                    </span>
                                </label>
                            </div>

                            {/* Tombol Submit */}
                            <button 
                                type="submit" 
                                disabled={processing} 
                                className="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white font-black py-4 rounded-2xl shadow-lg shadow-orange-500/25 transition-all transform active:scale-[0.98] mt-6 flex justify-center items-center gap-2 disabled:opacity-70 cursor-pointer text-sm"
                            >
                                {processing ? (
                                    <><svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...</>
                                ) : (
                                    <>Masuk Sekarang <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></>
                                )}
                            </button>
                        </form>
                    </div>

                    {/* Footer Navigasi */}
                    <div className="bg-gray-900/50 p-6 border-t border-gray-700/50 flex flex-col items-center justify-center gap-3">
                        <p className="text-xs text-gray-400 font-medium">
                            Belum punya akun?{' '}
                            <Link href="/register" className="font-extrabold text-orange-500 hover:text-orange-400 hover:underline transition-colors">
                                Daftar Sekarang
                            </Link>
                        </p>
                        
                        <div className="flex items-center gap-4 text-[10px] font-bold text-gray-500">
                            <a href={`https://wa.me/${store?.admin_whatsapp || '6282317458496'}`} target="_blank" rel="noreferrer" className="hover:text-white transition-colors">
                                Butuh Bantuan?
                            </a>
                            <span className="w-1 h-1 bg-gray-700 rounded-full"></span>
                            <Link href="/" className="hover:text-white transition-colors">
                                Katalog Layanan
                            </Link>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    );
}