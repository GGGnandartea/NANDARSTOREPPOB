import React, { useState, useEffect, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

// ─── ICON COMPONENTS (PROFESSIONAL VECTOR SVGS) ─────────────────────────────
const Icons = {
  Pulsa: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="5" y="2" width="14" height="20" rx="3" />
      <path d="M12 18h.01" />
      <path d="M9 5h6" />
      <path d="M17 7a3 3 0 0 1 3 3" />
      <path d="M17 4a6 6 0 0 1 6 6" />
    </svg>
  ),
  Pln: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
      <path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z" />
    </svg>
  ),
  Game: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="2" y="6" width="20" height="12" rx="6" />
      <line x1="6" y1="12" x2="10" y2="12" />
      <line x1="8" y1="10" x2="8" y2="14" />
      <circle cx="15.5" cy="12" r="1" fill="currentColor" />
      <circle cx="18.5" cy="12" r="1" fill="currentColor" />
    </svg>
  ),
  Ewallet: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="2" y="4" width="20" height="16" rx="3" />
      <path d="M2 10h20" />
      <circle cx="17" cy="14" r="1.5" fill="currentColor" />
    </svg>
  ),
  Streaming: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="2" y="3" width="20" height="14" rx="2" />
      <polygon points="10 8 16 11 10 14 10 8" fill="currentColor" stroke="none" />
      <line x1="8" y1="21" x2="16" y2="21" />
      <line x1="12" y1="17" x2="12" y2="21" />
    </svg>
  ),
  Tagihan: ({ className = "w-6 h-6" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z" />
      <path d="M12 9v4" />
      <path d="M10 13h4" />
    </svg>
  ),
  Search: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="11" cy="11" r="8" />
      <line x1="21" y1="21" x2="16.65" y2="16.65" />
    </svg>
  ),
  Lightning: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="currentColor">
      <path d="M13 2L3 14h8l-2 8 10-12h-8l2-8z" />
    </svg>
  ),
  Shield: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      <polyline points="9 12 11 14 15 10" />
    </svg>
  ),
  CreditCard: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <rect x="2" y="4" width="20" height="16" rx="3" />
      <line x1="2" y1="10" x2="22" y2="10" />
    </svg>
  ),
  Clock: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10" />
      <polyline points="12 6 12 12 16 14" />
    </svg>
  ),
  Check: ({ className = "w-4 h-4" }) => (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="20 6 9 17 4 12" />
    </svg>
  ),
};

const OPERATORS = [
  { id: '21', name: 'Telkomsel', code: 'telkomsel', prefixes: ['0811', '0812', '0813', '0821', '0822', '0823', '0851', '0852', '0853'] },
  { id: '11', name: 'Indosat Ooredoo', code: 'indosat', prefixes: ['0814', '0815', '0816', '0855', '0856', '0857', '0858'] },
  { id: '31', name: 'XL Axiata', code: 'xl', prefixes: ['0817', '0818', '0819', '0859', '0877', '0878'] },
  { id: '41', name: 'Tri (3)', code: 'tri', prefixes: ['0895', '0896', '0897', '0898', '0899'] },
  { id: '51', name: 'Axis', code: 'axis', prefixes: ['0831', '0832', '0833', '0838'] },
  { id: '81', name: 'Smartfren', code: 'smartfren', prefixes: ['0881', '0882', '0883', '0884', '0885', '0886', '0887', '0888', '0889'] },
];

const GAMES = [
  { id: 'AAO.AAAL', name: 'Mobile Legends', badge: 'MLBB', needZone: true, zoneLabel: 'Zone ID', placeholderId: 'Contoh: 12345678', placeholderZone: 'Contoh: 2149' },
  { id: 'AAO.AAAF', name: 'Free Fire', badge: 'FF', needZone: false, placeholderId: 'Contoh: 87654321' },
  { id: 'AAO.AAAJ', name: 'Honor of Kings', badge: 'HOK', needZone: false, placeholderId: 'Contoh: 98765432' },
  { id: 'AAO.AAAG', name: 'Genshin Impact', badge: 'GI', needZone: true, zoneLabel: 'Server', servers: ['Asia', 'America', 'Europe', 'TW_HK_MO'], placeholderId: 'UID Genshin' },
  { id: 'AAO.AAAS', name: 'PUBG Mobile', badge: 'PUBG', needZone: false, placeholderId: 'Nomor ID Karakter PUBG' },
  { id: 'AAO.AAAX', name: 'Valorant', badge: 'VAL', needZone: false, placeholderId: 'Riot ID (Nama#Tag)' },
  { id: 'AAO.AAAU', name: 'Roblox', badge: 'RBLX', needZone: false, placeholderId: 'Username Roblox' },
];

const EWALLETS = [
  { id: 'EWAL.DANA', name: 'DANA', badge: 'DANA' },
  { id: 'EWAL.GOPAY', name: 'GoPay', badge: 'GOPAY' },
  { id: 'EWAL.OVO', name: 'OVO', badge: 'OVO' },
  { id: 'EWAL.SHOPEE', name: 'ShopeePay', badge: 'SPAY' },
];

const STREAMING_SERVICES = [
  { id: 'AAB.AAAA', name: 'Spotify', badge: 'SPOTIFY', placeholder: 'Email / Username Spotify' },
  { id: 'AAB.AAAS', name: 'Netflix', badge: 'NETFLIX', placeholder: 'Email / Akun Netflix' },
  { id: 'AAB.AAAT', name: 'Disney+ Hotstar', badge: 'DISNEY+', placeholder: 'Nomor HP Akun Disney+' },
  { id: 'AAB.AAAE', name: 'Vidio', badge: 'VIDIO', placeholder: 'Nomor HP / Email Vidio' },
  { id: 'AAB.AAAW', name: 'Prime Video', badge: 'PRIME', placeholder: 'Email Akun Amazon Prime' },
  { id: 'AAB.AAAU', name: 'HBO GO', badge: 'HBO', placeholder: 'Email Akun HBO GO' },
  { id: 'AAB.AABA', name: 'Viu', badge: 'VIU', placeholder: 'Email / Akun Viu' },
  { id: 'AAB.AAAR', name: 'WeTV', badge: 'WETV', placeholder: 'Nomor HP / Akun WeTV' },
  { id: 'AAB.AAAP', name: 'Bstation', badge: 'BSTATION', placeholder: 'UID / Akun Bstation' },
  { id: 'AAB.AAAG', name: 'Vision+', badge: 'VISION+', placeholder: 'Nomor HP / Email Vision+' },
  { id: 'AAB.AAAC', name: 'Catchplay+', badge: 'CATCHPLAY', placeholder: 'Email Akun Catchplay' },
  { id: 'AAB.AAAB', name: 'beIN Sports', badge: 'BEIN', placeholder: 'Email beIN Sports Connect' },
  { id: 'AAB.AAAD', name: 'iTunes Gift Card', badge: 'ITUNES', placeholder: 'Apple ID / No HP' },
  { id: 'AAB.AAAQ', name: 'Tinder', badge: 'TINDER', placeholder: 'Nomor HP Akun Tinder' },
  { id: 'AAB.AAAJ', name: 'Bigo Live', badge: 'BIGO', placeholder: 'Bigo ID Pengguna' },
  { id: 'AAB.AAAY', name: 'JOOX Music', badge: 'JOOX', placeholder: 'Nomor HP / JOOX ID' },
  { id: 'AAB.AAAZ', name: 'iFlix', badge: 'IFLIX', placeholder: 'Email Akun iFlix' },
  { id: 'AAB.AAAX', name: 'Mola TV', badge: 'MOLA', placeholder: 'Email Akun Mola' },
  { id: 'AAB.AAAH', name: 'Genflix', badge: 'GENFLIX', placeholder: 'Email / Nomor HP Genflix' },
  { id: 'AAB.AAAI', name: 'Sushiroll', badge: 'SUSHIROLL', placeholder: 'Email Akun Sushiroll' },
  { id: 'AAB.AAAN', name: 'Lita', badge: 'LITA', placeholder: 'Lita User ID' },
  { id: 'AAB.AAAO', name: 'Litmatch', badge: 'LITMATCH', placeholder: 'Litmatch User ID' },
  { id: 'AAB.AAAK', name: 'Yalla Live', badge: 'YALLA', placeholder: 'Yalla Live ID' },
  { id: 'AAB.AAAL', name: 'Mico Live', badge: 'MICO', placeholder: 'Mico User ID' },
  { id: 'AAB.AAAM', name: 'LivU', badge: 'LIVU', placeholder: 'LivU User ID' },
  { id: 'AAB.AAAV', name: 'LinkPlus', badge: 'LINKPLUS', placeholder: 'Nomor HP / Akun LinkPlus' },
];

const PPOB_TABS = [
  { id: 'pulsa', label: 'Pulsa & Data', iconKey: 'Pulsa', desc: 'Semua Operator' },
  { id: 'pln', label: 'Token PLN', iconKey: 'Pln', desc: 'Prabayar & Pasca' },
  { id: 'game', label: 'Voucher Game', iconKey: 'Game', desc: 'MLBB, FF, Genshin' },
  { id: 'ewallet', label: 'E-Wallet', iconKey: 'Ewallet', desc: 'DANA, GoPay, OVO' },
  { id: 'streaming', label: 'Aplikasi Streaming', iconKey: 'Streaming', desc: 'Spotify, Netflix, Vidio' },
  { id: 'tagihan', label: 'Tagihan & PDAM', iconKey: 'Tagihan', desc: 'Cek & Bayar Rutin' },
];

const fmt = {
  rp: (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0),
};

export default function Ppob({ store, initialPulsa }) {
  const { auth } = usePage().props;
  const user = auth?.user;
  const userBalance = user?.balance ?? 0;

  // Baca parameter ?tab= dari URL saat inisialisasi
  const [activeTab, setActiveTab] = useState(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const tabParam = params.get('tab');
      if (['pulsa', 'pln', 'game', 'ewallet', 'streaming', 'tagihan'].includes(tabParam)) {
        return tabParam;
      }
    }
    return 'pulsa';
  });

  const handleSwitchTab = (tabId) => {
    setActiveTab(tabId);
    setSelectedProduct(null);
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
      if (tabParam && ['pulsa', 'pln', 'game', 'ewallet', 'streaming', 'tagihan'].includes(tabParam)) {
        setActiveTab(tabParam);
      }
    };
    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  const [phoneNumber, setPhoneNumber] = useState('');
  const [selectedOperator, setSelectedOperator] = useState(OPERATORS[0]);
  const [pulsaType, setPulsaType] = useState('pulsa'); // 'pulsa' | 'data'

  // PLN state
  const [plnType, setPlnType] = useState('prepaid'); // 'prepaid' (token) | 'postpaid' (tagihan)
  const [plnCustomerId, setPlnCustomerId] = useState('');
  const [plnInquiryResult, setPlnInquiryResult] = useState(null);
  const [isPlnInquiring, setIsPlnInquiring] = useState(false);

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

  // Fetch products when category / operator / game / ewallet / streaming changes
  useEffect(() => {
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

  // Inquiry PLN Pascabayar
  const handlePlnInquiry = async () => {
    if (!plnCustomerId || plnCustomerId.length < 8) {
      Swal.fire({
        icon: 'warning',
        title: 'Periksa Nomor',
        text: 'Masukkan ID Pelanggan PLN / Nomor Meter yang valid.',
        confirmButtonColor: '#10b981',
      });
      return;
    }

    setIsPlnInquiring(true);
    setPlnInquiryResult(null);
    try {
      const res = await fetch('/ppob/inquiry', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          product_id: '100301', // PLN Postpaid
          customer_id: plnCustomerId,
        }),
      });
      const data = await res.json();
      if (data.status === 'success') {
        setPlnInquiryResult(data);
        setSelectedProduct({
          code: '100301',
          name: 'Tagihan Listrik PLN',
          price: data.total,
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Inquiry Gagal',
          text: data.message || 'ID Pelanggan tidak ditemukan atau sudah lunas.',
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
      setIsPlnInquiring(false);
    }
  };

  // Target Customer ID based on current active tab
  const currentTargetNumber = useMemo(() => {
    if (activeTab === 'pulsa') return phoneNumber;
    if (activeTab === 'pln') return plnCustomerId;
    if (activeTab === 'game') return gameZoneId ? `${gameUserId} (${gameZoneId})` : gameUserId;
    if (activeTab === 'ewallet') return ewalletPhone;
    if (activeTab === 'streaming') return streamingAccount;
    return phoneNumber;
  }, [activeTab, phoneNumber, plnCustomerId, gameUserId, gameZoneId, ewalletPhone, streamingAccount]);

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
          // Tutup drawer terlebih dahulu agar tidak terjadi popup ganda
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
      <header className="sticky top-0 z-40 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200/80 dark:border-white/5">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <Link
              href="/"
              className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-emerald-500 hover:text-white dark:hover:bg-emerald-600 text-xs font-bold text-gray-700 dark:text-gray-300 transition"
            >
              <span>← Kembali ke Toko</span>
            </Link>
            <div className="h-5 w-px bg-gray-200 dark:bg-white/10 hidden sm:block"></div>
            <div className="flex items-center gap-2.5">
              {store?.logo ? (
                <img src={store.logo} alt={store.name} className="w-8 h-8 rounded-lg object-contain bg-emerald-500/10 p-0.5" />
              ) : (
                <div className="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-600 text-white font-black flex items-center justify-center text-xs shadow-sm">
                  NS
                </div>
              )}
              <div>
                <span className="font-black text-sm tracking-tight text-gray-900 dark:text-white flex items-center gap-1.5">
                  {store?.name || 'NandarStore'}
                  <span className="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                    PPOB RESMI
                  </span>
                </span>
              </div>
            </div>
          </div>

          <div className="flex items-center gap-2.5">
            {/* User Info / Balance */}
            {user ? (
              <div className="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <div className="text-left">
                  <p className="text-[10px] font-bold text-gray-400 leading-none">Saldo Akun</p>
                  <p className="text-xs font-black text-emerald-600 dark:text-emerald-400 leading-tight">{fmt.rp(userBalance)}</p>
                </div>
                <Link
                  href="/profile"
                  className="ml-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline"
                >
                  +Topup
                </Link>
              </div>
            ) : (
              <Link
                href="/login"
                className="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-sm transition"
              >
                Login Akun
              </Link>
            )}

            {/* Dark Mode Button */}
            <button
              type="button"
              onClick={toggleDarkMode}
              className="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:text-emerald-500 transition cursor-pointer"
              title="Ganti Tema"
            >
              {isDark ? (
                <svg className="w-4 h-4 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                  <circle cx="12" cy="12" r="5" />
                  <path d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
              ) : (
                <svg className="w-4 h-4 text-gray-600" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
              )}
            </button>
          </div>
        </div>
      </header>

      {/* HERO SECTION */}
      <section className="relative overflow-hidden pt-8 pb-10 sm:pb-12 bg-gradient-to-b from-emerald-500/5 via-transparent to-transparent">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
              <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs font-black uppercase tracking-wider mb-3 shadow-xs">
                <span className="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span>Layanan Transaksi Otomatis Aktif 24 Jam Nonstop</span>
              </div>
              <h1 className="text-2xl sm:text-4xl font-black tracking-tight text-gray-900 dark:text-white">
                Dashboard Pembelian <span className="text-transparent bg-clip-text bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600">PPOB &amp; Pulsa</span>
              </h1>
              <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-medium mt-1.5 max-w-2xl leading-relaxed">
                Isi pulsa reguler, paket data internet, token PLN, e-wallet, top up game dan bayar tagihan dengan proses otomatis instan dalam hitungan detik.
              </p>
            </div>

            <div className="flex flex-wrap items-center gap-2 self-start md:self-auto">
              <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
                <Icons.Lightning className="w-3.5 h-3.5 text-emerald-500" />
                <span>Proses 1-5 Detik</span>
              </span>
              <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
                <Icons.Shield className="w-3.5 h-3.5 text-emerald-500" />
                <span>SN Terbit Otomatis</span>
              </span>
              <span className="px-3 py-1.5 rounded-xl bg-white dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-2 shadow-xs">
                <Icons.CreditCard className="w-3.5 h-3.5 text-emerald-500" />
                <span>QRIS &amp; Saldo</span>
              </span>
            </div>
          </div>

          {/* MAIN CATEGORY NAV TABS (PROFESSIONAL VECTOR ICONS) */}
          <div className="mt-8 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
            {PPOB_TABS.map((tab) => {
              const active = activeTab === tab.id;
              const TabIcon = Icons[tab.iconKey] || Icons.Pulsa;
              return (
                <button
                  key={tab.id}
                  type="button"
                  onClick={() => handleSwitchTab(tab.id)}
                  className={`p-3.5 sm:p-4 rounded-2xl border text-left transition-all duration-200 cursor-pointer flex flex-col justify-between group ${
                    active
                      ? 'bg-emerald-600 text-white border-emerald-500 shadow-md shadow-emerald-600/20 scale-[1.02] dark:bg-emerald-950/50 dark:text-white dark:border-emerald-500/70 dark:shadow-md dark:shadow-emerald-950/40 dark:ring-1 dark:ring-emerald-500/30'
                      : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-emerald-500/40 text-gray-800 dark:text-gray-300 shadow-xs hover:shadow-sm'
                  }`}
                >
                  <div className="flex items-center justify-between mb-3">
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center transition-transform group-hover:scale-105 ${
                      active
                        ? 'bg-white/20 text-white shadow-inner dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/30'
                        : 'bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 group-hover:text-emerald-500 dark:group-hover:text-emerald-400 group-hover:bg-emerald-500/10'
                    }`}>
                      <TabIcon className="w-5 h-5" />
                    </div>
                    {active && (
                      <span className="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-white/20 text-white border border-white/20 dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/40">
                        Aktif
                      </span>
                    )}
                  </div>
                  <div>
                    <h3 className="font-black text-xs sm:text-sm tracking-tight">{tab.label}</h3>
                    <p className={`text-[10px] font-medium mt-0.5 ${active ? 'text-white/80 dark:text-emerald-200/70' : 'text-gray-400'}`}>{tab.desc}</p>
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      </section>

      {/* WORKSPACE AREA */}
      <main className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* KOLOM KIRI: INPUT DATA & OPERATOR SELECTOR (5 COLS) */}
          <div className="lg:col-span-5 space-y-6">
            <div className="bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm space-y-5">
              
              {/* TAB 1: PULSA & DATA */}
              {activeTab === 'pulsa' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Pulsa className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Nomor Tujuan</h3>
                        <p className="text-[11px] text-gray-400 font-medium">Operator terdeteksi otomatis</p>
                      </div>
                    </div>
                    <span className="text-xs font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                      {selectedOperator.name}
                    </span>
                  </div>

                  <div>
                    <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                      Nomor Handphone
                    </label>
                    <div className="relative">
                      <input
                        type="tel"
                        value={phoneNumber}
                        onChange={(e) => setPhoneNumber(e.target.value.replace(/[^0-9]/g, ''))}
                        placeholder="Contoh: 081234567890"
                        className="w-full pl-4 pr-12 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                      />
                      {phoneNumber.length >= 4 && (
                        <div className="absolute right-3.5 top-3.5 text-xs font-bold px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white dark:bg-emerald-500/20 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-xs">
                          {selectedOperator.name.split(' ')[0]}
                        </div>
                      )}
                    </div>
                  </div>

                  {/* Manual Operator Switcher */}
                  <div>
                    <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-2">
                      Pilih Operator Manual
                    </label>
                    <div className="grid grid-cols-3 gap-2">
                      {OPERATORS.map((op) => {
                        const isSelected = selectedOperator.id === op.id;
                        return (
                          <button
                            key={op.id}
                            type="button"
                            onClick={() => setSelectedOperator(op)}
                            className={`p-2.5 rounded-xl border text-center transition cursor-pointer text-xs font-black ${
                              isSelected
                                ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60 dark:shadow-xs'
                                : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                            }`}
                          >
                            {op.name.split(' ')[0]}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                </>
              )}

              {/* TAB 2: TOKEN LISTRIK PLN */}
              {activeTab === 'pln' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Pln className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Layanan Listrik PLN</h3>
                        <p className="text-[11px] text-gray-400 font-medium">Token Prabayar &amp; Tagihan Pasca</p>
                      </div>
                    </div>
                  </div>

                  {/* Switch Token vs Pasca */}
                  <div className="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-gray-100 dark:bg-gray-900 border border-gray-200/60 dark:border-white/5">
                    <button
                      type="button"
                      onClick={() => {
                        setPlnType('prepaid');
                        setPlnInquiryResult(null);
                      }}
                      className={`py-2.5 rounded-xl text-xs font-black transition cursor-pointer ${
                        plnType === 'prepaid'
                          ? 'bg-white dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-sm'
                          : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                      }`}
                    >
                      Token Prabayar
                    </button>
                    <button
                      type="button"
                      onClick={() => {
                        setPlnType('postpaid');
                        setSelectedProduct(null);
                      }}
                      className={`py-2.5 rounded-xl text-xs font-black transition cursor-pointer ${
                        plnType === 'postpaid'
                          ? 'bg-white dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 dark:border dark:border-emerald-500/40 shadow-sm'
                          : 'text-gray-500 hover:text-gray-900 dark:hover:text-white'
                      }`}
                    >
                      Tagihan Pasca
                    </button>
                  </div>

                  <div>
                    <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                      Nomor Meter / ID Pelanggan
                    </label>
                    <input
                      type="text"
                      value={plnCustomerId}
                      onChange={(e) => setPlnCustomerId(e.target.value.replace(/[^0-9]/g, ''))}
                      placeholder="Contoh: 14012345678"
                      className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                    />
                    <p className="text-[10px] text-gray-400 mt-1">Masukkan 11-12 digit nomor meteran atau ID PLN Anda.</p>
                  </div>

                  {/* TOMBOL CEK TAGIHAN ELEGAN & JELAS */}
                  {plnType === 'postpaid' && (
                    <button
                      type="button"
                      onClick={handlePlnInquiry}
                      disabled={isPlnInquiring || !plnCustomerId}
                      className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      {isPlnInquiring ? (
                        <>
                          <svg className="w-5 h-5 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                          <span>Mengecek Tagihan PLN...</span>
                        </>
                      ) : (
                        <>
                          <Icons.Search className="w-5 h-5" />
                          <span>Cek Tagihan Listrik PLN</span>
                        </>
                      )}
                    </button>
                  )}

                  {plnInquiryResult && (
                    <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-xs space-y-2">
                      <div className="flex justify-between">
                        <span className="text-gray-500 font-medium">Nama Pelanggan:</span>
                        <span className="font-black text-gray-900 dark:text-white">{plnInquiryResult.customer_name}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-500 font-medium">Jumlah Tagihan:</span>
                        <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(plnInquiryResult.amount)}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-500 font-medium">Admin Bank:</span>
                        <span className="font-mono font-bold text-gray-900 dark:text-white">{fmt.rp(plnInquiryResult.admin_fee)}</span>
                      </div>
                      <div className="flex justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800/40">
                        <span className="font-black text-emerald-800 dark:text-emerald-300">Total Pembayaran:</span>
                        <span className="font-black font-mono text-base text-emerald-600 dark:text-emerald-400">{fmt.rp(plnInquiryResult.total)}</span>
                      </div>
                    </div>
                  )}
                </>
              )}

              {/* TAB 3: GAME */}
              {activeTab === 'game' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Game className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Pilih Game Favorit</h3>
                        <p className="text-[11px] text-gray-400 font-medium">Top up otomatis masuk ke akun</p>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-2">
                    {GAMES.map((game) => {
                      const isSelected = selectedGame.id === game.id;
                      return (
                        <button
                          key={game.id}
                          type="button"
                          onClick={() => {
                            setSelectedGame(game);
                            setSelectedProduct(null);
                          }}
                          className={`p-3 rounded-xl border text-left transition cursor-pointer flex items-center gap-2.5 ${
                            isSelected
                              ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                              : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                          }`}
                        >
                          <span className={`text-[9px] font-black uppercase px-1.5 py-0.5 rounded-md ${
                            isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                          }`}>
                            {game.badge}
                          </span>
                          <span className="text-xs font-black truncate">{game.name}</span>
                        </button>
                      );
                    })}
                  </div>

                  <div className="space-y-3 pt-2">
                    <div>
                      <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                        User ID Game
                      </label>
                      <input
                        type="text"
                        value={gameUserId}
                        onChange={(e) => setGameUserId(e.target.value)}
                        placeholder={selectedGame.placeholderId || 'Masukkan User ID'}
                        className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                      />
                    </div>

                    {selectedGame.needZone && (
                      <div>
                        <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                          {selectedGame.zoneLabel || 'Zone ID / Server'}
                        </label>
                        {selectedGame.servers ? (
                          <select
                            value={gameZoneId}
                            onChange={(e) => setGameZoneId(e.target.value)}
                            className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                          >
                            <option value="">Pilih Server</option>
                            {selectedGame.servers.map((s) => (
                              <option key={s} value={s}>{s}</option>
                            ))}
                          </select>
                        ) : (
                          <input
                            type="text"
                            value={gameZoneId}
                            onChange={(e) => setGameZoneId(e.target.value)}
                            placeholder={selectedGame.placeholderZone || 'Zone ID'}
                            className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                          />
                        )}
                      </div>
                    )}
                  </div>
                </>
              )}

              {/* TAB 4: E-WALLET */}
              {activeTab === 'ewallet' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Ewallet className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Top Up E-Wallet</h3>
                        <p className="text-[11px] text-gray-400 font-medium">DANA, GoPay, OVO, ShopeePay</p>
                      </div>
                    </div>
                  </div>

                  <div className="grid grid-cols-2 gap-2">
                    {EWALLETS.map((ew) => {
                      const isSelected = selectedEwallet.id === ew.id;
                      return (
                        <button
                          key={ew.id}
                          type="button"
                          onClick={() => {
                            setSelectedEwallet(ew);
                            setSelectedProduct(null);
                          }}
                          className={`p-3.5 rounded-xl border text-center transition cursor-pointer flex items-center justify-center gap-2 ${
                            isSelected
                              ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                              : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                          }`}
                        >
                          <span className={`text-[10px] font-black px-2 py-0.5 rounded-md ${
                            isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                          }`}>
                            {ew.badge}
                          </span>
                          <span className="text-xs font-black">{ew.name}</span>
                        </button>
                      );
                    })}
                  </div>

                  <div>
                    <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                      Nomor HP Terdaftar di {selectedEwallet.name}
                    </label>
                    <input
                      type="tel"
                      value={ewalletPhone}
                      onChange={(e) => setEwalletPhone(e.target.value.replace(/[^0-9]/g, ''))}
                      placeholder="Contoh: 081234567890"
                      className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                    />
                  </div>
                </>
              )}

              {/* TAB 5: APLIKASI STREAMING */}
              {activeTab === 'streaming' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Streaming className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Aplikasi Streaming</h3>
                        <p className="text-[11px] text-gray-400 font-medium">Voucher &amp; Langganan Premium Resmi</p>
                      </div>
                    </div>
                    <span className="text-xs font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                      {selectedStreaming.name}
                    </span>
                  </div>

                  {/* Filter / Search Streaming Service */}
                  <div>
                    <div className="flex items-center justify-between mb-1.5">
                      <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider">
                        Pilih Layanan Streaming ({STREAMING_SERVICES.length})
                      </label>
                      {streamingSearch && (
                        <button
                          type="button"
                          onClick={() => setStreamingSearch('')}
                          className="text-[10px] font-bold text-emerald-600 hover:underline"
                        >
                          Reset
                        </button>
                      )}
                    </div>
                    <input
                      type="text"
                      value={streamingSearch}
                      onChange={(e) => setStreamingSearch(e.target.value)}
                      placeholder="Cari: Spotify, Netflix, Vidio..."
                      className="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition mb-2.5"
                    />

                    {/* Scrollable grid for streaming services */}
                    <div className="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1 custom-scrollbar">
                      {STREAMING_SERVICES.filter((s) =>
                        s.name.toLowerCase().includes(streamingSearch.toLowerCase())
                      ).map((stream) => {
                        const isSelected = selectedStreaming.id === stream.id;
                        return (
                          <button
                            key={stream.id}
                            type="button"
                            onClick={() => {
                              setSelectedStreaming(stream);
                              setSelectedProduct(null);
                            }}
                            className={`p-2.5 rounded-xl border text-left transition cursor-pointer flex items-center gap-2 ${
                              isSelected
                                ? 'bg-emerald-600 text-white border-emerald-500 shadow-sm dark:bg-emerald-500/20 dark:text-emerald-300 dark:border-emerald-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/60 border-gray-200 dark:border-white/5 text-gray-700 dark:text-gray-300 hover:border-emerald-500/40'
                            }`}
                          >
                            <span className={`text-[8px] font-black uppercase px-1.5 py-0.5 rounded ${
                              isSelected ? 'bg-white/20 text-white dark:bg-emerald-500/30 dark:text-emerald-300' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                            }`}>
                              {stream.badge}
                            </span>
                            <span className="text-xs font-black truncate">{stream.name}</span>
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Input Akun / Email / No HP Tujuan */}
                  <div>
                    <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                      {selectedStreaming.placeholder || 'Email / Nomor HP / Akun Tujuan'}
                    </label>
                    <input
                      type="text"
                      value={streamingAccount}
                      onChange={(e) => setStreamingAccount(e.target.value)}
                      placeholder={selectedStreaming.placeholder || 'Masukkan akun tujuan'}
                      className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                    />
                    <p className="text-[10px] text-gray-400 mt-1">
                      Kode voucher atau konfirmasi aktivasi {selectedStreaming.name} dikirimkan instan via sistem &amp; WhatsApp.
                    </p>
                  </div>
                </>
              )}

              {/* TAB 6: TAGIHAN PDAM & BPJS */}
              {activeTab === 'tagihan' && (
                <>
                  <div className="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div className="flex items-center gap-2.5">
                      <div className="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <Icons.Tagihan className="w-5 h-5" />
                      </div>
                      <div>
                        <h3 className="font-black text-sm text-gray-900 dark:text-white">Tagihan Rutin &amp; PDAM</h3>
                        <p className="text-[11px] text-gray-400 font-medium">Air PDAM, BPJS Kesehatan</p>
                      </div>
                    </div>
                  </div>

                  <div className="space-y-3">
                    <div>
                      <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                        Jenis Layanan
                      </label>
                      <select className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition">
                        <option value="pdam">PDAM Air Bersih (Kota / Kabupaten)</option>
                        <option value="bpjs">BPJS Kesehatan</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1.5">
                        Nomor Pelanggan
                      </label>
                      <input
                        type="text"
                        placeholder="Contoh: 123456789"
                        className="w-full px-4 py-3.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-inner"
                      />
                    </div>

                    <button
                      type="button"
                      onClick={() => Swal.fire({
                        icon: 'info',
                        title: 'Inquiry PDAM',
                        text: 'Silakan masukkan nomor pelanggan PDAM Anda.',
                        confirmButtonColor: '#10b981',
                      })}
                      className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/30 border border-emerald-500/40 hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer"
                    >
                      <Icons.Search className="w-4 h-4" />
                      <span>Cek Tagihan Air PDAM</span>
                    </button>
                  </div>
                </>
              )}

            </div>
          </div>

          {/* KOLOM KANAN: DENOMINASI / PRODUK GRID (7 COLS) */}
          <div className="lg:col-span-7 space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-base font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                  <span>Pilih Nominal &amp; Produk</span>
                  <span className="text-xs font-black px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40">
                    {products.length} Pilihan
                  </span>
                </h3>
                <p className="text-xs text-gray-400 mt-0.5">Pilih produk yang ingin Anda beli, lalu lanjutkan ke pembayaran.</p>
              </div>
            </div>

            {/* PRODUCT CARDS GRID */}
            {isLoadingProducts ? (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 py-16 text-center">
                <div className="col-span-2 flex flex-col items-center justify-center space-y-3">
                  <div className="w-10 h-10 border-4 border-emerald-500 border-t-transparent rounded-full animate-spin"></div>
                  <p className="text-xs font-bold text-gray-400">Memuat katalog produk resmi...</p>
                </div>
              </div>
            ) : products.length === 0 ? (
              <div className="p-10 text-center rounded-3xl bg-white dark:bg-[#131b2e] border border-gray-200/80 dark:border-white/5 space-y-2">
                <div className="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto text-gray-400">
                  <Icons.Search className="w-6 h-6" />
                </div>
                <h4 className="font-black text-sm text-gray-800 dark:text-gray-200">Katalog Sedang Dimuat</h4>
                <p className="text-xs text-gray-400 max-w-sm mx-auto">Tidak ada produk yang tersedia untuk pilihan ini saat ini. Silakan pilih nominal atau operator lain.</p>
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                {products.map((item) => {
                  const isSelected = selectedProduct?.code === item.code;
                  const isMaintenance = item.status === 'maintenance';
                  const isInactive = item.status === 'inactive';

                  return (
                    <div
                      key={item.code}
                      onClick={() => handleOpenCheckout(item)}
                      className={`p-4 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col justify-between group relative overflow-hidden ${
                        isMaintenance
                          ? 'bg-amber-500/5 border-amber-500/30 opacity-75'
                          : isInactive
                          ? 'bg-gray-100 dark:bg-white/5 opacity-50 cursor-not-allowed'
                          : isSelected
                          ? 'bg-emerald-50/70 dark:bg-emerald-950/20 border-emerald-500 shadow-md ring-2 ring-emerald-500/20'
                          : 'bg-white dark:bg-[#131b2e] border-gray-200/80 dark:border-white/5 hover:border-emerald-500/50 hover:shadow-md'
                      }`}
                    >
                      <div>
                        <div className="flex items-start justify-between gap-2 mb-2">
                          <h4 className="font-black text-xs sm:text-sm text-gray-900 dark:text-white group-hover:text-emerald-500 transition">
                            {item.name}
                          </h4>
                          {isMaintenance ? (
                            <span className="text-[10px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40 shrink-0">
                              GANGGUAN
                            </span>
                          ) : isInactive ? (
                            <span className="text-[10px] font-black uppercase px-1.5 py-0.5 rounded bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 shrink-0">
                              NONAKTIF
                            </span>
                          ) : (
                            <span className="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shrink-0">
                              INSTAN
                            </span>
                          )}
                        </div>
                      </div>

                      <div className="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-white/5 mt-2 gap-2">
                        <div>
                          <p className="text-[10px] font-black uppercase text-gray-400">Harga Layanan</p>
                          <span className="text-sm sm:text-base font-black font-mono text-emerald-600 dark:text-emerald-400">
                            {fmt.rp(item.price)}
                          </span>
                        </div>
                        <button
                          type="button"
                          className={`px-4 py-2.5 rounded-xl text-xs font-black shadow-md transition-all flex items-center gap-1.5 shrink-0 ${
                            isMaintenance
                              ? 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30 cursor-not-allowed'
                              : isInactive
                              ? 'bg-gray-100 dark:bg-white/5 text-gray-400 cursor-not-allowed'
                              : 'bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/90 dark:hover:bg-emerald-500 dark:text-white border border-emerald-500/40 shadow-sm shadow-emerald-950/30 group-hover:scale-105 active:scale-95 cursor-pointer'
                          }`}
                        >
                          {isMaintenance ? (
                            <span>Gangguan</span>
                          ) : isInactive ? (
                            <span>Tutup</span>
                          ) : (
                            <>
                              <span>Beli Sekarang</span>
                              <svg className="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </>
                          )}
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}

          </div>
        </div>
      </main>

      {/* FOOTER */}
      <footer className="mt-16 border-t border-gray-200/80 dark:border-white/5 py-8 text-center text-xs font-semibold text-gray-500 dark:text-gray-400">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
          <p>&copy; {new Date().getFullYear()} {store?.name || 'NandarStore'}. Hak Cipta Dilindungi.</p>
          <div className="flex items-center gap-4">
            <Link href="/" className="hover:text-emerald-500 transition">Beranda Toko</Link>
            <span>•</span>
            <Link href="/faq" className="hover:text-emerald-500 transition">Pusat Bantuan & FAQ</Link>
            <span>•</span>
            <Link href="/syarat-ketentuan" className="hover:text-emerald-500 transition">Syarat & Ketentuan</Link>
          </div>
        </div>
      </footer>

      {/* CHECKOUT MODAL DRAWER */}
      {checkoutModal && selectedProduct && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md animate-fade-in">
          <div className="bg-white dark:bg-[#0f172a] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-6 sm:p-7 shadow-2xl relative overflow-hidden space-y-5">
            
            {/* Header Modal */}
            <div className="flex items-center justify-between pb-3.5 border-b border-gray-100 dark:border-white/5">
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-md shadow-emerald-500/20">
                  <Icons.Lightning className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-black text-base text-gray-900 dark:text-white leading-tight">Konfirmasi Pembelian</h3>
                  <p className="text-[11px] font-semibold text-gray-400 mt-0.5">Periksa tujuan dan metode bayar Anda</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setCheckoutModal(false)}
                disabled={isSubmitting}
                className="w-8 h-8 rounded-full bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center justify-center font-black text-sm transition cursor-pointer"
              >
                ✕
              </button>
            </div>

            {/* RINGKASAN PRODUK */}
            <div className="p-4 rounded-2xl bg-gray-50 dark:bg-white/[0.03] border border-gray-200/80 dark:border-white/5 space-y-2.5 text-xs">
              <div className="flex justify-between items-start gap-2">
                <span className="text-gray-400 shrink-0 font-medium">Layanan / Produk:</span>
                <span className="font-black text-gray-900 dark:text-white text-right leading-snug">{selectedProduct.name}</span>
              </div>
              <div className="flex justify-between items-center gap-2">
                <span className="text-gray-400 font-medium">Nomor / ID Tujuan:</span>
                <span className="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 font-mono font-black text-emerald-600 dark:text-emerald-400 text-xs tracking-wider">
                  {currentTargetNumber}
                </span>
              </div>
              <div className="flex justify-between items-center pt-2.5 border-t border-gray-200 dark:border-white/5">
                <span className="font-black text-gray-900 dark:text-white text-xs uppercase tracking-wider">Total Tagihan:</span>
                <span className="font-black font-mono text-lg text-emerald-600 dark:text-emerald-400">
                  {fmt.rp(selectedProduct.price)}
                </span>
              </div>
            </div>

            {/* METODE PEMBAYARAN */}
            <div className="space-y-2.5">
              <div className="flex items-center justify-between">
                <label className="block text-xs font-black text-gray-500 uppercase tracking-wider">
                  Metode Pembayaran:
                </label>
                <span className="text-[10px] font-bold text-gray-400">Pilih salah satu</span>
              </div>

              {/* Opsi 1: Saldo Akun */}
              <label
                onClick={() => setPaymentMethod('balance')}
                className={`p-3.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all ${
                  paymentMethod === 'balance'
                    ? 'bg-emerald-500/10 border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm'
                    : 'bg-gray-50 dark:bg-white/[0.02] border-gray-200 dark:border-white/5 hover:border-emerald-500/40'
                }`}
              >
                <div className="flex items-center gap-3">
                  <input
                    type="radio"
                    name="pay_method"
                    value="balance"
                    checked={paymentMethod === 'balance'}
                    onChange={() => setPaymentMethod('balance')}
                    className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300"
                  />
                  <div>
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <p className="font-black text-xs text-gray-900 dark:text-white">Saldo Akun NandarStore</p>
                      <span className="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                        Instan 1 Detik
                      </span>
                    </div>
                    <p className="text-[11px] text-gray-400 mt-0.5">
                      Saldo Anda: <strong className="text-emerald-600 dark:text-emerald-400 font-mono font-bold">{fmt.rp(userBalance)}</strong>
                      {userBalance >= selectedProduct.price && (
                        <span className="text-[10px] text-gray-400 font-normal ml-1">
                          (Sisa: {fmt.rp(userBalance - selectedProduct.price)})
                        </span>
                      )}
                    </p>
                  </div>
                </div>
                <div className="text-right shrink-0">
                  {userBalance >= selectedProduct.price ? (
                    <span className="text-[11px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded-lg">
                      Bebas Biaya
                    </span>
                  ) : (
                    <a
                      href="/profile"
                      target="_blank"
                      className="text-[10px] font-black text-rose-500 bg-rose-500/10 px-2 py-1 rounded-lg hover:underline"
                    >
                      + Top Up Saldo
                    </a>
                  )}
                </div>
              </label>

              {/* Opsi 2: QRIS Otomatis */}
              <label
                onClick={() => setPaymentMethod('qris')}
                className={`p-3.5 rounded-2xl border flex items-center justify-between cursor-pointer transition-all ${
                  paymentMethod === 'qris'
                    ? 'bg-emerald-500/10 border-emerald-500 ring-2 ring-emerald-500/20 shadow-sm'
                    : 'bg-gray-50 dark:bg-white/[0.02] border-gray-200 dark:border-white/5 hover:border-emerald-500/40'
                }`}
              >
                <div className="flex items-center gap-3">
                  <input
                    type="radio"
                    name="pay_method"
                    value="qris"
                    checked={paymentMethod === 'qris'}
                    onChange={() => setPaymentMethod('qris')}
                    className="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300"
                  />
                  <div>
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <p className="font-black text-xs text-gray-900 dark:text-white">QRIS Instan (Semua Bank &amp; E-Wallet)</p>
                      <span className="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                        Otomatis
                      </span>
                    </div>
                    <p className="text-[11px] text-gray-400 mt-0.5">BCA, Mandiri, BRI, BNI, DANA, GoPay, OVO, ShopeePay</p>
                  </div>
                </div>
                <div className="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <Icons.CreditCard className="w-4 h-4" />
                </div>
              </label>
            </div>

            {/* TOMBOL BAYAR SEKARANG PROFESIONAL (HIJAU KONSISTEN) */}
            <div className="pt-2 space-y-2.5">
              <button
                type="button"
                onClick={handleConfirmPayment}
                disabled={isSubmitting || (paymentMethod === 'balance' && userBalance < selectedProduct.price)}
                className="w-full py-4 px-5 rounded-2xl font-black text-sm sm:text-base bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 border border-emerald-500/40 shadow-lg shadow-emerald-950/40 hover:scale-[1.01] active:scale-[0.99] transition-all duration-200 flex items-center justify-between cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div className="flex items-center gap-3">
                  {isSubmitting ? (
                    <svg className="w-5 h-5 animate-spin text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                  ) : (
                    <div className="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-sm shadow-inner">
                      <Icons.Lightning className="w-4 h-4" />
                    </div>
                  )}
                  <div className="text-left">
                    <p className="leading-tight text-xs sm:text-sm font-black tracking-tight">
                      {isSubmitting
                        ? 'Memproses Transaksi...'
                        : paymentMethod === 'balance'
                        ? 'Bayar Sekarang via Saldo'
                        : 'Lanjut Pembayaran via QRIS'}
                    </p>
                    <p className="text-[10px] font-medium opacity-85 leading-tight mt-0.5">
                      {paymentMethod === 'balance'
                        ? 'Proses instan 1 detik langsung masuk otomatis'
                        : 'Generate QRIS otomatis & konfirmasi realtime'}
                    </p>
                  </div>
                </div>

                <div className="px-3 py-1.5 rounded-xl bg-black/20 backdrop-blur-sm font-mono font-black text-xs sm:text-sm shrink-0">
                  {fmt.rp(selectedProduct.price)}
                </div>
              </button>

              <button
                type="button"
                onClick={() => setCheckoutModal(false)}
                disabled={isSubmitting}
                className="w-full py-2.5 rounded-xl text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition text-center cursor-pointer"
              >
                Batal / Pilih Produk Lain
              </button>

              <div className="flex items-center justify-center gap-1.5 text-[11px] font-semibold text-gray-400 pt-1">
                <Icons.Shield className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                <span>Transaksi Terlindungi SSL 256-Bit • Sistem Otomatis Terverifikasi</span>
              </div>
            </div>

          </div>
        </div>
      )}

      {/* QRIS POPUP DISPLAY */}
      {qrisData && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
          <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-sm p-6 text-center shadow-2xl space-y-4">
            <div>
              <span className="text-[10px] font-black uppercase px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/40">
                Menunggu Pembayaran QRIS
              </span>
              <h3 className="text-base font-black text-gray-900 dark:text-white mt-2">Scan QRIS untuk Membayar</h3>
              <p className="text-xs text-gray-400">Selesaikan sebelum waktu berakhir</p>
            </div>

            {qrisData.url_qris && (
              <div className="p-3 bg-white rounded-2xl border inline-block shadow-inner">
                <img src={qrisData.url_qris} alt="QRIS" className="w-52 h-52 mx-auto object-contain" />
              </div>
            )}

            <div>
              <p className="text-[10px] text-gray-400 uppercase font-bold">Total Pembayaran:</p>
              <div className="flex items-center justify-center gap-2">
                <span className="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400">{fmt.rp(qrisData.total_nominal)}</span>
                <button
                  type="button"
                  onClick={() => copyToClipboard(qrisData.total_nominal)}
                  className="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-white/10 text-xs font-bold hover:bg-emerald-500 hover:text-white transition"
                >
                  {isCopied ? 'Tersalin' : 'Salin'}
                </button>
              </div>
              <p className="text-[11px] font-bold text-amber-500 mt-1 flex items-center justify-center gap-1">
                <Icons.Clock className="w-3.5 h-3.5" />
                <span>Waktu tersisa: {Math.floor(qrisTimer / 60)}:{(qrisTimer % 60).toString().padStart(2, '0')}</span>
              </p>
            </div>

            <button
              type="button"
              onClick={() => setQrisData(null)}
              className="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-200 transition"
            >
              Batalkan
            </button>
          </div>
        </div>
      )}

      {/* SUCCESS MODAL POPUP */}
      {successOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
          <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-6 text-center shadow-2xl space-y-5">
            <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-green-500 text-white flex items-center justify-center font-black text-2xl mx-auto shadow-lg shadow-emerald-500/30">
              <Icons.Check className="w-8 h-8" />
            </div>

            <div>
              <h3 className="text-xl font-black text-gray-900 dark:text-white">Pembayaran Berhasil!</h3>
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Layanan PPOB Anda telah berhasil diproses secara otomatis.</p>
            </div>

            {successOrder.invoice_number && (
              <div className="py-2 px-3 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-200 dark:border-white/5 inline-flex items-center gap-2 text-xs">
                <span className="text-gray-400 font-medium">No. Invoice:</span>
                <span className="font-mono font-bold text-gray-800 dark:text-gray-200">{successOrder.invoice_number}</span>
              </div>
            )}

            {successOrder.sn_or_account && (
              <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-left space-y-1.5">
                <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                  Nomor Seri (SN) / Kode Token Pengisian:
                </p>
                <div className="flex items-center justify-between gap-2">
                  <span className="font-mono font-black text-sm text-gray-900 dark:text-white break-all">
                    {successOrder.sn_or_account}
                  </span>
                  <button
                    type="button"
                    onClick={() => copyToClipboard(successOrder.sn_or_account)}
                    className="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shrink-0 cursor-pointer transition"
                  >
                    {isCopied ? 'Tersalin' : 'Salin'}
                  </button>
                </div>
              </div>
            )}

            <div className="space-y-2.5 pt-1">
              <a
                href="/profile?tab=orders"
                className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 font-black text-xs sm:text-sm shadow-md shadow-emerald-950/40 border border-emerald-500/30 flex items-center justify-center gap-2 transition hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
              >
                <span>Lihat Riwayat Transaksi &amp; Cetak Struk</span>
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
              </a>

              <div className="flex gap-2">
                <a
                  href={successOrder.redirect_url || `/order/invoice/${successOrder.invoice_number}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex-1 py-2.5 px-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-800 dark:text-gray-200 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                  <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                  <span>Lihat Invoice Resmi</span>
                </a>
                <button
                  type="button"
                  onClick={() => setSuccessOrder(null)}
                  className="py-2.5 px-4 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-500 dark:text-gray-400 font-bold text-xs transition cursor-pointer"
                >
                  Tutup
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

    </div>
  );
}
