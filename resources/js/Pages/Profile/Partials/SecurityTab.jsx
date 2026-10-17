import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Icon } from './ProfileIcons';

export default function SecurityTab({ flash }) {
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
        <button type="button" onClick={() => setShowPass(p => ({ ...p, [key]: !p[key] }))} className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer" aria-label={`Toggle ${label}`}>
          {showPass[key] ? <Icon.EyeOff className="w-4 h-4" /> : <Icon.Eye className="w-4 h-4" />}
        </button>
      </div>
    </div>
  );

  return (
    <div>
      <h2 className="text-lg font-black text-gray-900 dark:text-white mb-4">Ubah Password</h2>
      <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
        <div className="flex items-center gap-3 mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-2xl border border-orange-100 dark:border-orange-800/50">
          <Icon.Lock className="w-5 h-5 text-orange-500 shrink-0" />
          <p className="text-xs font-semibold text-gray-600 dark:text-gray-300">Gunakan password yang kuat minimal 8 karakter dengan kombinasi huruf dan angka.</p>
        </div>
        
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
      </div>
    </div>
  );
}
