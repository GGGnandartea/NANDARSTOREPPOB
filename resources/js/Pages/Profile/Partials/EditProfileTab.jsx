import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Icon } from './ProfileIcons';

export default function EditProfileTab({ user, onOpenWaModal, onOpenChangeWaModal }) {
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
    <div>
      <h2 className="text-lg font-black text-gray-900 dark:text-white mb-4">Edit Profil</h2>
      <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
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
      </div>
    </div>
  );
}

