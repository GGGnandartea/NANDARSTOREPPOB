import React from 'react';
import { Link } from '@inertiajs/react';
import FavoriteButton from '../../../Components/FavoriteButton';
import { fmt } from './ProfileIcons';

export default function FavoritesTab({ products = [] }) {
    return <section>
        <h1 className="text-xl font-bold text-gray-900 dark:text-white">Produk Favorit</h1>
        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1 mb-5">Produk yang Anda simpan untuk dibeli nanti.</p>
        {!products.length ? <div className="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 text-center">
            <p className="text-gray-700 dark:text-gray-200">Belum ada produk favorit. Ketuk ikon hati pada produk untuk menyimpannya.</p>
            <Link href="/" className="inline-flex min-h-11 items-center mt-4 px-4 bg-orange-500 text-white rounded-xl font-semibold">Jelajahi Produk</Link>
        </div> : <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            {products.map(product => <article key={product.id} className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 flex flex-col gap-3">
                <div className="flex items-start gap-3">
                    {product.image && <img src={product.image} alt="" className="w-14 h-14 rounded-xl object-contain shrink-0" />}
                    <h2 className="flex-1 min-w-0 text-sm font-semibold text-gray-900 dark:text-white break-words">{product.name}</h2>
                    <FavoriteButton compact productId={product.id} productName={product.name} />
                </div>
                <p className="font-bold text-orange-600 dark:text-orange-400">{fmt.rp(product.price)}</p>
                {product.status === 'available' ? <Link href={`/product/${product.id}`} className="mt-auto min-h-11 flex items-center justify-center bg-orange-500 text-white rounded-xl text-sm font-semibold">{product.stock > 0 ? 'Lihat Produk' : 'Lihat Produk ? Stok Habis'}</Link> : <p className="mt-auto text-sm text-gray-600 dark:text-gray-300">Produk sedang tidak tersedia</p>}
            </article>)}
        </div>}
    </section>;
}
