import React, { useState, useRef, useEffect, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';

// ─── ICON COMPONENTS ──────────────────────────────────────────────────────────
const Icon = {
  Dashboard: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>,
  User: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>,
  History: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>,
  Wallet: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>,
  Lock: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>,
  Logout: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>,
  Plus: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>,
  Minus: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" /></svg>,
  Check: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" /></svg>,
  Home: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>,
  Eye: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>,
  EyeOff: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>,
  ChevronRight: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M9 5l7 7-7 7" /></svg>,
  Menu: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" /></svg>,
  X: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" /></svg>,
  Scan: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8H3m2 0V6m1 2h12M3 16h2m0 0v2m0-2h4m4 0h6m-6-8h.01M5 16h.01" /></svg>,
  Tag: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>,
  Sparkles: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>,
  ShieldCheck: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>,
  FileText: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>,
  Copy: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10a2 2 0 00-2 2v3a2 2 0 002 2h8a2 2 0 002-2v-3a2 2 0 00-2-2z" /></svg>,
  Search: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>,
  ArrowDownLeft: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M19 5L5 19M5 19h10M5 19V9" /></svg>,
  ArrowUpRight: (p) => <svg {...p} fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 19L19 5M19 5H9M19 5v10" /></svg>,
};

// ─── UTILS ────────────────────────────────────────────────────────────────────
const fmt = {
  rp: (n) => 'Rp ' + new Intl.NumberFormat('id-ID').format(n || 0),
  date: (d) => d ? new Date(d).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-',
};

const StatusBadge = ({ status }) => {
  const map = {
    PAID: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    SUCCESS: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    PENDING: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
    PROCESSING: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    FAILED: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    EXPIRED: 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
  };
  const labels = { PAID: 'Lunas', SUCCESS: 'Berhasil', PENDING: 'Menunggu', PROCESSING: 'Diproses', FAILED: 'Gagal', EXPIRED: 'Kadaluarsa' };
  const s = (status || '').toUpperCase();
  return <span className={`text-[10px] font-black px-2 py-0.5 rounded-full ${map[s] || 'bg-gray-100 text-gray-500'}`}>{labels[s] || s || '-'}</span>;
};

// ─── PAGINATION COMPONENT (PERSIS FOTO KEDUA) ───────────────────────────────
function Pagination({ currentPage, totalPages, onPageChange }) {
  if (totalPages <= 1) return null;

  const getPages = () => {
    const pages = [];
    if (totalPages <= 7) {
      for (let i = 1; i <= totalPages; i++) pages.push(i);
    } else {
      if (currentPage <= 3) {
        pages.push(1, 2, 3, 4, '...', totalPages);
      } else if (currentPage >= totalPages - 2) {
        pages.push(1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages);
      } else {
        pages.push(1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages);
      }
    }
    return pages;
  };

  return (
    <div className="flex justify-center mt-6">
      <div className="inline-flex items-center gap-1 sm:gap-1.5 p-1.5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700/80 shadow-sm text-xs font-bold text-gray-500 dark:text-gray-400">
        {/* Prev Button */}
        <button
          type="button"
          disabled={currentPage === 1}
          onClick={() => onPageChange(currentPage - 1)}
          className="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-xl hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
        >
          <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M15 19l-7-7 7-7" /></svg>
          <span>Prev</span>
        </button>

        {/* Page Numbers */}
        {getPages().map((p, idx) => {
          if (p === '...') {
            return (
              <span key={`dots-${idx}`} className="px-1.5 sm:px-2 text-gray-400 select-none">
                ...
              </span>
            );
          }
          const isActive = p === currentPage;
          return (
            <button
              key={`page-${p}`}
              type="button"
              onClick={() => onPageChange(p)}
              className={`min-w-[28px] sm:min-w-[32px] h-7 sm:h-8 px-2 sm:px-2.5 rounded-xl flex items-center justify-center font-black transition cursor-pointer ${
                isActive
                  ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-sm'
                  : 'hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/50 text-gray-600 dark:text-gray-400'
              }`}
            >
              {p}
            </button>
          );
        })}

        {/* Next Button */}
        <button
          type="button"
          disabled={currentPage === totalPages}
          onClick={() => onPageChange(currentPage + 1)}
          className="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 rounded-xl hover:text-gray-900 dark:hover:text-white disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
        >
          <span>Next</span>
          <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M9 5l7 7-7 7" /></svg>
        </button>
      </div>
    </div>
  );
}

// ─── TOPUP MODAL ──────────────────────────────────────────────────────────────
function TopupModal({ onClose, onSuccess }) {
  const [amount, setAmount] = useState('');
  const [step, setStep] = useState('form'); // form | qris | done
  const [qrisUrl, setQrisUrl] = useState('');
  const [orderId, setOrderId] = useState('');
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const pollingRef = useRef(null);

  const presets = [10000, 25000, 50000, 100000, 200000, 500000];

  const startPolling = (oid) => {
    pollingRef.current = setInterval(async () => {
      try {
        const r = await fetch(`/topup/status/${oid}`);
        const d = await r.json();
        if (d.status === 'paid' || d.status === 'PAID') {
          clearInterval(pollingRef.current);
          setStep('done');
          onSuccess?.();
        }
      } catch {}
    }, 3000);
  };

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
      <div className="w-full max-w-sm bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-gray-100 dark:border-gray-700">
        <div className="bg-gradient-to-br from-[#0b1121] to-[#1e3a5f] p-5 flex items-center justify-between">
          <div>
            <h3 className="text-white font-black text-lg">Top Up Saldo</h3>
            <p className="text-blue-300 text-xs font-medium mt-0.5">Bayar via QRIS otomatis</p>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-5">
          {step === 'form' && (
            <form onSubmit={handleSubmit} className="space-y-4">
              <div>
                <label className="text-xs font-black text-gray-500 uppercase tracking-wider mb-2 block">Nominal Top Up</label>
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
                {loading ? <><svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>Memproses...</> : <><Icon.Scan className="w-4 h-4" />Bayar via QRIS</>}
              </button>
            </form>
          )}

          {step === 'qris' && (
            <div className="text-center">
              <p className="text-xs font-black text-gray-400 uppercase tracking-wider mb-1">Total Dibayar</p>
              <p className="text-3xl font-black text-gray-900 dark:text-white mb-4">{fmt.rp(total)}</p>
              <div className="bg-white dark:bg-gray-800 border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-2xl p-3 inline-block mb-4">
                {qrisUrl ? <img src={qrisUrl} alt="QRIS" className="w-52 h-52 object-contain" /> : <div className="w-52 h-52 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center text-gray-400 text-xs font-bold">Memuat QRIS...</div>}
              </div>
              <div className="flex items-center justify-center gap-2 text-blue-500 font-bold text-xs animate-pulse bg-blue-50 dark:bg-blue-900/20 py-2.5 rounded-xl mb-3">
                <div className="w-2 h-2 rounded-full bg-blue-500"></div>Menunggu pembayaran...
              </div>
              <p className="text-[10px] text-gray-400 mb-3">ID: <span className="font-mono text-gray-600 dark:text-gray-400">{orderId}</span></p>
              <button onClick={onClose} className="text-gray-400 hover:text-red-500 text-xs font-bold transition cursor-pointer">Tutup (bayar nanti)</button>
            </div>
          )}

          {step === 'done' && (
            <div className="text-center py-4">
              <div className="w-20 h-20 bg-green-50 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-100 dark:border-green-800">
                <Icon.Check className="w-10 h-10 text-green-500" />
              </div>
              <h3 className="text-xl font-black text-gray-900 dark:text-white mb-2">Top Up Berhasil!</h3>
              <p className="text-sm text-gray-500 dark:text-gray-400 mb-5">Saldo kamu bertambah <span className="font-black text-green-600">{fmt.rp(total)}</span></p>
              <button onClick={onClose} className="w-full bg-gray-900 text-white font-black py-3 rounded-2xl cursor-pointer">Selesai</button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ─── UPGRADE RESELLER MODAL ───────────────────────────────────────────────────
function UpgradeResellerModal({ onClose, userBalance }) {
  const [method, setMethod] = useState(userBalance >= 20000 ? 'balance' : 'qris');
  const [step, setStep] = useState('choose'); // choose | qris | done
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [qrisData, setQrisData] = useState(null);
  const pollingRef = useRef(null);

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
        setQrisData(data);
        setStep('qris');
        startPolling(data.order_id);
      } else {
        setError(data.message || 'Gagal membuat QRIS.');
      }
    } catch { setError('Terjadi kesalahan server.'); }
    setLoading(false);
  };

  const startPolling = (orderId) => {
    pollingRef.current = setInterval(async () => {
      try {
        const res = await fetch(`/reseller/payment-status/${orderId}`);
        const data = await res.json();
        if (data.status === 'paid' || data.status === 'PAID') {
          clearInterval(pollingRef.current);
          setStep('done');
          setTimeout(() => window.location.reload(), 2000);
        }
      } catch {}
    }, 3000);
  };

  useEffect(() => () => clearInterval(pollingRef.current), []);

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/70 backdrop-blur-md" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="w-full max-w-sm bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-amber-500/40 text-white">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 p-5 flex items-center justify-between text-gray-950">
          <div>
            <span className="text-[10px] font-black uppercase tracking-widest bg-black/20 px-2 py-0.5 rounded-full inline-block mb-1">Aktivasi Seumur Hidup</span>
            <h3 className="font-black text-lg">Upgrade ke Reseller VIP</h3>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-black/20 flex items-center justify-center text-gray-950 hover:bg-black/30 transition cursor-pointer">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6">
          {step === 'choose' && (
            <div className="space-y-4">
              <div className="text-center pb-2">
                <p className="text-xs text-gray-400 font-bold uppercase">Biaya Upgrade</p>
                <p className="text-3xl font-black text-amber-400 mt-0.5">Rp 20.000</p>
                <p className="text-[11px] text-gray-400 mt-1 font-medium">Buka harga modal reseller termurah untuk semua produk!</p>
              </div>

              {error && <div className="bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold p-3 rounded-xl">{error}</div>}

              {/* Pilihan Metode */}
              <div className="space-y-2">
                <button
                  type="button"
                  onClick={() => setMethod('balance')}
                  disabled={userBalance < 20000}
                  className={`w-full flex items-center justify-between p-3.5 rounded-2xl border-2 transition cursor-pointer ${method === 'balance' ? 'border-amber-500 bg-amber-500/10' : 'border-gray-800 bg-gray-800/40 hover:border-gray-700'} ${userBalance < 20000 ? 'opacity-40 cursor-not-allowed' : ''}`}
                >
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-xl bg-green-500/20 text-green-400 flex items-center justify-center">
                      <Icon.Wallet className="w-4 h-4" />
                    </div>
                    <div className="text-left">
                      <p className="text-xs font-black text-white">Potong Saldo</p>
                      <p className="text-[10px] text-gray-400">Saldo kamu: {fmt.rp(userBalance)}</p>
                    </div>
                  </div>
                  {userBalance >= 20000 ? (
                    <span className="text-[9px] font-black bg-green-500/20 text-green-400 px-2 py-0.5 rounded-full">Tersedia</span>
                  ) : (
                    <span className="text-[9px] font-black bg-red-500/20 text-red-400 px-2 py-0.5 rounded-full">Kurang</span>
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
                      <p className="text-[10px] text-gray-400">Semua e-wallet & mobile banking</p>
                    </div>
                  </div>
                  <span className="text-[9px] font-black bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">Otomatis</span>
                </button>
              </div>

              <button
                type="button"
                onClick={method === 'balance' ? handleUpgradeBalance : handleUpgradeQris}
                disabled={loading}
                className="w-full bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black py-4 rounded-2xl shadow-lg transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 mt-3"
              >
                {loading ? 'Memproses...' : method === 'balance' ? 'Bayar Rp 20.000 dari Saldo' : 'Tampilkan QRIS Pembayaran →'}
              </button>
            </div>
          )}

          {step === 'qris' && (
            <div className="text-center">
              <p className="text-xs text-gray-400 font-bold uppercase mb-1">Total Pembayaran</p>
              <p className="text-3xl font-black text-amber-400 mb-4">{fmt.rp(qrisData?.total_nominal || 20000)}</p>
              
              <div className="bg-white p-3 rounded-2xl inline-block shadow-lg mb-4">
                {qrisData?.url_qris ? (
                  <img src={qrisData.url_qris} alt="QRIS Upgrade" className="w-48 h-48 object-contain" />
                ) : (
                  <div className="w-48 h-48 flex items-center justify-center text-gray-500 text-xs font-bold">Memuat QRIS...</div>
                )}
              </div>

              <div className="flex items-center justify-center gap-2 text-amber-400 font-bold text-xs bg-amber-500/10 py-2.5 rounded-xl border border-amber-500/20 mb-3 animate-pulse">
                <div className="w-2 h-2 rounded-full bg-amber-400"></div>Menunggu pembayaran...
              </div>
              <p className="text-[10px] text-gray-400 mb-2">ID: <span className="font-mono text-gray-300">{qrisData?.order_id}</span></p>
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

// ─── RESET PASSWORD SECTION ───────────────────────────────────────────────────
function ResetPasswordSection({ flash }) {
  const [currentPassword, setCurrentPassword] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPass, setShowPass] = useState({ cur: false, new: false, con: false });
  const [localSuccess, setLocalSuccess] = useState('');
  const [localError, setLocalError] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    setLoading(true); setLocalSuccess(''); setLocalError('');
    router.post('/profile/reset-password', {
      current_password: currentPassword, password, password_confirmation: passwordConfirmation,
    }, {
      onSuccess: () => { setLocalSuccess('Password berhasil diubah!'); setCurrentPassword(''); setPassword(''); setPasswordConfirmation(''); },
      onError: (errs) => { setLocalError(Object.values(errs)[0] || 'Terjadi kesalahan.'); },
      onFinish: () => setLoading(false),
    });
  };

  const inp = (label, val, setVal, key) => (
    <div>
      <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">{label}</label>
      <div className="relative">
        <input type={showPass[key] ? 'text' : 'password'} value={val} onChange={e => setVal(e.target.value)} className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none pr-11 transition" required />
        <button type="button" onClick={() => setShowPass(p => ({ ...p, [key]: !p[key] }))} className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer">
          {showPass[key] ? <Icon.EyeOff className="w-4 h-4" /> : <Icon.Eye className="w-4 h-4" />}
        </button>
      </div>
    </div>
  );

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {localSuccess && <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm font-bold p-3 rounded-2xl">{localSuccess}</div>}
      {localError && <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-bold p-3 rounded-2xl">{localError}</div>}
      {inp('Password Saat Ini', currentPassword, setCurrentPassword, 'cur')}
      {inp('Password Baru', password, setPassword, 'new')}
      {inp('Konfirmasi Password Baru', passwordConfirmation, setPasswordConfirmation, 'con')}
      <button type="submit" disabled={loading} className="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-orange-500/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
        {loading ? <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> : <Icon.Lock className="w-4 h-4" />}
        {loading ? 'Menyimpan...' : 'Simpan Password Baru'}
      </button>
    </form>
  );
}

// ─── GANTI NOMOR WHATSAPP MODAL ───────────────────────────────────────────────
function ChangeWaModal({ onClose, currentWhatsapp, onNumberChanged }) {
  const [newWhatsapp, setNewWhatsapp] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const getCsrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
  };

  const handleSaveAndVerify = async () => {
    if (!newWhatsapp || newWhatsapp.trim().length < 8) {
      setError('Masukkan nomor WhatsApp yang valid (contoh: 08123456789)');
      return;
    }
    setError('');
    setLoading(true);
    try {
      const res = await fetch('/profile/change-whatsapp', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({ whatsapp: newWhatsapp }),
      });
      let data = {};
      try {
        data = await res.json();
      } catch {}
      if (res.ok && data.status === 'success') {
        onNumberChanged?.(data);
      } else {
        setError(data.message || data.error || (data.errors ? Object.values(data.errors)[0]?.[0] : null) || 'Gagal mengubah nomor WhatsApp.');
      }
    } catch (err) {
      console.error(err);
      setError('Gagal menghubungi server. Silakan coba lagi.');
    }
    setLoading(false);
  };

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/75 backdrop-blur-md" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-orange-500/30 text-gray-900 dark:text-white animate-fade-in-up">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 p-5 flex items-center justify-between text-white">
          <div className="flex items-center gap-2.5">
            <div className="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center font-black text-xl shadow-inner">
              📱
            </div>
            <div>
              <h3 className="font-black text-lg">Ganti Nomor WhatsApp</h3>
              <p className="text-orange-100 text-xs font-semibold">Perbarui nomor aktif akun NandarStore Anda</p>
            </div>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6 space-y-4">
          <div className="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
            <span className="text-gray-500 dark:text-gray-400 font-bold">Nomor Tersimpan Saat Ini:</span>
            <span className="font-mono font-black text-gray-800 dark:text-gray-200">
              {currentWhatsapp || '(Belum diatur)'}
            </span>
          </div>

          <div className="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 rounded-2xl">
            <p className="text-xs text-amber-800 dark:text-amber-200 font-semibold leading-relaxed">
              💡 <b>Catatan:</b> Setelah nomor diubah, Anda dapat langsung mengirim chat verifikasi 1-klik ke Bot WhatsApp agar pengiriman akun dan invoice otomatis langsung terhubung ke nomor baru Anda.
            </p>
          </div>

          <div>
            <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">
              Nomor WhatsApp Baru
            </label>
            <input
              type="text"
              value={newWhatsapp}
              onChange={(e) => setNewWhatsapp(e.target.value)}
              placeholder="Contoh: 08123456789 atau 628123456789"
              className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none"
              autoFocus
            />
            <p className="text-[11px] text-gray-400 mt-1 font-medium">Format: 08xxx atau 628xxx (9-14 digit angka).</p>
          </div>

          {error && <p className="text-red-500 text-xs font-bold text-center">{error}</p>}

          <div className="pt-2 flex flex-col gap-2.5">
            <button
              type="button"
              onClick={handleSaveAndVerify}
              disabled={loading}
              className="w-full bg-gradient-to-r from-orange-500 to-amber-600 hover:brightness-110 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-orange-500/25 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 text-sm"
            >
              {loading ? (
                <>
                  <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                  <span>Menyimpan...</span>
                </>
              ) : (
                <>
                  <span>Simpan &amp; Verifikasi Baru</span>
                  <span>→</span>
                </>
              )}
            </button>
            <button
              type="button"
              onClick={onClose}
              className="w-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold py-2.5 rounded-2xl transition cursor-pointer text-xs"
            >
              Batal
            </button>
          </div>
        </div>

      </div>
    </div>
  );
}

// ─── WA VERIFICATION MODAL ────────────────────────────────────────────────────
function WaVerificationModal({ onClose, userWhatsapp, onVerified, initialData = null, onOpenChangeModal }) {
  const [whatsapp, setWhatsapp] = useState(initialData?.whatsapp || userWhatsapp || '');
  const [step, setStep] = useState(initialData ? 'waiting' : 'ready'); // ready | waiting | confirmed
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [statusNotice, setStatusNotice] = useState('');
  const [verificationData, setVerificationData] = useState(initialData || null);
  const [copied, setCopied] = useState(false);
  const pollingRef = useRef(null);

  const getCsrf = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const cookieMatch = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return cookieMatch ? decodeURIComponent(cookieMatch[1]) : '';
  };

  const handleRequestCode = async (phoneToUse) => {
    const phone = phoneToUse || whatsapp;
    if (!phone || phone.trim().length < 8) {
      setError('Masukkan nomor WhatsApp yang valid (contoh: 08123456789)');
      return;
    }
    setError('');
    setStatusNotice('');
    setLoading(true);
    try {
      const res = await fetch('/profile/wa-verify/request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrf(),
        },
        body: JSON.stringify({ whatsapp: phone }),
      });
      let data = {};
      try {
        data = await res.json();
      } catch {}
      if (res.ok && data.status === 'success') {
        setVerificationData(data);
        setStep('waiting');
        startPolling();
      } else {
        setError(data.message || data.error || (data.errors ? Object.values(data.errors)[0]?.[0] : null) || 'Gagal membuat kode verifikasi.');
      }
    } catch (err) {
      console.error(err);
      setError('Gagal menghubungi server. Silakan coba lagi.');
    }
    setLoading(false);
  };

  const startPolling = () => {
    if (pollingRef.current) clearInterval(pollingRef.current);
    pollingRef.current = setInterval(async () => {
      try {
        const res = await fetch('/profile/wa-verify/status');
        const data = await res.json();
        if (data.is_verified) {
          clearInterval(pollingRef.current);
          setStep('confirmed');
          onVerified?.();
        }
      } catch {}
    }, 3000);
  };

  useEffect(() => {
    if (initialData) {
      setVerificationData(initialData);
      setWhatsapp(initialData.whatsapp || '');
      setStep('waiting');
      startPolling();
    }
    return () => {
      if (pollingRef.current) clearInterval(pollingRef.current);
    };
  }, [initialData]);

  const handleManualCheckStatus = async () => {
    setLoading(true);
    setError('');
    setStatusNotice('');
    try {
      const res = await fetch('/profile/wa-verify/status');
      const data = await res.json();
      if (data.is_verified) {
        if (pollingRef.current) clearInterval(pollingRef.current);
        setStep('confirmed');
        onVerified?.();
      } else {
        setStatusNotice('⚠️ Pesan verifikasi belum terdeteksi oleh WhatsApp Bot. Pastikan Anda sudah mengirimkan chat berisi kode unik ke nomor Bot (08990738216) dan mohon tunggu beberapa detik.');
      }
    } catch {
      setError('Gagal menghubungi server.');
    }
    setLoading(false);
  };

  const handleCopyCode = () => {
    if (verificationData?.code) {
      navigator.clipboard.writeText(verificationData.code);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    }
  };

  return (
    <div className="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/75 backdrop-blur-md" onClick={(e) => e.target === e.currentTarget && onClose()}>
      <div className="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-hidden border border-emerald-500/30 text-gray-900 dark:text-white">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-emerald-600 to-green-600 p-5 flex items-center justify-between text-white">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center font-black text-xl">
              📲
            </div>
            <div>
              <h3 className="font-black text-lg">Verifikasi Nomor WhatsApp</h3>
              <p className="text-green-100 text-xs font-semibold">Notifikasi & pengiriman akun otomatis</p>
            </div>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6">
          {step === 'ready' && (
            <div className="space-y-4">
              <div className="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-800/40 rounded-2xl">
                <p className="text-xs text-emerald-800 dark:text-emerald-200 font-semibold leading-relaxed">
                  Verifikasi nomor WhatsApp diperlukan agar sistem kami dapat mengirimkan <span className="font-extrabold">rincian akun (Email & Password), link invoice resmi, dan status pesanan otomatis</span> langsung ke WhatsApp Anda.
                </p>
              </div>

              <div>
                <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">Nomor WhatsApp Anda</label>
                <input
                  type="text"
                  value={whatsapp}
                  onChange={(e) => setWhatsapp(e.target.value)}
                  placeholder="Contoh: 08123456789"
                  className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none"
                />
              </div>

              {error && <p className="text-red-500 text-xs font-bold">{error}</p>}

              <button
                type="button"
                onClick={() => handleRequestCode()}
                disabled={loading}
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-600/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60"
              >
                {loading ? 'Memproses...' : 'Dapatkan Kode Verifikasi →'}
              </button>
            </div>
          )}

          {step === 'waiting' && verificationData && (
            <div className="space-y-4">
              <div className="flex items-center justify-between px-1 text-xs">
                <span className="text-gray-500 dark:text-gray-400 font-bold">
                  Nomor: <span className="font-mono font-bold text-gray-900 dark:text-white">{verificationData.whatsapp || whatsapp}</span>
                </span>
                <button
                  type="button"
                  onClick={() => {
                    if (pollingRef.current) clearInterval(pollingRef.current);
                    onClose();
                    onOpenChangeModal?.();
                  }}
                  className="text-orange-500 hover:text-orange-600 hover:underline font-bold text-[11px] cursor-pointer"
                >
                  ✏️ Ganti Nomor
                </button>
              </div>

              <div className="text-center">
                <span className="text-[11px] font-black uppercase tracking-wider text-gray-400">Kode Unik Verifikasi Anda:</span>
                <div className="mt-2 flex items-center justify-center gap-2">
                  <div className="px-5 py-2.5 bg-gray-900 dark:bg-black border-2 border-emerald-500 rounded-2xl font-mono text-2xl font-black text-emerald-400 tracking-wider shadow-inner">
                    {verificationData.code}
                  </div>
                  <button
                    type="button"
                    onClick={handleCopyCode}
                    className="p-3 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-2xl transition cursor-pointer text-gray-600 dark:text-gray-300"
                    title="Salin Kode"
                  >
                    <Icon.Copy className="w-5 h-5" />
                  </button>
                </div>
                {copied && <span className="text-[11px] font-bold text-emerald-500 mt-1 inline-block">✓ Kode disalin!</span>}
              </div>

              {/* Instruksi Langkah */}
              <div className="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-200 dark:border-gray-700/60 space-y-2.5 text-xs">
                <div className="flex items-start gap-2.5">
                  <span className="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-black flex items-center justify-center shrink-0">1</span>
                  <p className="font-semibold text-gray-700 dark:text-gray-300">
                    Klik tombol hijau di bawah untuk membuka WhatsApp Bot (08990738216) dengan pesan otomatis berisi kode unik ini.
                  </p>
                </div>
                <div className="flex items-start gap-2.5">
                  <span className="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-black flex items-center justify-center shrink-0">2</span>
                  <p className="font-semibold text-gray-700 dark:text-gray-300">
                    Kirimkan pesan tersebut. Bot WhatsApp NandarStore akan otomatis mendeteksi kode verifikasi dan mengonfirmasi akun Anda secara instan!
                  </p>
                </div>
              </div>

              {/* Status Box Menunggu Validasi Bot */}
              <div className="p-3.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-2xl text-center space-y-1">
                <div className="flex items-center justify-center gap-2 text-amber-600 dark:text-amber-400 font-extrabold text-xs">
                  <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                  Menunggu Pesan Terkirim ke Bot WhatsApp...
                </div>
                <p className="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                  Status akan otomatis terverifikasi begitu bot mendeteksi chat masuk berisi kode unik Anda.
                </p>
              </div>

              {statusNotice && (
                <div className="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 rounded-xl text-xs font-semibold text-red-600 dark:text-red-400 text-center">
                  {statusNotice}
                </div>
              )}

              {error && <p className="text-red-500 text-xs font-bold text-center">{error}</p>}

              {/* Tombol Kirim ke WA Bot */}
              <a
                href={verificationData.wa_url}
                target="_blank"
                rel="noopener noreferrer"
                className="w-full bg-[#25D366] hover:bg-[#20ba5a] text-white font-black py-3.5 rounded-2xl shadow-lg shadow-green-600/20 transition flex items-center justify-center gap-2 cursor-pointer text-sm"
              >
                <span>📲</span> Kirim Pesan ke WhatsApp Bot (08990738216)
              </a>

              {/* Tombol Cek Status Manual */}
              <button
                type="button"
                onClick={handleManualCheckStatus}
                disabled={loading}
                className="w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold py-3 rounded-2xl transition flex items-center justify-center gap-2 cursor-pointer text-xs disabled:opacity-60"
              >
                {loading ? 'Memeriksa...' : '🔄 Cek Status Validasi Sekarang'}
              </button>
            </div>
          )}

          {step === 'confirmed' && (
            <div className="text-center py-4">
              <div className="w-16 h-16 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-500 border border-emerald-200 dark:border-emerald-800 rounded-full flex items-center justify-center mx-auto mb-3 animate-bounce">
                <Icon.Check className="w-8 h-8" />
              </div>
              <h4 className="text-lg font-black text-gray-900 dark:text-white mb-1">WhatsApp Berhasil Diverifikasi!</h4>
              <p className="text-xs text-gray-500 dark:text-gray-400 font-medium mb-5">
                Nomor Anda telah terkonfirmasi. Mulai sekarang rincian pesanan dan invoice akan otomatis dikirimkan ke WhatsApp Anda.
              </p>
              <button
                type="button"
                onClick={() => { onClose(); window.location.reload(); }}
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-2xl cursor-pointer text-xs"
              >
                Selesai & Muat Ulang
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

// ─── EDIT PROFILE SECTION ─────────────────────────────────────────────────────
function EditProfileSection({ user, onOpenWaModal, onOpenChangeWaModal }) {
  const [form, setForm] = useState({ name: user?.name || '', email: user?.email || '', whatsapp: user?.whatsapp || '' });
  const [loading, setLoading] = useState(false);
  const [localSuccess, setLocalSuccess] = useState('');
  const [localError, setLocalError] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    setLoading(true); setLocalSuccess(''); setLocalError('');
    router.post('/profile/update', form, {
      onSuccess: () => setLocalSuccess('Profil berhasil diperbarui!'),
      onError: (errs) => setLocalError(Object.values(errs)[0] || 'Gagal memperbarui profil.'),
      onFinish: () => setLoading(false),
    });
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      {localSuccess && <div className="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm font-bold p-3 rounded-2xl">{localSuccess}</div>}
      {localError && <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 text-sm font-bold p-3 rounded-2xl">{localError}</div>}
      
      <div>
        <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">Nama Lengkap</label>
        <input type="text" value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" required />
      </div>
      
      <div>
        <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 block">Email</label>
        <input type="email" value={form.email} onChange={e => setForm(p => ({ ...p, email: e.target.value }))} className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" required />
      </div>
      
      <div>
        <div className="flex items-center justify-between mb-2">
          <label className="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. WhatsApp</label>
          <div className="flex items-center gap-2">
            {user?.is_whatsapp_verified ? (
              <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                ✅ Terverifikasi
              </span>
            ) : (
              <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700">
                ⚠️ Belum Terverifikasi
              </span>
            )}
            <button
              type="button"
              onClick={() => onOpenChangeWaModal?.()}
              className="text-[10px] font-bold text-orange-500 hover:text-orange-600 hover:underline cursor-pointer flex items-center gap-1"
            >
              <span>✏️</span> Ganti Nomor
            </button>
          </div>
        </div>
        <input type="text" value={form.whatsapp} onChange={e => setForm(p => ({ ...p, whatsapp: e.target.value }))} className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition" placeholder="Contoh: 08123456789" />

        {/* WhatsApp Verification Banner */}
        {user?.is_whatsapp_verified ? (
          <div className="mt-2.5 p-3.5 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/60 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-start gap-3">
              <span className="text-xl">✅</span>
              <div>
                <p className="text-xs font-black text-emerald-800 dark:text-emerald-300">Nomor WhatsApp Terverifikasi ({user?.whatsapp})</p>
                <p className="text-[10px] text-emerald-700/80 dark:text-emerald-400/80 font-medium">Notifikasi pesanan dan invoice resmi otomatis aktif dikirim ke nomor ini.</p>
              </div>
            </div>
            <button
              type="button"
              onClick={() => onOpenChangeWaModal?.()}
              className="px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 font-extrabold text-xs rounded-xl shadow-sm transition cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
            >
              <span>✏️</span> Ganti Nomor WA
            </button>
          </div>
        ) : (
          <div className="mt-2.5 p-3.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div className="flex items-start gap-2.5">
              <span className="text-xl">📲</span>
              <div>
                <p className="text-xs font-black text-amber-800 dark:text-amber-300">Verifikasi Nomor WhatsApp</p>
                <p className="text-[10px] text-amber-700/80 dark:text-amber-400/80 font-medium">Verifikasi nomor Anda agar detail akun, password, dan invoice otomatis dikirim via WhatsApp!</p>
              </div>
            </div>
            <button
              type="button"
              onClick={() => onOpenWaModal?.(form.whatsapp)}
              className="px-4 py-2 bg-gradient-to-r from-emerald-600 to-green-600 hover:brightness-110 text-white font-black text-xs rounded-xl shadow transition cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
            >
              <span>📲</span> Verifikasi Sekarang
            </button>
          </div>
        )}
      </div>

      <button type="submit" disabled={loading} className="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-3.5 rounded-2xl shadow-lg shadow-orange-500/20 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
        {loading ? <svg className="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> : <Icon.User className="w-4 h-4" />}
        {loading ? 'Menyimpan...' : 'Simpan Perubahan'}
      </button>
    </form>
  );
}

// ─── MAIN PROFILE PAGE ────────────────────────────────────────────────────────
export default function Profile({ orders = [], topups = [], mutations = [], stats = null }) {
  const { auth, flash, url } = usePage().props;
  const user = auth?.user;
  const isReseller = user?.role === 'reseller';

  const allMutations = mutations && mutations.length > 0 ? mutations : topups;

  // Akumulasi Total Pemasukan & Pengeluaran Sejak Pertama Kali Daftar
  const totalIncomeCalc = useMemo(() => {
    if (stats?.total_income !== undefined && stats?.total_income !== null) return Number(stats.total_income);
    return allMutations
      .filter(m => m.type === 'income' && m.status === 'success')
      .reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
  }, [allMutations, stats]);

  const totalExpenseCalc = useMemo(() => {
    if (stats?.total_expense !== undefined && stats?.total_expense !== null) return Number(stats.total_expense);
    return allMutations
      .filter(m => m.type === 'expense' && m.status === 'success')
      .reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
  }, [allMutations, stats]);

  const totalIncomeCount = stats?.income_count ?? allMutations.filter(m => m.type === 'income' && m.status === 'success').length;
  const totalExpenseCount = stats?.expense_count ?? allMutations.filter(m => m.type === 'expense' && m.status === 'success').length;

  const [activeMenu, setActiveMenu] = useState(() => {
    if (typeof window !== 'undefined') {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if (tabParam && ['dashboard', 'orders', 'topup', 'security', 'settings'].includes(tabParam)) {
        return tabParam;
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
  }, [orderSearchQuery]);

  useEffect(() => {
    setMutationPage(1);
  }, [mutationFilter]);

  const filteredOrders = orders.filter((o) => {
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
          'Accept': 'application/json',
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
        if (tab === 'history') setActiveMenu('orders');
        else if (['dashboard', 'profile', 'orders', 'topup', 'security'].includes(tab)) {
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
      document.documentElement.classList.add('dark'); setIsDark(true);
    }
  }, []);

  const toggleDark = () => {
    if (isDark) { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); setIsDark(false); }
    else { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); setIsDark(true); }
  };

  const menus = [
    { key: 'dashboard', label: 'Dashboard',             icon: Icon.Dashboard },
    { key: 'orders',   label: 'Riwayat Pembelian',      icon: Icon.History, count: orders.length },
    { key: 'topup',    label: 'Riwayat Saldo & Top Up',  icon: Icon.Wallet,  count: allMutations.length },
    { key: 'profile',  label: 'Edit Profil',            icon: Icon.User     },
    { key: 'security', label: 'Ubah Password',          icon: Icon.Lock     },
  ];

  const handleMenuClick = (key) => { 
    setActiveMenu(key); 
    setSidebarOpen(false); 
    const targetUrl = key === 'dashboard' ? '/profile' : `/profile?tab=${key}`;
    router.visit(targetUrl, { preserveState: true, preserveScroll: true, replace: true });
  };

  // Sidebar content
  const SidebarContent = () => (
    <div className="flex flex-col h-fit">
      {/* Avatar & Info */}
      <div className="p-5 border-b border-gray-100 dark:border-gray-700/50">
        <div className="flex items-center gap-3">
          {isReseller ? (
            <div className="w-13 h-13 rounded-2xl bg-gradient-to-br from-amber-400 via-yellow-500 to-amber-600 text-gray-950 flex items-center justify-center font-black text-xl shadow-lg shadow-amber-500/20 shrink-0 relative border-2 border-yellow-300">
              {user?.name?.charAt(0)?.toUpperCase()}
              <span className="absolute -top-2 -right-2 text-sm">👑</span>
            </div>
          ) : (
            <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 text-white flex items-center justify-center font-black text-xl shadow-lg shrink-0">
              {user?.name?.charAt(0)?.toUpperCase()}
            </div>
          )}

          <div className="min-w-0">
            <p className="font-black text-gray-900 dark:text-white text-sm truncate flex items-center gap-1">
              {user?.name}
            </p>
            <p className="text-xs text-gray-400 truncate">{user?.email}</p>
            
            {isReseller ? (
              <span className="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 mt-1 inline-flex items-center gap-1 uppercase shadow-md shadow-amber-500/20">
                👑 VIP RESELLER
              </span>
            ) : (
              <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 mt-1 inline-block uppercase">
                {user?.role || 'user'}
              </span>
            )}
          </div>
        </div>

        {/* User Stats */}
        <div className="flex justify-between items-center mt-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700">
            <div className="text-center">
                <p className="text-[10px] font-bold text-gray-400 uppercase">Pembelian</p>
                <p className="font-black text-gray-900 dark:text-white text-sm">{orders.length}</p>
            </div>
            <div className="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>
            <div className="text-center">
                <p className="text-[10px] font-bold text-gray-400 uppercase">Bergabung</p>
                <p className="font-black text-gray-900 dark:text-white text-sm">{user?.created_at ? new Date(user.created_at).getFullYear() : '2026'}</p>
            </div>
        </div>

        {/* Ringkasan Akumulasi Saldo Sejak Daftar */}
        <div className="mt-3 p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-700/60 text-xs space-y-2">
          <p className="text-[10px] font-black text-gray-400 dark:text-gray-400 uppercase tracking-wider">
            Akumulasi Akun
          </p>
          <div className="flex justify-between items-center">
            <span className="text-[11px] font-bold text-gray-500 dark:text-gray-400 flex items-center gap-1">
              <span className="text-emerald-500 font-black">↓</span> Pemasukan
            </span>
            <span className="font-black text-emerald-600 dark:text-emerald-400 text-xs">
              +{fmt.rp(totalIncomeCalc)}
            </span>
          </div>
          <div className="flex justify-between items-center">
            <span className="text-[11px] font-bold text-gray-500 dark:text-gray-400 flex items-center gap-1">
              <span className="text-rose-500 font-black">↑</span> Pengeluaran
            </span>
            <span className="font-black text-rose-600 dark:text-rose-400 text-xs">
              -{fmt.rp(totalExpenseCalc)}
            </span>
          </div>
        </div>

        {/* WhatsApp Verified Badge in Sidebar */}
        <div className="mt-3 flex items-center justify-between p-2.5 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-700/60 text-xs">
          <div className="flex items-center gap-2 min-w-0">
            <span className="text-sm">📱</span>
            <div className="truncate">
              <p className="font-extrabold text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-wider">WhatsApp</p>
              <p className="font-bold text-[11px] text-gray-800 dark:text-gray-200 truncate">{user?.whatsapp || 'Belum diisi'}</p>
            </div>
          </div>
          <div className="flex items-center gap-1 shrink-0">
            {user?.is_whatsapp_verified ? (
              <span className="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                ✓ Verified
              </span>
            ) : (
              <button
                type="button"
                onClick={() => { setWaModalPhone(user?.whatsapp || ''); setShowWaModal(true); }}
                className="text-[9px] font-black px-2 py-0.5 rounded-full bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 cursor-pointer transition"
              >
                Verifikasi
              </button>
            )}
            <button
              type="button"
              onClick={() => setShowChangeWaModal(true)}
              className="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 cursor-pointer transition flex items-center gap-0.5"
              title="Ganti nomor WhatsApp"
            >
              <span>✏️</span> Ubah
            </button>
          </div>
        </div>

        {/* Saldo Card */}
        {isReseller ? (
          <div className="mt-4 bg-gradient-to-br from-[#1c1917] via-[#292524] to-[#451a03] border border-amber-500/30 rounded-2xl p-4 text-white relative overflow-hidden shadow-lg shadow-amber-950/20">
            <div className="absolute top-0 right-0 w-24 h-24 bg-amber-500/10 rounded-full blur-xl pointer-events-none"></div>
            <div className="flex justify-between items-center mb-1">
              <p className="text-[10px] font-black text-amber-400 uppercase tracking-wider">Saldo Reseller VIP</p>
              <span className="text-[8px] font-black bg-amber-500/20 text-amber-300 px-1.5 py-0.5 rounded border border-amber-500/30">BEBAS POTONGAN</span>
            </div>
            <p className="text-2xl font-black text-amber-300">{fmt.rp(user?.balance)}</p>
            <button
              onClick={() => setShowTopup(true)}
              className="mt-3 flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 text-xs font-black px-3.5 py-2 rounded-xl transition cursor-pointer shadow-md"
            >
              <Icon.Plus className="w-3.5 h-3.5" />Top Up Saldo
            </button>
          </div>
        ) : (
          <div className="mt-4 bg-gradient-to-br from-[#0b1121] to-[#1e3a5f] rounded-2xl p-4 text-white relative overflow-hidden">
            <div className="absolute top-0 right-0 w-20 h-20 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
            <p className="text-[10px] font-black text-blue-300 uppercase tracking-wider mb-1">Saldo Kamu</p>
            <p className="text-2xl font-black">{fmt.rp(user?.balance)}</p>
            <button
              onClick={() => setShowTopup(true)}
              className="mt-3 flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs font-black px-3 py-1.5 rounded-xl transition cursor-pointer shadow"
            >
              <Icon.Plus className="w-3.5 h-3.5" />Top Up
            </button>
          </div>
        )}

        {/* Promo Upgrade untuk Pelanggan Biasa */}
        {!isReseller && (
          <div className="mt-3 bg-gradient-to-r from-amber-500/15 via-yellow-500/15 to-amber-500/15 border border-amber-500/30 rounded-2xl p-3.5 text-center">
            <span className="text-base">👑</span>
            <p className="text-xs font-black text-amber-500 dark:text-amber-400 mt-1">Mau Harga Lebih Murah?</p>
            <p className="text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-0.5">Daftar Reseller hanya Rp 20.000 seumur hidup</p>
            <button
              onClick={() => setShowUpgradeModal(true)}
              className="mt-2.5 w-full bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 text-xs font-black py-2 rounded-xl hover:brightness-110 transition shadow cursor-pointer"
            >
              Upgrade Sekarang 👑
            </button>
          </div>
        )}
      </div>

      {/* Menu */}
      <nav className="flex-1 p-3 space-y-1">
        {menus.map(m => {
          const isActive = activeMenu === m.key;
          let activeClass = 'bg-orange-500 text-white shadow-md shadow-orange-500/20';
          if (isReseller) {
            activeClass = 'bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 text-gray-950 font-black shadow-lg shadow-amber-500/25';
          }

          return (
            <button
              key={m.key}
              onClick={() => handleMenuClick(m.key)}
              className={`w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-sm font-bold transition cursor-pointer ${isActive ? activeClass : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'}`}
            >
              <m.icon className="w-4 h-4 shrink-0" />
              <span className="flex-1 text-left">{m.label}</span>
              {m.badge && (
                <span className={`text-[9px] font-black px-1.5 py-0.5 rounded-full ${isActive ? 'bg-black text-amber-300' : 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300'}`}>
                  {m.badge}
                </span>
              )}
              {m.count !== undefined && m.count > 0 && (
                <span className={`text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[18px] text-center ${isActive ? 'bg-black/20 text-current' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'}`}>{m.count}</span>
              )}
              <Icon.ChevronRight className={`w-3.5 h-3.5 shrink-0 ${isActive ? 'opacity-80' : 'text-gray-300 dark:text-gray-600'}`} />
            </button>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="p-3 border-t border-gray-100 dark:border-gray-700/50">
        <button
          onClick={() => router.post('/logout')}
          className="w-full flex items-center gap-3 px-3 py-3 rounded-2xl text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition cursor-pointer"
        >
          <Icon.Logout className="w-4 h-4" />Keluar dari Akun
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0f172a] font-jakarta transition-colors">
      <Head>
        <title>{isReseller ? 'Profil VIP Reseller - NandarStore' : 'Profil Saya - NandarStore'}</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
        <style>{`.font-jakarta{font-family:'Plus Jakarta Sans',sans-serif}.glass-nav{background:rgba(255,255,255,0.95);backdrop-filter:blur(10px)}.dark .glass-nav{background:rgba(15,23,42,0.9)}`}</style>
      </Head>

      {/* NAVBAR */}
      <nav className="glass-nav sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800 shadow-sm">
        <div className="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <button onClick={() => setSidebarOpen(true)} className="md:hidden p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
              <Icon.Menu className="w-5 h-5 dark:text-white" />
            </button>
            <Link href="/" className="flex items-center gap-2">
              <div className="w-8 h-8 bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg text-white flex items-center justify-center font-black text-sm shadow-md">NS</div>
              <span className="font-black text-gray-900 dark:text-white hidden sm:block">Nandar<span className="text-orange-500">Store</span></span>
            </Link>
          </div>
          <div className="flex items-center gap-2">
            <button onClick={toggleDark} className="p-2 rounded-full text-gray-400 hover:text-gray-700 dark:hover:text-white transition cursor-pointer">
              {isDark ? <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clipRule="evenodd"/></svg> : <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>}
            </button>
            <Link href="/" className="flex items-center gap-1.5 text-xs font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 dark:hover:text-orange-400 transition">
              <Icon.Home className="w-4 h-4" /><span className="hidden sm:block">Beranda</span>
            </Link>
          </div>
        </div>
      </nav>

      {/* MOBILE SIDEBAR OVERLAY */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-50 flex md:hidden">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setSidebarOpen(false)} />
          <div className="relative w-72 bg-white dark:bg-gray-900 h-full shadow-2xl overflow-y-auto">
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
              <span className="font-black text-gray-900 dark:text-white">Menu</span>
              <button onClick={() => setSidebarOpen(false)} className="p-1.5 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-white cursor-pointer"><Icon.X className="w-5 h-5" /></button>
            </div>
            <SidebarContent />
          </div>
        </div>
      )}

      {/* MAIN LAYOUT */}
      <div className="max-w-6xl mx-auto px-4 py-6">
        <div className="flex gap-6 items-start">

          {/* DESKTOP SIDEBAR */}
          <aside className="hidden md:flex flex-col w-72 shrink-0 h-fit self-start">
            <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm overflow-hidden h-fit">
              <SidebarContent />
            </div>
          </aside>

          {/* MAIN CONTENT */}
          <main className="flex-1 min-w-0">

            {/* Dashboard Profil (Beranda Akun Pengguna) */}
            {activeMenu === 'dashboard' && (
              <div className="space-y-5">
                {/* Banner Sapaan Pengguna */}
                <div className="bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent border border-orange-500/20 dark:border-orange-500/30 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden">
                  <div className="absolute top-0 right-0 w-36 h-36 bg-orange-500/10 rounded-full blur-2xl pointer-events-none"></div>
                  
                  <div className="flex items-center gap-3.5 relative z-10 min-w-0">
                    <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 text-white flex items-center justify-center font-black text-2xl shadow-lg shadow-orange-500/25 shrink-0">
                      👋
                    </div>
                    <div className="min-w-0">
                      <div className="flex items-center gap-2 flex-wrap">
                        <h1 className="text-base sm:text-xl font-black text-gray-900 dark:text-white leading-tight">
                          Halo, <span className="text-orange-500">{user?.name}</span>!
                        </h1>
                        {isReseller ? (
                          <span className="text-[9px] font-black px-2 py-0.5 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-950 uppercase shadow-xs">
                            👑 VIP Reseller
                          </span>
                        ) : (
                          <span className="text-[9px] font-black px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 uppercase">
                            Member
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Selamat datang di <strong className="text-gray-700 dark:text-gray-300">Dashboard Profil</strong>. Pantau saldo, mutasi, dan transaksi singkat akun Anda di sini.
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 relative z-10 shrink-0">
                    <div className="px-3.5 py-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/80 rounded-xl text-xs shadow-sm flex items-center gap-2">
                      <span className="text-gray-400 font-bold">Saldo:</span>
                      <span className="font-black text-orange-500 text-sm">{fmt.rp(user?.balance)}</span>
                    </div>
                    <button
                      type="button"
                      onClick={() => setShowTopup(true)}
                      className="px-3.5 py-2 bg-orange-500 hover:bg-orange-600 text-white font-black text-xs rounded-xl shadow-md shadow-orange-500/20 transition cursor-pointer flex items-center gap-1.5 active:scale-95"
                    >
                      <Icon.Plus className="w-3.5 h-3.5" />
                      <span>Top Up</span>
                    </button>
                  </div>
                </div>

                {/* 4 Kartu Metrik Ringkas */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                  {/* Saldo Aktif */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400">Saldo Aktif</span>
                      <div className="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs">
                        💳
                      </div>
                    </div>
                    <p className="text-base sm:text-lg font-black text-gray-900 dark:text-white truncate">
                      {fmt.rp(user?.balance)}
                    </p>
                    <button
                      type="button"
                      onClick={() => setShowTopup(true)}
                      className="mt-2 text-[11px] font-bold text-orange-500 hover:text-orange-600 inline-flex items-center gap-0.5 cursor-pointer"
                    >
                      + Top Up Saldo
                    </button>
                  </div>

                  {/* Total Pengeluaran */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400">Pengeluaran</span>
                      <div className="w-7 h-7 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-xs">
                        ↑
                      </div>
                    </div>
                    <p className="text-base sm:text-lg font-black text-rose-600 dark:text-rose-400 truncate">
                      -{fmt.rp(totalExpenseCalc)}
                    </p>
                    <p className="mt-2 text-[11px] font-medium text-gray-400 truncate">
                      {totalExpenseCount}x belanja
                    </p>
                  </div>

                  {/* Total Pemasukan */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pemasukan</span>
                      <div className="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xs">
                        ↓
                      </div>
                    </div>
                    <p className="text-base sm:text-lg font-black text-emerald-600 dark:text-emerald-400 truncate">
                      +{fmt.rp(totalIncomeCalc)}
                    </p>
                    <p className="mt-2 text-[11px] font-medium text-gray-400 truncate">
                      {totalIncomeCount}x top up
                    </p>
                  </div>

                  {/* Total Pesanan */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-4 shadow-sm hover:shadow-md transition">
                    <div className="flex items-center justify-between mb-1.5">
                      <span className="text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">Pesanan</span>
                      <div className="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs">
                        📦
                      </div>
                    </div>
                    <p className="text-base sm:text-lg font-black text-gray-900 dark:text-white truncate">
                      {orders.length}
                    </p>
                    <button
                      type="button"
                      onClick={() => handleMenuClick('orders')}
                      className="mt-2 text-[11px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 dark:hover:text-orange-400 inline-flex items-center gap-0.5 cursor-pointer"
                    >
                      Lihat Semua →
                    </button>
                  </div>
                </div>

                {/* Transaksi Singkat: 2 Kolom (Pesanan Terakhir & Aktivitas Saldo Terakhir) */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                  {/* Kolom 1: Pesanan Terakhir */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div className="flex items-center gap-2.5">
                          <div className="w-8 h-8 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center">
                            <Icon.History className="w-4 h-4" />
                          </div>
                          <div>
                            <h3 className="text-sm font-black text-gray-900 dark:text-white">Pesanan Terakhir</h3>
                            <p className="text-[11px] text-gray-400">Ringkasan transaksi pembelian akun Anda</p>
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => handleMenuClick('orders')}
                          className="text-xs font-bold text-orange-500 hover:text-orange-600 transition cursor-pointer flex items-center gap-1"
                        >
                          Lihat Semua <Icon.ChevronRight className="w-3 h-3" />
                        </button>
                      </div>

                      {orders.length === 0 ? (
                        <div className="py-8 text-center text-gray-400">
                          <Icon.History className="w-8 h-8 mx-auto mb-2 opacity-30" />
                          <p className="text-xs font-bold text-gray-500 dark:text-gray-400">Belum ada riwayat pesanan</p>
                          <Link href="/" className="mt-2.5 inline-block text-xs font-black text-orange-500 hover:underline">
                            Mulai Belanja Sekarang →
                          </Link>
                        </div>
                      ) : (
                        <div className="space-y-2.5">
                          {orders.slice(0, 4).map((o, idx) => (
                            <div
                              key={o.id || idx}
                              className="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700/50 hover:border-orange-500/30 transition flex items-center justify-between gap-3"
                            >
                              <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-1.5 flex-wrap">
                                  <span className="text-[10px] font-mono font-bold text-gray-500 dark:text-gray-400 truncate max-w-[130px]">
                                    #{o.invoice_number || o.id}
                                  </span>
                                  <StatusBadge status={o.order_status || o.payment_status} />
                                </div>
                                <p className="text-xs font-black text-gray-800 dark:text-white truncate mt-1">
                                  {o.product_name || 'Produk Digital'}
                                </p>
                                <p className="text-[10px] text-gray-400 mt-0.5">
                                  {fmt.date(o.created_at)}
                                </p>
                              </div>
                              <div className="text-right shrink-0">
                                <p className="text-xs sm:text-sm font-black text-orange-500">
                                  {fmt.rp(o.total_amount || o.price)}
                                </p>
                                <a
                                  href={`/order/invoice/${o.invoice_number || o.id}`}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="text-[10px] font-bold text-gray-500 hover:text-orange-500 dark:text-gray-400 mt-0.5 inline-flex items-center gap-0.5"
                                >
                                  Invoice ↗
                                </a>
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                    </div>

                    {orders.length > 4 && (
                      <div className="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 text-center">
                        <button
                          type="button"
                          onClick={() => handleMenuClick('orders')}
                          className="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-orange-500 cursor-pointer"
                        >
                          +{orders.length - 4} pesanan lainnya. Klik untuk melihat riwayat lengkap →
                        </button>
                      </div>
                    )}
                  </div>

                  {/* Kolom 2: Aktivitas Saldo Terakhir */}
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 p-5 shadow-sm flex flex-col justify-between">
                    <div>
                      <div className="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-700/60">
                        <div className="flex items-center gap-2.5">
                          <div className="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                            <Icon.Wallet className="w-4 h-4" />
                          </div>
                          <div>
                            <h3 className="text-sm font-black text-gray-900 dark:text-white">Aktivitas Saldo</h3>
                            <p className="text-[11px] text-gray-400">Mutasi top up dan pengeluaran dana</p>
                          </div>
                        </div>
                        <button
                          type="button"
                          onClick={() => handleMenuClick('topup')}
                          className="text-xs font-bold text-blue-500 hover:text-blue-600 transition cursor-pointer flex items-center gap-1"
                        >
                          Lihat Semua <Icon.ChevronRight className="w-3 h-3" />
                        </button>
                      </div>

                      {allMutations.length === 0 ? (
                        <div className="py-8 text-center text-gray-400">
                          <Icon.Wallet className="w-8 h-8 mx-auto mb-2 opacity-30" />
                          <p className="text-xs font-bold text-gray-500 dark:text-gray-400">Belum ada mutasi saldo</p>
                          <button
                            type="button"
                            onClick={() => setShowTopup(true)}
                            className="mt-2.5 inline-block text-xs font-black text-blue-500 hover:underline cursor-pointer"
                          >
                            Top Up Saldo Sekarang →
                          </button>
                        </div>
                      ) : (
                        <div className="space-y-2.5">
                          {allMutations.slice(0, 4).map((m, idx) => {
                            const isInc = m.type === 'income';
                            return (
                              <div
                                key={m.id || idx}
                                className="p-3 rounded-xl bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-700/50 hover:border-blue-500/30 transition flex items-center justify-between gap-3"
                              >
                                <div className="flex items-center gap-2.5 min-w-0 flex-1">
                                  <div className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 font-black text-xs ${
                                    isInc
                                      ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400'
                                      : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'
                                  }`}>
                                    {isInc ? '↓' : '↑'}
                                  </div>
                                  <div className="min-w-0 flex-1">
                                    <p className="text-xs font-black text-gray-800 dark:text-white truncate">
                                      {m.title || (isInc ? 'Top Up Saldo' : 'Pengeluaran')}
                                    </p>
                                    <div className="flex items-center gap-1.5 text-[10px] text-gray-400 mt-0.5">
                                      <span>{fmt.date(m.created_at)}</span>
                                      {m.invoice && <span className="font-mono truncate">({m.invoice})</span>}
                                    </div>
                                  </div>
                                </div>
                                <div className="text-right shrink-0">
                                  <p className={`text-xs sm:text-sm font-black ${
                                    isInc
                                      ? 'text-emerald-600 dark:text-emerald-400'
                                      : 'text-rose-600 dark:text-rose-400'
                                  }`}>
                                    {isInc ? '+' : '-'}{fmt.rp(m.amount)}
                                  </p>
                                  <span className={`text-[9px] font-black uppercase px-1.5 py-0.2 rounded ${
                                    m.status === 'success' || m.status === 'paid'
                                      ? 'text-emerald-500'
                                      : 'text-amber-500'
                                  }`}>
                                    {m.status === 'success' || m.status === 'paid' ? 'Sukses' : m.status}
                                  </span>
                                </div>
                              </div>
                            );
                          })}
                        </div>
                      )}
                    </div>

                    {allMutations.length > 4 && (
                      <div className="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/60 text-center">
                        <button
                          type="button"
                          onClick={() => handleMenuClick('topup')}
                          className="text-xs font-bold text-gray-500 dark:text-gray-400 hover:text-blue-500 cursor-pointer"
                        >
                          +{allMutations.length - 4} mutasi lainnya. Klik untuk melihat riwayat lengkap →
                        </button>
                      </div>
                    )}
                  </div>
                </div>

                {/* Banner WhatsApp jika belum terverifikasi */}
                {!user?.is_whatsapp_verified && (
                  <div className="bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-transparent border border-amber-500/20 dark:border-amber-500/30 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
                    <div className="flex items-center gap-3">
                      <span className="text-2xl">📱</span>
                      <div>
                        <h4 className="text-xs sm:text-sm font-black text-gray-900 dark:text-white">
                          Nomor WhatsApp Belum Terverifikasi
                        </h4>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                          Verifikasi sekarang agar struk pesanan & akun produk bisa otomatis dikirimkan langsung ke WhatsApp Anda.
                        </p>
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => { setWaModalPhone(user?.whatsapp || ''); setShowWaModal(true); }}
                      className="px-4 py-2 bg-gradient-to-r from-amber-500 to-yellow-500 hover:brightness-110 text-gray-950 font-black text-xs rounded-xl shadow transition cursor-pointer shrink-0"
                    >
                      Verifikasi Sekarang
                    </button>
                  </div>
                )}
              </div>
            )}

            {/* Edit Profil */}
            {activeMenu === 'profile' && (
              <div>
                <h2 className="text-lg font-black text-gray-900 dark:text-white mb-4">Edit Profil</h2>
                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                  <EditProfileSection
                    user={user}
                    onOpenWaModal={(phone) => { setWaModalPhone(phone || user?.whatsapp || ''); setShowWaModal(true); }}
                    onOpenChangeWaModal={() => setShowChangeWaModal(true)}
                  />
                </div>
              </div>
            )}

            {/* Riwayat Pembelian */}
            {activeMenu === 'orders' && (
              <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                  <div>
                    <h2 className="text-lg font-black text-gray-900 dark:text-white">Riwayat Pembelian</h2>
                    <p className="text-xs text-gray-400 mt-0.5">Daftar semua transaksi dan produk yang telah kamu beli</p>
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                      {orderSearchQuery.trim()
                        ? `${filteredOrders.length} dari ${orders.length} transaksi`
                        : `${orders.length} transaksi`}
                    </span>
                  </div>
                </div>

                {/* Search Bar */}
                {orders.length > 0 && (
                  <div className="mb-4">
                    <div className="relative">
                      <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                        <Icon.Search className="w-4 h-4" />
                      </div>
                      <input
                        type="text"
                        value={orderSearchQuery}
                        onChange={(e) => setOrderSearchQuery(e.target.value)}
                        placeholder="Cari berdasarkan No. Invoice, Nama Produk, Akun/SN, No. WA, Catatan..."
                        className="w-full pl-10 pr-10 py-2.5 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 shadow-sm transition"
                      />
                      {orderSearchQuery && (
                        <button
                          type="button"
                          onClick={() => setOrderSearchQuery('')}
                          className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
                        >
                          <Icon.X className="w-4 h-4" />
                        </button>
                      )}
                    </div>
                  </div>
                )}

                {orders.length === 0 ? (
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center shadow-sm">
                    <div className="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                      <Icon.History className="w-8 h-8 text-gray-300 dark:text-gray-600" />
                    </div>
                    <h3 className="font-extrabold text-gray-800 dark:text-white mb-1">Belum Ada Pembelian</h3>
                    <p className="text-xs text-gray-400 mb-5">Riwayat transaksi kamu akan muncul di sini.</p>
                    <Link href="/" className="inline-flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition">
                      Mulai Belanja
                    </Link>
                  </div>
                ) : filteredOrders.length === 0 ? (
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-10 text-center shadow-sm">
                    <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                      <Icon.Search className="w-7 h-7" />
                    </div>
                    <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Pesanan Tidak Ditemukan</h3>
                    <p className="text-xs text-gray-400 mb-4">Tidak ada transaksi yang cocok dengan "{orderSearchQuery}".</p>
                    <button
                      type="button"
                      onClick={() => setOrderSearchQuery('')}
                      className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
                    >
                      <Icon.X className="w-3.5 h-3.5" />
                      Reset Pencarian
                    </button>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {paginatedOrders.map((o, i) => (
                      <div key={o.id || i} className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 sm:p-5 shadow-sm hover:shadow-md transition-shadow">
                        <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                          <div className="flex-1 min-w-0">
                            <div className="flex flex-wrap items-center gap-2 mb-1.5">
                              <span className="text-[11px] font-mono font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700/60 px-2.5 py-0.5 rounded-md break-all">#{o.invoice_number || o.id}</span>
                              <StatusBadge status={o.order_status || o.payment_status} />
                              {o.product_type === 'ppob' ? (
                                <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                  ⚡ PPOB
                                </span>
                              ) : (
                                <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                  ⚡ Akun Premium
                                </span>
                              )}
                            </div>
                            <p className="font-extrabold text-sm sm:text-base text-gray-900 dark:text-white leading-snug mb-1 break-words">{o.product_name}</p>

                            {o.notes && !/premku|preflix|provider|api|invalid|\[internal/i.test(o.notes) && (
                              <p className="text-xs text-gray-500 dark:text-gray-400 mb-2 italic break-words">
                                Catatan: {o.notes}
                              </p>
                            )}

                            {o.sn_or_account && (
                              <div className="mt-2.5 p-3 sm:p-3.5 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/50 rounded-xl">
                                <div className="flex items-center justify-between gap-2 mb-1.5">
                                  <p className="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Detail Akun / Serial Number</p>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      navigator.clipboard.writeText(o.sn_or_account);
                                      setCopiedSnId(o.id || o.invoice_number);
                                      showToast('success', 'Detail akun berhasil disalin ke clipboard!');
                                      setTimeout(() => setCopiedSnId(null), 2500);
                                    }}
                                    className="text-[10px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline flex items-center gap-1 cursor-pointer shrink-0"
                                  >
                                    {copiedSnId === (o.id || o.invoice_number) ? (
                                      <>
                                        <Icon.Check className="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                                        <span className="text-emerald-600 dark:text-emerald-400 font-black">Tersalin!</span>
                                      </>
                                    ) : (
                                      <>
                                        <Icon.Copy className="w-3 h-3" />
                                        <span>Salin Akun</span>
                                      </>
                                    )}
                                  </button>
                                </div>
                                <div className="bg-white/80 dark:bg-black/30 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-900/40">
                                  <p className="text-xs font-mono font-bold text-emerald-900 dark:text-emerald-200 break-all whitespace-pre-wrap select-all">{o.sn_or_account}</p>
                                </div>
                              </div>
                            )}

                            <div className="flex flex-wrap items-center gap-2 mt-3">
                              <p className="text-[11px] text-gray-400 font-medium">{fmt.date(o.created_at)}</p>
                              <a
                                href={`/order/invoice/${o.invoice_number || o.id}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer shadow-sm"
                                title="Lihat dan Cetak Invoice PDF Resmi"
                              >
                                <Icon.FileText className="w-3.5 h-3.5 text-orange-500" />
                                <span>Cetak Invoice</span>
                              </a>
                              <button
                                type="button"
                                onClick={() => handleSendOrderWa(o.invoice_number || o.id)}
                                disabled={waSendingId === (o.invoice_number || o.id)}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-xl transition cursor-pointer shadow-sm border border-emerald-200 dark:border-emerald-800 disabled:opacity-50"
                                title="Kirim detail akun & link invoice ke nomor WhatsApp terverifikasi"
                              >
                                {waSendingId === (o.invoice_number || o.id) ? (
                                  <span>Mengirim...</span>
                                ) : (
                                  <>
                                    <span>📲</span>
                                    <span>Kirim ke WA</span>
                                  </>
                                )}
                              </button>
                            </div>
                          </div>
                          <div className="sm:text-right shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-700 flex sm:flex-col justify-between sm:justify-start items-center sm:items-end">
                            <span className="text-xs text-gray-400 sm:hidden">Total Bayar:</span>
                            <div>
                              <p className={`font-black text-base sm:text-lg ${isReseller ? 'text-amber-500' : 'text-orange-500'}`}>{fmt.rp(o.total_amount || o.price)}</p>
                              {o.discount_amount > 0 && <p className="text-[10px] text-green-600 font-bold">Hemat {fmt.rp(o.discount_amount)}</p>}
                            </div>
                          </div>
                        </div>
                      </div>
                    ))}

                    {/* Pagination Riwayat Pembelian (Persis Foto) */}
                    <div className="pt-2">
                      <Pagination
                        currentPage={orderPage}
                        totalPages={orderTotalPages}
                        onPageChange={(p) => {
                          setOrderPage(p);
                          window.scrollTo({ top: 0, behavior: 'smooth' });
                        }}
                      />
                      {filteredOrders.length > 0 && (
                        <p className="text-center text-[11px] text-gray-400 mt-2 font-medium">
                          Menampilkan {((orderPage - 1) * 10) + 1} - {Math.min(orderPage * 10, filteredOrders.length)} dari {filteredOrders.length} riwayat pembelian
                        </p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Riwayat Top Up & Mutasi Saldo */}
            {activeMenu === 'topup' && (
              <div>
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                  <div>
                    <h2 className="text-lg font-black text-gray-900 dark:text-white">Riwayat Top Up &amp; Saldo</h2>
                    <p className="text-xs text-gray-400 mt-0.5">Catatan seluruh pemasukan dan pengeluaran dana akun Anda</p>
                  </div>
                  <button
                    type="button"
                    onClick={() => setShowTopup(true)}
                    className={`inline-flex items-center gap-1.5 text-xs font-black px-4 py-2.5 rounded-xl shadow transition cursor-pointer shrink-0 ${
                      isReseller
                        ? 'bg-gradient-to-r from-amber-500 to-yellow-500 text-gray-950 hover:brightness-110 shadow-amber-500/20'
                        : 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-600/20'
                    }`}
                  >
                    <Icon.Plus className="w-3.5 h-3.5" />
                    <span>Top Up Saldo</span>
                  </button>
                </div>

                {/* ─── KARTU STATISTIK AKUMULASI PEMASUKAN & PENGELUARAN ─── */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5 mb-5">
                  {/* Total Pengeluaran Sejak Daftar */}
                  <div className="bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-transparent border border-rose-500/20 dark:border-rose-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[10px] font-black uppercase tracking-wider text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
                        Total Pengeluaran
                      </span>
                      <div className="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center font-black text-sm">
                        ↑
                      </div>
                    </div>
                    <p className="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">
                      -{fmt.rp(totalExpenseCalc)}
                    </p>
                    <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                      <span>🛍️</span> {totalExpenseCount}x transaksi belanja sejak daftar
                    </p>
                  </div>

                  {/* Total Pemasukan Sejak Daftar */}
                  <div className="bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-500/20 dark:border-emerald-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Total Pemasukan
                      </span>
                      <div className="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-black text-sm">
                        ↓
                      </div>
                    </div>
                    <p className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                      +{fmt.rp(totalIncomeCalc)}
                    </p>
                    <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                      <span>✓</span> {totalIncomeCount}x transaksi top up sejak daftar
                    </p>
                  </div>

                  {/* Sisa Saldo Aktif Saat Ini */}
                  <div className="sm:col-span-2 lg:col-span-1 bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-transparent border border-blue-500/20 dark:border-blue-500/30 rounded-2xl p-4 relative overflow-hidden shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 flex items-center gap-1.5">
                        <span className="w-2 h-2 rounded-full bg-blue-500"></span>
                        Sisa Saldo Aktif
                      </span>
                      <div className="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center font-black text-sm">
                        💳
                      </div>
                    </div>
                    <p className="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                      {fmt.rp(user?.balance)}
                    </p>
                    <p className="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                      <span>⚡</span> Saldo siap digunakan bertransaksi
                    </p>
                  </div>
                </div>

                {/* Filter Pills Pemasukan / Pengeluaran */}
                <div className="flex items-center gap-2 mb-4 overflow-x-auto pb-1">
                  <button
                    type="button"
                    onClick={() => setMutationFilter('all')}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap ${
                      mutationFilter === 'all'
                        ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 shadow-sm'
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                    }`}
                  >
                    Semua ({allMutations.length})
                  </button>
                  <button
                    type="button"
                    onClick={() => setMutationFilter('income')}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1.5 ${
                      mutationFilter === 'income'
                        ? 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20'
                        : 'bg-white dark:bg-gray-800 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
                    }`}
                  >
                    <span>↓ Pemasukan</span>
                    <span className="text-[10px] opacity-80">({allMutations.filter(m => m.type === 'income').length})</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => setMutationFilter('expense')}
                    className={`px-3.5 py-1.5 rounded-xl text-xs font-black transition cursor-pointer whitespace-nowrap flex items-center gap-1.5 ${
                      mutationFilter === 'expense'
                        ? 'bg-rose-600 text-white shadow-sm shadow-rose-600/20'
                        : 'bg-white dark:bg-gray-800 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-50 dark:hover:bg-rose-950/20'
                    }`}
                  >
                    <span>↑ Pengeluaran</span>
                    <span className="text-[10px] opacity-80">({allMutations.filter(m => m.type === 'expense').length})</span>
                  </button>
                </div>

                {allMutations.length === 0 ? (
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center shadow-sm">
                    <div className="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                      <Icon.Wallet className="w-8 h-8 text-gray-300 dark:text-gray-600" />
                    </div>
                    <h3 className="font-extrabold text-gray-800 dark:text-white mb-1">Belum Ada Riwayat Mutasi</h3>
                    <p className="text-xs text-gray-400 mb-5">Riwayat transaksi top up dan penggunaan saldo Anda akan tampil di sini.</p>
                    <button
                      type="button"
                      onClick={() => setShowTopup(true)}
                      className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow transition cursor-pointer"
                    >
                      <Icon.Plus className="w-4 h-4" />Top Up Sekarang
                    </button>
                  </div>
                ) : filteredMutations.length === 0 ? (
                  <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-10 text-center shadow-sm">
                    <div className="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                      <Icon.Wallet className="w-7 h-7" />
                    </div>
                    <h3 className="font-extrabold text-sm text-gray-800 dark:text-white mb-1">Tidak Ada Data</h3>
                    <p className="text-xs text-gray-400 mb-4">Belum ada mutasi untuk kategori filter yang dipilih.</p>
                    <button
                      type="button"
                      onClick={() => setMutationFilter('all')}
                      className="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 text-xs font-bold rounded-xl transition cursor-pointer"
                    >
                      Tampilkan Semua
                    </button>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {paginatedMutations.map((m, idx) => {
                      const isIncome = m.type === 'income';
                      const isExpense = m.type === 'expense';
                      const isPending = m.status === 'pending';
                      const isExpired = m.status === 'expired';

                      return (
                        <div key={m.id || idx} className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/80 p-4 shadow-sm hover:shadow-md transition">
                          <div className="flex items-center justify-between gap-3 sm:gap-4">
                            {/* Icon & Detail */}
                            <div className="flex items-center gap-3 sm:gap-3.5 min-w-0">
                              <div className={`w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 border ${
                                isIncome
                                  ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800/60'
                                  : isExpense
                                  ? 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 border-rose-200 dark:border-rose-800/60'
                                  : isPending
                                  ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-800/60 animate-pulse'
                                  : 'bg-gray-100 dark:bg-gray-800 text-gray-400 border-gray-200 dark:border-gray-700'
                              }`}>
                                {isIncome ? (
                                  <Icon.ArrowDownLeft className="w-5 h-5" />
                                ) : isExpense ? (
                                  <Icon.ArrowUpRight className="w-5 h-5" />
                                ) : isPending ? (
                                  <Icon.Scan className="w-5 h-5" />
                                ) : (
                                  <Icon.Wallet className="w-5 h-5" />
                                )}
                              </div>

                              <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-0.5">
                                  {isIncome && (
                                    <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-700">
                                      Pemasukan
                                    </span>
                                  )}
                                  {isExpense && (
                                    <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-700">
                                      Pengeluaran
                                    </span>
                                  )}
                                  {isPending && (
                                    <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-700 animate-pulse">
                                      Menunggu Pembayaran
                                    </span>
                                  )}
                                  {isExpired && (
                                    <span className="text-[10px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-300 dark:border-gray-700">
                                      Kadaluarsa
                                    </span>
                                  )}
                                  {m.invoice_number && (
                                    <span className="font-mono text-[10px] text-gray-400 truncate">
                                      #{m.invoice_number}
                                    </span>
                                  )}
                                </div>

                                <p className="font-extrabold text-xs sm:text-sm text-gray-900 dark:text-white truncate">
                                  {m.description || (isIncome ? 'Top Up Saldo' : isExpense ? 'Transaksi Saldo' : 'Tagihan QRIS')}
                                </p>

                                <div className="flex items-center gap-2 mt-0.5">
                                  <p className="text-[10px] text-gray-400">{fmt.date(m.created_at)}</p>
                                  {isPending && (
                                    <span className="text-[10px] font-bold text-amber-500">
                                      (Kadaluarsa dlm 10 menit)
                                    </span>
                                  )}
                                </div>
                              </div>
                            </div>

                            {/* Nominal & Tombol QRIS */}
                            <div className="text-right shrink-0">
                              <p className={`font-black text-sm sm:text-base ${
                                isIncome
                                  ? 'text-emerald-600 dark:text-emerald-400'
                                  : isExpense
                                  ? 'text-rose-600 dark:text-rose-400'
                                  : isPending
                                  ? 'text-amber-500 dark:text-amber-400'
                                  : 'text-gray-400'
                              }`}>
                                {isIncome ? `+${fmt.rp(m.amount)}` : isExpense ? `-${fmt.rp(m.amount)}` : fmt.rp(m.amount)}
                              </p>

                              {isPending && m.qris_url && (
                                <a
                                  href={m.qris_url}
                                  target="_blank"
                                  rel="noopener noreferrer"
                                  className="mt-1 inline-flex items-center gap-1 text-[11px] font-black text-blue-500 hover:text-blue-600 hover:underline"
                                >
                                  Bayar QRIS →
                                </a>
                              )}
                            </div>
                          </div>
                        </div>
                      );
                    })}

                    {/* Pagination Riwayat Top Up / Mutasi (Persis Foto) */}
                    <div className="pt-2">
                      <Pagination
                        currentPage={mutationPage}
                        totalPages={mutationTotalPages}
                        onPageChange={(p) => {
                          setMutationPage(p);
                          window.scrollTo({ top: 0, behavior: 'smooth' });
                        }}
                      />
                      {filteredMutations.length > 0 && (
                        <p className="text-center text-[11px] text-gray-400 mt-2 font-medium">
                          Menampilkan {((mutationPage - 1) * 10) + 1} - {Math.min(mutationPage * 10, filteredMutations.length)} dari {filteredMutations.length} mutasi saldo
                        </p>
                      )}
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* Ubah Password */}
            {activeMenu === 'security' && (
              <div>
                <h2 className="text-lg font-black text-gray-900 dark:text-white mb-4">Ubah Password</h2>
                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                  <div className="flex items-center gap-3 mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-100 dark:border-orange-800/50">
                    <Icon.Lock className="w-5 h-5 text-orange-500 shrink-0" />
                    <p className="text-xs font-semibold text-gray-600 dark:text-gray-300">Gunakan password yang kuat minimal 8 karakter dengan kombinasi huruf dan angka.</p>
                  </div>
                  <ResetPasswordSection flash={flash} />
                </div>
              </div>
            )}

          </main>
        </div>
      </div>

      {/* TOP UP MODAL */}
      {showTopup && <TopupModal onClose={() => setShowTopup(false)} onSuccess={() => { setShowTopup(false); router.reload({ only: [] }); }} />}

      {/* UPGRADE RESELLER MODAL */}
      {showUpgradeModal && <UpgradeResellerModal onClose={() => setShowUpgradeModal(false)} userBalance={user?.balance || 0} />}

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
