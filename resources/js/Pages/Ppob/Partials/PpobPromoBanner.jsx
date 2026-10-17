import React from 'react';
import { Icons } from './PpobConstants';

export default function PpobPromoBanner() {
  return (
    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div>
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-black uppercase tracking-wider mb-3 shadow-xs">
          <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
          <span>Layanan Transaksi Otomatis Aktif 24 Jam Nonstop</span>
        </div>
        <h1 className="text-2xl sm:text-4xl font-black tracking-tight text-gray-900 dark:text-white">
          Dashboard Pembelian <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600">PPOB &amp; Pulsa</span>
        </h1>
        <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-medium mt-1.5 max-w-2xl leading-relaxed">
          Isi pulsa reguler, paket data internet, token PLN, e-wallet, top up game dan bayar tagihan dengan proses otomatis instan dalam hitungan detik.
        </p>
      </div>

      <div className="flex flex-wrap items-center gap-2 self-start md:self-auto">
        <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
          <Icons.Lightning className="w-3.5 h-3.5 text-emerald-500" />
          <span>Proses 1-5 Detik</span>
        </span>
        <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
          <Icons.Shield className="w-3.5 h-3.5 text-emerald-500" />
          <span>SN Terbit Otomatis</span>
        </span>
        <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
          <Icons.CreditCard className="w-3.5 h-3.5 text-emerald-500" />
          <span>QRIS &amp; Saldo</span>
        </span>
      </div>
    </div>
  );
}

