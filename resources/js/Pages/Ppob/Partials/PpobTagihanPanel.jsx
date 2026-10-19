import React from 'react';
import { Icons, fmt } from './PpobConstants';
import { extractBillRows } from './PpobBillUtils';

/**
 * Panel kolom kanan untuk tab Tagihan & PDAM.
 * Menampilkan rincian hasil inquiry dan tombol lanjut bayar.
 */
export default function PpobTagihanPanel({ inquiryResult, isInquiring, onOpenCheckout, serviceLabel }) {
  const summary = inquiryResult?.summary;
  const rows = extractBillRows(inquiryResult?.data);
  const hasBill = summary && summary.grandTotal > 0;

  if (isInquiring) {
    return (
      <div className="lg:col-span-7">
        <div className="p-10 text-center rounded-3xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 space-y-3">
          <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
          <p className="text-xs font-bold text-gray-400">Menghubungi server biller untuk cek tagihan...</p>
        </div>
      </div>
    );
  }

  if (!hasBill) {
    return (
      <div className="lg:col-span-7">
        <div className="p-10 text-center rounded-3xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 space-y-3">
          <div className="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto text-gray-400">
            <Icons.Tagihan className="w-6 h-6" />
          </div>
          <h4 className="font-black text-sm text-gray-800 dark:text-gray-200">Belum Ada Tagihan</h4>
          <p className="text-xs text-gray-400 max-w-sm mx-auto">
            Pilih jenis layanan dan masukkan nomor pelanggan di kolom sebelah, lalu tekan tombol cek tagihan.
            Rincian tagihan akan tampil di sini.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="lg:col-span-7 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-base font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>Rincian Tagihan</span>
            {summary.billCount > 0 && (
              <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                {summary.billCount} Periode
              </span>
            )}
          </h3>
          <p className="text-xs text-gray-400 mt-0.5">{serviceLabel}</p>
        </div>
      </div>

      {/* DATA PELANGGAN */}
      <div className="p-4 sm:p-5 rounded-2xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 space-y-2.5">
        <div className="flex items-start justify-between gap-3 text-xs">
          <span className="text-gray-400 font-medium shrink-0">Nama Pelanggan</span>
          <span className="font-black text-gray-900 dark:text-white text-right">{summary.customerName}</span>
        </div>
        <div className="flex items-start justify-between gap-3 text-xs">
          <span className="text-gray-400 font-medium shrink-0">Nomor Pelanggan</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white text-right">{summary.customerId}</span>
        </div>
        {summary.address && (
          <div className="flex items-start justify-between gap-3 text-xs">
            <span className="text-gray-400 font-medium shrink-0">Alamat</span>
            <span className="font-semibold text-gray-700 dark:text-gray-300 text-right">{summary.address}</span>
          </div>
        )}
        {summary.branch && (
          <div className="flex items-start justify-between gap-3 text-xs">
            <span className="text-gray-400 font-medium shrink-0">Cabang</span>
            <span className="font-semibold text-gray-700 dark:text-gray-300 text-right">{summary.branch}</span>
          </div>
        )}
        {summary.transactionType && (
          <div className="flex items-start justify-between gap-3 text-xs">
            <span className="text-gray-400 font-medium shrink-0">Jenis Transaksi</span>
            <span className="font-semibold text-gray-700 dark:text-gray-300 text-right">{summary.transactionType}</span>
          </div>
        )}
        {summary.tariff && (
          <div className="flex items-start justify-between gap-3 text-xs">
            <span className="text-gray-400 font-medium shrink-0">Tarif / Daya</span>
            <span className="font-semibold text-gray-700 dark:text-gray-300 text-right">
              {summary.tariff}{summary.power ? ` / ${summary.power} VA` : ''}
            </span>
          </div>
        )}
      </div>

      {/* RINCIAN PER PERIODE */}
      {rows.length > 0 && (
        <div className="rounded-2xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 dark:border-white/5">
            <h4 className="text-[11px] font-black uppercase tracking-wider text-gray-500">Rincian Tagihan Per Periode</h4>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-xs">
              <thead>
                <tr className="bg-gray-50 dark:bg-white/[0.03] text-gray-500">
                  <th className="text-left font-black uppercase tracking-wider px-4 py-2.5">Periode</th>
                  <th className="text-right font-black uppercase tracking-wider px-3 py-2.5">Pemakaian</th>
                  <th className="text-right font-black uppercase tracking-wider px-3 py-2.5">Denda</th>
                  <th className="text-right font-black uppercase tracking-wider px-4 py-2.5">Tagihan</th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row, idx) => (
                  <tr key={`${row.periode}-${idx}`} className="border-t border-gray-100 dark:border-white/5">
                    <td className="px-4 py-2.5 font-bold text-gray-900 dark:text-white">
                      {row.periode || '-'}
                      {row.meterAwal && row.meterAkhir && (
                        <span className="block text-[10px] text-gray-400 font-medium">
                          {row.meterAwal} - {row.meterAkhir}
                        </span>
                      )}
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono text-gray-700 dark:text-gray-300">
                      {row.pemakaian || '-'}
                    </td>
                    <td className="px-3 py-2.5 text-right font-mono text-gray-700 dark:text-gray-300">
                      {fmt.rp(row.denda)}
                    </td>
                    <td className="px-4 py-2.5 text-right font-mono font-bold text-gray-900 dark:text-white">
                      {fmt.rp(row.tagihan)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* RINGKASAN PEMBAYARAN */}
      <div className="p-4 sm:p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 space-y-2.5">
        <div className="flex justify-between text-xs">
          <span className="text-gray-600 dark:text-gray-400 font-medium">Tagihan</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(summary.amount)}</span>
        </div>
        <div className="flex justify-between text-xs">
          <span className="text-gray-600 dark:text-gray-400 font-medium">Denda</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(summary.penalty)}</span>
        </div>
        <div className="flex justify-between text-xs">
          <span className="text-gray-600 dark:text-gray-400 font-medium">Admin Bank</span>
          <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(summary.adminFee)}</span>
        </div>
        {summary.serviceFee > 0 && (
          <div className="flex justify-between text-xs">
            <span className="text-gray-600 dark:text-gray-400 font-medium">Biaya Layanan</span>
            <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(summary.serviceFee)}</span>
          </div>
        )}
        <div className="flex justify-between items-center pt-2.5 border-t border-emerald-300 dark:border-emerald-800/40">
          <span className="font-black text-emerald-800 dark:text-emerald-300 text-sm">Total Bayar</span>
          <span className="font-black font-mono text-lg text-emerald-600 dark:text-emerald-400">{fmt.rp(summary.grandTotal)}</span>
        </div>
      </div>

      <p className="flex items-center gap-1.5 text-[11px] font-semibold text-gray-400">
        <Icons.Clock className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
        <span>Data inquiry berlaku 10 menit. Lakukan pengecekan ulang bila sudah kedaluwarsa.</span>
      </p>

      <button
        type="button"
        onClick={() =>
          onOpenCheckout({
            code: inquiryResult?.product_id || 'TAGIHAN',
            name: `${serviceLabel} - ${summary.customerName}`,
            price: summary.grandTotal,
            inquiryKey: inquiryResult?.inquiry_key,
          })
        }
        className="w-full py-4 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 font-black text-sm shadow-lg shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-between cursor-pointer"
      >
        <span className="flex items-center gap-2.5">
          <Icons.Lightning className="w-5 h-5" />
          <span>Bayar Tagihan Sekarang</span>
        </span>
        <span className="px-3 py-1.5 rounded-xl bg-black/20 font-mono font-black text-sm">
          {fmt.rp(summary.grandTotal)}
        </span>
      </button>
    </div>
  );
}
