import React from 'react';
import { Link } from '@inertiajs/react';
import { fmt } from './PpobConstants';

export default function PpobHeader({ store, user, userBalance, isDark, toggleDarkMode }) {
  return (
    <header className="sticky top-0 z-40 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200/80 dark:border-white/5">
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <Link
            href="/"
            className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-600 text-xs font-bold text-gray-700 dark:text-gray-300 transition"
          >
            <span>← Kembali ke Toko</span>
          </Link>
          <div className="h-5 w-px bg-gray-200 dark:bg-white/10 hidden sm:block"></div>
          <div className="flex items-center gap-2.5">
            {store?.logo ? (
              <img src={store.logo} alt={store.name} className="w-8 h-8 rounded-lg object-contain bg-emerald-500/10 p-0.5" />
            ) : (
              <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-600 text-white font-black flex items-center justify-center text-xs shadow-sm">
                NS
              </div>
            )}
            <div>
              <span className="font-black text-sm tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5">
                {store?.name || 'NandarStore'}
                <span className="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                  PPOB RESMI
                </span>
              </span>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2.5">
          {/* User Info / Balance */}
          {user ? (
            <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <div className="text-left">
                <p className="text-[10px] font-bold text-gray-400 leading-none">Saldo Akun</p>
                <p className="text-xs font-black text-emerald-600 dark:text-emerald-400 leading-tight">{fmt.rp(userBalance)}</p>
              </div>
              <Link
                href="/profile"
                className="ml-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline"
              >
                +Topup
              </Link>
            </div>
          ) : (
            <Link
              href="/login"
              className="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-sm transition"
            >
              Login Akun
            </Link>
          )}

          {/* Dark Mode Button */}
          <button
            type="button"
            onClick={toggleDarkMode}
            className="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-emerald-500 transition cursor-pointer"
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
  );
}
