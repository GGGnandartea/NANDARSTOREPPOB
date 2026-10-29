import FavoriteButton from '../Components/FavoriteButton';
import React, { useEffect, useState } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function ProductDetail({ product, auth }) {
    useEffect(() => {
        if (localStorage.getItem('theme') === 'dark' || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    }, []);

    const isOutOfStock = product.stock <= 0;
    const maxStock = product.stock > 0 ? product.stock : 1;
    const [quantity, setQuantity] = useState(1);

    const isReseller = auth?.user?.role === 'reseller' && Boolean(product.reseller_price);
    const unitPrice = isReseller ? Number(product.reseller_price) : Number(product.price);
    const subtotal = unitPrice * quantity;

    const handleDecrement = () => {
        setQuantity((prev) => Math.max(1, prev - 1));
    };

    const handleIncrement = () => {
        setQuantity((prev) => Math.min(maxStock, prev + 1));
    };

    const handleQtyInput = (e) => {
        let val = parseInt(e.target.value) || 1;
        if (val < 1) val = 1;
        if (val > maxStock) val = maxStock;
        setQuantity(val);
    };

    return (
        <div className="min-h-[100dvh] bg-gray-50 dark:bg-[#0f172a] font-jakarta pb-20 transition-colors duration-300 overflow-x-clip">
            <Head title={`${product.name} - PremiumKita`} />
            <style dangerouslySetInnerHTML={{__html: `@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'); .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }`}} />

            {/* Navbar */}
            <nav className="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-50">
                <div className="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/" className="p-2 -ml-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 transition-colors cursor-pointer">
                            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                        </Link>
                        <h1 className="font-extrabold text-gray-900 dark:text-white text-lg tracking-tight">Detail Produk</h1>
                    </div>
                </div>
            </nav>

            <div className="max-w-5xl mx-auto px-4 mt-6 sm:mt-8">
                <div className="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700/50 overflow-hidden flex flex-col md:flex-row">
                    
                    {/* Gambar Produk (Kiri) */}
                    <div className="w-full md:w-2/5 lg:w-1/3 bg-gray-50 dark:bg-gray-900 p-5 sm:p-8 flex items-center justify-center relative">
                        <div className="w-40 h-40 sm:w-64 sm:h-64 rounded-3xl overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700 relative group">
                            {product.image ? (
                                <img src={product.image} className="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt={product.name} />
                            ) : (
                                <div className="w-full h-full flex items-center justify-center bg-gray-200 dark:bg-gray-800 text-gray-400 font-black text-4xl">PK</div>
                            )}
                            <div className="absolute top-3 left-3">
                                <span className={`text-[10px] font-extrabold px-3 py-1.5 rounded-lg shadow-sm border backdrop-blur-md ${isOutOfStock ? 'bg-red-500/90 text-white border-red-400' : 'bg-gray-900/80 dark:bg-white/90 text-white dark:text-black border-transparent'}`}>
                                    Stok: {product.stock}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Informasi & Tombol Aksi (Kanan) */}
                    <div className="w-full md:w-3/5 lg:w-2/3 p-4 sm:p-10 flex flex-col">
                        
                        {/* Label Tipe */}
                        <div className="mb-3 flex flex-wrap items-center gap-2">
                            <span className="bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400 text-[10px] font-extrabold px-3 py-1.5 rounded border border-orange-200 dark:border-orange-800/50 uppercase tracking-widest">
                                {product.type === 'api' ? 'Proses Instan' : 'Manual Admin'}
                            </span>
                            {product.category && (
                                <span className="bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400 text-[10px] font-extrabold px-3 py-1.5 rounded border border-cyan-200 dark:border-cyan-800/50 uppercase tracking-widest">
                                    {product.category}
                                </span>
                            )}
                        </div>

                        <div className="my-3"><FavoriteButton productId={product.id} productName={product.name} /></div>
                        {/* Judul & Harga */}
                        <h2 className="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-tight mb-4">
                            {product.name}
                        </h2>
                        
                        <div className="mb-8 pb-6 border-b border-gray-100 dark:border-gray-700">
                            {auth?.user?.role === 'reseller' && product.reseller_price ? (
                                <div>
                                    <div className="flex items-center gap-2 mb-1">
                                        <span className="text-sm font-bold text-gray-400 line-through">
                                            Rp {Number(product.price).toLocaleString('id-ID')}
                                        </span>
                                        <span className="text-[10px] font-black bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 px-2.5 py-0.5 rounded-full shadow-sm uppercase">
                                            👑 HARGA RESELLER VIP
                                        </span>
                                    </div>
                                    <div className="text-3xl sm:text-4xl font-black text-amber-500">
                                        Rp {Number(product.reseller_price).toLocaleString('id-ID')}
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    <div className="text-3xl sm:text-4xl font-black text-orange-500">
                                        Rp {Number(product.price).toLocaleString('id-ID')}
                                    </div>
                                    {product.reseller_price && (
                                        <div className="mt-2.5 inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50 px-3 py-1.5 rounded-xl">
                                            <span className="text-sm">👑</span>
                                            <span className="text-xs font-bold text-gray-600 dark:text-gray-300">Harga Reseller:</span>
                                            <span className="text-xs font-black text-amber-600 dark:text-amber-400">Rp {Number(product.reseller_price).toLocaleString('id-ID')}</span>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Area Deskripsi Asli dari API */}
                        <div className="flex-1 mb-10">
                            <h3 className="text-sm font-extrabold text-gray-900 dark:text-white mb-3 uppercase tracking-widest flex items-center gap-2">
                                <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Deskripsi & Catatan
                            </h3>
                            <div className="text-sm text-gray-600 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-900/50 p-5 rounded-2xl border border-gray-100 dark:border-gray-700/50 whitespace-pre-wrap font-medium">
                                {product.description}
                            </div>
                        </div>

                        {/* Selector Kuantitas Pembelian */}
                        {!isOutOfStock && auth?.user?.role !== 'admin' && (
                            <div className="mb-6 p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/60 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div>
                                    <span className="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                                        Jumlah Pembelian
                                    </span>
                                    <span className="text-[11px] font-semibold text-gray-400">
                                        Maksimal sesuai sisa stok ({product.stock} pcs)
                                    </span>
                                </div>
                                <div className="flex items-center gap-4 w-full sm:w-auto justify-between sm:justify-end">
                                    <div className="flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-1 shadow-sm">
                                        <button
                                            type="button"
                                            onClick={handleDecrement}
                                            disabled={quantity <= 1}
                                            className="w-9 h-9 rounded-lg flex items-center justify-center font-black text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition text-lg"
                                        >
                                            -
                                        </button>
                                        <input
                                            type="number"
                                            min="1"
                                            max={maxStock}
                                            value={quantity}
                                            onChange={handleQtyInput}
                                            className="w-14 text-center font-black text-base bg-transparent text-gray-900 dark:text-white outline-none"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleIncrement}
                                            disabled={quantity >= maxStock}
                                            className="w-9 h-9 rounded-lg flex items-center justify-center font-black text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-30 disabled:cursor-not-allowed transition text-lg"
                                        >
                                            +
                                        </button>
                                    </div>
                                    <div className="text-right">
                                        <span className="block text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">Subtotal</span>
                                        <span className="text-base sm:text-lg font-black text-orange-500">
                                            Rp {subtotal.toLocaleString('id-ID')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* Tombol Aksi Fokus (Kembali & Beli) */}
                        <div className="mt-auto flex flex-col sm:flex-row gap-3">
                            <Link href="/" className="flex items-center justify-center gap-2 px-6 py-3.5 sm:py-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-extrabold rounded-2xl transition-colors cursor-pointer w-full sm:w-auto shrink-0 shadow-sm">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                <span className="hidden sm:block">Kembali</span>
                            </Link>

                            {auth?.user?.role === 'admin' ? (
                                <div className="flex-1 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-900 text-white p-3.5 sm:p-4 rounded-2xl border border-slate-700">
                                    <div className="flex items-center gap-2.5">
                                        <span className="text-xl">👑</span>
                                        <div className="text-left">
                                            <p className="text-xs font-black text-orange-400 uppercase tracking-wider">Mode Pantau Admin</p>
                                            <p className="text-[11px] text-slate-300">Akun Admin tidak dapat checkout / belanja.</p>
                                        </div>
                                    </div>
                                    <Link 
                                        href="/admin/dashboard" 
                                        className="w-full sm:w-auto px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-xs rounded-xl transition text-center shrink-0"
                                    >
                                        Dashboard Admin →
                                    </Link>
                                </div>
                            ) : isOutOfStock ? (
                                <button disabled className="w-full bg-gray-200 dark:bg-gray-700 text-gray-400 dark:text-gray-500 font-extrabold py-4 rounded-2xl cursor-not-allowed">
                                    Stok Habis Sementara
                                </button>
                            ) : (
                                <Link 
                                    href={`/checkout/${product.id}?qty=${quantity}`} 
                                    className="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-400 hover:to-orange-500 text-white font-extrabold py-3.5 sm:py-4 rounded-2xl shadow-lg shadow-orange-500/30 transition-all hover:-translate-y-1 cursor-pointer text-sm sm:text-base"
                                >
                                    Gas Beli Langsung ({quantity} pcs)
                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                                </Link>
                            )}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    );
}