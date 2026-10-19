import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

import { OPERATORS, GAMES, EWALLETS, STREAMING_SERVICES, PPOB_TABS, BILL_TABS, BILL_TAB_SERVICE, fmt } from './Partials/PpobConstants';
import { BILL_SERVICES, extractBillSummary } from './Partials/PpobBillUtils';
import { PDAM_PRODUCTS } from './Partials/PdamProducts';
import PpobHeader from './Partials/PpobHeader';
import PpobPromoBanner from './Partials/PpobPromoBanner';
import PpobTabNav from './Partials/PpobTabNav';
import PpobInputSection from './Partials/PpobInputSection';
import PpobProductGrid from './Partials/PpobProductGrid';
import PpobTagihanPanel from './Partials/PpobTagihanPanel';
import PpobCheckoutModal from './Partials/PpobCheckoutModal';
import PpobQrisModal from './Partials/PpobQrisModal';
import PpobSuccessModal from './Partials/PpobSuccessModal';
import PpobMobileNav from './Partials/PpobMobileNav';

export default function PpobIndex({ store, initialPulsa }) {
  const { auth } = usePage().props;
  const user = auth?.user;
  const userBalance = user?.balance ?? 0;

  const VALID_TABS = PPOB_TABS.map((t) => t.id);

  // Baca parameter ?tab= dari URL saat inisialisasi
  const [activeTab, setActiveTab] = useState(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const tabParam = params.get('tab');
      // Dukung URL lama ?tab=tagihan -> arahkan ke PDAM
      if (tabParam === 'tagihan') return 'pdam';
      if (VALID_TABS.includes(tabParam)) return tabParam;
    }
    return 'pulsa';
  });

  const handleSwitchTab = (tabId) => {
    setActiveTab(tabId);
    setSelectedProduct(null);
    // Reset hasil inquiry agar tidak ada nominal dari layanan lain yang terbawa.
    setTagihanInquiryResult(null);
    if (typeof window !== 'undefined' && window.history) {
      const url = new URL(window.location);
      url.searchParams.set('tab', tabId);
      window.history.replaceState({}, '', url);
    }
  };

  useEffect(() => {
    const handlePopState = () => {
      const params = new URLSearchParams(window.location.search);
      const tabParam = params.get('tab');
      if (tabParam === 'tagihan') {
        setActiveTab('pdam');
      } else if (tabParam && VALID_TABS.includes(tabParam)) {
        setActiveTab(tabParam);
      }
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  const [phoneNumber, setPhoneNumber] = useState('');
  const [selectedOperator, setSelectedOperator] = useState(OPERATORS[0]);
  const [pulsaType, setPulsaType] = useState('pulsa'); // 'pulsa' | 'data'

  // PLN Token (prabayar) state
  const [plnType, setPlnType] = useState('prepaid'); // 'prepaid' (token) — pascabayar punya tab sendiri
  const [plnCustomerId, setPlnCustomerId] = useState('');

  // Game state
  const [selectedGame, setSelectedGame] = useState(GAMES[0]);
  const [gameUserId, setGameUserId] = useState('');
  const [gameZoneId, setGameZoneId] = useState('');

  // E-Wallet state
  const [selectedEwallet, setSelectedEwallet] = useState(EWALLETS[0]);
  const [ewalletPhone, setEwalletPhone] = useState('');

  // Streaming state
  const [selectedStreaming, setSelectedStreaming] = useState(STREAMING_SERVICES[0]);
  const [streamingAccount, setStreamingAccount] = useState('');
  const [streamingSearch, setStreamingSearch] = useState('');

  // Tagihan state (dipakai tab PDAM, Tagihan PLN, dan BPJS)
  // tagihanType mengikuti tab aktif sehingga tiap layanan punya menu sendiri.
  const [tagihanType, setTagihanType] = useState('pdam');
  const [tagihanCustomerId, setTagihanCustomerId] = useState('');
  const [tagihanInquiryResult, setTagihanInquiryResult] = useState(null);
  const [isTagihanInquiring, setIsTagihanInquiring] = useState(false);
  const [tagihanPdamRegion, setTagihanPdamRegion] = useState('400191'); // Default PDAM productID
  const [tagihanPeriod, setTagihanPeriod] = useState('01'); // BPJS: jumlah bulan yang dibayar
  const [tagihanPdamSearch, setTagihanPdamSearch] = useState('');

  // Product Selection & Loading
  const [products, setProducts] = useState(initialPulsa || []);
  const [isLoadingProducts, setIsLoadingProducts] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState(null);

  // Payment & Checkout State
  const [paymentMethod, setPaymentMethod] = useState(userBalance >= 10000 ? 'balance' : 'qris');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [checkoutModal, setCheckoutModal] = useState(false);
  const [qrisData, setQrisData] = useState(null);
  const [qrisTimer, setQrisTimer] = useState(300);
  const [successOrder, setSuccessOrder] = useState(null);
  const [isCopied, setIsCopied] = useState(false);

  // Dark Mode Detection
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

  // Auto detect operator from phone number
  useEffect(() => {
    if (phoneNumber.length >= 4) {
      const prefix = phoneNumber.substring(0, 4);
      const matched = OPERATORS.find((op) => op.prefixes.includes(prefix));
      if (matched && matched.id !== selectedOperator.id) {
        setSelectedOperator(matched);
      }
    }
  }, [phoneNumber]);

  // Sinkronkan jenis layanan tagihan dengan tab aktif (menu terpisah per layanan).
  // Tab "Tagihan PLN" punya dua sub-layanan: pascabayar dan non-taglis.
  useEffect(() => {
    if (activeTab === 'pdam') setTagihanType('pdam');
    else if (activeTab === 'bpjs') setTagihanType('bpjs');
    else if (activeTab === 'pln_postpaid') setTagihanType('pln_postpaid');

    if (BILL_TABS.includes(activeTab)) {
      setTagihanInquiryResult(null);
      setSelectedProduct(null);
    }
  }, [activeTab]);

  const activeBillService = BILL_SERVICES.find((s) => s.id === tagihanType) || BILL_SERVICES[0];
  const activeBillProductId = activeBillService.id === 'pdam' ? tagihanPdamRegion : activeBillService.productId;

  // Fetch products when category / operator / game / ewallet / streaming changes.
  // Tab tagihan tidak memakai katalog produk: nominalnya berasal dari hasil inquiry.
  useEffect(() => {
    if (BILL_TABS.includes(activeTab)) {
      setProducts([]);
      setSelectedProduct(null);
      setIsLoadingProducts(false);
      return;
    }

    let category = 'pulsa';
    let prefix = '21';

    if (activeTab === 'pulsa') {
      category = 'pulsa';
      prefix = selectedOperator.id;
    } else if (activeTab === 'pln') {
      category = 'pln';
      prefix = '100302'; // PLN Token
    } else if (activeTab === 'game') {
      category = 'game';
      prefix = selectedGame.id;
    } else if (activeTab === 'ewallet') {
      category = 'ewallet';
      prefix = selectedEwallet.id;
    } else if (activeTab === 'streaming') {
      category = 'streaming';
      prefix = selectedStreaming.id;
    }

    fetchProducts(category, prefix);
  }, [activeTab, selectedOperator, selectedGame, selectedEwallet, selectedStreaming]);

  const fetchProducts = async (cat, pref) => {
    setIsLoadingProducts(true);
    setSelectedProduct(null);
    try {
      const res = await fetch(`/ppob/products?category=${cat}&prefix=${pref}`);
      const data = await res.json();
      if (data.status === 'success') {
        setProducts(data.data || []);
      }
    } catch (err) {
      console.error('Error fetching PPOB products:', err);
    } finally {
      setIsLoadingProducts(false);
    }
  };

  // Inquiry tagihan (PDAM, Tagihan PLN, BPJS) memakai handler tagihan terpadu.
  // Handler khusus PLN tab lama dihapus; tab "Tagihan PLN" memakai alur yang sama.

  // Tagihan Inquiry (PDAM, PLN Pascabayar / Non-Taglis / Prabayar, BPJS)
  const handleTagihanInquiry = async () => {
    const minLength = activeBillService.minLength || 4;
    if (!tagihanCustomerId || tagihanCustomerId.length < minLength) {
      Swal.fire({
        icon: 'warning',
        title: 'Periksa Nomor',
        text: `Masukkan nomor pelanggan ${activeBillService.label} dengan benar.`,
        confirmButtonColor: '#10b981',
      });
      return;
    }

    setIsTagihanInquiring(true);
    setTagihanInquiryResult(null);
    setSelectedProduct(null);
    try {
      const payload = {
        product_id: activeBillProductId,
        customer_id: tagihanCustomerId,
      };
      if (activeBillService.id === 'bpjs') {
        payload.periode = tagihanPeriod;
      }

      const res = await fetch('/ppob/inquiry', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json();

      if (data.status === 'success') {
        const summary = extractBillSummary(data);
        setTagihanInquiryResult({ ...data, summary });
        setSelectedProduct({
          code: activeBillProductId,
          name: `${activeBillService.label} - ${summary.customerName}`,
          price: summary.grandTotal,
          inquiryKey: data.inquiry_key,
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Inquiry Gagal',
          text: data.message || 'Nomor pelanggan tidak ditemukan atau tagihan sudah lunas.',
          confirmButtonColor: '#10b981',
        });
      }
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Koneksi Error',
        text: 'Gagal terhubung ke server inquiry.',
        confirmButtonColor: '#10b981',
      });
    } finally {
      setIsTagihanInquiring(false);
    }
  };

  // Target Customer ID based on current active tab
  const currentTargetNumber = useMemo(() => {
    if (activeTab === 'pulsa') return phoneNumber;
    if (activeTab === 'pln') return plnCustomerId;
    if (activeTab === 'game') return gameZoneId ? `${gameUserId} (${gameZoneId})` : gameUserId;
    if (activeTab === 'ewallet') return ewalletPhone;
    if (activeTab === 'streaming') return streamingAccount;
    if (BILL_TABS.includes(activeTab)) return tagihanCustomerId;
    return phoneNumber;
  }, [activeTab, phoneNumber, plnCustomerId, gameUserId, gameZoneId, ewalletPhone, streamingAccount, tagihanCustomerId]);

  // Validation before opening checkout
  const handleOpenCheckout = (product) => {
    if (product.status === 'maintenance') {
      Swal.fire({
        icon: 'warning',
        title: 'Produk Sedang Maintenance',
        text: 'Layanan ini sedang mengalami pemeliharaan sistem. Silakan coba beberapa saat lagi atau pilih nominal lain.',
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (product.status === 'inactive') {
      Swal.fire({
        icon: 'info',
        title: 'Produk Tidak Tersedia',
        text: 'Produk ini sedang dinonaktifkan.',
        confirmButtonColor: '#10b981',
      });
      return;
    }

    setSelectedProduct(product);

    if (activeTab === 'pulsa' && (!phoneNumber || phoneNumber.length < 9)) {
      Swal.fire({
        icon: 'warning',
        title: 'Nomor HP Belum Lengkap',
        text: 'Masukkan nomor handphone tujuan dengan benar (minimal 10 digit).',
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (activeTab === 'pln' && (!plnCustomerId || plnCustomerId.length < 8)) {
      Swal.fire({
        icon: 'warning',
        title: 'Nomor Meter Belum Lengkap',
        text: 'Masukkan Nomor Meter atau ID Pelanggan PLN.',
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (activeTab === 'game' && (!gameUserId || gameUserId.length < 3)) {
      Swal.fire({
        icon: 'warning',
        title: 'ID Game Belum Diisi',
        text: 'Masukkan User ID akun game Anda.',
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (activeTab === 'game' && selectedGame.needZone && !gameZoneId) {
      Swal.fire({
        icon: 'warning',
        title: 'Zone / Server ID Diperlukan',
        text: `Silakan isi ${selectedGame.zoneLabel || 'Server / Zone ID'} game Anda.`,
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (activeTab === 'ewallet' && (!ewalletPhone || ewalletPhone.length < 9)) {
      Swal.fire({
        icon: 'warning',
        title: 'Nomor E-Wallet Belum Diisi',
        text: 'Masukkan nomor akun e-wallet Anda.',
        confirmButtonColor: '#10b981',
      });
      return;
    }
    if (activeTab === 'streaming' && (!streamingAccount || streamingAccount.trim().length < 3)) {
      Swal.fire({
        icon: 'warning',
        title: 'Akun Belum Diisi',
        text: `Masukkan ${selectedStreaming.placeholder || 'Email / Nomor HP / Akun'} tujuan Anda.`,
        confirmButtonColor: '#10b981',
      });
      return;
    }

    if (BILL_TABS.includes(activeTab)) {
      if (!tagihanInquiryResult || !product.inquiryKey) {
        Swal.fire({
          icon: 'warning',
          title: 'Inquiry Belum Dilakukan',
          text: 'Lakukan cek tagihan terlebih dahulu sebelum melanjutkan pembayaran.',
          confirmButtonColor: '#10b981',
        });
        return;
      }
    }

    setCheckoutModal(true);
  };

  // Submit payment
  const handleConfirmPayment = async () => {
    if (!selectedProduct || isSubmitting) return;

    if (paymentMethod === 'balance') {
      if (!user) {
        Swal.fire({
          icon: 'info',
          title: 'Login Diperlukan',
          text: 'Silakan login terlebih dahulu untuk menggunakan pembayaran Saldo Akun.',
          showCancelButton: true,
          confirmButtonText: 'Login Sekarang',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#10b981',
        }).then((res) => {
          if (res.isConfirmed) window.location.href = '/login';
        });
        return;
      }
      if (userBalance < selectedProduct.price) {
        Swal.fire({
          icon: 'error',
          title: 'Saldo Tidak Cukup',
          text: `Saldo Anda ${fmt.rp(userBalance)}, total belanja ${fmt.rp(selectedProduct.price)}. Silakan gunakan QRIS atau Top Up saldo Anda.`,
          showCancelButton: true,
          confirmButtonText: 'Top Up Saldo Sekarang',
          cancelButtonText: 'Gunakan QRIS',
          confirmButtonColor: '#10b981',
          cancelButtonColor: '#64748b',
        }).then((res) => {
          if (res.isConfirmed) {
            window.location.href = '/profile';
          } else {
            setPaymentMethod('qris');
          }
        });
        return;
      }
    }

    setIsSubmitting(true);
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const res = await fetch('/ppob/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          product_code: selectedProduct.code,
          product_name: selectedProduct.name,
          customer_id: currentTargetNumber,
          amount: selectedProduct.price,
          payment_method: paymentMethod,
          inquiry_key: selectedProduct.inquiryKey,
          whatsapp: user?.whatsapp || (activeTab === 'pulsa' ? phoneNumber : ''),
          email: user?.email || '',
        }),
      });

      let data;
      const rawText = await res.text();
      try {
        data = JSON.parse(rawText);
      } catch (jsonErr) {
        console.error('[PPOB-JSON-PARSE-ERR]', rawText);
        throw new Error('Respon dari server tidak valid (' + res.status + '). Silakan coba lagi.');
      }

      if (res.ok && data.status === 'success') {
        if (paymentMethod === 'balance') {
          setCheckoutModal(false);
          setSuccessOrder(data);
        } else {
          // QRIS mode
          setCheckoutModal(false);
          setQrisData(data);
          setQrisTimer(300);
          startQrisPolling(data.order_id);
        }
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Transaksi Gagal',
          text: data?.message || `Terjadi kesalahan saat memproses pesanan (Kode: ${res.status}).`,
          confirmButtonColor: '#10b981',
        });
      }
    } catch (err) {
      console.error('[PPOB-CHECKOUT-ERR]', err);
      Swal.fire({
        icon: 'error',
        title: 'Koneksi Error',
        text: err.message || 'Gagal menghubungi server pembayaran. Pastikan internet Anda aktif.',
        confirmButtonColor: '#10b981',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  // QRIS Polling & Countdown
  useEffect(() => {
    let interval = null;
    if (qrisData && qrisTimer > 0) {
      interval = setInterval(() => {
        setQrisTimer((prev) => prev - 1);
      }, 1000);
    } else if (qrisTimer === 0 && qrisData) {
      setQrisData(null);
      Swal.fire({
        icon: 'warning',
        title: 'Waktu Habis',
        text: 'Batas pembayaran QRIS telah kedaluwarsa. Silakan ulangi pesanan.',
        confirmButtonColor: '#10b981',
      });
    }
    return () => clearInterval(interval);
  }, [qrisData, qrisTimer]);

  const startQrisPolling = (orderId) => {
    const poller = setInterval(async () => {
      try {
        const res = await fetch(`/ppob/status/${orderId}`);
        const data = await res.json();
        if (data.status === 'completed' || data.payment_status === 'paid') {
          clearInterval(poller);
          setQrisData(null);
          setCheckoutModal(false);
          setSuccessOrder(data);
        }
      } catch (e) {
        // quiet poll
      }
    }, 3500);
  };

  const copyToClipboard = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setIsCopied(true);
    setTimeout(() => setIsCopied(false), 2000);
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0b0f19] text-gray-900 dark:text-gray-100 transition-colors duration-200">
      <Head title="Dashboard Layanan PPOB & Pulsa - NandarStore" />

      {/* TOP HEADER */}
      <PpobHeader
        store={store}
        user={user}
        userBalance={userBalance}
        isDark={isDark}
        toggleDarkMode={toggleDarkMode}
      />

      {/* HERO SECTION */}
      <section className="relative overflow-hidden pt-5 pb-6 sm:pt-8 sm:pb-12 bg-gradient-to-b from-emerald-500/5 via-transparent to-transparent">
        <div className="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8">
          <PpobPromoBanner />
          <PpobTabNav activeTab={activeTab} onSwitchTab={handleSwitchTab} />
        </div>
      </section>

      {/* WORKSPACE AREA */}
      <main className="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8 pb-20">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-5 lg:gap-8 items-start">
          {/* KOLOM KIRI: INPUT DATA & OPERATOR SELECTOR (5 COLS) */}
          <div className="lg:col-span-5 space-y-6">
            <PpobInputSection
              activeTab={activeTab}
              phoneNumber={phoneNumber}
              setPhoneNumber={setPhoneNumber}
              selectedOperator={selectedOperator}
              setSelectedOperator={setSelectedOperator}
              plnType={plnType}
              setPlnType={setPlnType}
              plnCustomerId={plnCustomerId}
              setPlnCustomerId={setPlnCustomerId}
              setSelectedProduct={setSelectedProduct}
              selectedGame={selectedGame}
              setSelectedGame={setSelectedGame}
              gameUserId={gameUserId}
              setGameUserId={setGameUserId}
              gameZoneId={gameZoneId}
              setGameZoneId={setGameZoneId}
              selectedEwallet={selectedEwallet}
              setSelectedEwallet={setSelectedEwallet}
              ewalletPhone={ewalletPhone}
              setEwalletPhone={setEwalletPhone}
              selectedStreaming={selectedStreaming}
              setSelectedStreaming={setSelectedStreaming}
              streamingAccount={streamingAccount}
              setStreamingAccount={setStreamingAccount}
              streamingSearch={streamingSearch}
              setStreamingSearch={setStreamingSearch}
              onSwitchTab={handleSwitchTab}
              tagihanType={tagihanType}
              setTagihanType={setTagihanType}
              tagihanCustomerId={tagihanCustomerId}
              setTagihanCustomerId={setTagihanCustomerId}
              tagihanInquiryResult={tagihanInquiryResult}
              setTagihanInquiryResult={setTagihanInquiryResult}
              isTagihanInquiring={isTagihanInquiring}
              handleTagihanInquiry={handleTagihanInquiry}
              tagihanPdamRegion={tagihanPdamRegion}
              setTagihanPdamRegion={setTagihanPdamRegion}
              tagihanPeriod={tagihanPeriod}
              setTagihanPeriod={setTagihanPeriod}
              tagihanPdamSearch={tagihanPdamSearch}
              setTagihanPdamSearch={setTagihanPdamSearch}
            />
          </div>

          {/* KOLOM KANAN: PANEL TAGIHAN ATAU GRID PRODUK (7 COLS) */}
          {BILL_TABS.includes(activeTab) ? (
            <PpobTagihanPanel
              inquiryResult={tagihanInquiryResult}
              isInquiring={isTagihanInquiring}
              onOpenCheckout={handleOpenCheckout}
              serviceLabel={activeBillService.label}
            />
          ) : (
            <PpobProductGrid
              products={products}
              isLoadingProducts={isLoadingProducts}
              selectedProduct={selectedProduct}
              onOpenCheckout={handleOpenCheckout}
            />
          )}
        </div>
      </main>

      {/* FOOTER */}
      <footer className="mt-16 border-t border-gray-200/80 dark:border-white/5 py-8 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p>&copy; {new Date().getFullYear()} {store?.name || 'NandarStore'}. Hak Cipta Dilindungi.</p>
          <div className="flex items-center gap-4">
            <Link href="/" className="hover:text-emerald-500 transition">Beranda Toko</Link>
            <span>•</span>
            <Link href="/faq" className="hover:text-emerald-500 transition">Pusat Bantuan &amp; FAQ</Link>
            <span>•</span>
            <Link href="/syarat-ketentuan" className="hover:text-emerald-500 transition">Syarat &amp; Ketentuan</Link>
          </div>
        </div>
      </footer>

      {/* CHECKOUT MODAL DRAWER */}
      <PpobCheckoutModal
        isOpen={checkoutModal}
        onClose={() => setCheckoutModal(false)}
        selectedProduct={selectedProduct}
        currentTargetNumber={currentTargetNumber}
        paymentMethod={paymentMethod}
        setPaymentMethod={setPaymentMethod}
        userBalance={userBalance}
        isSubmitting={isSubmitting}
        onConfirmPayment={handleConfirmPayment}
      />

      {/* QRIS POPUP DISPLAY */}
      <PpobQrisModal
        qrisData={qrisData}
        onClose={() => setQrisData(null)}
        qrisTimer={qrisTimer}
        copyToClipboard={copyToClipboard}
        isCopied={isCopied}
      />

      {/* SUCCESS MODAL POPUP */}
      <PpobSuccessModal
        successOrder={successOrder}
        onClose={() => setSuccessOrder(null)}
        copyToClipboard={copyToClipboard}
        isCopied={isCopied}
      />

      {/* BOTTOM NAVIGATION BAR FOR MOBILE (HP) */}
      <PpobMobileNav user={user} />
    </div>
  );
}

