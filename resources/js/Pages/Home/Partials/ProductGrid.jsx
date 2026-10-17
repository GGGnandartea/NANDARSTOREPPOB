import React from 'react';
import { Link, router } from '@inertiajs/react';

export default function ProductGrid({
    productList,
    promo,
    auth,
    generateFakeOriginalPrice,
    setSearchTerm,
}) {
    if (productList.length === 0) {
        return (
            <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center shadow-sm">
                <div className="w-16 h-16 bg-gray-50 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg className="w-8 h-8 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <h3 className="text-lg font-extrabold text-black dark:text-white mb-1">Produk Kosong</h3>
                <p className="text-xs text-gray-500 dark:text-gray-400">Silakan ubah kata kunci pencarian Anda.</p>
                <button onClick={() => { setSearchTerm(''); router.get('/', {}, { preserveState: true, replace: true }); }} className="mt-5 px-5 py-2 rounded-full bg-orange-50 dark:bg-orange-900/30 text-orange-600 font-bold text-xs hover:bg-orange-100 transition-colors cursor-pointer">
                    Tampilkan Semua Produk
                </button>
            </div>
        );
    }

    return (
        <div className="grid grid-cols-2 min-[480px]:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4 mb-10">
            {productList.map((product) => {
                const isPromoActive = product.is_promo === 1 || product.is_promo === true || product.is_promo === '1';
                const fakePrice = isPromoActive ? generateFakeOriginalPrice(product.price) : 0;
                const badgeText = isPromoActive ? (promo?.badge_text ?? 'PROMO') : '';
                
                return (
                    <Link key={product.id} href={`/product/${product.id}`} className="bg-white dark:bg-gray-800 p-2 sm:p-2.5 rounded-[1.2rem] border border-gray-100 dark:border-gray-700/50 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl group flex flex-col h-full shadow-sm cursor-pointer relative overflow-hidden">
                        
                        {badgeText && badgeText.trim() !== '' && fakePrice > 0 && (
                            <div className="absolute -right-7 top-4 bg-red-500 text-white text-[9px] font-black py-0.5 px-8 rotate-45 shadow-sm z-20 uppercase">
                                {badgeText}
                            </div>
                        )}
                        
                        <div className="block relative aspect-square bg-gray-50 dark:bg-gray-900 rounded-[14px] overflow-hidden mb-3 p-3 sm:p-2 flex items-center justify-center">
                            {product.image ? (
                                <img src={product.image} alt={product.name} className="w-[85%] h-[85%] sm:w-[95%] sm:h-[95%] object-contain rounded-xl group-hover:scale-105 transition-transform duration-500" />
                            ) : (
                                <div className="w-full h-full flex flex-col items-center justify-center text-gray-300 dark:text-gray-700"><span className="font-extrabold text-2xl tracking-widest opacity-20">NS</span></div>
                            )}
                        </div>
                        
                        <div className="px-1 flex flex-col flex-1 pb-1">
                            <div className="mb-1.5 flex flex-wrap gap-1">
                                <span className={`inline-block text-[9px] font-extrabold px-1.5 py-0.5 rounded shadow-sm border ${product.stock <= 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800/50' : 'bg-gray-50 dark:bg-gray-700/50 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600'}`}>
                                    Stok: {product.stock}
                                </span>
                                {product.category && (
                                    <span className="inline-block text-[9px] font-extrabold px-1.5 py-0.5 rounded shadow-sm border bg-cyan-50 dark:bg-cyan-900/20 text-cyan-600 dark:text-cyan-400 border-cyan-200 dark:border-cyan-800/50 line-clamp-1 max-w-[60%]">
                                        {product.category}
                                    </span>
                                )}
                            </div>
                            
                            <h3 className="text-[11px] sm:text-sm font-extrabold text-black dark:text-white line-clamp-2 leading-tight mb-2 group-hover:text-orange-500 transition-colors">
                                {product.name}
                            </h3>
                            
                            <div className="mt-auto mb-3 flex flex-col justify-end">
                                {auth?.user?.role === 'reseller' && product.reseller_price ? (
                                    <>
                                        <div className="flex items-center gap-1 min-h-[15px]">
                                            <span className="text-[10px] text-gray-400 line-through">
                                                Rp {new Intl.NumberFormat('id-ID').format(product.price)}
                                            </span>
                                            <span className="text-[8px] font-black bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 px-1.5 py-0.5 rounded uppercase">
                                                👑 VIP
                                            </span>
                                        </div>
                                        <div className="font-extrabold text-amber-500 dark:text-amber-400 text-[13px] sm:text-[15px] leading-none mt-0.5">
                                            Rp {new Intl.NumberFormat('id-ID').format(product.reseller_price)}
                                        </div>
                                    </>
                                ) : (
                                    <>
                                        {fakePrice > 0 ? (
                                            <div className="text-[10px] text-gray-400 dark:text-gray-500 line-through decoration-red-500/50 decoration-2 font-semibold min-h-[15px]">
                                                Rp {new Intl.NumberFormat('id-ID').format(fakePrice)}
                                            </div>
                                        ) : (
                                            <div className="min-h-[15px]"></div>
                                        )}
                                        <div className="font-extrabold text-orange-500 dark:text-orange-500 text-[13px] sm:text-[15px] leading-none mt-0.5">
                                            Rp {new Intl.NumberFormat('id-ID').format(product.price)}
                                        </div>
                                        {product.reseller_price && (
                                            <div className="text-[10px] font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1 mt-1 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded border border-amber-200/60 dark:border-amber-800/40 w-fit">
                                                <span>👑</span> Reseller: Rp {new Intl.NumberFormat('id-ID').format(product.reseller_price)}
                                            </div>
                                        )}
                                    </>
                                )}
                            </div>

                            <div className="flex items-center gap-1.5 sm:gap-2">
                                <div className="flex-1 bg-gray-100 hover:bg-orange-500 dark:bg-gray-700 dark:hover:bg-orange-500 text-gray-800 dark:text-gray-200 hover:text-white dark:hover:text-white font-bold text-[10px] sm:text-xs py-2 sm:py-2.5 rounded-xl text-center transition-all shadow-sm">
                                    Beli Sekarang
                                </div>
                            </div>
                        </div>
                    </Link>
                );
            })}
        </div>
    );
}
