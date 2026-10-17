import React from 'react';
import { Link } from '@inertiajs/react';

export default function CheckoutSuccessSection({
  isAccountPending,
  completedOrder,
  balanceSuccess,
  orderId,
  handleCopyAccount,
  copied,
  product,
  finalPrice,
  user,
  waSentSuccess,
  handleResendWa,
  isResendingWa,
  fmt,
}) {
  const accountText = completedOrder?.sn_or_account || balanceSuccess?.sn_or_account;
  const currentOrderId = completedOrder?.order_id || balanceSuccess?.order_id || orderId;
  const productName = completedOrder?.product_name || product?.name;
  const totalAmount = completedOrder?.total_amount || balanceSuccess?.total_nominal || finalPrice;

  return (
    <div className="text-center py-2 space-y-4">
      {isAccountPending(accountText) ? (
        /* ─── STATE MENUNGGU AKUN (PROMINENT LARGE LOADING CARD) ─── */
        <div className="bg-gradient-to-br from-orange-500/10 via-amber-500/10 to-orange-600/5 border-2 border-orange-500/30 rounded-3xl p-6 sm:p-7 text-center relative overflow-hidden shadow-2xl space-y-4 animate-fade-in">
          {/* Glowing background circles */}
          <div className="absolute -top-10 -left-10 w-36 h-36 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
          <div className="absolute -bottom-10 -right-10 w-36 h-36 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>

          {/* Large Orbital Glowing Loader */}
          <div className="relative w-20 h-20 mx-auto flex items-center justify-center pt-1">
            <div className="absolute inset-0 rounded-full border-4 border-orange-500/20"></div>
            <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-orange-500 border-r-amber-400 animate-spin"></div>
            <div className="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-orange-500/40">
              ⚡
            </div>
          </div>

          {/* Requested Text: Big & Prominent */}
          <div className="space-y-1.5 relative z-10">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-[10px] font-black uppercase tracking-wider">
              <span className="w-2 h-2 rounded-full bg-orange-500 animate-ping"></span>
              Pembayaran Berhasil • Menyiapkan Lisensi
            </span>
            <h3 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white leading-tight">
              Akun sedang disiapkan, jangan tutup halaman ini.
            </h3>
            <p className="text-sm sm:text-base font-black text-orange-600 dark:text-orange-400 max-w-md mx-auto leading-relaxed animate-pulse">
              Akun akan muncul secara otomatis!!!
            </p>
          </div>

          {/* Animated Progress Bar */}
          <div className="max-w-xs mx-auto space-y-2 relative z-10">
            <div className="w-full h-2.5 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden relative shadow-inner">
              <div className="h-full w-1/2 bg-gradient-to-r from-orange-500 via-amber-400 to-orange-500 rounded-full animate-progress-slide"></div>
            </div>
            <p className="text-[11px] text-gray-400 dark:text-gray-500 font-medium">
              ⚡ Sedang memproses dan menyiapkan lisensi Anda secara otomatis...
            </p>
          </div>
        </div>
      ) : (
        /* ─── STATE AKUN SUDAH SIAP ─── */
        <>
          <div className="w-16 h-16 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center mx-auto border border-emerald-500/20 shadow-lg shadow-emerald-500/10">
            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <div>
            <h3 className="text-2xl font-black text-gray-900 dark:text-white">Hore, Pembayaran Berhasil!</h3>
            <p className="text-gray-500 dark:text-gray-400 text-xs mt-1">
              Pesanan telah lunas. Detail akun &amp; dokumen invoice Anda siap diakses di bawah ini.
            </p>
          </div>

          {/* KOTAK DETAIL AKUN & KREDENSIAL */}
          <div className="bg-[#0b1121] text-white p-5 rounded-3xl border border-gray-800 text-left shadow-xl relative overflow-hidden">
            <div className="flex items-center justify-between mb-2.5">
              <span className="text-[10px] font-black tracking-widest uppercase text-orange-400 flex items-center gap-1.5">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Detail Akun / Lisensi Anda
              </span>
              <button
                type="button"
                onClick={() => handleCopyAccount(accountText)}
                className="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg text-[10px] font-extrabold transition cursor-pointer flex items-center gap-1"
              >
                {copied ? '✓ Tersalin!' : '📋 Salin Akun'}
              </button>
            </div>

            <div className="bg-[#020617] border border-gray-800 rounded-2xl p-3.5 font-mono text-xs text-cyan-400 whitespace-pre-wrap break-all select-all leading-relaxed min-h-[60px] flex items-center">
              {accountText}
            </div>

            <p className="text-[10px] text-gray-400 mt-2 font-medium">
              *Simpan email &amp; password atau kode lisensi ini baik-baik.
            </p>
          </div>
        </>
      )}

      {/* RINGKASAN TRANSAKSI */}
      <div className="bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl text-left space-y-2 text-xs">
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-bold">No. Invoice</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white select-all">{currentOrderId}</span>
        </div>
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-bold">Layanan</span>
          <span className="font-bold text-gray-900 dark:text-white truncate max-w-[200px]">{productName}</span>
        </div>
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-bold">Metode Bayar</span>
          <span className="font-bold text-gray-900 dark:text-white">{balanceSuccess ? 'Saldo Akun' : 'QRIS Otomatis'}</span>
        </div>
        <div className="flex justify-between items-center py-1">
          <span className="text-gray-400 font-bold">Total Lunas</span>
          <span className="font-black text-orange-500 text-sm">{fmt.rp(totalAmount)}</span>
        </div>
      </div>

      {/* ACTION BUTTONS */}
      <div className="space-y-2.5 pt-1">
        <a
          href={`/order/invoice/${currentOrderId}`}
          target="_blank"
          rel="noreferrer"
          className="w-full bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black py-3.5 px-4 rounded-2xl shadow-xl shadow-orange-500/20 transition flex items-center justify-center gap-2 cursor-pointer text-sm"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Download / Cetak Invoice (PDF)
        </a>

        <Link
          href="/"
          className="block w-full bg-gray-900 dark:bg-gray-700 hover:bg-black dark:hover:bg-gray-600 text-white font-black py-3.5 rounded-2xl transition cursor-pointer text-xs"
        >
          Selesai &amp; Kembali Belanja
        </Link>
      </div>

      {/* INFO WHATSAPP NOTIFIKASI */}
      {user?.is_whatsapp_verified ? (
        <div className="bg-emerald-500/10 border border-emerald-500/25 p-4 rounded-2xl text-left flex items-start gap-3">
          <div className="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 font-bold text-sm">
            📱
          </div>
          <div className="flex-1">
            <div className="flex items-center justify-between">
              <p className="text-xs font-black text-emerald-600 dark:text-emerald-400">
                {waSentSuccess ? '✓ Terkirim ke WhatsApp!' : 'Detail Akun & Invoice ke WhatsApp'}
              </p>
              <span className="text-[9px] bg-emerald-500/20 text-emerald-500 font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                Otomatis
              </span>
            </div>
            <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
              Pesan otomatis berisi detail akun dan invoice resmi dikirimkan ke nomor WhatsApp terverifikasi:{' '}
              <strong className="text-gray-900 dark:text-white font-mono">{user.whatsapp}</strong>
            </p>
            <button
              type="button"
              onClick={() => handleResendWa(currentOrderId)}
              disabled={isResendingWa}
              className="mt-2.5 inline-flex items-center gap-1.5 text-xs font-black text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer disabled:opacity-50"
            >
              {isResendingWa ? (
                <>
                  <svg className="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <span>Mengirim ulang ke WhatsApp...</span>
                </>
              ) : (
                <span>📲 Kirim Ulang ke WhatsApp</span>
              )}
            </button>
          </div>
        </div>
      ) : !user ? (
        <div className="bg-amber-500/10 border border-amber-500/20 p-3.5 rounded-2xl text-left">
          <p className="text-[11px] text-amber-700 dark:text-amber-400 font-bold leading-relaxed">
            💡 <strong>Tips:</strong> Ingin struk pesanan &amp; detail akun otomatis dikirim ke WhatsApp?{' '}
            <Link href="/login" className="underline font-black text-orange-600 dark:text-orange-400">
              Daftar atau Login sekarang
            </Link>
          </p>
        </div>
      ) : (
        <div className="bg-amber-500/10 border border-amber-500/20 p-3.5 rounded-2xl text-left">
          <p className="text-[11px] text-amber-700 dark:text-amber-400 font-bold leading-relaxed">
            💡 <strong>Tips:</strong> Ingin struk pesanan &amp; detail akun otomatis dikirim ke WhatsApp Anda? Verifikasi nomor WhatsApp Anda di{' '}
            <Link href="/profile" className="underline font-black">
              Menu Profil
            </Link>.
          </p>
        </div>
      )}
    </div>
  );
}
