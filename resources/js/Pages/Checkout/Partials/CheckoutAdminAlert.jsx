import React from 'react';
import { Link } from '@inertiajs/react';

export default function CheckoutAdminAlert() {
  return (
    <div className="bg-amber-500/10 border-2 border-amber-500/30 rounded-3xl p-6 text-center space-y-4">
      <div className="w-16 h-16 bg-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-sm">
        👑
      </div>
      <div>
        <h3 className="text-lg font-black text-gray-900 dark:text-white">Mode Pantau Admin Aktif</h3>
        <p className="text-xs text-gray-600 dark:text-gray-300 leading-relaxed font-medium mt-1.5">
          Anda sedang melihat toko dalam status <strong>Administrator</strong>. Akun admin hanya memiliki hak pantau toko, tidak memiliki profil pembeli atau saldo, serta tidak dapat melakukan transaksi atau checkout produk.
        </p>
      </div>
      <div className="pt-2">
        <Link
          href="/admin/dashboard"
          className="inline-flex items-center justify-center gap-2 w-full bg-gray-900 hover:bg-black dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-black text-xs py-3.5 px-4 rounded-xl transition shadow-md cursor-pointer"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Kembali ke Dashboard Admin
        </Link>
      </div>
    </div>
  );
}

