import React from 'react';
import { Icons } from './PpobConstants';

export default function PpobSuccessModal({
  successOrder,
  onClose,
  copyToClipboard,
  isCopied,
}) {
  if (!successOrder) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-6 text-center shadow-2xl space-y-5">
        <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-green-500 text-white flex items-center justify-center font-black text-2xl mx-auto shadow-lg shadow-emerald-500/30">
          <Icons.Check className="w-8 h-8" />
        </div>

        <div>
          <h3 className="text-xl font-black text-gray-900 dark:text-white">Pembayaran Berhasil!</h3>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Layanan PPOB Anda telah berhasil diproses secara otomatis.</p>
        </div>

        {successOrder.invoice_number && (
          <div className="py-2 px-3 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-200 dark:border-white/5 inline-flex items-center gap-2 text-xs">
            <span className="text-gray-400 font-medium">No. Invoice:</span>
            <span className="font-mono font-bold text-gray-800 dark:text-gray-200">{successOrder.invoice_number}</span>
          </div>
        )}

        {successOrder.sn_or_account && (
          <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-left space-y-1.5">
            <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
              Nomor Seri (SN) / Kode Token Pengisian:
            </p>
            <div className="flex items-center justify-between gap-2">
              <span className="font-mono font-black text-sm text-gray-900 dark:text-white break-all">
                {successOrder.sn_or_account}
              </span>
              <button
                type="button"
                onClick={() => copyToClipboard(successOrder.sn_or_account)}
                className="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shrink-0 cursor-pointer transition"
              >
                {isCopied ? 'Tersalin' : 'Salin'}
              </button>
            </div>
          </div>
        )}

        <div className="space-y-2.5 pt-1">
          <a
            href="/profile?tab=orders"
            className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 font-black text-xs sm:text-sm shadow-md shadow-emerald-950/40 border border-emerald-500/30 flex items-center justify-center gap-2 transition hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
          >
            <span>Lihat Riwayat Transaksi &amp; Cetak Struk</span>
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
            </svg>
          </a>

          <div className="flex gap-2">
            <a
              href={successOrder.redirect_url || `/order/invoice/${successOrder.invoice_number}`}
              target="_blank"
              rel="noopener noreferrer"
              className="flex-1 py-2.5 px-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-800 dark:text-gray-200 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              <span>Lihat Invoice Resmi</span>
            </a>
            <button
              type="button"
              onClick={onClose}
              className="py-2.5 px-4 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-500 dark:text-gray-400 font-bold text-xs transition cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
