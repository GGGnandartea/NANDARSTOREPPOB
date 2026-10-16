import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

const fmt = {
  rp: (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0),
};

export default function Checkout({ product, paymentChannels }) {
  const { auth, store } = usePage().props;
  const user = auth?.user;
  const userBalance = user?.balance ?? 0;

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
  const [orderId, setOrderId] = useState(null);
  const [qrisUrl, setQrisUrl] = useState(null);
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
  const basePrice = isReseller ? Number(product.reseller_price) : Number(product.price);
  const finalPrice = Math.max(0, basePrice - discountAmount);
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
      timer: 2000
    });
    setTimeout(() => setCopied(false), 2000);
  };

  // Cek apakah teks akun masih berupa status tunggu provider
  const isAccountPending = (text) => {
    if (!text) return true;
    const lower = text.toLowerCase();
    return lower.includes('sedang diproses') || 
           lower.includes('menunggu') || 
           lower.includes('processing') || 
           lower.includes('pending') ||
           lower.includes('kosong') ||
           lower.includes('admin akan segera');
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
      if (data.status === 'SUCCESS' || data.status === 'LUNAS' || data.status === 'PAID' || data.status === 'COMPLETED' || data.payment_status === 'paid') {
        setOrderStatus('SUCCESS');
        setCompletedOrder(data);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Pembayaran Ditemukan & Berhasil!',
          showConfirmButton: false,
          timer: 3000
        });
      } else {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'info',
          title: 'Belum terdeteksi. Sistem akan otomatis cek terus.',
          showConfirmButton: false,
          timer: 3000
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
          if (data.status === 'SUCCESS' || data.status === 'LUNAS' || data.status === 'PAID' || data.status === 'COMPLETED' || data.payment_status === 'paid') {
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
              timer: 3500
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

  const checkVoucher = async () => {
    if (!voucher) return;
    setVoucherLoading(true);
    try {
      const res = await fetch('/api/check-voucher', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ code: voucher, product_id: product.id }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setDiscountAmount(data.discount_amount); setDiscountName(data.code);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: `Diskon Rp ${data.discount_amount.toLocaleString('id-ID')} diterapkan!`, showConfirmButton: false, timer: 3000 });
      } else {
        setDiscountAmount(0); setDiscountName('');
        Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: data.message || 'Voucher tidak valid.', showConfirmButton: false, timer: 3000 });
      }
    } catch { Swal.fire('Error', 'Gagal mengecek voucher', 'error'); }
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
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
          timer: 3000
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
    e.preventDefault(); setIsProcessing(true);
    try {
      const targetWa = user ? (user?.is_whatsapp_verified ? user.whatsapp : (whatsapp || user?.whatsapp || '')) : null;
      const res = await fetch('/checkout/process', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ product_id: product.id, buyer_name: buyerName, email: email || null, whatsapp: targetWa, notes, voucher_code: discountName }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setOrderId(data.order_id); setTotalNominal(data.total_nominal); setQrisUrl(data.url_qris);
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
      Swal.fire('Saldo Tidak Cukup', `Saldo kamu ${fmt.rp(userBalance)}, butuh ${fmt.rp(finalPrice)}. Silakan top up terlebih dahulu.`, 'warning');
      return;
    }
    const result = await Swal.fire({
      title: 'Konfirmasi Pembelian',
      html: `<p class="text-sm text-gray-600">Bayar <strong>${fmt.rp(finalPrice)}</strong> menggunakan saldo?<br>Sisa saldo: <strong>${fmt.rp(userBalance - finalPrice)}</strong></p>`,
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
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
        body: JSON.stringify({ product_id: product.id, buyer_name: buyerName, email: email || null, whatsapp: targetWa, notes, voucher_code: discountName }),
      });
      const data = await res.json();
      if (res.ok && data.status === 'success') {
        setBalanceSuccess(data);
        setCompletedOrder({
          order_id: data.order_id,
          product_name: data.product_name || product.name,
          total_amount: data.total_nominal || finalPrice,
          sn_or_account: data.sn_or_account,
          payment_method: 'Saldo Akun',
          payment_status: 'paid',
          status: 'LUNAS',
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
    Swal.fire({ title: 'Batalkan Pesanan?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', cancelButtonColor: '#f97316', confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Lanjut Bayar', reverseButtons: true, customClass: { popup: 'rounded-[2rem]' } })
      .then(r => { if (r.isConfirmed) window.location.href = '/'; });
  };

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0f172a] flex items-center justify-center p-4 sm:p-6 font-jakarta">
      <Head>
        <title>{`Checkout - ${store?.name || 'NandarStore'}`}</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
        <style>{`.font-jakarta{font-family:'Plus Jakarta Sans',sans-serif}`}</style>
      </Head>

      <div className="w-full max-w-[440px] bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">

        {/* HEADER */}
        <div className="bg-[#0b1121] p-6 sm:p-8 text-center relative overflow-hidden">
          <div className="absolute -top-10 -right-10 w-40 h-40 bg-orange-500 rounded-full blur-[60px] opacity-20 pointer-events-none"></div>
          {!orderId && !balanceSuccess && (
            <button onClick={() => window.history.back()} className="absolute left-5 top-6 text-gray-400 hover:text-white transition cursor-pointer">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </button>
          )}
          <button 
            type="button" 
            onClick={toggleDarkMode} 
            className="absolute right-5 top-6 p-2 rounded-xl text-gray-400 hover:text-white hover:bg-white/10 transition cursor-pointer"
            title={isDark ? 'Mode Terang' : 'Mode Gelap'}
          >
            {isDark ? (
              <svg className="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            ) : (
              <svg className="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            )}
          </button>
          <h2 className="text-xl sm:text-2xl font-black text-white mt-8 tracking-tight">Selesaikan Pembayaran</h2>
          <p className="text-orange-500 text-xs mt-1.5 font-bold tracking-widest uppercase">Sistem Cepat &amp; Aman</p>
        </div>

        <div className="p-6 sm:p-8">

          {/* ═══ FORM INPUT ═══ */}
          {!orderId && !balanceSuccess && (
            <div>
              {/* Ringkasan Produk */}
              <div className="bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl mb-6 flex items-center gap-3">
                {product.image && <img src={product.image} alt={product.name} className="w-12 h-12 object-contain rounded-xl bg-white border border-gray-100 p-1 shrink-0" />}
                <div className="min-w-0">
                  <p className="text-[10px] text-gray-400 font-black uppercase tracking-widest">Layanan Dipilih</p>
                  <h3 className="font-black text-gray-900 dark:text-white text-sm leading-tight truncate">{product.name}</h3>
                </div>
              </div>

              {user?.role === 'admin' ? (
                <div className="bg-amber-500/10 border-2 border-amber-500/30 rounded-3xl p-6 text-center space-y-4">
                  <div className="w-16 h-16 bg-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto text-3xl shadow-sm">
                    👑
                  </div>
                  <div>
                    <h3 className="text-lg font-black text-gray-900 dark:text-white">Mode Pantau Admin Aktif</h3>
                    <p className="text-xs text-gray-600 dark:text-gray-300 leading-relaxed font-medium mt-1.5">
                      Anda sedang melihat toko dalam status <strong>Administrator</strong>. Akun admin hanya memiliki hak pantau toko, tidak memiliki profil pembeli atau saldo, serta tidak dapat melakukan transaksi atau checkout produk.
                    </p>
                  </div>
                  <div className="pt-2">
                    <Link
                      href="/admin/dashboard"
                      className="inline-flex items-center justify-center gap-2 w-full bg-gray-900 hover:bg-black dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-black text-xs py-3.5 px-4 rounded-xl transition shadow-md cursor-pointer"
                    >
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                      Kembali ke Dashboard Admin
                    </Link>
                  </div>
                </div>
              ) : (
                <div className="space-y-4">
                  {/* Field Nama Pembeli */}
                  <div>
                    <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                      Nama Pembeli / Panggilan <span className="text-orange-500">*</span>
                    </label>
                    <input 
                      type="text" 
                      required 
                      value={buyerName} 
                      onChange={e => setBuyerName(e.target.value)} 
                      placeholder="Contoh: Budi, Cindy, Rizky..." 
                      className="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3.5 rounded-2xl text-sm font-bold text-gray-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" 
                    />
                  </div>

                  {/* Field WhatsApp */}
                  <div>
                    <div className="flex items-center justify-between mb-1.5">
                      <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                        Nomor WhatsApp
                      </label>
                      {!user ? (
                        <span className="text-[10px] font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full flex items-center gap-1 border border-gray-200 dark:border-gray-700">
                          🔒 Khusus Akun Terdaftar
                        </span>
                      ) : user?.is_whatsapp_verified ? (
                        <span className="text-[10px] font-black text-emerald-500 bg-emerald-500/10 px-2.5 py-0.5 rounded-full flex items-center gap-1 border border-emerald-500/20">
                          <span className="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          🔒 Terverifikasi (Terkunci)
                        </span>
                      ) : (
                        <span className="text-[10px] font-bold text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded-full">
                          Belum Verifikasi
                        </span>
                      )}
                    </div>
                    <div className="relative">
                      {!user ? (
                        <input 
                          type="tel" 
                          disabled={true}
                          value="" 
                          readOnly
                          placeholder="Fitur kirim WhatsApp dinonaktifkan (Khusus Akun Terdaftar)" 
                          className="w-full border-2 border-dashed border-gray-200 dark:border-gray-700 bg-gray-100/90 dark:bg-gray-800/60 p-3.5 rounded-2xl text-xs font-semibold text-gray-400 dark:text-gray-500 cursor-not-allowed select-none transition outline-none" 
                        />
                      ) : (
                        <input 
                          type="tel" 
                          readOnly={Boolean(user?.is_whatsapp_verified)}
                          value={user?.is_whatsapp_verified ? (user?.whatsapp || '') : whatsapp} 
                          onChange={e => {
                            if (!user?.is_whatsapp_verified) {
                              setWhatsapp(e.target.value);
                            }
                          }} 
                          placeholder="Contoh: 08123456789 atau 628xxx" 
                          className={`w-full border-2 p-3.5 rounded-2xl text-sm font-bold transition outline-none ${
                            user?.is_whatsapp_verified
                              ? 'bg-gray-100 dark:bg-gray-800/80 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 cursor-not-allowed select-none pr-10'
                              : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 text-gray-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10'
                          }`} 
                        />
                      )}
                      {user?.is_whatsapp_verified && (
                        <div className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 flex items-center gap-1 text-sm pointer-events-none" title="Nomor WhatsApp terkunci dari profil">
                          🔒
                        </div>
                      )}
                    </div>
                    {!user ? (
                      <div className="p-3 bg-amber-500/10 border border-amber-500/25 rounded-2xl text-[11px] text-amber-700 dark:text-amber-300 mt-2 flex items-start gap-2 shadow-sm">
                        <span className="text-base leading-none">⚠️</span>
                        <div className="leading-relaxed">
                          <span>Daftar atau login untuk mendapatkan detail akun dikirimkan langsung ke WhatsApp kamu. </span>
                          <Link href="/login" className="font-black underline text-orange-600 dark:text-orange-400 hover:text-orange-500">
                            Klik disini untuk Login / Daftar
                          </Link>
                        </div>
                      </div>
                    ) : user?.is_whatsapp_verified ? (
                      <p className="text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-1.5 flex items-center gap-1">
                        <span>🟢</span> Nomor permanen dari profil. Detail akun &amp; link invoice PDF dikirim ke WA ini.
                      </p>
                    ) : (
                      <p className="text-[11px] text-amber-600 dark:text-amber-400 font-medium mt-1.5">
                        ⚠️ Daftar atau login untuk mendapatkan detail akun dikirimkan langsung ke WhatsApp kamu. Verifikasi WhatsApp Anda di <Link href="/profile" className="underline font-bold text-orange-500">Profil</Link> agar detail dikirim otomatis.
                      </p>
                    )}
                  </div>

                  {/* Field Email (Opsional) */}
                  <div>
                    <div className="flex items-center justify-between mb-1.5">
                      <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                        Email Aktif <span className="text-gray-400 font-normal lowercase">(opsional, jika diperlukan)</span>
                      </label>
                      <span className="text-[10px] font-bold text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700">
                        Opsional
                      </span>
                    </div>
                    <input 
                      type="email" 
                      value={email} 
                      onChange={e => setEmail(e.target.value)} 
                      placeholder="Kosongkan jika tidak ada" 
                      className="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3.5 rounded-2xl text-sm font-bold text-gray-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none" 
                    />
                    <p className="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
                      *Opsional — isi jika kamu memerlukan salinan invoice / bukti pembelian dikirim ke email.
                    </p>
                  </div>

                  {/* Field Catatan (Opsional) */}
                  <div>
                    <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Catatan (Opsional)</label>
                    <input type="text" value={notes} onChange={e => setNotes(e.target.value)} placeholder="Kosongkan jika tidak ada" className="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3 rounded-2xl text-xs font-bold text-gray-900 dark:text-white focus:border-orange-500 transition outline-none" />
                  </div>

                {/* Voucher */}
                <div>
                  <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Kode Voucher (Opsional)</label>
                  <div className="flex gap-2">
                    <input type="text" value={voucher} onChange={e => setVoucher(e.target.value.toUpperCase())} disabled={discountAmount > 0} placeholder="Ketik kode voucher..." className="flex-1 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3.5 rounded-2xl text-sm font-black text-gray-900 dark:text-white placeholder-gray-400 focus:border-orange-500 transition outline-none disabled:bg-gray-50 dark:disabled:bg-gray-700 disabled:text-orange-600" />
                    {discountAmount === 0 ? (
                      <button type="button" onClick={checkVoucher} disabled={!voucher || voucherLoading} className="px-4 bg-gray-900 dark:bg-gray-700 hover:bg-black text-white font-bold rounded-2xl transition cursor-pointer disabled:opacity-50">
                        {voucherLoading ? '...' : 'Pakai'}
                      </button>
                    ) : (
                      <button type="button" onClick={() => { setVoucher(''); setDiscountAmount(0); setDiscountName(''); }} className="px-4 bg-red-100 hover:bg-red-200 text-red-600 font-bold rounded-2xl transition cursor-pointer">Batal</button>
                    )}
                  </div>
                </div>

                {/* Ringkasan Harga */}
                <div className="pt-4 border-t border-gray-100 dark:border-gray-700">
                  {isReseller && (
                    <div className="mb-3 bg-gradient-to-r from-amber-500/15 to-yellow-500/15 border border-amber-500/30 p-2.5 rounded-xl flex items-center justify-between">
                      <span className="text-xs font-bold text-amber-500 flex items-center gap-1.5">👑 Diskon Reseller VIP</span>
                      <span className="text-xs font-black text-amber-500">Hemat {fmt.rp(product.price - product.reseller_price)}</span>
                    </div>
                  )}
                  <div className="flex justify-between text-sm mb-1">
                    <span className="font-bold text-gray-400">Harga {isReseller ? 'Normal' : ''}</span>
                    <span className={`font-bold ${isReseller ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'}`}>{fmt.rp(product.price)}</span>
                  </div>
                  {isReseller && (
                    <div className="flex justify-between text-sm mb-1">
                      <span className="font-bold text-amber-500">Harga Khusus Reseller</span>
                      <span className="font-bold text-amber-500">{fmt.rp(product.reseller_price)}</span>
                    </div>
                  )}
                  {discountAmount > 0 && (
                    <div className="flex justify-between text-sm mb-1">
                      <span className="font-bold text-orange-500">Diskon ({discountName})</span>
                      <span className="font-bold text-orange-500">-{fmt.rp(discountAmount)}</span>
                    </div>
                  )}
                  <div className="flex justify-between items-end mt-3">
                    <span className="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Bayar</span>
                    <span className="text-3xl font-black text-gray-900 dark:text-white">{fmt.rp(finalPrice)}</span>
                  </div>
                </div>

                {/* ─── PILIH METODE PEMBAYARAN ─── */}
                <div>
                  <p className="text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Metode Pembayaran</p>
                  <div className="space-y-2">

                    {/* QRIS */}
                    <button
                      type="button"
                      onClick={() => setPayMethod('qris')}
                      className={`w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 transition cursor-pointer ${payMethod === 'qris' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20' : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600'}`}
                    >
                      <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition ${payMethod === 'qris' ? 'border-orange-500' : 'border-gray-300 dark:border-gray-600'}`}>
                        {payMethod === 'qris' && <div className="w-2.5 h-2.5 rounded-full bg-orange-500"></div>}
                      </div>
                      <div className="flex-1 text-left">
                        <p className="font-black text-sm text-gray-900 dark:text-white">QRIS Otomatis</p>
                        <p className="text-[10px] text-gray-400">E-Wallet, M-Banking — Instan</p>
                      </div>
                      <span className="text-[9px] font-black bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-0.5 rounded-full uppercase">Populer</span>
                    </button>

                    {/* SALDO */}
                    {user && (
                      <button
                        type="button"
                        onClick={() => setPayMethod('balance')}
                        className={`w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 transition cursor-pointer ${payMethod === 'balance' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600'} ${!canPayBalance ? 'opacity-60' : ''}`}
                      >
                        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition ${payMethod === 'balance' ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'}`}>
                          {payMethod === 'balance' && <div className="w-2.5 h-2.5 rounded-full bg-blue-500"></div>}
                        </div>
                        <div className="flex-1 text-left">
                          <p className="font-black text-sm text-gray-900 dark:text-white">Bayar dengan Saldo</p>
                          <p className="text-[10px] text-gray-400">Saldo kamu: <span className={`font-bold ${canPayBalance ? 'text-green-600' : 'text-red-500'}`}>{fmt.rp(userBalance)}</span></p>
                        </div>
                        {!canPayBalance && <span className="text-[9px] font-black bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full">Kurang</span>}
                        {canPayBalance && <span className="text-[9px] font-black bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-0.5 rounded-full">Cukup ✓</span>}
                      </button>
                    )}
                  </div>
                </div>

                {/* TOMBOL BAYAR */}
                {payMethod === 'qris' ? (
                  <button
                    type="button"
                    onClick={handleQrisCheckout}
                    disabled={isProcessing || !buyerName.trim()}
                    className="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-4 rounded-2xl shadow-xl shadow-orange-500/25 transition active:scale-[0.98] flex justify-center items-center gap-2 disabled:opacity-60 cursor-pointer mt-2"
                  >
                    {isProcessing ? <><svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</> : 'Bayar via QRIS →'}
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={handleBalanceCheckout}
                    disabled={isProcessing || !buyerName.trim() || !canPayBalance}
                    className="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-500/25 transition active:scale-[0.98] flex justify-center items-center gap-2 disabled:opacity-60 cursor-pointer mt-2"
                  >
                    {isProcessing ? <><svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</> : `Bayar ${fmt.rp(finalPrice)} dari Saldo →`}
                  </button>
                )}

                {/* Link top up jika saldo kurang */}
                {user && payMethod === 'balance' && !canPayBalance && (
                  <Link href="/profile" className="block text-center text-xs font-bold text-blue-500 hover:text-blue-600 hover:underline transition mt-1">
                    + Top Up Saldo dulu →
                  </Link>
                )}

                {!user && (
                  <p className="text-center text-xs text-gray-400 mt-1">
                    <Link href="/login" className="text-orange-500 font-bold hover:underline">Login</Link> untuk membayar dengan saldo
                  </p>
                )}
              </div>
            )}
          </div>
        )}

          {/* ═══ QRIS WAITING ═══ */}
          {orderId && orderStatus === 'WAITING_PAYMENT' && (
            <div className="text-center">
              {/* TOMBOL CEK PEMBAYARAN DI BAGIAN ATAS DENGAN COOLDOWN 100 DETIK */}
              <div className="mb-4">
                <button
                  type="button"
                  onClick={manualCheckPayment}
                  disabled={cooldown > 0 || isCheckingManual}
                  className={`w-full py-3.5 px-4 rounded-2xl font-black text-sm flex items-center justify-center gap-2.5 transition shadow-lg ${
                    cooldown > 0 || isCheckingManual
                      ? 'bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-gray-700 cursor-not-allowed select-none'
                      : 'bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 text-white shadow-orange-500/25 active:scale-[0.98] cursor-pointer'
                  }`}
                >
                  {isCheckingManual ? (
                    <>
                      <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                      </svg>
                      <span>Sedang Mengecek Status...</span>
                    </>
                  ) : cooldown > 0 ? (
                    <>
                      <svg className="w-4 h-4 animate-spin text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <span>⏱️ Cek Pembayaran ({cooldown}s)</span>
                    </>
                  ) : (
                    <>
                      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                      </svg>
                      <span>🔄 Cek Pembayaran Sekarang</span>
                    </>
                  )}
                </button>
              </div>

              {/* WARNING 5 MENIT */}
              <div className="mb-5 bg-gradient-to-r from-red-500/15 via-amber-500/15 to-red-500/15 border-2 border-red-500/30 dark:border-red-500/40 rounded-2xl p-4 text-left shadow-md">
                <div className="flex items-start gap-3">
                  <div className="w-9 h-9 rounded-xl bg-red-500/20 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0 font-black text-lg animate-bounce">
                    ⚠️
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-[11px] font-black text-red-600 dark:text-red-400 tracking-wide uppercase leading-tight">
                      PERHATIAN: QRIS INI BERLAKU SELAMA 5 MENIT SAJA
                    </p>
                    <p className="text-[11px] font-bold text-gray-700 dark:text-gray-200 mt-0.5 leading-snug">
                      MOHON SEGERA LAKUKAN PEMBAYARAN SEBELUM KEDALUWARSA!
                    </p>
                    <div className="mt-2 flex items-center gap-2">
                      <span className="text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Batas Waktu Bayar:
                      </span>
                      <span className={`font-mono text-xs font-black px-2.5 py-0.5 rounded-lg border flex items-center gap-1.5 ${
                        qrisTimeLeft < 60
                          ? 'bg-red-500 text-white border-red-600 animate-pulse'
                          : 'bg-red-50 dark:bg-red-950/60 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800/60'
                      }`}>
                        <span>⏱️</span>
                        {formatTimer(qrisTimeLeft)}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <p className="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Pembayaran</p>
              <p className="text-4xl font-black text-gray-900 dark:text-white mb-4">{fmt.rp(totalNominal)}</p>
              <p className="text-[10px] text-orange-600 font-bold bg-orange-50 dark:bg-orange-900/20 py-1.5 px-3 rounded-lg inline-block mb-5 border border-orange-100 dark:border-orange-800">
                ⚠️ Scan QRIS menggunakan E-Wallet / M-Banking Anda.
              </p>
              <div className="inline-flex items-center gap-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold py-2 px-4 rounded-xl mb-5">
                <span>No. Pesanan:</span><span className="text-orange-500 select-all font-mono">{orderId}</span>
              </div>
              <div className="bg-white dark:bg-gray-800 p-4 rounded-[2rem] border-2 border-dashed border-gray-200 dark:border-gray-600 inline-block mb-6">
                <div className="w-56 h-56 sm:w-60 sm:h-60 overflow-hidden rounded-2xl mx-auto bg-gray-50 dark:bg-gray-700 flex items-center justify-center">
                  {qrisUrl ? <img src={qrisUrl} alt="QRIS Code" className="w-full h-full object-contain" /> : <span className="text-gray-400 font-bold text-xs">Memuat QRIS...</span>}
                </div>
              </div>
              <div className="flex items-center justify-center gap-2 text-orange-500 font-bold text-xs animate-pulse bg-orange-50 dark:bg-orange-900/20 py-3 rounded-2xl mb-4">
                <div className="w-2 h-2 rounded-full bg-orange-500"></div>Menunggu Pembayaran Anda...
              </div>
              <button onClick={handleCancel} className="text-gray-400 hover:text-red-500 text-xs font-bold transition cursor-pointer w-full py-2">Batalkan Pesanan</button>
            </div>
          )}

          {/* ═══ SUCCESS ═══ */}
          {(orderStatus === 'SUCCESS' || balanceSuccess) && (
            <div className="text-center py-2 space-y-4">
              {isAccountPending(completedOrder?.sn_or_account || balanceSuccess?.sn_or_account) ? (
                /* ─── STATE MENUNGGU AKUN (PROMINENT LARGE LOADING CARD) ─── */
                <div className="bg-gradient-to-br from-orange-500/10 via-amber-500/10 to-orange-600/5 border-2 border-orange-500/30 rounded-3xl p-6 sm:p-7 text-center relative overflow-hidden shadow-2xl space-y-4 animate-fade-in">
                  {/* Glowing background circles */}
                  <div className="absolute -top-10 -left-10 w-36 h-36 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>
                  <div className="absolute -bottom-10 -right-10 w-36 h-36 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>

                  {/* Large Orbital Glowing Loader */}
                  <div className="relative w-20 h-20 mx-auto flex items-center justify-center pt-1">
                    <div className="absolute inset-0 rounded-full border-4 border-orange-500/20"></div>
                    <div className="absolute inset-0 rounded-full border-4 border-transparent border-t-orange-500 border-r-amber-400 animate-spin"></div>
                    <div className="w-12 h-12 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center text-white text-xl font-black shadow-lg shadow-orange-500/40">
                      ⚡
                    </div>
                  </div>

                  {/* Requested Text: Big & Prominent */}
                  <div className="space-y-1.5 relative z-10">
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/15 border border-orange-500/30 text-orange-600 dark:text-orange-400 text-[10px] font-black uppercase tracking-wider">
                      <span className="w-2 h-2 rounded-full bg-orange-500 animate-ping"></span>
                      Pembayaran Berhasil • Menyiapkan Lisensi
                    </span>
                    <h3 className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white leading-tight">
                      Akun sedang disiapkan, jangan tutup halaman ini.
                    </h3>
                    <p className="text-sm sm:text-base font-black text-orange-600 dark:text-orange-400 max-w-md mx-auto leading-relaxed animate-pulse">
                      Akun akan muncul secara otomatis!!!
                    </p>
                  </div>

                  {/* Animated Progress Bar */}
                  <div className="max-w-xs mx-auto space-y-2 relative z-10">
                    <div className="w-full h-2.5 bg-gray-200 dark:bg-gray-800 rounded-full overflow-hidden relative shadow-inner">
                      <div className="h-full w-1/2 bg-gradient-to-r from-orange-500 via-amber-400 to-orange-500 rounded-full animate-progress-slide"></div>
                    </div>
                    <p className="text-[11px] text-gray-400 dark:text-gray-500 font-medium">
                      ⚡ Sedang memproses dan menyiapkan lisensi Anda secara otomatis...
                    </p>
                  </div>
                </div>
              ) : (
                /* ─── STATE AKUN SUDAH SIAP ─── */
                <>
                  <div className="w-16 h-16 bg-emerald-500/10 text-emerald-500 rounded-full flex items-center justify-center mx-auto border border-emerald-500/20 shadow-lg shadow-emerald-500/10">
                    <svg className="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="3" d="M5 13l4 4L19 7"/></svg>
                  </div>
                  
                  <div>
                    <h3 className="text-2xl font-black text-gray-900 dark:text-white">Hore, Pembayaran Berhasil!</h3>
                    <p className="text-gray-500 dark:text-gray-400 text-xs mt-1">
                      Pesanan telah lunas. Detail akun &amp; dokumen invoice Anda siap diakses di bawah ini.
                    </p>
                  </div>

                  {/* KOTAK DETAIL AKUN & KREDENSIAL */}
                  <div className="bg-[#0b1121] text-white p-5 rounded-3xl border border-gray-800 text-left shadow-xl relative overflow-hidden">
                    <div className="flex items-center justify-between mb-2.5">
                      <span className="text-[10px] font-black tracking-widest uppercase text-orange-400 flex items-center gap-1.5">
                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        Detail Akun / Lisensi Anda
                      </span>
                      <button
                        type="button"
                        onClick={() => handleCopyAccount(completedOrder?.sn_or_account || balanceSuccess?.sn_or_account)}
                        className="px-2.5 py-1 bg-white/10 hover:bg-white/20 text-white rounded-lg text-[10px] font-extrabold transition cursor-pointer flex items-center gap-1"
                      >
                        {copied ? '✓ Tersalin!' : '📋 Salin Akun'}
                      </button>
                    </div>

                    <div className="bg-[#020617] border border-gray-800 rounded-2xl p-3.5 font-mono text-xs text-cyan-400 whitespace-pre-wrap break-all select-all leading-relaxed min-h-[60px] flex items-center">
                      {completedOrder?.sn_or_account || balanceSuccess?.sn_or_account}
                    </div>

                    <p className="text-[10px] text-gray-400 mt-2 font-medium">
                      *Simpan email &amp; password atau kode lisensi ini baik-baik.
                    </p>
                  </div>
                </>
              )}

              {/* RINGKASAN TRANSAKSI */}
              <div className="bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700 p-4 rounded-2xl text-left space-y-2 text-xs">
                <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
                  <span className="text-gray-400 font-bold">No. Invoice</span>
                  <span className="font-mono font-bold text-gray-900 dark:text-white select-all">{completedOrder?.order_id || balanceSuccess?.order_id || orderId}</span>
                </div>
                <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
                  <span className="text-gray-400 font-bold">Layanan</span>
                  <span className="font-bold text-gray-900 dark:text-white truncate max-w-[200px]">{completedOrder?.product_name || product.name}</span>
                </div>
                <div className="flex justify-between items-center py-1 border-b border-gray-200/50 dark:border-gray-700/50">
                  <span className="text-gray-400 font-bold">Metode Bayar</span>
                  <span className="font-bold text-gray-900 dark:text-white">{balanceSuccess ? 'Saldo Akun' : 'QRIS Otomatis'}</span>
                </div>
                <div className="flex justify-between items-center py-1">
                  <span className="text-gray-400 font-bold">Total Lunas</span>
                  <span className="font-black text-orange-500 text-sm">{fmt.rp(completedOrder?.total_amount || balanceSuccess?.total_nominal || finalPrice)}</span>
                </div>
              </div>

              {/* ACTION BUTTONS */}
              <div className="space-y-2.5 pt-1">
                <a
                  href={`/order/invoice/${completedOrder?.order_id || balanceSuccess?.order_id || orderId}`}
                  target="_blank"
                  rel="noreferrer"
                  className="w-full bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black py-3.5 px-4 rounded-2xl shadow-xl shadow-orange-500/20 transition flex items-center justify-center gap-2 cursor-pointer text-sm"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  Download / Cetak Invoice (PDF)
                </a>

                <Link
                  href="/"
                  className="block w-full bg-gray-900 dark:bg-gray-700 hover:bg-black dark:hover:bg-gray-600 text-white font-black py-3.5 rounded-2xl transition cursor-pointer text-xs"
                >
                  Selesai &amp; Kembali Belanja
                </Link>
              </div>

              {/* INFO WHATSAPP NOTIFIKASI */}
              {user?.is_whatsapp_verified ? (
                <div className="bg-emerald-500/10 border border-emerald-500/25 p-4 rounded-2xl text-left flex items-start gap-3">
                  <div className="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 font-bold text-sm">
                    📱
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between">
                      <p className="text-xs font-black text-emerald-600 dark:text-emerald-400">
                        {waSentSuccess ? '✓ Terkirim ke WhatsApp!' : 'Detail Akun & Invoice ke WhatsApp'}
                      </p>
                      <span className="text-[9px] bg-emerald-500/20 text-emerald-500 font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">
                        Otomatis
                      </span>
                    </div>
                    <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                      Pesan otomatis berisi detail akun dan invoice resmi dikirimkan ke nomor WhatsApp terverifikasi: <strong className="text-gray-900 dark:text-white font-mono">{user.whatsapp}</strong>
                    </p>
                    <button
                      type="button"
                      onClick={() => handleResendWa(completedOrder?.order_id || balanceSuccess?.order_id || orderId)}
                      disabled={isResendingWa}
                      className="mt-2.5 inline-flex items-center gap-1.5 text-xs font-black text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer disabled:opacity-50"
                    >
                      {isResendingWa ? (
                        <>
                          <svg className="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                          <span>Mengirim ulang ke WhatsApp...</span>
                        </>
                      ) : (
                        <span>📲 Kirim Ulang ke WhatsApp</span>
                      )}
                    </button>
                  </div>
                </div>
              ) : !user ? (
                <div className="bg-amber-500/10 border border-amber-500/20 p-3.5 rounded-2xl text-left">
                  <p className="text-[11px] text-amber-700 dark:text-amber-400 font-bold leading-relaxed">
                    💡 <strong>Tips:</strong> Ingin struk pesanan &amp; detail akun otomatis dikirim ke WhatsApp?{' '}
                    <Link href="/login" className="underline font-black text-orange-600 dark:text-orange-400">
                      Daftar atau Login sekarang
                    </Link>
                  </p>
                </div>
              ) : (
                <div className="bg-amber-500/10 border border-amber-500/20 p-3.5 rounded-2xl text-left">
                  <p className="text-[11px] text-amber-700 dark:text-amber-400 font-bold leading-relaxed">
                    💡 <strong>Tips:</strong> Ingin struk pesanan &amp; detail akun otomatis dikirim ke WhatsApp Anda? Verifikasi nomor WhatsApp Anda di <Link href="/profile" className="underline font-black">Menu Profil</Link>.
                  </p>
                </div>
              )}
            </div>
          )}

        </div>
      </div>
    </div>
  );
}