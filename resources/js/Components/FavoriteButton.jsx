import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function FavoriteButton({ productId, productName, compact = false }) {
    const { auth, favoriteProductIds = [] } = usePage().props;
    const saved = favoriteProductIds.some(id => String(id) === String(productId));
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const toggle = (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (busy) return;
        if (!auth?.user) { router.visit('/login'); return; }
        setBusy(true);
        setError('');
        router.visit(`/favorites/${productId}`, {
            method: saved ? 'delete' : 'put', preserveScroll: true, preserveState: true,
            onError: () => setError('Gagal menyimpan favorit. Coba lagi.'),
            onFinish: () => setBusy(false),
        });
    };
    return (
        <div>
            <button type="button" onClick={toggle} disabled={busy} aria-pressed={saved}
                aria-label={`${saved ? 'Hapus dari' : 'Simpan ke'} favorit: ${productName}`}
                title={auth?.user ? (saved ? 'Hapus favorit' : 'Simpan favorit') : 'Masuk untuk menyimpan favorit'}
                className={`min-h-11 min-w-11 inline-flex items-center justify-center gap-2 rounded-xl border bg-white dark:bg-gray-900 shadow-sm disabled:opacity-50 ${compact ? '' : 'px-3'} ${saved ? 'text-rose-500 border-rose-200 dark:border-rose-800' : 'text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700'}`}>
                <svg aria-hidden="true" className="w-5 h-5" viewBox="0 0 24 24" fill={saved ? 'currentColor' : 'none'} stroke="currentColor" strokeWidth="1.8" strokeLinejoin="round"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 00-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 000-7.8z" /></svg>
                {!compact && <span className="text-sm font-medium">{busy ? 'Menyimpan...' : saved ? 'Tersimpan' : 'Simpan Favorit'}</span>}
            </button>
            {error && <p role="alert" className="text-xs text-red-600 bg-white dark:bg-gray-900 rounded-lg p-2">{error}</p>}
        </div>
    );
}
