import React, { useState, useEffect } from 'react';
import { Icons, fmt } from './PpobConstants';

export default function PpobQrisModal({
  qrisData,
  onClose,
  qrisTimer,
  copyToClipboard,
  isCopied,
  onManualCheck,
  isCheckingQris = false,
}) {
  const [imgLoaded, setImgLoaded] = useState(false);
  const [cooldown, setCooldown] = useState(0);

  useEffect(() => {
    setImgLoaded(false);
    setCooldown(0);
  }, [qrisData?.url_qris]);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setInterval(() => {
      setCooldown((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(t);
  }, [cooldown]);

  const handleManualCheckClick = () => {
    if (cooldown > 0 || isCheckingQris || !onManualCheck) return;
    setCooldown(100);
    onManualCheck();
  };

  if (!qrisData) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/80 backdrop-blur-md animate-fade-in overflow-y-auto">
      <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-sm p-5 sm:p-6 text-center shadow-2xl space-y-4 max-h-[92dvh] overflow-y-auto">
        <div>
          <span className="text-[10px] font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/40">
            Menunggu Pembayaran QRIS (20 Menit)
          </span>
          <h3 className="text-base font-black text-gray-900 dark:text-white mt-2">Scan QRIS untuk Membayar</h3>
          <p className="text-xs text-gray-400">Selesaikan sesuai nominal unik sebelum waktu berakhir</p>
          {(qrisData.invoice_number || qrisData.order_id) && (
            <div className="mt-2 inline-flex flex-wrap items-center justify-center gap-1.5 px-3 py-1 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-[11px] font-mono font-bold text-gray-700 dark:text-gray-300">
              <span>ID Transaksi: #{qrisData.invoice_number || qrisData.order_id}</span>
              {qrisData.customer_id && <span>• Tujuan: {qrisData.customer_id}</span>}
            </div>
          )}
        </div>

        {qrisData.url_qris && (
          <div className="p-3 bg-white rounded-2xl border inline-block shadow-inner relative min-w-[208px] min-h-[208px]">
            {!imgLoaded && (
              <div className="w-52 h-52 flex flex-col items-center justify-center bg-slate-50 rounded-xl animate-pulse">
                <div className="w-10 h-10 rounded-full border-4 border-emerald-200 border-t-emerald-500 animate-spin mb-2"></div>
                <p className="text-xs font-black text-slate-700">Menyiapkan QRIS...</p>
                <p className="text-[10px] font-semibold text-slate-400 mt-0.5">Memuat kode QR pembayaran</p>
              </div>
            )}
            <img
              src={qrisData.url_qris}
              alt="QRIS"
              onLoad={() => setImgLoaded(true)}
              onError={() => setImgLoaded(true)}
              className={`w-52 h-52 mx-auto object-contain transition-opacity duration-300 ${imgLoaded ? 'opacity-100' : 'opacity-0 absolute inset-3'}`}
            />
          </div>
        )}

        <div>
          <p className="text-[10px] text-gray-400 uppercase font-bold">Total Pembayaran:</p>
          <div className="flex items-center justify-center gap-2">
            <span className="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">
              {fmt.rp(qrisData.total_nominal)}
            </span>
            <button
              type="button"
              onClick={() => copyToClipboard(qrisData.total_nominal)}
              className="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-white/10 text-xs font-bold hover:bg-emerald-500 hover:text-white transition"
            >
              {isCopied ? 'Tersalin' : 'Salin'}
            </button>
          </div>
          <p className="text-[11px] font-bold text-amber-500 mt-1 flex items-center justify-center gap-1">
            <Icons.Clock className="w-3.5 h-3.5" />
            <span>
              Waktu tersisa (20 Menit): {Math.floor(qrisTimer / 60)}:{(qrisTimer % 60).toString().padStart(2, '0')}
            </span>
          </p>
          <p className="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 mt-1.5 bg-emerald-500/10 border border-emerald-500/20 rounded-xl px-2.5 py-1.5">
            Aman direfresh: Jika halaman ter-refresh, transaksi ini tetap tersimpan &amp; dicek otomatis di latar belakang tanpa perlu order ulang.
          </p>
        </div>

        {/* TOMBOL CEK PEMBAYARAN & INVOICE YANG MUDAH TERLIHAT DI HP */}
        <div className="space-y-2 pt-1">
          {onManualCheck && (
            <button
              type="button"
              onClick={handleManualCheckClick}
              disabled={isCheckingQris || cooldown > 0}
              className="w-full py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-black text-xs shadow-lg shadow-emerald-500/25 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {isCheckingQris ? (
                <>
                  <span className="w-3.5 h-3.5 rounded-full border-2 border-white border-t-transparent animate-spin"></span>
                  <span>Mengecek Status Pembayaran...</span>
                </>
              ) : cooldown > 0 ? (
                <span>⏱️ Cek Pembayaran ({cooldown}s)</span>
              ) : (
                <span>🔄 Sudah Bayar? Cek Pembayaran Sekarang</span>
              )}
            </button>
          )}

          {qrisData.invoice_url && (
            <a
              href={qrisData.invoice_url}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full py-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-700 dark:text-emerald-300 font-bold text-xs hover:bg-emerald-100 transition flex items-center justify-center gap-1.5"
            >
              <span>📄 Buka &amp; Simpan Halaman Invoice</span>
            </a>
          )}

          <button
            type="button"
            onClick={onClose}
            className="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-200 transition"
          >
            Tutup / Batalkan
          </button>
        </div>
      </div>
    </div>
  );
}

