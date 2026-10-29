import React, { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { rememberGuestOrder } from '../../utils/guestOrders';
import Swal from 'sweetalert2';

import OrderProgress from '../../Components/OrderProgress';
import CheckoutHeader from './Partials/CheckoutHeader';
import CheckoutProductSummary from './Partials/CheckoutProductSummary';
import CheckoutAdminAlert from './Partials/CheckoutAdminAlert';
import CheckoutForm from './Partials/CheckoutForm';
import CheckoutQrisSection from './Partials/CheckoutQrisSection';
import CheckoutSuccessSection from './Partials/CheckoutSuccessSection';

const fmt = {
  rp: (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0),
};

export default function CheckoutIndex({ product, paymentChannels, initialQty = 1 }) {
  const { auth, store } = usePage().props;
  const user = auth?.user;
  const userBalance = user?.balance ?? 0;

  const maxStock = product.stock > 0 ? Math.min(100, Math.max(1, Number(product.stock) || 1)) : (product.type === 'local' && product.process_type !== 'manual' ? 0 : 100);

  const [quantity, setQuantity] = useState(() => {
    const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
    const qParam = urlParams ? parseInt(urlParams.get('qty'), 10) : null;
    const initial = Number.isInteger(qParam) && qParam > 0 ? qParam : (initialQty || 1);
    return Math.max(1, Math.min(initial, maxStock));
  });

  const [buyerName, setBuyerName] = useState(user?.name || '');
  const [email, setEmail] = useState(user?.email || '');
  const [whatsapp, setWhatsapp] = useState(user?.whatsapp || '');
  const [notes, setNotes] = useState('');
  const [voucher, setVoucher] = useState('');
  const [voucherLoading, setVoucherLoading] = useState(false);
  const [discountAmount, setDiscountAmount] = useState(0);
  const [discountName, setDiscountName] = useState('');

  // payment method: 'qris' | 'balance'
  const [payMethod, setPayMethod] = useState('qris');

  const [isProcessing, setIsProcessing] = useState(false);
  const [trackingData, setTrackingData] = useState(null);
  const [orderId, setOrderId] = useState(null);
  const [qrisUrl, setQrisUrl] = useState(null);
  const [invoiceUrl, setInvoiceUrl] = useState(null);
  const [totalNominal, setTotalNominal] = useState(0);
  const [orderStatus, setOrderStatus] = useState('WAITING_PAYMENT');
  const [balanceSuccess, setBalanceSuccess] = useState(null); // { order_id, message }
  const [completedOrder, setCompletedOrder] = useState(null);
  const [copied, setCopied] = useState(false);
  const [isResendingWa, setIsResendingWa] = useState(false);
  const [waSentSuccess, setWaSentSuccess] = useState(false);

  // QRIS Manual Check Cooldown & 5-minute Expiration Timer
  const [cooldown, setCooldown] = useState(0);
  const [isCheckingManual, setIsCheckingManual] = useState(false);
  const [qrisTimeLeft, setQrisTimeLeft] = useState(300); // 5 menit (300 detik)
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

  const isReseller = user?.role === 'reseller' && Boolean(product.reseller_price);
  const unitPrice = isReseller ? Number(product.reseller_price) : Number(product.price);
  const subtotal = unitPrice * quantity;
  const finalPrice = Math.max(0, subtotal - discountAmount);
  const canPayBalance = user && userBalance >= finalPrice;

  const handleCopyAccount = (text) => {
    if (!text) return;
    navigator.clipboard.writeText(text);
    setCopied(true);
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Detail akun berhasil disalin!',
      showConfirmButton: false,
      timer: 2000,
    });
    setTimeout(() => setCopied(false), 2000);
  };

  // Cek apakah teks akun masih berupa status tunggu provider
  const isAccountPending = (text) => {
    if (!text) return true;
    const lower = String(text).toLowerCase();
    return (
      lower.includes('sedang diproses') ||
      lower.includes('menunggu') ||
      lower.includes('processing') ||
      lower.includes('pending') ||
      lower.includes('kosong') ||
      lower.includes('admin akan segera')
    );
  };

  // Cooldown timer (100 detik)
  useEffect(() => {
    if (cooldown <= 0) return;
    const timer = setInterval(() => {
      setCooldown((prev) => (prev > 0 ? prev - 1 : 0));
    }, 1000);
    return () => clearInterval(timer);
  }, [cooldown]);

  // QRIS 5-minute countdown (300 detik)
  useEffect(() => {
    if (!orderId || orderStatus !== 'WAITING_PAYMENT') return;
    setQrisTimeLeft(300);
    const timer = setInterval(() => {
      setQrisTimeLeft((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(timer);
  }, [orderId, orderStatus]);

  const formatTimer = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
  };

  const manualCheckPayment = async () => {
    if (cooldown > 0 || isCheckingManual || !orderId) return;
    setIsCheckingManual(true);
    setCooldown(100);
    try {
      const res = await fetch(`/checkout/status/${orderId}`);
      const data = await res.json();
          if (res.ok && data.order_id) setTrackingData(data);
      if (
        data.status === 'SUCCESS' ||
        data.status === 'LUNAS' ||
        data.status === 'PAID' ||
        data.status === 'COMPLETED' ||
        data.payment_status === 'paid'
      ) {
        setOrderStatus('SUCCESS');
        setCompletedOrder(data);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Pembayaran Ditemukan & Berhasil!',
          showConfirmButton: false,
          timer: 3000,
        });
      } else {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: 'Belum terdeteksi. Sistem akan otomatis cek terus.',
          showConfirmButton: false,
          timer: 3000,
        });
      }
    } catch {
      Swal.fire('Error', 'Gagal memeriksa status pembayaran.', 'error');
    }
    setIsCheckingManual(false);
  };

  // Polling QRIS
  useEffect(() => {
    let interval;
    if (orderId && orderStatus === 'WAITING_PAYMENT') {
      interval = setInterval(async () => {
        try {
          const res = await fetch(`/checkout/status/${orderId}`);
          const data = await res.json();
          if (res.ok && data.order_id) setTrackingData(data);
          if (
            data.status === 'SUCCESS' ||
            data.status === 'LUNAS' ||
            data.status === 'PAID' ||
            data.status === 'COMPLETED' ||
            data.payment_status === 'paid'
          ) {
            setOrderStatus('SUCCESS');
            setCompletedOrder(data);
            clearInterval(interval);
          }
        } catch {}
      }, 3000);
    }
    return () => clearInterval(interval);
  }, [orderId, orderStatus]);

  // Polling lanjutan untuk API Orders jika akun belum selesai / masih generating
  useEffect(() => {
    let apiInterval;
    const currentOrderId = completedOrder?.order_id || balanceSuccess?.order_id || orderId;
    const currentSn = completedOrder?.sn_or_account || balanceSuccess?.sn_or_account;

    if (currentOrderId && (orderStatus === 'SUCCESS' || balanceSuccess) && isAccountPending(currentSn)) {
      let pollCount = 0;
      apiInterval = setInterval(async () => {
        pollCount++;
        try {
          const res = await fetch(`/checkout/status/${currentOrderId}`);
          const data = await res.json();
          if (res.ok && data.order_id) setTrackingData(data);
          if (data && data.sn_or_account && !isAccountPending(data.sn_or_account)) {
            setCompletedOrder((prev) => ({
              ...(prev || {}),
              ...data,
              sn_or_account: data.sn_or_account,
            }));
            if (balanceSuccess) {
              setBalanceSuccess((prev) => ({
                ...(prev || {}),
                sn_or_account: data.sn_or_account,
              }));
            }
            clearInterval(apiInterval);
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: 'Akun berhasil diterima dari server!',
              showConfirmButton: false,
              timer: 3500,
            });
          } else if (pollCount >= 40) {
            clearInterval(apiInterval);
          }
        } catch {}
      }, 3000);
    }

    return () => {
      if (apiInterval) clearInterval(apiInterval);
    };
  }, [orderId, orderStatus, balanceSuccess, completedOrder?.sn_or_account, balanceSuccess?.sn_or_account]);

  const getCsrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
  };

  const handleQuantityChange = (newQty) => {
    const validQty = Math.max(1, Math.min(newQty, maxStock));
    setQuantity(validQty);
    if (voucher && discountName) {
      checkVoucher(validQty, false);
    }
  };

  const checkVoucher = async (qtyToUse = quantity, showToast = true) => {
    if (!voucher) return;
    setVoucherLoading(true);
    try {
      const res = await fetch('/api/check-voucher', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ code: voucher, product_id: product.id, quantity: qtyToUse }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setDiscountAmount(data.discount_amount);
        setDiscountName(data.code);
        if (showToast) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Diskon Rp ${data.discount_amount.toLocaleString('id-ID')} diterapkan!`,
            showConfirmButton: false,
            timer: 3000,
          });
        }
      } else {
        setDiscountAmount(0);
        setDiscountName('');
        if (showToast) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: data.message || 'Voucher tidak valid.',
            showConfirmButton: false,
            timer: 3000,
          });
        }
      }
    } catch {
      if (showToast) {
        Swal.fire('Error', 'Gagal mengecek voucher', 'error');
      }
    }
    setVoucherLoading(false);
  };

  // Kirim Ulang ke WhatsApp
  const handleResendWa = async (oid) => {
    const idToUse = oid || completedOrder?.order_id || balanceSuccess?.order_id || orderId;
    if (!idToUse) return;
    setIsResendingWa(true);
    try {
      const res = await fetch(`/order/${idToUse}/resend-wa`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setWaSentSuccess(true);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: `Terkirim ke WA (${data.target})!`,
          showConfirmButton: false,
          timer: 3000,
        });
      } else {
        Swal.fire('Info', data.message || 'Gagal mengirim pesan WhatsApp.', 'info');
      }
    } catch {
      Swal.fire('Error', 'Gagal menghubungi server.', 'error');
    }
    setIsResendingWa(false);
  };

  // Bayar via QRIS
  const handleQrisCheckout = async (e) => {
    e.preventDefault();
    setIsProcessing(true);
    try {
      const targetWa = user ? (user?.is_whatsapp_verified ? user.whatsapp : (whatsapp || user?.whatsapp || '')) : null;
      const res = await fetch('/checkout/process', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({
          product_id: product.id,
          quantity,
          buyer_name: buyerName,
          email: email || null,
          whatsapp: targetWa,
          notes,
          voucher_code: discountName,
        }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setTrackingData(data);
        setOrderId(data.order_id);
        setTotalNominal(data.total_nominal);
        setQrisUrl(data.url_qris);
        setInvoiceUrl(data.invoice_url);
        if (!user) rememberGuestOrder({ ...data, product_name: product.name });
      } else {
        Swal.fire('Mohon Maaf', data.message || 'Gagal memproses pembayaran.', 'error');
      }
    } catch (err) {
      console.error('QRIS Checkout error:', err);
      Swal.fire('Error', 'Terjadi kesalahan server saat memproses tagihan.', 'error');
    }
    setIsProcessing(false);
  };

  // Bayar via Saldo
  const handleBalanceCheckout = async (e) => {
    e.preventDefault();
    if (!canPayBalance) {
      Swal.fire(
        'Saldo Tidak Cukup',
        `Saldo kamu ${fmt.rp(userBalance)}, butuh ${fmt.rp(finalPrice)}. Silakan top up terlebih dahulu.`,
        'warning'
      );
      return;
    }
    const result = await Swal.fire({
      title: 'Konfirmasi Pembelian',
      html: `<p class="text-sm text-gray-600">Bayar <strong>${fmt.rp(finalPrice)}</strong> untuk <strong>${quantity} pcs</strong> menggunakan saldo?<br>Sisa saldo: <strong>${fmt.rp(userBalance - finalPrice)}</strong></p>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#f97316',
      cancelButtonColor: '#6b7280',
      confirmButtonText: 'Ya, Bayar!',
      cancelButtonText: 'Batal',
    });
    if (!result.isConfirmed) return;

    setIsProcessing(true);
    try {
      const targetWa = user?.is_whatsapp_verified ? user.whatsapp : (whatsapp || user?.whatsapp || '');
      const res = await fetch('/profile/buy-balance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({
          product_id: product.id,
          quantity,
          buyer_name: buyerName,
          email: email || null,
          whatsapp: targetWa,
          notes,
          voucher_code: discountName,
        }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setTrackingData(data);
        setBalanceSuccess(data);
        setCompletedOrder({
          order_id: data.order_id,
          product_name: data.product_name || product.name,
          total_amount: data.total_nominal || finalPrice,
          sn_or_account: data.sn_or_account,
          payment_method: 'Saldo Akun',
          payment_status: 'paid',
          status: 'LUNAS',
          quantity: data.quantity || quantity,
          created_at: data.created_at,
          wa_sent: data.wa_sent,
          wa_target: data.wa_target,
        });
        if (data.wa_sent) setWaSentSuccess(true);
        setOrderStatus('SUCCESS');
      } else {
        Swal.fire('Gagal', data.message || 'Gagal memproses pembayaran dengan saldo.', 'error');
      }
    } catch (err) {
      console.error('Balance checkout error:', err);
      Swal.fire('Error', 'Terjadi kesalahan server saat pembayaran saldo.', 'error');
    }
    setIsProcessing(false);
  };

  const handleCancel = () => {
    Swal.fire({
      title: 'Batalkan Pesanan?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#f97316',
      confirmButtonText: 'Ya, Batalkan',
      cancelButtonText: 'Lanjut Bayar',
      reverseButtons: true,
      customClass: { popup: 'rounded-[2rem]' },
    }).then((r) => {
      if (r.isConfirmed) window.location.href = '/';
    });
  };

  const canGoBack = !orderId && !balanceSuccess;

  return (
    <div className="checkout-ui min-h-[100dvh] bg-gray-50 dark:bg-[#0f172a] flex items-start sm:items-center justify-center py-3 px-3 sm:p-6 font-jakarta overflow-x-hidden">
      <Head>
        <title>{`Checkout - ${store?.name || 'PremiumKita'}`}</title>
        <link
          href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap"
          rel="stylesheet"
        />
        <style>{`.font-jakarta{font-family:'Plus Jakarta Sans',sans-serif}`}</style>
      </Head>

      <div className="w-full max-w-[440px] my-auto bg-white dark:bg-gray-900 rounded-3xl sm:rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        {/* HEADER */}
        <CheckoutHeader
          store={store}
          isDark={isDark}
          toggleDarkMode={toggleDarkMode}
          canGoBack={canGoBack}
        />

        <div className="p-4 sm:p-8">
          {trackingData && <OrderProgress order={trackingData} />}
          {/* ═══ FORM INPUT ═══ */}
          {!orderId && !balanceSuccess && (
            <div>
              {/* Ringkasan Produk */}
              <CheckoutProductSummary product={product} quantity={quantity} />

              {user?.role === 'admin' ? (
                <CheckoutAdminAlert />
              ) : (
                <CheckoutForm
                  user={user}
                  userBalance={userBalance}
                  product={product}
                  quantity={quantity}
                  setQuantity={handleQuantityChange}
                  maxStock={maxStock}
                  unitPrice={unitPrice}
                  subtotal={subtotal}
                  buyerName={buyerName}
                  setBuyerName={setBuyerName}
                  email={email}
                  setEmail={setEmail}
                  whatsapp={whatsapp}
                  setWhatsapp={setWhatsapp}
                  notes={notes}
                  setNotes={setNotes}
                  voucher={voucher}
                  setVoucher={setVoucher}
                  voucherLoading={voucherLoading}
                  checkVoucher={checkVoucher}
                  discountAmount={discountAmount}
                  setDiscountAmount={setDiscountAmount}
                  discountName={discountName}
                  setDiscountName={setDiscountName}
                  isReseller={isReseller}
                  finalPrice={finalPrice}
                  canPayBalance={canPayBalance}
                  payMethod={payMethod}
                  setPayMethod={setPayMethod}
                  isProcessing={isProcessing}
                  handleQrisCheckout={handleQrisCheckout}
                  handleBalanceCheckout={handleBalanceCheckout}
                  fmt={fmt}
                />
              )}
            </div>
          )}

          {/* ═══ QRIS WAITING ═══ */}
          {orderId && orderStatus === 'WAITING_PAYMENT' && (
            <CheckoutQrisSection
              manualCheckPayment={manualCheckPayment}
              cooldown={cooldown}
              isCheckingManual={isCheckingManual}
              qrisTimeLeft={qrisTimeLeft}
              formatTimer={formatTimer}
              totalNominal={totalNominal}
              orderId={orderId}
              invoiceUrl={invoiceUrl}
              qrisUrl={qrisUrl}
              handleCancel={handleCancel}
              fmt={fmt}
            />
          )}

          {/* ═══ SUCCESS ═══ */}
          {(orderStatus === 'SUCCESS' || balanceSuccess) && (
            <CheckoutSuccessSection
              isAccountPending={isAccountPending}
              completedOrder={completedOrder}
              balanceSuccess={balanceSuccess}
              orderId={orderId}
              invoiceUrl={invoiceUrl}
              handleCopyAccount={handleCopyAccount}
              copied={copied}
              product={product}
              quantity={completedOrder?.quantity || balanceSuccess?.quantity || quantity}
              finalPrice={finalPrice}
              user={user}
              waSentSuccess={waSentSuccess}
              handleResendWa={handleResendWa}
              isResendingWa={isResendingWa}
              fmt={fmt}
            />
          )}
        </div>
      </div>
    </div>
  );
}

