import React from 'react';
import { Link } from '@inertiajs/react';
import { Icon } from './ProfileIcons';

export default function ProfileNavbar({
  sidebarOpen,
  setSidebarOpen,
  toggleDark,
  isDark,
}) {
  return (
    <nav className="glass-nav sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800 shadow-sm">
      <div className="max-w-6xl mx-auto px-3 sm:px-4 min-h-16 gap-2 flex items-center justify-between">
        <div className="flex items-center gap-1 sm:gap-3">
          <button 
            onClick={() => setSidebarOpen(true)} 
            className="md:hidden min-h-11 min-w-11 p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer"
            aria-label="Buka Menu Sidebar"
            aria-expanded={sidebarOpen}
            aria-controls="profile-mobile-menu"
          >
            <Icon.Menu className="w-5 h-5 dark:text-white" />
          </button>
          <Link href="/" className="flex items-center gap-2">
            <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-black text-sm shadow-md">NS</div>
            <span className="font-black text-gray-900 dark:text-white hidden sm:block">Nandar<span className="text-orange-500">Store</span></span>
          </Link>
        </div>
        <div className="flex items-center gap-2">
          <button 
            onClick={toggleDark} 
            className="min-h-11 min-w-11 flex items-center justify-center p-2 rounded-full text-gray-400 hover:text-gray-700 dark:hover:text-white transition cursor-pointer"
            title="Ganti Mode Gelap / Terang"
          >
            {isDark ? (
              <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clipRule="evenodd"/></svg>
            ) : (
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            )}
          </button>
          <Link href="/" className="min-h-11 flex items-center justify-center gap-1.5 rounded-xl px-2 sm:px-3 bg-orange-500/10 text-xs font-bold text-gray-500 hover:text-orange-500 dark:text-gray-300 dark:hover:text-orange-400 transition">
            <Icon.Home className="w-4 h-4" /><span>Kembali ke Beranda</span>
          </Link>
        </div>
      </div>
    </nav>
  );
}

