import React, { useState, useRef, useEffect } from 'react';
import { Icon } from './ProfileIcons';

export default function WaVerificationModal({ onClose, userWhatsapp, onVerified, initialData = null, onOpenChangeModal }) {
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
      <div className="w-full max-w-md bg-white dark:bg-gray-900 rounded-3xl shadow-2xl overflow-y-auto max-h-[92dvh] border border-emerald-500/30 text-gray-900 dark:text-white">
        
        {/* Header */}
        <div className="bg-gradient-to-r from-emerald-600 to-green-600 p-5 flex items-center justify-between text-white">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center font-black text-xl">
              📲
            </div>
            <div>
              <h3 className="font-black text-lg">Verifikasi Nomor WhatsApp</h3>
              <p className="text-green-100 text-xs font-semibold">Notifikasi &amp; pengiriman akun otomatis</p>
            </div>
          </div>
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer" aria-label="Tutup Verifikasi WA">
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
                <label className="text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider mb-2 block">Nomor WhatsApp Anda</label>
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
                <span className="text-gray-500 dark:text-gray-300 font-medium">
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
                <span className="text-[11px] font-medium uppercase tracking-wider text-gray-400">Kode Unik Verifikasi Anda:</span>
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
                    Kirimkan pesan tersebut. Bot WhatsApp PremiumKita akan otomatis mendeteksi kode verifikasi dan mengonfirmasi akun Anda secara instan!
                  </p>
                </div>
              </div>

              {/* Status Box Menunggu Validasi Bot */}
              <div className="p-3.5 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-2xl text-center space-y-1">
                <div className="flex items-center justify-center gap-2 text-amber-600 dark:text-amber-400 font-extrabold text-xs">
                  <span className="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                  Menunggu Pesan Terkirim ke Bot WhatsApp...
                </div>
                <p className="text-[11px] text-gray-500 dark:text-gray-300 font-medium">
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
              <p className="text-xs text-gray-500 dark:text-gray-300 font-medium mb-5">
                Nomor Anda telah terkonfirmasi. Mulai sekarang rincian pesanan dan invoice akan otomatis dikirimkan ke WhatsApp Anda.
              </p>
              <button
                type="button"
                onClick={() => { onClose(); window.location.reload(); }}
                className="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-2xl cursor-pointer text-xs"
              >
                Selesai &amp; Muat Ulang
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

