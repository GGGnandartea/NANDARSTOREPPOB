export function orderProgress(order = {}) {
    const safeOrder = order && typeof order === 'object' ? order : {};
    const status = String(safeOrder.order_status || safeOrder.status || '').toLowerCase();
    const payment = String(safeOrder.payment_status || '').toLowerCase();
    const isPpob = safeOrder.product_type === 'ppob' || String(safeOrder.invoice_number || safeOrder.order_id || '').startsWith('PPOB-');
    const paid = ['paid', 'success', 'settlement', 'lunas'].includes(payment) || status === 'paid';
    const stopped = {
        refunded: isPpob ? 'Transaksi gagal — Dana telah dikembalikan (refund) otomatis ke Saldo Akun' : 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun',
        refund: isPpob ? 'Transaksi gagal — Dana telah dikembalikan (refund) otomatis ke Saldo Akun' : 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun',
        expired: 'Pembayaran kedaluwarsa',
        failed: paid ? 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun' : 'Dibatalkan oleh server',
        failure: paid ? 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun' : 'Dibatalkan oleh server',
        canceled: paid ? 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun' : 'Dibatalkan oleh server',
        cancelled: paid ? 'Dibatalkan oleh server — Dana telah dikembalikan 100% ke Saldo Akun' : 'Dibatalkan oleh server',
        rejected: 'Dibatalkan oleh server',
    };
    const exception = stopped[status] || stopped[payment];
    const complete = ['completed', 'success', 'lunas'].includes(status);
    return { step: exception ? (paid ? 1 : 0) : complete ? 2 : paid ? 1 : 0, exception, terminal: Boolean(exception || complete), isPpob };
}
