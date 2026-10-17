import React from 'react';
import { Link } from '@inertiajs/react';
import { Icon, fmt } from './ProfileIcons';
import StatusBadge from './StatusBadge';
import Pagination from './Pagination';

export default function OrdersTab({
  orders,
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
  return (
    <div>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
          <h2 className="text-lg font-black text-gray-900 dark:text-white">Riwayat Pembelian</h2>
          <p className="text-xs text-gray-400 mt-0.5">Daftar semua transaksi dan produk yang telah kamu beli</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
            {orderSearchQuery.trim()
              ? `${filteredOrders.length} dari ${orders.length} transaksi`
              : `${orders.length} transaksi`}
          </span>
        </div>
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
            <Icon.History className="w-8 h-8 text-gray-300 dark:text-gray-600" />
          </div>
          <h3 className="font-extrabold text-gray-800 dark:text-white mb-1">Belum Ada Pembelian</h3>
          <p className="text-xs text-gray-400 mb-5">Riwayat transaksi kamu akan muncul di sini.</p>
          <Link href="/" className="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition">
            Mulai Belanja
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
            <div key={o.id || i} className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 sm:p-5 shadow-sm hover:shadow-md transition-shadow">
              <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div className="flex-1 min-w-0">
                  <div className="flex flex-wrap items-center gap-2 mb-1.5">
                    <span className="text-[11px] font-mono font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/60 px-2.5 py-0.5 rounded-md break-all">#{o.invoice_number || o.id}</span>
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
                  <p className="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white leading-snug mb-1 break-words">{o.product_name}</p>

                  {o.notes && !/premku|preflix|provider|api|invalid|\[internal/i.test(o.notes) && (
                    <p className="text-xs text-gray-500 dark:text-gray-400 mb-2 italic break-words">
                      Catatan: {o.notes}
                    </p>
                  )}

                  {o.sn_or_account && (
                    <div className="mt-2.5 p-3 sm:p-3.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl">
                      <div className="flex items-center justify-between gap-2 mb-1.5">
                        <p className="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Detail Akun / Serial Number</p>
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
                        <p className="text-xs font-mono font-bold text-emerald-900 dark:text-emerald-200 break-all whitespace-pre-wrap select-all">{o.sn_or_account}</p>
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
                      title="Kirim detail akun & link invoice ke nomor WhatsApp terverifikasi"
                    >
                      {waSendingId === (o.invoice_number || o.id) ? (
                        <span>Mengirim...</span>
                      ) : (
                        <>
                          <span>📲</span>
                          <span>Kirim ke WA</span>
                        </>
                      )}
                    </button>
                  </div>
                </div>
                <div className="sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-700 flex sm:flex-col justify-between sm:justify-start items-center sm:items-end">
                  <span className="text-xs text-gray-400 sm:hidden">Total Bayar:</span>
                  <div>
                    <p className={`font-black text-base sm:text-lg ${isReseller ? 'text-amber-500' : 'text-orange-500'}`}>{fmt.rp(o.total_amount || o.price)}</p>
                    {o.discount_amount > 0 && <p className="text-[10px] text-green-600 font-bold">Hemat {fmt.rp(o.discount_amount)}</p>}
                  </div>
                </div>
              </div>
            </div>
          ))}

          {/* Pagination Riwayat Pembelian */}
          <div className="pt-2">
            <Pagination
              currentPage={orderPage}
              totalPages={orderTotalPages}
              onPageChange={(p) => {
                setOrderPage(p);
                window.scrollTo({ top: 0, behavior: 'smooth' });
              }}
            />
            {filteredOrders.length > 0 && (
              <p className="text-center text-[11px] text-gray-400 mt-2 font-medium">
                Menampilkan {((orderPage - 1) * 10) + 1} - {Math.min(orderPage * 10, filteredOrders.length)} dari {filteredOrders.length} riwayat pembelian
              </p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
