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
}) {
    return (
        <>
            {/* SLIDER KATEGORI POPULER */}
            <div id="katalog-produk" className="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 p-4 sm:p-6 mb-4 relative group/slider scroll-mt-20">
                <div className="flex items-center justify-between mb-4">
                    <div>
                        <h2 className="text-sm sm:text-base font-extrabold text-black dark:text-white">Kategori Premium Populer</h2>
                        <p className="mt-1 text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">Terlaris berdasarkan penjualan 30 hari terakhir</p>
                    </div>
                    <button onClick={() => setShowCategoryModal(true)} className="text-xs font-bold text-orange-500 hover:text-orange-600 hover:underline transition-colors cursor-pointer bg-orange-50 dark:bg-orange-900/30 px-3 py-1.5 rounded-full">
                        Lihat Semua
                    </button>
                </div>
                
                <button onClick={() => scrollSlider(brandContainerRef, 'left')} className="absolute left-1 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer" aria-label="Geser Kategori Kiri">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                
                <div ref={brandContainerRef} className="flex gap-4 sm:gap-6 overflow-x-auto hide-scroll pb-2 snap-x snap-mandatory scroll-smooth">
                    <div onClick={() => handleCategoryBrand('')} className="flex flex-col items-center gap-2 cursor-pointer snap-start min-w-[70px] group">
                        <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center border-2 transition-all ${!filters.category && !filters.tipe ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-500' : 'border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 text-gray-400 group-hover:border-orange-200 shadow-sm'}`}>
                            <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                        <span className={`text-[10px] sm:text-xs font-bold text-center leading-tight line-clamp-2 ${!filters.category && !filters.tipe ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300'}`}>Semua<br/>Brand</span>
                    </div>
                    {categoryList.map((cat, index) => (
                        <div key={index} onClick={() => handleCategoryBrand(cat.name)} className="flex flex-col items-center gap-2 cursor-pointer snap-start min-w-[70px] group">
                            <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl overflow-hidden flex items-center justify-center border-2 transition-all p-1 ${filters.category === cat.name ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 shadow-md' : 'border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50 group-hover:border-orange-200 shadow-sm'}`}>
                                {cat.image ? (
                                    <img src={cat.image} alt={cat.name} className="w-full h-full object-cover rounded-xl" />
                                ) : (
                                    <div className="w-full h-full bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center font-black text-gray-400 text-xs">NS</div>
                                )}
                            </div>
                            <span className={`text-[10px] sm:text-xs font-bold text-center leading-tight line-clamp-2 max-w-[80px] ${filters.category === cat.name ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300'}`}>{cat.name}</span>
                        </div>
                    ))}
                </div>
                
                <button onClick={() => scrollSlider(brandContainerRef, 'right')} className="absolute right-1 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer" aria-label="Geser Kategori Kanan">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>

            {/* SLIDER TIPE MANUAL */}
            <div className="mb-8 relative group/typeSlider">
                <button onClick={() => scrollSlider(typeContainerRef, 'left')} className="absolute -left-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex" aria-label="Geser Tipe Kiri">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                
                <div ref={typeContainerRef} className="flex gap-2.5 sm:gap-3 overflow-x-auto hide-scroll pb-2 px-1 scroll-smooth">
                    <button onClick={() => handleCategoryTipe('')} className={`px-4 py-2 sm:px-5 sm:py-2.5 rounded-full text-[11px] sm:text-xs font-extrabold whitespace-nowrap transition-all border shadow-sm cursor-pointer ${!filters.tipe && !filters.category ? 'bg-gray-900 dark:bg-white text-white dark:text-black border-gray-900 dark:border-white' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-gray-400'}`}>
                        Semua Tipe
                    </button>
                    {manualCategories.map((tipe, idx) => (
                        <button key={idx} onClick={() => handleCategoryTipe(tipe)} className={`px-4 py-2 sm:px-5 sm:py-2.5 rounded-full text-[11px] sm:text-xs font-extrabold whitespace-nowrap transition-all border shadow-sm cursor-pointer ${filters.tipe === tipe ? 'bg-orange-500 text-white border-orange-500' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700 hover:border-orange-300'}`}>
                            {tipe}
                        </button>
                    ))}
                </div>

                <button onClick={() => scrollSlider(typeContainerRef, 'right')} className="absolute -right-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex" aria-label="Geser Tipe Kanan">
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </>
    );
}

