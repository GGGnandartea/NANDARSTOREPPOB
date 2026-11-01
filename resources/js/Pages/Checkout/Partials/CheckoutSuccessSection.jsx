import React, { useState } from 'react';
import { Link } from '@inertiajs/react';

export default function CheckoutSuccessSection({
  isAccountPending,
  completedOrder,
  balanceSuccess,
  orderId,
  invoiceUrl,
  handleCopyAccount,
  copied,
  product,
  quantity = 1,
  finalPrice,
  user,
  buyerName = '',
  waSentSuccess,
  handleResendWa,
  isResendingWa,
  fmt,
}) {
  const rawAccountText = completedOrder?.sn_or_account || balanceSuccess?.sn_or_account;
  const accountText =
    typeof rawAccountText === 'object' && rawAccountText !== null
      ? JSON.stringify(rawAccountText, null, 2)
      : String(rawAccountText || '');
  const currentOrderId = completedOrder?.order_id || balanceSuccess?.order_id || orderId;
  const productName = completedOrder?.product_name || product?.name;
  const totalAmount = completedOrder?.total_amount || balanceSuccess?.total_nominal || finalPrice;
  const displayQty = completedOrder?.quantity || balanceSuccess?.quantity || quantity;

  const isManualAdminProduct = Boolean(
    (product?.type === 'local' && product?.process_type === 'manual') ||
      completedOrder?.provider === 'Manual Admin' ||
      balanceSuccess?.provider === 'Manual Admin' ||
      completedOrder?.is_manual_admin ||
      balanceSuccess?.is_manual_admin ||
      accountText.toLowerCase().includes('wajib mengisi form garansi')
  );

  const [showManualPopup, setShowManualPopup] = useState(true);

  const displayBuyerName =
    completedOrder?.buyer_name ||
    balanceSuccess?.buyer_name ||
    buyerName ||
    user?.name ||
    'Pembeli Publik';

  const displayBuyerRole =
    completedOrder?.buyer_category ||
    balanceSuccess?.buyer_category ||
    (user ? (String(user.role || '').toLowerCase() === 'reseller' ? 'RESELLER' : 'USER BIASA') : 'PUBLIK');

  const displayDate =
    completedOrder?.created_at ||
    balanceSuccess?.created_at ||
    new Date().toLocaleString('id-ID', {
      timeZone: 'Asia/Jakarta',
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });

  const adminWaNumber = '6282317458496';
  const waClaimMessage = encodeURIComponent(
    `Halo Admin PremiumKita, saya sudah menyelesaikan pembayaran untuk produk Manual Admin.\n\n` +
      `*BUKTI PESANAN SAYA:*\n` +
      `• No. Invoice: #${currentOrderId}\n` +
      `• Nama Produk: ${productName}\n` +
      `• Jumlah Pesanan: ${displayQty} Pcs\n` +
      `• Total Bayar: ${fmt.rp(totalAmount)}\n` +
      `• Username Pembeli: ${displayBuyerName} (${displayBuyerRole})\n` +
      `• Tanggal: ${displayDate} WIB\n\n` +
      `Mohon kirimkan *Form Garansi* dan *Akun untuk Login* pesanan saya (screenshot bukti terlampir). Terima kasih!`
  );
  const waClaimUrl = `https://wa.me/${adminWaNumber}?text=${waClaimMessage}`;

  const isPendingText =
    isAccountPending(accountText) ||
    accountText.toLowerCase().includes('wajib mengisi form garansi') ||
    accountText.toLowerCase().includes('meminta form garansi');

  return (
    <div className="text-center py-2 space-y-4">
      {/* ─── POPUP MODAL OTOMATIS KHUSUS PRODUK MANUAL ADMIN ─── */}
      {isManualAdminProduct && isPendingText && showManualPopup && (
        <div className="fixed inset-0 z-[999] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in">
          <div className="w-full max-w-md bg-white dark:bg-[#0f172a] border-2 border-orange-500/50 rounded-3xl p-5 sm:p-6 shadow-2xl text-center space-y-4 relative overflow-hidden">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white flex items-center justify-center mx-auto shadow-lg shadow-orange-500/30">
              <svg className="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2.5"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                />
              </svg>
            </div>

            <div className="space-y-2">
              <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-[11px] font-black uppercase tracking-wider">
                ✓ Pembayaran Berhasil • Wajib Klaim Garansi
              </span>
              <div className="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border-2 border-amber-400/60 dark:border-amber-500/40">
                <p className="text-xs sm:text-sm font-black text-amber-900 dark:text-amber-200 leading-relaxed uppercase">
                  PRODUK INI WAJIB MENGISI FORM GARANSI, SILAHKAN SCREENSHOT INI DAN CHAT ADMIN UNTUK MEMINTA FORM GARANSI DAN AKUN UNTUK LOGIN
                </p>
              </div>
            </div>

            {/* RINGKASAN BUKTI UNTUK DI-SCREENSHOT */}
            <div className="bg-gray-50 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-3.5 text-left space-y-1.5 text-xs">
              <div className="flex justify-between items-center py-0.5 border-b border-gray-200/60 dark:border-gray-700/60">
                <span className="text-gray-400 font-semibold">No. Invoice</span>
                <span className="font-mono font-black text-gray-900 dark:text-white">#{currentOrderId}</span>
              </div>
              <div className="flex justify-between items-center py-0.5 border-b border-gray-200/60 dark:border-gray-700/60">
                <span className="text-gray-400 font-semibold">Produk</span>
                <span className="font-black text-gray-900 dark:text-white truncate max-w-[190px]">{productName}</span>
              </div>
              <div className="flex justify-between items-center py-0.5 border-b border-gray-200/60 dark:border-gray-700/60">
                <span className="text-gray-400 font-semibold">Pembeli</span>
                <span className="font-bold text-gray-900 dark:text-white">
                  {displayBuyerName} <span className="text-[10px] text-orange-500 font-black">({displayBuyerRole})</span>
                </span>
              </div>
              <div className="flex justify-between items-center py-0.5 border-b border-gray-200/60 dark:border-gray-700/60">
                <span className="text-gray-400 font-semibold">Jumlah Pesanan</span>
                <span className="font-black text-orange-500">{displayQty} Pcs</span>
              </div>
              <div className="flex justify-between items-center py-0.5 border-b border-gray-200/60 dark:border-gray-700/60">
                <span className="text-gray-400 font-semibold">Total Lunas</span>
                <span className="font-black text-emerald-600 dark:text-emerald-400">{fmt.rp(totalAmount)}</span>
              </div>
              <div className="flex justify-between items-center py-0.5">
                <span className="text-gray-400 font-semibold">Tanggal</span>
                <span className="font-bold text-gray-700 dark:text-gray-300">{displayDate} WIB</span>
              </div>
            </div>

            <div className="space-y-2 pt-1">
              <a
                href={waClaimUrl}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs sm:text-sm shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>💬 Chat Admin Sekarang (Minta Form Garansi &amp; Akun)</span>
              </a>
              <button
                type="button"
                onClick={() => setShowManualPopup(false)}
                className="w-full py-2.5 px-4 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs transition cursor-pointer"
              >
                Sudah Screenshot — Lihat Detail Halaman Ini
              </button>
            </div>
          </div>
        </div>
      )}

      {isManualAdminProduct && isPendingText ? (
        /* ─── STATE KHUSUS PRODUK MANUAL ADMIN (WAJIB ISI FORM GARANSI & CHAT ADMIN) ─── */
        <div className="bg-gradient-to-br from-amber-500/15 via-orange-500/10 to-amber-600/5 border-2 border-amber-500/40 rounded-3xl p-5 sm:p-6 text-center relative overflow-hidden shadow-xl space-y-4 animate-fade-in">
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white flex items-center justify-center mx-auto shadow-lg shadow-orange-500/30">
            <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2.5"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
          </div>

          <div className="space-y-2 relative z-10">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-[11px] font-black uppercase tracking-wider">
              ✓ Pembayaran Lunas • Layanan Manual Admin
            </span>
            <div className="p-3.5 rounded-2xl bg-white/90 dark:bg-gray-900/90 border border-amber-400/50 shadow-sm">
              <p className="text-xs sm:text-sm font-black text-amber-900 dark:text-amber-300 leading-relaxed uppercase">
                PRODUK INI WAJIB MENGISI FORM GARANSI, SILAHKAN SCREENSHOT INI DAN CHAT ADMIN UNTUK MEMINTA FORM GARANSI DAN AKUN UNTUK LOGIN
              </p>
            </div>
          </div>

          <div className="space-y-2 relative z-10">
            <a
              href={waClaimUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs sm:text-sm shadow-lg shadow-emerald-600/25 transition flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>💬 Chat Admin Sekarang (Minta Form Garansi &amp; Akun)</span>
            </a>
            <button
              type="button"
              onClick={() => setShowManualPopup(true)}
              className="w-full py-2 px-3 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 font-bold text-xs transition cursor-pointer"
            >
              📸 Tampilkan Kembali Popup Bukti Screenshot
            </button>
          </div>
        </div>
      ) : isPendingText ? (
        /* ─── STATE MENUNGGU AKUN OTOMATIS API (PROMINENT LARGE LOADING CARD) ─── */
        <div className="bg-gradient-to-br from-orange-500/10 via-amber-500/10 to-orange-600/5 border-2 border-orange-500/30 rounded-3xl p-6 sm:p-7 text-center relative overflow-hidden shadow-2xl space-y-4 animate-fade-in">
          {/* Glowing background circles */}
          <div className="absolute -top-10 -left-10 w-36 h-36 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
          <div className="absolute -bottom-10 -right-10 w-36 h-36 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>

          {/* Large Orbital Glowing Loader */}
          <div className="relative w-20 h-20 mx-auto flex items-center justify-center pt-1">
            <div className="absolute inset-0 rounded-full border-4 border-orange-500/20"></div>
            <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-orange-500 border-r-amber-400 animate-spin"></div>
            <div className="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-orange-500/40">
              <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
          </div>

          {/* Requested Text: Big & Prominent */}
          <div className="space-y-1.5 relative z-10">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-[11px] font-black uppercase tracking-wider">
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
            <p className="text-[11px] text-gray-400 dark:text-gray-300 font-medium">
              Sedang memproses dan menyiapkan lisensi Anda secara otomatis...
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
            <p className="text-gray-500 dark:text-gray-300 text-xs mt-1">
              Pesanan telah lunas. Detail akun &amp; dokumen invoice Anda siap diakses di bawah ini.
            </p>
          </div>

          {/* KOTAK DETAIL AKUN & KREDENSIAL */}
          <div className="bg-[#0b1121] text-white p-5 rounded-3xl border border-gray-800 text-left shadow-xl relative overflow-hidden">
            <div className="flex items-center justify-between mb-2.5">
              <span className="text-[11px] font-black tracking-widest uppercase text-orange-400 flex items-center gap-1.5">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Detail Akun / Lisensi Anda
              </span>
              <button
                type="button"
                onClick={() => handleCopyAccount(accountText)}
                className="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg text-[11px] font-extrabold transition cursor-pointer flex items-center gap-1"
              >
                {copied ? '✓ Tersalin!' : '📋 Salin Akun'}
              </button>
            </div>

            <div className="bg-[#020617] border border-gray-800 rounded-2xl p-3.5 font-mono text-xs text-cyan-400 whitespace-pre-wrap break-all select-all leading-relaxed min-h-[60px] flex items-center">
              {accountText}
            </div>

            <p className="text-[11px] text-gray-400 mt-2 font-medium">
              *Simpan email &amp; password atau kode lisensi ini baik-baik.
            </p>
          </div>
        </>
      )}

      {/* RINGKASAN TRANSAKSI */}
      <div className="bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl text-left space-y-2 text-xs">
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-medium">No. Invoice</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white select-all">{currentOrderId}</span>
        </div>
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-medium">Layanan</span>
          <span className="font-bold text-gray-900 dark:text-white truncate max-w-[200px]">{productName}</span>
        </div>
        {displayQty > 1 && (
          <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
            <span className="text-gray-400 font-medium">Jumlah Pembelian</span>
            <span className="font-black text-orange-500 px-2 py-0.5 rounded-lg bg-orange-500/10 border border-orange-500/20">{displayQty} pcs</span>
          </div>
        )}
        <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
          <span className="text-gray-400 font-medium">Metode Bayar</span>
          <span className="font-bold text-gray-900 dark:text-white">{balanceSuccess ? 'Saldo Akun' : 'QRIS Otomatis'}</span>
        </div>
        <div className="flex justify-between items-center py-1">
          <span className="text-gray-400 font-medium">Total Lunas</span>
          <span className="font-black text-orange-500 text-sm">{fmt.rp(totalAmount)}</span>
        </div>
      </div>

      {/* ACTION BUTTONS */}
      <div className="space-y-2.5 pt-1">
        <a
          href={completedOrder?.invoice_url || balanceSuccess?.invoice_url || invoiceUrl || `/order/invoice/${currentOrderId}`}
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
              <span className="text-[11px] bg-emerald-500/20 text-emerald-500 font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                Otomatis
              </span>
            </div>
            <p className="text-[11px] text-gray-500 dark:text-gray-300 mt-1">
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
