import React from 'react';
import { Link } from '@inertiajs/react';

export default function CheckoutForm({
  user,
  userBalance,
  product,
  quantity = 1,
  setQuantity,
  maxStock = 999,
  unitPrice,
  subtotal,
  buyerName,
  setBuyerName,
  email,
  setEmail,
  whatsapp,
  setWhatsapp,
  notes,
  setNotes,
  voucher,
  setVoucher,
  voucherLoading,
  checkVoucher,
  discountAmount,
  setDiscountAmount,
  discountName,
  setDiscountName,
  isReseller,
  finalPrice,
  canPayBalance,
  payMethod,
  setPayMethod,
  isProcessing,
  handleQrisCheckout,
  handleBalanceCheckout,
  fmt,
}) {
  return (
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
          onChange={(e) => setBuyerName(e.target.value)}
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
              value={user?.is_whatsapp_verified ? user?.whatsapp || '' : whatsapp}
              onChange={(e) => {
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
            <div
              className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 flex items-center gap-1 text-sm pointer-events-none"
              title="Nomor WhatsApp terkunci dari profil"
            >
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
            ⚠️ Daftar atau login untuk mendapatkan detail akun dikirimkan langsung ke WhatsApp kamu. Verifikasi WhatsApp Anda di{' '}
            <Link href="/profile" className="underline font-bold text-orange-500">
              Profil
            </Link>{' '}
            agar detail dikirim otomatis.
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
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Kosongkan jika tidak ada"
          className="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3.5 rounded-2xl text-sm font-bold text-gray-900 dark:text-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition outline-none"
        />
        <p className="text-[11px] text-gray-400 dark:text-gray-500 mt-1">
          *Opsional — isi jika kamu memerlukan salinan invoice / bukti pembelian dikirim ke email.
        </p>
      </div>

      {/* Field Catatan (Opsional) */}
      <div>
        <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
          Catatan (Opsional)
        </label>
        <input
          type="text"
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
          placeholder="Kosongkan jika tidak ada"
          className="w-full bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3 rounded-2xl text-xs font-bold text-gray-900 dark:text-white focus:border-orange-500 transition outline-none"
        />
      </div>

      {/* Kuantitas Pembelian */}
      <div className="bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-700/80 p-3.5 rounded-2xl">
        <div className="flex items-center justify-between mb-2">
          <label className="text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">
            Jumlah Pembelian
          </label>
          <span className="text-[10px] font-bold text-gray-400 dark:text-gray-500">
            {product.type === 'local' ? (
              product.stock > 0 ? (
                <span>Tersedia: <strong className="text-emerald-500 font-black">{product.stock} pcs</strong></span>
              ) : (
                <span className="text-red-500 font-bold">Stok Habis</span>
              )
            ) : (
              <span>Maksimal 100 pcs</span>
            )}
          </span>
        </div>
        <div className="flex items-center justify-between gap-3">
          <div className="flex items-center border-2 border-gray-200 dark:border-gray-700 rounded-2xl bg-white dark:bg-gray-900 p-1 shadow-sm">
            <button
              type="button"
              disabled={quantity <= 1}
              onClick={() => setQuantity && setQuantity(quantity - 1)}
              className="w-9 h-9 rounded-xl flex items-center justify-center font-black text-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
            >
              -
            </button>
            <input
              type="number"
              min={1}
              max={maxStock}
              value={quantity}
              onChange={(e) => {
                const val = parseInt(e.target.value, 10);
                if (setQuantity) {
                  if (isNaN(val) || val < 1) setQuantity(1);
                  else setQuantity(Math.min(val, maxStock));
                }
              }}
              className="w-14 text-center font-black text-base text-gray-900 dark:text-white bg-transparent outline-none focus:ring-0"
            />
            <button
              type="button"
              disabled={quantity >= maxStock}
              onClick={() => setQuantity && setQuantity(quantity + 1)}
              className="w-9 h-9 rounded-xl flex items-center justify-center font-black text-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 disabled:opacity-30 disabled:cursor-not-allowed transition cursor-pointer"
            >
              +
            </button>
          </div>
          <div className="text-right">
            <p className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Subtotal</p>
            <p className="text-base font-black text-gray-900 dark:text-white">{fmt.rp(subtotal || (unitPrice * quantity))}</p>
          </div>
        </div>
      </div>

      {/* Voucher */}
      <div>
        <label className="block text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
          Kode Voucher (Opsional)
        </label>
        <div className="flex gap-2">
          <input
            type="text"
            value={voucher}
            onChange={(e) => setVoucher(e.target.value.toUpperCase())}
            disabled={discountAmount > 0}
            placeholder="Ketik kode voucher..."
            className="flex-1 bg-white dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 p-3.5 rounded-2xl text-sm font-black text-gray-900 dark:text-white placeholder-gray-400 focus:border-orange-500 transition outline-none disabled:bg-gray-50 dark:disabled:bg-gray-700 disabled:text-orange-600"
          />
          {discountAmount === 0 ? (
            <button
              type="button"
              onClick={() => checkVoucher && checkVoucher(quantity)}
              disabled={!voucher || voucherLoading}
              className="px-4 bg-gray-900 dark:bg-gray-700 hover:bg-black text-white font-bold rounded-2xl transition cursor-pointer disabled:opacity-50"
            >
              {voucherLoading ? '...' : 'Pakai'}
            </button>
          ) : (
            <button
              type="button"
              onClick={() => {
                setVoucher('');
                setDiscountAmount(0);
                setDiscountName('');
              }}
              className="px-4 bg-red-100 hover:bg-red-200 text-red-600 font-bold rounded-2xl transition cursor-pointer"
            >
              Batal
            </button>
          )}
        </div>
      </div>

      {/* Ringkasan Harga */}
      <div className="pt-4 border-t border-gray-100 dark:border-gray-700">
        {isReseller && (
          <div className="mb-3 bg-gradient-to-r from-amber-500/15 to-yellow-500/15 border border-amber-500/30 p-2.5 rounded-xl flex items-center justify-between">
            <span className="text-xs font-bold text-amber-500 flex items-center gap-1.5">👑 Diskon Reseller VIP</span>
            <span className="text-xs font-black text-amber-500">
              Hemat {fmt.rp((product.price - product.reseller_price) * quantity)}
            </span>
          </div>
        )}
        <div className="flex justify-between text-sm mb-1">
          <span className="font-bold text-gray-400">Harga Satuan {isReseller ? 'Normal' : ''}</span>
          <span className={`font-bold ${isReseller ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300'}`}>
            {fmt.rp(product.price)}
          </span>
        </div>
        {isReseller && (
          <div className="flex justify-between text-sm mb-1">
            <span className="font-bold text-amber-500">Harga Satuan Reseller</span>
            <span className="font-bold text-amber-500">{fmt.rp(product.reseller_price)}</span>
          </div>
        )}
        {quantity > 1 && (
          <div className="flex justify-between text-sm mb-1">
            <span className="font-bold text-gray-400">Jumlah Beli</span>
            <span className="font-black text-gray-900 dark:text-white">{quantity} pcs</span>
          </div>
        )}
        {quantity > 1 && (
          <div className="flex justify-between text-sm mb-1">
            <span className="font-bold text-gray-400">Subtotal</span>
            <span className="font-bold text-gray-900 dark:text-white">{fmt.rp(subtotal || (unitPrice * quantity))}</span>
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
        <p className="text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
          Metode Pembayaran
        </p>
        <div className="space-y-2">
          {/* QRIS */}
          <button
            type="button"
            onClick={() => setPayMethod('qris')}
            className={`w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 transition cursor-pointer ${
              payMethod === 'qris'
                ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/20'
                : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600'
            }`}
          >
            <div
              className={`w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition ${
                payMethod === 'qris' ? 'border-orange-500' : 'border-gray-300 dark:border-gray-600'
              }`}
            >
              {payMethod === 'qris' && <div className="w-2.5 h-2.5 rounded-full bg-orange-500"></div>}
            </div>
            <div className="flex-1 text-left">
              <p className="font-black text-sm text-gray-900 dark:text-white">QRIS Otomatis</p>
              <p className="text-[10px] text-gray-400">E-Wallet, M-Banking — Instan</p>
            </div>
            <span className="text-[9px] font-black bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-0.5 rounded-full uppercase">
              Populer
            </span>
          </button>

          {/* SALDO */}
          {user && (
            <button
              type="button"
              onClick={() => setPayMethod('balance')}
              className={`w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 transition cursor-pointer ${
                payMethod === 'balance'
                  ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                  : 'border-gray-100 dark:border-gray-700 hover:border-gray-200 dark:hover:border-gray-600'
              } ${!canPayBalance ? 'opacity-60' : ''}`}
            >
              <div
                className={`w-5 h-5 rounded-full border-2 flex items-center justify-center shrink-0 transition ${
                  payMethod === 'balance' ? 'border-blue-500' : 'border-gray-300 dark:border-gray-600'
                }`}
              >
                {payMethod === 'balance' && <div className="w-2.5 h-2.5 rounded-full bg-blue-500"></div>}
              </div>
              <div className="flex-1 text-left">
                <p className="font-black text-sm text-gray-900 dark:text-white">Bayar dengan Saldo</p>
                <p className="text-[10px] text-gray-400">
                  Saldo kamu:{' '}
                  <span className={`font-bold ${canPayBalance ? 'text-green-600' : 'text-red-500'}`}>
                    {fmt.rp(userBalance)}
                  </span>
                </p>
              </div>
              {!canPayBalance && (
                <span className="text-[9px] font-black bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-2 py-0.5 rounded-full">
                  Kurang
                </span>
              )}
              {canPayBalance && (
                <span className="text-[9px] font-black bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 px-2 py-0.5 rounded-full">
                  Cukup ✓
                </span>
              )}
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
          {isProcessing ? (
            <>
              <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Memproses...
            </>
          ) : (
            'Bayar via QRIS →'
          )}
        </button>
      ) : (
        <button
          type="button"
          onClick={handleBalanceCheckout}
          disabled={isProcessing || !buyerName.trim() || !canPayBalance}
          className="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-4 rounded-2xl shadow-xl shadow-blue-500/25 transition active:scale-[0.98] flex justify-center items-center gap-2 disabled:opacity-60 cursor-pointer mt-2"
        >
          {isProcessing ? (
            <>
              <svg className="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              Memproses...
            </>
          ) : (
            `Bayar ${fmt.rp(finalPrice)} dari Saldo →`
          )}
        </button>
      )}

      {/* Link top up jika saldo kurang */}
      {user && payMethod === 'balance' && !canPayBalance && (
        <Link
          href="/profile"
          className="block text-center text-xs font-bold text-blue-500 hover:text-blue-600 hover:underline transition mt-1"
        >
          + Top Up Saldo dulu →
        </Link>
      )}

      {!user && (
        <p className="text-center text-xs text-gray-400 mt-1">
          <Link href="/login" className="text-orange-500 font-bold hover:underline">
            Login
          </Link>{' '}
          untuk membayar dengan saldo
        </p>
      )}
    </div>
  );
}

