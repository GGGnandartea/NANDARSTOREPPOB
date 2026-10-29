import React from 'react';

export default function CheckoutHeader({
  store,
  isDark,
  toggleDarkMode,
  canGoBack,
}) {
  return (
    <div className="bg-[#0b1121] p-4 sm:p-8 text-center relative overflow-hidden">
      <div className="absolute -top-10 -right-10 w-40 h-40 bg-orange-500 rounded-full blur-[60px] opacity-20 pointer-events-none"></div>

      {canGoBack && (
        <button
          type="button"
          onClick={() => window.history.back()}
          className="absolute left-5 top-6 text-gray-400 hover:text-white transition cursor-pointer"
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
        </button>
      )}

      <button
        type="button"
        onClick={toggleDarkMode}
        className="absolute right-5 top-6 p-2 rounded-xl text-gray-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
        title={isDark ? 'Mode Terang' : 'Mode Gelap'}
      >
        {isDark ? (
          <svg className="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
        ) : (
          <svg className="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth="2"
              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
          </svg>
        )}
      </button>

      <h2 className="text-lg sm:text-2xl font-black text-white mt-6 sm:mt-8 tracking-tight">Selesaikan Pembayaran</h2>
      <p className="text-orange-500 text-xs mt-1.5 font-bold tracking-widest uppercase">Sistem Cepat &amp; Aman</p>
    </div>
  );
}

