import React, { useState, useMemo, useEffect } from 'react';
import { Icon, fmt } from './ProfileIcons';
import Pagination from './Pagination';

const MONTH_OPTIONS = [
  { value: 'all', label: 'Semua Bulan' },
  { value: '1', label: 'Januari' },
  { value: '2', label: 'Februari' },
  { value: '3', label: 'Maret' },
  { value: '4', label: 'April' },
  { value: '5', label: 'Mei' },
  { value: '6', label: 'Juni' },
  { value: '7', label: 'Juli' },
  { value: '8', label: 'Agustus' },
  { value: '9', label: 'September' },
  { value: '10', label: 'Oktober' },
  { value: '11', label: 'November' },
  { value: '12', label: 'Desember' },
];

const QUICK_PERIODS = [
  { id: 'all', label: 'Semua Waktu' },
  { id: 'today', label: 'Hari Ini' },
  { id: 'yesterday', label: 'Kemarin' },
  { id: '7d', label: '7 Hari Terakhir' },
  { id: '30d', label: '30 Hari Terakhir' },
  { id: 'this_month', label: 'Bulan Ini' },
  { id: 'this_year', label: 'Tahun Ini' },
];

export default function MutationsTab({
  allMutations = [],
  mutationFilter,
  setMutationFilter,
  mutationPage,
  setMutationPage,
  totalIncomeCalc,
  totalExpenseCalc,
  totalRefundCalc = 0,
  totalIncomeCount,
  totalExpenseCount,
  totalRefundCount = 0,
  user,
  isReseller,
  setShowTopup,
}) {
  const [searchQuery, setSearchQuery] = useState('');
  const [quickPeriod, setQuickPeriod] = useState('all');
  const [selectedMonth, setSelectedMonth] = useState('all');
  const [selectedYear, setSelectedYear] = useState('all');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [sortOrder, setSortOrder] = useState('newest');

  const availableYears = useMemo(() => {
    const years = new Set([new Date().getFullYear(), 2026, 2025, 2024]);
    (allMutations || []).forEach((m) => {
      if (m.created_at) {
        const d = new Date(m.created_at);
        if (!Number.isNaN(d.getTime())) {
          years.add(d.getFullYear());
        }
      }
    });
    return Array.from(years).sort((a, b) => b - a);
  }, [allMutations]);

  const counts = useMemo(() => {
    let inc = 0;
    let exp = 0;
    let ref = 0;
    let pend = 0;
    (allMutations || []).forEach((m) => {
      const isRef = m.type === 'refund' || String(m.raw_type || '').toLowerCase() === 'refund';
      if (isRef) ref += 1;
      else if (m.type === 'income') inc += 1;
      else if (m.type === 'expense') exp += 1;
      else if (m.status === 'pending') pend += 1;
    });
    return { inc, exp, ref, pend };
  }, [allMutations]);

  const handleQuickPeriodChange = (periodId) => {
    setQuickPeriod(periodId);
    if (periodId !== 'all') {
      setSelectedMonth('all');
      setSelectedYear('all');
      setDateFrom('');
      setDateTo('');
    }
    setMutationPage(1);
  };

  const resetAllFilters = () => {
    setMutationFilter('all');
    setSearchQuery('');
    setQuickPeriod('all');
    setSelectedMonth('all');
    setSelectedYear('all');
    setDateFrom('');
    setDateTo('');
    setSortOrder('newest');
    setMutationPage(1);
  };

  const hasActiveFilter =
    mutationFilter !== 'all' ||
    Boolean(searchQuery.trim()) ||
    quickPeriod !== 'all' ||
    selectedMonth !== 'all' ||
    selectedYear !== 'all' ||
    Boolean(dateFrom) ||
    Boolean(dateTo) ||
    sortOrder !== 'newest';

  const filteredMutations = useMemo(() => {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);

    const list = (allMutations || []).filter((m) => {
      const isRef = m.type === 'refund' || String(m.raw_type || '').toLowerCase() === 'refund';
      const isInc = m.type === 'income' && !isRef;
      const isExp = m.type === 'expense';
      const isPend = m.status === 'pending';

      // 1. Filter Kategori Mutasi
      if (mutationFilter === 'income' && !isInc) return false;
      if (mutationFilter === 'expense' && !isExp) return false;
      if (mutationFilter === 'refund' && !isRef) return false;
      if (mutationFilter === 'pending' && !isPend) return false;

      // 2. Filter Pencarian Kata Kunci
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase().trim();
        const desc = String(m.description || m.title || '').toLowerCase();
        const inv = String(m.invoice_number || m.invoice || m.id || '').toLowerCase();
        const amt = String(m.amount || '').toLowerCase();
        if (!desc.includes(q) && !inv.includes(q) && !amt.includes(q)) {
          return false;
        }
      }

      // 3. Filter Tanggal, Hari, Bulan, Tahun
      if (m.created_at) {
        const mDate = new Date(m.created_at);
        if (!Number.isNaN(mDate.getTime())) {
          if (quickPeriod === 'today') {
            if (mDate < todayStart) return false;
          } else if (quickPeriod === 'yesterday') {
            if (mDate < yesterdayStart || mDate >= todayStart) return false;
          } else if (quickPeriod === '7d') {
            const d7 = new Date(todayStart);
            d7.setDate(d7.getDate() - 6);
            if (mDate < d7) return false;
          } else if (quickPeriod === '30d') {
            const d30 = new Date(todayStart);
            d30.setDate(d30.getDate() - 29);
            if (mDate < d30) return false;
          } else if (quickPeriod === 'this_month') {
            if (mDate.getMonth() !== now.getMonth() || mDate.getFullYear() !== now.getFullYear()) return false;
          } else if (quickPeriod === 'this_year') {
            if (mDate.getFullYear() !== now.getFullYear()) return false;
          }

          if (selectedMonth !== 'all') {
            if (mDate.getMonth() + 1 !== Number(selectedMonth)) return false;
          }
          if (selectedYear !== 'all') {
            if (mDate.getFullYear() !== Number(selectedYear)) return false;
          }
          if (dateFrom) {
            const fromStart = new Date(`${dateFrom}T00:00:00`);
            if (!Number.isNaN(fromStart.getTime()) && mDate < fromStart) return false;
          }
          if (dateTo) {
            const toEnd = new Date(`${dateTo}T23:59:59.999`);
            if (!Number.isNaN(toEnd.getTime()) && mDate > toEnd) return false;
          }
        }
      }

      return true;
    });

    return [...list].sort((a, b) => {
      if (sortOrder === 'oldest') {
        return new Date(a.created_at || 0).getTime() - new Date(b.created_at || 0).getTime();
      }
      if (sortOrder === 'highest') {
        return Number(b.amount || 0) - Number(a.amount || 0);
      }
      if (sortOrder === 'lowest') {
        return Number(a.amount || 0) - Number(b.amount || 0);
      }
      return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
    });
  }, [allMutations, mutationFilter, searchQuery, quickPeriod, selectedMonth, selectedYear, dateFrom, dateTo, sortOrder]);

  useEffect(() => {
    setMutationPage(1);
  }, [mutationFilter, searchQuery, quickPeriod, selectedMonth, selectedYear, dateFrom, dateTo, sortOrder]);

  const mutationTotalPages = Math.ceil(filteredMutations.length / 10);
  const paginatedMutations = filteredMutations.slice((mutationPage - 1) * 10, mutationPage * 10);

  const filteredSummary = useMemo(() => {
    let income = 0;
    let grossExpense = 0;
    let refund = 0;
    filteredMutations.forEach((m) => {
      if (m.status !== 'success' && m.status !== 'paid') return;
      const isRef = m.type === 'refund' || String(m.raw_type || '').toLowerCase() === 'refund';
      const amt = Number(m.amount || 0);
      if (isRef) refund += amt;
      else if (m.type === 'income') income += amt;
      else if (m.type === 'expense') grossExpense += amt;
    });
    return {
      income,
      netExpense: Math.max(0, grossExpense - refund),
      refund,
    };
  }, [filteredMutations]);

  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 dark:text-white">Riwayat Top Up &amp; Mutasi Saldo</h2>
          <p className="text-xs text-gray-400 mt-0.5">
            Catatan akurat pemasukan top up, pengeluaran bersih, dan pengembalian dana (refund)
          </p>
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

      {/* ─── KARTU STATISTIK AKUMULASI PEMASUKAN MURNI, PENGELUARAN BERSIH & REFUND ─── */}
      <div className={`grid grid-cols-1 sm:grid-cols-2 ${totalRefundCalc > 0 ? 'lg:grid-cols-4' : 'lg:grid-cols-3'} gap-3.5 mb-5`}>
        {/* Total Pengeluaran Bersih Sejak Daftar */}
        <div className="bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-transparent border border-rose-500/20 dark:border-rose-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
              Pengeluaran Bersih
            </span>
            <div className="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center font-black text-sm">
              ↑
            </div>
          </div>
          <p className="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
            -{fmt.rp(totalExpenseCalc)}
          </p>
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-300 mt-1 flex items-center gap-1">
            <span>🛍️</span> {totalExpenseCount}x belanja sukses (tidak termasuk gagal/refund)
          </p>
        </div>

        {/* Total Pemasukan Murni Sejak Daftar */}
        <div className="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 dark:border-emerald-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Pemasukan Top Up
            </span>
            <div className="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm">
              ↓
            </div>
          </div>
          <p className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
            +{fmt.rp(totalIncomeCalc)}
          </p>
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-300 mt-1 flex items-center gap-1">
            <span>✓</span> {totalIncomeCount}x top up saldo murni
          </p>
        </div>

        {/* Total Dana Di-Refund (Tampil jika ada riwayat refund) */}
        {totalRefundCalc > 0 && (
          <div className="bg-gradient-to-br from-sky-500/10 via-sky-500/5 to-transparent border border-sky-500/20 dark:border-sky-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
            <div className="flex items-center justify-between mb-2">
              <span className="text-[11px] font-black uppercase tracking-wider text-sky-600 dark:text-sky-400 flex items-center gap-1.5">
                <span className="w-2 h-2 rounded-full bg-sky-500"></span>
                Dana Di-Refund
              </span>
              <div className="w-8 h-8 rounded-xl bg-sky-500/15 text-sky-600 dark:text-sky-400 flex items-center justify-center font-black text-sm">
                ↩
              </div>
            </div>
            <p className="text-xl sm:text-2xl font-black text-sky-600 dark:text-sky-400 tracking-tight">
              {fmt.rp(totalRefundCalc)}
            </p>
            <p className="text-[11px] font-medium text-gray-500 dark:text-gray-300 mt-1 flex items-center gap-1">
              <span>🛡️</span> {totalRefundCount}x transaksi gagal kembali ke saldo
            </p>
          </div>
        )}

        {/* Sisa Saldo Aktif Saat Ini */}
        <div className={`${totalRefundCalc > 0 ? '' : 'sm:col-span-2 lg:col-span-1'} bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-transparent border border-blue-500/20 dark:border-blue-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm`}>
          <div className="flex items-center justify-between mb-2">
            <span className="text-[11px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
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
          <p className="text-[11px] font-medium text-gray-500 dark:text-gray-300 mt-1 flex items-center gap-1">
            <span>✓</span> Saldo siap digunakan bertransaksi
          </p>
        </div>
      </div>

      {/* ─── PANEL FILTER LENGKAP (KATEGORI, HARI, BULAN, TAHUN, RENTANG TANGGAL) ─── */}
      <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 p-3.5 sm:p-4 mb-4 shadow-xs space-y-3.5">
        {/* Baris 1: Pencarian & Kategori Mutasi */}
        <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-2.5">
          {/* Filter Pills Kategori */}
          <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
            <button
              type="button"
              onClick={() => setMutationFilter('all')}
              className={`px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap ${
                mutationFilter === 'all'
                  ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-xs'
                  : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
              }`}
            >
              Semua ({allMutations.length})
            </button>
            <button
              type="button"
              onClick={() => setMutationFilter('income')}
              className={`px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1 ${
                mutationFilter === 'income'
                  ? 'bg-emerald-600 text-white shadow-xs'
                  : 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60'
              }`}
            >
              <span>↓ Pemasukan</span>
              <span className="text-[11px] opacity-80">({counts.inc})</span>
            </button>
            <button
              type="button"
              onClick={() => setMutationFilter('expense')}
              className={`px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1 ${
                mutationFilter === 'expense'
                  ? 'bg-rose-600 text-white shadow-xs'
                  : 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60'
              }`}
            >
              <span>↑ Pengeluaran</span>
              <span className="text-[11px] opacity-80">({counts.exp})</span>
            </button>
            <button
              type="button"
              onClick={() => setMutationFilter('refund')}
              className={`px-3 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1 ${
                mutationFilter === 'refund'
                  ? 'bg-sky-600 text-white shadow-xs'
                  : 'bg-sky-50 dark:bg-sky-950/30 text-sky-600 dark:text-sky-400 border border-sky-200 dark:border-sky-800/60'
              }`}
            >
              <span>↩ Refund Dana</span>
              <span className="text-[11px] opacity-80">({counts.ref})</span>
            </button>
          </div>

          {/* Input Pencarian Mutasi */}
          <div className="relative flex-1 lg:max-w-xs">
            <span className="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
              <Icon.Search className="w-4 h-4" />
            </span>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Cari deskripsi, #Invoice, nominal..."
              className="w-full pl-9 pr-8 py-2 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-semibold text-gray-800 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition"
            />
            {searchQuery && (
              <button
                type="button"
                onClick={() => setSearchQuery('')}
                className="absolute inset-y-0 right-0 flex items-center pr-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
              >
                <Icon.X className="w-3.5 h-3.5" />
              </button>
            )}
          </div>
        </div>

        {/* Baris 2: Filter Cepat Waktu (Hari Ini, Kemarin, 7 Hari, 30 Hari, Bulan Ini, Tahun Ini) */}
        <div>
          <span className="text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-gray-400 block mb-1.5">
            Filter Cepat Waktu
          </span>
          <div className="flex flex-wrap gap-1.5">
            {QUICK_PERIODS.map((p) => {
              const active = quickPeriod === p.id;
              return (
                <button
                  key={p.id}
                  type="button"
                  onClick={() => handleQuickPeriodChange(p.id)}
                  className={`px-2.5 py-1.5 rounded-lg text-[11px] font-bold transition cursor-pointer ${
                    active
                      ? 'bg-blue-600 text-white shadow-xs'
                      : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'
                  }`}
                >
                  {p.label}
                </button>
              );
            })}
          </div>
        </div>

        {/* Baris 3: Pilih Bulan, Tahun, Dari Tanggal, Sampai Tanggal & Urutan */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 pt-2 border-t border-gray-100 dark:border-gray-700/60">
          <div>
            <label className="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Pilih Bulan</label>
            <select
              value={selectedMonth}
              onChange={(e) => {
                setSelectedMonth(e.target.value);
                if (e.target.value !== 'all') setQuickPeriod('all');
              }}
              className="w-full px-2.5 py-2 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
            >
              {MONTH_OPTIONS.map((m) => (
                <option key={m.value} value={m.value}>{m.label}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Pilih Tahun</label>
            <select
              value={selectedYear}
              onChange={(e) => {
                setSelectedYear(e.target.value);
                if (e.target.value !== 'all') setQuickPeriod('all');
              }}
              className="w-full px-2.5 py-2 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
            >
              <option value="all">Semua Tahun</option>
              {availableYears.map((yr) => (
                <option key={yr} value={String(yr)}>{yr}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Dari Tanggal</label>
            <input
              type="date"
              value={dateFrom}
              onChange={(e) => {
                setDateFrom(e.target.value);
                if (e.target.value) setQuickPeriod('all');
              }}
              className="w-full px-2.5 py-1.5 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
            />
          </div>

          <div>
            <label className="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Sampai Tanggal</label>
            <input
              type="date"
              value={dateTo}
              onChange={(e) => {
                setDateTo(e.target.value);
                if (e.target.value) setQuickPeriod('all');
              }}
              className="w-full px-2.5 py-1.5 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
            />
          </div>

          <div className="col-span-2 sm:col-span-1">
            <label className="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Urutkan</label>
            <select
              value={sortOrder}
              onChange={(e) => setSortOrder(e.target.value)}
              className="w-full px-2.5 py-2 bg-gray-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 rounded-xl text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-blue-500"
            >
              <option value="newest">Terbaru</option>
              <option value="oldest">Terlama</option>
              <option value="highest">Nominal Tertinggi</option>
              <option value="lowest">Nominal Terendah</option>
            </select>
          </div>
        </div>

        {/* Baris 4: Ringkasan Hasil Filter & Tombol Reset */}
        {hasActiveFilter && (
          <div className="flex flex-wrap items-center justify-between gap-2 pt-2.5 border-t border-gray-100 dark:border-gray-700/60 text-xs">
            <div className="flex flex-wrap items-center gap-2 text-gray-600 dark:text-gray-300 font-semibold">
              <span>
                Ditemukan <strong className="text-blue-600 dark:text-blue-400">{filteredMutations.length}</strong> mutasi
              </span>
              <span>•</span>
              <span>
                Masuk: <strong className="text-emerald-600 dark:text-emerald-400">+{fmt.rp(filteredSummary.income)}</strong>
              </span>
              <span>•</span>
              <span>
                Keluar Bersih: <strong className="text-rose-600 dark:text-rose-400">-{fmt.rp(filteredSummary.netExpense)}</strong>
              </span>
              {filteredSummary.refund > 0 && (
                <>
                  <span>•</span>
                  <span>
                    Refund: <strong className="text-sky-600 dark:text-sky-400">{fmt.rp(filteredSummary.refund)}</strong>
                  </span>
                </>
              )}
            </div>
            <button
              type="button"
              onClick={resetAllFilters}
              className="text-xs font-extrabold text-rose-500 hover:text-rose-600 dark:text-rose-400 flex items-center gap-1 cursor-pointer"
            >
              <Icon.X className="w-3.5 h-3.5" /> Reset Semua Filter
            </button>
          </div>
        )}
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
          <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Tidak Ada Mutasi Sesuai Filter</h3>
          <p className="text-xs text-gray-400 mb-4">Tidak ditemukan catatan mutasi untuk tanggal atau kategori yang dipilih.</p>
          <button
            type="button"
            onClick={resetAllFilters}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Reset Filter &amp; Tampilkan Semua
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {paginatedMutations.map((m, idx) => {
            const isRefund = m.type === 'refund' || String(m.raw_type || '').toLowerCase() === 'refund';
            const isIncome = m.type === 'income' && !isRefund;
            const isExpense = m.type === 'expense';
            const isPending = m.status === 'pending';
            const isExpired = m.status === 'expired';

            return (
              <div key={m.id || idx} className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/80 p-4 shadow-sm hover:shadow-md transition">
                <div className="flex items-center justify-between gap-3 sm:gap-4">
                  {/* Icon & Detail */}
                  <div className="flex items-center gap-3 sm:gap-3.5 min-w-0">
                    <div className={`w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border font-black ${
                      isRefund
                        ? 'bg-sky-50 dark:bg-sky-950/40 text-sky-600 dark:text-sky-400 border-sky-200 dark:border-sky-800/60'
                        : isIncome
                          ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'
                          : isExpense
                            ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/60'
                            : isPending
                              ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800/60 animate-pulse'
                              : 'bg-gray-100 dark:bg-gray-800 text-gray-400 border-gray-200 dark:border-gray-700'
                    }`}>
                      {isRefund ? (
                        <span className="text-lg">↩</span>
                      ) : isIncome ? (
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
                        {isRefund && (
                          <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 border border-sky-300 dark:border-sky-700">
                            ↩ Pengembalian Dana (Refund)
                          </span>
                        )}
                        {isIncome && (
                          <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                            Pemasukan Top Up
                          </span>
                        )}
                        {isExpense && (
                          <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-700">
                            Pengeluaran Belanja
                          </span>
                        )}
                        {isPending && (
                          <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 animate-pulse">
                            Menunggu Pembayaran
                          </span>
                        )}
                        {isExpired && (
                          <span className="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-300 border border-gray-300 dark:border-gray-700">
                            Kadaluarsa
                          </span>
                        )}
                        {m.invoice_number && (
                          <span className="font-mono text-[11px] text-gray-400 truncate">
                            #{m.invoice_number}
                          </span>
                        )}
                      </div>

                      <p className="font-extrabold text-xs sm:text-sm text-gray-900 dark:text-white break-words">
                        {m.description || (isRefund ? 'Pengembalian Dana Transaksi Gagal' : isIncome ? 'Top Up Saldo' : isExpense ? 'Transaksi Saldo' : 'Tagihan QRIS')}
                      </p>

                      <div className="flex items-center gap-2 mt-0.5">
                        <p className="text-[11px] text-gray-400">{fmt.date(m.created_at)}</p>
                        {isPending && (
                          <span className="text-[11px] font-bold text-amber-500">
                            (Kadaluarsa dlm 10 menit)
                          </span>
                        )}
                      </div>
                    </div>
                  </div>

                  {/* Nominal & Tombol QRIS */}
                  <div className="text-right shrink-0">
                    <p className={`font-black text-sm sm:text-base ${
                      isRefund
                        ? 'text-sky-600 dark:text-sky-400'
                        : isIncome
                          ? 'text-emerald-600 dark:text-emerald-400'
                          : isExpense
                            ? 'text-rose-600 dark:text-rose-400'
                            : isPending
                              ? 'text-amber-500 dark:text-amber-400'
                              : 'text-gray-400'
                    }`}>
                      {isRefund || isIncome ? `+${fmt.rp(m.amount)}` : isExpense ? `-${fmt.rp(m.amount)}` : fmt.rp(m.amount)}
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
