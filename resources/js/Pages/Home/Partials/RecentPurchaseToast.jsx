import React from 'react';

export default function RecentPurchaseToast({ showNotif, activeNotif }) {
    return (
        <div className={`fixed bottom-[3.8rem] left-2 sm:bottom-6 sm:left-6 z-40 transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] ${showNotif ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-10 opacity-0 scale-90 pointer-events-none'}`}>
            <div className="bg-white/95 dark:bg-[#111827]/95 backdrop-blur-md p-1.5 px-2.5 sm:p-3 sm:pr-5 rounded-xl sm:rounded-2xl shadow-xl border border-gray-200/90 dark:border-white/10 flex items-center gap-2 sm:gap-3 max-w-[210px] sm:max-w-xs">
                <div className="w-6 h-6 sm:w-10 sm:h-10 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center shrink-0">
                    <svg className="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div className="min-w-0">
                    <p className="text-[10px] sm:text-xs font-black text-gray-900 dark:text-white leading-tight truncate">
                        <span className="text-orange-500 font-extrabold">{activeNotif?.name || 'Pelanggan'}</span> beli:
                    </p>
                    <p className="text-[9px] sm:text-[11px] font-bold text-gray-600 dark:text-gray-300 truncate max-w-[130px] sm:max-w-[200px] leading-tight">{activeNotif?.product}</p>
                    <span className="text-[8px] sm:text-[9px] font-extrabold text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        {activeNotif?.time_ago || 'Baru saja'}
                    </span>
                </div>
            </div>
        </div>
    );
}
