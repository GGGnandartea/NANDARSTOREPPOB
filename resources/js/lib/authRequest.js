import axios from 'axios';

// Use a token from this application's session, not a potentially stale meta tag
// or an XSRF cookie shared by another application on the parent domain.
const client = axios.create({
    timeout: 20000,
    withCredentials: true,
    withXSRFToken: false,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
});

export async function submitAuth(url, payload, transport = client) {
    for (let attempt = 0; attempt < 2; attempt++) {
        const { data: session } = await transport.get('/auth/session', {
            params: { t: Date.now() },
            headers: { 'Cache-Control': 'no-cache' },
        });
        if (!session?.token) throw new Error('Invalid session response');

        try {
            // Laravel also accepts _token in JSON. Keep the fresh token intact
            // if a proxy or the global fetch wrapper replaces custom headers.
            const { data } = await transport.post(url, { ...payload, _token: session.token }, {
                headers: { 'X-CSRF-TOKEN': session.token },
            });
            if (!data?.redirect && data?.status !== 'reseller_payment_required') {
                throw new Error('Invalid authentication response');
            }
            return data;
        } catch (error) {
            // Only retry a rejected CSRF check; never replay a timed-out signup.
            if (error.response?.status !== 419 || attempt === 1) throw error;
        }
    }
}

export function authErrors(error) {
    const status = error.response?.status;
    if (status === 419) {
        return { general: 'Sesi keamanan tidak cocok. Muat ulang halaman dan coba lagi. Jika tetap gagal, hubungi admin untuk memeriksa konfigurasi sesi server.' };
    }
    if (error.response?.data?.errors) {
        return Object.fromEntries(Object.entries(error.response.data.errors)
            .map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]));
    }
    if (status === 429) return { general: 'Terlalu banyak percobaan. Tunggu sebentar sebelum mencoba lagi.' };
    if (error.code === 'ECONNABORTED' || error.code === 'ETIMEDOUT') {
        return { general: 'Server terlalu lama merespons. Jika sedang mendaftar, coba masuk dengan akun tersebut sebelum mendaftar ulang.' };
    }
    return { general: 'Permintaan belum berhasil. Periksa koneksi Anda atau coba lagi beberapa saat.' };
}
