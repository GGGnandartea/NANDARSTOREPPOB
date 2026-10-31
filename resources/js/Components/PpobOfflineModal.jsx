import React, { useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';

const STATUS_THEMES = {
    offline: {
        badgeBg: 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border-rose-500/30',
        iconWrap: 'bg-gradient-to-br from-rose-500 to-red-600 text-white shadow-lg shadow-rose-500/30',
        topGlow: 'from-rose-500/20 via-red-500/5 to-transparent',
        dot: 'bg-rose-500',
        etaBox: 'bg-rose-50 dark:bg-rose-950/30 border-rose-200/80 dark:border-rose-800/40 text-rose-800 dark:text-rose-200',
    },
    maintenance: {
        badgeBg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
        iconWrap: 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30',
        topGlow: 'from-amber-500/20 via-orange-500/5 to-transparent',
        dot: 'bg-amber-500',
        etaBox: 'bg-amber-50 dark:bg-amber-950/30 border-amber-200/80 dark:border-amber-800/40 text-amber-800 dark:text-amber-200',
    },
    gangguan: {
        badgeBg: 'bg-red-500/15 text-red-600 dark:text-red-400 border-red-500/30',
        iconWrap: 'bg-gradient-to-br from-red-600 to-rose-700 text-white shadow-lg shadow-red-600/30',
        topGlow: 'from-red-500/20 via-rose-500/5 to-transparent',
        dot: 'bg-red-500',
        etaBox: 'bg-red-50 dark:bg-red-950/30 border-red-200/80 dark:border-red-800/40 text-red-800 dark:text-red-200',
    },
    restock: {
        badgeBg: 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30',
        iconWrap: 'bg-gradient-to-br from-blue-500 to-cyan-600 text-white shadow-lg shadow-blue-500/30',
        topGlow: 'from-blue-500/20 via-cyan-500/5 to-transparent',
        dot: 'bg-blue-500',
        etaBox: 'bg-blue-50 dark:bg-blue-950/30 border-blue-200/80 dark:border-blue-800/40 text-blue-800 dark:text-blue-200',
    },
    scheduled_cutoff: {
        badgeBg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30',
        iconWrap: 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/30',
        topGlow: 'from-amber-500/20 via-orange-500/5 to-transparent',
        dot: 'bg-amber-500',
        etaBox: 'bg-amber-50 dark:bg-amber-950/30 border-amber-200/80 dark:border-amber-800/40 text-amber-800 dark:text-amber-200',
    },
};

function StatusIcon({ reason }) {
    if (reason === 'maintenance') {
        return (
            <svg className="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        );
    }

    if (reason === 'gangguan') {
        return (
            <svg className="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                />
            </svg>
        );
    }

    if (reason === 'restock') {
        return (
            <svg className="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
            </svg>
        );
    }

    if (reason === 'scheduled_cutoff') {
        return (
            <svg className="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
        );
    }

    return (
        <svg className="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth="2"
                d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"
            />
        </svg>
    );
}

export default function PpobOfflineModal({
    open,
    onClose,
    status: propStatus,
    targetHref = '/ppob',
    isOnPpobPage = false,
}) {
    const { props } = usePage();
    const status = propStatus || props?.store?.ppob_status || {};
    const adminWaRaw = props?.store?.admin_whatsapp || '082317458496';
    const cleanWa = String(adminWaRaw).replace(/[^0-9]/g, '').replace(/^0/, '62');
    const waHref = `https://wa.me/${cleanWa}?text=${encodeURIComponent(
        'Halo Admin, saya ingin menanyakan informasi layanan PPOB yang sedang ' + (status?.status_badge || 'Offline') + '.'
    )}`;

    useEffect(() => {
        if (!open) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') onClose?.();
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [open, onClose]);

    if (!open) return null;

    const reason = status?.reason || status?.status_mode || 'offline';
    const theme = STATUS_THEMES[reason] || STATUS_THEMES.offline;
    const badgeLabel = status?.status_badge || 'SEDANG OFFLINE';
    const title = status?.status_title || 'Layanan PPOB Sedang Offline Sementara';
    const message =
        status?.message ||
        'Mohon maaf, layanan transaksi PPOB (Pulsa, Paket Data, Token PLN & Tagihan) saat ini sedang dinonaktifkan sementara. Pembelian Produk Aplikasi Premium tetap buka 24 jam.';
    const eta = status?.status_eta || 'Segera Kembali Aktif';
    const allowCatalogBrowse = status?.allow_catalog_browse !== false;

    const statusOptions = [
        { key: 'offline', label: 'Offline' },
        { key: 'maintenance', label: 'Maintenance' },
        { key: 'gangguan', label: 'Gangguan Pusat' },
        { key: 'restock', label: 'Restock Saldo' },
        { key: 'scheduled_cutoff', label: 'Cut-Off Harian' },
    ];

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="ppob-status-modal-title"
            className="fixed inset-0 z-[120] flex items-end sm:items-center justify-center p-0 sm:p-4"
        >
            {/* Backdrop */}
            <div
                onClick={onClose}
                className="fixed inset-0 bg-slate-950/75 backdrop-blur-xs transition-opacity"
            />

            {/* Modal Panel */}
            <div className="relative z-10 w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl bg-white dark:bg-[#0f172a] border-t sm:border border-gray-200 dark:border-white/10 shadow-2xl overflow-hidden ppob-fade-in max-h-[92dvh] flex flex-col">
                {/* Top Ambient Glow */}
                <div className={`pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b ${theme.topGlow}`} />

                {/* Mobile Drag Handle */}
                <div className="sm:hidden pt-3 pb-1 flex justify-center relative z-10">
                    <span className="w-11 h-1.5 rounded-full bg-gray-300 dark:bg-white/20" />
                </div>

                {/* Close Button */}
                <button
                    type="button"
                    onClick={onClose}
                    aria-label="Tutup pemberitahuan"
                    className="!min-h-0 absolute top-3.5 right-3.5 sm:top-4 sm:right-4 z-20 w-8 h-8 rounded-full bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 text-gray-600 dark:text-gray-300 flex items-center justify-center transition cursor-pointer"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div className="relative z-10 p-5 sm:p-7 overflow-y-auto space-y-5">
                    {/* Icon + Status Badge + Title */}
                    <div className="flex flex-col items-center text-center space-y-3">
                        <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center ${theme.iconWrap}`}>
                            <StatusIcon reason={reason} />
                        </div>

                        <span
                            className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider border ${theme.badgeBg}`}
                        >
                            <span className={`w-2 h-2 rounded-full animate-pulse ${theme.dot}`} />
                            <span>STATUS PPOB: {badgeLabel}</span>
                        </span>

                        <h3
                            id="ppob-status-modal-title"
                            className="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight leading-snug"
                        >
                            {title}
                        </h3>
                    </div>

                    {/* Visual Status Pill Indicator (Menunjukkan posisi status saat ini secara jelas) */}
                    <div className="flex flex-wrap items-center justify-center gap-1.5">
                        {statusOptions.map((item) => {
                            const isActive = reason === item.key;
                            return (
                                <span
                                    key={item.key}
                                    className={`px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wide border transition ${
                                        isActive
                                            ? `${theme.badgeBg} ring-1 ring-current font-black`
                                            : 'bg-gray-100/70 dark:bg-white/5 text-gray-400 dark:text-gray-500 border-gray-200/60 dark:border-white/5 opacity-60'
                                    }`}
                                >
                                    {isActive ? `● ${item.label}` : item.label}
                                </span>
                            );
                        })}
                    </div>

                    {/* Main Message Box */}
                    <div className="p-4 rounded-2xl bg-gray-50 dark:bg-slate-900/90 border border-gray-200/80 dark:border-white/10 text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-300 leading-relaxed text-center">
                        {message}
                    </div>

                    {/* Info Grid: Estimasi & Status Produk Lain */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div className={`p-3 rounded-2xl border ${theme.etaBox}`}>
                            <span className="text-[10px] font-black uppercase tracking-wider opacity-75 block">
                                Estimasi / Jadwal Aktif
                            </span>
                            <span className="text-xs font-black block mt-0.5">{eta}</span>
                        </div>

                        <div className="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200/80 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-200">
                            <span className="text-[10px] font-black uppercase tracking-wider opacity-75 block">
                                Katalog Aplikasi Premium
                            </span>
                            <span className="text-xs font-black flex items-center gap-1.5 mt-0.5">
                                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                                <span>Tetap Buka 24 Jam</span>
                            </span>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="space-y-2.5 pt-1">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            {isOnPpobPage ? (
                                <Link
                                    href="/"
                                    className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-xs sm:text-sm shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2 transition"
                                >
                                    <span>← Kembali ke Beranda</span>
                                </Link>
                            ) : (
                                <button
                                    type="button"
                                    onClick={onClose}
                                    className="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-xs sm:text-sm shadow-lg shadow-orange-500/20 flex items-center justify-center gap-2 transition cursor-pointer"
                                >
                                    <span>Belanja Produk Premium</span>
                                </button>
                            )}

                            <a
                                href={waHref}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2 transition"
                            >
                                <span>Tanya Admin CS (WA)</span>
                            </a>
                        </div>

                        {allowCatalogBrowse && (
                            <div className="text-center pt-1">
                                {isOnPpobPage ? (
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="!min-h-0 py-1.5 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white underline transition cursor-pointer"
                                    >
                                        Tutup &amp; Tetap Lihat Daftar Harga Katalog (Mode Baca)
                                    </button>
                                ) : (
                                    <Link
                                        href={targetHref || '/ppob'}
                                        onClick={onClose}
                                        className="inline-block py-1.5 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 underline transition"
                                    >
                                        Tetap Lihat Daftar Harga Katalog PPOB (Mode Baca) →
                                    </Link>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

