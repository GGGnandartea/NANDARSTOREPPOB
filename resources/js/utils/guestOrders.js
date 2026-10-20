const STORAGE_KEY = 'nandar.guest-orders.v1';

export function orderLookup(input, origin = window.location.origin) {
    const value = String(input || '').trim();
    if (/^[A-Za-z0-9_-]+$/.test(value)) {
        return { orderId: value, statusUrl: `/checkout/status/${encodeURIComponent(value)}` };
    }
    const url = new URL(value, origin);
    const match = url.pathname.match(/^\/(?:order\/invoice|invoice)\/([A-Za-z0-9_-]+)$/);
    if (url.origin !== origin || !match || !/^[A-Za-z0-9]{64}$/.test(url.searchParams.get('token') || '')) {
        throw new Error('Masukkan nomor invoice atau tautan invoice pribadi dari toko ini.');
    }
    return { orderId: match[1], statusUrl: `/checkout/status/${encodeURIComponent(match[1])}?token=${encodeURIComponent(url.searchParams.get('token'))}` };
}

export function savedGuestOrders() {
    try {
        const entries = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        if (!Array.isArray(entries)) return [];
        return entries.filter(item => {
            try { return typeof item.invoice_url === 'string' && item.invoice_url.includes('token=') && orderLookup(item.invoice_url).orderId === item.order_id; }
            catch { return false; }
        }).slice(0, 100);
    } catch { return []; }
}

export function rememberGuestOrder(order) {
    try {
        if (!order?.invoice_url?.includes('token=')) return;
        const lookup = orderLookup(order.invoice_url);
        if (lookup.orderId !== order.order_id) return;
        // Save only the private receipt link and label, never delivered credentials.
        const item = { order_id: order.order_id, invoice_url: order.invoice_url,
            product_name: order.product_name || 'Pesanan Anda', created_at: order.placed_at || order.created_at || null };
        const entries = savedGuestOrders().filter(entry => entry.order_id !== item.order_id);
        localStorage.setItem(STORAGE_KEY, JSON.stringify([item, ...entries].slice(0, 100)));
    } catch { /* Private links remain usable when browser storage is unavailable. */ }
}
