import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';

export default function MobileBottomNav({
    auth,
    showTrackModal,
    setShowTrackModal,
    showCategoryModal,
    setShowCategoryModal,
    store,
    onOpenPpob,
}) {
    const [activeSection, setActiveSection] = useState(() =>
        typeof window !== 'undefined' && window.location.hash === '#katalog-produk' ? 'apps' : 'home');
    useEffect(() => {
        const syncHash = () => setActiveSection(window.location.hash === '#katalog-produk' ? 'apps' : 'home');
        window.addEventListener('hashchange', syncHash);
        return () => window.removeEventListener('hashchange', syncHash);
    }, []);
    const activeItem = showTrackModal ? 'track' : showCategoryModal ? 'apps' : activeSection;
    const isPpobOff = Boolean(store?.ppob_status?.is_cutoff);
    const itemClass = (item) => `min-h-12 flex flex-col items-center justify-center py-1 transition-colors duration-200 cursor-pointer ${activeItem === item ? 'text-orange-500 dark:text-orange-400' : 'text-gray-500 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400'}`;
    return (
        <div className="store-bottom-nav sm:hidden fixed bottom-0 left-0 right-0 z-[100] bg-white/95 dark:bg-[#0b1121]/95 backdrop-blur-xl border-t border-gray-200/90 dark:border-white/10 px-1 py-1 shadow-[0_-4px_20px_rgba(0,0,0,0.08)]">
            <div className="grid grid-cols-5 items-center">
                {/* 1. Beranda */}
                <button 
                    type="button" 
                    onClick={() => { 
                        setActiveSection('home');
                        setShowTrackModal(false); 
                        setShowCategoryModal(false); 
                        window.scrollTo({ top: 0, behavior: 'smooth' }); 
                    }} 
                    aria-pressed={activeItem === 'home'}
                    className={itemClass('home')}
                >
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinejoin="round" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">Beranda</span>
                </button>

                {/* 2. Aplikasi Premium */}
                <button 
                    type="button" 
                    onClick={() => { 
                        setActiveSection('apps');
                        setShowTrackModal(false); 
                        setShowCategoryModal(false); 
                        const el = document.getElementById('katalog-produk'); 
                        if (el) { 
                            el.scrollIntoView({ behavior: 'smooth' }); 
                        } else { 
                            setShowCategoryModal(true); 
                        } 
                    }} 
                    aria-pressed={activeItem === 'apps'}
                    className={itemClass('apps')}
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
                <Link
                    href="/ppob"
                    onClick={(e) => {
                        if (isPpobOff && onOpenPpob) {
                            e.preventDefault();
                            onOpenPpob('/ppob');
                        }
                    }}
                    className="min-h-12 flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-300 hover:text-emerald-500 dark:hover:text-emerald-400 relative transition-colors"
                >
                    <div className="relative">
                        <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                        {isPpobOff && (
                            <span className="absolute -top-1 -right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-[#0b1121]" />
                        )}
                    </div>
                    <span className="text-[11px] font-medium mt-1 tracking-tight">PPOB</span>
                </Link>

                {/* 4. Lacak Pesanan */}
                <button 
                    type="button" 
                    onClick={() => { setShowCategoryModal(false); setShowTrackModal(!showTrackModal); }}
                    aria-pressed={activeItem === 'track'}
                    className={itemClass('track')}
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
