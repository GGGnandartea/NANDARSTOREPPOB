import React from 'react';
import { Link } from '@inertiajs/react';
import { Icon, fmt } from './ProfileIcons';
import StatusBadge from './StatusBadge';

export default function DashboardTab({
  user,
  isReseller,
  orders,
  allMutations,
  totalIncomeCalc,
  totalExpenseCalc,
  totalIncomeCount,
  totalExpenseCount,
  setShowTopup,
  handleMenuClick,
  setWaModalPhone,
  setShowWaModal,
}) {
  return (
    <div className="space-y-5">
      {/* Banner Sapaan Pengguna */}
      <div className="bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent border border-orange-500/20 dark:border-orange-500/30 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden">
        <div className="absolute top-0 right-0 w-36 h-36 bg-orange-500/10 rounded-full blur-2xl pointer-events-none"></div>
        
        <div className="flex items-center gap-3.5 relative z-10 min-w-0">
          <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white flex items-center justify-center font-black text-2xl shadow-lg shadow-orange-500/25 shrink-0">
            👋
          </div>
          <div className="min-w-0">
            <div className="flex items-center gap-2 flex-wrap">
              <h1 className="text-base sm:text-xl font-black text-gray-900 dark:text-white leading-tight">
                Halo, <span className="text-orange-500">{user?.name}</span>!
              </h1>
              {isReseller ? (
                <span className="text-[9px] font-black px-2 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 uppercase shadow-xs">
                  👑 VIP Reseller
                </span>
              ) : (
                <span className="text-[9px] font-black px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 uppercase">
                  Member
                </span>
              )}
            </div>
            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Selamat datang di <strong className="text-gray-700 dark:text-gray-300">Dashboard Profil</strong>. Pantau saldo, mutasi, dan transaksi singkat akun Anda di sini.
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 relative z-10 shrink-0">
          <div className="px-3.5 py-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 rounded-xl text-xs shadow-sm flex items-center gap-2">
            <span className="text-gray-400 font-bold">Saldo:</span>
            <span className="font-black text-orange-500 text-sm">{fmt.rp(user?.balance)}</span>
          </div>
          <button
            type="button"
            onClick={() => setShowTopup(true)}
            className="px-3.5 py-2 bg-orange-500 hover:bg-orange-600 text-white font-black text-xs rounded-xl shadow-md shadow-orange-500/20 transition cursor-pointer flex items-center gap-1.5 active:scale-95"
          >
            <Icon.Plus className="w-3.5 h-3.5" />
            <span>Top Up</span>
          </button>
        </div>
      </div>

      {/* 4 Kartu Metrik Ringkas */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Saldo Aktif */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
          <div className="flex items-center justify-between mb-1.5">
            <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Saldo Aktif</span>
            <div className="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs">
              💳
            </div>
          </div>
          <p className="text-base sm:text-lg font-black text-gray-900 dark:text-white truncate">
            {fmt.rp(user?.balance)}
          </p>
          <button
            type="button"
            onClick={() => setShowTopup(true)}
            className="mt-2 text-[11px] font-bold text-orange-500 hover:text-orange-600 inline-flex items-center gap-0.5 cursor-pointer"
          >
            + Top Up Saldo
          </button>
        </div>

        {/* Total Pengeluaran */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
          <div className="flex items-center justify-between mb-1.5">
            <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Pengeluaran</span>
            <div className="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs">
              ↑
            </div>
          </div>
          <p className="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 truncate">
            -{fmt.rp(totalExpenseCalc)}
          </p>
          <p className="mt-2 text-[11px] font-medium text-gray-400 truncate">
            {totalExpenseCount}x belanja
          </p>
        </div>

        {/* Total Pemasukan */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
          <div className="flex items-center justify-between mb-1.5">
            <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pemasukan</span>
            <div className="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
              ↓
            </div>
          </div>
          <p className="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 truncate">
            +{fmt.rp(totalIncomeCalc)}
          </p>
          <p className="mt-2 text-[11px] font-medium text-gray-400 truncate">
            {totalIncomeCount}x top up
          </p>
        </div>

        {/* Total Pesanan */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
          <div className="flex items-center justify-between mb-1.5">
            <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Pesanan</span>
            <div className="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs">
              📦
            </div>
          </div>
          <p className="text-base sm:text-lg font-black text-gray-900 dark:text-white truncate">
            {orders.length}
          </p>
          <button
            type="button"
            onClick={() => handleMenuClick('orders')}
            className="mt-2 text-[11px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 dark:hover:text-orange-400 inline-flex items-center gap-0.5 cursor-pointer"
          >
            Lihat Semua →
          </button>
        </div>
      </div>

      {/* Transaksi Singkat: 2 Kolom (Pesanan Terakhir & Aktivitas Saldo Terakhir) */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {/* Kolom 1: Pesanan Terakhir */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700/60">
              <div className="flex items-center gap-2.5">
                <div className="w-8 h-8 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center">
                  <Icon.History className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-sm font-black text-gray-900 dark:text-white">Pesanan Terakhir</h3>
                  <p className="text-[11px] text-gray-400">Ringkasan transaksi pembelian akun Anda</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => handleMenuClick('orders')}
                className="text-xs font-bold text-orange-500 hover:text-orange-600 transition cursor-pointer flex items-center gap-1"
              >
                Lihat Semua <Icon.ChevronRight className="w-3 h-3" />
              </button>
            </div>

            {orders.length === 0 ? (
              <div className="py-8 text-center text-gray-400">
                <Icon.History className="w-8 h-8 mx-auto mb-2 opacity-30" />
                <p className="text-xs font-bold text-gray-500 dark:text-gray-400">Belum ada riwayat pesanan</p>
                <Link href="/" className="mt-2.5 inline-block text-xs font-black text-orange-500 hover:underline">
                  Mulai Belanja Sekarang →
                </Link>
              </div>
            ) : (
              <div className="space-y-2.5">
                {orders.slice(0, 4).map((o, idx) => (
                  <div
                    key={o.id || idx}
                    className="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700/50 hover:border-orange-500/30 transition flex items-center justify-between gap-3"
                  >
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center gap-1.5 flex-wrap">
                        <span className="text-[10px] font-mono font-bold text-gray-500 dark:text-gray-400 truncate max-w-[130px]">
                          #{o.invoice_number || o.id}
                        </span>
                        <StatusBadge status={o.order_status || o.payment_status} />
                      </div>
                      <p className="text-xs font-black text-gray-800 dark:text-white truncate mt-1">
                        {o.product_name || 'Produk Digital'}
                      </p>
                      <p className="text-[10px] text-gray-400 mt-0.5">
                        {fmt.date(o.created_at)}
                      </p>
                    </div>
                    <div className="text-right shrink-0">
                      <p className="text-xs sm:text-sm font-black text-orange-500">
                        {fmt.rp(o.total_amount || o.price)}
                      </p>
                      <a
                        href={`/order/invoice/${o.invoice_number || o.id}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-[10px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 mt-0.5 inline-flex items-center gap-0.5"
                      >
                        Invoice ↗
                      </a>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {orders.length > 4 && (
            <div className="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 text-center">
              <button
                type="button"
                onClick={() => handleMenuClick('orders')}
                className="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500 cursor-pointer"
              >
                +{orders.length - 4} pesanan lainnya. Klik untuk melihat riwayat lengkap →
              </button>
            </div>
          )}
        </div>

        {/* Kolom 2: Aktivitas Saldo Terakhir */}
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm flex flex-col justify-between">
          <div>
            <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700/60">
              <div className="flex items-center gap-2.5">
                <div className="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                  <Icon.Wallet className="w-4 h-4" />
                </div>
                <div>
                  <h3 className="text-sm font-black text-gray-900 dark:text-white">Aktivitas Saldo</h3>
                  <p className="text-[11px] text-gray-400">Mutasi top up dan pengeluaran dana</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => handleMenuClick('topup')}
                className="text-xs font-bold text-blue-500 hover:text-blue-600 transition cursor-pointer flex items-center gap-1"
              >
                Lihat Semua <Icon.ChevronRight className="w-3 h-3" />
              </button>
            </div>

            {allMutations.length === 0 ? (
              <div className="py-8 text-center text-gray-400">
                <Icon.Wallet className="w-8 h-8 mx-auto mb-2 opacity-30" />
                <p className="text-xs font-bold text-gray-500 dark:text-gray-400">Belum ada mutasi saldo</p>
                <button
                  type="button"
                  onClick={() => setShowTopup(true)}
                  className="mt-2.5 inline-block text-xs font-black text-blue-500 hover:underline cursor-pointer"
                >
                  Top Up Saldo Sekarang →
                </button>
              </div>
            ) : (
              <div className="space-y-2.5">
                {allMutations.slice(0, 4).map((m, idx) => {
                  const isInc = m.type === 'income';
                  return (
                    <div
                      key={m.id || idx}
                      className="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700/50 hover:border-blue-500/30 transition flex items-center justify-between gap-3"
                    >
                      <div className="flex items-center gap-2.5 min-w-0 flex-1">
                        <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-black text-xs ${
                          isInc
                            ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400'
                            : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                        }`}>
                          {isInc ? '↓' : '↑'}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="text-xs font-black text-gray-800 dark:text-white truncate">
                            {m.title || (isInc ? 'Top Up Saldo' : 'Pengeluaran')}
                          </p>
                          <div className="flex items-center gap-1.5 text-[10px] text-gray-400 mt-0.5">
                            <span>{fmt.date(m.created_at)}</span>
                            {m.invoice && <span className="font-mono truncate">({m.invoice})</span>}
                          </div>
                        </div>
                      </div>
                      <div className="text-right shrink-0">
                        <p className={`text-xs sm:text-sm font-black ${
                          isInc
                            ? 'text-emerald-600 dark:text-emerald-400'
                            : 'text-rose-600 dark:text-rose-400'
                        }`}>
                          {isInc ? '+' : '-'}{fmt.rp(m.amount)}
                        </p>
                        <span className={`text-[9px] font-black uppercase px-1.5 py-0.2 rounded ${
                          m.status === 'success' || m.status === 'paid'
                            ? 'text-emerald-500'
                            : 'text-amber-500'
                        }`}>
                          {m.status === 'success' || m.status === 'paid' ? 'Sukses' : m.status}
                        </span>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>

          {allMutations.length > 4 && (
            <div className="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 text-center">
              <button
                type="button"
                onClick={() => handleMenuClick('topup')}
                className="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-blue-500 cursor-pointer"
              >
                +{allMutations.length - 4} mutasi lainnya. Klik untuk melihat riwayat lengkap →
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Banner WhatsApp jika belum terverifikasi */}
      {!user?.is_whatsapp_verified && (
        <div className="bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-transparent border border-amber-500/20 dark:border-amber-500/30 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
          <div className="flex items-center gap-3">
            <span className="text-2xl">📱</span>
            <div>
              <h4 className="text-xs sm:text-sm font-black text-gray-900 dark:text-white">
                Nomor WhatsApp Belum Terverifikasi
              </h4>
              <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                Verifikasi sekarang agar struk pesanan &amp; akun produk bisa otomatis dikirimkan langsung ke WhatsApp Anda.
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={() => { setWaModalPhone(user?.whatsapp || ''); setShowWaModal(true); }}
            className="px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black text-xs rounded-xl shadow transition cursor-pointer shrink-0"
          >
            Verifikasi Sekarang
          </button>
        </div>
      )}
    </div>
  );
}

