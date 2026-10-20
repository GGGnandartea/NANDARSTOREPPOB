import React from 'react';
import { Link } from '@inertiajs/react';

export default function MobileBottomNav({
    auth,
    showTrackModal,
    setShowTrackModal,
    showCategoryModal,
    setShowCategoryModal,
}) {
    return (
        <div className="store-bottom-nav sm:hidden fixed bottom-0 left-0 right-0 z-[100] bg-white/95 dark:bg-[#0b1121]/95 backdrop-blur-xl border-t border-gray-200/90 dark:border-white/10 px-1 py-1 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
            <div className="grid grid-cols-5 items-center">
                {/* 1. Beranda */}
                <button 
                    type="button" 
                    onClick={() => { 
                        setShowTrackModal(false); 
                        setShowCategoryModal(false); 
                        window.scrollTo({ top: 0, behavior: 'smooth' }); 
                    }} 
                    className={`min-h-12 flex flex-col items-center justify-center py-1 transition-colors cursor-pointer ${!showTrackModal && !showCategoryModal ? 'text-orange-500 dark:text-orange-400' : 'text-gray-500 dark:text-gray-300 hover:text-orange-500'}`}
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">Beranda</span>
                </button>

                {/* 2. Aplikasi Premium */}
                <button 
                    type="button" 
                    onClick={() => { 
                        setShowTrackModal(false); 
                        setShowCategoryModal(false); 
                        const el = document.getElementById('katalog-produk'); 
                        if (el) { 
                            el.scrollIntoView({ behavior: 'smooth' }); 
                        } else { 
                            setShowCategoryModal(true); 
                        } 
                    }} 
                    className="min-h-12 flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 cursor-pointer transition-colors"
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="7" height="7" rx="2" strokeWidth="2" />
                        <rect x="14" y="3" width="7" height="7" rx="2" strokeWidth="2" />
                        <rect x="14" y="14" width="7" height="7" rx="2" strokeWidth="2" />
                        <rect x="3" y="14" width="7" height="7" rx="2" strokeWidth="2" />
                    </svg>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">Aplikasi</span>
                </button>

                {/* 3. PPOB */}
                <Link href="/ppob" className="min-h-12 flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-300 hover:text-emerald-500 dark:hover:text-emerald-400 relative transition-colors">
                    <div className="relative">
                        <svg className="w-5 h-5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinejoin="round"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                    </div>
                    <span className="text-[11px] font-medium mt-1 tracking-tight text-emerald-600 dark:text-emerald-400">PPOB</span>
                </Link>

                {/* 4. Lacak Pesanan */}
                <button 
                    type="button" 
                    onClick={() => setShowTrackModal(!showTrackModal)} 
                    className={`min-h-12 flex flex-col items-center justify-center py-1 cursor-pointer transition-colors ${showTrackModal ? 'text-orange-500 dark:text-orange-400 font-black' : 'text-gray-500 dark:text-gray-300 hover:text-orange-500'}`}
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">Lacak</span>
                </button>

                {/* 5. Profil / Masuk */}
                <Link href={auth?.user ? (auth.user.role === 'admin' ? '/admin/dashboard' : '/profile') : '/login'} className="min-h-12 flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-300 hover:text-orange-500 transition-colors">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">{auth?.user ? 'Profil' : 'Masuk'}</span>
                </Link>
            </div>
        </div>
    );
}

