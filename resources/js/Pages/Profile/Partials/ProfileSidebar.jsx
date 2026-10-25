import React from 'react';
import { router } from '@inertiajs/react';
import { Icon, fmt } from './ProfileIcons';

export default function ProfileSidebar({
  user,
  isReseller,
  orders,
  allMutations,
  totalIncomeCalc,
  totalExpenseCalc,
  menus,
  activeMenu,
  handleMenuClick,
  setShowTopup,
  setShowUpgradeModal,
  resellerSettings,
  setShowWaModal,
  setWaModalPhone,
  setShowChangeWaModal,
}) {
  const isFree = Boolean(resellerSettings?.is_free);
  const fee = Number(resellerSettings?.fee ?? 20000);
  return (
    <div className="flex flex-col h-fit">
      {/* Avatar & Info */}
      <div className="p-5 border-b border-gray-100 dark:border-gray-700/50">
        <div className="flex items-center gap-3">
          {isReseller ? (
            <div className="w-13 h-13 rounded-2xl bg-gradient-to-br from-amber-400 via-yellow-500 to-amber-600 text-gray-950 flex items-center justify-center font-black text-xl shadow-lg shadow-amber-500/20 shrink-0 relative border-2 border-yellow-300">
              {user?.name?.charAt(0)?.toUpperCase()}
              <span className="absolute -top-2 -right-2 text-sm">👑</span>
            </div>
          ) : (
            <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center font-black text-xl shadow-lg shrink-0">
              {user?.name?.charAt(0)?.toUpperCase()}
            </div>
          )}

          <div className="min-w-0">
            <p className="font-black text-gray-900 dark:text-white text-sm truncate flex items-center gap-1">
              {user?.name}
            </p>
            <p className="text-xs text-gray-400 truncate">{user?.email}</p>
            
            {isReseller ? (
              <span className="text-[11px] font-black px-2.5 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 mt-1 inline-flex items-center gap-1 uppercase shadow-md shadow-amber-500/20">
                👑 VIP RESELLER
              </span>
            ) : (
              <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 mt-1 inline-block uppercase">
                {user?.role || 'user'}
              </span>
            )}
          </div>
        </div>

        {/* User Stats */}
        <div className="flex justify-between items-center mt-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
            <div className="text-center">
                <p className="text-[11px] font-medium text-gray-400 uppercase">Pembelian</p>
                <p className="font-black text-gray-900 dark:text-white text-sm">{orders.length}</p>
            </div>
            <div className="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
            <div className="text-center">
                <p className="text-[11px] font-medium text-gray-400 uppercase">Bergabung</p>
                <p className="font-black text-gray-900 dark:text-white text-sm">{user?.created_at ? new Date(user.created_at).getFullYear() : '2026'}</p>
            </div>
        </div>

        {/* Ringkasan Akumulasi Saldo Sejak Daftar */}
        <div className="mt-3 p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-700/60 text-xs space-y-2">
          <p className="text-[11px] font-medium text-gray-400 dark:text-gray-300 uppercase tracking-wider">
            Akumulasi Akun
          </p>
          <div className="flex justify-between items-center">
            <span className="text-[11px] font-medium text-gray-500 dark:text-gray-300 flex items-center gap-1">
              <span className="text-emerald-500 font-black">↓</span> Pemasukan
            </span>
            <span className="font-black text-emerald-600 dark:text-emerald-400 text-xs">
              +{fmt.rp(totalIncomeCalc)}
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-[11px] font-medium text-gray-500 dark:text-gray-300 flex items-center gap-1">
              <span className="text-rose-500 font-black">↑</span> Pengeluaran
            </span>
            <span className="font-black text-rose-600 dark:text-rose-400 text-xs">
              -{fmt.rp(totalExpenseCalc)}
            </span>
          </div>
        </div>

        {/* WhatsApp Verified Badge in Sidebar */}
        <div className="mt-3 flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-700/60 text-xs">
          <div className="flex items-center gap-2 min-w-0">
            <span className="text-sm">📱</span>
            <div className="truncate">
              <p className="font-medium text-[11px] text-gray-500 dark:text-gray-300 uppercase tracking-wider">WhatsApp</p>
              <p className="font-bold text-[11px] text-gray-800 dark:text-gray-200 truncate">{user?.whatsapp || 'Belum diisi'}</p>
            </div>
          </div>
          <div className="flex items-center gap-1 shrink-0">
            {user?.is_whatsapp_verified ? (
              <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                ✓ Verified
              </span>
            ) : (
              <button
                type="button"
                onClick={() => { setWaModalPhone(user?.whatsapp || ''); setShowWaModal(true); }}
                className="text-[11px] font-black px-2 py-0.5 rounded-full bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 cursor-pointer transition"
              >
                Verifikasi
              </button>
            )}
            <button
              type="button"
              onClick={() => setShowChangeWaModal(true)}
              className="text-[11px] font-bold px-1.5 py-0.5 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 cursor-pointer transition flex items-center gap-0.5"
              title="Ganti nomor WhatsApp"
            >
              <span>✏️</span> Ubah
            </button>
          </div>
        </div>

        {/* Saldo Card */}
        {isReseller ? (
          <div className="mt-4 bg-gradient-to-br from-[#1c1917] via-[#292524] to-[#451a03] border border-amber-500/30 rounded-2xl p-4 text-white relative overflow-hidden shadow-lg shadow-amber-950/20">
            <div className="absolute top-0 right-0 w-24 h-24 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div className="flex justify-between items-center mb-1">
              <p className="text-[11px] font-black text-amber-400 uppercase tracking-wider">Saldo Reseller VIP</p>
              <span className="text-[11px] font-black bg-amber-500/20 text-amber-300 px-1.5 py-0.5 rounded border border-amber-500/30">BEBAS POTONGAN</span>
            </div>
            <p className="text-2xl font-black text-amber-300">{fmt.rp(user?.balance)}</p>
            <button
              onClick={() => setShowTopup(true)}
              className="mt-3 flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 text-xs font-black px-3.5 py-2 rounded-xl transition cursor-pointer shadow-md"
            >
              <Icon.Plus className="w-3.5 h-3.5" />Top Up Saldo
            </button>
          </div>
        ) : (
          <div className="mt-4 bg-gradient-to-br from-[#0b1121] to-[#1e3a5f] rounded-2xl p-4 text-white relative overflow-hidden">
            <div className="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
            <p className="text-[11px] font-black text-blue-300 uppercase tracking-wider mb-1">Saldo Kamu</p>
            <p className="text-2xl font-black">{fmt.rp(user?.balance)}</p>
            <button
              onClick={() => setShowTopup(true)}
              className="mt-3 flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-black px-3 py-1.5 rounded-xl transition cursor-pointer shadow"
            >
              <Icon.Plus className="w-3.5 h-3.5" />Top Up
            </button>
          </div>
        )}

        {/* Promo Upgrade untuk Pelanggan Biasa */}
        {!isReseller && (
          <div className={`mt-3 border rounded-2xl p-3.5 text-center transition-all ${isFree ? 'bg-gradient-to-r from-emerald-500/15 via-amber-500/15 to-yellow-500/15 border-emerald-500/40 shadow-sm' : 'bg-gradient-to-r from-amber-500/15 via-yellow-500/15 to-amber-500/15 border-amber-500/30'}`}>
            <div className="flex items-center justify-center gap-1.5">
              <span className="text-base">👑</span>
              {isFree && (
                <span className="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500 text-white animate-pulse">
                  PROMO
                </span>
              )}
            </div>
            <p className="text-xs font-black text-amber-500 dark:text-amber-400 mt-1">
              {isFree ? 'Promo VIP Reseller Gratis!' : 'Mau Harga Lebih Murah?'}
            </p>
            <p className="text-[11px] text-gray-500 dark:text-gray-300 font-medium mt-0.5">
              {isFree ? 'Aktivasi seumur hidup Rp 0 (Promo Terbatas)' : `Daftar Reseller hanya Rp ${fee.toLocaleString('id-ID')} seumur hidup`}
            </p>
            <button
              onClick={() => setShowUpgradeModal(true)}
              className={`mt-2.5 w-full text-xs font-black py-2.5 rounded-xl transition shadow cursor-pointer ${isFree ? 'bg-gradient-to-r from-emerald-500 via-teal-500 to-green-600 text-white hover:brightness-110 shadow-emerald-500/20' : 'bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 hover:brightness-110'}`}
            >
              {isFree ? 'Klaim VIP Gratis Sekarang' : 'Upgrade Sekarang'}
            </button>
          </div>
        )}
      </div>

      {/* Menu */}
      <nav className="flex-1 p-3 space-y-1">
        {menus.map(m => {
          const isActive = activeMenu === m.key;
          let activeClass = 'bg-orange-500 text-white shadow-md shadow-orange-500/20';
          if (isReseller) {
            activeClass = 'bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 text-gray-950 font-black shadow-lg shadow-amber-500/25';
          }

          return (
            <button
              key={m.key}
              onClick={() => handleMenuClick(m.key)}
              className={`w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-sm font-bold transition cursor-pointer ${isActive ? activeClass : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'}`}
            >
              <m.icon
                className={`w-4 h-4 shrink-0 ${
                  !isActive
                    ? m.key === 'orders_ppob'
                      ? 'text-emerald-500'
                      : m.key === 'orders_app'
                      ? 'text-blue-500'
                      : m.key === 'topup'
                      ? 'text-amber-500'
                      : ''
                    : ''
                }`}
              />
              <span className="flex-1 text-left">{m.label}</span>
              {m.badge && (
                <span className={`text-[11px] font-black px-1.5 py-0.5 rounded-full ${isActive ? 'bg-black text-amber-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'}`}>
                  {m.badge}
                </span>
              )}
              {m.count !== undefined && m.count > 0 && (
                <span
                  className={`text-[11px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center ${
                    isActive
                      ? 'bg-black/20 text-current'
                      : m.key === 'orders_ppob'
                      ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300'
                      : m.key === 'orders_app'
                      ? 'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300'
                      : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'
                  }`}
                >
                  {m.count}
                </span>
              )}
              <Icon.ChevronRight className={`w-3.5 h-3.5 shrink-0 ${isActive ? 'opacity-80' : 'text-gray-300 dark:text-gray-600'}`} />
            </button>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="p-3 border-t border-gray-100 dark:border-gray-700/50">
        <button
          onClick={() => router.post('/logout')}
          className="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition cursor-pointer"
        >
          <Icon.Logout className="w-4 h-4" />Keluar dari Akun
        </button>
      </div>
    </div>
  );
}

