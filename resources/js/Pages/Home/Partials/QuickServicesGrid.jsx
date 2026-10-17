import React from 'react';
import { Link } from '@inertiajs/react';

export default function QuickServicesGrid({
    filters,
    handleCategoryTipe,
    scrollToSection,
    setShowTrackModal,
    effectiveAdminWa,
    store,
}) {
    if (filters.category || filters.tipe || filters.search) {
        return null;
    }

    return (
        <div className="bg-white dark:bg-[#131b2e] rounded-2xl sm:rounded-3xl border border-gray-200/80 dark:border-white/5 shadow-sm p-3.5 sm:p-5 mb-5 md:mb-6 animate-fade-in-up">
            <div className="flex items-center justify-between mb-3 px-1">
                <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h3 className="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                        Layanan &amp; Kategori Favorit
                    </h3>
                </div>
                <span className="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-gray-500 flex items-center gap-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Siap 24 Jam
                </span>
            </div>

            {/* Grid: 5 kolom di HP (2 baris pas untuk 10 layanan), 10 kolom di Desktop */}
            <div className="grid grid-cols-5 md:grid-cols-5 lg:grid-cols-10 gap-2 sm:gap-3">
                {/* 1. Pulsa & Data */}
                <Link
                    href="/ppob?tab=pulsa"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-emerald-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-emerald-500 text-white text-[7px] font-black uppercase">Auto</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Pulsa &amp; Data</span>
                </Link>

                {/* 2. Listrik PLN */}
                <Link
                    href="/ppob?tab=pln"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-amber-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200/80 dark:border-amber-800/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-amber-500 text-white text-[7px] font-black uppercase">Token</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-amber-600 dark:group-hover:text-amber-400">Token PLN</span>
                </Link>

                {/* 3. Top Up E-Wallet */}
                <Link
                    href="/ppob?tab=ewallet"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-sky-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200/80 dark:border-sky-800/40 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5" fill="currentColor"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-sky-500 text-white text-[7px] font-black uppercase">DANA</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-sky-600 dark:group-hover:text-sky-400">E-Wallet</span>
                </Link>

                {/* 4. Voucher Game */}
                <Link
                    href="/ppob?tab=game"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-purple-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 border border-purple-200/80 dark:border-purple-800/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-purple-500 text-white text-[7px] font-black uppercase">Game</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-purple-600 dark:group-hover:text-purple-400">Game Topup</span>
                </Link>

                {/* 5. Tagihan Air PDAM */}
                <Link
                    href="/ppob?tab=pdam"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-cyan-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 border border-cyan-200/80 dark:border-cyan-800/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-cyan-600 dark:group-hover:text-cyan-400">Air PDAM</span>
                </Link>

                {/* 6. Akun Streaming */}
                <button
                    type="button"
                    onClick={() => handleCategoryTipe('Streaming')}
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-rose-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200/80 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-rose-500 text-white text-[7px] font-black uppercase">Film</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-rose-600 dark:group-hover:text-rose-400">Streaming</span>
                </button>

                {/* 7. Aplikasi & AI */}
                <button
                    type="button"
                    onClick={() => handleCategoryTipe('')}
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-indigo-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/80 dark:border-indigo-800/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Aplikasi &amp; AI</span>
                </button>

                {/* 8. Paling Laris */}
                <button
                    type="button"
                    onClick={() => scrollToSection('paling-laris')}
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-orange-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-orange-50 dark:bg-orange-950/50 border border-orange-200/80 dark:border-orange-800/40 text-orange-600 dark:text-orange-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 23c-4.97 0-9-4.03-9-9 0-3.8 2.38-7.05 5.74-8.35.5-.19 1.04.14 1.13.66.1.58-.2 1.14-.72 1.39C6.88 8.84 5 11.23 5 14c0 3.86 3.14 7 7 7s7-3.14 7-7c0-2.43-1.42-4.59-3.52-5.59-.5-.24-.76-.79-.64-1.33.12-.54.67-.88 1.21-.74C19.78 7.63 22 10.6 22 14c0 4.97-4.03 9-9 9z"/></svg>
                        <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-orange-500 text-white text-[7px] font-black uppercase">HOT</span>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-orange-600 dark:group-hover:text-orange-400">Paling Laris</span>
                </button>

                {/* 9. Lacak Pesanan */}
                <button
                    type="button"
                    onClick={() => setShowTrackModal(true)}
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-slate-50 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-orange-500">Lacak Order</span>
                </button>

                {/* 10. Hubungi CS */}
                <a
                    href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(store?.name || 'NandarStore')},%20saya%20butuh%20bantuan`}
                    target="_blank"
                    rel="noreferrer"
                    className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-emerald-50/60 dark:hover:bg-white/5 transition-all text-center"
                >
                    <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                        <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                    </div>
                    <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Bantuan CS</span>
                </a>
            </div>
        </div>
    );
}
