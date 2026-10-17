import React, { useState, useRef, useEffect, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';

// Subcomponents & Helpers
import { Icon } from './Partials/ProfileIcons';
import ProfileNavbar from './Partials/ProfileNavbar';
import ProfileSidebar from './Partials/ProfileSidebar';
import DashboardTab from './Partials/DashboardTab';
import OrdersTab from './Partials/OrdersTab';
import MutationsTab from './Partials/MutationsTab';
import EditProfileTab from './Partials/EditProfileTab';
import SecurityTab from './Partials/SecurityTab';
import TopupModal from './Partials/TopupModal';
import UpgradeResellerModal from './Partials/UpgradeResellerModal';
import ChangeWaModal from './Partials/ChangeWaModal';
import WaVerificationModal from './Partials/WaVerificationModal';

export default function Profile({ orders = [], topups = [], mutations = [], stats = null }) {
  const { auth, flash, url } = usePage().props;
  const user = auth?.user;
  const isReseller = user?.role === 'reseller';

  const allMutations = mutations && mutations.length > 0 ? mutations : topups;

  // Pisahkan pesanan Aplikasi Premium vs PPOB 24 Jam
  const appOrders = useMemo(() => orders.filter((o) => o.product_type !== 'ppob'), [orders]);
  const ppobOrders = useMemo(() => orders.filter((o) => o.product_type === 'ppob'), [orders]);

  // Akumulasi Total Pemasukan & Pengeluaran Sejak Pertama Kali Daftar
  const totalIncomeCalc = useMemo(() => {
    if (stats?.total_income !== undefined && stats?.total_income !== null) return Number(stats.total_income);
    return allMutations
      .filter((m) => m.type === 'income' && m.status === 'success')
      .reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
  }, [allMutations, stats]);

  const totalExpenseCalc = useMemo(() => {
    if (stats?.total_expense !== undefined && stats?.total_expense !== null) return Number(stats.total_expense);
    return allMutations
      .filter((m) => m.type === 'expense' && m.status === 'success')
      .reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
  }, [allMutations, stats]);

  const totalIncomeCount =
    stats?.income_count ?? allMutations.filter((m) => m.type === 'income' && m.status === 'success').length;
  const totalExpenseCount =
    stats?.expense_count ?? allMutations.filter((m) => m.type === 'expense' && m.status === 'success').length;

  const [activeMenu, setActiveMenu] = useState(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if (tabParam) {
        if (tabParam === 'ppob' || tabParam === 'orders_ppob') return 'orders_ppob';
        if (tabParam === 'orders' || tabParam === 'orders_app' || tabParam === 'history') return 'orders_app';
        if (tabParam === 'orders_all') return 'orders_all';
        if (tabParam === 'settings') return 'profile';
        if (['dashboard', 'topup', 'security', 'profile'].includes(tabParam)) return tabParam;
      }
    }
    return 'dashboard';
  });

  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [showTopup, setShowTopup] = useState(false);
  const [showUpgradeModal, setShowUpgradeModal] = useState(false);
  const [showWaModal, setShowWaModal] = useState(false);
  const [showChangeWaModal, setShowChangeWaModal] = useState(false);
  const [waModalPhone, setWaModalPhone] = useState(user?.whatsapp || '');
  const [waModalInitialData, setWaModalInitialData] = useState(null);
  const [waSendingId, setWaSendingId] = useState(null);
  const [orderSearchQuery, setOrderSearchQuery] = useState('');
  const [isDark, setIsDark] = useState(() => typeof window !== 'undefined' && document.documentElement.classList.contains('dark'));

  // Pagination & Toast States
  const [orderPage, setOrderPage] = useState(1);
  const [copiedSnId, setCopiedSnId] = useState(null);
  const [toast, setToast] = useState(null);
  const [mutationPage, setMutationPage] = useState(1);
  const [mutationFilter, setMutationFilter] = useState('all'); // all | income | expense

  const showToast = (type, text) => {
    setToast({ type, text });
    setTimeout(() => setToast(null), 3500);
  };

  useEffect(() => {
    setOrderPage(1);
  }, [orderSearchQuery, activeMenu]);

  useEffect(() => {
    setMutationPage(1);
  }, [mutationFilter]);

  // Tentukan daftar order aktif berdasarkan menu tab
  const currentOrderCategory = useMemo(() => {
    if (activeMenu === 'orders_ppob') return 'ppob';
    if (activeMenu === 'orders_all') return 'all';
    return 'app';
  }, [activeMenu]);

  const currentOrderList = useMemo(() => {
    if (currentOrderCategory === 'ppob') return ppobOrders;
    if (currentOrderCategory === 'all') return orders;
    return appOrders;
  }, [currentOrderCategory, ppobOrders, orders, appOrders]);

  const filteredOrders = useMemo(() => {
    return currentOrderList.filter((o) => {
      if (!orderSearchQuery.trim()) return true;
      const q = orderSearchQuery.toLowerCase().trim();
      const inv = String(o.invoice_number || o.id || '').toLowerCase();
      const prod = String(o.product_name || '').toLowerCase();
      const wa = String(o.whatsapp || '').toLowerCase();
      const email = String(o.email || '').toLowerCase();
      const sn = String(o.sn_or_account || '').toLowerCase();
      const notes = String(o.notes || '').toLowerCase();
      const status = String(o.order_status || o.payment_status || '').toLowerCase();
      const total = String(o.total_amount || o.price || '').toLowerCase();

      return (
        inv.includes(q) ||
        prod.includes(q) ||
        wa.includes(q) ||
        email.includes(q) ||
        sn.includes(q) ||
        notes.includes(q) ||
        status.includes(q) ||
        total.includes(q)
      );
    });
  }, [currentOrderList, orderSearchQuery]);

  const orderTotalPages = Math.ceil(filteredOrders.length / 10);
  const paginatedOrders = filteredOrders.slice((orderPage - 1) * 10, orderPage * 10);

  const filteredMutations = allMutations.filter((m) => {
    if (mutationFilter === 'income') return m.type === 'income';
    if (mutationFilter === 'expense') return m.type === 'expense';
    return true;
  });

  const mutationTotalPages = Math.ceil(filteredMutations.length / 10);
  const paginatedMutations = filteredMutations.slice((mutationPage - 1) * 10, mutationPage * 10);

  const handleSendOrderWa = async (orderId) => {
    if (!user?.is_whatsapp_verified) {
      showToast('error', 'Nomor WhatsApp Anda belum terverifikasi. Silakan klik tombol Verifikasi di tab Edit Profil.');
      return;
    }
    setWaSendingId(orderId);
    try {
      const res = await fetch(`/order/${orderId}/resend-wa`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        showToast('success', `Detail akun & invoice berhasil dikirim ke WhatsApp (${data.target || user.whatsapp})`);
      } else {
        showToast('error', data.message || 'Gagal mengirim pesan WhatsApp.');
      }
    } catch {
      showToast('error', 'Terjadi kesalahan jaringan.');
    }
    setWaSendingId(null);
  };

  const handleNumberChanged = (data) => {
    setShowChangeWaModal(false);
    setWaModalPhone(data.whatsapp);
    setWaModalInitialData(data);
    setShowWaModal(true);
  };

  useEffect(() => {
    if (typeof window !== 'undefined') {
      const params = new URLSearchParams(window.location.search);
      const tab = params.get('tab');
      if (tab) {
        if (tab === 'history' || tab === 'orders' || tab === 'orders_app') setActiveMenu('orders_app');
        else if (tab === 'ppob' || tab === 'orders_ppob') setActiveMenu('orders_ppob');
        else if (tab === 'orders_all') setActiveMenu('orders_all');
        else if (tab === 'settings') setActiveMenu('profile');
        else if (['dashboard', 'profile', 'topup', 'security'].includes(tab)) {
          setActiveMenu(tab);
        }
      } else {
        setActiveMenu('dashboard');
      }
    }
  }, [url]);

  useEffect(() => {
    const theme = localStorage.getItem('theme');
    if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
      setIsDark(true);
    }
  }, []);

  const toggleDark = () => {
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

  const menus = [
    { key: 'dashboard',   label: 'Dashboard',             icon: Icon.Dashboard },
    { key: 'orders_app',  label: 'Pesanan Aplikasi',      icon: Icon.App,     count: appOrders.length },
    { key: 'orders_ppob', label: 'Pesanan PPOB',          icon: Icon.Ppob,    count: ppobOrders.length },
    { key: 'topup',       label: 'Riwayat Saldo & Top Up', icon: Icon.Wallet,  count: allMutations.length },
    { key: 'profile',     label: 'Edit Profil',           icon: Icon.User },
    { key: 'security',    label: 'Ubah Password',         icon: Icon.Lock },
  ];

  const handleMenuClick = (key) => {
    let normalizedKey = key;
    if (key === 'orders') normalizedKey = 'orders_app';
    else if (key === 'ppob') normalizedKey = 'orders_ppob';
    setActiveMenu(normalizedKey);
    setSidebarOpen(false);
    setOrderPage(1);
    setOrderSearchQuery('');
    let targetParam = normalizedKey;
    if (normalizedKey === 'orders_app') targetParam = 'orders';
    else if (normalizedKey === 'orders_ppob') targetParam = 'ppob';

    const targetUrl = normalizedKey === 'dashboard' ? '/profile' : `/profile?tab=${targetParam}`;
    router.visit(targetUrl, { preserveState: true, preserveScroll: true, replace: true });
  };

  const isOrdersTabActive =
    activeMenu === 'orders_app' ||
    activeMenu === 'orders_ppob' ||
    activeMenu === 'orders_all' ||
    activeMenu === 'orders';

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0f172a] font-jakarta transition-colors">
      <Head>
        <title>{isReseller ? 'Profil VIP Reseller - NandarStore' : 'Profil Saya - NandarStore'}</title>
        <link
          href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet"
        />
        <style>{`.font-jakarta{font-family:'Plus Jakarta Sans',sans-serif}.glass-nav{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px)}.dark .glass-nav{background:rgba(15,23,42,0.9)}`}</style>
      </Head>

      {/* NAVBAR */}
      <ProfileNavbar setSidebarOpen={setSidebarOpen} toggleDark={toggleDark} isDark={isDark} />

      {/* MOBILE SIDEBAR OVERLAY */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-50 flex md:hidden">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setSidebarOpen(false)} />
          <div className="relative w-72 bg-white dark:bg-gray-900 h-full shadow-2xl overflow-y-auto">
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <span className="font-black text-gray-900 dark:text-white">Menu</span>
              <button
                onClick={() => setSidebarOpen(false)}
                className="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white cursor-pointer"
                aria-label="Tutup Sidebar"
              >
                <Icon.X className="w-5 h-5" />
              </button>
            </div>
            <ProfileSidebar
              user={user}
              isReseller={isReseller}
              orders={orders}
              allMutations={allMutations}
              totalIncomeCalc={totalIncomeCalc}
              totalExpenseCalc={totalExpenseCalc}
              menus={menus}
              activeMenu={activeMenu}
              handleMenuClick={handleMenuClick}
              setShowTopup={setShowTopup}
              setShowUpgradeModal={setShowUpgradeModal}
              setShowWaModal={setShowWaModal}
              setWaModalPhone={setWaModalPhone}
              setShowChangeWaModal={setShowChangeWaModal}
            />
          </div>
        </div>
      )}

      {/* MAIN LAYOUT */}
      <div className="max-w-6xl mx-auto px-4 py-6">
        <div className="flex gap-6 items-start">
          {/* DESKTOP SIDEBAR */}
          <aside className="hidden md:flex flex-col w-72 shrink-0 h-fit self-start">
            <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm overflow-hidden h-fit">
              <ProfileSidebar
                user={user}
                isReseller={isReseller}
                orders={orders}
                allMutations={allMutations}
                totalIncomeCalc={totalIncomeCalc}
                totalExpenseCalc={totalExpenseCalc}
                menus={menus}
                activeMenu={activeMenu}
                handleMenuClick={handleMenuClick}
                setShowTopup={setShowTopup}
                setShowUpgradeModal={setShowUpgradeModal}
                setShowWaModal={setShowWaModal}
                setWaModalPhone={setWaModalPhone}
                setShowChangeWaModal={setShowChangeWaModal}
              />
            </div>
          </aside>

          {/* MAIN CONTENT */}
          <main className="flex-1 min-w-0">
            {/* Dashboard Profil */}
            {activeMenu === 'dashboard' && (
              <DashboardTab
                user={user}
                isReseller={isReseller}
                orders={orders}
                allMutations={allMutations}
                totalIncomeCalc={totalIncomeCalc}
                totalExpenseCalc={totalExpenseCalc}
                totalIncomeCount={totalIncomeCount}
                totalExpenseCount={totalExpenseCount}
                setShowTopup={setShowTopup}
                handleMenuClick={handleMenuClick}
                setWaModalPhone={setWaModalPhone}
                setShowWaModal={setShowWaModal}
              />
            )}

            {/* Edit Profil */}
            {activeMenu === 'profile' && (
              <EditProfileTab
                user={user}
                onOpenWaModal={(phone) => {
                  setWaModalPhone(phone || user?.whatsapp || '');
                  setShowWaModal(true);
                }}
                onOpenChangeWaModal={() => setShowChangeWaModal(true)}
              />
            )}

            {/* Riwayat Pembelian (Aplikasi vs PPOB vs Semua) */}
            {isOrdersTabActive && (
              <OrdersTab
                orders={currentOrderList}
                allOrders={orders}
                activeCategory={currentOrderCategory}
                onSelectCategory={(cat) => {
                  if (cat === 'ppob') handleMenuClick('orders_ppob');
                  else if (cat === 'all') handleMenuClick('orders_all');
                  else handleMenuClick('orders_app');
                }}
                filteredOrders={filteredOrders}
                paginatedOrders={paginatedOrders}
                orderSearchQuery={orderSearchQuery}
                setOrderSearchQuery={setOrderSearchQuery}
                orderPage={orderPage}
                setOrderPage={setOrderPage}
                orderTotalPages={orderTotalPages}
                copiedSnId={copiedSnId}
                setCopiedSnId={setCopiedSnId}
                showToast={showToast}
                handleSendOrderWa={handleSendOrderWa}
                waSendingId={waSendingId}
                isReseller={isReseller}
              />
            )}

            {/* Riwayat Top Up & Mutasi Saldo */}
            {activeMenu === 'topup' && (
              <MutationsTab
                allMutations={allMutations}
                filteredMutations={filteredMutations}
                paginatedMutations={paginatedMutations}
                mutationFilter={mutationFilter}
                setMutationFilter={setMutationFilter}
                mutationPage={mutationPage}
                setMutationPage={setMutationPage}
                mutationTotalPages={mutationTotalPages}
                totalIncomeCalc={totalIncomeCalc}
                totalExpenseCalc={totalExpenseCalc}
                totalIncomeCount={totalIncomeCount}
                totalExpenseCount={totalExpenseCount}
                user={user}
                isReseller={isReseller}
                setShowTopup={setShowTopup}
              />
            )}

            {/* Ubah Password */}
            {activeMenu === 'security' && <SecurityTab flash={flash} />}
          </main>
        </div>
      </div>

      {/* TOP UP MODAL */}
      {showTopup && (
        <TopupModal
          onClose={() => setShowTopup(false)}
          onSuccess={() => {
            setShowTopup(false);
            router.reload({ only: [] });
          }}
        />
      )}

      {/* UPGRADE RESELLER MODAL */}
      {showUpgradeModal && (
        <UpgradeResellerModal onClose={() => setShowUpgradeModal(false)} userBalance={user?.balance || 0} />
      )}

      {/* GANTI NOMOR WHATSAPP MODAL */}
      {showChangeWaModal && (
        <ChangeWaModal
          onClose={() => setShowChangeWaModal(false)}
          currentWhatsapp={user?.whatsapp || ''}
          onNumberChanged={handleNumberChanged}
        />
      )}

      {/* WA VERIFICATION MODAL */}
      {showWaModal && (
        <WaVerificationModal
          onClose={() => {
            setShowWaModal(false);
            setWaModalInitialData(null);
          }}
          userWhatsapp={waModalPhone || user?.whatsapp || ''}
          initialData={waModalInitialData}
          onOpenChangeModal={() => setShowChangeWaModal(true)}
          onVerified={() => {
            setShowWaModal(false);
            setWaModalInitialData(null);
            router.reload();
          }}
        />
      )}

      {/* FLOATING TOAST NOTIFICATION */}
      {toast && (
        <div className="fixed bottom-6 right-6 z-[250] flex items-center gap-3 px-4 py-3 bg-gray-900 dark:bg-gray-800 text-white rounded-2xl shadow-2xl border border-gray-700/80 text-xs font-bold animate-fade-in">
          <span>{toast.type === 'success' ? '✅' : '⚠️'}</span>
          <span>{toast.text}</span>
        </div>
      )}
    </div>
  );
}
