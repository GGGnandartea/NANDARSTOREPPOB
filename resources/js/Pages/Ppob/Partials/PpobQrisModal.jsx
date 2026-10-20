import React from 'react';
import { Icons, fmt } from './PpobConstants';

export default function PpobQrisModal({
  qrisData,
  onClose,
  qrisTimer,
  copyToClipboard,
  isCopied,
}) {
  if (!qrisData) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-sm p-6 text-center shadow-2xl space-y-4">
        <div>
          <span className="text-[10px] font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/40">
            Menunggu Pembayaran QRIS
          </span>
          <h3 className="text-base font-black text-gray-900 dark:text-white mt-2">Scan QRIS untuk Membayar</h3>
          <p className="text-xs text-gray-400">Selesaikan sebelum waktu berakhir</p>
        </div>

        {qrisData.invoice_url && (
          <div className="text-xs text-gray-500">
            <a href={qrisData.invoice_url} target="_blank" rel="noopener noreferrer" className="font-bold text-emerald-600 underline">
              Buka dan simpan tautan invoice
            </a>
            <p className="mt-1">Simpan tautan ini secara pribadi untuk mengakses pesanan tamu.</p>
          </div>
        )}

        {qrisData.url_qris && (
          <div className="p-3 bg-white rounded-2xl border inline-block shadow-inner">
            <img src={qrisData.url_qris} alt="QRIS" className="w-52 h-52 mx-auto object-contain" />
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
              Waktu tersisa: {Math.floor(qrisTimer / 60)}:{(qrisTimer % 60).toString().padStart(2, '0')}
            </span>
          </p>
        </div>

        <button
          type="button"
          onClick={onClose}
          className="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-200 transition"
        >
          Batalkan
        </button>
      </div>
    </div>
  );
}

