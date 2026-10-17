import React from 'react';
import { Link, router } from '@inertiajs/react';

export default function Navbar({
    auth,
    store,
    isScrolled,
    searchTerm,
    handleInputChange,
    handleSearch,
    toggleDarkMode,
    isDark,
    profileMenuRef,
    showProfileMenu,
    setShowProfileMenu,
    setShowTrackModal,
}) {
    return (
        <>
            {/* ADMIN MONITOR BAR */}
            {auth?.user?.role === 'admin' && (
                <div className="bg-slate-900 text-slate-200 text-xs py-2 px-4 border-b border-slate-800 shrink-0 sticky top-0 z-50">
                    <div className="max-w-7xl mx-auto flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <span className="text-base">👑</span>
                            <span className="font-black text-orange-400">Mode Pantau Admin:</span>
                            <span className="text-[11px] text-slate-300 hidden sm:inline">Akun profil, saldo pelanggan, checkout, dan top-up dinonaktifkan.</span>
                        </div>
                        <Link 
                            href="/admin/dashboard" 
                            className="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-[11px] rounded-lg transition shrink-0 shadow-sm"
                        >
                            <span>Dashboard Admin</span>
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </Link>
                    </div>
                </div>
            )}

            {/* NAVBAR */}
            <nav className={`glass-nav sticky ${auth?.user?.role === 'admin' ? 'top-8' : 'top-0'} z-40 transition-all duration-300 ${isScrolled ? 'border-b border-gray-200 dark:border-gray-800 shadow-sm' : 'border-b border-transparent dark:border-transparent'}`}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
                    
                    <div className="flex-1 h-full relative overflow-hidden flex items-center">
                        <Link href="/" className={`absolute left-0 flex items-center gap-2 cursor-pointer transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] ${isScrolled ? '-translate-y-16 opacity-0 sm:translate-y-0 sm:opacity-100 sm:relative sm:mr-6' : 'translate-y-0 opacity-100 relative sm:mr-6'}`}>
                            {store?.logo ? (
                                <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-white/10 p-0.5 flex items-center justify-center shadow-md shrink-0 overflow-hidden">
                                    <img src={store.logo} alt={store?.name || 'NandarStore'} className="w-full h-full object-contain rounded-lg" />
                                </div>
                            ) : (
                                <div className="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-bold shadow-md shrink-0 text-sm">NS</div>
                            )}
                            <span className="text-xl font-extrabold text-black dark:text-white tracking-tight hidden md:block shrink-0">{store?.name || 'NandarStore'}</span>
                        </Link>

                        <div className={`absolute left-0 w-full max-w-xl transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] ${isScrolled ? 'translate-y-0 opacity-100 pointer-events-auto sm:relative' : 'translate-y-16 opacity-0 pointer-events-none sm:-translate-y-4 sm:absolute'}`}>
                            <form onSubmit={handleSearch} className="relative w-full">
                                <input type="text" value={searchTerm} onChange={handleInputChange} placeholder="Cari layanan..." className="w-full pl-4 pr-12 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-orange-500 rounded-full text-[13px] sm:text-sm font-bold dark:text-white outline-none transition-colors shadow-inner" />
                                <button type="submit" className="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-orange-500 hover:bg-orange-600 text-white rounded-full flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Cari Produk">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="flex items-center gap-1 sm:gap-3 shrink-0 relative z-10 bg-white/50 dark:bg-gray-900/50 sm:bg-transparent backdrop-blur-md rounded-full p-1 sm:p-0">
                        {/* PPOB DASHBOARD LINK BUTTON */}
                        <Link
                            href="/ppob"
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs shadow-md shadow-emerald-500/20 hover:scale-105 transition-all cursor-pointer shrink-0"
                            title="Buka Layanan PPOB & Pulsa 24 Jam"
                        >
                            <span className="text-xs">⚡</span>
                            <span className="hidden sm:inline">PPOB &amp; Pulsa</span>
                            <span className="sm:hidden">PPOB</span>
                            <span className="text-[9px] bg-white/25 px-1.5 py-0.2 rounded-full font-black uppercase tracking-wider">HOT</span>
                        </Link>

                        <button onClick={toggleDarkMode} className="p-2 rounded-full text-gray-500 hover:text-black dark:text-gray-400 dark:hover:bg-gray-800 transition-colors cursor-pointer" title="Ganti Mode Gelap / Terang">
                            {isDark ? (
                                <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clipRule="evenodd"></path></svg>
                            ) : (
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                            )}
                        </button>

                        {auth?.user ? (
                            <div className="relative" ref={profileMenuRef}>
                                {auth.user.role === 'admin' ? (
                                    <button
                                        onClick={() => setShowProfileMenu(!showProfileMenu)}
                                        className="flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full bg-slate-900 text-white dark:bg-slate-800 border border-slate-700 hover:border-orange-500 transition-colors focus:outline-none cursor-pointer shadow-sm"
                                        title="Mode Pantau Admin"
                                    >
                                        <span className="text-xs">👑</span>
                                        <span className="text-xs font-black hidden sm:inline text-orange-400">Admin</span>
                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => setShowProfileMenu(!showProfileMenu)}
                                        className="flex items-center justify-center p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none cursor-pointer"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                )}

                                {/* DROPDOWN MENU */}
                                {showProfileMenu && (
                                    auth.user.role === 'admin' ? (
                                        <div className="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50 animate-fade-in-up">
                                            {/* Info Admin */}
                                            <div className="px-5 py-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-b border-slate-700">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-xl bg-orange-500/20 text-orange-400 border border-orange-500/30 flex items-center justify-center font-bold text-lg shrink-0">
                                                        👑
                                                    </div>
                                                    <div className="flex-1 overflow-hidden">
                                                        <p className="text-sm font-black text-white truncate">{auth.user.name}</p>
                                                        <span className="inline-block text-[9px] font-black uppercase tracking-wider bg-orange-500/20 text-orange-400 border border-orange-500/30 px-2 py-0.5 rounded-full mt-0.5">
                                                            Mode Pantau Toko
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="mt-3 bg-amber-500/10 border border-amber-500/20 rounded-xl p-2.5 text-[11px] text-amber-300 font-medium leading-relaxed">
                                                    👁️ <strong>Mode Pantau Aktif:</strong> Akun admin tidak memiliki saldo &amp; profil pembeli, serta tidak dapat melakukan transaksi atau top-up.
                                                </div>
                                            </div>

                                            {/* Menu Items for Admin */}
                                            <div className="p-2 flex flex-col gap-1">
                                                <Link
                                                    href="/admin/dashboard"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-orange-600 dark:text-orange-400 bg-orange-50/70 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                    </div>
                                                    <div className="flex-1">
                                                        <p className="font-black text-sm">Dashboard Admin</p>
                                                        <p className="text-[10px] text-gray-500 dark:text-gray-400">Kembali ke panel kelola</p>
                                                    </div>
                                                </Link>

                                                <div className="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-2"></div>

                                                <button
                                                    onClick={() => { setShowProfileMenu(false); router.post('/logout'); }}
                                                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                                    </div>
                                                    Keluar
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50 animate-fade-in-up">
                                            {/* Info User */}
                                            <div className="px-5 py-4 bg-gradient-to-br from-orange-500 to-orange-600">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-lg shadow-inner backdrop-blur-sm border border-white/30 shrink-0">
                                                        {auth.user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="flex-1 overflow-hidden">
                                                        <p className="text-sm font-black text-white truncate">{auth.user.name}</p>
                                                        <p className="text-xs text-orange-100 truncate">{auth.user.email}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {/* Saldo Info */}
                                            <div className="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-6 h-6 rounded-md bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    <span className="text-xs font-bold text-gray-600 dark:text-gray-400">Saldo</span>
                                                </div>
                                                <span className="text-sm font-black text-green-500 dark:text-green-400">Rp {new Intl.NumberFormat('id-ID').format(auth.user.balance || 0)}</span>
                                            </div>

                                            {/* Menu Items */}
                                            <div className="p-2 flex flex-col gap-1">
                                                <Link
                                                    href="/profile"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    </div>
                                                    Profil Saya
                                                </Link>

                                                <Link
                                                    href="/profile?tab=orders"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                                    </div>
                                                    Riwayat Pembelian
                                                </Link>
                                                
                                                <Link
                                                    href="/profile?tab=topup"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    Top Up &amp; Mutasi
                                                </Link>
                                                
                                                <div className="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-2"></div>
                                                
                                                <button
                                                    onClick={() => { setShowProfileMenu(false); router.post('/logout'); }}
                                                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                                    </div>
                                                    Keluar
                                                </button>
                                            </div>
                                        </div>
                                    )
                                )}
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 sm:gap-3">
                                <button onClick={() => setShowTrackModal(true)} className="hidden sm:flex text-xs font-bold text-gray-700 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 px-4 py-2 sm:py-2.5 rounded-full transition shadow-sm cursor-pointer items-center gap-2">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    Lacak Pesanan
                                </button>
                                <Link href="/login" className="text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 px-4 py-2 sm:px-5 sm:py-2.5 rounded-full transition shadow-md cursor-pointer mr-1 sm:mr-0">
                                    Masuk/Daftar
                                </Link>
                            </div>
                        )}
                    </div>

                </div>
            </nav>
        </>
    );
}

