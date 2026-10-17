import React from 'react';

export default function CheckoutQrisSection({
  manualCheckPayment,
  cooldown,
  isCheckingManual,
  qrisTimeLeft,
  formatTimer,
  totalNominal,
  orderId,
  qrisUrl,
  handleCancel,
  fmt,
}) {
  return (
    <div className="text-center">
      {/* TOMBOL CEK PEMBAYARAN DI BAGIAN ATAS DENGAN COOLDOWN 100 DETIK */}
      <div className="mb-4">
        <button
          type="button"
          onClick={manualCheckPayment}
          disabled={cooldown > 0 || isCheckingManual}
          className={`w-full py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 transition shadow-lg ${
            cooldown > 0 || isCheckingManual
              ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 cursor-not-allowed select-none'
              : 'bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 text-white shadow-orange-500/25 active:scale-[0.98] cursor-pointer'
          }`}
        >
          {isCheckingManual ? (
            <>
              <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <span>Sedang Mengecek Status...</span>
            </>
          ) : cooldown > 0 ? (
            <>
              <svg className="w-4 h-4 animate-spin text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>⏱️ Cek Pembayaran ({cooldown}s)</span>
            </>
          ) : (
            <>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              <span>🔄 Cek Pembayaran Sekarang</span>
            </>
          )}
        </button>
      </div>

      {/* WARNING 5 MENIT */}
      <div className="mb-5 bg-gradient-to-r from-red-500/15 via-amber-500/15 to-red-500/15 border-2 border-red-500/30 dark:border-red-500/40 rounded-2xl p-4 text-left shadow-md">
        <div className="flex items-start gap-3">
          <div className="w-9 h-9 rounded-xl bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0 font-black text-lg animate-bounce">
            ⚠️
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-[11px] font-black text-red-600 dark:text-red-400 tracking-wide uppercase leading-tight">
              PERHATIAN: QRIS INI BERLAKU SELAMA 5 MENIT SAJA
            </p>
            <p className="text-[11px] font-bold text-gray-700 dark:text-gray-200 mt-0.5 leading-snug">
              MOHON SEGERA LAKUKAN PEMBAYARAN SEBELUM KEDALUWARSA!
            </p>
            <div className="mt-2 flex items-center gap-2">
              <span className="text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Batas Waktu Bayar:
              </span>
              <span
                className={`font-mono text-xs font-black px-2.5 py-0.5 rounded-lg border flex items-center gap-1.5 ${
                  qrisTimeLeft < 60
                    ? 'bg-red-500 text-white border-red-600 animate-pulse'
                    : 'bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800/60'
                }`}
              >
                <span>⏱️</span>
                {formatTimer(qrisTimeLeft)}
              </span>
            </div>
          </div>
        </div>
      </div>

      <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Pembayaran</p>
      <p className="text-4xl font-black text-gray-900 dark:text-white mb-4">{fmt.rp(totalNominal)}</p>
      <p className="text-[10px] text-orange-600 font-bold bg-orange-50 dark:bg-orange-900/20 py-1.5 px-3 rounded-lg inline-block mb-5 border border-orange-100 dark:border-orange-800">
        ⚠️ Scan QRIS menggunakan E-Wallet / M-Banking Anda.
      </p>
      <div className="inline-flex items-center gap-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold py-2 px-4 rounded-xl mb-5">
        <span>No. Pesanan:</span>
        <span className="text-orange-500 select-all font-mono">{orderId}</span>
      </div>
      <div className="bg-white dark:bg-gray-800 p-4 rounded-[2rem] border-2 border-dashed border-gray-200 dark:border-gray-600 inline-block mb-6">
        <div className="w-56 h-56 sm:w-60 sm:h-60 overflow-hidden rounded-2xl mx-auto bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
          {qrisUrl ? (
            <img src={qrisUrl} alt="QRIS Code" className="w-full h-full object-contain" />
          ) : (
            <span className="text-gray-400 font-bold text-xs">Memuat QRIS...</span>
          )}
        </div>
      </div>
      <div className="flex items-center justify-center gap-2 text-orange-500 font-bold text-xs animate-pulse bg-orange-50 dark:bg-orange-900/20 py-3 rounded-2xl mb-4">
        <div className="w-2 h-2 rounded-full bg-orange-500"></div>Menunggu Pembayaran Anda...
      </div>
      <button
        type="button"
        onClick={handleCancel}
        className="text-gray-400 hover:text-red-500 text-xs font-bold transition cursor-pointer w-full py-2"
      >
        Batalkan Pesanan
      </button>
    </div>
  );
}
