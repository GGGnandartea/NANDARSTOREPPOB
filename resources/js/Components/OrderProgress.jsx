import React from 'react';
import { orderProgress } from '../utils/orderProgress';

const formatTime = value => {
    const date = new Date(value);
    return value && !Number.isNaN(date.getTime())
        ? `${date.toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })} WIB`
        : null;
};
export default function OrderProgress({ order }) {
    if (!order || typeof order !== 'object') return null;
    const { step, exception, isPpob } = orderProgress(order);
    const updated = formatTime(order.updated_at);
    const created = formatTime(order.placed_at || order.created_at);
    const steps = isPpob
        ? ['Menunggu pembayaran', 'Pending', 'Berhasil']
        : ['Menunggu pembayaran', 'Diproses', 'Selesai'];
    return <section aria-label="Proses pesanan" className="my-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-3 text-left">
        <ol className="grid grid-cols-3 gap-2">
            {steps.map((label, index) => <li key={label} aria-current={!exception && index === step ? 'step' : undefined} className={`text-xs leading-relaxed ${index <= step && !exception ? (isPpob && step === 1 && index === 1 ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300') : 'text-gray-500 dark:text-gray-400'}`}>
                <div className={`h-1 rounded-full mb-2 ${index <= step && !exception ? (isPpob && step === 1 && index === 1 ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-gray-200 dark:bg-gray-700'}`} />
                <span className="font-semibold">{index + 1}. {label}</span>
                {!exception && index === step && <span className="block text-[11px]">{step === 2 ? (isPpob ? 'Transaksi berhasil' : 'Pesanan selesai') : (isPpob && step === 1 ? 'Sedang diproses operator' : 'Status saat ini')}</span>}
            </li>)}
        </ol>
        {exception && <p role="status" className="mt-2 text-sm font-semibold text-rose-600 dark:text-rose-300">{exception}</p>}
        {created && <p className="mt-3 text-[11px] text-gray-600 dark:text-gray-300">Dibuat: {created}</p>}
        <p className="mt-1 text-[11px] text-gray-600 dark:text-gray-300">{updated ? `Pembaruan pesanan terakhir: ${updated}` : 'Waktu pembaruan belum tersedia'}</p>
    </section>;
}
