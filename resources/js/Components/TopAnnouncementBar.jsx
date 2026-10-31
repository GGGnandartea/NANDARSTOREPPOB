import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';

const THEME_STYLES = {
    promo: {
        wrapper: 'bg-gradient-to-r from-orange-600 via-amber-500 to-orange-600 text-white border-b border-orange-400/30',
        badge: 'bg-black/25 text-white border border-white/25',
        dot: 'bg-amber-200',
        fadeLeft: 'from-orange-600 to-transparent',
        fadeRight: 'from-orange-600 to-transparent',
    },
    info: {
        wrapper: 'bg-gradient-to-r from-blue-600 via-cyan-600 to-blue-600 text-white border-b border-cyan-400/30',
        badge: 'bg-black/25 text-white border border-white/25',
        dot: 'bg-cyan-200',
        fadeLeft: 'from-blue-600 to-transparent',
        fadeRight: 'from-blue-600 to-transparent',
    },
    warning: {
        wrapper: 'bg-gradient-to-r from-rose-600 via-red-600 to-rose-600 text-white border-b border-rose-400/30',
        badge: 'bg-black/25 text-white border border-white/25',
        dot: 'bg-rose-200',
        fadeLeft: 'from-rose-600 to-transparent',
        fadeRight: 'from-rose-600 to-transparent',
    },
    success: {
        wrapper: 'bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 text-white border-b border-emerald-400/30',
        badge: 'bg-black/25 text-white border border-white/25',
        dot: 'bg-emerald-200',
        fadeLeft: 'from-emerald-600 to-transparent',
        fadeRight: 'from-emerald-600 to-transparent',
    },
};

export default function TopAnnouncementBar({ announcement: propAnnouncement }) {
    const { props } = usePage();
    const announcement = propAnnouncement || props?.store?.announcement;

    const isEnabled = Boolean(announcement?.enabled && announcement?.text?.trim());
    const text = (announcement?.text || '').trim();
    const badge = (announcement?.badge || 'INFO').trim();
    const themeKey = THEME_STYLES[announcement?.theme] ? announcement.theme : 'promo';
    const speedKey = ['slow', 'normal', 'fast'].includes(announcement?.speed) ? announcement.speed : 'normal';
    const link = (announcement?.link || '').trim();

    const storageKey = `pk_ann_dismissed_${btoa(encodeURIComponent(text)).slice(0, 24)}`;
    const [dismissed, setDismissed] = useState(false);

    useEffect(() => {
        if (!text || typeof window === 'undefined') return;
        try {
            setDismissed(sessionStorage.getItem(storageKey) === '1');
        } catch {
            setDismissed(false);
        }
    }, [storageKey, text]);

    if (!isEnabled || dismissed) {
        return null;
    }

    const theme = THEME_STYLES[themeKey];
    const speedClass =
        speedKey === 'slow'
            ? 'announcement-marquee-slow'
            : speedKey === 'fast'
              ? 'announcement-marquee-fast'
              : 'announcement-marquee-normal';

    const handleDismiss = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDismissed(true);
        try {
            sessionStorage.setItem(storageKey, '1');
        } catch {
            // ignore storage errors
        }
    };

    const repeatedItems = [0, 1, 2, 3];

    return (
        <div
            role="region"
            aria-label="Pengumuman Toko"
            className={`relative z-50 w-full overflow-hidden select-none shadow-xs ${theme.wrapper}`}
        >
            <div className="max-w-7xl mx-auto px-2.5 sm:px-6 h-8 sm:h-9 flex items-center justify-between gap-2 sm:gap-3">
                {/* Left Badge */}
                <div className="shrink-0 flex items-center gap-1.5 z-10">
                    <span
                        className={`inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] sm:text-[11px] font-black uppercase tracking-wider shadow-2xs ${theme.badge}`}
                    >
                        <span className={`w-1.5 h-1.5 rounded-full animate-pulse ${theme.dot}`} />
                        <svg
                            className="w-3 h-3 shrink-0 hidden sm:inline-block"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2.4"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"
                            />
                        </svg>
                        <span>{badge}</span>
                    </span>
                </div>

                {/* Scrolling Marquee Viewport */}
                <div className="relative flex-1 min-w-0 overflow-hidden h-full flex items-center">
                    <div
                        className={`pointer-events-none absolute inset-y-0 left-0 w-4 bg-gradient-to-r ${theme.fadeLeft} z-10`}
                    />
                    <div
                        className={`pointer-events-none absolute inset-y-0 right-0 w-4 bg-gradient-to-l ${theme.fadeRight} z-10`}
                    />

                    {link ? (
                        <a
                            href={link}
                            target={link.startsWith('http') ? '_blank' : undefined}
                            rel={link.startsWith('http') ? 'noopener noreferrer' : undefined}
                            className={`announcement-marquee-track ${speedClass} items-center hover:underline`}
                        >
                            {[0, 1].map((groupIndex) => (
                                <div
                                    key={groupIndex}
                                    aria-hidden={groupIndex === 1 ? 'true' : undefined}
                                    className="flex items-center shrink-0"
                                >
                                    {repeatedItems.map((itemIdx) => (
                                        <span
                                            key={itemIdx}
                                            className="inline-flex items-center text-[11px] sm:text-xs font-bold tracking-wide whitespace-nowrap pr-10 sm:pr-14"
                                        >
                                            <span>{text}</span>
                                            <span className="mx-3 opacity-60">•</span>
                                            <span className="underline font-black text-white/95">Klik di sini →</span>
                                        </span>
                                    ))}
                                </div>
                            ))}
                        </a>
                    ) : (
                        <div className={`announcement-marquee-track ${speedClass} items-center`}>
                            {[0, 1].map((groupIndex) => (
                                <div
                                    key={groupIndex}
                                    aria-hidden={groupIndex === 1 ? 'true' : undefined}
                                    className="flex items-center shrink-0"
                                >
                                    {repeatedItems.map((itemIdx) => (
                                        <span
                                            key={itemIdx}
                                            className="inline-flex items-center text-[11px] sm:text-xs font-bold tracking-wide whitespace-nowrap pr-10 sm:pr-14"
                                        >
                                            <span>{text}</span>
                                            <span className="ml-10 sm:ml-14 opacity-50">•</span>
                                        </span>
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Right Action / Dismiss */}
                <div className="shrink-0 flex items-center gap-1.5 z-10">
                    {link && (
                        <a
                            href={link}
                            target={link.startsWith('http') ? '_blank' : undefined}
                            rel={link.startsWith('http') ? 'noopener noreferrer' : undefined}
                            className="hidden md:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white text-slate-900 hover:bg-white/90 text-[10px] font-black uppercase tracking-wider transition shadow-2xs"
                        >
                            <span>Lihat</span>
                            <span>→</span>
                        </a>
                    )}
                    <button
                        type="button"
                        onClick={handleDismiss}
                        title="Tutup pengumuman"
                        aria-label="Tutup pengumuman"
                        className="!min-h-0 w-6 h-6 rounded-full bg-black/20 hover:bg-black/35 text-white/90 hover:text-white flex items-center justify-center transition cursor-pointer"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    );
}

