import React, { useState } from 'react';
import { Icon } from './ProfileIcons';

export default function ChangeWaModal({ onClose, currentWhatsapp, onNumberChanged }) {
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
          <button onClick={onClose} className="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-white hover:bg-white/20 transition cursor-pointer" aria-label="Tutup Ganti Nomor WA">
            <Icon.X className="w-4 h-4" />
          </button>
        </div>

        <div className="p-6 space-y-4">
          <div className="p-3.5 bg-gray-50 dark:bg-gray-800/60 rounded-2xl border border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
            <span className="text-gray-500 dark:text-gray-300 font-medium">Nomor Tersimpan Saat Ini:</span>
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
            <label className="text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider mb-2 block">
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
              className="w-full py-2.5 text-xs text-gray-500 dark:text-gray-300 font-bold hover:text-gray-800 dark:hover:text-gray-200 transition cursor-pointer"
            >
              Batal
            </button>
          </div>
        </div>

      </div>
    </div>
  );
}

