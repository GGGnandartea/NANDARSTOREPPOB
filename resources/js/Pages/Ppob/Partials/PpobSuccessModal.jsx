import React, { useState, useEffect } from 'react';
import { Icons, fmt } from './PpobConstants';

export default function PpobSuccessModal({
  successOrder,
  onClose,
  onOrderUpdated,
  copyToClipboard,
  isCopied,
  user,
  adminWhatsapp = '6282317458496',
}) {
  const [isRechecking, setIsRechecking] = useState(false);
  const [recheckFeedback, setRecheckFeedback] = useState('');

  if (!successOrder) return null;

  const status = String(successOrder.order_status || successOrder.status || '').toLowerCase();
  const completed = ['completed', 'success', 'lunas'].includes(status);
  const failed = ['failed', 'refunded'].includes(status);
  const isPending = !completed && !failed;
  const isGuestBuyer = !user && !successOrder.is_refunded;

  const invoiceNumber = successOrder.invoice_number || successOrder.order_id || '';
  const invoiceUrl =
    successOrder.invoice_url ||
    successOrder.redirect_url ||
    (invoiceNumber ? `/order/invoice/${invoiceNumber}` : '/ppob');

  // Extract guest token from invoice_url if present so /ppob/check-status/{orderId}?token=... works for guests too
  const getGuestTokenParam = () => {
    try {
      if (successOrder.guest_access_token) return `?token=${encodeURIComponent(successOrder.guest_access_token)}`;
      if (invoiceUrl && invoiceUrl.includes('token=')) {
        const u = new URL(invoiceUrl, window.location.origin);
        const tk = u.searchParams.get('token');
        if (tk) return `?token=${encodeURIComponent(tk)}`;
      }
    } catch {
      // ignore URL parse error
    }
    return '';
  };

  const handleRecheckNow = async (silent = false) => {
    if (!invoiceNumber || isRechecking) return;
    if (!silent) {
      setIsRechecking(true);
      setRecheckFeedback('');
    }
    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const tokenQuery = getGuestTokenParam();
      const res = await fetch(`/ppob/check-status/${encodeURIComponent(invoiceNumber)}${tokenQuery}`, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (res.ok && data) {
        if (onOrderUpdated) {
          onOrderUpdated({
            ...successOrder,
            ...data,
          });
        }
        const newSt = String(data.order_status || '').toLowerCase();
        if (!silent) {
          if (['completed', 'success', 'lunas'].includes(newSt)) {
            setRecheckFeedback('✅ Transaksi telah BERHASIL diproses oleh operator!');
          } else if (['failed', 'refunded'].includes(newSt)) {
            setRecheckFeedback('❌ Transaksi dinyatakan GAGAL oleh operator.');
          } else {
            setRecheckFeedback(data.message || '⏳ Transaksi masih dalam antrean proses (Pending) di operator.');
          }
        }
      } else if (!silent) {
        setRecheckFeedback(data?.message || 'Belum dapat memuat status terbaru dari server.');
      }
    } catch {
      if (!silent) {
        setRecheckFeedback('Gagal menghubungi server. Silakan coba lagi beberapa saat.');
      }
    } finally {
      if (!silent) {
        setIsRechecking(false);
      }
    }
  };

  // Auto-poll every 8 seconds while modal is open and order is still pending
  useEffect(() => {
    setRecheckFeedback('');
    if (!invoiceNumber || !isPending) return;
    const timer = setInterval(() => {
      if (!document.hidden) {
        handleRecheckNow(true);
      }
    }, 8000);
    return () => clearInterval(timer);
  }, [invoiceNumber, isPending]);

  const cleanAdminWa = String(adminWhatsapp || '6282317458496').replace(/[^0-9]/g, '').replace(/^0/, '62');
  const fullInvoiceLink =
    typeof window !== 'undefined' && invoiceUrl.startsWith('/')
      ? `${window.location.origin}${invoiceUrl}`
      : invoiceUrl;
  const paidNominal = fmt.rp(successOrder.total_amount || successOrder.total_nominal || successOrder.price || 0);
  const guestRefundWaText = encodeURIComponent(
    `Halo Admin NandarStore, saya Pembeli Publik (Tanpa Akun) yang melakukan pembayaran via QRIS namun status transaksi PPOB saya GAGAL.\n\n` +
      `Berikut rincian pesanan saya:\n` +
      `• No. Invoice: #${invoiceNumber}\n` +
      `• Layanan: ${successOrder.product_name || 'Produk PPOB'}\n` +
      `• Nominal Lunas: ${paidNominal}\n` +
      `• Link Invoice: ${fullInvoiceLink}\n\n` +
      `Bersama pesan ini saya lampirkan bukti pembayaran QRIS & screenshot invoice untuk klaim pengembalian dana (Refund). Terima kasih.`
  );
  const guestRefundWaUrl = `https://wa.me/${cleanAdminWa}?text=${guestRefundWaText}`;

  const title = completed
    ? 'Transaksi Berhasil!'
    : failed
      ? isGuestBuyer
        ? 'Transaksi Gagal — Klaim Refund Admin'
        : 'Transaksi Gagal — Saldo Di-Refund'
      : 'Transaksi Pending';

  const description = completed
    ? 'Layanan PPOB Anda telah berhasil diproses ke nomor tujuan.'
    : failed
      ? isGuestBuyer
        ? 'Mohon maaf, transaksi gagal diproses oleh operator. Karena Anda membeli sebagai Pembeli Publik (Tanpa Login) via QRIS, silakan chat Admin dengan membawa bukti pembelian untuk pengembalian dana 100%.'
        : successOrder.message ||
          'Transaksi gagal diproses oleh operator. Saldo Anda telah dikembalikan 100% secara otomatis ke Saldo Akun.'
      : 'Pesanan sedang diproses (Pending) oleh operator. Anda dapat menekan tombol Cek Status Ulang di bawah ini kapan saja.';

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in">
      <div className="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-md p-6 text-center shadow-2xl space-y-4 max-h-[92dvh] overflow-y-auto">
        {completed ? (
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-500 to-green-500 text-white flex items-center justify-center font-black text-2xl mx-auto shadow-lg shadow-emerald-500/30">
            <Icons.Check className="w-8 h-8" />
          </div>
        ) : failed ? (
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-rose-500 to-red-600 text-white flex items-center justify-center font-black text-2xl mx-auto shadow-lg shadow-rose-500/30">
            <svg className="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </div>
        ) : (
          <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-amber-500 to-yellow-500 text-white flex items-center justify-center font-black text-2xl mx-auto shadow-lg shadow-amber-500/30">
            <Icons.Clock className="w-8 h-8" />
          </div>
        )}

        <div>
          <h3 className="text-xl font-black text-gray-900 dark:text-white">{title}</h3>
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">{description}</p>
        </div>

        {invoiceNumber && (
          <div className="py-2 px-3 bg-gray-50 dark:bg-white/5 rounded-xl border border-gray-200 dark:border-white/5 inline-flex items-center gap-2 text-xs">
            <span className="text-gray-400 font-medium">No. Invoice:</span>
            <span className="font-mono font-bold text-gray-800 dark:text-gray-200">{invoiceNumber}</span>
          </div>
        )}

        {/* KOTAK GAGAL: BEDAKAN MEMBER (AUTO REFUND SALDO) VS TAMU QRIS (CHAT ADMIN BAWA BUKTI) */}
        {failed && !isGuestBuyer && (
          <div className="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-left">
            <p className="text-xs font-bold text-rose-700 dark:text-rose-300">
              ↩ Dana sebesar {paidNominal} telah dikembalikan 100% ke Saldo Akun Anda dan tercatat di Mutasi Dana.
            </p>
          </div>
        )}

        {failed && isGuestBuyer && (
          <div className="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 text-left space-y-2.5">
            <p className="text-xs font-black uppercase tracking-wider text-rose-700 dark:text-rose-300">
              📌 Panduan Klaim Refund Pembeli Publik (QRIS):
            </p>
            <ul className="text-xs text-rose-900 dark:text-rose-200 space-y-1 font-medium list-disc pl-4">
              <li>Siapkan <strong>bukti pembayaran QRIS</strong> (mutasi E-Wallet / M-Banking).</li>
              <li>Simpan <strong>tautan / screenshot Invoice #{invoiceNumber}</strong>.</li>
              <li>Klik tombol WhatsApp Admin di bawah untuk pencairan refund 100% ke E-Wallet / Bank Anda.</li>
            </ul>
            <a
              href={guestRefundWaUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs flex items-center justify-center gap-2 shadow-md shadow-emerald-950/30 transition cursor-pointer"
            >
              <span>💬 Chat Admin &amp; Klaim Refund (Bawa Bukti Pembelian)</span>
            </a>
          </div>
        )}

        {/* KOTAK PENDING + TOMBOL CEK STATUS ULANG LANGSUNG DI MODAL */}
        {isPending && (
          <div className="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/40 text-left space-y-3">
            <p className="text-xs font-bold text-amber-800 dark:text-amber-300 leading-relaxed">
              ⏳ Status: Pending — Sedang dalam antrean pemrosesan operator. Jika transaksi selesai, SN/Token akan langsung tampil di sini.
            </p>
            <button
              type="button"
              onClick={() => handleRecheckNow(false)}
              disabled={isRechecking}
              className="w-full py-2.5 px-3.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-black text-xs flex items-center justify-center gap-2 shadow-sm transition cursor-pointer disabled:opacity-60"
            >
              <svg
                className={`w-4 h-4 ${isRechecking ? 'animate-spin' : ''}`}
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth="2.5"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
              <span>{isRechecking ? 'Mengecek ke Server Kiosbank...' : 'Cek Status Ulang Sekarang'}</span>
            </button>
            {recheckFeedback && (
              <p className="text-[11px] font-bold text-amber-900 dark:text-amber-200 bg-amber-100/80 dark:bg-amber-900/40 px-3 py-2 rounded-lg">
                {recheckFeedback}
              </p>
            )}
          </div>
        )}

        {completed && successOrder.sn_or_account && (
          <div className="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-left space-y-1.5">
            <p className="text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
              Nomor Seri (SN) / Kode Token Pengisian:
            </p>
            <div className="flex items-center justify-between gap-2">
              <span className="font-mono font-black text-sm text-gray-900 dark:text-white break-all">
                {successOrder.sn_or_account}
              </span>
              <button
                type="button"
                onClick={() => copyToClipboard(successOrder.sn_or_account)}
                className="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shrink-0 cursor-pointer transition"
              >
                {isCopied ? 'Tersalin' : 'Salin'}
              </button>
            </div>
          </div>
        )}

        <div className="space-y-2.5 pt-1">
          {user ? (
            <a
              href="/profile?tab=ppob"
              className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white dark:bg-emerald-600/95 dark:hover:bg-emerald-500 font-black text-xs sm:text-sm shadow-md shadow-emerald-950/40 border border-emerald-500/30 flex items-center justify-center gap-2 transition hover:scale-[1.01] active:scale-[0.99] cursor-pointer"
            >
              <span>Lihat Riwayat Transaksi PPOB</span>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
              </svg>
            </a>
          ) : (
            <a
              href={invoiceUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs sm:text-sm shadow-md shadow-emerald-950/40 border border-emerald-500/30 flex items-center justify-center gap-2 transition cursor-pointer"
            >
              <span>Buka &amp; Simpan Bukti Invoice Resmi</span>
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
              </svg>
            </a>
          )}

          <div className="flex gap-2">
            <a
              href={invoiceUrl}
              target="_blank"
              rel="noopener noreferrer"
              className="flex-1 py-2.5 px-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-800 dark:text-gray-200 font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
              </svg>
              <span>Cetak Invoice</span>
            </a>
            <button
              type="button"
              onClick={onClose}
              className="py-2.5 px-4 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-500 dark:text-gray-400 font-bold text-xs transition cursor-pointer"
            >
              Tutup
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
