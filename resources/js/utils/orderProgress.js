export function orderProgress(order) {
    const status = String(order.order_status || order.status || '').toLowerCase();
    const payment = String(order.payment_status || '').toLowerCase();
    const stopped = {
        refunded: 'Dana dikembalikan', refund: 'Dana dikembalikan',
        expired: 'Pembayaran kedaluwarsa', failed: 'Pesanan gagal', failure: 'Pesanan gagal',
        canceled: 'Pesanan dibatalkan', cancelled: 'Pesanan dibatalkan', rejected: 'Pesanan ditolak',
    };
    const exception = stopped[status] || stopped[payment];
    const paid = ['paid', 'success', 'settlement', 'lunas'].includes(payment) || status === 'paid';
    const complete = ['completed', 'success', 'lunas'].includes(status);
    return { step: exception ? (paid ? 1 : 0) : complete ? 2 : paid ? 1 : 0, exception, terminal: Boolean(exception || complete) };
}
