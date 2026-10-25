import React, { useEffect, useState } from 'react';
import OrderProgress from '../../../Components/OrderProgress';
import { orderProgress } from '../../../utils/orderProgress';

export default function TrackOrderModal({
    showTrackModal, setShowTrackModal, trackOrderId, setTrackOrderId,
    handleTrackOrder, trackLoading, trackResult, setTrackResult,
}) {
    const [showAccount, setShowAccount] = useState(false);
    const [copyMessage, setCopyMessage] = useState('');
    const progress = trackResult?.order_id ? orderProgress(trackResult) : null;
    const canShowAccount = trackResult?.is_ready && progress?.step === 2 && !progress?.exception && trackResult?.sn_or_account;

    useEffect(() => { setShowAccount(false); setCopyMessage(''); }, [showTrackModal, trackResult?.order_id]);

    useEffect(() => {
        if (!showTrackModal || !trackResult?.order_id || orderProgress(trackResult).terminal) return;
        const controller = new AbortController();
        let busy = false;
        const timer = setInterval(async () => {
            if (busy || document.hidden) return;
            busy = true;
            try {
                const response = await fetch(`/checkout/status/${encodeURIComponent(trackResult.order_id)}`, { signal: controller.signal, headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    if (!controller.signal.aborted) setTrackResult({ status: 'NOT_FOUND' });
                    return;
                }
                const data = await response.json();
                if (!controller.signal.aborted) setTrackResult(data);
            } catch { /* The last known status remains visible during connection failures. */ }
            finally { busy = false; }
        }, 15000);
        return () => { clearInterval(timer); controller.abort(); };
    }, [showTrackModal, trackResult?.order_id, trackResult?.status, setTrackResult]);

    const copy = async (value, success) => {
        try { await navigator.clipboard.writeText(value); setCopyMessage(success); }
        catch { setCopyMessage('Tidak dapat menyalin otomatis. Buka invoice untuk menyalin secara manual.'); }
    };
    if (!showTrackModal) return null;

    return <div className="fixed inset-0 z-[110] flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-sm">
        <div role="dialog" aria-modal="true" aria-labelledby="track-order-title" className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-lg max-h-[90dvh] overflow-y-auto shadow-2xl border border-gray-100 dark:border-gray-700">
            <div className="sticky top-0 z-10 p-4 sm:p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-white dark:bg-gray-800">
                <h2 id="track-order-title" className="text-lg font-bold text-gray-900 dark:text-white">Cek Pesanan</h2>
                <button type="button" onClick={() => { setShowTrackModal(false); setTrackResult(null); setTrackOrderId(''); }} className="min-w-11 min-h-11 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-300 bg-gray-100 dark:bg-gray-700" aria-label="Tutup Riwayat Pesanan">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div className="p-4 sm:p-6 space-y-5">
                <p className="text-sm text-gray-600 dark:text-gray-300">Masukkan ID pesanan untuk melihat status dan detailnya. Untuk pesanan tanpa login di perangkat lain, gunakan tautan invoice pribadi.</p>
                <form onSubmit={handleTrackOrder} className="space-y-3">
                    <div>
                        <label htmlFor="track-order-input" className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">ID Pesanan atau Tautan Invoice Pribadi</label>
                        <input id="track-order-input" type="text" required value={trackOrderId} onChange={e => setTrackOrderId(e.target.value)} placeholder="Masukkan ID pesanan atau tautan invoice" className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white" />
                    </div>
                    <button type="submit" disabled={trackLoading} className="min-h-11 w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-3 rounded-xl disabled:opacity-60">{trackLoading ? 'Memuat pesanan...' : 'Lihat Detail Pesanan'}</button>
                </form>

                {trackResult && <div aria-live="polite">
                    {trackResult.order_id ? <>
                        <p className="text-xs text-gray-500 dark:text-gray-300 break-all">{trackResult.order_id}</p>
                        <h3 className="mt-1 text-sm font-bold text-gray-900 dark:text-white">{trackResult.product_name}</h3>
                        <OrderProgress order={trackResult} />
                        {trackResult.is_guest && ['failed', 'refunded'].includes(String(trackResult.order_status || trackResult.status || '').toLowerCase()) && (
                            <section className="mb-3 p-4 rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 space-y-2">
                                <h4 className="text-sm font-bold text-rose-800 dark:text-rose-200">Transaksi Gagal — Klaim Refund Pembeli Publik (QRIS)</h4>
                                <p className="text-xs text-rose-700 dark:text-rose-300 leading-relaxed">
                                    Karena Anda membeli tanpa login menggunakan QRIS, silakan hubungi WhatsApp Admin dengan membawa <strong>bukti pembayaran QRIS</strong> &amp; <strong>tautan invoice (#{trackResult.order_id})</strong> untuk proses pengembalian dana (Refund) 100%.
                                </p>
                                <a
                                    href={`https://wa.me/6282317458496?text=${encodeURIComponent(`Halo Admin PremiumKita, saya Pembeli Publik (Tanpa Akun) ingin klaim Refund pesanan QRIS yang gagal.\n• No. Invoice: #${trackResult.order_id}\n• Produk: ${trackResult.product_name || '-'}\n• Link Invoice: ${trackResult.invoice_url || ''}\nBersama pesan ini saya lampirkan bukti pembayaran QRIS.`)}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center min-h-10 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold"
                                >
                                    💬 Chat Admin &amp; Klaim Refund (Bawa Bukti Pembelian)
                                </a>
                            </section>
                        )}
                        {canShowAccount && <section className="mb-3 p-4 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30">
                            <h4 className="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Detail Akun / Serial Number</h4>
                            <div className="flex flex-wrap gap-2 mt-2">
                                <button type="button" onClick={() => setShowAccount(value => !value)} aria-expanded={showAccount} className="min-h-11 px-3 rounded-lg border border-emerald-300 dark:border-emerald-700 text-sm text-emerald-800 dark:text-emerald-200">{showAccount ? 'Sembunyikan Akun' : 'Tampilkan Akun'}</button>
                                <button type="button" onClick={() => copy(trackResult.sn_or_account, 'Detail akun berhasil disalin.')} className="min-h-11 px-3 rounded-lg bg-emerald-600 text-sm text-white">Salin Akun</button>
                            </div>
                            {showAccount && <pre className="mt-3 p-3 rounded-lg bg-white dark:bg-gray-900 whitespace-pre-wrap break-all text-sm leading-relaxed text-gray-900 dark:text-gray-100">{trackResult.sn_or_account}</pre>}
                        </section>}
                        {!progress.terminal && <p className="text-xs text-gray-600 dark:text-gray-300 mb-3">Detail akun akan tersedia setelah pesanan selesai. Status diperiksa setiap 15 detik.</p>}
                        <div className="flex flex-wrap gap-2">
                            {trackResult.invoice_url && <a href={trackResult.invoice_url} className="inline-flex items-center min-h-11 px-4 rounded-xl bg-orange-500 text-white text-sm font-semibold">Buka Invoice & Detail</a>}
                            {trackResult.is_guest && trackResult.invoice_url && <button type="button" onClick={() => copy(trackResult.invoice_url, 'Tautan invoice pribadi disalin. Simpan untuk membuka pesanan dari browser lain.')} className="min-h-11 px-3 rounded-xl border border-gray-200 dark:border-gray-600 text-sm text-gray-700 dark:text-gray-200">Salin Tautan Pribadi</button>}
                        </div>
                        {trackResult.is_guest && <p className="mt-3 text-xs text-gray-600 dark:text-gray-300">Simpan tautan pribadi Anda. Tautan ini dapat membuka detail akun dan tetap diperlukan jika data browser dihapus.</p>}
                        {copyMessage && <p role="status" className="mt-2 text-xs text-gray-700 dark:text-gray-200">{copyMessage}</p>}
                    </> : <p role="alert" className="text-sm text-red-600 dark:text-red-300">{trackResult.status === 'ERROR' ? 'Gagal memuat pesanan. Periksa nomor invoice, tautan, atau koneksi lalu coba lagi.' : 'Pesanan tidak dapat diakses. Gunakan browser saat membeli atau tempel tautan invoice pribadi Anda.'}</p>}
                </div>}
            </div>
        </div>
    </div>;
}
