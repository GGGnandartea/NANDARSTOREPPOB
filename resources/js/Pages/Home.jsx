import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Home({ products = [], categories = [], manualCategories = [], filters = {}, terms, promo = { markup_percent: 25, badge_text: 'PROMO' }, banners: serverBanners = [], adminWhatsapp = '6282317458496', whatsappGroupLink = 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H', recentPurchases = [], topSellingToday = [] }) {
    const { auth, store } = usePage().props;
    const effectiveAdminWa = store?.admin_whatsapp || adminWhatsapp || '6282317458496';

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [isDark, setIsDark] = useState(false);
    const [showTerms, setShowTerms] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);
    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showProfileMenu, setShowProfileMenu] = useState(false);
    
    const [showTrackModal, setShowTrackModal] = useState(false);
    const [trackOrderId, setTrackOrderId] = useState('');
    const [trackResult, setTrackResult] = useState(null);
    const [trackLoading, setTrackLoading] = useState(false);

    const [showFabMenu, setShowFabMenu] = useState(false);
    
    const [currentBanner, setCurrentBanner] = useState(0);
    const [isHoveredBanner, setIsHoveredBanner] = useState(false);
    const [touchStartX, setTouchStartX] = useState(null);
    const [touchEndX, setTouchEndX] = useState(null);
    const [showNotif, setShowNotif] = useState(false);
    const [activeNotif, setActiveNotif] = useState(null);

    // State Realtime Produk Paling Banyak Dibeli Hari Ini
    const [liveTopSelling, setLiveTopSelling] = useState(topSellingToday || []);

    useEffect(() => {
        if (topSellingToday && topSellingToday.length > 0) {
            setLiveTopSelling(topSellingToday);
        }
    }, [topSellingToday]);

    const defaultBanners = [
        { id: 1, title: 'Beli App Premium Lebih Hemat', subtitle: 'Netflix, Spotify, Canva Premium dengan harga termurah & garansi penuh.', badge: 'FLASH SALE', bg_gradient: 'from-orange-600 to-red-600', image: null, image_mobile: null },
        { id: 2, title: 'Aplikasi Premium Bergaransi', subtitle: 'YouTube Premium, CapCut Pro, ChatGPT Plus proses instan otomatis 24 jam.', badge: 'PREMIUM', bg_gradient: 'from-blue-600 to-indigo-700', image: null, image_mobile: null },
        { id: 3, title: 'Promo Pembayaran QRIS', subtitle: 'Bisa bayar pakai GoPay, OVO, Dana, ShopeePay & Semua Mobile Banking.', badge: 'PROMO', bg_gradient: 'from-emerald-500 to-teal-600', image: null, image_mobile: null }
    ];

    const bannerList = (serverBanners && serverBanners.length > 0) ? serverBanners : defaultBanners;
    
    const brandContainerRef = useRef(null);
    const typeContainerRef = useRef(null);
    const topSellingContainerRef = useRef(null);
    const profileMenuRef = useRef(null);
    
    const productList = Array.isArray(products) ? products : (products?.data || []);
    const categoryList = Array.isArray(categories) ? categories : (categories?.data || []);
    
    useEffect(() => {
        const theme = localStorage.getItem('theme');
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            setIsDark(true); document.documentElement.classList.add('dark');
        } else {
            setIsDark(false); document.documentElement.classList.remove('dark');
        }

        const handleScroll = () => { setIsScrolled(window.scrollY > 200); };
        window.addEventListener('scroll', handleScroll);
        
        const handleClickOutside = (e) => {
            if (profileMenuRef.current && !profileMenuRef.current.contains(e.target)) {
                setShowProfileMenu(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        
        // Polling realtime ringan untuk produk & penjualan hari ini
        const silentRefresh = setInterval(() => {
            router.reload({ only: ['products', 'topSellingToday'], preserveState: true, preserveScroll: true, replace: true });
        }, 12000);

        return () => {
            window.removeEventListener('scroll', handleScroll);
            document.removeEventListener('mousedown', handleClickOutside);
            clearInterval(silentRefresh);
        };
    }, []);

    // Polling background mandiri untuk live update data realtime harian
    useEffect(() => {
        let isMounted = true;
        const fetchLiveTopSelling = async () => {
            try {
                const res = await fetch('/api/top-selling-today');
                if (res.ok) {
                    const json = await res.json();
                    if (isMounted && json?.status === 'success' && Array.isArray(json?.data) && json.data.length > 0) {
                        setLiveTopSelling(json.data);
                    }
                }
            } catch (err) {}
        };

        const timer = setInterval(fetchLiveTopSelling, 15000);
        return () => {
            isMounted = false;
            clearInterval(timer);
        };
    }, []);

    useEffect(() => {
        if (isHoveredBanner || bannerList.length <= 1) return;
        const interval = setInterval(() => {
            setCurrentBanner((prev) => (prev + 1) % bannerList.length);
        }, 5000);
        return () => clearInterval(interval);
    }, [bannerList.length, isHoveredBanner]);

    const handlePrevBanner = () => {
        setCurrentBanner((prev) => (prev - 1 + bannerList.length) % bannerList.length);
    };

    const handleNextBanner = () => {
        setCurrentBanner((prev) => (prev + 1) % bannerList.length);
    };

    const handleTouchStart = (e) => {
        setTouchStartX(e.targetTouches[0].clientX);
    };

    const handleTouchMove = (e) => {
        setTouchEndX(e.targetTouches[0].clientX);
    };

    const handleTouchEnd = () => {
        if (!touchStartX || !touchEndX) return;
        const distance = touchStartX - touchEndX;
        if (distance > 40) {
            setCurrentBanner((prev) => (prev + 1) % bannerList.length);
        } else if (distance < -40) {
            setCurrentBanner((prev) => (prev - 1 + bannerList.length) % bannerList.length);
        }
        setTouchStartX(null);
        setTouchEndX(null);
    };

    const handleBannerClick = (banner) => {
        if (!banner.link_url) return;
        if (banner.link_url.startsWith('http://') || banner.link_url.startsWith('https://')) {
            window.open(banner.link_url, '_blank');
        } else {
            router.visit(banner.link_url);
        }
    };

    const purchaseList = (recentPurchases && recentPurchases.length > 0)
        ? recentPurchases
        : [
            { name: 'Budi Santoso', product: 'Netflix Premium 1 Bulan', time_ago: 'Baru saja' },
            { name: 'Rian Pratama', product: 'Spotify Premium Individual', time_ago: '5 menit yang lalu' },
            { name: 'Sisca Dewi', product: 'Canva Pro 1 Tahun', time_ago: '12 menit yang lalu' },
            { name: 'Dimas Kurnia', product: 'YouTube Premium 1 Bulan', time_ago: '25 menit yang lalu' },
            { name: 'Anisa Rahma', product: 'CapCut Pro 1 Bulan', time_ago: '35 menit yang lalu' },
        ];

    const purchaseIndexRef = useRef(0);

    useEffect(() => {
        if (!purchaseList.length) return;

        const showNextPurchase = () => {
            const item = purchaseList[purchaseIndexRef.current % purchaseList.length];
            purchaseIndexRef.current = (purchaseIndexRef.current + 1) % purchaseList.length;
            setActiveNotif(item);
            setShowNotif(true);

            setTimeout(() => {
                setShowNotif(false);
            }, 4500);
        };

        const initialTimer = setTimeout(() => {
            showNextPurchase();
        }, 2000);

        const interval = setInterval(() => {
            showNextPurchase();
        }, 8000);

        return () => {
            clearTimeout(initialTimer);
            clearInterval(interval);
        };
    }, [recentPurchases]);

    const toggleDarkMode = () => {
        if (isDark) {
            document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); setIsDark(false);
        } else {
            document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); setIsDark(true);
        }
    };

    const handleInputChange = (e) => {
        const value = e.target.value; setSearchTerm(value);
        if (value === '') {
            router.get('/', {}, { preserveState: true, replace: true, preserveScroll: true });
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const params = {};
        if (searchTerm) params.search = searchTerm;
        router.get('/', params, { preserveState: true, preserveScroll: true });
    };

    const handleCategoryBrand = (cat) => {
        const params = {};
        if (searchTerm) params.search = searchTerm;
        if (cat) params.category = cat; 
        router.get('/', params, { preserveState: true, preserveScroll: true });
        setShowCategoryModal(false);
    };

    const handleCategoryTipe = (tipe) => {
        const params = {};
        if (searchTerm) params.search = searchTerm;
        if (tipe) params.tipe = tipe;
        router.get('/', params, { preserveState: true, preserveScroll: true });
    };

    const scrollSlider = (ref, direction) => {
        if (ref.current) {
            const scrollAmount = direction === 'left' ? -250 : 250;
            ref.current.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    };

    const scrollToSection = (id) => {
        const el = document.getElementById(id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth' });
        }
    };

    const generateFakeOriginalPrice = (price) => {
        const numPrice = Number(price);
        const markup = Number(promo?.markup_percent ?? 25);
        if (markup <= 0) return 0;
        return Math.floor(numPrice * (1 + (markup / 100)));
    };

    const handleTrackOrder = async (e) => {
        e.preventDefault();
        if(!trackOrderId) return;
        setTrackLoading(true); setTrackResult(null);
        try {
            const res = await fetch(`/checkout/status/${trackOrderId}`);
            const data = await res.json();
            setTrackResult(data.status);
        } catch (error) {
            setTrackResult('ERROR');
        }
        setTrackLoading(false);
    };

    return (
        <div className="min-h-screen flex flex-col bg-gray-50 dark:bg-[#0f172a] transition-colors duration-300 font-jakarta pb-20 sm:pb-0">
            <Head>
                <title>{`${store?.name || 'NandarStore'} - Platform Digital & Akun Premium`}</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
                <style>{`
                    .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }
                    .hide-scroll::-webkit-scrollbar { display: none; }
                    .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
                    .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); }
                    .dark .glass-nav { background: rgba(15, 23, 42, 0.9); }
                `}</style>
            </Head>

            {/* ADMIN MONITOR BAR */}
            {auth?.user?.role === 'admin' && (
                <div className="bg-slate-900 text-slate-200 text-xs py-2 px-4 border-b border-slate-800 shrink-0 sticky top-0 z-50">
                    <div className="max-w-7xl mx-auto flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2">
                            <span className="text-base">👑</span>
                            <span className="font-black text-orange-400">Mode Pantau Admin:</span>
                            <span className="text-[11px] text-slate-300 hidden sm:inline">Akun profil, saldo pelanggan, checkout, dan top-up dinonaktifkan.</span>
                        </div>
                        <Link 
                            href="/admin/dashboard" 
                            className="inline-flex items-center gap-1.5 px-3 py-1 bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-[11px] rounded-lg transition shrink-0 shadow-sm"
                        >
                            <span>Dashboard Admin</span>
                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </Link>
                    </div>
                </div>
            )}

            {/* NAVBAR */}
            <nav className={`glass-nav sticky ${auth?.user?.role === 'admin' ? 'top-8' : 'top-0'} z-40 transition-all duration-300 ${isScrolled ? 'border-b border-gray-200 dark:border-gray-800 shadow-sm' : 'border-b border-transparent dark:border-transparent'}`}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
                    
                    <div className="flex-1 h-full relative overflow-hidden flex items-center">
                        <Link href="/" className={`absolute left-0 flex items-center gap-2 cursor-pointer transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] ${isScrolled ? '-translate-y-16 opacity-0 sm:translate-y-0 sm:opacity-100 sm:relative sm:mr-6' : 'translate-y-0 opacity-100 relative sm:mr-6'}`}>
                            {store?.logo ? (
                                <div className="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-white/10 p-0.5 flex items-center justify-center shadow-md shrink-0 overflow-hidden">
                                    <img src={store.logo} alt={store?.name || 'NandarStore'} className="w-full h-full object-contain rounded-lg" />
                                </div>
                            ) : (
                                <div className="w-8 h-8 sm:w-9 sm:h-9 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-bold shadow-md shrink-0 text-sm">NS</div>
                            )}
                            <span className="text-xl font-extrabold text-black dark:text-white tracking-tight hidden md:block shrink-0">{store?.name || 'NandarStore'}</span>
                        </Link>

                        <div className={`absolute left-0 w-full max-w-xl transition-all duration-500 ease-[cubic-bezier(0.4,0,0.2,1)] ${isScrolled ? 'translate-y-0 opacity-100 pointer-events-auto sm:relative' : 'translate-y-16 opacity-0 pointer-events-none sm:-translate-y-4 sm:absolute'}`}>
                            <form onSubmit={handleSearch} className="relative w-full">
                                <input type="text" value={searchTerm} onChange={handleInputChange} placeholder="Cari layanan..." className="w-full pl-4 pr-12 py-2 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-orange-500 rounded-full text-[13px] sm:text-sm font-bold dark:text-white outline-none transition-colors shadow-inner" />
                                <button type="submit" className="absolute right-1.5 top-1/2 -translate-y-1/2 w-7 h-7 bg-orange-500 hover:bg-orange-600 text-white rounded-full flex items-center justify-center transition-colors shadow-sm cursor-pointer" title="Cari Produk">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div className="flex items-center gap-1 sm:gap-3 shrink-0 relative z-10 bg-white/50 dark:bg-gray-900/50 sm:bg-transparent backdrop-blur-md rounded-full p-1 sm:p-0">
                        {/* PPOB DASHBOARD LINK BUTTON */}
                        <Link
                            href="/ppob"
                            className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-black text-xs shadow-md shadow-emerald-500/20 hover:scale-105 transition-all cursor-pointer shrink-0"
                            title="Buka Layanan PPOB & Pulsa 24 Jam"
                        >
                            <span className="text-xs">⚡</span>
                            <span className="hidden sm:inline">PPOB &amp; Pulsa</span>
                            <span className="sm:hidden">PPOB</span>
                            <span className="text-[9px] bg-white/25 px-1.5 py-0.2 rounded-full font-black uppercase tracking-wider">HOT</span>
                        </Link>

                        <button onClick={toggleDarkMode} className="p-2 rounded-full text-gray-500 hover:text-black dark:text-gray-400 dark:hover:bg-gray-800 transition-colors cursor-pointer">
                            {isDark ? (
                                <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clipRule="evenodd"></path></svg>
                            ) : (
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                            )}
                        </button>

                        {auth?.user ? (
                            <div className="relative" ref={profileMenuRef}>
                                {auth.user.role === 'admin' ? (
                                    <button
                                        onClick={() => setShowProfileMenu(!showProfileMenu)}
                                        className="flex items-center gap-1.5 sm:gap-2 px-3 py-1.5 rounded-full bg-slate-900 text-white dark:bg-slate-800 border border-slate-700 hover:border-orange-500 transition-colors focus:outline-none cursor-pointer shadow-sm"
                                        title="Mode Pantau Admin"
                                    >
                                        <span className="text-xs">👑</span>
                                        <span className="text-xs font-black hidden sm:inline text-orange-400">Admin</span>
                                        <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                ) : (
                                    <button
                                        onClick={() => setShowProfileMenu(!showProfileMenu)}
                                        className="flex items-center justify-center p-2 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none cursor-pointer"
                                    >
                                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                        </svg>
                                    </button>
                                )}

                                {/* DROPDOWN MENU */}
                                {showProfileMenu && (
                                    auth.user.role === 'admin' ? (
                                        <div className="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50 animate-fade-in-up">
                                            {/* Info Admin */}
                                            <div className="px-5 py-4 bg-gradient-to-br from-slate-900 to-slate-800 text-white border-b border-slate-700">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-xl bg-orange-500/20 text-orange-400 border border-orange-500/30 flex items-center justify-center font-bold text-lg shrink-0">
                                                        👑
                                                    </div>
                                                    <div className="flex-1 overflow-hidden">
                                                        <p className="text-sm font-black text-white truncate">{auth.user.name}</p>
                                                        <span className="inline-block text-[9px] font-black uppercase tracking-wider bg-orange-500/20 text-orange-400 border border-orange-500/30 px-2 py-0.5 rounded-full mt-0.5">
                                                            Mode Pantau Toko
                                                        </span>
                                                    </div>
                                                </div>
                                                <div className="mt-3 bg-amber-500/10 border border-amber-500/20 rounded-xl p-2.5 text-[11px] text-amber-300 font-medium leading-relaxed">
                                                    👁️ <strong>Mode Pantau Aktif:</strong> Akun admin tidak memiliki saldo &amp; profil pembeli, serta tidak dapat melakukan transaksi atau top-up.
                                                </div>
                                            </div>

                                            {/* Menu Items for Admin */}
                                            <div className="p-2 flex flex-col gap-1">
                                                <Link
                                                    href="/admin/dashboard"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-orange-600 dark:text-orange-400 bg-orange-50/70 dark:bg-orange-950/30 hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                                    </div>
                                                    <div className="flex-1">
                                                        <p className="font-black text-sm">Dashboard Admin</p>
                                                        <p className="text-[10px] text-gray-500 dark:text-gray-400">Kembali ke panel kelola</p>
                                                    </div>
                                                </Link>

                                                <div className="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-2"></div>

                                                <button
                                                    onClick={() => { setShowProfileMenu(false); router.post('/logout'); }}
                                                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                                    </div>
                                                    Keluar
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden z-50 animate-fade-in-up">
                                            {/* Info User */}
                                            <div className="px-5 py-4 bg-gradient-to-br from-orange-500 to-orange-600">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-full bg-white/20 text-white flex items-center justify-center font-bold text-lg shadow-inner backdrop-blur-sm border border-white/30 shrink-0">
                                                        {auth.user.name.charAt(0).toUpperCase()}
                                                    </div>
                                                    <div className="flex-1 overflow-hidden">
                                                        <p className="text-sm font-black text-white truncate">{auth.user.name}</p>
                                                        <p className="text-xs text-orange-100 truncate">{auth.user.email}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            {/* Saldo Info */}
                                            <div className="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex justify-between items-center">
                                                <div className="flex items-center gap-2">
                                                    <div className="w-6 h-6 rounded-md bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center">
                                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    <span className="text-xs font-bold text-gray-600 dark:text-gray-400">Saldo</span>
                                                </div>
                                                <span className="text-sm font-black text-green-500 dark:text-green-400">Rp {new Intl.NumberFormat('id-ID').format(auth.user.balance || 0)}</span>
                                            </div>

                                            {/* Menu Items */}
                                            <div className="p-2 flex flex-col gap-1">
                                                <Link
                                                    href="/profile"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    </div>
                                                    Profil Saya
                                                </Link>

                                                <Link
                                                    href="/profile?tab=orders"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                                    </div>
                                                    Riwayat Pembelian
                                                </Link>
                                                
                                                <Link
                                                    href="/profile?tab=topup"
                                                    onClick={() => setShowProfileMenu(false)}
                                                    className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-gray-700 dark:text-gray-200 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-500 group-hover:bg-orange-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    Top Up & Mutasi
                                                </Link>
                                                
                                                <div className="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-2"></div>
                                                
                                                <button
                                                    onClick={() => { setShowProfileMenu(false); router.post('/logout'); }}
                                                    className="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors cursor-pointer group"
                                                >
                                                    <div className="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors flex items-center justify-center shrink-0">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                                    </div>
                                                    Keluar
                                                </button>
                                            </div>
                                        </div>
                                    )
                                )}
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 sm:gap-3">
                                <button onClick={() => setShowTrackModal(true)} className="hidden sm:flex text-xs font-bold text-gray-700 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 px-4 py-2 sm:py-2.5 rounded-full transition shadow-sm cursor-pointer items-center gap-2">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                    Lacak Pesanan
                                </button>
                                <Link href="/login" className="text-xs font-bold text-white bg-orange-500 hover:bg-orange-600 px-4 py-2 sm:px-5 sm:py-2.5 rounded-full transition shadow-md cursor-pointer mr-1 sm:mr-0">
                                    Masuk/Daftar
                                </Link>
                            </div>
                        )}
                    </div>

                </div>
            </nav>

            {/* HERO CAROUSEL & SEARCH SECTION */}
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



            {/* LIVE NOTIFICATION TOAST (REALTIME 24H BUYERS LOOP) */}
            <div className={`fixed bottom-6 left-6 z-50 transition-all duration-500 ease-[cubic-bezier(0.34,1.56,0.64,1)] ${showNotif ? 'translate-y-0 opacity-100 scale-100' : 'translate-y-20 opacity-0 scale-90 pointer-events-none'}`}>
                <div className="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md p-3 pr-5 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 flex items-center gap-3">
                    <div className="w-10 h-10 bg-green-100 dark:bg-green-900/40 text-green-500 rounded-full flex items-center justify-center shrink-0">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <p className="text-xs font-black text-gray-900 dark:text-white">
                            <span className="text-orange-500">{activeNotif?.name || 'Pelanggan'}</span> baru saja membeli
                        </p>
                        <p className="text-[11px] font-bold text-gray-600 dark:text-gray-300 truncate max-w-[220px]">{activeNotif?.product}</p>
                        <span className="text-[9px] font-extrabold text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            {activeNotif?.time_ago || 'Baru saja'}
                        </span>
                    </div>
                </div>
            </div>

            {/* KONTEN UTAMA */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 flex-1 w-full">
                
                {/* MENU LAYANAN UNGGULAN & SHORTCUT CEPAT (SHOPEE STYLE ADAPTED FOR DIGITAL STORE) */}
                {!filters.category && !filters.tipe && !filters.search && (
                    <div className="bg-white dark:bg-[#131b2e] rounded-2xl sm:rounded-3xl border border-gray-200/80 dark:border-white/5 shadow-sm p-3.5 sm:p-5 mb-5 md:mb-6 animate-fade-in-up">
                        <div className="flex items-center justify-between mb-3 px-1">
                            <div className="flex items-center gap-2">
                                <span className="w-2 h-2 rounded-full bg-orange-500"></span>
                                <h3 className="text-xs sm:text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                                    Layanan &amp; Kategori Favorit
                                </h3>
                            </div>
                            <span className="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Siap 24 Jam
                            </span>
                        </div>

                        {/* Grid: 5 kolom di HP (2 baris pas untuk 10 layanan), 10 kolom di Desktop */}
                        <div className="grid grid-cols-5 md:grid-cols-5 lg:grid-cols-10 gap-2 sm:gap-3">
                            {/* 1. Pulsa & Data */}
                            <Link
                                href="/ppob?tab=pulsa"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-emerald-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-emerald-500 text-white text-[7px] font-black uppercase">Auto</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Pulsa &amp; Data</span>
                            </Link>

                            {/* 2. Listrik PLN */}
                            <Link
                                href="/ppob?tab=pln"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-amber-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200/80 dark:border-amber-800/40 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-amber-500 text-white text-[7px] font-black uppercase">Token</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-amber-600 dark:group-hover:text-amber-400">Token PLN</span>
                            </Link>

                            {/* 3. Top Up E-Wallet */}
                            <Link
                                href="/ppob?tab=ewallet"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-sky-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-sky-50 dark:bg-sky-950/50 border border-sky-200/80 dark:border-sky-800/40 text-sky-600 dark:text-sky-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5" fill="currentColor"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-sky-500 text-white text-[7px] font-black uppercase">DANA</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-sky-600 dark:group-hover:text-sky-400">E-Wallet</span>
                            </Link>

                            {/* 4. Voucher Game */}
                            <Link
                                href="/ppob?tab=game"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-purple-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 border border-purple-200/80 dark:border-purple-800/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-purple-500 text-white text-[7px] font-black uppercase">Game</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-purple-600 dark:group-hover:text-purple-400">Game Topup</span>
                            </Link>

                            {/* 5. Tagihan Air PDAM */}
                            <Link
                                href="/ppob?tab=pdam"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-cyan-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 border border-cyan-200/80 dark:border-cyan-800/40 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-cyan-600 dark:group-hover:text-cyan-400">Air PDAM</span>
                            </Link>

                            {/* 6. Akun Streaming */}
                            <button
                                type="button"
                                onClick={() => handleCategoryTipe('Streaming')}
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-rose-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200/80 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-rose-500 text-white text-[7px] font-black uppercase">Film</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-rose-600 dark:group-hover:text-rose-400">Streaming</span>
                            </button>

                            {/* 7. Aplikasi & AI */}
                            <button
                                type="button"
                                onClick={() => handleCategoryTipe('')}
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-indigo-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200/80 dark:border-indigo-800/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-indigo-600 dark:group-hover:text-indigo-400">Aplikasi &amp; AI</span>
                            </button>

                            {/* 8. Paling Laris */}
                            <button
                                type="button"
                                onClick={() => scrollToSection('paling-laris')}
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-orange-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-orange-50 dark:bg-orange-950/50 border border-orange-200/80 dark:border-orange-800/40 text-orange-600 dark:text-orange-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all relative">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 23c-4.97 0-9-4.03-9-9 0-3.8 2.38-7.05 5.74-8.35.5-.19 1.04.14 1.13.66.1.58-.2 1.14-.72 1.39C6.88 8.84 5 11.23 5 14c0 3.86 3.14 7 7 7s7-3.14 7-7c0-2.43-1.42-4.59-3.52-5.59-.5-.24-.76-.79-.64-1.33.12-.54.67-.88 1.21-.74C19.78 7.63 22 10.6 22 14c0 4.97-4.03 9-9 9z"/></svg>
                                    <span className="absolute -top-1 -right-1 px-1 py-0.2 rounded-full bg-orange-500 text-white text-[7px] font-black uppercase">HOT</span>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-orange-600 dark:group-hover:text-orange-400">Paling Laris</span>
                            </button>

                            {/* 9. Lacak Pesanan */}
                            <button
                                type="button"
                                onClick={() => setShowTrackModal(true)}
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-slate-50 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 text-slate-700 dark:text-slate-300 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-orange-500">Lacak Order</span>
                            </button>

                            {/* 10. Hubungi CS */}
                            <a
                                href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(store?.name || 'NandarStore')},%20saya%20butuh%20bantuan`}
                                target="_blank"
                                rel="noreferrer"
                                className="flex flex-col items-center group cursor-pointer p-1.5 sm:p-2 rounded-2xl hover:bg-emerald-50/60 dark:hover:bg-white/5 transition-all text-center"
                            >
                                <div className="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200/80 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs group-hover:scale-110 group-hover:shadow-md transition-all">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2z"/></svg>
                                </div>
                                <span className="text-[10px] sm:text-xs font-extrabold text-gray-800 dark:text-gray-200 mt-1.5 leading-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400">Bantuan CS</span>
                            </a>
                        </div>
                    </div>
                )}

                {/* PUSAT LAYANAN PPOB & PULSA */}
                {!filters.category && !filters.tipe && !filters.search && (
                    <div className="mb-6 md:mb-8 animate-fade-in-up">
                        <Link
                            href="/ppob"
                            className="block p-5 sm:p-7 rounded-3xl bg-gradient-to-r from-emerald-50 via-teal-50/70 to-emerald-100/50 dark:from-gray-900 dark:via-[#0e221d] dark:to-[#123129] border border-emerald-200 dark:border-emerald-500/30 text-gray-900 dark:text-white shadow-lg shadow-emerald-900/5 dark:shadow-xl dark:shadow-emerald-950/20 hover:border-emerald-400 dark:hover:border-emerald-400 hover:shadow-xl transition-all group overflow-hidden relative cursor-pointer"
                        >
                            <div className="absolute -top-16 -right-16 w-48 h-48 bg-emerald-500/10 dark:bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
                            <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-5 relative z-10">
                                <div className="flex items-center gap-4">
                                    <div className="w-13 h-13 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30 shrink-0 group-hover:scale-105 transition-transform p-3">
                                        <svg className="w-7 h-7" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 flex-wrap mb-1">
                                            <h3 className="text-base sm:text-lg font-black text-gray-900 dark:text-white tracking-wide">
                                                Pusat Layanan PPOB, Pulsa &amp; Tagihan
                                            </h3>
                                            <span className="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30">
                                                TRANSAKSI OTOMATIS 24 JAM
                                            </span>
                                        </div>
                                        <p className="text-xs text-gray-600 dark:text-gray-300 font-medium max-w-2xl leading-relaxed">
                                            Beli pulsa semua operator, paket data, token listrik PLN, top up e-wallet (DANA, GoPay, OVO), voucher game (MLBB, FF, Genshin) &amp; bayar tagihan PDAM otomatis detik ini juga.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex flex-wrap items-center gap-2.5 self-end md:self-center shrink-0">
                                    <div className="hidden xl:flex items-center gap-3 text-xs font-bold text-emerald-800 dark:text-emerald-300/90 bg-white/80 dark:bg-white/5 px-4 py-2.5 rounded-2xl border border-emerald-200/80 dark:border-white/10 shadow-xs">
                                        <span className="flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                            Pulsa &amp; Data
                                        </span>
                                        <span className="text-gray-300 dark:text-white/20">•</span>
                                        <span className="flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                                            Token PLN
                                        </span>
                                        <span className="text-gray-300 dark:text-white/20">•</span>
                                        <span className="flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><line x1="15" y1="13" x2="15.01" y2="13"/><line x1="18" y1="11" x2="18.01" y2="11"/></svg>
                                            Game
                                        </span>
                                        <span className="text-gray-300 dark:text-white/20">•</span>
                                        <span className="flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5 text-sky-600 dark:text-sky-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/></svg>
                                            E-Wallet
                                        </span>
                                    </div>
                                    <span className="py-3 px-5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-950/20 border border-emerald-400/40 group-hover:scale-105 transition-all flex items-center gap-2 shrink-0">
                                        <span>Buka Dashboard PPOB</span>
                                        <svg className="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>
                )}

                {/* FLASH SALE / PALING BANYAK DIBELI (REALTIME HARIAN) */}
                {((liveTopSelling && liveTopSelling.length > 0) || productList.length > 0) && !filters.category && !filters.tipe && !filters.search && (
                    <div id="paling-laris" className="bg-gradient-to-r from-orange-500 via-rose-500 to-red-600 rounded-3xl shadow-lg p-1 sm:p-1.5 mb-6 md:mb-8 animate-fade-in-up relative overflow-hidden">
                        <div className="bg-white dark:bg-gray-900 rounded-[1.3rem] px-4 py-4 sm:px-6 sm:py-5 flex flex-col lg:flex-row items-center justify-between gap-4 relative">
                            {/* Header Kiri */}
                            <div className="flex items-center justify-between w-full lg:w-auto shrink-0">
                                <div className="flex items-center gap-3">
                                    <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500/20 to-red-500/20 border border-orange-500/30 flex items-center justify-center shrink-0 shadow-inner">
                                        <span className="text-2xl animate-bounce">🔥</span>
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 flex-wrap">
                                            <h2 className="text-base sm:text-lg font-black text-gray-900 dark:text-white leading-tight">
                                                Paling Banyak Dibeli Hari Ini
                                            </h2>
                                            <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800 tracking-wider uppercase shadow-xs">
                                                <span className="w-1.5 h-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                                Realtime
                                            </span>
                                        </div>
                                        <p className="text-xs font-bold text-gray-500 dark:text-gray-400 mt-0.5">
                                            Harga spesial dengan stok terbatas.
                                        </p>
                                    </div>
                                </div>

                                {/* Slider Navigation Arrows on Mobile / Tablet */}
                                <div className="flex items-center gap-1 lg:hidden">
                                    <button
                                        type="button"
                                        onClick={() => scrollSlider(topSellingContainerRef, 'left')}
                                        className="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-gray-600 dark:text-gray-300 flex items-center justify-center transition cursor-pointer"
                                        aria-label="Scroll Kiri"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => scrollSlider(topSellingContainerRef, 'right')}
                                        className="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-gray-600 dark:text-gray-300 flex items-center justify-center transition cursor-pointer"
                                        aria-label="Scroll Kanan"
                                    >
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path></svg>
                                    </button>
                                </div>
                            </div>
                            
                            {/* Slider Cards Kanan */}
                            <div className="relative w-full lg:w-auto flex-1 min-w-0 flex items-center">
                                {/* Desktop scroll arrow left */}
                                <button
                                    type="button"
                                    onClick={() => scrollSlider(topSellingContainerRef, 'left')}
                                    className="hidden lg:flex w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-500 hover:text-orange-500 hover:border-orange-500 z-10 mr-1 shrink-0 cursor-pointer transition active:scale-90"
                                    aria-label="Geser Kiri"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7"></path></svg>
                                </button>

                                <div
                                    ref={topSellingContainerRef}
                                    className="flex items-center gap-2.5 sm:gap-3 w-full overflow-x-auto hide-scroll pb-1 scroll-smooth snap-x snap-mandatory"
                                >
                                    {(liveTopSelling.length > 0 ? liveTopSelling : productList.slice(0, 4)).map((product, idx) => {
                                        const isHotToday = Boolean(product.today_sales && product.today_sales > 0);
                                        const rankNumber = idx + 1;

                                        return (
                                            <Link
                                                key={`top-selling-${product.id}-${idx}`}
                                                href={product.is_ppob ? (product.ppob_url || '/ppob') : `/product/${product.id}`}
                                                className="flex items-center gap-2.5 bg-gray-50 dark:bg-gray-800/80 hover:bg-emerald-50/40 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700/80 rounded-2xl p-2.5 min-w-[200px] sm:min-w-[220px] max-w-[260px] hover:border-emerald-500 hover:shadow-md transition-all group cursor-pointer shrink-0 snap-start relative overflow-hidden"
                                            >
                                                {/* Ranking Badge Mini */}
                                                <div className={`absolute top-1 left-1 w-4 h-4 rounded-md flex items-center justify-center font-black text-[9px] z-10 ${
                                                    rankNumber === 1
                                                        ? 'bg-amber-400 text-gray-950 shadow-xs'
                                                        : rankNumber === 2
                                                        ? 'bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white'
                                                        : rankNumber === 3
                                                        ? 'bg-amber-700 text-white'
                                                        : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                                                }`}>
                                                    {rankNumber}
                                                </div>

                                                {/* Foto / Icon Produk */}
                                                <div className="w-12 h-12 rounded-xl bg-white dark:bg-gray-900 p-1 flex items-center justify-center shrink-0 border border-gray-100 dark:border-gray-800 shadow-xs group-hover:scale-105 transition-transform">
                                                    {product.image ? (
                                                        <img src={product.image} alt={product.name} className="w-full h-full object-contain" />
                                                    ) : product.is_ppob ? (
                                                        <div className="w-full h-full rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center p-2 shadow-xs">
                                                            {product.ppob_icon_type === 'pln' ? (
                                                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                                                            ) : product.ppob_icon_type === 'game' ? (
                                                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="6" width="20" height="12" rx="6"/><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15.5" cy="12" r="1" fill="currentColor"/><circle cx="18.5" cy="12" r="1" fill="currentColor"/></svg>
                                                            ) : product.ppob_icon_type === 'ewallet' ? (
                                                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 10h20"/><circle cx="17" cy="14" r="1.5" fill="currentColor"/></svg>
                                                            ) : (
                                                                <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><rect x="5" y="2" width="14" height="20" rx="3"/><path d="M12 18h.01"/></svg>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-[10px] font-black text-gray-400">NS</span>
                                                    )}
                                                </div>

                                                {/* Info Produk */}
                                                <div className="flex flex-col min-w-0 flex-1">
                                                    <div className="flex items-center gap-1">
                                                        <span className="text-xs font-black text-gray-900 dark:text-white line-clamp-1 group-hover:text-emerald-500 transition-colors">
                                                            {product.name}
                                                        </span>
                                                    </div>

                                                    {/* Realtime Sales Tag */}
                                                    <div className="mt-0.5 flex items-center gap-1">
                                                        {product.is_ppob && (
                                                            <span className="text-[8px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/80 px-1 py-0.2 rounded border border-emerald-200 dark:border-emerald-800/40 uppercase">
                                                                PPOB
                                                            </span>
                                                        )}
                                                        {isHotToday ? (
                                                            <span className="text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/80 px-1.5 py-0.5 rounded border border-rose-200 dark:border-rose-900/40 inline-flex items-center gap-1">
                                                                <span className="w-1 h-1 rounded-full bg-rose-500 animate-pulse"></span>
                                                                🔥 {product.sales_label || `${product.today_sales} dibeli hari ini`}
                                                            </span>
                                                        ) : product.total_sales > 0 ? (
                                                            <span className="text-[9px] font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/60 px-1.5 py-0.5 rounded border border-amber-200 dark:border-amber-900/40 inline-flex items-center gap-0.5">
                                                                ⚡ {product.sales_label || `${product.total_sales} terjual`}
                                                            </span>
                                                        ) : (
                                                            <span className="text-[9px] font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded inline-flex items-center gap-0.5">
                                                                ⭐ Terpopuler
                                                            </span>
                                                        )}
                                                    </div>

                                                    <div className="flex items-center gap-1.5 mt-1">
                                                        <span className="text-xs font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                                            Rp {new Intl.NumberFormat('id-ID').format(product.price)}
                                                        </span>
                                                        {product.stock !== undefined && product.stock !== null && !product.is_ppob && (
                                                            <span className="text-[9px] font-semibold text-gray-400 truncate">
                                                                • Sisa {product.stock}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </Link>
                                        );
                                    })}
                                </div>

                                {/* Desktop scroll arrow right */}
                                <button
                                    type="button"
                                    onClick={() => scrollSlider(topSellingContainerRef, 'right')}
                                    className="hidden lg:flex w-7 h-7 rounded-full bg-white dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 items-center justify-center text-gray-500 hover:text-orange-500 hover:border-orange-500 z-10 ml-1 shrink-0 cursor-pointer transition active:scale-90"
                                    aria-label="Geser Kanan"
                                >
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {/* SLIDER KATEGORI */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700/50 p-4 sm:p-6 mb-4 relative group/slider">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-sm sm:text-base font-extrabold text-black dark:text-white">Kategori Premium Populer</h2>
                        <button onClick={() => setShowCategoryModal(true)} className="text-xs font-bold text-orange-500 hover:text-orange-600 hover:underline transition-colors cursor-pointer bg-orange-50 dark:bg-orange-900/30 px-3 py-1.5 rounded-full">
                            Lihat Semua
                        </button>
                    </div>
                    
                    <button onClick={() => scrollSlider(brandContainerRef, 'left')} className="absolute left-1 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer">
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
                    
                    <button onClick={() => scrollSlider(brandContainerRef, 'right')} className="absolute right-1 top-1/2 mt-2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/slider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div className="mb-8 relative group/typeSlider">
                    <button onClick={() => scrollSlider(typeContainerRef, 'left')} className="absolute -left-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex">
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

                    <button onClick={() => scrollSlider(typeContainerRef, 'right')} className="absolute -right-3 top-1/2 -translate-y-1/2 w-8 h-8 bg-white dark:bg-gray-700 rounded-full shadow-lg border border-gray-100 dark:border-gray-600 flex items-center justify-center text-gray-500 z-10 opacity-0 group-hover/typeSlider:opacity-100 transition-opacity hover:text-orange-500 cursor-pointer hidden sm:flex">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                {productList.length === 0 ? (
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
                ) : (
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
                )}
            </div>

            {/* NEW FOOTER 3 KOLOM */}
            <footer className="bg-white dark:bg-[#0b1121] border-t border-gray-200 dark:border-gray-800 mt-auto py-10 transition-colors z-20 relative">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-10 lg:gap-16">
                        
                        <div className="flex flex-col gap-4">
                            <div className="flex items-center gap-2">
                                {store?.logo ? (
                                    <div className="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 border border-gray-100 dark:border-white/10 p-0.5 flex items-center justify-center shadow-md overflow-hidden">
                                        <img src={store.logo} alt={store?.name || 'NandarStore'} className="w-full h-full object-contain rounded-lg" />
                                    </div>
                                ) : (
                                    <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-bold text-sm shadow-md">NS</div>
                                )}
                                <span className="font-black text-gray-900 dark:text-white text-xl tracking-tight">{store?.name || 'NandarStore'}</span>
                            </div>
                            <p className="text-gray-500 dark:text-gray-400 text-sm leading-relaxed font-medium">
                                Platform digital andalan untuk upgrade produktivitas dan hiburanmu tanpa bikin dompet boncos. Nikmati pengalaman belanja akun premium yang 100% aman, diproses otomatis secara instan, dengan penawaran harga spesial.
                            </p>
                            <div className="flex items-center gap-3 mt-2">
                                <a href="https://instagram.com/nandarstore" target="_blank" rel="noreferrer" className="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 flex items-center justify-center hover:bg-orange-500 hover:text-white dark:hover:bg-orange-500 transition-colors shadow-sm">
                                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                </a>
                                <a href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(store?.name || 'NandarStore')},`} target="_blank" rel="noreferrer" className="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 flex items-center justify-center hover:bg-orange-500 hover:text-white dark:hover:bg-orange-500 transition-colors shadow-sm">
                                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                </a>
                            </div>
                        </div>

                        <div className="flex flex-col gap-4 md:pl-10">
                            <h3 className="font-black text-gray-900 dark:text-white tracking-widest text-sm uppercase mb-1">Layanan</h3>
                            <ul className="space-y-3">
                                <li>
                                    <Link href="/" className="text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Beranda
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/ppob" className="text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Layanan PPOB 24 Jam
                                    </Link>
                                </li>
                                <li>
                                    <button onClick={() => setShowTrackModal(true)} className="text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors cursor-pointer">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Lacak Pesanan
                                    </button>
                                </li>
                                <li>
                                    <Link href="/faq" className="text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Pusat Bantuan & FAQ
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/syarat-ketentuan" className="text-gray-500 dark:text-gray-400 hover:text-orange-500 dark:hover:text-orange-400 font-medium text-sm flex items-center gap-2 transition-colors">
                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7"></path></svg> Syarat & Ketentuan
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <div className="flex flex-col gap-4">
                            <h3 className="font-black text-gray-900 dark:text-white tracking-widest text-sm uppercase mb-1">Hubungi Kami</h3>
                            <ul className="space-y-4">
                                <li className="flex items-start gap-3">
                                    <div className="mt-0.5 text-orange-500">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </div>
                                    <span className="text-gray-500 dark:text-gray-400 font-medium text-sm">Jawa Barat, Indonesia.</span>
                                </li>
                                <li className="flex items-start gap-3">
                                    <div className="mt-0.5 text-orange-500">
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    </div>
                                    <span className="text-gray-500 dark:text-gray-400 font-medium text-sm">+62 823-1745-8496</span>
                                </li>
                            </ul>
                        </div>

                    </div>
                    
                    <div className="mt-12 pt-6 border-t border-gray-100 dark:border-gray-800 flex flex-col md:flex-row items-center justify-between gap-4">
                        <p className="text-gray-500 dark:text-gray-500 text-xs font-bold text-center md:text-left">
                            &copy; {new Date().getFullYear()} {store?.name || 'NandarStore'}. Hak Cipta Dilindungi.
                        </p>
                        <p className="text-gray-500 dark:text-gray-500 text-xs font-bold text-center">
                            Made with <span className="text-red-500">❤</span> by <span className="text-orange-500">{store?.name || 'NandarStore'}</span>
                        </p>
                    </div>
                </div>
            </footer>

            {/* FLOATING ACTION BUTTON */}
            <div className="fixed bottom-20 sm:bottom-6 right-4 sm:right-6 z-50 flex flex-col items-end gap-3">
                <div className={`flex flex-col gap-3 transition-all duration-300 origin-bottom ${showFabMenu ? 'scale-100 opacity-100 translate-y-0' : 'scale-75 opacity-0 translate-y-10 pointer-events-none'}`}>
                    <a href={whatsappGroupLink || "https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H"} target="_blank" rel="noreferrer" className="flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-xl px-4 py-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group cursor-pointer">
                        <span className="text-xs font-extrabold text-gray-700 dark:text-gray-200">Join Grup WA</span>
                        <div className="w-8 h-8 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        </div>
                    </a>
                    <a href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(store?.name || 'NandarStore')},`} target="_blank" rel="noreferrer" className="flex items-center gap-3 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-xl px-4 py-3 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group cursor-pointer">
                        <span className="text-xs font-extrabold text-gray-700 dark:text-gray-200">Chat Admin</span>
                        <div className="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        </div>
                    </a>
                </div>
                <button onClick={() => setShowFabMenu(!showFabMenu)} className={`w-14 h-14 rounded-full flex items-center justify-center text-white shadow-[0_10px_30px_-10px_rgba(249,115,22,0.6)] transition-all duration-300 cursor-pointer ${showFabMenu ? 'bg-gray-800 dark:bg-gray-700 rotate-45' : 'bg-orange-500 hover:bg-orange-600 hover:-translate-y-1 hover:scale-105'}`}>
                    {showFabMenu ? (
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 4v16m8-8H4"></path></svg>
                    ) : (
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    )}
                </button>
            </div>

            {/* MODAL LACAK PESANAN */}
            {showTrackModal && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md flex flex-col shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 dark:border-gray-700">
                        <div className="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                            <h3 className="text-lg font-black dark:text-white flex items-center gap-2">
                                <svg className="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                Lacak Pesanan
                            </h3>
                            <button onClick={() => { setShowTrackModal(false); setTrackResult(null); setTrackOrderId(''); }} className="p-2 text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-gray-700 rounded-full shadow-sm cursor-pointer">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div className="p-6">
                            <form onSubmit={handleTrackOrder} className="flex flex-col gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5">Nomor Invoice / Order ID</label>
                                    <input type="text" required value={trackOrderId} onChange={(e) => setTrackOrderId(e.target.value)} placeholder="Contoh: INV-123456" className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm font-bold dark:text-white transition-all outline-none" />
                                </div>
                                <button type="submit" disabled={trackLoading} className="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-3 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer">
                                    {trackLoading ? (
                                        <svg className="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    ) : (
                                        <>Cek Status Sekarang <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></>
                                    )}
                                </button>
                            </form>

                            {trackResult && (
                                <div className={`mt-5 p-4 rounded-xl border flex flex-col items-center justify-center text-center gap-2 ${
                                    trackResult === 'SUCCESS' ? 'bg-green-50/50 dark:bg-green-900/10 border-green-200 dark:border-green-800' : 
                                    trackResult === 'PENDING' ? 'bg-yellow-50/50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800' : 
                                    'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800'
                                }`}>
                                    <span className="text-xs font-bold text-gray-500 dark:text-gray-400">Status Pesanan:</span>
                                    {trackResult === 'SUCCESS' && (
                                        <div className="space-y-2 mt-1">
                                            <span className="text-green-600 dark:text-green-400 font-black text-xl uppercase tracking-widest block">Lunas &amp; Selesai ✓</span>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Pesanan telah berhasil diproses oleh sistem.</p>
                                            <a 
                                                href={`/order/invoice/${trackOrderId}`} 
                                                target="_blank" 
                                                rel="noreferrer"
                                                className="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-xs rounded-xl shadow-md transition-colors mt-2"
                                            >
                                                <span>Buka Detail Akun &amp; Invoice PDF</span>
                                                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                            </a>
                                        </div>
                                    )}
                                    {trackResult === 'PENDING' && <span className="text-yellow-600 dark:text-yellow-400 font-black text-xl uppercase tracking-widest">Tertunda (Pending)</span>}
                                    {(trackResult !== 'SUCCESS' && trackResult !== 'PENDING') && <span className="text-red-600 dark:text-red-400 font-black text-lg uppercase tracking-widest">Tidak Ditemukan / Gagal</span>}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL SYARAT KETENTUAN */}
            {showTerms && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm transition-opacity">
                    <div className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 dark:border-gray-700">
                        <div className="p-5 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                            <h3 className="text-lg sm:text-xl font-extrabold dark:text-white">Syarat & Ketentuan</h3>
                            <button onClick={() => setShowTerms(false)} className="p-2 text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-gray-700 rounded-full shadow-sm cursor-pointer">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div className="p-5 sm:p-6 overflow-y-auto flex-1 bg-white dark:bg-gray-800">
                            <div className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-medium" dangerouslySetInnerHTML={{ __html: terms || 'Belum ada syarat dan ketentuan.' }} />
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL KATEGORI */}
            {showCategoryModal && (
                <div className="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-black/70 backdrop-blur-sm transition-opacity">
                    <div className="bg-white dark:bg-gray-800 rounded-[2rem] w-full max-w-4xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 dark:border-gray-700">
                        <div className="p-4 sm:p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50 relative">
                            <div className="flex items-center gap-3 sm:gap-4">
                                <div className="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-500 rounded-full flex items-center justify-center shadow-inner">
                                    <svg className="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                </div>
                                <div>
                                    <h3 className="text-lg sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">Semua Kategori Premium</h3>
                                    <p className="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pilih brand untuk melihat daftar produk.</p>
                                </div>
                            </div>
                            <button onClick={() => setShowCategoryModal(false)} className="p-2.5 text-gray-400 hover:text-white bg-gray-100 hover:bg-red-500 dark:bg-gray-700 dark:hover:bg-red-500 transition-colors rounded-full cursor-pointer shadow-sm absolute top-4 right-4 sm:top-6 sm:right-6">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div className="p-4 sm:p-6 overflow-y-auto flex-1 bg-gray-50/50 dark:bg-gray-900/50">
                            {categoryList.length === 0 ? (
                                <div className="text-center text-gray-500 py-10 font-bold">Kategori belum tersedia.</div>
                            ) : (
                                <div className="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-4 sm:gap-6 pt-2">
                                    <div onClick={() => handleCategoryBrand('')} className="flex flex-col items-center gap-2 cursor-pointer group">
                                        <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl flex items-center justify-center border-2 transition-all shadow-sm ${!filters.category ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 text-orange-500' : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-400 group-hover:border-orange-200'}`}>
                                            <svg className="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                        </div>
                                        <span className={`text-[10px] sm:text-xs font-bold text-center leading-tight line-clamp-2 ${!filters.category ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300'}`}>Semua<br/>Brand</span>
                                    </div>

                                    {categoryList.map((cat, index) => (
                                        <div key={index} onClick={() => handleCategoryBrand(cat.name)} className="flex flex-col items-center gap-2 cursor-pointer group">
                                            <div className={`w-14 h-14 sm:w-16 sm:h-16 rounded-2xl overflow-hidden flex items-center justify-center border-2 transition-all p-1 shadow-sm ${filters.category === cat.name ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30' : 'border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 group-hover:border-orange-200'}`}>
                                                {cat.image ? (
                                                    <img src={cat.image} alt={cat.name} className="w-full h-full object-cover rounded-xl" />
                                                ) : (
                                                    <div className="w-full h-full bg-gray-200 dark:bg-gray-600 rounded-xl flex items-center justify-center font-black text-gray-400 text-xs">NS</div>
                                                )}
                                            </div>
                                            <span className={`text-[10px] sm:text-xs font-bold text-center leading-tight line-clamp-2 max-w-[80px] ${filters.category === cat.name ? 'text-orange-500' : 'text-gray-600 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white'}`}>{cat.name}</span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* BOTTOM NAVIGATION BAR FOR MOBILE (HP) */}
            <div className="sm:hidden fixed bottom-0 left-0 right-0 z-40 bg-white/95 dark:bg-[#0b1121]/95 backdrop-blur-lg border-t border-gray-200 dark:border-white/10 px-2 py-1.5 shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                <div className="grid grid-cols-4 items-center">
                    <Link href="/" className="flex flex-col items-center justify-center py-1 text-orange-500 dark:text-orange-400">
                        <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                        <span className="text-[10px] font-extrabold mt-0.5">Beranda</span>
                    </Link>
                    <Link href="/ppob" className="flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-400 hover:text-emerald-500 dark:hover:text-emerald-400 relative">
                        <div className="relative">
                            <svg className="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z"/></svg>
                            <span className="absolute -top-1 -right-2 px-1 py-0.2 rounded-full bg-emerald-500 text-[8px] font-black text-white leading-none">HOT</span>
                        </div>
                        <span className="text-[10px] font-extrabold mt-0.5">PPOB 24 Jam</span>
                    </Link>
                    <button type="button" onClick={() => setShowTrackModal(true)} className="flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-400 hover:text-orange-500 cursor-pointer">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        <span className="text-[10px] font-extrabold mt-0.5">Lacak</span>
                    </button>
                    <Link href={auth?.user ? (auth.user.role === 'admin' ? '/admin/dashboard' : '/profile') : '/login'} className="flex flex-col items-center justify-center py-1 text-gray-500 dark:text-gray-400 hover:text-orange-500">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span className="text-[10px] font-extrabold mt-0.5">{auth?.user ? 'Profil' : 'Masuk'}</span>
                    </Link>
                </div>
            </div>

        </div>
    );
}