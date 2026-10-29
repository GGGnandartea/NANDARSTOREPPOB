import React from 'react';
import { Icons, fmt } from './PpobConstants';

export default function PpobCheckoutModal({
  isOpen,
  onClose,
  selectedProduct,
  currentTargetNumber,
  paymentMethod,
  setPaymentMethod,
  userBalance,
  isSubmitting,
  onConfirmPayment,
}) {
  if (!isOpen || !selectedProduct) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/75 backdrop-blur-md animate-fade-in overflow-y-auto">
      <div className="bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-4 sm:p-7 shadow-2xl relative overflow-y-auto max-h-[92dvh] space-y-4 sm:space-y-5">
        {/* Header Modal */}
        <div className="flex items-center justify-between pb-3.5 border-b border-gray-100 dark:border-white/5">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
              <Icons.Lightning className="w-5 h-5" />
            </div>
            <div>
              <h3 className="font-black text-base text-gray-900 dark:text-white leading-tight">Konfirmasi Pembelian</h3>
              <p className="text-[11px] font-semibold text-gray-400 mt-0.5">Periksa tujuan dan metode bayar Anda</p>
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            disabled={isSubmitting}
            className="w-8 h-8 rounded-full bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center justify-center font-black text-sm transition cursor-pointer"
          >
            ✕
          </button>
        </div>

        {/* RINGKASAN PRODUK */}
        <div className="p-4 rounded-2xl bg-gray-50 dark:bg-white/[0.03] border border-gray-200/80 dark:border-white/5 space-y-2.5 text-xs">
          <div className="flex justify-between items-start gap-2">
            <span className="text-gray-400 shrink-0 font-medium">Layanan / Produk:</span>
            <span className="font-black text-gray-900 dark:text-white text-right leading-snug">{selectedProduct.name}</span>
          </div>
          <div className="flex justify-between items-center gap-2">
            <span className="text-gray-400 font-medium">Nomor / ID Tujuan:</span>
            <span className="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 font-mono font-black text-emerald-600 dark:text-emerald-400 text-xs tracking-wider">
              {currentTargetNumber}
            </span>
          </div>
          <div className="flex justify-between items-center pt-2.5 border-t border-gray-200 dark:border-white/5">
            <span className="font-black text-gray-900 dark:text-white text-xs uppercase tracking-wider">Total Tagihan:</span>
            <span className="font-black font-mono text-lg text-emerald-600 dark:text-emerald-400">
              {fmt.rp(selectedProduct.price)}
            </span>
          </div>
        </div>

        {/* METODE PEMBAYARAN */}
        <div className="space-y-2.5">
          <div className="flex items-center justify-between">
            <label className="block text-xs font-black text-gray-500 uppercase tracking-wider">
              Metode Pembayaran:
            </label>
            <span className="text-[10px] font-bold text-gray-400">Pilih salah satu</span>
          </div>

          {/* Opsi 1: Saldo Akun */}
          <label
            onClick={() => setPaymentMethod('balance')}
            className={`p-3.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all ${
              paymentMethod === 'balance'
                ? 'bg-emerald-500/10 border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm'
                : 'bg-gray-50 dark:bg-white/[0.02] border-gray-200 dark:border-white/5 hover:border-emerald-500/40'
            }`}
          >
            <div className="flex items-center gap-3">
              <input
                type="radio"
                name="pay_method"
                value="balance"
                checked={paymentMethod === 'balance'}
                onChange={() => setPaymentMethod('balance')}
                className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300"
              />
              <div>
                <div className="flex items-center gap-1.5 flex-wrap">
                  <p className="font-black text-xs text-gray-900 dark:text-white">Saldo Akun PremiumKita</p>
                  <span className="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                    Instan 1 Detik
                  </span>
                </div>
                <p className="text-[11px] text-gray-400 mt-0.5">
                  Saldo Anda: <strong className="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{fmt.rp(userBalance)}</strong>
                  {userBalance >= selectedProduct.price && (
                    <span className="text-[10px] text-gray-400 font-normal ml-1">
                      (Sisa: {fmt.rp(userBalance - selectedProduct.price)})
                    </span>
                  )}
                </p>
              </div>
            </div>
            <div className="text-right shrink-0">
              {userBalance >= selectedProduct.price ? (
                <span className="text-[11px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-lg">
                  Bebas Biaya
                </span>
              ) : (
                <a
                  href="/profile"
                  target="_blank"
                  className="text-[10px] font-black text-rose-500 bg-rose-500/10 px-2 py-1 rounded-lg hover:underline"
                >
                  + Top Up Saldo
                </a>
              )}
            </div>
          </label>

          {/* Opsi 2: QRIS Otomatis */}
          <label
            onClick={() => setPaymentMethod('qris')}
            className={`p-3.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all ${
              paymentMethod === 'qris'
                ? 'bg-emerald-500/10 border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm'
                : 'bg-gray-50 dark:bg-white/[0.02] border-gray-200 dark:border-white/5 hover:border-emerald-500/40'
            }`}
          >
            <div className="flex items-center gap-3">
              <input
                type="radio"
                name="pay_method"
                value="qris"
                checked={paymentMethod === 'qris'}
                onChange={() => setPaymentMethod('qris')}
                className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300"
              />
              <div>
                <div className="flex items-center gap-1.5 flex-wrap">
                  <p className="font-black text-xs text-gray-900 dark:text-white">QRIS Instan (Semua Bank &amp; E-Wallet)</p>
                  <span className="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                    Otomatis
                  </span>
                </div>
                <p className="text-[11px] text-gray-400 mt-0.5">BCA, Mandiri, BRI, BNI, DANA, GoPay, OVO, ShopeePay</p>
              </div>
            </div>
            <div className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
              <Icons.CreditCard className="w-4 h-4" />
            </div>
          </label>
        </div>

        {/* TOMBOL BAYAR SEKARANG PROFESIONAL (HIJAU KONSISTEN) */}
        <div className="pt-2 space-y-2.5">
          <button
            type="button"
            onClick={onConfirmPayment}
            disabled={isSubmitting || (paymentMethod === 'balance' && userBalance < selectedProduct.price)}
            className="w-full py-4 px-5 rounded-2xl font-black text-sm sm:text-base bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 border border-emerald-500/40 shadow-lg shadow-emerald-950/40 hover:scale-[1.01] active:scale-[0.99] transition-all duration-200 flex items-center justify-between cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div className="flex items-center gap-3">
              {isSubmitting ? (
                <svg className="w-5 h-5 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
              ) : (
                <div className="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-sm shadow-inner">
                  <Icons.Lightning className="w-4 h-4" />
                </div>
              )}
              <div className="text-left">
                <p className="leading-tight text-xs sm:text-sm font-black tracking-tight">
                  {isSubmitting
                    ? 'Memproses Transaksi...'
                    : paymentMethod === 'balance'
                    ? 'Bayar Sekarang via Saldo'
                    : 'Lanjut Pembayaran via QRIS'}
                </p>
                <p className="text-[10px] font-medium opacity-85 leading-tight mt-0.5">
                  {paymentMethod === 'balance'
                    ? 'Proses instan 1 detik langsung masuk otomatis'
                    : 'Generate QRIS otomatis & konfirmasi realtime'}
                </p>
              </div>
            </div>

            <div className="px-3 py-1.5 rounded-xl bg-black/20 backdrop-blur-sm font-mono font-black text-xs sm:text-sm shrink-0">
              {fmt.rp(selectedProduct.price)}
            </div>
          </button>

          <button
            type="button"
            onClick={onClose}
            disabled={isSubmitting}
            className="w-full py-2.5 rounded-xl text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition text-center cursor-pointer"
          >
            Batal / Pilih Produk Lain
          </button>

          <div className="flex items-center justify-center gap-1.5 text-[11px] font-semibold text-gray-400 pt-1">
            <Icons.Shield className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
            <span>Transaksi Terlindungi SSL 256-Bit • Sistem Otomatis Terverifikasi</span>
          </div>
        </div>
      </div>
    </div>
  );
}

