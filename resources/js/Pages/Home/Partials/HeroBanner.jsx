import React from 'react';

export default function HeroBanner({
    bannerList,
    currentBanner,
    setCurrentBanner,
    isHoveredBanner,
    setIsHoveredBanner,
    handlePrevBanner,
    handleNextBanner,
    handleTouchStart,
    handleTouchMove,
    handleTouchEnd,
    handleBannerClick,
    searchTerm,
    handleInputChange,
    handleSearch,
    isScrolled,
}) {
    return (
        <div className="bg-gradient-to-b from-orange-50/70 via-gray-50/40 to-gray-50 dark:from-[#0b1121] dark:via-[#0b1121] dark:to-[#0f172a] pt-4 sm:pt-6 pb-8 sm:pb-12 relative overflow-hidden transition-colors">
            <div className="absolute top-0 left-0 w-full h-full overflow-hidden opacity-15 dark:opacity-30 pointer-events-none">
                <div className="absolute top-[-10%] left-[20%] w-[400px] h-[400px] bg-gradient-to-br from-orange-400 to-transparent rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-[100px]"></div>
            </div>
            
            {/* BANNER CAROUSEL: Full Width on Desktop, Compact & Proporsional on Mobile */}
            <div className="w-full px-4 sm:px-6 md:px-8 lg:px-10 xl:px-14 2xl:max-w-[1720px] 2xl:mx-auto relative z-10 mb-5 sm:mb-7">
                <div 
                    className="w-full rounded-2xl sm:rounded-3xl overflow-hidden shadow-xl sm:shadow-2xl relative bg-white dark:bg-gray-900 aspect-[16/7] sm:aspect-[21/9] md:aspect-[3.2/1] lg:aspect-[3.6/1] xl:aspect-[3.8/1] border border-gray-200/80 dark:border-white/10 group select-none"
                    onMouseEnter={() => setIsHoveredBanner(true)}
                    onMouseLeave={() => setIsHoveredBanner(false)}
                    onTouchStart={handleTouchStart}
                    onTouchMove={handleTouchMove}
                    onTouchEnd={handleTouchEnd}
                >
                    {/* Slide Track */}
                    <div 
                        className="w-full h-full flex transition-transform duration-1000 ease-[cubic-bezier(0.16,1,0.3,1)] will-change-transform" 
                        style={{ transform: `translateX(-${currentBanner * 100}%)` }}
                    >
                        {bannerList.map((banner, idx) => {
                            const hasImage = Boolean(banner.image);
                            const hasMobileImage = Boolean(banner.image_mobile);
                            const hasText = Boolean(banner.title || banner.subtitle || banner.desc || banner.badge);
                            const bgGrad = banner.bg_gradient 
                                ? (banner.bg_gradient.startsWith('bg-') ? banner.bg_gradient : `bg-gradient-to-r ${banner.bg_gradient}`) 
                                : (banner.bg ? banner.bg : 'bg-gradient-to-r from-orange-600 to-red-600');

                            return (
                                <div 
                                    key={banner.id || idx} 
                                    onClick={() => handleBannerClick(banner)}
                                    className={`w-full h-full flex-shrink-0 relative overflow-hidden flex flex-col justify-center transition-all duration-1000 ease-[cubic-bezier(0.16,1,0.3,1)] ${banner.link_url ? 'cursor-pointer' : ''} ${!hasImage ? bgGrad : 'bg-gray-950'} ${currentBanner === idx ? 'scale-100 opacity-100' : 'scale-[0.985] opacity-75'}`}
                                >
                                    {/* Custom Banner Image (Responsive Desktop & Mobile) */}
                                    {hasImage && (
                                        <picture className="absolute inset-0 w-full h-full pointer-events-none">
                                            {hasMobileImage && (
                                                <source media="(max-width: 640px)" srcSet={banner.image_mobile} />
                                            )}
                                            <img 
                                                src={banner.image} 
                                                alt={banner.title || 'Promo Banner'} 
                                                className={`w-full h-full object-cover select-none pointer-events-none transition-transform duration-1000 ease-[cubic-bezier(0.16,1,0.3,1)] group-hover:scale-[1.02] ${currentBanner === idx ? 'scale-100' : 'scale-105'}`}
                                                loading={idx === 0 ? "eager" : "lazy"}
                                            />
                                        </picture>
                                    )}

                                    {/* Overlay Kontras Teks jika ada gambar */}
                                    {hasImage && hasText && (
                                        <div className="absolute inset-0 bg-gradient-to-r from-black/85 via-black/40 to-transparent z-10 pointer-events-none"></div>
                                    )}
                                    
                                    {!hasImage && (
                                        <div className="absolute inset-0 bg-black/15 z-10 pointer-events-none"></div>
                                    )}

                                    {/* Konten Teks Banner (Hanya muncul jika ada judul/subtitle/badge) */}
                                    {hasText && (
                                        <div className="relative z-20 w-full h-full flex flex-col justify-center px-6 sm:px-12 md:px-16 lg:px-20 py-4 max-w-3xl">
                                            {banner.badge && (
                                                <span className="inline-block self-start px-2.5 py-1 bg-white/20 backdrop-blur-md rounded-full text-[9px] sm:text-[10px] md:text-xs font-black text-white mb-1.5 sm:mb-2 uppercase tracking-wider shadow-sm">
                                                    {banner.badge}
                                                </span>
                                            )}
                                            {banner.title && (
                                                <h2 className="text-base sm:text-2xl md:text-3xl lg:text-4xl font-black text-white mb-1 sm:mb-2 tracking-tight leading-tight max-w-[280px] sm:max-w-lg lg:max-w-xl drop-shadow-md">
                                                    {banner.title}
                                                </h2>
                                            )}
                                            {(banner.subtitle || banner.desc) && (
                                                <p className="text-white/85 text-[10px] sm:text-xs md:text-sm font-medium max-w-[240px] sm:max-w-md lg:max-w-lg line-clamp-2 drop-shadow">
                                                    {banner.subtitle || banner.desc}
                                                </p>
                                            )}
                                        </div>
                                    )}

                                    {/* Decorative Icon fallback jika tanpa gambar */}
                                    {!hasImage && (
                                        <div className="absolute right-[-4%] bottom-[-15%] opacity-20 transform -rotate-12 scale-125 blur-[1px] pointer-events-none select-none">
                                            {idx % 3 === 0 && <span className="text-[120px] sm:text-[160px] md:text-[200px]">⚡</span>}
                                            {idx % 3 === 1 && <span className="text-[120px] sm:text-[160px] md:text-[200px]">💎</span>}
                                            {idx % 3 === 2 && <span className="text-[120px] sm:text-[160px] md:text-[200px]">🔥</span>}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    
                    {/* Desktop Navigation Arrows (Prev & Next) */}
                    {bannerList.length > 1 && (
                        <>
                            <button 
                                type="button"
                                onClick={(e) => { e.stopPropagation(); handlePrevBanner(); }}
                                className="hidden md:flex absolute left-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-black/40 hover:bg-black/70 text-white backdrop-blur-md items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 hover:scale-110 shadow-lg cursor-pointer border border-white/20"
                                aria-label="Previous Banner"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" /></svg>
                            </button>
                            <button 
                                type="button"
                                onClick={(e) => { e.stopPropagation(); handleNextBanner(); }}
                                className="hidden md:flex absolute right-4 top-1/2 -translate-y-1/2 z-30 w-10 h-10 rounded-full bg-black/40 hover:bg-black/70 text-white backdrop-blur-md items-center justify-center transition-all duration-300 opacity-0 group-hover:opacity-100 hover:scale-110 shadow-lg cursor-pointer border border-white/20"
                                aria-label="Next Banner"
                            >
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" /></svg>
                            </button>
                        </>
                    )}

                    {/* Indicators Dots */}
                    {bannerList.length > 1 && (
                        <div className="absolute bottom-3 sm:bottom-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5 sm:gap-2 z-30">
                            {bannerList.map((_, idx) => (
                                <button 
                                    key={idx} 
                                    type="button"
                                    onClick={(e) => { e.stopPropagation(); setCurrentBanner(idx); }} 
                                    className={`h-1.5 sm:h-2 rounded-full transition-all duration-500 ease-[cubic-bezier(0.16,1,0.3,1)] cursor-pointer shadow-sm ${currentBanner === idx ? 'bg-orange-500 w-6 sm:w-8' : 'bg-white/40 hover:bg-white/70 w-2 sm:w-2.5'}`}
                                    aria-label={`Go to banner ${idx + 1}`}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                {/* Search & Actions */}
                <div className={`transition-all duration-300 max-w-2xl mx-auto ${isScrolled ? 'opacity-0 scale-95 pointer-events-none' : 'opacity-100 scale-100'}`}>
                    <form onSubmit={handleSearch} className="relative w-full">
                        <div className="flex items-center bg-white dark:bg-white/10 backdrop-blur-md p-1.5 rounded-full border border-gray-200 dark:border-white/20 focus-within:border-orange-500 dark:focus-within:border-white group transition-all shadow-lg shadow-orange-500/5 dark:shadow-xl focus-within:ring-2 focus-within:ring-orange-500/20">
                            <div className="pl-4 text-gray-400 group-focus-within:text-orange-500">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" value={searchTerm} onChange={handleInputChange} placeholder="Cari aplikasi premium, game, pulsa &amp; tagihan..." className="w-full px-4 py-3 bg-transparent text-gray-900 dark:text-gray-100 group-focus-within:text-black dark:group-focus-within:text-white focus:outline-none text-xs sm:text-sm font-bold placeholder-gray-400 dark:placeholder-gray-400" />
                            <button type="submit" className="bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white px-6 sm:px-8 py-3 rounded-full text-xs sm:text-sm font-black transition-all hover:shadow-lg hover:scale-105 shadow-md cursor-pointer shrink-0">
                                Cari
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}

