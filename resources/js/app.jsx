import './bootstrap';
import '../css/app.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

class AppErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, errorMessage: '' };
    }

    static getDerivedStateFromError(error) {
        return {
            hasError: true,
            errorMessage: error?.message || 'Terjadi kesalahan saat menampilkan halaman.',
        };
    }

    componentDidCatch(error, info) {
        console.error('[AppErrorBoundary]', error, info);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-[100dvh] bg-[#0b0f19] text-white flex items-center justify-center p-4 font-sans">
                    <div className="w-full max-w-md bg-[#111827] border border-white/10 rounded-3xl p-6 text-center shadow-2xl space-y-4">
                        <div className="w-14 h-14 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 flex items-center justify-center mx-auto font-black text-xl">
                            ✓
                        </div>
                        <div className="space-y-1.5">
                            <h2 className="text-lg font-black text-white">Transaksi &amp; Data Anda Aman</h2>
                            <p className="text-xs text-gray-400 leading-relaxed">
                                Tampilan sedang dimuat ulang untuk menampilkan status terbaru pesanan Anda.
                            </p>
                        </div>
                        <div className="flex flex-col sm:flex-row gap-2.5 pt-1">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="flex-1 py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs cursor-pointer transition"
                            >
                                Muat Ulang Halaman
                            </button>
                            <a
                                href="/profile?tab=ppob"
                                className="flex-1 py-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-gray-200 font-bold text-xs flex items-center justify-center transition"
                            >
                                Buka Riwayat Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

createInertiaApp({
    title: (title) => `${title} - PremiumKita`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(
            <AppErrorBoundary>
                <App {...props} />
            </AppErrorBoundary>
        );
    },
    progress: {
        color: '#f97316',
        showSpinner: true,
    },
});
