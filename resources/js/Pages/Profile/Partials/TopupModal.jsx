import React, { useState, useRef, useEffect } from 'react';
import { Icon, fmt } from './ProfileIcons';

export default function TopupModal({ onClose, onSuccess }) {
  const [amount, setAmount] = useState('');
  const [step, setStep] = useState('form'); // form | qris | done
  const [qrisUrl, setQrisUrl] = useState('');
  const [orderId, setOrderId] = useState('');
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [imgLoaded, setImgLoaded] = useState(false);
  const [timeLeft, setTimeLeft] = useState(1200);
  const [checking, setChecking] = useState(false);
  const [cooldown, setCooldown] = useState(0);
  const [checkMsg, setCheckMsg] = useState('');
  const pollingRef = useRef(null);

  const presets = [10000, 25000, 50000, 100000, 200000, 500000];

  useEffect(() => {
    if (cooldown <= 0) return;
    const t = setInterval(() => setCooldown((prev) => (prev > 0 ? prev - 1 : 0)), 1000);
    return () => clearInterval(t);
  }, [cooldown]);

  const checkStatusOnce = async (oid, isManual = false) => {
    if (!oid || (isManual && cooldown > 0)) return;
    if (isManual) { setChecking(true); setCooldown(100); setCheckMsg(''); }
    try {
      const r = await fetch(`/topup/status/${oid}`);
      const d = await r.json();
      if (d.status === 'paid' || d.status === 'PAID') {
        clearInterval(pollingRef.current);
        setStep('done');
        onSuccess?.();
      } else if (isManual) {
        setCheckMsg('Pembayaran belum terdeteksi. Pastikan nominal unik sesuai.');
      }
    } catch {
      if (isManual) setCheckMsg('Gagal mengecek status pembayaran.');
    } finally {
      if (isManual) setChecking(false);
    }
  };

  const startPolling = (oid) => {
    pollingRef.current = setInterval(() => {
      checkStatusOnce(oid, false);
    }, 3000);
  };

  useEffect(() => {
    if (step !== 'qris' || timeLeft <= 0) return;
    const t = setInterval(() => setTimeLeft(prev => Math.max(0, prev - 1)), 1000);
    return () => clearInterval(t);
  }, [step, timeLeft]);

  useEffect(() => () => clearInterval(pollingRef.current), []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    const num = parseInt(amount);
    if (!num || num < 10000) { setError('Minimal top up Rp 10.000'); return; }
    setError(''); setLoading(true);
    try {
      const r = await fetch('/topup/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
        body: JSON.stringify({ amount: num }),
      });
      const d = await r.json();
      if (d.status === 'success') {
        setImgLoaded(false);
        setTimeLeft(1200);
        setQrisUrl(d.url_qris); setOrderId(d.order_id); setTotal(d.total_nominal);
        setStep('qris'); startPolling(d.order_id);
      } else {
        setError(d.message || 'Gagal membuat tagihan.');
      }
    } catch { setError('Gagal terhubung ke server.'); }
    setLoading(false);
  };

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="w-full max-w-sm bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-y-auto max-h-[92dvh] border border-gray-100 dark:border-gray-700">
        <div className="bg-gradient-to-br from-[#0b1121] to-[#1e3a5f] p-5 flex items-center justify-between">
          <div>
            <h3 className="text-white font-black text-lg">Top Up Saldo</h3>
            <p className="text-blue-300 text-xs font-medium mt-0.5">Bayar via QRIS otomatis (20 Menit)</p>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer" aria-label="Tutup Modal Top Up">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-5">
          {step === 'form' && (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2 block">Nominal Top Up</label>
                <div className="grid grid-cols-3 gap-2 mb-3">
                  {presets.map(p => (
                    <button key={p} type="button" onClick={() => setAmount(String(p))} className={`py-2 rounded-xl text-xs font-black border transition cursor-pointer ${amount === String(p) ? 'bg-blue-500 text-white border-blue-500' : 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-blue-300'}`}>
                      {fmt.rp(p).replace('Rp ', '')}
                    </button>
                  ))}
                </div>
                <input type="number" value={amount} onChange={e => setAmount(e.target.value)} placeholder="Atau ketik nominal lain..." className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-blue-500 outline-none" min="10000" />
              </div>
              {error && <p className="text-red-500 text-xs font-bold">{error}</p>}
              {amount > 0 && <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl p-3 flex justify-between items-center"><span className="text-xs font-bold text-blue-700 dark:text-blue-300">Total Top Up</span><span className="font-black text-blue-700 dark:text-blue-300">{fmt.rp(amount)}</span></div>}
              <button type="submit" disabled={loading} className="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-3.5 rounded-2xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                {loading ? <><svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Menyiapkan QRIS...</> : <><Icon.Scan className="w-4 h-4" />Bayar via QRIS</>}
              </button>
            </form>
          )}

          {step === 'qris' && (
            <div className="text-center">
              <p className="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Total Dibayar</p>
              <p className="text-3xl font-black text-gray-900 dark:text-white mb-2">{fmt.rp(total)}</p>
              <p className="text-[11px] font-bold text-amber-500 mb-3">
                ⏳ Batas Waktu (20 Menit): {String(Math.floor(timeLeft / 60)).padStart(2, '0')}:{String(timeLeft % 60).padStart(2, '0')}
              </p>
              <div className="bg-white dark:bg-gray-800 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-2xl p-3 inline-block mb-4 relative min-w-[208px] min-h-[208px]">
                {!imgLoaded && (
                  <div className="w-52 h-52 bg-gray-100 dark:bg-gray-700 rounded-xl flex flex-col items-center justify-center text-gray-500 dark:text-gray-300 text-xs font-bold animate-pulse">
                    <div className="w-9 h-9 rounded-full border-4 border-blue-200 border-t-blue-600 animate-spin mb-2"></div>
                    <span>Menyiapkan QRIS...</span>
                  </div>
                )}
                {qrisUrl && (
                  <img
                    src={qrisUrl}
                    alt="QRIS"
                    onLoad={() => setImgLoaded(true)}
                    onError={() => setImgLoaded(true)}
                    className={`w-52 h-52 object-contain transition-opacity duration-300 ${imgLoaded ? 'opacity-100' : 'opacity-0 absolute inset-3'}`}
                  />
                )}
              </div>
              <button
                type="button"
                onClick={() => checkStatusOnce(orderId, true)}
                disabled={checking || cooldown > 0}
                className="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed mb-2.5"
              >
                {checking ? 'Mengecek Pembayaran...' : cooldown > 0 ? `⏱️ Cek Pembayaran (${cooldown}s)` : '🔄 Sudah Bayar? Cek Pembayaran Sekarang'}
              </button>
              {checkMsg && <p className="text-[11px] font-bold text-amber-600 dark:text-amber-400 mb-2">{checkMsg}</p>}
              <div className="flex items-center justify-center gap-2 text-blue-500 font-bold text-xs animate-pulse bg-blue-50 dark:bg-blue-900/20 py-2 rounded-xl mb-3">
                <div className="w-2 h-2 rounded-full bg-blue-500"></div>Menunggu pembayaran otomatis...
              </div>
              <p className="text-[11px] text-gray-400 mb-3">ID: <span className="font-mono text-gray-600 dark:text-gray-300">{orderId}</span></p>
              <button onClick={onClose} className="text-gray-400 hover:text-red-500 text-xs font-bold transition cursor-pointer">Tutup (bayar nanti)</button>
            </div>
          )}

          {step === 'done' && (
            <div className="text-center py-4">
              <div className="w-20 h-20 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-100 dark:border-green-800">
                <Icon.Check className="w-10 h-10 text-green-500" />
              </div>
              <h3 className="text-xl font-black text-gray-900 dark:text-white mb-2">Top Up Berhasil!</h3>
              <p className="text-sm text-gray-500 dark:text-gray-300 mb-5">Saldo kamu bertambah <span className="font-black text-green-600">{fmt.rp(total)}</span></p>
              <button onClick={onClose} className="w-full bg-gray-900 text-white font-black py-3 rounded-2xl cursor-pointer">Selesai</button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
