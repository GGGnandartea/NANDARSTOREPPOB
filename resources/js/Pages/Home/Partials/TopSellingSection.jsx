import React from 'react';
import { Link } from '@inertiajs/react';

export default function TopSellingSection({
    liveTopSelling,
    productList,
    filters,
    topSellingContainerRef,
    scrollSlider,
    store,
    onOpenPpob,
}) {
    if (filters.category || filters.tipe || filters.search) {
        return null;
    }

    if ((!liveTopSelling || liveTopSelling.length === 0) && productList.length === 0) {
        return null;
    }

    const isPpobOff = Boolean(store?.ppob_status?.is_cutoff);
    const itemsToDisplay = (liveTopSelling && liveTopSelling.length > 0 ? liveTopSelling : productList).slice(0, 4);

    return (
        <div
            id="paling-laris"
            className="mb-5 md:mb-6 rounded-2xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/10 shadow-xs px-3.5 py-2.5 sm:px-4 sm:py-3 relative overflow-hidden"
        >
            {/* Slim Single-Row Header */}
            <div className="flex items-center justify-between gap-2 mb-2">
                <div className="flex items-center gap-2 min-w-0">
                    <span className="w-6 h-6 rounded-lg bg-orange-500/15 text-orange-500 flex items-center justify-center shrink-0">
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18" />
                            <polyline points="17 6 23 6 23 12" />
                        </svg>
                    </span>
                    <h2 className="text-xs sm:text-sm font-black text-gray-900 dark:text-white tracking-tight truncate">
                        Terlaris Hari Ini
                    </h2>
                </div>

                {/* Compact Navigation Arrows */}
                <div className="flex items-center gap-1 shrink-0">
                    <button
                        type="button"
                        onClick={() => scrollSlider(topSellingContainerRef, 'left')}
                        className="!min-h-0 w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-gray-100 dark:bg-white/5 hover:bg-orange-500 hover:text-white text-gray-500 dark:text-gray-300 flex items-center justify-center transition cursor-pointer active:scale-95"
                        aria-label="Geser Kiri"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        onClick={() => scrollSlider(topSellingContainerRef, 'right')}
                        className="!min-h-0 w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-gray-100 dark:bg-white/5 hover:bg-orange-500 hover:text-white text-gray-500 dark:text-gray-300 flex items-center justify-center transition cursor-pointer active:scale-95"
                        aria-label="Geser Kanan"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Slim Product Strip (No Extra Small Sub-Labels) */}
            <div
                ref={topSellingContainerRef}
                className="flex items-center gap-2 sm:gap-2.5 w-full overflow-x-auto hide-scroll scroll-smooth snap-x snap-mandatory"
            >
                {itemsToDisplay.map((product, idx) => {
                    const rankNumber = idx + 1;

                    const targetHref = product.is_ppob ? (product.ppob_url || '/ppob') : `/product/${product.id}`;

                    return (
                        <Link
                            key={`top-selling-${product.id}-${idx}`}
                            href={targetHref}
                            onClick={(e) => {
                                if (product.is_ppob && isPpobOff && onOpenPpob) {
                                    e.preventDefault();
                                    onOpenPpob(targetHref);
                                }
                            }}
                            className="flex items-center gap-2.5 bg-gray-50 dark:bg-[#0b1121]/80 hover:bg-orange-50/50 dark:hover:bg-white/[0.06] border border-gray-200/70 dark:border-white/5 hover:border-orange-500/40 rounded-xl px-2.5 py-2 min-w-[165px] sm:min-w-[195px] flex-1 transition-all duration-200 group cursor-pointer shrink-0 snap-start"
                        >
                            {/* Compact Icon + Rank */}
                            <div className="relative w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-white dark:bg-gray-900 p-1 flex items-center justify-center shrink-0 border border-gray-100 dark:border-white/5">
                                <span
                                    className={`absolute -top-1 -left-1 w-3.5 h-3.5 rounded-full flex items-center justify-center font-black text-[9px] leading-none ${
                                        rankNumber === 1
                                            ? 'bg-amber-400 text-gray-950'
                                            : rankNumber === 2
                                            ? 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white'
                                            : 'bg-orange-500 text-white'
                                    }`}
                                >
                                    {rankNumber}
                                </span>
                                {product.image ? (
                                    <img src={product.image} alt={product.name} className="w-full h-full object-contain rounded" />
                                ) : product.is_ppob ? (
                                    <div className="w-full h-full rounded-md bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center p-1">
                                        {product.ppob_icon_type === 'pln' ? (
                                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 18h6M10 22h4M12 2a7 7 0 0 0-4 12.75V17h8v-2.25A7 7 0 0 0 12 2z"/></svg>
                                        ) : product.ppob_icon_type === 'game' ? (
                                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                                        ) : product.ppob_icon_type === 'ewallet' ? (
                                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5" fill="currentColor"/></svg>
                                        ) : (
                                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                        )}
                                    </div>
                                ) : (
                                    <span className="text-[10px] font-black text-orange-500">PK</span>
                                )}
                            </div>

                            {/* Clean Name & Price Only */}
                            <div className="min-w-0 flex-1">
                                <span className="block text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-orange-500 transition-colors leading-tight">
                                    {product.name}
                                </span>
                                <span className="block text-[11px] font-black text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 leading-tight">
                                    Rp {new Intl.NumberFormat('id-ID').format(product.price)}
                                </span>
                            </div>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
