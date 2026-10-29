import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Terms({ store = {}, terms = '', adminWhatsapp = '082317458496' }) {
    const storeName = store?.name || 'PremiumKita';
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

    const [isCopied, setIsCopied] = useState(false);
    const copyPageUrl = () => {
        navigator.clipboard.writeText(window.location.href);
        setIsCopied(true);
        setTimeout(() => setIsCopied(false), 2000);
    };

    return (
        <div className="min-h-[100dvh] bg-gray-50 dark:bg-[#0b0f19] text-gray-900 dark:text-gray-100 transition-colors duration-200 overflow-x-hidden">
            <Head>
                <title>{`Syarat & Ketentuan Layanan - ${storeName}`}</title>
                <meta name="description" content={`Syarat & Ketentuan, aturan transaksi, serta kebijakan garansi resmi di ${storeName}.`} />
            </Head>

            {/* TOP NAVIGATION BAR */}
            <header className="sticky top-0 z-40 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200/80 dark:border-white/5">
                <div className="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 h-14 sm:h-16 flex items-center justify-between gap-2 sm:gap-4">
                    <div className="flex items-center gap-2 sm:gap-3 min-w-0">
                        <Link
                            href="/"
                            className="flex items-center gap-1.5 sm:gap-2 px-2.5 sm:px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-orange-500 hover:text-white dark:hover:bg-orange-600 text-xs font-bold text-gray-700 dark:text-gray-300 transition-all shadow-sm shrink-0"
                        >
                            <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span className="hidden sm:inline">Beranda Toko</span>
                            <span className="sm:hidden text-[11px] font-extrabold">Beranda</span>
                        </Link>
                        <div className="h-5 w-px bg-gray-200 dark:bg-white/10 hidden sm:block"></div>
                        <div className="flex items-center gap-2 min-w-0">
                            {store?.logo ? (
                                <img src={store.logo} alt={storeName} className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg object-contain bg-orange-500/10 p-0.5 shrink-0" />
                            ) : (
                                <div className="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gradient-to-tr from-orange-500 to-amber-600 text-white font-black flex items-center justify-center text-[10px] sm:text-xs shadow-sm shrink-0">
                                    PK
                                </div>
                            )}
                            <div className="min-w-0">
                                <span className="font-black text-xs sm:text-sm tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5 truncate">
                                    <span className="truncate">{storeName}</span>
                                    <span className="hidden sm:inline-flex text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 shrink-0">
                                        LEGAL & TERMS
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Link
                            href="/faq"
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-500/10 hover:bg-orange-500 text-orange-600 dark:text-orange-400 hover:text-white border border-orange-500/20 text-xs font-bold transition-all"
                        >
                            <span>💡 Pusat Bantuan & FAQ</span>
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
            <section className="relative overflow-hidden py-12 sm:py-16 bg-gradient-to-b from-blue-500/10 via-indigo-500/5 to-transparent border-b border-gray-100 dark:border-white/5">
                <div className="absolute inset-0 pointer-events-none">
                    <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl"></div>
                </div>

                <div className="relative max-w-4xl mx-auto px-4 sm:px-6 text-center">
                    <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/15 border border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs font-black uppercase tracking-wider mb-4">
                        <span>📜 Ketentuan & Kebijakan</span>
                    </div>

                    <h1 className="text-3xl sm:text-5xl font-black text-gray-900 dark:text-white tracking-tight leading-tight">
                        Syarat & <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500">Ketentuan Layanan</span>
                    </h1>

                    <p className="mt-3.5 text-sm sm:text-base text-gray-600 dark:text-gray-300 max-w-2xl mx-auto font-medium leading-relaxed">
                        Harap membaca dan memahami syarat dan ketentuan berikut sebelum melakukan transaksi di platform {storeName}. Dengan bertransaksi, Anda menyetujui seluruh ketentuan yang berlaku.
                    </p>

                    <div className="mt-6 flex items-center justify-center gap-3">
                        <button
                            onClick={copyPageUrl}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 hover:text-orange-500 dark:hover:text-orange-400 transition cursor-pointer shadow-sm"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            <span>{isCopied ? 'Tautan Disalin!' : 'Salin Tautan'}</span>
                        </button>
                    </div>
                </div>
            </section>

            {/* THREE KEY PILLARS */}
            <section className="max-w-4xl mx-auto px-4 sm:px-6 -mt-6">
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-white dark:bg-[#131b2e] p-5 rounded-2xl border border-gray-200/80 dark:border-white/5 shadow-md flex items-center gap-3.5">
                        <div className="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xl shrink-0">
                            🛡️
                        </div>
                        <div>
                            <h3 className="text-xs font-black uppercase text-gray-900 dark:text-white">Garansi Penuh</h3>
                            <p className="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Garansi ganti akun aktif selama durasi paket.</p>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-[#131b2e] p-5 rounded-2xl border border-gray-200/80 dark:border-white/5 shadow-md flex items-center gap-3.5">
                        <div className="w-11 h-11 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center text-xl shrink-0">
                            ✓
                        </div>
                        <div>
                            <h3 className="text-xs font-black uppercase text-gray-900 dark:text-white">Proses Instan</h3>
                            <p className="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Pengiriman akun & pulsa otomatis detik ini juga.</p>
                        </div>
                    </div>

                    <div className="bg-white dark:bg-[#131b2e] p-5 rounded-2xl border border-gray-200/80 dark:border-white/5 shadow-md flex items-center gap-3.5">
                        <div className="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center text-xl shrink-0">
                            🔒
                        </div>
                        <div>
                            <h3 className="text-xs font-black uppercase text-gray-900 dark:text-white">Aman & Terpercaya</h3>
                            <p className="text-[11px] text-gray-500 dark:text-gray-400 font-medium">Data transaksi terlindungi dengan standar enkripsi.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* MAIN CONTENT */}
            <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div className="bg-white dark:bg-[#131b2e] rounded-3xl border border-gray-200/80 dark:border-white/5 p-6 sm:p-10 shadow-xl shadow-black/5">
                    
                    {/* Render database terms if exists */}
                    {terms && terms.trim().length > 0 ? (
                        <div className="terms-body">
                            <div 
                                className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed font-medium whitespace-pre-line space-y-4"
                                dangerouslySetInnerHTML={{ __html: terms }}
                            />
                        </div>
                    ) : (
                        /* Default Structured Terms if database setting is empty */
                        <div className="space-y-8 text-sm text-gray-700 dark:text-gray-300 leading-relaxed font-medium">
                            <section>
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="w-6 h-6 rounded-lg bg-orange-500 text-white font-black text-xs flex items-center justify-center">1</span>
                                    <h2 className="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">Ketentuan Umum</h2>
                                </div>
                                <p className="text-xs sm:text-sm pl-8 text-gray-600 dark:text-gray-400">
                                    {storeName} adalah penyedia layanan pembelian produk digital, aplikasi akun premium legal, serta layanan PPOB (Pulsa, Paket Data, Token Listrik PLN, Top Up Game & E-Wallet) otomatis. Dengan melakukan pemesanan di situs kami, Anda menyatakan telah berusia cakap hukum dan menyetujui seluruh ketentuan ini.
                                </p>
                            </section>

                            <section>
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="w-6 h-6 rounded-lg bg-orange-500 text-white font-black text-xs flex items-center justify-center">2</span>
                                    <h2 className="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">Proses Pemesanan & Pembayaran</h2>
                                </div>
                                <ul className="list-disc pl-12 space-y-1.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    <li>Pembeli wajib mencantumkan nomor WhatsApp aktif dan alamat email yang valid untuk pengiriman invoice dan detail akun.</li>
                                    <li>Pembayaran melalui QRIS diproses secara otomatis oleh gateway perbankan. Pastikan Anda mentransfer tepat sesuai nominal hingga 3 digit kode unik (apabila ada).</li>
                                    <li>Pembayaran menggunakan Saldo Akun Member akan langsung memotong saldo seketika tanpa jeda waktu.</li>
                                    <li>Batas waktu pembayaran QRIS adalah 5 menit sejak invoice diterbitkan. Invoice yang lewat batas waktu akan dibatalkan otomatis oleh sistem.</li>
                                </ul>
                            </section>

                            <section>
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="w-6 h-6 rounded-lg bg-orange-500 text-white font-black text-xs flex items-center justify-center">3</span>
                                    <h2 className="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">Kebijakan Garansi & Penggantian (Warranty)</h2>
                                </div>
                                <div className="pl-8 space-y-2 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    <p>Kami menjamin seluruh produk akun premium bergaransi selama masa aktif paket yang dibeli:</p>
                                    <ul className="list-disc pl-5 space-y-1">
                                        <li><strong>Garansi Berlaku Untuk:</strong> Akun mengalami force logout, status langganan tiba-tiba kembali ke free, atau password error yang bukan disebabkan oleh kelalaian pembeli.</li>
                                        <li><strong>Garansi Hangus / Tidak Berlaku Apabila:</strong> Pembeli mengubah email login, mengubah profil milik pengguna lain pada jenis akun Sharing, membagikan akun ke pihak ketiga tanpa izin, atau melanggar aturan spesifik produk.</li>
                                        <li><strong>Waktu Penyelesaian Klaim:</strong> Proses reset atau penggantian akun (replace) dilakukan maksimal 1x24 jam kerja sejak laporan diterima oleh Customer Service kami.</li>
                                    </ul>
                                </div>
                            </section>

                            <section>
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="w-6 h-6 rounded-lg bg-orange-500 text-white font-black text-xs flex items-center justify-center">4</span>
                                    <h2 className="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">Ketentuan Layanan PPOB & Pulsa</h2>
                                </div>
                                <ul className="list-disc pl-12 space-y-1.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                    <li>Server PPOB terhubung langsung ke operator switching multi-biller secara otomatis dan real-time.</li>
                                    <li>Pembeli bertanggung jawab penuh atas kebenaran Nomor HP, No. Meter PLN, atau User ID Game yang diinput. Transaksi sukses ke nomor yang salah tidak dapat dibatalkan atau ditarik kembali.</li>
                                    <li>Jika terjadi gangguan jaringan dari operator pusat sehingga transaksi berstatus gagal, dana pembayaran akan dikembalikan 100% ke Saldo Akun Member.</li>
                                </ul>
                            </section>

                            <section>
                                <div className="flex items-center gap-2.5 mb-2">
                                    <span className="w-6 h-6 rounded-lg bg-orange-500 text-white font-black text-xs flex items-center justify-center">5</span>
                                    <h2 className="text-base sm:text-lg font-extrabold text-gray-900 dark:text-white">Kebijakan Pengembalian Dana (Refund)</h2>
                                </div>
                                <p className="text-xs sm:text-sm pl-8 text-gray-600 dark:text-gray-400">
                                    Pengembalian dana (refund) hanya dapat dilakukan apabila stok produk tidak tersedia dan tim {storeName} tidak dapat memberikan akun pengganti dalam kurun waktu 1x24 jam. Refund akan dikreditkan ke Saldo Akun atau dikembalikan ke rekening/e-wallet pembeli tanpa potongan.
                                </p>
                            </section>
                        </div>
                    )}

                    {/* CONTACT BOX INSIDE TERMS */}
                    <div className="mt-10 pt-8 border-t border-gray-100 dark:border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4 bg-gray-50 dark:bg-white/[0.02] p-5 rounded-2xl">
                        <div>
                            <h4 className="text-sm font-extrabold text-gray-900 dark:text-white">Pertanyaan seputar Syarat & Ketentuan?</h4>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Customer Care kami siap membantu memberikan penjelasan detail.</p>
                        </div>
                        <a
                            href={`https://wa.me/${effectiveAdminWa}?text=Halo%20Admin%20${encodeURIComponent(storeName)},%20saya%20ingin%20bertanya%20mengenai%20syarat%20dan%20ketentuan:`}
                            target="_blank"
                            rel="noreferrer"
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs shadow-md transition-all shrink-0 cursor-pointer"
                        >
                            <span>Chat Admin CS</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
            </main>

            {/* MINIMAL FOOTER */}
            <footer className="mt-16 border-t border-gray-200/80 dark:border-white/5 py-8 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
                <div className="max-w-4xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p>&copy; {new Date().getFullYear()} {storeName}. Hak Cipta Dilindungi.</p>
                    <div className="flex items-center gap-4">
                        <Link href="/" className="hover:text-orange-500 transition">Beranda</Link>
                        <span>•</span>
                        <Link href="/ppob" className="hover:text-orange-500 transition">Layanan PPOB</Link>
                        <span>•</span>
                        <Link href="/faq" className="hover:text-orange-500 transition">Pusat Bantuan & FAQ</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}

