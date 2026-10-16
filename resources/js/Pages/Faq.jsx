import React, { useState, useMemo, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Faq({ store = {}, adminWhatsapp = '082317458496', whatsappGroupLink = '' }) {
    const storeName = store?.name || 'NandarStore';
    const effectiveAdminWa = (adminWhatsapp || store?.whatsapp || '082317458496').replace(/[^0-9]/g, '');

    // Dark Mode Detection & Sync
    const [isDark, setIsDark] = useState(false);
    useEffect(() => {
        const theme = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (theme === 'dark' || (!theme && prefersDark)) {
            document.documentElement.classList.add('dark');
            setIsDark(true);
        } else {
            document.documentElement.classList.remove('dark');
            setIsDark(false);
        }
    }, []);

    const toggleDarkMode = () => {
        if (isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
            setIsDark(false);
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
            setIsDark(true);
        }
    };

    // FAQ Data
    const FAQ_ITEMS = [
        // KATEGORI: CARA ORDER & AKUN
        {
            id: 1,
            category: 'order',
            categoryLabel: 'Akun & Order',
            q: 'Bagaimana cara membeli akun premium di NandarStore?',
            a: 'Caranya sangat mudah: (1) Pilih produk aplikasi premium yang diinginkan di Beranda, (2) Masukkan nomor WhatsApp & Email aktif Anda untuk pengiriman akun, (3) Pilih metode pembayaran (QRIS Otomatis atau Saldo Akun), (4) Selesaikan pembayaran. Akun dan invoice akan langsung muncul di layar dan otomatis dikirimkan ke WhatsApp Anda dalam hitungan detik!'
        },
        {
            id: 2,
            category: 'order',
            categoryLabel: 'Akun & Order',
            q: 'Berapa lama proses pengiriman akun setelah pembayaran berhasil?',
            a: 'Semua produk di NandarStore diproses secara OTOMATIS & INSTAN 24 Jam. Begitu pembayaran QRIS terdeteksi lunas atau Saldo terpotong, sistem gateway kami langsung memproses pesanan Anda dalam rentang waktu 1 hingga 30 detik.'
        },
        {
            id: 3,
            category: 'order',
            categoryLabel: 'Akun & Order',
            q: 'Apa perbedaan tipe Akun Private dan Akun Sharing?',
            a: '• Akun Private: Akun 100% milik Anda sendiri tanpa campur dengan orang lain. Anda bebas mengatur profil, pin keamanan, dan download film/konten secara privat.\n• Akun Sharing: 1 akun digunakan bersama beberapa pelanggan lain dengan slot profil khusus masing-masing. Harganya jauh lebih hemat dan sangat cocok bagi penggunaan santai.'
        },
        {
            id: 4,
            category: 'order',
            categoryLabel: 'Akun & Order',
            q: 'Apakah akun yang dijual di NandarStore legal dan aman?',
            a: 'Ya, 100% legal dan aman! Kami menggunakan metode subscription dan pembayaran legal resmi. Akun terbebas dari tindakan ilegal atau hacking, serta dilengkapi garansi perlindungan penuh.'
        },

        // KATEGORI: PPOB & PULSA 24 JAM
        {
            id: 5,
            category: 'ppob',
            categoryLabel: 'PPOB & Pulsa 24 Jam',
            q: 'Apakah layanan PPOB, pulsa, dan token PLN aktif 24 jam non-stop?',
            a: 'Tentu saja! Server PPOB kami terhubung secara direct host-to-host ke switching multi-biller 24/7. Anda bisa membeli pulsa Telkomsel, Indosat, XL, Tri, Smartfren, hingga isi token listrik PLN kapan saja bahkan pada tengah malam tanpa gangguan.'
        },
        {
            id: 6,
            category: 'ppob',
            categoryLabel: 'PPOB & Pulsa 24 Jam',
            q: 'Beli Token Listrik PLN, bagaimana cara melihat 20 digit nomor tokennya?',
            a: 'Setelah status transaksi PPOB dinyatakan Sukses/Selesai, 20 digit kode Token PLN langsung tertera di kolom SN (Serial Number) pada halaman invoice atau notifikasi WhatsApp Anda. Cukup ketikkan 20 angka tersebut ke meteran PLN rumah Anda.'
        },
        {
            id: 7,
            category: 'ppob',
            categoryLabel: 'PPOB & Pulsa 24 Jam',
            q: 'Saya salah memasukkan nomor HP / No. Meter tujuan, bisakah dibatalkan?',
            a: 'Sistem server PPOB memproses transaksi secara otomatis ke provider seketika setelah pembayaran diterima. Transaksi yang sudah terkirim ke provider tidak dapat dibatalkan atau ditarik kembali. Mohon selalu teliti memeriksa nomor tujuan sebelum mengonfirmasi pembayaran.'
        },
        {
            id: 8,
            category: 'ppob',
            categoryLabel: 'PPOB & Pulsa 24 Jam',
            q: 'Top-up E-Wallet (DANA, OVO, GoPay, ShopeePay) apakah ada biaya admin?',
            a: 'Harga yang tertera pada produk e-wallet di NandarStore sudah merupakan harga final yang sangat kompetitif dan hemat dibanding transfer antar bank reguler.'
        },

        // KATEGORI: PEMBAYARAN & SALDO
        {
            id: 9,
            category: 'payment',
            categoryLabel: 'Pembayaran & Saldo',
            q: 'Metode pembayaran apa saja yang diterima di NandarStore?',
            a: 'Kami menerima:\n1. QRIS Nasional Realtime: Bisa di-scan menggunakan seluruh aplikasi m-Banking (BCA, BRI, Mandiri, BNI, CIMB, Neo, Jago, dll) serta semua E-Wallet (DANA, GoPay, OVO, ShopeePay, LinkAja).\n2. Saldo Akun Member: Pembayaran 1-klik instan langsung terpotong dari saldo dompet NandarStore Anda tanpa biaya transaksi QRIS.'
        },
        {
            id: 10,
            category: 'payment',
            categoryLabel: 'Pembayaran & Saldo',
            q: 'Bagaimana cara mengisi (Top Up) Saldo Akun NandarStore?',
            a: 'Silakan Login ke akun Anda > Masuk ke menu Profil > Pilih tab Top Up Saldo > Masukkan nominal top-up yang diinginkan (minimal Rp 10.000) > Scan QRIS yang tampil. Saldo akan otomatis masuk ke akun Anda detik itu juga!'
        },
        {
            id: 11,
            category: 'payment',
            categoryLabel: 'Pembayaran & Saldo',
            q: 'Saya sudah transfer QRIS tapi status web masih menunggu pembayaran?',
            a: 'Biasanya gateway membutuhkan waktu 5-15 detik untuk memvalidasi mutasi bank. Jika dalam 2 menit status belum berganti, silakan klik tombol "Cek Status Transaksi" atau hubungi Customer Care WhatsApp kami dengan menyertakan bukti transfer dan nomor invoice.'
        },

        // KATEGORI: GARANSI & KENDALA
        {
            id: 12,
            category: 'warranty',
            categoryLabel: 'Garansi & Kendala',
            q: 'Berapa lama masa garansi untuk setiap produk akun premium?',
            a: 'Garansi berlaku penuh sesuai durasi langganan produk yang Anda beli (misalnya 1 Bulan = Garansi 30 Hari Penuh). Selama masa garansi aktif, jika terjadi kendala akun back-to-free atau force logout, tim teknis kami akan memulihkan atau memberikan akun pengganti.'
        },
        {
            id: 13,
            category: 'warranty',
            categoryLabel: 'Garansi & Kendala',
            q: 'Bagaimana prosedur klaim garansi jika akun bermasalah?',
            a: 'Cukup klik tombol "Chat Admin CS" via WhatsApp, sebutkan Nomor Invoice pesanan Anda dan sertakan tangkapan layar (screenshot) kendala yang dialami. Tim CS kami akan merespons dan menyelesaikan kendala maksimal 1x24 jam kerja.'
        },
        {
            id: 14,
            category: 'warranty',
            categoryLabel: 'Garansi & Kendala',
            q: 'Apakah ada jaminan pengembalian dana (Refund)?',
            a: 'Prioritas utama adalah penggantian akun (replace) atau perbaikan akses. Jika sistem atau produk mengalami kendala permanen yang tidak dapat diselesaikan oleh kami, dana akan dikembalikan penuh (100% Refund) ke Saldo Akun Anda atau rekening Anda.'
        },

        // KATEGORI: RESELLER & KEMITRAAN
        {
            id: 15,
            category: 'reseller',
            categoryLabel: 'Reseller & Kemitraan',
            q: 'Apakah ada harga khusus bagi Reseller yang ingin menjual kembali?',
            a: 'Tentu ada! Kami menyediakan Tier Akun Reseller VIP dengan potongan harga spesial (harga grosir) untuk semua akun premium dan margin fee PPOB yang lebih murah. Anda bisa menjual kembali dengan margin keuntungan yang sangat menarik.'
        },
        {
            id: 16,
            category: 'reseller',
            categoryLabel: 'Reseller & Kemitraan',
            q: 'Bagaimana cara bergabung menjadi Reseller Resmi NandarStore?',
            a: 'Daftar akun gratis di menu Register > Buka menu Profil Member > Pilih Upgrade ke Reseller VIP > Lakukan pembayaran aktivasi. Akun Anda langsung otomatis aktif sebagai Reseller seumur hidup.'
        }
    ];

    const [activeCategory, setActiveCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [openItemIds, setOpenItemIds] = useState([1, 5, 9, 12]); // default buka item pertama tiap topik

    const categories = [
        { id: 'all', label: 'Semua Pertanyaan', icon: '✨' },
        { id: 'order', label: 'Akun & Order', icon: '🛒' },
        { id: 'ppob', label: 'PPOB & Pulsa 24 Jam', icon: '⚡' },
        { id: 'payment', label: 'Pembayaran & Saldo', icon: '💳' },
        { id: 'warranty', label: 'Garansi & Kendala', icon: '🛡️' },
        { id: 'reseller', label: 'Reseller VIP', icon: '👑' },
    ];

    const filteredFaqs = useMemo(() => {
        return FAQ_ITEMS.filter((item) => {
            const matchesCat = activeCategory === 'all' || item.category === activeCategory;
            const matchesSearch =
                searchQuery.trim() === '' ||
                item.q.toLowerCase().includes(searchQuery.toLowerCase()) ||
                item.a.toLowerCase().includes(searchQuery.toLowerCase()) ||
                item.categoryLabel.toLowerCase().includes(searchQuery.toLowerCase());
            return matchesCat && matchesSearch;
        });
    }, [activeCategory, searchQuery]);

    const toggleAccordion = (id) => {
        setOpenItemIds((prev) =>
            prev.includes(id) ? prev.filter((item) => item !== id) : [...prev, id]
        );
    };

    const expandAll = () => {
        setOpenItemIds(filteredFaqs.map((f) => f.id));
    };

    const collapseAll = () => {
        setOpenItemIds([]);
    };

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-[#0b0f19] text-gray-900 dark:text-gray-100 transition-colors duration-200">
            <Head>
                <title>{`Pusat Bantuan & FAQ - ${storeName}`}</title>
                <meta name="description" content="Temukan jawaban cepat seputar cara pembelian akun premium, transaksi PPOB pulsa 24 jam, metode pembayaran QRIS/saldo, dan klaim garansi di NandarStore." />
            </Head>

            {/* TOP NAVIGATION BAR */}
            <header className="sticky top-0 z-40 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200/80 dark:border-white/5">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/"
                            className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-orange-500 hover:text-white dark:hover:bg-orange-600 text-xs font-bold text-gray-700 dark:text-gray-300 transition-all shadow-sm"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>Beranda Toko</span>
                        </Link>
                        <div className="h-5 w-px bg-gray-200 dark:bg-white/10 hidden sm:block"></div>
                        <div className="flex items-center gap-2.5">
                            {store?.logo ? (
                                <img src={store.logo} alt={storeName} className="w-8 h-8 rounded-lg object-contain bg-orange-500/10 p-0.5" />
                            ) : (
                                <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-orange-500 to-amber-600 text-white font-black flex items-center justify-center text-xs shadow-sm">
                                    NS
                                </div>
                            )}
                            <div>
                                <span className="font-black text-sm tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5">
                                    {storeName}
                                    <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-orange-500/15 text-orange-600 dark:text-orange-400 border border-orange-500/30">
                                        PUSAT BANTUAN
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Link
                            href="/ppob"
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500 text-emerald-600 dark:text-emerald-400 hover:text-white border border-emerald-500/20 text-xs font-bold transition-all"
                        >
                            <span>⚡ Layanan PPOB</span>
                        </Link>
                        <Link
                            href="/syarat-ketentuan"
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300 text-xs font-bold transition-all"
                        >
                            <span>Syarat & Ketentuan</span>
                        </Link>
                        <button
                            onClick={toggleDarkMode}
                            className="p-2 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-600 dark:text-gray-300 transition-colors cursor-pointer"
                            title={isDark ? "Ganti ke Terang" : "Ganti ke Gelap"}
                        >
                            {isDark ? (
                                <svg className="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clipRule="evenodd" />
                                </svg>
                            ) : (
                                <svg className="w-4 h-4 text-slate-700" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
                                </svg>
                            )}
                        </button>
                    </div>
                </div>
            </header>

            {/* HERO SECTION */}
            <section className="relative overflow-hidden py-12 sm:py-16 bg-gradient-to-b from-orange-500/10 via-amber-500/5 to-transparent border-b border-gray-100 dark:border-white/5">
                <div className="absolute inset-0 pointer-events-none">
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-orange-500/15 rounded-full blur-3xl"></div>
                </div>

                <div className="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
                    <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-xs font-black uppercase tracking-wider mb-4">
                        <span>💡 Bantuan & Tanya Jawab</span>
                    </div>

                    <h1 className="text-3xl sm:text-5xl font-black text-gray-900 dark:text-white tracking-tight leading-tight">
                        Pusat Bantuan & <span className="text-transparent bg-clip-text bg-gradient-to-r from-orange-500 to-amber-500">FAQ</span>
                    </h1>

                    <p className="mt-3.5 text-sm sm:text-base text-gray-600 dark:text-gray-300 max-w-2xl mx-auto font-medium leading-relaxed">
                        Temukan solusi cepat dan jawaban atas pertanyaan umum seputar pembelian akun premium, PPOB 24 jam, cara bayar QRIS, hingga panduan klaim garansi di {storeName}.
                    </p>

                    {/* SEARCH INPUT BAR */}
                    <div className="mt-8 max-w-xl mx-auto relative">
                        <div className="relative flex items-center shadow-xl shadow-orange-500/5 rounded-2xl overflow-hidden border-2 border-orange-500/20 focus-within:border-orange-500 transition-all bg-white dark:bg-[#131b2e]">
                            <div className="pl-4 text-gray-400">
                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="11" cy="11" r="8" strokeWidth="2.5" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" strokeWidth="2.5" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Cari pertanyaan... (cth: pulsa, garansi, qris, private)"
                                className="w-full py-3.5 pl-3 pr-10 bg-transparent text-sm font-semibold text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none"
                            />
                            {searchQuery && (
                                <button
                                    onClick={() => setSearchQuery('')}
                                    className="pr-4 text-xs font-bold text-gray-400 hover:text-red-500 transition-colors"
                                >
                                    ✕ Bersihkan
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            {/* MAIN CONTENT CONTAINER */}
            <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                {/* CATEGORY TABS FILTER */}
                <div className="flex items-center gap-2 overflow-x-auto pb-3 mb-6 no-scrollbar">
                    {categories.map((cat) => {
                        const isActive = activeCategory === cat.id;
                        return (
                            <button
                                key={cat.id}
                                onClick={() => setActiveCategory(cat.id)}
                                className={`flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs font-bold whitespace-nowrap transition-all cursor-pointer ${
                                    isActive
                                        ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30 scale-102'
                                        : 'bg-white dark:bg-[#131b2e] text-gray-600 dark:text-gray-300 border border-gray-200/80 dark:border-white/5 hover:border-orange-500/50 hover:text-orange-500'
                                }`}
                            >
                                <span>{cat.icon}</span>
                                <span>{cat.label}</span>
                            </button>
                        );
                    })}
                </div>

                {/* CONTROLS HEADER */}
                <div className="flex items-center justify-between gap-4 mb-5 px-1">
                    <div className="text-xs font-bold text-gray-500 dark:text-gray-400">
                        Menampilkan <span className="text-orange-500 font-black">{filteredFaqs.length}</span> pertanyaan
                        {searchQuery && <span> untuk kata kunci "{searchQuery}"</span>}
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={expandAll}
                            className="text-[11px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 dark:hover:text-orange-400 transition cursor-pointer"
                        >
                            Buka Semua
                        </button>
                        <span className="text-gray-300 dark:text-gray-700">•</span>
                        <button
                            onClick={collapseAll}
                            className="text-[11px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 dark:hover:text-orange-400 transition cursor-pointer"
                        >
                            Tutup Semua
                        </button>
                    </div>
                </div>

                {/* ACCORDION LIST */}
                {filteredFaqs.length === 0 ? (
                    <div className="text-center py-16 bg-white dark:bg-[#131b2e] rounded-3xl border border-gray-100 dark:border-white/5 p-8 shadow-sm">
                        <div className="w-16 h-16 mx-auto mb-4 rounded-2xl bg-orange-100 dark:bg-orange-900/30 text-orange-500 flex items-center justify-center text-2xl">
                            🔍
                        </div>
                        <h3 className="text-lg font-black text-gray-900 dark:text-white">Pertanyaan Tidak Ditemukan</h3>
                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                            Tidak ada hasil yang cocok dengan kata kunci "{searchQuery}". Butuh bantuan langsung? Hubungi tim Admin Customer Service kami.
                        </p>
                        <a
                            href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(storeName)},%20saya%20mau%20tanya%20seputar%20FAQ:`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 mt-5 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs shadow-md transition-all cursor-pointer"
                        >
                            <span>Chat Admin WhatsApp</span>
                            <span>→</span>
                        </a>
                    </div>
                ) : (
                    <div className="space-y-3.5">
                        {filteredFaqs.map((faq) => {
                            const isOpen = openItemIds.includes(faq.id);
                            return (
                                <div
                                    key={faq.id}
                                    className={`rounded-2xl transition-all duration-200 border ${
                                        isOpen
                                            ? 'bg-white dark:bg-[#131b2e] border-orange-500/40 shadow-lg shadow-orange-500/5 ring-1 ring-orange-500/20'
                                            : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-orange-500/30'
                                    }`}
                                >
                                    <button
                                        onClick={() => toggleAccordion(faq.id)}
                                        className="w-full text-left p-4 sm:p-5 flex items-start justify-between gap-4 cursor-pointer"
                                    >
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-1.5">
                                                <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400">
                                                    {faq.categoryLabel}
                                                </span>
                                            </div>
                                            <h3 className="text-sm sm:text-base font-extrabold text-gray-900 dark:text-white leading-snug">
                                                {faq.q}
                                            </h3>
                                        </div>
                                        <div
                                            className={`w-7 h-7 rounded-xl flex items-center justify-center shrink-0 transition-transform duration-200 ${
                                                isOpen
                                                    ? 'bg-orange-500 text-white rotate-180'
                                                    : 'bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400'
                                            }`}
                                        >
                                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </button>

                                    {isOpen && (
                                        <div className="px-4 pb-5 sm:px-5 sm:pb-5 pt-1 border-t border-gray-100 dark:border-white/5">
                                            <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300 leading-relaxed font-medium whitespace-pre-line">
                                                {faq.a}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}

                {/* BOTTOM CALLOUT / CONTACT BOX */}
                <div className="mt-14 p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-orange-500/10 via-amber-500/5 to-emerald-500/10 border border-orange-500/20 text-center relative overflow-hidden">
                    <div className="w-14 h-14 mx-auto mb-3.5 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-2xl shadow-lg shadow-orange-500/30">
                        💬
                    </div>
                    <h3 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                        Masih Punya Pertanyaan Lain?
                    </h3>
                    <p className="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300 max-w-lg mx-auto font-medium leading-relaxed">
                        Tim Customer Service {storeName} siap membantu kendala pesanan atau pertanyaan Anda setiap saat 24 jam sehari.
                    </p>

                    <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <a
                            href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(storeName)},%20saya%20butuh%20bantuan:`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2.5 px-6 py-3 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-extrabold text-xs shadow-lg shadow-emerald-500/25 transition-all cursor-pointer transform hover:-translate-y-0.5"
                        >
                            <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                            </svg>
                            <span>Hubungi Admin WhatsApp</span>
                        </a>

                        {whatsappGroupLink && (
                            <a
                                href={whatsappGroupLink}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center gap-2 px-6 py-3 rounded-2xl bg-white dark:bg-[#131b2e] hover:bg-gray-100 dark:hover:bg-white/10 text-gray-800 dark:text-gray-200 border border-gray-200/80 dark:border-white/10 font-extrabold text-xs transition-all cursor-pointer"
                            >
                                <span>👥 Gabung Grup WhatsApp</span>
                            </a>
                        )}

                        <Link
                            href="/syarat-ketentuan"
                            className="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-300 font-bold text-xs transition-all"
                        >
                            <span>Baca Syarat & Ketentuan</span>
                            <span>→</span>
                        </Link>
                    </div>
                </div>
            </main>

            {/* MINIMAL FOOTER */}
            <footer className="mt-20 border-t border-gray-200/80 dark:border-white/5 py-8 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                <div className="max-w-4xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p>&copy; {new Date().getFullYear()} {storeName}. Hak Cipta Dilindungi.</p>
                    <div className="flex items-center gap-4">
                        <Link href="/" className="hover:text-orange-500 transition">Beranda</Link>
                        <span>•</span>
                        <Link href="/ppob" className="hover:text-orange-500 transition">Layanan PPOB</Link>
                        <span>•</span>
                        <Link href="/syarat-ketentuan" className="hover:text-orange-500 transition">Syarat & Ketentuan</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}

