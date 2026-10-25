import React, { useState, useMemo, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { Icon, fmt, buildRepeatOrderUrl } from './ProfileIcons';
import StatusBadge from './StatusBadge';
import OrderProgress from '../../../Components/OrderProgress';
import Pagination from './Pagination';

const MONTH_OPTIONS = [
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
  { key: 'all', label: 'Semua Waktu' },
  { key: 'today', label: 'Hari Ini' },
  { key: 'yesterday', label: 'Kemarin' },
  { key: '7d', label: '7 Hari Terakhir' },
  { key: '30d', label: '30 Hari Terakhir' },
  { key: 'this_month', label: 'Bulan Ini' },
  { key: 'this_year', label: 'Tahun Ini' },
];

export default function OrdersTab({
  orders = [],
  allOrders = [],
  activeCategory = 'app', // 'app' | 'ppob' | 'all'
  onSelectCategory,
  orderSearchQuery,
  setOrderSearchQuery,
  orderPage,
  setOrderPage,
  copiedSnId,
  setCopiedSnId,
  showToast,
  handleSendOrderWa,
  waSendingId,
  isReseller,
}) {
  // Filter States (Hari, Bulan, Tahun, Tanggal, Status, Metode Bayar, Urutan)
  const [periodFilter, setPeriodFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all'); // 'all' | 'completed' | 'processing' | 'failed'
  const [monthFilter, setMonthFilter] = useState('');
  const [yearFilter, setYearFilter] = useState('');
  const [startDateFilter, setStartDateFilter] = useState('');
  const [endDateFilter, setEndDateFilter] = useState('');
  const [methodFilter, setMethodFilter] = useState('all'); // 'all' | 'balance' | 'qris'
  const [sortBy, setSortBy] = useState('newest'); // 'newest' | 'oldest' | 'highest' | 'lowest'
  const [showAdvancedFilter, setShowAdvancedFilter] = useState(true);

  // Sync / Recheck Status States
  const [syncingAll, setSyncingAll] = useState(false);
  const [recheckingOrderId, setRecheckingOrderId] = useState(null);

  const appCount = allOrders.filter((o) => o.product_type !== 'ppob').length;
  const ppobCount = allOrders.filter((o) => o.product_type === 'ppob').length;
  const totalCount = allOrders.length;

  const pendingOrdersCount = useMemo(() => {
    return allOrders.filter((o) => {
      const st = String(o.order_status || '').toLowerCase();
      const pay = String(o.payment_status || '').toLowerCase();
      return ['paid', 'success', 'settlement'].includes(pay) && ['processing', 'pending'].includes(st);
    }).length;
  }, [allOrders]);

  // Daftar tahun dinamis dari riwayat transaksi + tahun berjalan
  const availableYears = useMemo(() => {
    const currentYear = new Date().getFullYear();
    const yearSet = new Set([currentYear, currentYear - 1, currentYear - 2]);
    allOrders.forEach((o) => {
      if (o.created_at) {
        const d = new Date(o.created_at);
        if (!Number.isNaN(d.getFullYear())) {
          yearSet.add(d.getFullYear());
        }
      }
    });
    return Array.from(yearSet).sort((a, b) => b - a);
  }, [allOrders]);

  // Reset halaman ke 1 saat filter berubah
  useEffect(() => {
    if (setOrderPage) setOrderPage(1);
  }, [
    periodFilter,
    statusFilter,
    monthFilter,
    yearFilter,
    startDateFilter,
    endDateFilter,
    methodFilter,
    sortBy,
    orderSearchQuery,
    activeCategory,
  ]);

  const hasActiveFilters =
    periodFilter !== 'all' ||
    statusFilter !== 'all' ||
    monthFilter !== '' ||
    yearFilter !== '' ||
    startDateFilter !== '' ||
    endDateFilter !== '' ||
    methodFilter !== '' && methodFilter !== 'all' ||
    sortBy !== 'newest' ||
    Boolean(orderSearchQuery && orderSearchQuery.trim());

  const resetAllFilters = () => {
    setPeriodFilter('all');
    setStatusFilter('all');
    setMonthFilter('');
    setYearFilter('');
    setStartDateFilter('');
    setEndDateFilter('');
    setMethodFilter('all');
    setSortBy('newest');
    if (setOrderSearchQuery) setOrderSearchQuery('');
    if (setOrderPage) setOrderPage(1);
  };

  const handleQuickPeriodChange = (key) => {
    setPeriodFilter(key);
    if (key !== 'all') {
      setMonthFilter('');
      setYearFilter('');
      setStartDateFilter('');
      setEndDateFilter('');
    }
  };

  // Sinkronisasi Semua Transaksi Pending (/Services/Check-Status Kiosbank & API)
  const handleSyncAllOrders = async () => {
    if (syncingAll) return;
    setSyncingAll(true);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const res = await fetch('/profile/orders/sync-pending', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({}),
      });
      const data = await res.json();
      router.reload({
        only: ['orders', 'auth', 'mutations', 'topups', 'stats'],
        preserveScroll: true,
        onFinish: () => {
          setSyncingAll(false);
          if (showToast) {
            showToast('success', data.message || 'Status pesanan berhasil diperbarui dari server!');
          }
        },
      });
    } catch {
      router.reload({
        only: ['orders'],
        preserveScroll: true,
        onFinish: () => {
          setSyncingAll(false);
          if (showToast) showToast('success', 'Daftar pesanan telah dimuat ulang.');
        },
      });
    }
  };

  // Cek Status Ulang 1 Transaksi PPOB Spesifik (/Services/Check-Status)
  const handleRecheckSingleOrder = async (invoiceOrId) => {
    if (!invoiceOrId || recheckingOrderId) return;
    setRecheckingOrderId(invoiceOrId);
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const res = await fetch(`/ppob/check-status/${encodeURIComponent(invoiceOrId)}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
      });
      const data = await res.json();
      router.reload({
        only: ['orders', 'auth', 'mutations', 'topups', 'stats'],
        preserveScroll: true,
        onFinish: () => {
          setRecheckingOrderId(null);
          if (showToast) {
            const st = String(data.order_status || '').toLowerCase();
            const toastType = ['failed', 'refunded'].includes(st) ? 'error' : 'success';
            showToast(toastType, data.message || 'Status transaksi telah diperiksa ke server Kiosbank.');
          }
        },
      });
    } catch {
      setRecheckingOrderId(null);
      if (showToast) showToast('error', 'Gagal memeriksa status ke server. Silakan coba lagi.');
    }
  };

  // Logika Pemfilteran Lengkap
  const computedFilteredOrders = useMemo(() => {
    const now = new Date();
    const todayStart = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0);
    const todayEnd = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 23, 59, 59, 999);

    const yesterdayStart = new Date(todayStart);
    yesterdayStart.setDate(yesterdayStart.getDate() - 1);
    const yesterdayEnd = new Date(todayEnd);
    yesterdayEnd.setDate(yesterdayEnd.getDate() - 1);

    const sevenDaysAgo = new Date(todayStart);
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 6);

    const thirtyDaysAgo = new Date(todayStart);
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);

    const list = orders.filter((o) => {
      // 1. Pencarian Kata Kunci
      if (orderSearchQuery && orderSearchQuery.trim()) {
        const q = orderSearchQuery.toLowerCase().trim();
        const inv = String(o.invoice_number || o.id || '').toLowerCase();
        const prod = String(o.product_name || '').toLowerCase();
        const wa = String(o.whatsapp || '').toLowerCase();
        const email = String(o.email || '').toLowerCase();
        const sn = String(o.sn_or_account || '').toLowerCase();
        const notes = String(o.notes || '').toLowerCase();
        const status = String(o.order_status || o.payment_status || '').toLowerCase();
        const total = String(o.total_amount || o.price || '').toLowerCase();

        const matchesQuery =
          inv.includes(q) ||
          prod.includes(q) ||
          wa.includes(q) ||
          email.includes(q) ||
          sn.includes(q) ||
          notes.includes(q) ||
          status.includes(q) ||
          total.includes(q);

        if (!matchesQuery) return false;
      }

      // 2. Filter Status Transaksi
      if (statusFilter !== 'all') {
        const rawStatus = String(o.order_status || o.payment_status || '').toLowerCase();
        if (statusFilter === 'completed' && !['completed', 'success', 'lunas', 'selesai'].includes(rawStatus)) {
          return false;
        }
        if (statusFilter === 'processing' && !['processing', 'pending'].includes(rawStatus)) {
          return false;
        }
        if (statusFilter === 'failed' && !['failed', 'refunded', 'canceled', 'cancelled', 'expired'].includes(rawStatus)) {
          return false;
        }
      }

      // 3. Filter Metode Pembayaran
      if (methodFilter !== 'all') {
        const invUpper = String(o.invoice_number || '').toUpperCase();
        const isQrisOrder = invUpper.includes('QRIS') || invUpper.startsWith('INV-');
        if (methodFilter === 'balance' && isQrisOrder) return false;
        if (methodFilter === 'qris' && !isQrisOrder) return false;
      }

      // 4. Filter Tanggal / Hari / Bulan / Tahun
      if (o.created_at) {
        const orderDate = new Date(o.created_at);
        if (!Number.isNaN(orderDate.getTime())) {
          // Periode Cepat
          if (periodFilter === 'today' && (orderDate < todayStart || orderDate > todayEnd)) {
            return false;
          }
          if (periodFilter === 'yesterday' && (orderDate < yesterdayStart || orderDate > yesterdayEnd)) {
            return false;
          }
          if (periodFilter === '7d' && orderDate < sevenDaysAgo) {
            return false;
          }
          if (periodFilter === '30d' && orderDate < thirtyDaysAgo) {
            return false;
          }
          if (
            periodFilter === 'this_month' &&
            (orderDate.getMonth() !== now.getMonth() || orderDate.getFullYear() !== now.getFullYear())
          ) {
            return false;
          }
          if (periodFilter === 'this_year' && orderDate.getFullYear() !== now.getFullYear()) {
            return false;
          }

          // Bulan Spesifik (1-12)
          if (monthFilter !== '' && orderDate.getMonth() + 1 !== Number(monthFilter)) {
            return false;
          }

          // Tahun Spesifik
          if (yearFilter !== '' && orderDate.getFullYear() !== Number(yearFilter)) {
            return false;
          }

          // Rentang Tanggal (Dari Tanggal - Sampai Tanggal)
          if (startDateFilter) {
            const [sy, sm, sd] = startDateFilter.split('-').map(Number);
            const startBound = new Date(sy, sm - 1, sd, 0, 0, 0, 0);
            if (orderDate < startBound) return false;
          }
          if (endDateFilter) {
            const [ey, em, ed] = endDateFilter.split('-').map(Number);
            const endBound = new Date(ey, em - 1, ed, 23, 59, 59, 999);
            if (orderDate > endBound) return false;
          }
        }
      }

      return true;
    });

    // 5. Pengurutan (Sorting)
    return [...list].sort((a, b) => {
      if (sortBy === 'oldest') {
        return new Date(a.created_at || 0).getTime() - new Date(b.created_at || 0).getTime();
      }
      if (sortBy === 'highest') {
        return Number(b.total_amount || b.price || 0) - Number(a.total_amount || a.price || 0);
      }
      if (sortBy === 'lowest') {
        return Number(a.total_amount || a.price || 0) - Number(b.total_amount || b.price || 0);
      }
      return new Date(b.created_at || 0).getTime() - new Date(a.created_at || 0).getTime();
    });
  }, [
    orders,
    orderSearchQuery,
    statusFilter,
    methodFilter,
    periodFilter,
    monthFilter,
    yearFilter,
    startDateFilter,
    endDateFilter,
    sortBy,
  ]);

  const computedTotalPages = Math.max(1, Math.ceil(computedFilteredOrders.length / 10));
  const currentPage = Math.min(orderPage || 1, computedTotalPages);
  const computedPaginatedOrders = computedFilteredOrders.slice((currentPage - 1) * 10, currentPage * 10);

  // Total nominal dari pesanan yang tampil pada hasil filter (khusus yang sukses/lunas)
  const filteredTotalNominal = useMemo(() => {
    return computedFilteredOrders
      .filter((o) => !['failed', 'refunded', 'canceled', 'cancelled', 'expired'].includes(String(o.order_status || '').toLowerCase()))
      .reduce((acc, curr) => acc + Number(curr.total_amount || curr.price || 0), 0);
  }, [computedFilteredOrders]);

  const getHeaderInfo = () => {
    if (activeCategory === 'ppob') {
      return {
        title: 'Riwayat Transaksi PPOB',
        desc: 'Daftar pembelian pulsa, paket data, token PLN, tagihan PDAM, voucher game & e-wallet',
        emptyTitle: 'Belum Ada Transaksi PPOB',
        emptyDesc: 'Transaksi PPOB Anda akan tercatat secara otomatis di sini.',
        emptyCta: 'Beli PPOB Sekarang →',
        emptyHref: '/ppob',
      };
    }
    if (activeCategory === 'app') {
      return {
        title: 'Riwayat Pesanan Aplikasi Premium',
        desc: 'Daftar transaksi lisensi dan akun aplikasi premium resmi Anda',
        emptyTitle: 'Belum Ada Pesanan Aplikasi',
        emptyDesc: 'Riwayat akun aplikasi premium Anda akan muncul di sini setelah pembelian.',
        emptyCta: 'Mulai Belanja Aplikasi →',
        emptyHref: '/#katalog-produk',
      };
    }
    return {
      title: 'Semua Riwayat Pembelian',
      desc: 'Daftar seluruh transaksi produk aplikasi premium dan PPOB',
      emptyTitle: 'Belum Ada Pembelian',
      emptyDesc: 'Riwayat transaksi Anda akan muncul di sini.',
      emptyCta: 'Mulai Belanja Sekarang →',
      emptyHref: '/',
    };
  };

  const header = getHeaderInfo();

  return (
    <div>
      {/* Header Info & Action Bar */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
            <span>{header.title}</span>
            {activeCategory === 'ppob' && (
              <span className="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                PPOB
              </span>
            )}
            {activeCategory === 'app' && (
              <span className="text-[11px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-300 dark:border-blue-800">
                💎 Premium
              </span>
            )}
          </h2>
          <p className="text-xs text-gray-400 mt-0.5">{header.desc}</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <span className="text-xs font-bold px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shrink-0">
            {hasActiveFilters
              ? `${computedFilteredOrders.length} dari ${orders.length} transaksi`
              : `${orders.length} transaksi`}
          </span>
        </div>
      </div>

      {/* Baris Tombol Sinkronisasi Status & Toggle Filter */}
      <div className="flex flex-wrap items-center justify-between gap-2.5 mb-3">
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={handleSyncAllOrders}
            disabled={syncingAll}
            className="min-h-10 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-orange-500/50 text-xs sm:text-sm font-bold text-gray-700 dark:text-gray-200 inline-flex items-center gap-2 shadow-sm transition cursor-pointer disabled:opacity-60"
            title="Sinkronkan status pesanan terbaru & cek transaksi PPOB pending ke server (/Services/Check-Status)"
          >
            <svg
              className={`w-4 h-4 text-orange-500 ${syncingAll ? 'animate-spin' : ''}`}
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2.2"
                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
              />
            </svg>
            <span>{syncingAll ? 'Menyinkronkan Status...' : 'Perbarui Status Pesanan'}</span>
            {pendingOrdersCount > 0 && (
              <span className="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                {pendingOrdersCount} Pending
              </span>
            )}
          </button>

          <button
            type="button"
            onClick={() => setShowAdvancedFilter((prev) => !prev)}
            className={`min-h-10 px-3.5 py-2 rounded-xl border text-xs font-bold inline-flex items-center gap-1.5 transition cursor-pointer ${
              showAdvancedFilter || hasActiveFilters
                ? 'border-orange-500/50 bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400'
                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300'
            }`}
          >
            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            <span>Filter Tanggal &amp; Lainnya</span>
          </button>
        </div>

        {hasActiveFilters && (
          <button
            type="button"
            onClick={resetAllFilters}
            className="px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 text-xs font-bold hover:bg-rose-100 transition cursor-pointer inline-flex items-center gap-1"
          >
            <Icon.X className="w-3.5 h-3.5" />
            <span>Reset Semua Filter</span>
          </button>
        )}
      </div>

      {/* FILTER TABS: APLIKASI VS PPOB VS SEMUA */}
      <div className="flex items-center gap-2 p-1.5 bg-gray-100 dark:bg-gray-800/80 rounded-2xl mb-4 border border-gray-200/60 dark:border-gray-700/60 overflow-x-auto">
        <button
          type="button"
          onClick={() => onSelectCategory && onSelectCategory('app')}
          className={`px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2 shrink-0 ${
            activeCategory === 'app'
              ? 'bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm border border-gray-200/50 dark:border-gray-700'
              : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
          }`}
        >
          <Icon.App className="w-3.5 h-3.5" />
          <span>Aplikasi Premium</span>
          <span
            className={`text-[11px] px-1.5 py-0.2 rounded-full font-bold ${
              activeCategory === 'app'
                ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
            }`}
          >
            {appCount}
          </span>
        </button>

        <button
          type="button"
          onClick={() => onSelectCategory && onSelectCategory('ppob')}
          className={`px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2 shrink-0 ${
            activeCategory === 'ppob'
              ? 'bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 shadow-sm border border-gray-200/50 dark:border-gray-700'
              : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
          }`}
        >
          <Icon.Ppob className="w-3.5 h-3.5 text-emerald-500" />
          <span>PPOB</span>
          <span
            className={`text-[11px] px-1.5 py-0.2 rounded-full font-bold ${
              activeCategory === 'ppob'
                ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
            }`}
          >
            {ppobCount}
          </span>
        </button>

        <button
          type="button"
          onClick={() => onSelectCategory && onSelectCategory('all')}
          className={`px-3.5 py-2 rounded-xl text-xs font-black transition cursor-pointer flex items-center gap-2 shrink-0 ${
            activeCategory === 'all'
              ? 'bg-white dark:bg-gray-900 text-orange-600 dark:text-orange-400 shadow-sm border border-gray-200/50 dark:border-gray-700'
              : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
          }`}
        >
          <Icon.History className="w-3.5 h-3.5" />
          <span>Semua Transaksi</span>
          <span
            className={`text-[11px] px-1.5 py-0.2 rounded-full font-bold ${
              activeCategory === 'all'
                ? 'bg-orange-100 dark:bg-orange-950 text-orange-700 dark:text-orange-300'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
            }`}
          >
            {totalCount}
          </span>
        </button>
      </div>

      {/* PANEL FILTER LENGKAP: HARI, BULAN, TAHUN, TANGGAL, STATUS, METODE & URUTAN */}
      {orders.length > 0 && (
        <div className="mb-4 space-y-3">
          {/* Search Input */}
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
              <Icon.Search className="w-4 h-4" />
            </div>
            <input
              type="text"
              value={orderSearchQuery}
              onChange={(e) => setOrderSearchQuery(e.target.value)}
              placeholder="Cari berdasarkan No. Invoice, Nama Produk, Akun/SN, No. WA, Catatan..."
              className="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 shadow-sm transition"
            />
            {orderSearchQuery && (
              <button
                type="button"
                onClick={() => setOrderSearchQuery('')}
                className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
                aria-label="Hapus Pencarian"
              >
                <Icon.X className="w-4 h-4" />
              </button>
            )}
          </div>

          {showAdvancedFilter && (
            <div className="p-3.5 sm:p-4 rounded-2xl bg-white dark:bg-gray-800/90 border border-gray-200/80 dark:border-gray-700/80 shadow-sm space-y-3.5">
              {/* 1. Pill Rentang Waktu Cepat (Hari / Minggu / Bulan / Tahun) */}
              <div>
                <span className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1.5">
                  Filter Waktu Cepat (Hari / Bulan / Tahun)
                </span>
                <div className="flex items-center gap-1.5 overflow-x-auto pb-1 no-scrollbar">
                  {QUICK_PERIODS.map((p) => {
                    const active =
                      periodFilter === p.key &&
                      monthFilter === '' &&
                      yearFilter === '' &&
                      !startDateFilter &&
                      !endDateFilter;
                    return (
                      <button
                        key={p.key}
                        type="button"
                        onClick={() => handleQuickPeriodChange(p.key)}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition cursor-pointer border ${
                          active
                            ? 'bg-orange-500 text-white border-orange-500 shadow-sm shadow-orange-500/20'
                            : 'bg-gray-50 dark:bg-gray-900/60 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-orange-400'
                        }`}
                      >
                        {p.label}
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* 2. Grid Dropdown: Bulan, Tahun, Status, Metode Bayar & Urutan */}
              <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                <div>
                  <label className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">
                    Pilih Bulan
                  </label>
                  <select
                    value={monthFilter}
                    onChange={(e) => {
                      setMonthFilter(e.target.value);
                      if (e.target.value !== '') setPeriodFilter('all');
                    }}
                    className="w-full px-2.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  >
                    <option value="">Semua Bulan</option>
                    {MONTH_OPTIONS.map((m) => (
                      <option key={m.value} value={m.value}>
                        {m.label}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">
                    Pilih Tahun
                  </label>
                  <select
                    value={yearFilter}
                    onChange={(e) => {
                      setYearFilter(e.target.value);
                      if (e.target.value !== '') setPeriodFilter('all');
                    }}
                    className="w-full px-2.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  >
                    <option value="">Semua Tahun</option>
                    {availableYears.map((y) => (
                      <option key={y} value={String(y)}>
                        Tahun {y}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">
                    Status Pesanan
                  </label>
                  <select
                    value={statusFilter}
                    onChange={(e) => setStatusFilter(e.target.value)}
                    className="w-full px-2.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  >
                    <option value="all">Semua Status</option>
                    <option value="completed">✓ Berhasil / Selesai</option>
                    <option value="processing">⏳ Pending / Diproses</option>
                    <option value="failed">✕ Gagal / Refund</option>
                  </select>
                </div>

                <div>
                  <label className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">
                    Metode Bayar
                  </label>
                  <select
                    value={methodFilter}
                    onChange={(e) => setMethodFilter(e.target.value)}
                    className="w-full px-2.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  >
                    <option value="all">Semua Metode</option>
                    <option value="balance">💰 Saldo Akun</option>
                    <option value="qris">📱 QRIS Otomatis</option>
                  </select>
                </div>

                <div className="col-span-2 sm:col-span-1">
                  <label className="block text-[10px] font-extrabold uppercase tracking-wider text-gray-400 mb-1">
                    Urutkan
                  </label>
                  <select
                    value={sortBy}
                    onChange={(e) => setSortBy(e.target.value)}
                    className="w-full px-2.5 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  >
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                    <option value="highest">Nominal Tertinggi</option>
                    <option value="lowest">Nominal Terendah</option>
                  </select>
                </div>
              </div>

              {/* 3. Filter Tanggal Spesifik / Rentang Tanggal + Ringkasan Nominal */}
              <div className="pt-2.5 border-t border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-2">
                  <span className="text-[11px] font-bold text-gray-500 dark:text-gray-400">
                    📅 Filter Tanggal:
                  </span>
                  <input
                    type="date"
                    aria-label="Dari Tanggal"
                    value={startDateFilter}
                    onChange={(e) => {
                      setStartDateFilter(e.target.value);
                      if (e.target.value) setPeriodFilter('all');
                    }}
                    className="px-2.5 py-1.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  />
                  <span className="text-xs text-gray-400 font-bold">s/d</span>
                  <input
                    type="date"
                    aria-label="Sampai Tanggal"
                    value={endDateFilter}
                    onChange={(e) => {
                      setEndDateFilter(e.target.value);
                      if (e.target.value) setPeriodFilter('all');
                    }}
                    className="px-2.5 py-1.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:border-orange-500"
                  />
                </div>

                <div className="flex items-center justify-between sm:justify-end gap-3 text-xs">
                  <span className="text-gray-500 dark:text-gray-400 font-semibold">
                    Total Transaksi Aktif:{' '}
                    <strong className="font-black text-orange-500">{fmt.rp(filteredTotalNominal)}</strong>
                  </span>
                </div>
              </div>
            </div>
          )}
        </div>
      )}

      {orders.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center shadow-sm">
          <div className="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
            {activeCategory === 'ppob' ? (
              <Icon.Ppob className="w-8 h-8 text-emerald-500" />
            ) : (
              <Icon.App className="w-8 h-8 text-blue-500" />
            )}
          </div>
          <h3 className="font-extrabold text-gray-800 dark:text-white mb-1">{header.emptyTitle}</h3>
          <p className="text-xs text-gray-400 mb-5">{header.emptyDesc}</p>
          <Link
            href={header.emptyHref}
            className={`inline-flex items-center gap-2 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition cursor-pointer ${
              activeCategory === 'ppob'
                ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20'
                : 'bg-orange-500 hover:bg-orange-600 shadow-orange-500/20'
            }`}
          >
            {header.emptyCta}
          </Link>
        </div>
      ) : computedFilteredOrders.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-10 text-center shadow-sm">
          <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
            <Icon.Search className="w-7 h-7" />
          </div>
          <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Pesanan Tidak Ditemukan</h3>
          <p className="text-xs text-gray-400 mb-4">
            Tidak ada transaksi yang cocok dengan filter tanggal, bulan, tahun, atau kata kunci yang dipilih.
          </p>
          <button
            type="button"
            onClick={resetAllFilters}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            <Icon.X className="w-3.5 h-3.5" />
            Reset Semua Filter
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {computedPaginatedOrders.map((o, i) => {
            const isOrderPpob = o.product_type === 'ppob' || String(o.invoice_number || '').startsWith('PPOB-');
            const isOrderPending = ['processing', 'pending'].includes(String(o.order_status || '').toLowerCase());
            const isOrderRechecking = recheckingOrderId === (o.invoice_number || o.id);

            return (
              <div
                key={o.id || i}
                className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 sm:p-5 shadow-sm hover:shadow-md transition-shadow"
              >
                <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                  <div className="flex-1 min-w-0">
                    <div className="flex flex-wrap items-center gap-2 mb-1.5">
                      <span className="text-[11px] font-mono font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/60 px-2.5 py-0.5 rounded-md break-all">
                        #{o.invoice_number || o.id}
                      </span>
                      <StatusBadge status={o.order_status || o.payment_status} isPpob={isOrderPpob} />
                      {isOrderPpob ? (
                        <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                          PPOB
                        </span>
                      ) : (
                        <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                          Akun Premium
                        </span>
                      )}
                    </div>
                    <p className="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white leading-snug mb-1 break-words">
                      {o.product_name}
                      {o.quantity > 1 && (
                        <span className="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                          {o.quantity} pcs
                        </span>
                      )}
                    </p>

                    <OrderProgress order={o} />

                    {o.notes && !/premku|preflix|provider|api|invalid|\[internal/i.test(o.notes) && (
                      <p className="text-xs text-gray-500 dark:text-gray-300 mb-2 italic break-words">
                        Catatan: {o.notes}
                      </p>
                    )}

                    {isOrderPpob && ['failed', 'refunded'].includes(String(o.order_status || '').toLowerCase()) && (
                      <div className="mt-2.5 p-3 sm:p-3.5 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/50 rounded-xl">
                        <p className="text-[11px] font-black text-rose-600 dark:text-rose-400 uppercase tracking-wider mb-1">
                          ✕ Transaksi Gagal — Saldo Di-Refund 100%
                        </p>
                        <p className="text-xs font-semibold text-rose-900 dark:text-rose-200 leading-relaxed">
                          Transaksi ini tidak dapat diproses oleh operator. Dana sebesar{' '}
                          <span className="font-black">{fmt.rp(o.total_amount || o.price)}</span> telah dikembalikan
                          otomatis 100% ke Saldo Akun Anda.
                        </p>
                      </div>
                    )}

                    {isOrderPpob && isOrderPending && (
                      <div className="mt-2.5 p-3 sm:p-3.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-xl">
                        <div className="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                          <p className="text-[11px] font-black text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                            ⏳ Status: Pending (Sedang Diproses Operator)
                          </p>
                          <button
                            type="button"
                            onClick={() => handleRecheckSingleOrder(o.invoice_number || o.id)}
                            disabled={isOrderRechecking}
                            className="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-[11px] font-black shadow-sm transition cursor-pointer disabled:opacity-60"
                            title="Cek status terbaru transaksi ini ke endpoint /Services/Check-Status Kiosbank"
                          >
                            <svg
                              className={`w-3.5 h-3.5 ${isOrderRechecking ? 'animate-spin' : ''}`}
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                            >
                              <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                              />
                            </svg>
                            <span>{isOrderRechecking ? 'Mengecek Server...' : 'Cek Status Ulang'}</span>
                          </button>
                        </div>
                        <p className="text-xs font-semibold text-amber-900 dark:text-amber-200 leading-relaxed">
                          Transaksi sedang dalam antrean pemrosesan operator. SN / Token akan muncul otomatis setelah
                          transaksi berhasil, atau saldo di-refund otomatis 100% jika transaksi gagal.
                        </p>
                      </div>
                    )}

                    {o.sn_or_account && !['failed', 'refunded'].includes(String(o.order_status || '').toLowerCase()) && (
                      <div className="mt-2.5 p-3 sm:p-3.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl">
                        <div className="flex items-center justify-between gap-2 mb-1.5">
                          <p className="text-[11px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                            Detail Akun / Serial Number
                          </p>
                          <button
                            type="button"
                            onClick={() => {
                              navigator.clipboard.writeText(o.sn_or_account);
                              setCopiedSnId(o.id || o.invoice_number);
                              showToast('success', 'Detail akun berhasil disalin ke clipboard!');
                              setTimeout(() => setCopiedSnId(null), 2500);
                            }}
                            className="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline flex items-center gap-1 cursor-pointer shrink-0"
                          >
                            {copiedSnId === (o.id || o.invoice_number) ? (
                              <>
                                <Icon.Check className="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                                <span className="text-emerald-600 dark:text-emerald-400 font-black">Tersalin!</span>
                              </>
                            ) : (
                              <>
                                <Icon.Copy className="w-3 h-3" />
                                <span>Salin Akun</span>
                              </>
                            )}
                          </button>
                        </div>
                        <div className="bg-white/80 dark:bg-black/30 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                          <p className="text-xs font-mono font-bold text-emerald-900 dark:text-emerald-200 break-all whitespace-pre-wrap select-all">
                            {o.sn_or_account}
                          </p>
                        </div>
                      </div>
                    )}

                    <div className="flex flex-wrap items-center gap-2 mt-3">
                      <p className="text-[11px] text-gray-400 font-medium">{fmt.date(o.created_at)}</p>
                      <a
                        href={buildRepeatOrderUrl(o)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white text-xs font-extrabold rounded-xl transition cursor-pointer shadow-sm shadow-orange-500/20"
                        title="Beli Lagi Produk & Nomor Tujuan Ini dengan 1 Klik"
                      >
                        <Icon.Refresh className="w-3.5 h-3.5" />
                        <span>Beli Lagi</span>
                      </a>
                      <a
                        href={`/order/invoice/${o.invoice_number || o.id}`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer shadow-sm"
                        title="Lihat dan Cetak Invoice PDF Resmi"
                      >
                        <Icon.FileText className="w-3.5 h-3.5 text-orange-500" />
                        <span>Cetak Invoice</span>
                      </a>
                      <button
                        type="button"
                        onClick={() => handleSendOrderWa(o.invoice_number || o.id)}
                        disabled={waSendingId === (o.invoice_number || o.id)}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-xl transition cursor-pointer shadow-sm border border-emerald-200 dark:border-emerald-800 disabled:opacity-50"
                        title="Kirim detail akun & invoice ke nomor WhatsApp Anda"
                      >
                        {waSendingId === (o.invoice_number || o.id) ? (
                          <>
                            <svg className="w-3.5 h-3.5 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                              <path
                                className="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                              />
                            </svg>
                            <span>Mengirim...</span>
                          </>
                        ) : (
                          <>
                            <span className="text-sm">📱</span>
                            <span>Kirim ke WA</span>
                          </>
                        )}
                      </button>
                    </div>
                  </div>

                  <div className="text-left sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-700">
                    <p className="text-base sm:text-lg font-black text-orange-500">
                      {fmt.rp(o.total_amount || o.price)}
                    </p>
                    {isReseller && o.discount_amount > 0 && (
                      <span className="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        Diskon {fmt.rp(o.discount_amount)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          {/* Pagination */}
          <Pagination
            page={currentPage}
            totalPages={computedTotalPages}
            onPageChange={setOrderPage}
            itemCount={computedFilteredOrders.length}
            itemName="pesanan"
          />
        </div>
      )}
    </div>
  );
}
