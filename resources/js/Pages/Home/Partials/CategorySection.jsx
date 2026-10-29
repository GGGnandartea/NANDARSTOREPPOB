import React from 'react';

export default function CategorySection({
    categoryList,
    manualCategories,
    filters,
    brandContainerRef,
    typeContainerRef,
    scrollSlider,
    handleCategoryBrand,
    handleCategoryTipe,
    setShowCategoryModal,
    handleClearSearch,
}) {
    const hasActiveFilter = Boolean(filters?.category || filters?.tipe || filters?.search);

    return (
        <>
            {/* SLIDER KATEGORI POPULER */}
            <div id="katalog-produk" className="bg-white dark:bg-[#131b2e] rounded-2xl shadow-xs border border-gray-200/80 dark:border-white/5 p-4 sm:p-5 mb-4 relative group/slider scroll-mt-20">
                <div className="flex items-center justify-between mb-3.5">
                    <div>
                        <h2 className="text-sm sm:text-base font-extrabold text-black dark:text-white">Kategori Premium Populer</h2>
                        <p className="mt-0.5 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400">Terlaris berdasarkan penjualan 30 hari terakhir</p>
                    </div>
                    <button
                        type="button"
                        onClick={() => setShowCategoryModal(true)}
                        className="!min-h-0 text-xs font-bold text-orange-500 hover:text-orange-600 transition-colors cursor-pointer bg-orange-50 dark:bg-orange-500/10 border border-orange-500/20 px-3 py-1.5 rounded-full"
                    >
                        Lihat Semua
                    </button>
                </div>

                <button
                    type="button"
                    onClick={() => scrollSlider(brandContainerRef, 'left')}
                    className="!min-h-0 absolute left-1.5 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer"
                    aria-label="Geser Kategori Kiri"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                </button>

                <div ref={brandContainerRef} className="flex gap-3.5 sm:gap-5 overflow-x-auto hide-scroll pb-1.5 snap-x snap-mandatory scroll-smooth">
                    <div onClick={() => handleCategoryBrand('')} className="flex flex-col items-center gap-1.5 cursor-pointer snap-start min-w-[66px] group">
                        <div className={`w-14 h-14 sm:w-15 sm:h-15 rounded-2xl flex items-center justify-center border-2 transition-all duration-200 ${!filters.category && !filters.tipe ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/15 text-orange-500 shadow-xs' : 'border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-gray-800/70 text-gray-400 group-hover:border-orange-400/50'}`}>
                            <svg className="w-6 h-6 sm:w-7 sm:h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                        <span className={`text-[11px] font-bold text-center leading-tight line-clamp-2 ${!filters.category && !filters.tipe ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300'}`}>Semua<br/>Brand</span>
                    </div>
                    {categoryList.map((cat, index) => (
                        <div key={index} onClick={() => handleCategoryBrand(cat.name)} className="flex flex-col items-center gap-1.5 cursor-pointer snap-start min-w-[66px] group">
                            <div className={`w-14 h-14 sm:w-15 sm:h-15 rounded-2xl overflow-hidden flex items-center justify-center border-2 transition-all duration-200 p-1 ${filters.category === cat.name ? 'border-orange-500 bg-orange-50 dark:bg-orange-500/15 shadow-md scale-[1.03]' : 'border-gray-100 dark:border-white/10 bg-gray-50 dark:bg-gray-800/70 group-hover:border-orange-400/50'}`}>
                                {cat.image ? (
                                    <img src={cat.image} alt={cat.name} className="w-full h-full object-cover rounded-xl" />
                                ) : (
                                    <div className="w-full h-full bg-gray-200 dark:bg-gray-700 rounded-xl flex items-center justify-center font-black text-gray-400 text-xs">PK</div>
                                )}
                            </div>
                            <span className={`text-[11px] font-bold text-center leading-tight line-clamp-2 max-w-[76px] ${filters.category === cat.name ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300'}`}>{cat.name}</span>
                        </div>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={() => scrollSlider(brandContainerRef, 'right')}
                    className="!min-h-0 absolute right-1.5 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer"
                    aria-label="Geser Kategori Kanan"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            {/* SLIDER TIPE MANUAL */}
            <div className="mb-5 relative group/typeSlider">
                <button
                    type="button"
                    onClick={() => scrollSlider(typeContainerRef, 'left')}
                    className="!min-h-0 absolute -left-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex"
                    aria-label="Geser Tipe Kiri"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>

                <div ref={typeContainerRef} className="flex gap-2 sm:gap-2.5 overflow-x-auto hide-scroll pb-1 px-0.5 scroll-smooth">
                    <button
                        type="button"
                        onClick={() => handleCategoryTipe('')}
                        className={`!min-h-0 px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all border shadow-xs cursor-pointer ${
                            !filters.tipe && !filters.category
                                ? 'bg-gray-900 dark:bg-white text-white dark:text-black border-gray-900 dark:border-white'
                                : 'bg-white dark:bg-[#131b2e] text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-orange-400'
                        }`}
                    >
                        Semua Tipe
                    </button>
                    {manualCategories.map((tipe, idx) => (
                        <button
                            key={idx}
                            type="button"
                            onClick={() => handleCategoryTipe(tipe)}
                            className={`!min-h-0 px-4 py-2 rounded-full text-xs font-extrabold whitespace-nowrap transition-all border shadow-xs cursor-pointer ${
                                filters.tipe === tipe
                                    ? 'bg-orange-500 text-white border-orange-500 shadow-sm'
                                    : 'bg-white dark:bg-[#131b2e] text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-orange-400'
                            }`}
                        >
                            {tipe}
                        </button>
                    ))}
                </div>

                <button
                    type="button"
                    onClick={() => scrollSlider(typeContainerRef, 'right')}
                    className="!min-h-0 absolute -right-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-800 rounded-full shadow-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex"
                    aria-label="Geser Tipe Kanan"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            {/* BAR STATUS FILTER / PENCARIAN AKTIF */}
            {hasActiveFilter && (
                <div className="ppob-fade-in mb-4 flex flex-wrap items-center justify-between gap-2 px-3.5 py-2.5 rounded-xl bg-orange-500/10 dark:bg-orange-500/10 border border-orange-500/25">
                    <div className="flex items-center gap-2 text-xs font-bold text-gray-800 dark:text-gray-200">
                        <span className="w-2 h-2 rounded-full bg-orange-500 shrink-0" />
                        {filters.search ? (
                            <span>
                                Hasil pencarian di semua produk: <strong className="text-orange-600 dark:text-orange-400">&ldquo;{filters.search}&rdquo;</strong>
                            </span>
                        ) : (
                            <span>
                                Menampilkan kategori: <strong className="text-orange-600 dark:text-orange-400">{filters.category || filters.tipe}</strong>
                            </span>
                        )}
                    </div>
                    <button
                        type="button"
                        onClick={handleClearSearch}
                        className="!min-h-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-white/10 text-[11px] font-extrabold transition cursor-pointer"
                    >
                        <span>Semua Produk</span>
                        <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            )}
        </>
    );
}
