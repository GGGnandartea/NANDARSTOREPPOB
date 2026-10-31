import { orderLookup, rememberGuestOrder, savedGuestOrders } from '../../utils/guestOrders';
import React, { useState, useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';

// Sub-components / Partials
import Navbar from './Partials/Navbar';
import HeroBanner from './Partials/HeroBanner';
import RecentPurchaseToast from './Partials/RecentPurchaseToast';
import QuickServicesGrid from './Partials/QuickServicesGrid';
import PpobBanner from './Partials/PpobBanner';
import TopSellingSection from './Partials/TopSellingSection';
import CategorySection from './Partials/CategorySection';
import ProductGrid from './Partials/ProductGrid';
import FooterSection from './Partials/FooterSection';
import FloatingActionMenu from './Partials/FloatingActionMenu';
import TrackOrderModal from './Partials/TrackOrderModal';
import TermsModal from './Partials/TermsModal';
import CategoryModal from './Partials/CategoryModal';
import MobileBottomNav from './Partials/MobileBottomNav';
import PpobOfflineModal from '../../Components/PpobOfflineModal';

export default function Home({
    products = [],
    categories = [],
    manualCategories = [],
    filters = {},
    terms,
    promo = { markup_percent: 25, badge_text: 'PROMO' },
    banners: serverBanners = [],
    adminWhatsapp = '6282317458496',
    whatsappGroupLink = 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H',
    recentPurchases = [],
    topSellingToday = []
}) {
    const { auth, store } = usePage().props;
    const effectiveAdminWa = store?.admin_whatsapp || adminWhatsapp || '6282317458496';

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [isDark, setIsDark] = useState(false);
    const [showTerms, setShowTerms] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);
    const [showCategoryModal, setShowCategoryModal] = useState(false);
    const [showProfileMenu, setShowProfileMenu] = useState(false);
    const searchDebounceRef = useRef(null);

    const [showTrackModal, setShowTrackModal] = useState(false);
    const [trackOrderId, setTrackOrderId] = useState('');
    const [trackResult, setTrackResult] = useState(null);
    const [trackLoading, setTrackLoading] = useState(false);
    const trackRequestRef = useRef(null);
    useEffect(() => {
        if (!showTrackModal) { trackRequestRef.current?.abort(); setTrackLoading(false); }
        return () => trackRequestRef.current?.abort();
    }, [showTrackModal]);

    const [showFabMenu, setShowFabMenu] = useState(false);
    const [ppobModalOpen, setPpobModalOpen] = useState(false);
    const [ppobTargetHref, setPpobTargetHref] = useState('/ppob');

    const handleOpenPpob = (href = '/ppob') => {
        if (store?.ppob_status?.is_cutoff) {
            setPpobTargetHref(href || '/ppob');
            setPpobModalOpen(true);
            return;
        }
        router.visit(href || '/ppob');
    };

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
        { id: 2, title: 'Aplikasi Premium Bergaransi', subtitle: 'YouTube Premium, CapCut Pro, ChatGPT Plus proses instan otomatis bergaransi.', badge: 'PREMIUM', bg_gradient: 'from-blue-600 to-indigo-700', image: null, image_mobile: null },
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

        const handleScroll = () => {
            const scrollTop = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
            setIsScrolled(scrollTop > 130);
        };
        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });

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
            if (searchDebounceRef.current) clearTimeout(searchDebounceRef.current);
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
        if (banner.link_url.startsWith('/ppob') && store?.ppob_status?.is_cutoff) {
            handleOpenPpob(banner.link_url);
            return;
        }
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

    // Saat mengetik di search bar, otomatis keluar dari filter kategori/tipe dan cari di Semua Produk
    const handleInputChange = (e) => {
        const value = e.target.value;
        setSearchTerm(value);

        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }

        const trimmed = value.trim();
        if (trimmed === '') {
            router.get('/', {}, { preserveState: true, replace: true, preserveScroll: true });
            return;
        }

        searchDebounceRef.current = setTimeout(() => {
            router.get('/', { search: trimmed }, { preserveState: true, replace: true, preserveScroll: true });
        }, 300);
    };

    const handleClearSearch = () => {
        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }
        setSearchTerm('');
        router.get('/', {}, { preserveState: true, replace: true, preserveScroll: true });
    };

    // Saat submit pencarian (Enter / klik tombol Cari), reset kategori & cari di Semua Produk
    const handleSearch = (e) => {
        e?.preventDefault();
        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }
        const trimmed = searchTerm.trim();
        const params = trimmed ? { search: trimmed } : {};
        router.get('/', params, { preserveState: true, preserveScroll: true, replace: true });
    };

    const handleCategoryBrand = (cat) => {
        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }
        setSearchTerm('');
        const params = {};
        if (cat) params.category = cat;
        router.get('/', params, { preserveState: true, preserveScroll: true });
        setShowCategoryModal(false);
    };

    const handleCategoryTipe = (tipe) => {
        if (searchDebounceRef.current) {
            clearTimeout(searchDebounceRef.current);
        }
        setSearchTerm('');
        const params = {};
        if (tipe) params.tipe = tipe;
        router.get('/', params, { preserveState: true, preserveScroll: true });
    };

    const scrollSlider = (ref, direction) => {
        if (ref.current) {
            const scrollAmount = direction === 'left' ? -250 : 250;
            ref.current.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    };

    const generateFakeOriginalPrice = (price) => {
        const numPrice = Number(price);
        const markup = Number(promo?.markup_percent ?? 25);
        if (markup <= 0) return 0;
        return Math.floor(numPrice * (1 + (markup / 100)));
    };

    const handleTrackOrder = async (e, lookupInput = trackOrderId) => {
        e?.preventDefault();
        if (!lookupInput) return;
        trackRequestRef.current?.abort();
        const controller = new AbortController();
        trackRequestRef.current = controller;
        setTrackLoading(true); setTrackResult(null);
        try {
            const saved = savedGuestOrders().find(order => order.order_id === lookupInput.trim());
            const lookup = orderLookup(saved?.invoice_url || lookupInput);
            setTrackOrderId(lookup.orderId);
            const res = await fetch(lookup.statusUrl, { signal: controller.signal, headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (controller.signal.aborted) return;
            setTrackResult(res.ok ? data : { status: 'NOT_FOUND' });
            if (res.ok && data.is_guest) rememberGuestOrder(data);
        } catch (error) {
            if (!controller.signal.aborted) setTrackResult({ status: 'ERROR' });
        }
        if (!controller.signal.aborted) setTrackLoading(false);
    };

    return (
        <div className="storefront-ui min-h-[100dvh] flex flex-col bg-gray-50 dark:bg-[#0f172a] transition-colors duration-300 font-jakarta pb-20 sm:pb-0 overflow-x-clip">
            <Head>
                <title>{`${store?.name || 'PremiumKita'} - Platform Digital & Akun Premium`}</title>
                <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
                <style>{`
                    .font-jakarta { font-family: 'Plus Jakarta Sans', sans-serif; }
                    .hide-scroll::-webkit-scrollbar { display: none; }
                    .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
                    .glass-nav { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
                    .dark .glass-nav { background: rgba(15, 23, 42, 0.92); }
                `}</style>
            </Head>

            {/* NAVBAR */}
            <Navbar
                auth={auth}
                store={store}
                isScrolled={isScrolled}
                filters={filters}
                searchTerm={searchTerm}
                handleInputChange={handleInputChange}
                handleSearch={handleSearch}
                handleClearSearch={handleClearSearch}
                toggleDarkMode={toggleDarkMode}
                isDark={isDark}
                profileMenuRef={profileMenuRef}
                showProfileMenu={showProfileMenu}
                setShowProfileMenu={setShowProfileMenu}
                setShowTrackModal={setShowTrackModal}
                onOpenPpob={handleOpenPpob}
            />

            {/* HERO CAROUSEL & SEARCH SECTION */}
            <HeroBanner
                bannerList={bannerList}
                currentBanner={currentBanner}
                setCurrentBanner={setCurrentBanner}
                isHoveredBanner={isHoveredBanner}
                setIsHoveredBanner={setIsHoveredBanner}
                handlePrevBanner={handlePrevBanner}
                handleNextBanner={handleNextBanner}
                handleTouchStart={handleTouchStart}
                handleTouchMove={handleTouchMove}
                handleTouchEnd={handleTouchEnd}
                handleBannerClick={handleBannerClick}
                searchTerm={searchTerm}
                handleInputChange={handleInputChange}
                handleSearch={handleSearch}
                handleClearSearch={handleClearSearch}
                isScrolled={isScrolled}
                filters={filters}
            />

            {/* LIVE NOTIFICATION TOAST */}
            <RecentPurchaseToast
                showNotif={showNotif && !showFabMenu && !showTrackModal && !showCategoryModal && !ppobModalOpen}
                activeNotif={activeNotif}
            />

            {/* KONTEN UTAMA */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-20 flex-1 w-full">

                {/* MENU LAYANAN UNGGULAN & SHORTCUT CEPAT */}
                <QuickServicesGrid
                    filters={filters}
                    setShowTrackModal={setShowTrackModal}
                    effectiveAdminWa={effectiveAdminWa}
                    store={store}
                    onOpenPpob={handleOpenPpob}
                />

                {/* PUSAT LAYANAN PPOB & PULSA */}
                <PpobBanner
                    filters={filters}
                    store={store}
                    onOpenPpob={handleOpenPpob}
                />

                {/* FLASH SALE / PALING BANYAK DIBELI (REALTIME HARIAN) */}
                <TopSellingSection
                    liveTopSelling={liveTopSelling}
                    productList={productList}
                    filters={filters}
                    topSellingContainerRef={topSellingContainerRef}
                    scrollSlider={scrollSlider}
                    store={store}
                    onOpenPpob={handleOpenPpob}
                />

                {/* SLIDER KATEGORI & PRODUK */}
                <CategorySection
                    categoryList={categoryList}
                    manualCategories={manualCategories}
                    filters={filters}
                    brandContainerRef={brandContainerRef}
                    typeContainerRef={typeContainerRef}
                    scrollSlider={scrollSlider}
                    handleCategoryBrand={handleCategoryBrand}
                    handleCategoryTipe={handleCategoryTipe}
                    setShowCategoryModal={setShowCategoryModal}
                    handleClearSearch={handleClearSearch}
                />

                {/* DAFTAR PRODUK (GRID) */}
                <ProductGrid
                    productList={productList}
                    promo={promo}
                    auth={auth}
                    generateFakeOriginalPrice={generateFakeOriginalPrice}
                    setSearchTerm={setSearchTerm}
                />
            </div>

            {/* FOOTER */}
            <FooterSection
                store={store}
                effectiveAdminWa={effectiveAdminWa}
                setShowTrackModal={setShowTrackModal}
                onOpenPpob={handleOpenPpob}
            />

            {/* FLOATING ACTION BUTTON */}
            <FloatingActionMenu
                showFabMenu={showFabMenu}
                setShowFabMenu={setShowFabMenu}
                whatsappGroupLink={whatsappGroupLink}
                effectiveAdminWa={effectiveAdminWa}
                store={store}
            />

            {/* MODAL LACAK PESANAN */}
            <TrackOrderModal
                showTrackModal={showTrackModal}
                setShowTrackModal={setShowTrackModal}
                trackOrderId={trackOrderId}
                setTrackOrderId={setTrackOrderId}
                handleTrackOrder={handleTrackOrder}
                trackLoading={trackLoading}
                trackResult={trackResult}
                setTrackResult={setTrackResult}
            />

            {/* MODAL SYARAT KETENTUAN */}
            <TermsModal
                showTerms={showTerms}
                setShowTerms={setShowTerms}
                terms={terms}
            />

            {/* MODAL KATEGORI */}
            <CategoryModal
                showCategoryModal={showCategoryModal}
                setShowCategoryModal={setShowCategoryModal}
                categoryList={categoryList}
                filters={filters}
                handleCategoryBrand={handleCategoryBrand}
            />

            {/* MODAL STATUS PPOB OFFLINE / MAINTENANCE / GANGGUAN */}
            <PpobOfflineModal
                open={ppobModalOpen}
                onClose={() => setPpobModalOpen(false)}
                status={store?.ppob_status}
                targetHref={ppobTargetHref}
                isOnPpobPage={false}
            />

            {/* BOTTOM NAVIGATION BAR FOR MOBILE (HP) - PERSISTENT & SLEEK */}
            <MobileBottomNav
                auth={auth}
                showTrackModal={showTrackModal}
                setShowTrackModal={setShowTrackModal}
                showCategoryModal={showCategoryModal}
                setShowCategoryModal={setShowCategoryModal}
                store={store}
                onOpenPpob={handleOpenPpob}
            />

        </div>
    );
}
