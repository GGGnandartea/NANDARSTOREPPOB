import React from 'react';
import { Link } from '@inertiajs/react';

export default function PpobBanner({ filters, store, onOpenPpob }) {
    if (filters.category || filters.tipe || filters.search) {
        return null;
    }

    const ppobStatus = store?.ppob_status;
    const isPpobOff = Boolean(ppobStatus?.is_cutoff);

    const handleClick = (e) => {
        if (isPpobOff && onOpenPpob) {
            e.preventDefault();
            onOpenPpob('/ppob');
        }
    };

    return (
        <div className="mb-4 sm:mb-6 animate-fade-in-up">
            <Link
                href="/ppob"
                onClick={handleClick}
                className={`block p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl border text-gray-900 dark:text-white shadow-md transition-all group overflow-hidden relative cursor-pointer ${
                    isPpobOff
                        ? 'bg-gradient-to-r from-amber-50 via-rose-50/70 to-amber-100/50 dark:from-gray-900 dark:via-[#221419] dark:to-[#29151c] border-rose-200 dark:border-rose-500/30 hover:border-rose-400'
                        : 'bg-gradient-to-r from-emerald-50 via-teal-50/70 to-emerald-100/50 dark:from-gray-900 dark:via-[#0e221d] dark:to-[#123129] border-emerald-200 dark:border-emerald-500/30 shadow-emerald-900/5 dark:shadow-xl dark:shadow-emerald-950/20 hover:border-emerald-400 dark:hover:border-emerald-400 hover:shadow-lg'
                }`}
            >
                <div className="absolute -top-16 -right-16 w-48 h-48 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
                <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 sm:gap-4 relative z-10">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 flex-wrap mb-1">
                            <h3 className="text-sm sm:text-base font-black text-gray-900 dark:text-white tracking-tight">
                                Pusat Layanan PPOB, Pulsa &amp; Tagihan
                            </h3>
                            {isPpobOff ? (
                                <span className="text-[10px] sm:text-[11px] font-black uppercase px-2.5 py-0.5 rounded-full bg-rose-500 text-white shadow-xs flex items-center gap-1.5">
                                    <span className="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                    <span>{ppobStatus?.status_badge || 'SEDANG OFFLINE'}</span>
                                </span>
                            ) : (
                                <span className="text-[11px] sm:text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                                    Transaksi Otomatis &amp; Instan
                                </span>
                            )}
                        </div>
                        <p className="text-[11px] sm:text-xs text-gray-600 dark:text-gray-300 font-medium max-w-2xl leading-relaxed line-clamp-2 sm:line-clamp-none">
                            {isPpobOff
                                ? ppobStatus?.message || 'Layanan transaksi PPOB sedang dinonaktifkan sementara. Klik untuk melihat detail status & estimasi aktif kembali.'
                                : 'Beli pulsa semua operator, paket data, token listrik PLN, top up e-wallet (DANA, GoPay, OVO), voucher game (MLBB, FF, Genshin) & bayar tagihan PDAM otomatis detik ini juga.'}
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5 self-end md:self-center shrink-0 w-full md:w-auto justify-between md:justify-end pt-1 md:pt-0">
                        <div className="hidden xl:flex items-center gap-2.5 text-xs font-bold text-emerald-800 dark:text-emerald-300/90 bg-white/80 dark:bg-white/5 px-3.5 py-2 rounded-xl border border-emerald-200/80 dark:border-white/10 shadow-xs">
                            <span className="flex items-center gap-1.5">
                                <svg className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                Pulsa &amp; Data
                            </span>
                            <span className="text-gray-300 dark:text-white/20">•</span>
                            <span className="flex items-center gap-1.5">
                                <svg className="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.75V17h8v-2.25A7 7 0 0 0 12 2z"/></svg>
                                Token PLN
                            </span>
                            <span className="text-gray-300 dark:text-white/20">•</span>
                            <span className="flex items-center gap-1.5">
                                <svg className="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                                Game
                            </span>
                            <span className="text-gray-300 dark:text-white/20">•</span>
                            <span className="flex items-center gap-1.5">
                                <svg className="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/></svg>
                                E-Wallet
                            </span>
                        </div>
                        <span
                            className={`py-2 px-3.5 sm:py-2.5 sm:px-4 rounded-xl text-white font-extrabold text-[11px] sm:text-xs shadow-md group-hover:scale-105 transition-all flex items-center gap-1.5 shrink-0 ml-auto md:ml-0 ${
                                isPpobOff
                                    ? 'bg-rose-600 hover:bg-rose-500 border border-rose-400/40 shadow-rose-950/20'
                                    : 'bg-emerald-600 hover:bg-emerald-500 border border-emerald-400/40 shadow-emerald-950/20'
                            }`}
                        >
                            <span>{isPpobOff ? 'Cek Status PPOB' : 'Buka Dashboard PPOB'}</span>
                            <svg className="w-3.5 h-3.5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    );
}
