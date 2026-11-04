@extends('layouts.admin')
@section('title', 'Gateway Payment (QRIS)')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="{
    pgStatus: 'checking',
    pgConnected: false,
    pgProtocol: @js($qrisProtocol ?? 'http'),
    pgHost: @js($qrisHost ?? '127.0.0.1'),
    pgPort: @js($qrisPort ?? '4061'),
    pgBaseUrl: @js($qrisGatewayUrl ?? 'http://127.0.0.1:4061'),
    pgLatency: 0,
    settingsModalOpen: false,
    checkInterval: null,

    get computedUrl() {
        const cleanHost = (this.pgHost || '127.0.0.1').trim().replace(/^https?:\/\//i, '').split('/')[0].split(':')[0] || '127.0.0.1';
        const cleanPort = String(this.pgPort || '4061').replace(/[^0-9]/g, '') || '4061';
        const proto = this.pgProtocol === 'https' ? 'https' : 'http';
        if ((cleanPort === '80' && proto === 'http') || (cleanPort === '443' && proto === 'https')) {
            return `${proto}://${cleanHost}`;
        }
        return `${proto}://${cleanHost}:${cleanPort}`;
    },

    async fetchStatus() {
        try {
            const res = await fetch('{{ route('admin.settings.payment.status', [], false) }}');
            if (res.ok) {
                const data = await res.json();
                this.pgStatus = data.status;
                this.pgConnected = Boolean(data.connected);
                this.pgLatency = data.latency_ms || 0;
                if (data.base_url) this.pgBaseUrl = data.base_url;
            } else {
                this.pgStatus = 'offline';
                this.pgConnected = false;
            }
        } catch (e) {
            this.pgStatus = 'offline';
            this.pgConnected = false;
        }
    },

    init() {
        this.fetchStatus();
        this.checkInterval = setInterval(() => {
            this.fetchStatus();
        }, 4000);
    }
}">

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ session('error') ?? $errors->first() }}</div>
        </div>
    @endif

    <!-- HEADER BANNER (PERSIS SEPERTI GATEWAY WA) -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Gateway Payment Server (QRIS)</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-blue text-white shadow-sm shadow-blue-500/20">
                    Node.js Service
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">
                Kelola koneksi IP Server &amp; Port Payment Gateway QRIS otomatis, pantau status real-time, dan uji koneksi endpoint pembayaran.
            </p>
        </div>

        <a href="{{ route('admin.wa_verifications', ['section' => 'gateway']) }}" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 hover:border-blue-500 transition flex items-center gap-2 self-start md:self-auto">
            <span>Buka Gateway WhatsApp Bot</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
        </a>
    </div>

    <!-- KARTU STATUS REAL-TIME SERVER PAYMENT GATEWAY (SEPERTI WA GATEWAY) -->
    <div class="admin-card p-6 relative overflow-hidden">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <div class="space-y-2.5 flex-1">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-xl shadow-md"
                         :class="{
                             'bg-emerald-500 text-white': pgConnected,
                             'bg-rose-500 text-white': pgStatus === 'offline',
                             'bg-gray-500 text-white animate-pulse': pgStatus === 'checking'
                         }">
                        <span x-show="pgConnected">🟢</span>
                        <span x-show="pgStatus === 'checking'">⏳</span>
                        <span x-show="pgStatus === 'offline'">🔴</span>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">
                                Payment Gateway QRIS Otomatis
                            </h2>
                            <template x-if="pgConnected">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>ONLINE &amp; AKTIF (<span x-text="pgLatency + ' ms'"></span>)</span>
                                </span>
                            </template>
                            <template x-if="pgStatus === 'checking'">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                                    MEMERIKSA KONEKSI...
                                </span>
                            </template>
                            <template x-if="pgStatus === 'offline'">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                                    SERVICE OFFLINE
                                </span>
                            </template>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">
                            Endpoint Aktif: <code class="font-mono font-bold text-blue-600 dark:text-blue-400" x-text="pgBaseUrl"></code>
                            (IP/Host: <code class="font-mono font-bold">{{ $qrisHost }}</code> • Port: <code class="font-mono font-bold">{{ $qrisPort }}</code>)
                        </p>
                    </div>
                </div>

                <template x-if="pgConnected">
                    <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 text-xs text-emerald-900 dark:text-emerald-300 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold">Status Server QRIS:</span>
                            <span class="font-mono font-black text-emerald-600 dark:text-emerald-400">{{ $qrisHost }}:{{ $qrisPort }} Siap Menerima Transaksi</span>
                        </div>
                        <p class="text-[11px] opacity-90">
                            ✓ Pembuatan kode QRIS Checkout Produk, PPOB, Top Up Saldo, dan Upgrade Reseller terhubung langsung secara otomatis (Durasi QRIS: 20 Menit).
                        </p>
                    </div>
                </template>

                <template x-if="pgStatus === 'offline'">
                    <div class="p-3.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 text-xs text-rose-900 dark:text-rose-300 space-y-1">
                        <p class="font-bold">⚠️ Service Payment Gateway Belum Merespons di {{ $qrisHost }}:{{ $qrisPort }}</p>
                        <p class="text-[11px] opacity-90">
                            Pastikan proses PM2 <code class="bg-rose-100 dark:bg-rose-900/50 px-1.5 py-0.5 rounded font-mono font-bold">pm2 restart payment_gateway</code> berjalan di VPS atau sesuaikan IP Server &amp; Port di formulir konfigurasi di bawah ini.
                        </p>
                    </div>
                </template>
            </div>

            <!-- Tombol Aksi Gateway -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full lg:w-auto">
                <form action="{{ route('admin.settings.payment.test') }}" method="POST">
                    @csrf
                    <button
                        type="submit"
                        class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition cursor-pointer flex items-center gap-1.5 shadow-sm"
                    >
                        <span>Uji Koneksi Server</span>
                    </button>
                </form>

                <button
                    type="button"
                    @click="settingsModalOpen = true"
                    class="px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold transition cursor-pointer"
                    title="Pengaturan Cepat URL Gateway"
                >
                    URL Gateway
                </button>

                <button
                    type="button"
                    @click="fetchStatus()"
                    class="px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold transition cursor-pointer"
                    title="Refresh Status"
                >
                    Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- KARTU KONFIGURASI IP SERVER & PORT PAYMENT GATEWAY -->
    <div class="admin-card overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-black/20 flex items-center justify-between gap-3.5 flex-wrap">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl gradient-blue text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                    🌐
                </div>
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Konfigurasi IP Server &amp; Port Payment Gateway</h3>
                    <p class="text-[11px] font-semibold text-gray-400">Atur alamat IP Server / Host dan Port service Node.js QRIS (Sama seperti konfigurasi WA Gateway)</p>
                </div>
            </div>
            <span class="text-[11px] font-mono font-bold px-3 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40" x-text="computedUrl"></span>
        </div>

        <form action="{{ route('admin.settings.payment.save') }}" method="POST" class="p-6 sm:p-8 space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
                <!-- Protokol -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Protokol</label>
                    <select
                        name="qris_gateway_protocol"
                        x-model="pgProtocol"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                    >
                        <option value="http">http://</option>
                        <option value="https">https://</option>
                    </select>
                </div>

                <!-- IP Server / Host -->
                <div class="sm:col-span-6">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">IP Server / Host / Domain</label>
                    <input
                        type="text"
                        name="qris_gateway_host"
                        x-model="pgHost"
                        placeholder="Contoh: 127.0.0.1 atau IP VPS Anda"
                        required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-[10px] text-gray-400 mt-1">Isi <code>127.0.0.1</code> jika berada di server/VPS yang sama, atau masukkan IP publik VPS Payment Gateway.</p>
                </div>

                <!-- Port Server -->
                <div class="sm:col-span-3">
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Port Server</label>
                    <input
                        type="number"
                        name="qris_gateway_port"
                        x-model="pgPort"
                        placeholder="4061"
                        min="1"
                        max="65535"
                        required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-[10px] text-gray-400 mt-1">Default: <code>4061</code></p>
                </div>
            </div>

            <!-- Preview URL Otomatis & Daftar Endpoint -->
            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200/80 dark:border-white/10 space-y-2.5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <span class="text-[11px] font-black uppercase tracking-wider text-gray-500">Preview Endpoint Payment Gateway Aktif</span>
                    <button
                        type="button"
                        @click="pgProtocol = 'http'; pgHost = '127.0.0.1'; pgPort = '4061'"
                        class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
                    >
                        Reset ke Default Lokal (127.0.0.1:4061)
                    </button>
                </div>
                <input type="hidden" name="qris_gateway_url" :value="computedUrl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5 text-[11px] font-mono">
                    <div class="p-2.5 rounded-xl bg-white dark:bg-black/30 border border-gray-200/70 dark:border-white/5">
                        <span class="text-[10px] font-sans font-bold text-gray-400 block mb-0.5">Endpoint Buat QRIS (POST):</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 break-all" x-text="computedUrl + '/api/order'"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white dark:bg-black/30 border border-gray-200/70 dark:border-white/5">
                        <span class="text-[10px] font-sans font-bold text-gray-400 block mb-0.5">Endpoint Cek Status (GET):</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 break-all" x-text="computedUrl + '/api/order/status/{invoice}'"></span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white dark:bg-black/30 border border-gray-200/70 dark:border-white/5">
                        <span class="text-[10px] font-sans font-bold text-gray-400 block mb-0.5">Webhook Callback Toko (POST):</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400 break-all">{{ url('/api/webhook/qris') }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-white/5">
                <button type="submit" class="gradient-blue text-white font-black py-3.5 px-8 rounded-xl shadow-lg shadow-blue-500/25 transition-all hover:opacity-90 cursor-pointer text-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan IP &amp; Port Payment Gateway</span>
                </button>
            </div>
        </form>
    </div>

    <!-- MODAL PENGATURAN CEPAT URL GATEWAY (PERSIS SEPERTI WA GATEWAY) -->
    <div
        x-show="settingsModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        @keydown.escape.window="settingsModalOpen = false"
    >
        <div
            class="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-white/10 space-y-4"
            @click.away="settingsModalOpen = false"
        >
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                <h3 class="font-black text-base text-gray-900 dark:text-white">Pengaturan URL Payment Gateway</h3>
                <button @click="settingsModalOpen = false" class="p-1 text-gray-400 hover:text-gray-600 text-lg cursor-pointer">✕</button>
            </div>

            <form action="{{ route('admin.settings.payment.save') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">URL Service Payment Gateway Node.js</label>
                    <input
                        type="url"
                        name="qris_gateway_url"
                        value="{{ $qrisGatewayUrl }}"
                        required
                        class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    <p class="text-[10px] text-gray-400 mt-1">Default lokal: <code>http://127.0.0.1:4061</code></p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="settingsModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl gradient-blue text-white text-xs font-black uppercase tracking-wider shadow-md hover:opacity-90 transition cursor-pointer">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
