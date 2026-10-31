import React from 'react';
import { Link } from '@inertiajs/react';
import { fmt } from './PpobConstants';
import TopAnnouncementBar from '../../../Components/TopAnnouncementBar';

export default function PpobHeader({ store, user, userBalance, isDark, toggleDarkMode }) {
  return (
    <>
      <TopAnnouncementBar announcement={store?.announcement} />
      <header className="sticky top-0 z-40 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200/80 dark:border-white/5">
      <div className="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 h-14 sm:h-16 flex items-center justify-between gap-2 sm:gap-4">
        <div className="flex items-center gap-2 sm:gap-3 min-w-0">
          <Link
            href="/"
            className="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-600 text-xs font-bold text-gray-700 dark:text-gray-300 transition shrink-0"
            title="Kembali ke Beranda Toko"
          >
            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span className="hidden sm:inline">Kembali ke Toko</span>
            <span className="sm:hidden text-[11px] font-extrabold">Toko</span>
          </Link>
          <div className="h-4 w-px bg-gray-200 dark:bg-white/10 hidden sm:block"></div>
          <div className="flex items-center gap-2 min-w-0">
            {store?.logo ? (
              <img src={store.logo} alt={store.name} className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-contain bg-emerald-500/10 p-0.5 shrink-0" />
            ) : (
              <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-600 text-white font-black flex items-center justify-center text-[10px] sm:text-xs shadow-sm shrink-0">
                PK
              </div>
            )}
            <div className="min-w-0 flex items-center gap-1.5">
              <span className="font-black text-xs sm:text-sm tracking-tight text-gray-900 dark:text-white truncate">
                {store?.name || 'PremiumKita'}
              </span>
              <span className="hidden sm:inline-flex text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 shrink-0">
                PPOB RESMI
              </span>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
          {/* User Info / Balance */}
          {user ? (
            <div className="flex items-center gap-1.5 sm:gap-2 px-2 py-1 sm:px-3 sm:py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
              <div className="text-left">
                <p className="text-[9px] font-bold text-gray-400 leading-none hidden sm:block">Saldo Akun</p>
                <p className="text-[11px] sm:text-xs font-black text-emerald-600 dark:text-emerald-400 leading-tight">{fmt.rp(userBalance)}</p>
              </div>
              <Link
                href="/profile"
                className="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline pl-0.5"
              >
                +Topup
              </Link>
            </div>
          ) : (
            <Link
              href="/login"
              className="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-sm transition"
            >
              Masuk
            </Link>
          )}

          {/* Dark Mode Button */}
          <button
            type="button"
            onClick={toggleDarkMode}
            className="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-emerald-500 transition cursor-pointer shrink-0"
            title="Ganti Tema"
          >
            {isDark ? (
              <svg className="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="12" cy="12" r="5" />
                <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
              </svg>
            ) : (
              <svg className="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
              </svg>
            )}
          </button>
        </div>
      </div>
    </header>
    </>
  );
}

