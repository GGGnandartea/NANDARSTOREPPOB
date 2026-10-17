import React from 'react';
import { Icons, fmt } from './PpobConstants';

export default function PpobProductGrid({
  products,
  isLoadingProducts,
  selectedProduct,
  onOpenCheckout,
}) {
  return (
    <div className="lg:col-span-7 space-y-4">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-base font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
            <span>Pilih Nominal &amp; Produk</span>
            <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
              {products.length} Pilihan
            </span>
          </h3>
          <p className="text-xs text-gray-400 mt-0.5">Pilih produk yang ingin Anda beli, lalu lanjutkan ke pembayaran.</p>
        </div>
      </div>

      {/* PRODUCT CARDS GRID */}
      {isLoadingProducts ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 py-16 text-center">
          <div className="col-span-2 flex flex-col items-center justify-center space-y-3">
            <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
            <p className="text-xs font-bold text-gray-400">Memuat katalog produk resmi...</p>
          </div>
        </div>
      ) : products.length === 0 ? (
        <div className="p-10 text-center rounded-3xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 space-y-2">
          <div className="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto text-gray-400">
            <Icons.Search className="w-6 h-6" />
          </div>
          <h4 className="font-black text-sm text-gray-800 dark:text-gray-200">Katalog Sedang Dimuat</h4>
          <p className="text-xs text-gray-400 max-w-sm mx-auto">
            Tidak ada produk yang tersedia untuk pilihan ini saat ini. Silakan pilih nominal atau operator lain.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {products.map((item) => {
            const isSelected = selectedProduct?.code === item.code;
            const isMaintenance = item.status === 'maintenance';
            const isInactive = item.status === 'inactive';

            return (
              <div
                key={item.code}
                onClick={() => onOpenCheckout(item)}
                className={`p-4 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col justify-between group relative overflow-hidden ${
                  isMaintenance
                    ? 'bg-amber-500/5 border-amber-500/30 opacity-75'
                    : isInactive
                    ? 'bg-gray-100 dark:bg-white/5 opacity-50 cursor-not-allowed'
                    : isSelected
                    ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-500 shadow-md ring-2 ring-emerald-500/20'
                    : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-emerald-500/50 hover:shadow-md'
                }`}
              >
                <div>
                  <div className="flex items-start justify-between gap-2 mb-2">
                    <h4 className="font-black text-xs sm:text-sm text-gray-900 dark:text-white group-hover:text-emerald-500 transition">
                      {item.name}
                    </h4>
                    {isMaintenance ? (
                      <span className="text-[10px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40 shrink-0">
                        GANGGUAN
                      </span>
                    ) : isInactive ? (
                      <span className="text-[10px] font-black uppercase px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 shrink-0">
                        NONAKTIF
                      </span>
                    ) : (
                      <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shrink-0">
                        INSTAN
                      </span>
                    )}
                  </div>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5 mt-2 gap-2">
                  <div>
                    <p className="text-[10px] font-black uppercase text-gray-400">Harga Layanan</p>
                    <span className="text-sm sm:text-base font-black font-mono text-emerald-600 dark:text-emerald-400">
                      {fmt.rp(item.price)}
                    </span>
                  </div>
                  <button
                    type="button"
                    className={`px-4 py-2.5 rounded-xl text-xs font-black shadow-md transition-all flex items-center gap-1.5 shrink-0 ${
                      isMaintenance
                        ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30 cursor-not-allowed'
                        : isInactive
                        ? 'bg-gray-100 dark:bg-white/5 text-gray-400 cursor-not-allowed'
                        : 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white border border-emerald-500/40 shadow-sm shadow-emerald-950/30 group-hover:scale-105 active:scale-95 cursor-pointer'
                    }`}
                  >
                    {isMaintenance ? (
                      <span>Gangguan</span>
                    ) : isInactive ? (
                      <span>Tutup</span>
                    ) : (
                      <>
                        <span>Beli Sekarang</span>
                        <svg className="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                      </>
                    )}
                  </button>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
