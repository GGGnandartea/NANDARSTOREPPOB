import React from 'react';

export default function TrackOrderModal({
    showTrackModal,
    setShowTrackModal,
    trackOrderId,
    setTrackOrderId,
    handleTrackOrder,
    trackLoading,
    trackResult,
    setTrackResult,
}) {
    if (!showTrackModal) return null;

    return (
        <div className="fixed inset-0 z-[90] flex items-center justify-center p-4 pb-20 sm:pb-4 bg-black/60 backdrop-blur-sm transition-opacity">
            <div className="bg-white dark:bg-gray-800 rounded-3xl w-full max-w-md flex flex-col shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 dark:border-gray-700">
                <div className="p-5 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800/50">
                    <h3 className="text-lg font-black dark:text-white flex items-center gap-2">
                        <svg className="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Lacak Pesanan
                    </h3>
                    <button onClick={() => { setShowTrackModal(false); setTrackResult(null); setTrackOrderId(''); }} className="p-2 text-gray-400 hover:text-red-500 transition-colors bg-white dark:bg-gray-700 rounded-full shadow-sm cursor-pointer" aria-label="Tutup Modal Lacak">
                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div className="p-6">
                    <p className="mb-4 text-xs text-gray-500 dark:text-gray-400">
                        Masuk dengan akun pembelian untuk melacak invoice. Pembeli tamu dapat membuka tautan invoice pribadi yang disimpan saat checkout.
                    </p>
                    <form onSubmit={handleTrackOrder} className="flex flex-col gap-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1.5">Nomor Invoice / Order ID</label>
                            <input type="text" required value={trackOrderId} onChange={(e) => setTrackOrderId(e.target.value)} placeholder="Contoh: INV-123456" className="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-orange-500 text-sm font-bold dark:text-white transition-all outline-none" />
                        </div>
                        <button type="submit" disabled={trackLoading} className="w-full bg-orange-500 hover:bg-orange-600 text-white font-black py-3 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed cursor-pointer">
                            {trackLoading ? (
                                <svg className="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            ) : (
                                <>Cek Status Sekarang <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg></>
                            )}
                        </button>
                    </form>

                    {trackResult && (
                        <div className={`mt-5 p-4 rounded-xl border flex flex-col items-center justify-center text-center gap-2 ${
                            trackResult === 'SUCCESS' ? 'bg-green-50/50 dark:bg-green-900/10 border-green-200 dark:border-green-800' : 
                            trackResult === 'PENDING' ? 'bg-yellow-50/50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800' : 
                            'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800'
                        }`}>
                            <span className="text-xs font-bold text-gray-500 dark:text-gray-400">Status Pesanan:</span>
                            {trackResult === 'SUCCESS' && (
                                <div className="space-y-2 mt-1">
                                    <span className="text-green-600 dark:text-green-400 font-black text-xl uppercase tracking-widest block">Lunas &amp; Selesai ✓</span>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">Pesanan telah berhasil diproses oleh sistem.</p>
                                    <a 
                                        href={`/order/invoice/${trackOrderId}`} 
                                        target="_blank" 
                                        rel="noreferrer"
                                        className="inline-flex items-center gap-1.5 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white font-extrabold text-xs rounded-xl shadow-md transition-colors mt-2"
                                    >
                                        <span>Buka Detail Akun &amp; Invoice PDF</span>
                                        <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            )}
                            {trackResult === 'PENDING' && <span className="text-yellow-600 dark:text-yellow-400 font-black text-xl uppercase tracking-widest">Tertunda (Pending)</span>}
                            {(trackResult !== 'SUCCESS' && trackResult !== 'PENDING') && <span className="text-red-600 dark:text-red-400 font-black text-lg uppercase tracking-widest">Tidak Ditemukan / Gagal</span>}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

