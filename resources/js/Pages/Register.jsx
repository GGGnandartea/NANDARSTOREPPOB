import React, { useEffect, useState, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Register() {
    const { store } = usePage().props;
    const [role, setRole] = useState('user');
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [whatsapp, setWhatsapp] = useState('');
    const [password, setPassword] = useState('');
    const [passwordConfirmation, setPasswordConfirmation] = useState('');
    
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);

    // Modal QRIS Reseller
    const [showQrisModal, setShowQrisModal] = useState(false);
    const [qrisData, setQrisData] = useState(null);
    const [paymentStatus, setPaymentStatus] = useState('pending'); // pending | paid | failed
    const pollingRef = useRef(null);

    const getCsrf = () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
    };

    // Polling status pembayaran reseller
    useEffect(() => {
        if (showQrisModal && qrisData?.order_id && paymentStatus === 'pending') {
            pollingRef.current = setInterval(async () => {
                try {
                    const res = await fetch(`/reseller/payment-status/${qrisData.order_id}`);
                    const data = await res.json();
                    if (data.status === 'paid' || data.status === 'PAID') {
                        clearInterval(pollingRef.current);
                        setPaymentStatus('paid');
                        setTimeout(() => {
                            window.location.href = '/login?registered=reseller';
                        }, 2500);
                    }
                } catch (e) {}
            }, 3000);
        }

        return () => {
            if (pollingRef.current) clearInterval(pollingRef.current);
        };
    }, [showQrisModal, qrisData, paymentStatus]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});
        setProcessing(true);

        const payload = {
            name,
            email,
            whatsapp,
            password,
            password_confirmation: passwordConfirmation,
            role,
        };

        if (role === 'reseller') {
            try {
                const res = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrf(),
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok) {
                    if (data.errors) {
                        setErrors(data.errors);
                    } else {
                        setErrors({ general: data.message || 'Pendaftaran gagal.' });
                    }
                } else if (data.status === 'reseller_payment_required') {
                    setQrisData(data);
                    setPaymentStatus('pending');
                    setShowQrisModal(true);
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (err) {
                setErrors({ general: 'Gagal terhubung ke server.' });
            }
            setProcessing(false);
        } else {
            // Pelanggan biasa via Inertia router post
            router.post('/register', payload, {
                onError: (errs) => {
                    setErrors(errs);
                    setProcessing(false);
                },
                onFinish: () => setProcessing(false),
            });
        }
    };

    return (
        <div className="min-h-screen bg-[#0b1121] flex flex-col justify-center items-center p-4 sm:p-6 font-jakarta relative overflow-hidden">
            <Head>
                <title>{`Daftar Akun - ${store?.name || 'NandarStore'}`}</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
                <style>{`.font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }`}</style>
            </Head>

            {/* Efek Cahaya Latar Belakang */}
            <div className="absolute top-[-10%] left-[20%] w-[400px] h-[400px] bg-gradient-to-br from-orange-600/30 to-transparent rounded-full mix-blend-screen filter blur-[100px] pointer-events-none"></div>
            <div className="absolute bottom-[-10%] right-[10%] w-[300px] h-[300px] bg-gradient-to-tl from-amber-600/20 to-transparent rounded-full mix-blend-screen filter blur-[80px] pointer-events-none"></div>

            {/* TOMBOL KEMBALI KE BERANDA */}
            <div className="absolute top-6 left-6 z-50">
                <Link href="/" className="flex items-center gap-2 px-4 py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-full text-sm font-bold text-gray-300 hover:text-white backdrop-blur-md transition-all group">
                    <svg className="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Beranda
                </Link>
            </div>

            <div className="w-full max-w-[440px] relative z-10 animate-[fadeIn_0.5s_ease-out] mt-10 sm:mt-0">
                
                {/* Logo & Judul */}
                <div className="text-center mb-6">
                    <div className="inline-flex items-center justify-center gap-2.5 mb-3">
                        {store?.logo ? (
                            <img src={store.logo} alt={store?.name || 'Logo'} className="w-9 h-9 object-contain rounded-xl p-0.5 bg-white/10" />
                        ) : (
                            <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-black text-sm shadow-lg shadow-orange-500/30">NS</div>
                        )}
                        <span className="text-xl font-black text-white tracking-tight">{store?.name || 'NandarStore'}</span>
                    </div>
                    <h2 className="text-2xl font-black text-white tracking-tight mb-2">Buat Akun Baru</h2>
                    <p className="text-gray-400 text-xs font-medium px-4">Daftar sekarang dan nikmati penawaran produk digital terbaik.</p>
                </div>

                {/* Kotak Register Utama */}
                <div className="bg-gray-800/60 backdrop-blur-xl rounded-[2rem] shadow-2xl border border-gray-700/50 overflow-hidden">
                    
                    {/* TAB PEMILIHAN ROLE (PELANGGAN vs RESELLER) */}
                    <div className="flex p-2 bg-gray-900/50 border-b border-gray-700/50 gap-2">
                        <button 
                            type="button" 
                            onClick={() => setRole('user')} 
                            className={`flex-1 py-3 text-xs font-extrabold rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2 ${role === 'user' ? 'bg-gray-800 text-white shadow-md border border-gray-700' : 'text-gray-400 hover:text-white'}`}
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Pelanggan Biasa
                        </button>
                        <button 
                            type="button" 
                            onClick={() => setRole('reseller')} 
                            className={`flex-1 py-3 text-xs font-black rounded-xl transition-all cursor-pointer flex items-center justify-center gap-1.5 ${role === 'reseller' ? 'bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 shadow-lg shadow-amber-500/20' : 'text-amber-400 hover:text-amber-300'}`}
                        >
                            <span>👑</span> Reseller VIP
                        </button>
                    </div>

                    <div className="p-6 sm:p-8">
                        
                        {/* Notifikasi Khusus Reseller */}
                        {role === 'reseller' && (
                            <div className="bg-gradient-to-br from-amber-500/10 to-yellow-500/10 border border-amber-500/30 p-4 rounded-2xl mb-5 animate-[fadeIn_0.3s_ease-out]">
                                <div className="flex items-center gap-2 mb-1.5">
                                    <span className="text-base">👑</span>
                                    <h4 className="text-amber-400 text-xs font-black uppercase tracking-wider">Aktivasi Reseller VIP (Rp 20.000)</h4>
                                </div>
                                <p className="text-gray-300 text-[11px] leading-relaxed mb-2 font-medium">
                                    Nikmati <strong>Harga Modal Termurah</strong> untuk semua produk aplikasi premium &amp; digital, margin cuan maksimal seumur hidup.
                                </p>
                                <div className="bg-black/30 px-3 py-2 rounded-xl text-[10px] text-amber-200/90 font-bold flex items-center gap-2">
                                    <svg className="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Biaya aktivasi Rp 20.000 via QRIS otomatis muncul setelah klik daftar.
                                </div>
                            </div>
                        )}

                        {errors.general && (
                            <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold p-3 rounded-xl mb-4">
                                {errors.general}
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            {/* Input Nama */}
                            <div>
                                <label className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Nama Lengkap</label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    </div>
                                    <input 
                                        type="text" value={name} onChange={(e) => setName(e.target.value)} required placeholder="Misal: Ade Nandar"
                                        className={`w-full pl-10 pr-4 py-3 bg-gray-900/50 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${errors.name ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'}`}
                                    />
                                </div>
                                {errors.name && <p className="text-red-500 text-[10px] font-bold mt-1 px-1">{errors.name}</p>}
                            </div>

                            {/* Input Email */}
                            <div>
                                <label className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Alamat Email</label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
                                    </div>
                                    <input 
                                        type="email" value={email} onChange={(e) => setEmail(e.target.value)} required placeholder="email@contoh.com"
                                        className={`w-full pl-10 pr-4 py-3 bg-gray-900/50 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${errors.email ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'}`}
                                    />
                                </div>
                                {errors.email && <p className="text-red-500 text-[10px] font-bold mt-1 px-1">{errors.email}</p>}
                            </div>

                            {/* Input WhatsApp */}
                            <div>
                                <label className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 px-1">No. WhatsApp <span className="text-gray-500 font-normal lowercase">(notifikasi)</span></label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    </div>
                                    <input 
                                        type="text" value={whatsapp} onChange={(e) => setWhatsapp(e.target.value)} placeholder="08xxxxxxxxxx"
                                        className={`w-full pl-10 pr-4 py-3 bg-gray-900/50 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 border-gray-700 focus:border-orange-500 focus:ring-orange-500/20`}
                                    />
                                </div>
                            </div>

                            {/* Input Password */}
                            <div>
                                <label className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Kata Sandi (Min. 8 Karakter)</label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    </div>
                                    <input 
                                        type={showPassword ? 'text' : 'password'} 
                                        value={password} 
                                        onChange={(e) => setPassword(e.target.value)} 
                                        required 
                                        placeholder="••••••••"
                                        className={`w-full pl-10 pr-11 py-3 bg-gray-900/50 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${errors.password ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'}`}
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
                                {errors.password && <p className="text-red-500 text-[10px] font-bold mt-1 px-1">{errors.password}</p>}
                            </div>

                            {/* Input Konfirmasi Password */}
                            <div>
                                <label className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-1.5 px-1">Ulangi Kata Sandi</label>
                                <div className="relative">
                                    <div className="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-500">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <input 
                                        type={showPasswordConfirm ? 'text' : 'password'} 
                                        value={passwordConfirmation} 
                                        onChange={(e) => setPasswordConfirmation(e.target.value)} 
                                        required 
                                        placeholder="••••••••"
                                        className={`w-full pl-10 pr-11 py-3 bg-gray-900/50 border rounded-2xl text-sm font-bold text-white focus:ring-2 focus:outline-none transition-all placeholder-gray-600 ${errors.password_confirmation ? 'border-red-500 focus:ring-red-500/20' : 'border-gray-700 focus:border-orange-500 focus:ring-orange-500/20'}`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPasswordConfirm(!showPasswordConfirm)}
                                        className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-white transition cursor-pointer"
                                        title={showPasswordConfirm ? 'Sembunyikan password' : 'Lihat password'}
                                    >
                                        {showPasswordConfirm ? (
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path></svg>
                                        ) : (
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        )}
                                    </button>
                                </div>
                                {errors.password_confirmation && <p className="text-red-500 text-[10px] font-bold mt-1 px-1">{errors.password_confirmation}</p>}
                            </div>

                            {/* Tombol Submit */}
                            <button 
                                type="submit" 
                                disabled={processing} 
                                className={`w-full font-black py-4 rounded-2xl shadow-lg transition-all transform active:scale-[0.98] mt-4 flex justify-center items-center gap-2 disabled:opacity-70 cursor-pointer ${
                                    role === 'reseller' 
                                        ? 'bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 text-gray-950 shadow-amber-500/20 hover:brightness-110' 
                                        : 'bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white shadow-orange-500/20'
                                }`}
                            >
                                {processing ? (
                                    <><svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...</>
                                ) : role === 'reseller' ? (
                                    <>Daftar & Bayar Biaya Reseller (Rp 20.000) <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></>
                                ) : (
                                    <>Daftar Sebagai Pelanggan <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></>
                                )}
                            </button>
                        </form>
                    </div>

                    {/* Footer Box */}
                    <div className="bg-gray-900/50 p-5 border-t border-gray-700/50 flex flex-col items-center justify-center gap-2">
                        <p className="text-xs text-gray-400 font-medium">
                            Sudah punya akun?{' '}
                            <Link href="/login" className="font-extrabold text-orange-500 hover:text-orange-400 hover:underline transition-colors">
                                Masuk di sini
                            </Link>
                        </p>
                    </div>
                    
                </div>
            </div>

            {/* MODAL QRIS PEMBAYARAN RESELLER */}
            {showQrisModal && (
                <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/75 backdrop-blur-md animate-[fadeIn_0.3s_ease-out]">
                    <div className="w-full max-w-sm bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-amber-500/30 text-white">
                        
                        {/* Header Modal */}
                        <div className="bg-gradient-to-r from-amber-600 to-yellow-600 p-5 flex items-center justify-between text-gray-950">
                            <div>
                                <span className="text-[10px] font-black uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded-full inline-block mb-1">Biaya Aktivasi Akun</span>
                                <h3 className="font-black text-lg">Reseller VIP NandarStore</h3>
                            </div>
                            <div className="text-2xl">👑</div>
                        </div>

                        <div className="p-6 text-center">
                            {paymentStatus === 'pending' ? (
                                <>
                                    <p className="text-xs text-gray-400 font-bold uppercase tracking-wider mb-1">Total Pembayaran</p>
                                    <p className="text-3xl font-black text-amber-400 mb-4">
                                        Rp {new Intl.NumberFormat('id-ID').format(qrisData?.total_nominal || 20000)}
                                    </p>

                                    {/* QRIS Container */}
                                    <div className="bg-white p-3 rounded-2xl inline-block shadow-lg mb-4">
                                        {qrisData?.url_qris ? (
                                            <img src={qrisData.url_qris} alt="QRIS Reseller" className="w-52 h-52 object-contain" />
                                        ) : (
                                            <div className="w-52 h-52 flex flex-col items-center justify-center text-gray-600 font-bold text-xs p-4">
                                                <svg className="w-10 h-10 text-gray-400 mb-2 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8H3m2 0V6m1 2h12M3 16h2m0 0v2m0-2h4m4 0h6m-6-8h.01M5 16h.01"></path></svg>
                                                Scan QRIS pada aplikasi e-wallet Anda
                                            </div>
                                        )}
                                    </div>

                                    {/* Indikator Menunggu */}
                                    <div className="flex items-center justify-center gap-2 text-amber-400 font-bold text-xs bg-amber-500/10 py-2.5 rounded-xl border border-amber-500/20 mb-3 animate-pulse">
                                        <div className="w-2 h-2 rounded-full bg-amber-400"></div>
                                        Menunggu konfirmasi pembayaran...
                                    </div>

                                    <p className="text-[11px] text-gray-400 mb-4">
                                        Invoice: <span className="font-mono text-gray-300 font-bold">{qrisData?.invoice}</span>
                                    </p>

                                    <button 
                                        type="button" 
                                        onClick={() => setShowQrisModal(false)}
                                        className="text-xs text-gray-500 hover:text-gray-300 font-bold transition cursor-pointer"
                                    >
                                        Tutup (Bayar nanti & login setelah lunas)
                                    </button>
                                </>
                            ) : (
                                <div className="py-6">
                                    <div className="w-20 h-20 bg-green-500/20 text-green-400 border border-green-500/40 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                                        <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                    <h3 className="text-xl font-black text-white mb-2">Pembayaran Berhasil!</h3>
                                    <p className="text-sm text-gray-300 font-medium mb-4">
                                        Selamat! Akun Anda telah resmi aktif sebagai <strong className="text-amber-400">VIP Reseller</strong>.
                                    </p>
                                    <p className="text-xs text-gray-400 animate-pulse">Mengalihkan ke halaman login...</p>
                                </div>
                            )}
                        </div>

                    </div>
                </div>
            )}
        </div>
    );
}