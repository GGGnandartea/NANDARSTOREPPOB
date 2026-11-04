import React, { useState, useRef, useEffect } from 'react';
import { Icon, fmt } from './ProfileIcons';

export default function UpgradeResellerModal({ onClose, userBalance, resellerSettings }) {
  const isFree = Boolean(resellerSettings?.is_free);
  const fee = Number(resellerSettings?.fee ?? 20000);
  const promoTitle = resellerSettings?.promo_title || 'PROMO RESELLER VIP GRATIS!';
  const promoMessage = resellerSettings?.promo_message || 'Buka harga modal reseller termurah untuk semua produk tanpa biaya pendaftaran!';
  const promoBadge = resellerSettings?.promo_badge || 'PROMO VIP';

  const [method, setMethod] = useState(isFree || userBalance >= fee ? 'balance' : 'qris');
  const [step, setStep] = useState('choose'); // choose | qris | done
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [qrisData, setQrisData] = useState(null);
  const [imgLoaded, setImgLoaded] = useState(false);
  const [timeLeft, setTimeLeft] = useState(1200);
  const [checking, setChecking] = useState(false);
  const [cooldown, setCooldown] = useState(0);
  const [checkMsg, setCheckMsg] = useState('');
  const pollingRef = useRef(null);

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setInterval(() => setCooldown((prev) => (prev > 0 ? prev - 1 : 0)), 1000);
    return () => clearInterval(t);
  }, [cooldown]);

  const getCsrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
  };

  const handleUpgradeBalance = async () => {
    setLoading(true); setError('');
    try {
      const res = await fetch('/reseller/upgrade-balance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      });
      const data = await res.json();
      if (data.status === 'success') {
        setStep('done');
        setTimeout(() => window.location.reload(), 2000);
      } else {
        setError(data.message || 'Gagal upgrade.');
      }
    } catch { setError('Terjadi kesalahan server.'); }
    setLoading(false);
  };

  const handleUpgradeQris = async () => {
    setLoading(true); setError('');
    try {
      const res = await fetch('/reseller/upgrade-qris', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
      });
      const data = await res.json();
      if (data.status === 'success') {
        if (data.is_free) {
          setStep('done');
          setTimeout(() => window.location.reload(), 2000);
        } else {
          setImgLoaded(false);
          setTimeLeft(1200);
          setQrisData(data);
          setStep('qris');
          startPolling(data.order_id);
        }
      } else {
        setError(data.message || 'Gagal membuat QRIS.');
      }
    } catch { setError('Terjadi kesalahan server.'); }
    setLoading(false);
  };

  const checkStatusOnce = async (orderId, isManual = false) => {
    if (!orderId || (isManual && cooldown > 0)) return;
    if (isManual) { setChecking(true); setCooldown(100); setCheckMsg(''); }
    try {
      const res = await fetch(`/reseller/payment-status/${orderId}`);
      const data = await res.json();
      if (data.status === 'paid' || data.status === 'PAID') {
        clearInterval(pollingRef.current);
        setStep('done');
        setTimeout(() => window.location.reload(), 2000);
      } else if (isManual) {
        setCheckMsg('Pembayaran belum terdeteksi. Pastikan nominal unik sesuai.');
      }
    } catch {
      if (isManual) setCheckMsg('Gagal mengecek status pembayaran.');
    } finally {
      if (isManual) setChecking(false);
    }
  };

  const startPolling = (orderId) => {
    pollingRef.current = setInterval(() => {
      checkStatusOnce(orderId, false);
    }, 3000);
  };

  useEffect(() => {
    if (step !== 'qris' || timeLeft <= 0) return;
    const t = setInterval(() => setTimeLeft(prev => Math.max(0, prev - 1)), 1000);
    return () => clearInterval(t);
  }, [step, timeLeft]);

  useEffect(() => () => clearInterval(pollingRef.current), []);

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/70 backdrop-blur-md" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="w-full max-w-sm bg-gray-900 rounded-3xl shadow-2xl overflow-y-auto max-h-[92dvh] border border-amber-500/40 text-white">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 p-5 flex items-center justify-between text-gray-950">
          <div>
            <span className="text-[11px] font-black uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded-full inline-block mb-1">Aktivasi Seumur Hidup</span>
            <h3 className="font-black text-lg">Upgrade ke Reseller VIP</h3>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-black/20 flex items-center justify-center text-gray-950 hover:bg-black/30 transition cursor-pointer" aria-label="Tutup Upgrade Reseller">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6">
          {step === 'choose' && (
            <div className="space-y-4">
              <div className="text-center pb-2">
                <div className="flex items-center justify-center gap-2 mb-1">
                  <p className="text-xs text-gray-400 font-medium uppercase">Biaya Upgrade</p>
                  {isFree && (
                    <span className="text-[11px] font-black px-2 py-0.5 rounded-full bg-emerald-500 text-white uppercase animate-pulse">
                      {promoBadge}
                    </span>
                  )}
                </div>
                <p className={`text-3xl font-black mt-0.5 ${isFree ? 'text-emerald-400' : 'text-amber-400'}`}>
                  {isFree ? 'GRATIS (Rp 0)' : fmt.rp(fee)}
                </p>
                <p className="text-[11px] text-gray-300 mt-1 font-medium leading-relaxed">
                  {isFree ? promoMessage : 'Buka harga modal reseller termurah untuk semua produk!'}
                </p>
              </div>

              {error && <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold p-3 rounded-xl">{error}</div>}

              {isFree ? (
                /* ─── TAMPILAN PROMO GRATIS: 1-KLIK AKTIVASI ─── */
                <div className="space-y-3 pt-1">
                  <div className="bg-emerald-500/10 border border-emerald-500/30 p-3.5 rounded-2xl text-left space-y-1">
                    <p className="text-xs font-black text-emerald-400 flex items-center gap-1.5">
                      <span>🎉</span> Promo Spesial Aktif!
                    </p>
                    <p className="text-[11px] text-gray-300 leading-relaxed font-medium">
                      Anda berhak mendapatkan upgrade VIP Reseller secara gratis tanpa potong saldo atau pembayaran QRIS.
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={handleUpgradeBalance}
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-green-600 hover:brightness-110 text-white font-black py-4 rounded-2xl shadow-xl shadow-emerald-500/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
                  >
                    {loading ? (
                      'Memproses Aktivasi...'
                    ) : (
                      <>
                        <span>✓</span> Klaim Upgrade VIP Gratis Sekarang
                      </>
                    )}
                  </button>
                </div>
              ) : (
                /* ─── TAMPILAN BERBAYAR (STANDAR) ─── */
                <>
                  <div className="space-y-2">
                    <button
                      type="button"
                      onClick={() => setMethod('balance')}
                      disabled={userBalance < fee}
                      className={`w-full flex items-center justify-between p-3.5 rounded-2xl border-2 transition cursor-pointer ${method === 'balance' ? 'border-amber-500 bg-amber-500/10' : 'border-gray-800 bg-gray-800/40 hover:border-gray-700'} ${userBalance < fee ? 'opacity-40 cursor-not-allowed' : ''}`}
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center">
                          <Icon.Wallet className="w-4 h-4" />
                        </div>
                        <div className="text-left">
                          <p className="text-xs font-black text-white">Potong Saldo</p>
                          <p className="text-[11px] text-gray-400">Saldo kamu: {fmt.rp(userBalance)}</p>
                        </div>
                      </div>
                      {userBalance >= fee ? (
                        <span className="text-[11px] font-black bg-green-500/20 text-green-400 px-2 py-0.5 rounded-full">Tersedia</span>
                      ) : (
                        <span className="text-[11px] font-black bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full">Kurang</span>
                      )}
                    </button>

                    <button
                      type="button"
                      onClick={() => setMethod('qris')}
                      className={`w-full flex items-center justify-between p-3.5 rounded-2xl border-2 transition cursor-pointer ${method === 'qris' ? 'border-amber-500 bg-amber-500/10' : 'border-gray-800 bg-gray-800/40 hover:border-gray-700'}`}
                    >
                      <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                          <Icon.Scan className="w-4 h-4" />
                        </div>
                        <div className="text-left">
                          <p className="text-xs font-black text-white">Bayar QRIS Instan</p>
                          <p className="text-[11px] text-gray-400">Semua e-wallet &amp; mobile banking</p>
                        </div>
                      </div>
                      <span className="text-[11px] font-black bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">Otomatis</span>
                    </button>
                  </div>

                  <button
                    type="button"
                    onClick={method === 'balance' ? handleUpgradeBalance : handleUpgradeQris}
                    disabled={loading}
                    className="w-full bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black py-4 rounded-2xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 mt-3"
                  >
                    {loading ? 'Menyiapkan QRIS...' : method === 'balance' ? `Bayar ${fmt.rp(fee)} dari Saldo` : 'Tampilkan QRIS Pembayaran →'}
                  </button>
                </>
              )}
            </div>
          )}

          {step === 'qris' && (
            <div className="text-center">
              <p className="text-xs text-gray-400 font-medium uppercase mb-1">Total Pembayaran</p>
              <p className="text-3xl font-black text-amber-400 mb-2">{fmt.rp(qrisData?.total_nominal || 20000)}</p>
              <p className="text-[11px] font-bold text-amber-400 mb-3">
                ⏳ Batas Waktu (20 Menit): {String(Math.floor(timeLeft / 60)).padStart(2, '0')}:{String(timeLeft % 60).padStart(2, '0')}
              </p>
              
              <div className="bg-white p-3 rounded-2xl inline-block shadow-lg mb-4 relative min-w-[192px] min-h-[192px]">
                {!imgLoaded && (
                  <div className="w-48 h-48 flex flex-col items-center justify-center text-gray-500 text-xs font-bold animate-pulse">
                    <div className="w-9 h-9 rounded-full border-4 border-amber-200 border-t-amber-500 animate-spin mb-2"></div>
                    <span>Menyiapkan QRIS...</span>
                  </div>
                )}
                {qrisData?.url_qris && (
                  <img
                    src={qrisData.url_qris}
                    alt="QRIS Upgrade"
                    onLoad={() => setImgLoaded(true)}
                    onError={() => setImgLoaded(true)}
                    className={`w-48 h-48 object-contain transition-opacity duration-300 ${imgLoaded ? 'opacity-100' : 'opacity-0 absolute inset-3'}`}
                  />
                )}
              </div>

              <button
                type="button"
                onClick={() => checkStatusOnce(qrisData?.order_id, true)}
                disabled={checking || cooldown > 0}
                className="w-full py-3 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 font-black text-xs shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed mb-2.5"
              >
                {checking ? 'Mengecek Pembayaran...' : cooldown > 0 ? `⏱️ Cek Pembayaran (${cooldown}s)` : '🔄 Sudah Bayar? Cek Pembayaran Sekarang'}
              </button>
              {checkMsg && <p className="text-[11px] font-bold text-amber-300 mb-2">{checkMsg}</p>}

              <div className="flex items-center justify-center gap-2 text-amber-400 font-bold text-xs bg-amber-500/10 py-2 rounded-xl border border-amber-500/20 mb-3 animate-pulse">
                <div className="w-2 h-2 rounded-full bg-amber-400"></div>Menunggu pembayaran otomatis...
              </div>
              <p className="text-[11px] text-gray-400 mb-2">ID: <span className="font-mono text-gray-300">{qrisData?.order_id}</span></p>
              <button onClick={onClose} className="text-xs text-gray-500 hover:text-gray-300 font-bold transition cursor-pointer">Tutup (bayar nanti)</button>
            </div>
          )}

          {step === 'done' && (
            <div className="text-center py-6">
              <div className="w-20 h-20 bg-green-500/20 text-green-400 border border-green-500/30 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce">
                <Icon.Check className="w-10 h-10" />
              </div>
              <h3 className="text-xl font-black text-white mb-2">Selamat! VIP Reseller Aktif</h3>
              <p className="text-xs text-gray-300 font-medium">Akun Anda berhasil di-upgrade. Halaman akan dimuat ulang...</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
