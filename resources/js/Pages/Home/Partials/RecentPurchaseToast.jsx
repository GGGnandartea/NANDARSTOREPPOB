import React from 'react';

export default function RecentPurchaseToast({ showNotif, activeNotif }) {
    return (
        <div className={`store-purchase-toast fixed bottom-[3.8rem] left-2 sm:bottom-6 sm:left-6 z-40 pointer-events-none transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] ${showNotif ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-10 opacity-0 scale-90 pointer-events-none'}`}>
            <div className="bg-white/95 dark:bg-[#111827]/95 backdrop-blur-md px-2 py-1 sm:px-2.5 sm:py-1.5 rounded-xl sm:rounded-xl shadow-xl border border-gray-200/90 dark:border-white/10 flex items-center gap-1.5 sm:gap-2 w-fit max-w-[min(176px,calc(100vw-5rem))] sm:max-w-[220px]">
                <div className="w-4 h-4 sm:w-5 sm:h-5 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center shrink-0">
                    <svg className="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div className="min-w-0">
                    <p className="text-[11px] sm:text-[11px] font-black text-gray-900 dark:text-white leading-tight truncate">
                        <span className="text-orange-500 font-extrabold">{activeNotif?.name || 'Pelanggan'}</span> baru membeli
                    </p>
                    <p className="text-[11px] sm:text-xs font-bold text-gray-700 dark:text-gray-200 truncate leading-tight mt-0.5">{activeNotif?.product_name || activeNotif?.product || 'Produk digital'}</p>
                    <span className="text-[10px] sm:text-[11px] font-medium text-gray-400 dark:text-gray-300 flex items-center gap-1 mt-0.5">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        {activeNotif?.time || activeNotif?.time_ago || 'Baru saja'}
                    </span>
                </div>
            </div>
        </div>
    );
}

