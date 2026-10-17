import React from 'react';
import { Link } from '@inertiajs/react';
import { Icon, fmt } from './ProfileIcons';
import StatusBadge from './StatusBadge';
import Pagination from './Pagination';

export default function OrdersTab({
  orders,
  allOrders = [],
  activeCategory = 'app', // 'app' | 'ppob' | 'all'
  onSelectCategory,
  filteredOrders,
  paginatedOrders,
  orderSearchQuery,
  setOrderSearchQuery,
  orderPage,
  setOrderPage,
  orderTotalPages,
  copiedSnId,
  setCopiedSnId,
  showToast,
  handleSendOrderWa,
  waSendingId,
  isReseller,
}) {
  const appCount = allOrders.filter((o) => o.product_type !== 'ppob').length;
  const ppobCount = allOrders.filter((o) => o.product_type === 'ppob').length;
  const totalCount = allOrders.length;

  const getHeaderInfo = () => {
    if (activeCategory === 'ppob') {
      return {
        title: 'Riwayat Transaksi PPOB',
        desc: 'Daftar pembelian pulsa, paket data, token PLN, voucher game & e-wallet',
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
      {/* Header Info */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
            <span>{header.title}</span>
            {activeCategory === 'ppob' && (
              <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                ⚡ PPOB
              </span>
            )}
            {activeCategory === 'app' && (
              <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 border border-blue-300 dark:border-blue-800">
                💎 Premium
              </span>
            )}
          </h2>
          <p className="text-xs text-gray-400 mt-0.5">{header.desc}</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shrink-0">
            {orderSearchQuery.trim()
              ? `${filteredOrders.length} dari ${orders.length} transaksi`
              : `${orders.length} transaksi`}
          </span>
        </div>
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
            className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
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
            className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
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
            className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
              activeCategory === 'all'
                ? 'bg-orange-100 dark:bg-orange-950 text-orange-700 dark:text-orange-300'
                : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
            }`}
          >
            {totalCount}
          </span>
        </button>
      </div>

      {/* Search Bar */}
      {orders.length > 0 && (
        <div className="mb-4">
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
      ) : filteredOrders.length === 0 ? (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-10 text-center shadow-sm">
          <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
            <Icon.Search className="w-7 h-7" />
          </div>
          <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Pesanan Tidak Ditemukan</h3>
          <p className="text-xs text-gray-400 mb-4">Tidak ada transaksi yang cocok dengan "{orderSearchQuery}".</p>
          <button
            type="button"
            onClick={() => setOrderSearchQuery('')}
            className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            <Icon.X className="w-3.5 h-3.5" />
            Reset Pencarian
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          {paginatedOrders.map((o, i) => (
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
                    <StatusBadge status={o.order_status || o.payment_status} />
                    {o.product_type === 'ppob' ? (
                      <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        ⚡ PPOB
                      </span>
                    ) : (
                      <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                        ⚡ Akun Premium
                      </span>
                    )}
                  </div>
                  <p className="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white leading-snug mb-1 break-words">
                    {o.product_name}
                  </p>

                  {o.notes && !/premku|preflix|provider|api|invalid|\[internal/i.test(o.notes) && (
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-2 italic break-words">
                      Catatan: {o.notes}
                    </p>
                  )}

                  {o.sn_or_account && (
                    <div className="mt-2.5 p-3 sm:p-3.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl">
                      <div className="flex items-center justify-between gap-2 mb-1.5">
                        <p className="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
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
                          className="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline flex items-center gap-1 cursor-pointer shrink-0"
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
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
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
                    <span className="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                      Diskon {fmt.rp(o.discount_amount)}
                    </span>
                  )}
                </div>
              </div>
            </div>
          ))}

          {/* Pagination */}
          <Pagination
            page={orderPage}
            totalPages={orderTotalPages}
            onPageChange={setOrderPage}
            itemCount={filteredOrders.length}
            itemName="pesanan"
          />
        </div>
      )}
    </div>
  );
}
