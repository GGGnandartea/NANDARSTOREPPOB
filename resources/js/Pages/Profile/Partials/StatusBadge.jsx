import React from 'react';

export default function StatusBadge({ status, isPpob = false }) {
  const s = (status || '').toUpperCase();

  if (isPpob) {
    const ppobMap = {
      PAID: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40',
      SUCCESS: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40',
      COMPLETED: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40',
      PENDING: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40',
      PROCESSING: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40',
      REFUNDED: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40',
      FAILED: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40',
      EXPIRED: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
    };
    const ppobLabels = {
      PAID: 'Lunas',
      SUCCESS: 'Berhasil',
      COMPLETED: 'Berhasil',
      PENDING: 'Pending',
      PROCESSING: 'Pending',
      REFUNDED: 'Gagal (Saldo Di-Refund)',
      FAILED: 'Gagal (Saldo Di-Refund)',
      EXPIRED: 'Kadaluarsa',
    };
    return (
      <span className={`text-[11px] font-black px-2.5 py-0.5 rounded-full ${ppobMap[s] || 'bg-gray-100 text-gray-500'}`}>
        {ppobLabels[s] || s || '-'}
      </span>
    );
  }

  const map = {
    PAID: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    SUCCESS: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    COMPLETED: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    PENDING: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    PROCESSING: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    REFUNDED: 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-800/40',
    FAILED: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    EXPIRED: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300',
  };
  const labels = {
    PAID: 'Lunas',
    SUCCESS: 'Berhasil',
    COMPLETED: 'Selesai',
    PENDING: 'Menunggu',
    PROCESSING: 'Diproses',
    REFUNDED: 'Di-refund ke Saldo',
    FAILED: 'Gagal',
    EXPIRED: 'Kadaluarsa'
  };
  return <span className={`text-[11px] font-black px-2 py-0.5 rounded-full ${map[s] || 'bg-gray-100 text-gray-500'}`}>{labels[s] || s || '-'}</span>;
}

