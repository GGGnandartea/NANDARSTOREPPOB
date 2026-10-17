import React from 'react';
import { Icon, fmt } from './ProfileIcons';
import Pagination from './Pagination';

export default function MutationsTab({
  allMutations,
  filteredMutations,
  paginatedMutations,
  mutationFilter,
  setMutationFilter,
  mutationPage,
  setMutationPage,
  mutationTotalPages,
  totalIncomeCalc,
  totalExpenseCalc,
  totalIncomeCount,
  totalExpenseCount,
  user,
  isReseller,
  setShowTopup,
}) {
  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 dark:text-white">Riwayat Top Up &amp; Saldo</h2>
          <p className="text-xs text-gray-400 mt-0.5">Catatan seluruh pemasukan dan pengeluaran dana akun Anda</p>
        </div>
        <button
          type="button"
          onClick={() => setShowTopup(true)}
          className={`inline-flex items-center gap-1.5 text-xs font-black px-4 py-2.5 rounded-xl shadow transition cursor-pointer shrink-0 ${
            isReseller
              ? 'bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 hover:brightness-110 shadow-amber-500/20'
              : 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-600/20'
          }`}
        >
          <Icon.Plus className="w-3.5 h-3.5" />
          <span>Top Up Saldo</span>
        </button>
      </div>

      {/* ─── KARTU STATISTIK AKUMULASI PEMASUKAN & PENGELUARAN ─── */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
        {/* Total Pengeluaran Sejak Daftar */}
        <div className="bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-transparent border border-rose-500/20 dark:border-rose-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
              Total Pengeluaran
            </span>
            <div className="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center font-black text-sm">
              ↑
            </div>
          </div>
          <p className="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
            -{fmt.rp(totalExpenseCalc)}
          </p>
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
            <span>🛍️</span> {totalExpenseCount}x transaksi belanja sejak daftar
          </p>
        </div>

        {/* Total Pemasukan Sejak Daftar */}
        <div className="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 dark:border-emerald-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Total Pemasukan
            </span>
            <div className="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm">
              ↓
            </div>
          </div>
          <p className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            +{fmt.rp(totalIncomeCalc)}
          </p>
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
            <span>✓</span> {totalIncomeCount}x transaksi top up sejak daftar
          </p>
        </div>

        {/* Sisa Saldo Aktif Saat Ini */}
        <div className="sm:col-span-2 lg:col-span-1 bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-transparent border border-blue-500/20 dark:border-blue-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-blue-500"></span>
              Sisa Saldo Aktif
            </span>
            <div className="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm">
              💳
            </div>
          </div>
          <p className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            {fmt.rp(user?.balance)}
          </p>
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
            <span>⚡</span> Saldo siap digunakan bertransaksi
          </p>
        </div>
      </div>

      {/* Filter Pills Pemasukan / Pengeluaran */}
      <div className="flex items-center gap-2 mb-4 overflow-x-auto pb-1">
        <button
          type="button"
          onClick={() => setMutationFilter('all')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap ${
            mutationFilter === 'all'
              ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-sm'
              : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'
          }`}
        >
          Semua ({allMutations.length})
        </button>
        <button
          type="button"
          onClick={() => setMutationFilter('income')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1.5 ${
            mutationFilter === 'income'
              ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20'
              : 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
          }`}
        >
          <span>↓ Pemasukan</span>
          <span className="text-[10px] opacity-80">({allMutations.filter(m => m.type === 'income').length})</span>
        </button>
        <button
          type="button"
          onClick={() => setMutationFilter('expense')}
          className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1.5 ${
            mutationFilter === 'expense'
              ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/20'
              : 'bg-white dark:bg-gray-800 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-50 dark:hover:bg-rose-950/20'
          }`}
        >
          <span>↑ Pengeluaran</span>
          <span className="text-[10px] opacity-80">({allMutations.filter(m => m.type === 'expense').length})</span>
        </button>
      </div>

      {allMutations.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center shadow-sm">
          <div className="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
            <Icon.Wallet className="w-8 h-8 text-gray-300 dark:text-gray-600" />
          </div>
          <h3 className="font-extrabold text-gray-800 dark:text-white mb-1">Belum Ada Riwayat Mutasi</h3>
          <p className="text-xs text-gray-400 mb-5">Riwayat transaksi top up dan penggunaan saldo Anda akan tampil di sini.</p>
          <button
            type="button"
            onClick={() => setShowTopup(true)}
            className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition cursor-pointer"
          >
            <Icon.Plus className="w-4 h-4" />Top Up Sekarang
          </button>
        </div>
      ) : filteredMutations.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-10 text-center shadow-sm">
          <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
            <Icon.Wallet className="w-7 h-7" />
          </div>
          <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Tidak Ada Data</h3>
          <p className="text-xs text-gray-400 mb-4">Belum ada mutasi untuk kategori filter yang dipilih.</p>
          <button
            type="button"
            onClick={() => setMutationFilter('all')}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Tampilkan Semua
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {paginatedMutations.map((m, idx) => {
            const isIncome = m.type === 'income';
            const isExpense = m.type === 'expense';
            const isPending = m.status === 'pending';
            const isExpired = m.status === 'expired';

            return (
              <div key={m.id || idx} className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/80 p-4 shadow-sm hover:shadow-md transition">
                <div className="flex items-center justify-between gap-3 sm:gap-4">
                  {/* Icon & Detail */}
                  <div className="flex items-center gap-3 sm:gap-3.5 min-w-0">
                    <div className={`w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border ${
                      isIncome
                        ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'
                        : isExpense
                        ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/60'
                        : isPending
                        ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800/60 animate-pulse'
                        : 'bg-gray-100 dark:bg-gray-800 text-gray-400 border-gray-200 dark:border-gray-700'
                    }`}>
                      {isIncome ? (
                        <Icon.ArrowDownLeft className="w-5 h-5" />
                      ) : isExpense ? (
                        <Icon.ArrowUpRight className="w-5 h-5" />
                      ) : isPending ? (
                        <Icon.Scan className="w-5 h-5" />
                      ) : (
                        <Icon.Wallet className="w-5 h-5" />
                      )}
                    </div>

                    <div className="min-w-0">
                      <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-0.5">
                        {isIncome && (
                          <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                            Pemasukan
                          </span>
                        )}
                        {isExpense && (
                          <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-700">
                            Pengeluaran
                          </span>
                        )}
                        {isPending && (
                          <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 animate-pulse">
                            Menunggu Pembayaran
                          </span>
                        )}
                        {isExpired && (
                          <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-700">
                            Kadaluarsa
                          </span>
                        )}
                        {m.invoice_number && (
                          <span className="font-mono text-[10px] text-gray-400 truncate">
                            #{m.invoice_number}
                          </span>
                        )}
                      </div>

                      <p className="font-extrabold text-xs sm:text-sm text-gray-900 dark:text-white truncate">
                        {m.description || (isIncome ? 'Top Up Saldo' : isExpense ? 'Transaksi Saldo' : 'Tagihan QRIS')}
                      </p>

                      <div className="flex items-center gap-2 mt-0.5">
                        <p className="text-[10px] text-gray-400">{fmt.date(m.created_at)}</p>
                        {isPending && (
                          <span className="text-[10px] font-bold text-amber-500">
                            (Kadaluarsa dlm 10 menit)
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Nominal & Tombol QRIS */}
                  <div className="text-right shrink-0">
                    <p className={`font-black text-sm sm:text-base ${
                      isIncome
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : isExpense
                        ? 'text-rose-600 dark:text-rose-400'
                        : isPending
                        ? 'text-amber-500 dark:text-amber-400'
                        : 'text-gray-400'
                    }`}>
                      {isIncome ? `+${fmt.rp(m.amount)}` : isExpense ? `-${fmt.rp(m.amount)}` : fmt.rp(m.amount)}
                    </p>

                    {isPending && m.qris_url && (
                      <a
                        href={m.qris_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-1 inline-flex items-center gap-1 text-[11px] font-black text-blue-500 hover:text-blue-600 hover:underline"
                      >
                        Bayar QRIS →
                      </a>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          {/* Pagination Riwayat Top Up / Mutasi */}
          <div className="pt-2">
            <Pagination
              currentPage={mutationPage}
              totalPages={mutationTotalPages}
              onPageChange={(p) => {
                setMutationPage(p);
                window.scrollTo({ top: 0, behavior: 'smooth' });
              }}
            />
            {filteredMutations.length > 0 && (
              <p className="text-center text-[11px] text-gray-400 mt-2 font-medium">
                Menampilkan {((mutationPage - 1) * 10) + 1} - {Math.min(mutationPage * 10, filteredMutations.length)} dari {filteredMutations.length} mutasi saldo
              </p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
