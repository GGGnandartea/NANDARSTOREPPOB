import React from 'react';
import { Link } from '@inertiajs/react';

export default function TopSellingSection({
    liveTopSelling,
    productList,
    filters,
    topSellingContainerRef,
    scrollSlider,
}) {
    if (filters.category || filters.tipe || filters.search) {
        return null;
    }

    if ((!liveTopSelling || liveTopSelling.length === 0) && productList.length === 0) {
        return null;
    }

    const itemsToDisplay = liveTopSelling.length > 0 ? liveTopSelling : productList.slice(0, 4);

    return (
        <div id="paling-laris" className="bg-gradient-to-r from-orange-500 via-rose-500 to-red-600 rounded-3xl shadow-lg p-1 sm:p-1.5 mb-6 md:mb-8 animate-fade-in-up relative overflow-hidden">
            <div className="bg-white dark:bg-gray-900 rounded-[1.3rem] px-4 py-4 sm:px-6 sm:py-5 flex flex-col lg:flex-row items-center justify-between gap-4 relative">
                {/* Header Kiri */}
                <div className="flex items-center justify-between w-full lg:w-auto shrink-0">
                    <div className="flex items-center gap-3">
                        <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500/20 to-red-500/20 border border-orange-500/30 flex items-center justify-center shrink-0 shadow-inner">
                            <span className="text-2xl animate-bounce">🔥</span>
                        </div>
                        <div>
                            <div className="flex items-center gap-2 flex-wrap">
                                <h2 className="text-base sm:text-lg font-black text-gray-900 dark:text-white leading-tight">
                                    Paling Banyak Dibeli Hari Ini
                                </h2>
                                <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 tracking-wider uppercase shadow-xs">
                                    <span className="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                    Realtime
                                </span>
                            </div>
                            <p className="text-xs font-bold text-gray-500 dark:text-gray-400 mt-0.5">
                                Harga spesial dengan stok terbatas.
                            </p>
                        </div>
                    </div>

                    {/* Slider Navigation Arrows on Mobile / Tablet */}
                    <div className="flex items-center gap-1 lg:hidden">
                        <button
                            type="button"
                            onClick={() => scrollSlider(topSellingContainerRef, 'left')}
                            className="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-gray-600 dark:text-gray-300 flex items-center justify-center transition cursor-pointer"
                            aria-label="Scroll Kiri"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <button
                            type="button"
                            onClick={() => scrollSlider(topSellingContainerRef, 'right')}
                            className="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-gray-600 dark:text-gray-300 flex items-center justify-center transition cursor-pointer"
                            aria-label="Scroll Kanan"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>
                </div>
                
                {/* Slider Cards Kanan */}
                <div className="relative w-full lg:w-auto flex-1 min-w-0 flex items-center">
                    {/* Desktop scroll arrow left */}
                    <button
                        type="button"
                        onClick={() => scrollSlider(topSellingContainerRef, 'left')}
                        className="hidden lg:flex w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-500 hover:text-orange-500 hover:border-orange-500 z-10 mr-1 shrink-0 cursor-pointer transition active:scale-90"
                        aria-label="Geser Kiri"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                    </button>

                    <div
                        ref={topSellingContainerRef}
                        className="flex items-center gap-2.5 sm:gap-3 w-full overflow-x-auto hide-scroll pb-1 scroll-smooth snap-x snap-mandatory"
                    >
                        {itemsToDisplay.map((product, idx) => {
                            const isHotToday = Boolean(product.today_sales && product.today_sales > 0);
                            const rankNumber = idx + 1;

                            return (
                                <Link
                                    key={`top-selling-${product.id}-${idx}`}
                                    href={product.is_ppob ? (product.ppob_url || '/ppob') : `/product/${product.id}`}
                                    className="flex items-center gap-2.5 bg-gray-50 dark:bg-gray-800/80 hover:bg-emerald-50/40 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl p-2.5 min-w-[200px] sm:min-w-[220px] max-w-[260px] hover:border-emerald-500 hover:shadow-md transition-all group cursor-pointer shrink-0 snap-start relative overflow-hidden"
                                >
                                    {/* Ranking Badge Mini */}
                                    <div className={`absolute top-1 left-1 w-4 h-4 rounded-md flex items-center justify-center font-black text-[9px] z-10 ${
                                        rankNumber === 1
                                            ? 'bg-amber-400 text-gray-950 shadow-xs'
                                            : rankNumber === 2
                                            ? 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white'
                                            : rankNumber === 3
                                            ? 'bg-amber-700 text-white'
                                            : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                    }`}>
                                        {rankNumber}
                                    </div>

                                    {/* Foto / Icon Produk */}
                                    <div className="w-12 h-12 rounded-xl bg-white dark:bg-gray-900 p-1 flex items-center justify-center shrink-0 border border-gray-100 dark:border-gray-800 shadow-xs group-hover:scale-105 transition-transform">
                                        {product.image ? (
                                            <img src={product.image} alt={product.name} className="w-full h-full object-contain" />
                                        ) : product.is_ppob ? (
                                            <div className="w-full h-full rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center p-2 shadow-xs">
                                                {product.ppob_icon_type === 'pln' ? (
                                                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                                                ) : product.ppob_icon_type === 'game' ? (
                                                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                                                ) : product.ppob_icon_type === 'ewallet' ? (
                                                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5" fill="currentColor"/></svg>
                                                ) : (
                                                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                                )}
                                            </div>
                                        ) : (
                                            <span className="text-[10px] font-black text-gray-400">NS</span>
                                        )}
                                    </div>

                                    {/* Info Produk */}
                                    <div className="flex flex-col min-w-0 flex-1">
                                        <div className="flex items-center gap-1">
                                            <span className="text-xs font-black text-gray-900 dark:text-white line-clamp-1 group-hover:text-emerald-500 transition-colors">
                                                {product.name}
                                            </span>
                                        </div>

                                        {/* Realtime Sales Tag */}
                                        <div className="mt-0.5 flex items-center gap-1">
                                            {product.is_ppob && (
                                                <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-1 py-0.2 rounded border border-emerald-200 dark:border-emerald-800/40 uppercase">
                                                    PPOB
                                                </span>
                                            )}
                                            {isHotToday ? (
                                                <span className="text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/80 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900/40 inline-flex items-center gap-1">
                                                    <span className="w-1 h-1 rounded-full bg-rose-500 animate-pulse"></span>
                                                    🔥 {product.sales_label || `${product.today_sales} dibeli hari ini`}
                                                </span>
                                            ) : product.total_sales > 0 ? (
                                                <span className="text-[9px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-900/40 inline-flex items-center gap-0.5">
                                                    ⚡ {product.sales_label || `${product.total_sales} terjual`}
                                                </span>
                                            ) : (
                                                <span className="text-[9px] font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded inline-flex items-center gap-0.5">
                                                    ⭐ Terpopuler
                                                </span>
                                            )}
                                        </div>

                                        <div className="flex items-center gap-1.5 mt-1">
                                            <span className="text-xs font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                                Rp {new Intl.NumberFormat('id-ID').format(product.price)}
                                            </span>
                                            {product.stock !== undefined && product.stock !== null && !product.is_ppob && (
                                                <span className="text-[9px] font-semibold text-gray-400 truncate">
                                                    • Sisa {product.stock}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>

                    {/* Desktop scroll arrow right */}
                    <button
                        type="button"
                        onClick={() => scrollSlider(topSellingContainerRef, 'right')}
                        className="hidden lg:flex w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-500 hover:text-orange-500 hover:border-orange-500 z-10 ml-1 shrink-0 cursor-pointer transition active:scale-90"
                        aria-label="Geser Kanan"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    );
}

