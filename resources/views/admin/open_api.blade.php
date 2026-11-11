@extends('layouts.admin')

@section('title', 'Konfigurasi Open API Reseller (H2H)')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-20">

    <!-- Header & Master Switch -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500/15 border border-indigo-500/30 flex items-center justify-center text-indigo-500 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight">Open API H2H Khusus VIP Reseller</h2>
                        @if($cfg['enabled'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                ONLINE &amp; SIAP INTEGRASI ({{ $stats['eligible_total'] }} Produk Aplikasi)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                MODE MAINTENANCE / DINONAKTIFKAN
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        Endpoint integrasi website mitra VIP Reseller (<code class="text-indigo-500 font-bold">{{ url('/api/v1') }}</code>). Pembayaran 100% memotong <strong>Saldo Akun Reseller</strong> secara atomik. Cakupan dikunci ketat hanya untuk <strong>Aplikasi Premium Lokal, Premku, dan Preflix</strong> (User biasa, PPOB Kiosbank, dan Bot Telegram ditolak otomatis).
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <form action="{{ route('admin.open_api.toggle') }}" method="POST">
                    @csrf
                    @if($cfg['enabled'])
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            Matikan Sementara Open API
                        </button>
                    @else
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            Aktifkan Layanan Open API
                        </button>
                    @endif
                </form>

                <a href="{{ route('admin.resellers') }}" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-200 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    Data Reseller
                </a>
            </div>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Mitra Integrasi API</p>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['active_clients']) }} <span class="text-xs font-bold text-gray-400">/ {{ number_format($stats['total_resellers']) }} Reseller</span></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hanya akun berstatus VIP Reseller aktif</p>
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Katalog Aplikasi Terbuka</p>
            <p class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ number_format($stats['eligible_total']) }} Produk</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Lokal: {{ $stats['local_auto_count'] + $stats['local_manual_count'] }} • Premku: {{ $stats['premku_count'] }} • Preflix: {{ $stats['preflix_count'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Total Transaksi Open API</p>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">Rp {{ number_format($stats['total_api_revenue'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ number_format($stats['total_api_orders']) }} pesanan via Saldo Reseller</p>
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Trafik Request (24 Jam)</p>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ number_format($stats['requests_24h']) }} Hits</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Batas Default: {{ $cfg['rate_limit_rpm'] }} req/menit</p>
        </div>
    </div>

    <!-- Form Standar Keamanan & Konfigurasi Open API -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="border-b border-gray-100 dark:border-white/5 pb-4 mb-5">
            <h3 id="open-api-config-heading" class="text-base sm:text-lg font-black text-gray-900 dark:text-white">Standar Keamanan &amp; Konfigurasi Cakupan Open API</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Atur kebijakan tanda tangan kriptografi HMAC-SHA256, batas frekuensi request (Rate Limiting), kewajiban IP Whitelist, dan filter sumber produk Aplikasi Premium.
            </p>
        </div>

        <form action="{{ route('admin.open_api.save') }}" method="POST" aria-labelledby="open-api-config-heading" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Kolom Kiri: Standar Keamanan API -->
                <fieldset class="border border-gray-200 dark:border-white/10 rounded-2xl p-4 sm:p-5 space-y-4">
                    <legend class="px-2 text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        1. Standar Keamanan Autentikasi (API Security)
                    </legend>

                    <div class="space-y-2.5">
                        <p class="text-xs font-extrabold text-gray-800 dark:text-gray-200">Mode Verifikasi Kriptografi Request</p>

                        <div class="flex items-start gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-white/[0.02]">
                            <input type="radio" id="auth_mode_strict" name="auth_mode" value="hmac_strict" class="mt-1 accent-indigo-600" {{ $cfg['auth_mode'] === 'hmac_strict' ? 'checked' : '' }}>
                            <label for="auth_mode_strict" class="text-xs cursor-pointer">
                                <span class="font-black text-gray-900 dark:text-white block">HMAC-SHA256 Ketat di Semua Endpoint (Direkomendasikan)</span>
                                <span class="text-gray-500 dark:text-gray-400">Wajib menyertakan <code class="font-bold">X-API-Key</code>, <code class="font-bold">X-Timestamp</code>, dan <code class="font-bold">X-Signature</code> (Anti-Replay &amp; Anti-Tamper). Secret Key tidak pernah dikirim lewat jaringan.</span>
                            </label>
                        </div>

                        <div class="flex items-start gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-white/[0.02]">
                            <input type="radio" id="auth_mode_order" name="auth_mode" value="hmac_order_only" class="mt-1 accent-indigo-600" {{ $cfg['auth_mode'] === 'hmac_order_only' ? 'checked' : '' }}>
                            <label for="auth_mode_order" class="text-xs cursor-pointer">
                                <span class="font-black text-gray-900 dark:text-white block">HMAC-SHA256 Wajib Saat Order, Header Secret untuk Cek Katalog</span>
                                <span class="text-gray-500 dark:text-gray-400">Endpoint <code class="font-bold">POST /api/v1/orders</code> wajib HMAC-SHA256, sedangkan cek saldo/produk boleh memakai <code class="font-bold">X-API-Secret</code>.</span>
                            </label>
                        </div>

                        <div class="flex items-start gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50/70 dark:bg-white/[0.02]">
                            <input type="radio" id="auth_mode_secret" name="auth_mode" value="key_secret" class="mt-1 accent-indigo-600" {{ $cfg['auth_mode'] === 'key_secret' ? 'checked' : '' }}>
                            <label for="auth_mode_secret" class="text-xs cursor-pointer">
                                <span class="font-black text-gray-900 dark:text-white block">Kompatibilitas Dasar (HMAC-SHA256 atau Header X-API-Secret)</span>
                                <span class="text-gray-500 dark:text-gray-400">Memudahkan pengujian awal website mitra, namun tetap disarankan beralih ke HMAC-SHA256 saat produksi.</span>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                        <div>
                            <label for="timestamp_tolerance" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Toleransi Timestamp (Detik)</label>
                            <input type="number" id="timestamp_tolerance" name="timestamp_tolerance" min="30" max="3600" required value="{{ old('timestamp_tolerance', $cfg['timestamp_tolerance']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-sm font-bold text-gray-900 dark:text-white">
                            <p class="text-[11px] text-gray-400 mt-1">Default: 300 dtk (5 menit)</p>
                        </div>

                        <div>
                            <label for="rate_limit_rpm" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Rate Limit Umum (RPM)</label>
                            <input type="number" id="rate_limit_rpm" name="rate_limit_rpm" min="10" max="1000" required value="{{ old('rate_limit_rpm', $cfg['rate_limit_rpm']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-sm font-bold text-gray-900 dark:text-white">
                            <p class="text-[11px] text-gray-400 mt-1">Req/menit per Reseller</p>
                        </div>

                        <div>
                            <label for="order_rate_limit_rpm" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Rate Limit Order (RPM)</label>
                            <input type="number" id="order_rate_limit_rpm" name="order_rate_limit_rpm" min="5" max="300" required value="{{ old('order_rate_limit_rpm', $cfg['order_rate_limit_rpm']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-sm font-bold text-gray-900 dark:text-white">
                            <p class="text-[11px] text-gray-400 mt-1">Order/menit per Reseller</p>
                        </div>
                    </div>

                    <div class="space-y-2.5 pt-1">
                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" id="require_ip_whitelist" name="require_ip_whitelist" value="1" class="w-4 h-4 accent-indigo-600 rounded" {{ $cfg['require_ip_whitelist'] ? 'checked' : '' }}>
                            <label for="require_ip_whitelist" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer">
                                Wajibkan semua Reseller mendaftarkan IP Server pada IP Whitelist
                            </label>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" id="webhook_enabled" name="webhook_enabled" value="1" class="w-4 h-4 accent-indigo-600 rounded" {{ $cfg['webhook_enabled'] ? 'checked' : '' }}>
                            <label for="webhook_enabled" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer">
                                Aktifkan pengiriman Webhook Callback otomatis (Signed <code class="font-bold">X-PK-Signature</code>)
                            </label>
                        </div>
                    </div>
                </fieldset>

                <!-- Kolom Kanan: Cakupan Produk & Transaksi Saldo -->
                <fieldset class="border border-gray-200 dark:border-white/10 rounded-2xl p-4 sm:p-5 space-y-4">
                    <legend class="px-2 text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                        2. Cakupan Produk Aplikasi Premium &amp; Aturan Order
                    </legend>

                    <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/40 text-xs text-amber-800 dark:text-amber-300 font-semibold">
                        Sistem mengunci cakupan Open API agar <strong>hanya membawa Produk Aplikasi Premium</strong> dari sumber di bawah ini. Produk PPOB Kiosbank dan Telegram Bot API tidak pernah diekspos ke Open API.
                    </div>

                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-gray-200 dark:border-white/10">
                            <input type="checkbox" id="allow_local" name="allow_local" value="1" class="w-4 h-4 accent-emerald-600 rounded" {{ $cfg['allow_local'] ? 'checked' : '' }}>
                            <label for="allow_local" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer flex-1">
                                Izinkan Produk Lokal Otomatis ({{ $stats['local_auto_count'] }} produk aktif)
                            </label>
                        </div>

                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-gray-200 dark:border-white/10">
                            <input type="checkbox" id="allow_local_manual" name="allow_local_manual" value="1" class="w-4 h-4 accent-emerald-600 rounded" {{ $cfg['allow_local_manual'] ? 'checked' : '' }}>
                            <label for="allow_local_manual" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer flex-1">
                                Izinkan Produk Lokal Proses Manual Admin ({{ $stats['local_manual_count'] }} produk aktif)
                            </label>
                        </div>

                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-gray-200 dark:border-white/10">
                            <input type="checkbox" id="allow_premku" name="allow_premku" value="1" class="w-4 h-4 accent-emerald-600 rounded" {{ $cfg['allow_premku'] ? 'checked' : '' }}>
                            <label for="allow_premku" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer flex-1">
                                Izinkan Produk Aplikasi Provider Premku ({{ $stats['premku_count'] }} produk aktif)
                            </label>
                        </div>

                        <div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-gray-200 dark:border-white/10">
                            <input type="checkbox" id="allow_preflix" name="allow_preflix" value="1" class="w-4 h-4 accent-emerald-600 rounded" {{ $cfg['allow_preflix'] ? 'checked' : '' }}>
                            <label for="allow_preflix" class="text-xs font-bold text-gray-800 dark:text-gray-200 cursor-pointer flex-1">
                                Izinkan Produk Aplikasi Provider Preflix ({{ $stats['preflix_count'] }} produk aktif)
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                        <div>
                            <label for="max_qty_per_order" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Maks. Qty per Transaksi API</label>
                            <input type="number" id="max_qty_per_order" name="max_qty_per_order" min="1" max="50" required value="{{ old('max_qty_per_order', $cfg['max_qty_per_order']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-sm font-bold text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label for="extra_fee" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Biaya Tambahan H2H / Pcs (Rp)</label>
                            <input type="number" id="extra_fee" name="extra_fee" min="0" max="100000" required value="{{ old('extra_fee', $cfg['extra_fee']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-sm font-bold text-gray-900 dark:text-white">
                            <p class="text-[11px] text-gray-400 mt-1">Isi 0 agar murni Harga VIP Reseller</p>
                        </div>
                    </div>

                    <div>
                        <label for="maintenance_message" class="block text-xs font-extrabold text-gray-700 dark:text-gray-200 mb-1">Pesan Saat Mode Maintenance Open API</label>
                        <input type="text" id="maintenance_message" name="maintenance_message" maxlength="250" value="{{ old('maintenance_message', $cfg['maintenance_message']) }}" class="w-full px-3 py-2.5 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-xs font-semibold text-gray-900 dark:text-white">
                    </div>

                    <div class="flex items-center gap-2.5 pt-1">
                        <input type="checkbox" id="enabled_switch" name="enabled" value="1" class="w-4 h-4 accent-emerald-600 rounded" {{ $cfg['enabled'] ? 'checked' : '' }}>
                        <label for="enabled_switch" class="text-xs font-black text-emerald-700 dark:text-emerald-400 cursor-pointer">
                            Status Global Open API Aktif (Online)
                        </label>
                    </div>
                </fieldset>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs sm:text-sm font-black shadow-lg shadow-indigo-500/20 transition cursor-pointer">
                    Simpan Konfigurasi Open API
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Klien Open API Reseller -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 id="admin-clients-caption" class="text-base sm:text-lg font-black text-gray-900 dark:text-white">Daftar Kredensial Mitra Reseller API ({{ $clients->count() }})</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Kelola status akses, batas RPM khusus, reset IP Whitelist, atau cabut kredensial API per mitra Reseller.</p>
            </div>

            <search>
                <form action="{{ route('admin.open_api.index') }}" method="GET" class="flex items-center gap-2">
                    <label for="search_client" class="sr-only">Cari Reseller atau API Key</label>
                    <input type="search" id="search_client" name="search" value="{{ $search }}" placeholder="Cari nama, email, IP, API key..." autocorrect="off" autocapitalize="none" spellcheck="false" class="px-3.5 py-2 rounded-xl border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-xs font-semibold text-gray-900 dark:text-white w-56 sm:w-64">
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-gray-900 dark:bg-white/10 text-white text-xs font-extrabold cursor-pointer">Cari</button>
                </form>
            </search>
        </div>

        <div tabindex="0" role="region" aria-labelledby="admin-clients-caption" class="overflow-auto max-h-[520px] rounded-2xl border border-gray-200/80 dark:border-white/10">
            <table class="w-full text-left border-separate border-spacing-0 text-xs">
                <thead class="sticky top-0 z-10 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-300 uppercase text-[11px] font-black">
                    <tr>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Mitra Reseller &amp; Saldo</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">API Key &amp; Secret</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">IP Whitelist &amp; Webhook</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Trafik &amp; Order</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Tindakan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($clients as $cl)
                        @php
                            $u = $cl->user;
                            $ips = $cl->parsedIpWhitelist();
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                            <th scope="row" class="px-4 py-3.5 font-bold text-gray-900 dark:text-white align-top whitespace-nowrap bg-white dark:bg-[#111827]">
                                <div>{{ $u?->name ?? 'User Terhapus' }}</div>
                                <div class="text-[11px] font-normal text-gray-500">{{ $u?->email ?? '-' }}</div>
                                <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded-md bg-amber-500/15 text-amber-700 dark:text-amber-300 text-[11px] font-black">
                                    Saldo: Rp {{ number_format((int) ($u?->balance ?? 0), 0, ',', '.') }}
                                </div>
                            </th>
                            <td class="px-4 py-3.5 align-top whitespace-nowrap font-mono text-[11px]">
                                <div class="font-bold text-indigo-600 dark:text-indigo-400">{{ $cl->api_key }}</div>
                                <div class="text-gray-400 mt-0.5">Secret: {{ $cl->secret_key_hint }}</div>
                                @if($cl->rotated_at)
                                    <div class="text-[10px] text-gray-400 mt-0.5 font-sans">Rotasi: {{ $cl->rotated_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top">
                                @if(count($ips) > 0)
                                    <span class="inline-block px-2 py-0.5 rounded bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 font-bold text-[11px]">
                                        {{ count($ips) }} IP Terdaftar
                                    </span>
                                    <div class="text-[11px] text-gray-500 font-mono mt-1 max-w-[200px] truncate" title="{{ implode(', ', $ips) }}">{{ implode(', ', $ips) }}</div>
                                @else
                                    <span class="text-gray-400 text-[11px]">Semua IP (Belum dibatasi)</span>
                                @endif
                                @if($cl->webhook_url)
                                    <div class="text-[11px] text-sky-600 dark:text-sky-400 mt-1 max-w-[210px] truncate" title="{{ $cl->webhook_url }}">WH: {{ $cl->webhook_url }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                <div class="font-bold text-gray-800 dark:text-gray-200">{{ number_format($cl->total_orders) }} Order • {{ number_format($cl->total_requests) }} Hits</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    IP Terakhir: {{ $cl->last_used_ip ?: '-' }}
                                </div>
                                <div class="text-[11px] text-gray-400">
                                    Limit: {{ $cl->custom_rate_limit ? ($cl->custom_rate_limit . ' RPM (Khusus)') : ($cfg['rate_limit_rpm'] . ' RPM (Default)') }}
                                </div>
                            </td>
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                @if(!$u || $u->role !== 'reseller')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Bukan Reseller</span>
                                @elseif($cl->admin_suspended)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">Suspended Admin</span>
                                @elseif(!$cl->is_enabled)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/30">Dijeda User</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">Aktif</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 align-top whitespace-nowrap">
                                @if($u)
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <form action="{{ route('admin.open_api.clients.suspend', $u->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[11px] font-extrabold cursor-pointer {{ $cl->admin_suspended ? 'bg-emerald-600 text-white hover:bg-emerald-500' : 'bg-amber-500/20 text-amber-700 dark:text-amber-300 hover:bg-amber-500/30' }}">
                                                {{ $cl->admin_suspended ? 'Aktifkan' : 'Suspend' }}
                                            </button>
                                        </form>

                                        @if(count($ips) > 0)
                                            <form action="{{ route('admin.open_api.clients.clear_ip', $u->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[11px] font-extrabold bg-sky-500/15 text-sky-600 dark:text-sky-400 hover:bg-sky-500/25 cursor-pointer">
                                                    Reset IP
                                                </button>
                                            </form>
                                        @endif

                                        <form action="{{ route('admin.open_api.clients.revoke', $u->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg text-[11px] font-extrabold bg-rose-500/15 text-rose-600 dark:text-rose-400 hover:bg-rose-500/25 cursor-pointer">
                                                Cabut Key
                                            </button>
                                        </form>
                                    </div>

                                    <form action="{{ route('admin.open_api.clients.rate_limit', $u->id) }}" method="POST" class="mt-2 flex items-center gap-1.5">
                                        @csrf
                                        <label for="rpm_user_{{ $u->id }}" class="sr-only">Custom RPM {{ $u->name }}</label>
                                        <input type="number" id="rpm_user_{{ $u->id }}" name="custom_rate_limit" min="5" max="2000" value="{{ $cl->custom_rate_limit }}" placeholder="Custom RPM" class="w-28 px-2 py-1 rounded-lg border border-gray-300 dark:border-white/15 bg-white dark:bg-slate-900 text-[11px] font-bold">
                                        <button type="submit" class="px-2.5 py-1 rounded-lg bg-gray-800 dark:bg-white/10 text-white text-[11px] font-bold cursor-pointer">Set RPM</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 font-semibold">
                                Belum ada mitra VIP Reseller yang membuat kredensial Open API.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Log Audit Request Open API Terbaru -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 id="admin-logs-caption" class="text-base sm:text-lg font-black text-gray-900 dark:text-white">Log Audit Request Open API Terbaru</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Memantau autentikasi, alamat IP pemanggil, kode error keamanan, dan latensi respons secara realtime.</p>
            </div>

            @if($recentLogs->count() > 0)
                <form action="{{ route('admin.open_api.logs.clear') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 text-rose-600 dark:text-rose-400 text-xs font-extrabold cursor-pointer">
                        Bersihkan Log Audit
                    </button>
                </form>
            @endif
        </div>

        <div tabindex="0" role="region" aria-labelledby="admin-logs-caption" class="overflow-auto max-h-[460px] rounded-2xl border border-gray-200/80 dark:border-white/10">
            <table class="w-full text-left border-separate border-spacing-0 text-xs">
                <thead class="sticky top-0 z-10 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-300 uppercase text-[11px] font-black">
                    <tr>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Waktu (WIB)</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Reseller</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Endpoint</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">IP Server</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">HTTP</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-white/10 whitespace-nowrap">Ref / Invoice &amp; Ringkasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($recentLogs as $log)
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                            <th scope="row" class="px-4 py-2.5 font-mono text-[11px] text-gray-600 dark:text-gray-300 whitespace-nowrap bg-white dark:bg-[#111827]">
                                {{ $log->created_at?->timezone('Asia/Jakarta')->format('d/m H:i:s') }}
                                <span class="text-gray-400">({{ $log->response_time_ms }}ms)</span>
                            </th>
                            <td class="px-4 py-2.5 whitespace-nowrap font-bold text-gray-800 dark:text-gray-200">
                                {{ $log->user?->name ?? 'Unauthenticated' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap font-mono text-[11px]">
                                <span class="font-black {{ $log->method === 'POST' ? 'text-amber-600 dark:text-amber-400' : 'text-indigo-600 dark:text-indigo-400' }}">{{ $log->method }}</span>
                                <span class="text-gray-700 dark:text-gray-300 ml-1">{{ $log->endpoint }}</span>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap font-mono text-[11px] text-gray-500">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded font-black text-[11px] {{ $log->status_code < 400 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/15 text-rose-600 dark:text-rose-400' }}">
                                    {{ $log->status_code }}
                                </span>
                                @if($log->error_code)
                                    <span class="ml-1 font-mono text-[10px] text-rose-500">{{ $log->error_code }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-[11px] text-gray-600 dark:text-gray-300 max-w-md truncate" title="{{ $log->summary }}">
                                @if($log->invoice_number)
                                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">[{{ $log->invoice_number }}]</span>
                                @endif
                                @if($log->ref_id)
                                    <span class="font-mono text-indigo-500">(Ref: {{ $log->ref_id }})</span>
                                @endif
                                {{ $log->summary }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400 font-semibold">
                                Belum ada aktivitas request pada endpoint <code class="font-mono">/api/v1/*</code>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
