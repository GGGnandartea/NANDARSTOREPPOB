@extends('layouts.admin')

@section('title', 'Pengaturan Sistem & API')

@section('content')
<div class="max-w-6xl mx-auto space-y-8" x-data="apiHub()">
    
    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Konfigurasi & Pengaturan Sistem</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-blue text-white shadow-sm shadow-blue-500/20">
                    Sistem Inti v2.0
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Kelola kredensial provider pihak ketiga, pantau saldo API realtime, deposit otomatis, nomor WhatsApp bot & admin.</p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <button type="button" 
                    @click="refreshLiveStatus()" 
                    :disabled="isRefreshing" 
                    class="py-2.5 px-4 rounded-xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-200 hover:border-blue-500 hover:text-blue-500 font-bold text-xs shadow-sm transition flex items-center gap-2 cursor-pointer disabled:opacity-50">
                <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span x-text="isRefreshing ? 'Memperbarui...' : 'Refresh Saldo Realtime'">Refresh Saldo Realtime</span>
            </button>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- HERO WIDGET: SALDO & STATUS PROVIDER API (INTERAKTIF & BESAR) -->
    <!-- ======================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- KARTU 1: PREMKU SERVER (SALDO ASLI BESAR & FITUR DEPOSIT) -->
        <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-gray-900 via-[#13112c] to-[#1e1338] border border-purple-500/25 text-white shadow-xl shadow-purple-950/20 relative overflow-hidden flex flex-col justify-between">
            <!-- Glow Effect Background -->
            <div class="absolute -top-16 -right-16 w-44 h-44 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-44 h-44 bg-emerald-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <!-- Header Kartu Premku -->
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white flex items-center justify-center font-black text-base shadow-lg shadow-purple-500/30">
                            PK
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white tracking-wide">Premku Digital Store</h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase">API v2.0</span>
                            </div>
                            <p class="text-[11px] text-gray-400 font-medium">Provider Otomatis Akun Premium 24 Jam</p>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <div>
                        <span x-show="premku.ok" class="px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            TERHUBUNG AKTIF
                        </span>
                        <span x-show="!premku.ok" style="display: none;" class="px-3 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            PERIKSA KUNCI
                        </span>
                    </div>
                </div>

                <!-- DISPLAY SALDO ASLI (BESAR & JELAS) -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-sm mb-5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-300/90 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            Saldo Deposit Premku Realtime
                        </p>
                        <span class="text-[10px] font-mono font-bold text-gray-400" x-text="'Update: ' + (lastChecked || 'Baru Saja')"></span>
                    </div>

                    <div class="flex items-baseline flex-wrap gap-2.5">
                        <span class="text-3xl sm:text-4xl md:text-5xl font-black font-mono tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-300"
                              x-text="'Rp ' + (premku.saldo || '0')">
                            Rp {{ $premkuStatus['saldo'] ?? '0' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Siap Transaksi
                        </span>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-2 leading-relaxed">
                        Saldo ini terpotong otomatis setiap pelanggan Anda membeli akun Netflix, Capcut, Alight Motion, Canva, dll.
                    </p>
                </div>

                <!-- Metadata Profil Akun Premku -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5 text-xs">
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Username</p>
                        <p class="font-black text-white truncate mt-0.5" x-text="premku.username || '-'">{{ $premkuStatus['username'] ?? '-' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">WhatsApp Akun</p>
                        <p class="font-black text-white truncate mt-0.5" x-text="premku.whatsapp || '-'">{{ $premkuStatus['whatsapp'] ?? '-' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Katalog Terkait</p>
                        <p class="font-black text-emerald-400 truncate mt-0.5">{{ $premkuStatus['active_product_count'] ?? 0 }} / {{ $premkuStatus['product_count'] ?? 0 }} SKU</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Terdaftar</p>
                        <p class="font-black text-gray-300 truncate mt-0.5" x-text="premku.registered_at || '-'">{{ $premkuStatus['registered_at'] ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tombol Interaktif Premku -->
            <div class="flex flex-wrap items-center gap-2.5 pt-3 border-t border-white/10">
                <button type="button" 
                        @click="openDepositModal()" 
                        class="flex-1 py-3 px-4 rounded-xl gradient-green text-white font-black text-xs shadow-lg shadow-emerald-500/20 hover:opacity-95 transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Top Up / Deposit Saldo (QRIS)</span>
                </button>

                <a href="{{ route('admin.products.api') }}" 
                   class="py-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer border border-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    <span>Markup & Harga</span>
                </a>
            </div>
        </div>

        <!-- KARTU 2: PREFLIX H2H GATEWAY (INTEGRASI & STATUS KONEKSI) -->
        <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-gray-900 via-[#0e1e38] to-[#12284c] border border-blue-500/25 text-white shadow-xl shadow-blue-950/20 relative overflow-hidden flex flex-col justify-between">
            <!-- Glow Effect Background -->
            <div class="absolute -top-16 -right-16 w-44 h-44 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-44 h-44 bg-cyan-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <!-- Header Kartu Preflix -->
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 text-white flex items-center justify-center font-black text-base shadow-lg shadow-blue-500/30">
                            PF
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white tracking-wide">Preflix H2H Gateway</h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-blue-500/20 text-blue-300 border border-blue-500/30 uppercase">Enterprise</span>
                            </div>
                            <p class="text-[11px] text-gray-400 font-medium">Server Feed Katalog & Transaksi Otomatis</p>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <div>
                        <span x-show="preflix.ok" class="px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-blue-400 animate-pulse"></span>
                            SERVER H2H ONLINE
                        </span>
                        <span x-show="!preflix.ok" style="display: none;" class="px-3 py-1 rounded-full text-[10px] font-black bg-rose-500/20 text-rose-400 border border-rose-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            PERIKSA KREDENSIAL
                        </span>
                    </div>
                </div>

                <!-- DISPLAY STATUS H2H (BESAR & JELAS) -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-sm mb-5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-300/90 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Koneksi Katalog Server H2H
                        </p>
                        <span class="text-[10px] font-mono font-bold text-gray-400" x-text="'Ping: ' + (lastChecked || 'Normal')"></span>
                    </div>

                    <div class="flex items-baseline flex-wrap gap-2.5">
                        <span class="text-3xl sm:text-4xl md:text-5xl font-black font-mono tracking-tight text-white">
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-cyan-300 to-indigo-300" x-text="(preflix.kategori_count || '{{ $preflixStatus['kategori_count'] ?? 40 }}')">
                                {{ $preflixStatus['kategori_count'] ?? 40 }}
                            </span> Kategori
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-blue-500/20 text-blue-300 border border-blue-500/30">
                            Terhubung
                        </span>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-2 leading-relaxed">
                        Menyuplai feed streaming Disney, Netflix, Viu, Vidio, Spotify, Youtube Premium, Google Play, dan lisensi software lainnya.
                    </p>
                </div>

                <!-- Metadata Akun Preflix -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5 text-xs">
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">ID Pengguna</p>
                        <p class="font-black text-white truncate mt-0.5 font-mono">{{ $preflixIdUser ?: '-' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Key User</p>
                        <p class="font-black text-cyan-300 truncate mt-0.5 font-mono">{{ $preflixKeyUser ? 'Terverifikasi' : 'Kosong' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Katalog Terkait</p>
                        <p class="font-black text-cyan-400 truncate mt-0.5">{{ $preflixStatus['active_product_count'] ?? 0 }} / {{ $preflixStatus['product_count'] ?? 0 }} SKU</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Protokol</p>
                        <p class="font-black text-gray-300 truncate mt-0.5">HTTPS Form-H2H</p>
                    </div>
                </div>
            </div>

            <!-- Tombol Interaktif Preflix -->
            <div class="flex flex-wrap items-center gap-2.5 pt-3 border-t border-white/10">
                <button type="button" 
                        @click="refreshLiveStatus()" 
                        :disabled="isRefreshing" 
                        class="flex-1 py-3 px-4 rounded-xl gradient-blue text-white font-black text-xs shadow-lg shadow-blue-500/20 hover:opacity-95 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Cek Koneksi H2H Realtime</span>
                </button>

                <a href="{{ route('admin.products.api') }}" 
                   class="py-3 px-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer border border-white/10">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    <span>Markup & Harga</span>
                </a>
            </div>
        </div>

        <!-- KARTU 3: KIOSBANK H2H PPOB (OPEN API HOST-TO-HOST) -->
        <div class="p-6 sm:p-7 rounded-3xl bg-gradient-to-br from-gray-900 via-[#102421] to-[#12382f] border border-emerald-500/25 text-white shadow-xl shadow-emerald-950/20 relative overflow-hidden flex flex-col justify-between">
            <!-- Glow Effect Background -->
            <div class="absolute -top-16 -right-16 w-44 h-44 bg-emerald-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-44 h-44 bg-teal-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div>
                <!-- Header Kartu Kiosbank -->
                <div class="flex items-start justify-between gap-3 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center font-black text-base shadow-lg shadow-emerald-500/30">
                            KB
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-white tracking-wide">Kiosbank Open API</h3>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 uppercase">H2H PPOB</span>
                            </div>
                            <p class="text-[11px] text-gray-400 font-medium">Provider Pulsa, PLN, PDAM &amp; Voucher Game</p>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <div>
                        <span x-show="kiosbank.ok" class="px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            AKTIF TERHUBUNG
                        </span>
                        <span x-show="!kiosbank.ok" style="display: none;" class="px-3 py-1 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                            SIAP / WHITELIST
                        </span>
                    </div>
                </div>

                <!-- DISPLAY SALDO DEPOSIT KIOSBANK -->
                <div class="p-4 sm:p-5 rounded-2xl bg-white/[0.04] border border-white/10 backdrop-blur-sm mb-5">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-300/90 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Saldo Deposit Kiosbank
                        </p>
                        <span class="text-[10px] font-mono font-bold text-gray-400" x-text="'Mode: {{ strtoupper($kiosbankMode) }}'"></span>
                    </div>

                    <div class="flex items-baseline flex-wrap gap-2.5">
                        <span class="text-3xl sm:text-4xl md:text-5xl font-black font-mono tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-300"
                              x-text="'Rp ' + (kiosbank.saldo || '0')">
                            Rp {{ $kiosbankStatus['saldo'] ?? '0' }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            {{ $kiosbankEnabled == '1' ? 'Layanan Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <p class="text-[11px] text-gray-400 mt-2 leading-relaxed">
                        Digest Auth otomatis (Sign-On harian ter-cache). Saldo ini terpotong saat pelanggan membeli pulsa, kuota, token PLN atau game.
                    </p>
                </div>

                <!-- Metadata Kiosbank -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-5 text-xs">
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Merchant ID</p>
                        <p class="font-black text-white truncate mt-0.5 font-mono">{{ $kiosbankMerchantId ?: 'Belum diisi' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Mitra ID</p>
                        <p class="font-black text-emerald-300 truncate mt-0.5 font-mono">{{ $kiosbankMitra ?: 'DJI' }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Host Server</p>
                        <p class="font-black text-gray-200 truncate mt-0.5 text-[10px] font-mono">{{ $kiosbankHost }}:{{ $kiosbankPort }}</p>
                    </div>
                    <div class="p-2.5 rounded-xl bg-white/[0.03] border border-white/5">
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Otentikasi</p>
                        <p class="font-black text-emerald-400 truncate mt-0.5">HTTP Digest 401</p>
                    </div>
                </div>
            </div>

            <!-- Tombol Interaktif Kiosbank -->
            <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-white/10">
                <a href="{{ route('admin.ppob.products') }}" 
                   class="flex-1 py-2.5 px-3 rounded-xl bg-cyan-500 hover:bg-cyan-600 text-white font-black text-xs shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span>Atur Margin Produk</span>
                </a>

                <a href="{{ route('admin.ppob.deposit') }}" 
                   class="py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Top Up Saldo</span>
                </a>

                <button type="button" 
                        @click="refreshLiveStatus()" 
                        :disabled="isRefreshing" 
                        class="py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer border border-white/10 disabled:opacity-50" title="Cek Saldo Realtime">
                    <svg class="w-3.5 h-3.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Cek Saldo</span>
                </button>
            </div>
        </div>

    </div>

    <!-- ======================================================== -->
    <!-- FORM EDIT KREDENSIAL & PENGATURAN SISTEM                  -->
    <!-- ======================================================== -->
    <form action="{{ route('admin.settings.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        
        <!-- Banner Pintasan Menu Branding Baru -->
        <div class="p-5 sm:p-6 rounded-3xl bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent border border-orange-500/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl gradient-orange text-white flex items-center justify-center font-black text-xl shadow-lg shadow-orange-500/20 shrink-0">
                    🏪
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-black text-base text-gray-900 dark:text-white">Identitas &amp; Logo Toko (Branding)</h3>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-orange-100 dark:bg-orange-950 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">Menu Terpisah</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pengaturan Logo Toko Utama, Favicon Tab Browser, Logo Preloader, Nama Toko &amp; Slogan kini dikelola pada menu tersendiri.</p>
                </div>
            </div>
            <a href="{{ route('admin.branding') }}" class="px-4 py-2.5 rounded-xl gradient-orange text-white font-bold text-xs shadow-md shadow-orange-500/20 hover:opacity-95 transition shrink-0 flex items-center gap-2">
                <span>Buka Menu Branding</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Card 1: Pengaturan Server API Provider (Kiri) -->
            <div class="admin-card p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="w-9 h-9 rounded-xl gradient-blue text-white flex items-center justify-center font-black text-sm shadow-sm">
                        ⚡
                    </div>
                    <div>
                        <h3 class="font-black text-base text-gray-900 dark:text-white">Kredensial API Provider</h3>
                        <p class="text-[11px] font-semibold text-gray-400">Atur kunci otentikasi Premku & Preflix</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider">Premku API Key</label>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full"
                                  :class="premku.ok ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'"
                                  x-text="premku.ok ? '✓ AKTIF' : '⚠️ PERIKSA KEY'">
                                {{ ($premkuStatus['ok'] ?? false) ? '✓ AKTIF' : '⚠️ PERIKSA KEY' }}
                            </span>
                        </div>
                        <input type="text" name="premku_api_key" value="{{ $premkuApiKey }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500 transition" placeholder="Masukkan Key Premku dari premku.com...">
                        <p class="text-[11px] font-bold mt-1.5 text-gray-500 dark:text-gray-400" x-text="premku.message">
                            {{ $premkuStatus['message'] ?? 'Kunci API rahasia dari Dashboard premku.com' }}
                        </p>
                    </div>

                    <!-- Premku API Secret (Verifikasi HMAC-SHA256 Webhook) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider">Premku API Secret</label>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                                Webhook HMAC-SHA256
                            </span>
                        </div>
                        <input type="text" name="premku_api_secret" value="{{ $premkuApiSecret }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500 transition" placeholder="Masukkan API Secret dari halaman Profil premku.com...">
                        <p class="text-[11px] font-bold mt-1.5 text-gray-500 dark:text-gray-400">
                            Digunakan untuk memvalidasi header <code class="text-purple-500 font-mono">X-Premku-Signature</code> saat Premku mengirim webhook.
                        </p>
                    </div>

                    <!-- Callback Webhook URL Premku (1-Click Copy) -->
                    <div class="p-4 rounded-2xl bg-purple-50/80 dark:bg-purple-950/20 border border-purple-200/80 dark:border-purple-800/30 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-black text-purple-700 dark:text-purple-300 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                URL Callback Webhook Premku (v3.8)
                            </span>
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400">Pasang di Profil Premku</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly id="premkuWebhookUrlInput" value="{{ $premkuWebhookUrl }}" class="flex-1 px-3 py-2 bg-white dark:bg-black/40 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold text-gray-800 dark:text-gray-200 outline-none select-all shadow-sm">
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('premkuWebhookUrlInput').value); alert('URL Webhook Premku berhasil disalin ke clipboard!');" class="px-3.5 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-black text-xs transition cursor-pointer shrink-0 shadow-sm flex items-center gap-1.5 active:scale-95">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                <span>Salin URL</span>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-600 dark:text-gray-400 leading-relaxed">
                            Salin URL di atas lalu masukkan ke menu <strong>Profil Pengguna ➜ Callback URL</strong> di <em>premku.com</em>. Sistem NandarStore akan otomatis menerima akun &amp; menyelesaikan pesanan secara asinkron.
                        </p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-black text-gray-500 uppercase tracking-wider">Preflix ID User</label>
                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full"
                                  :class="preflix.ok ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'"
                                  x-text="preflix.ok ? '✓ AKTIF' : '⚠️ PERIKSA ID'">
                                {{ ($preflixStatus['ok'] ?? false) ? '✓ AKTIF' : '⚠️ PERIKSA ID' }}
                            </span>
                        </div>
                        <input type="text" name="preflix_id_user" value="{{ $preflixIdUser }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500 transition" placeholder="ID User Preflix...">
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Preflix Key User</label>
                        <input type="text" name="preflix_key_user" value="{{ $preflixKeyUser }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500 transition" placeholder="Key User Preflix...">
                        <p class="text-[11px] font-bold mt-1.5 text-gray-500 dark:text-gray-400" x-text="preflix.message">
                            {{ $preflixStatus['message'] ?? 'Kredensial akses H2H Preflix' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Card 2: WhatsApp Admin & Bot (Kanan Atas) -->
            <div class="admin-card p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="w-9 h-9 rounded-xl gradient-green text-white flex items-center justify-center font-black text-sm shadow-sm">
                        📱
                    </div>
                    <div>
                        <h3 class="font-black text-base text-gray-900 dark:text-white">WhatsApp Bot &amp; Layanan CS</h3>
                        <p class="text-[11px] font-semibold text-gray-400">Verifikasi kode, kirim pesanan &amp; CS</p>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Nomor WhatsApp Bot (Otomatisasi)</label>
                        <input type="text" name="bot_whatsapp" value="{{ $botWhatsapp ?? '08990738216' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 transition" placeholder="08990738216">
                        <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">
                            *Digunakan untuk menerima chat kode verifikasi pengguna dan mengirim detail akun otomatis via bot.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Nomor WhatsApp Admin (CS &amp; Bantuan)</label>
                        <input type="text" name="admin_whatsapp" value="{{ $adminWhatsapp ?? '082317458496' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 transition" placeholder="082317458496">
                        <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">
                            *Digunakan untuk tombol "Chat Admin" / Customer Service di website.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Link Grup Komunitas WhatsApp</label>
                        <input type="url" name="whatsapp_group_link" value="{{ $whatsappGroupLink ?? 'https://chat.whatsapp.com/KfdDhTYboOlLX1PBOkV36H' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500 transition" placeholder="https://chat.whatsapp.com/...">
                        <p class="text-[10px] text-gray-400 mt-1 leading-relaxed">
                            *Tautan link undangan grup WhatsApp yang muncul pada tombol "Join Grup WA".
                        </p>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 admin-card p-6 space-y-4">
                <h3 class="font-black text-base dark:text-white">Kiosbank PPOB</h3>
                <p class="text-sm text-gray-500">Atur mode Development/Production dan kredensial pada menu Konfigurasi Kiosbank.</p>
                <a href="{{ route('admin.ppob.settings') }}" class="inline-block px-5 py-3 rounded-xl bg-cyan-600 text-white font-bold">Buka Konfigurasi Kiosbank</a>
                <div class="grid md:grid-cols-2 gap-4">
                    <label class="text-sm dark:text-gray-200">Tipe markup
                        <select name="kiosbank_markup_type" class="block w-full p-3 border rounded-xl bg-white dark:bg-gray-900">
                            <option value="fixed" @selected($kiosbankMarkupType === 'fixed')>Nominal (Rp)</option>
                            <option value="percent" @selected($kiosbankMarkupType === 'percent')>Persentase (%)</option>
                        </select>
                    </label>
                    <label class="text-sm dark:text-gray-200">Nilai markup
                        <input type="number" min="0" name="kiosbank_markup_value" value="{{ $kiosbankMarkupValue }}" class="block w-full p-3 border rounded-xl bg-white dark:bg-gray-900">
                    </label>
                </div>
            </div>

            <!-- Card 3: Marketing & Promo Front-End (Full Width Bawah) -->
            <div class="lg:col-span-2 admin-card p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="w-9 h-9 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-sm shadow-sm">
                        🏷️
                    </div>
                    <div>
                        <h3 class="font-black text-base text-gray-900 dark:text-white">Marketing & Fitur Harga Coret</h3>
                        <p class="text-[11px] font-semibold text-gray-400">Tingkatkan konversi penjualan dengan diskon visual</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Mark-Up Harga Coret (%)</label>
                        <div class="relative">
                            <input type="number" name="promo_markup_percent" value="{{ $promoMarkup ?? 25 }}" class="w-full pl-4 pr-10 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                            <span class="absolute right-4 top-3 font-black text-gray-400">%</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed">
                            Contoh: Isi <strong>25</strong> untuk menampilkan harga coret 25% lebih mahal. Isi <strong>0</strong> untuk menonaktifkan.
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Teks Label Pita Promo</label>
                        <input type="text" name="promo_badge_text" value="{{ $promoText ?? 'PROMO' }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black tracking-widest uppercase dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="PROMO / HOT / FLASH">
                        <p class="text-[10px] text-gray-400 mt-1.5 leading-relaxed">
                            Contoh: <strong>PROMO</strong>, <strong>HOT</strong>, atau <strong>DISKON</strong>. Kosongkan jika ingin menyembunyikan label.
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="gradient-orange text-white font-black py-4 px-10 rounded-2xl shadow-xl shadow-orange-500/25 transition-all hover:opacity-90 hover:scale-[1.01] flex items-center gap-2 cursor-pointer text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span>Simpan Seluruh Pengaturan</span>
            </button>
        </div>
    </form>

    <!-- ======================================================== -->
    <!-- MODAL POPUP: DEPOSIT / TOP UP SALDO PREMKU VIA QRIS     -->
    <!-- ======================================================== -->
    <div x-show="depositModal" 
         x-transition.opacity.duration.200ms 
         style="display: none;" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        
        <div @click.away="closeDepositModal()" 
             class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl w-full max-w-lg p-6 sm:p-7 shadow-2xl relative overflow-hidden">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5 mb-5">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl gradient-green text-white flex items-center justify-center font-black text-sm shadow-sm">
                        QR
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Deposit Saldo Premku</h3>
                        <p class="text-[11px] text-gray-400">Pembayaran QRIS Otomatis Masuk Saldo</p>
                    </div>
                </div>

                <button type="button" @click="closeDepositModal()" class="w-8 h-8 rounded-full bg-gray-100 dark:bg-white/5 text-gray-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center transition cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- TAHAP 1: INPUT NOMINAL DEPOSIT -->
            <div x-show="depositStep === 'input'" class="space-y-4">
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Pilih Nominal Cepat</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="depositAmount = 25000" :class="depositAmount == 25000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 25.000
                        </button>
                        <button type="button" @click="depositAmount = 50000" :class="depositAmount == 50000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 50.000
                        </button>
                        <button type="button" @click="depositAmount = 100000" :class="depositAmount == 100000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 100.000
                        </button>
                        <button type="button" @click="depositAmount = 200000" :class="depositAmount == 200000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 200.000
                        </button>
                        <button type="button" @click="depositAmount = 300000" :class="depositAmount == 300000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 300.000
                        </button>
                        <button type="button" @click="depositAmount = 500000" :class="depositAmount == 500000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 font-bold'" class="py-2.5 px-3 rounded-xl border text-xs transition cursor-pointer text-center">
                            Rp 500.000
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Atau Masukkan Nominal Manual (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-xs font-black text-gray-400">Rp</span>
                        <input type="number" 
                               x-model.number="depositAmount" 
                               min="10000" 
                               step="5000"
                               class="w-full pl-10 pr-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-emerald-500" 
                               placeholder="Contoh: 50000">
                    </div>
                    <p class="text-[10px] text-gray-400 mt-1.5">*Minimal deposit Rp 10.000 sesuai regulasi API Premku.</p>
                </div>

                <div x-show="depositError" style="display: none;" class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-xs font-bold text-rose-600 dark:text-rose-400" x-text="depositError"></div>

                <div class="pt-2">
                    <button type="button" 
                            @click="createDeposit()" 
                            :disabled="isCreatingDeposit || depositAmount < 10000"
                            class="w-full py-3.5 px-4 rounded-xl gradient-green text-white font-black text-xs shadow-lg shadow-emerald-500/25 hover:opacity-95 transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <svg x-show="isCreatingDeposit" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isCreatingDeposit ? 'Menghubungkan ke Premku...' : 'Generate Tagihan QRIS Deposit'">Generate Tagihan QRIS Deposit</span>
                    </button>
                </div>
            </div>

            <!-- TAHAP 2: DISPLAY QRIS & INVOICE PEMBAYARAN -->
            <div x-show="depositStep === 'qris'" style="display: none;" class="space-y-4 text-center">
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-100 dark:border-white/5 inline-block mx-auto shadow-inner">
                    <!-- QR Image dari Premku API -->
                    <template x-if="depositData && depositData.qr_image">
                        <img :src="depositData.qr_image" alt="QRIS Premku" class="w-56 h-56 mx-auto object-contain rounded-xl bg-white p-2 shadow-sm">
                    </template>
                </div>

                <div>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider">Total yang Harus Ditransfer (Tepat):</p>
                    <div class="flex items-center justify-center gap-2 mt-1">
                        <span class="text-2xl font-black font-mono text-emerald-500" x-text="'Rp ' + Number(depositData ? depositData.total_bayar : 0).toLocaleString('id-ID')"></span>
                        <button type="button" 
                                @click="copyText(depositData ? depositData.total_bayar : '')" 
                                class="px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-white/10 hover:bg-emerald-100 dark:hover:bg-emerald-950/60 text-gray-600 dark:text-gray-300 hover:text-emerald-600 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer" 
                                title="Salin Nominal">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            <span x-text="isCopied ? 'Tersalin ✓' : 'Salin'"></span>
                        </button>
                    </div>
                    <p class="text-[11px] text-amber-500 dark:text-amber-400 font-bold mt-1">
                        ⚠️ Wajib transfer sesuai nominal tepat sampai 3 digit kode unik agar saldo otomatis masuk!
                    </p>
                </div>

                <div class="p-3 rounded-xl bg-white/[0.04] border border-white/5 text-xs text-left space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Invoice:</span>
                        <span class="font-mono font-bold text-gray-900 dark:text-white" x-text="depositData ? depositData.invoice : '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Status:</span>
                        <span class="font-black text-amber-500 uppercase flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span x-text="depositStatusText || 'Menunggu Pembayaran'"></span>
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <button type="button" 
                            @click="checkDepositStatusManually()" 
                            :disabled="isCheckingDeposit" 
                            class="flex-1 py-3 px-4 rounded-xl gradient-blue text-white font-black text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50">
                        <svg class="w-4 h-4" :class="{ 'animate-spin': isCheckingDeposit }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span x-text="isCheckingDeposit ? 'Mengecek...' : 'Cek Status Sekarang'">Cek Status Sekarang</span>
                    </button>

                    <button type="button" 
                            @click="cancelDeposit()" 
                            class="py-3 px-4 rounded-xl bg-gray-100 dark:bg-white/5 hover:bg-rose-50 dark:hover:bg-rose-950/40 text-gray-600 dark:text-gray-300 hover:text-rose-600 font-bold text-xs transition cursor-pointer border border-gray-200 dark:border-white/10">
                        Batalkan
                    </button>
                </div>
            </div>

            <!-- TAHAP 3: BERHASIL -->
            <div x-show="depositStep === 'success'" style="display: none;" class="space-y-4 text-center py-4">
                <div class="w-16 h-16 rounded-3xl bg-emerald-500 text-white flex items-center justify-center text-3xl font-black mx-auto shadow-xl shadow-emerald-500/30">
                    ✓
                </div>
                <div>
                    <h4 class="text-lg font-black text-gray-900 dark:text-white">Pembayaran Berhasil!</h4>
                    <p class="text-xs text-gray-400 mt-1">Saldo deposit Premku Anda telah bertambah secara otomatis.</p>
                </div>
                <button type="button" @click="closeDepositModal()" class="py-3 px-6 rounded-xl gradient-green text-white font-black text-xs shadow-lg transition cursor-pointer">
                    Selesai & Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Uji Sign-On Live Kiosbank -->
    <div x-show="signOnModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">
        <div @click.away="signOnModal = false" class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-white/10 rounded-3xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-lg font-black">
                        ⚡
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-900 dark:text-white">Hasil Uji Live Sign-On Kiosbank</h4>
                        <p class="text-[11px] text-gray-400 font-semibold">Diagnostik Autentikasi Host-to-Host</p>
                    </div>
                </div>
                <button type="button" @click="signOnModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white text-lg font-bold">✕</button>
            </div>

            <template x-if="signOnResult">
                <div class="space-y-3">
                    <div class="p-3.5 rounded-2xl flex items-center gap-3"
                         :class="signOnResult.ok ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300' : 'bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300'">
                        <span class="text-2xl" x-text="signOnResult.ok ? '✅' : '❌'"></span>
                        <div>
                            <h5 class="text-xs font-black uppercase" x-text="signOnResult.ok ? 'Sign-On Berhasil Terhubung' : 'Sign-On Gagal'"></h5>
                            <p class="text-[11px] font-semibold mt-0.5" x-text="signOnResult.message || '-'"></p>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-white/[0.03] border border-gray-100 dark:border-white/5 text-xs space-y-2 font-mono">
                        <div class="flex justify-between items-center text-[11px]">
                            <span class="text-gray-400 font-sans">Metode Otentikasi:</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="signOnResult.method || 'RSA Asymmetric'"></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px]">
                            <span class="text-gray-400 font-sans">Mode Server:</span>
                            <span class="font-bold uppercase" :class="signOnResult.mode === 'development' ? 'text-amber-500' : 'text-emerald-500'" x-text="signOnResult.mode || 'development'"></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px]" x-show="signOnResult.http_code">
                            <span class="text-gray-400 font-sans">HTTP Response:</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="signOnResult.http_code"></span>
                        </div>
                        <div class="flex justify-between items-center text-[11px]" x-show="signOnResult.session_id">
                            <span class="text-gray-400 font-sans">SessionID:</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-black text-emerald-600 dark:text-emerald-400 text-xs" x-text="signOnResult.session_id"></span>
                                <button type="button" @click="copyText(signOnResult.session_id)" class="text-[10px] text-gray-400 hover:text-emerald-500 cursor-pointer" title="Salin SessionID">📋</button>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-[11px]" x-show="signOnResult.x_date">
                            <span class="text-gray-400 font-sans">X-Date (WIB GMT+7):</span>
                            <span class="text-gray-500 dark:text-gray-400" x-text="signOnResult.x_date"></span>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[10px] text-amber-700 dark:text-amber-300 leading-relaxed">
                        ℹ️ <strong>Informasi Kuota:</strong> Kiosbank membatasi Sign-On maksimal 99x/hari. Di sistem NandarStore, SessionID disimpan otomatis di cache sampai pukul 23:55 WIB agar efisien dan terhindar dari RC 34.
                    </div>
                </div>
            </template>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="signOnModal = false" class="py-2.5 px-5 rounded-xl bg-gray-100 dark:bg-white/10 hover:bg-gray-200 dark:hover:bg-white/20 text-gray-700 dark:text-white text-xs font-bold transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<!-- JAVASCRIPT LOGIC REALTIME API HUB & DEPOSIT -->
<script>
function apiHub() {
    return {
        isRefreshing: false,
        lastChecked: '{{ now()->format("H:i:s") }} WIB',
        premku: {
            ok: {{ ($premkuStatus['ok'] ?? false) ? 'true' : 'false' }},
            saldo: '{{ $premkuStatus['saldo'] ?? "0" }}',
            raw_saldo: {{ $premkuStatus['raw_saldo'] ?? 0 }},
            username: '{{ $premkuStatus['username'] ?? "-" }}',
            whatsapp: '{{ $premkuStatus['whatsapp'] ?? "-" }}',
            registered_at: '{{ $premkuStatus['registered_at'] ?? "-" }}',
            message: '{{ $premkuStatus['message'] ?? "" }}'
        },
        preflix: {
            ok: {{ ($preflixStatus['ok'] ?? false) ? 'true' : 'false' }},
            kategori_count: {{ $preflixStatus['kategori_count'] ?? 40 }},
            message: '{{ $preflixStatus['message'] ?? "" }}'
        },
        kiosbank: {
            ok: {{ ($kiosbankStatus['ok'] ?? false) ? 'true' : 'false' }},
            saldo: '{{ $kiosbankStatus['saldo'] ?? "0" }}',
            message: '{{ $kiosbankStatus['message'] ?? "" }}'
        },

        // Kiosbank RSA & Sign-On Diagnostics
        isGeneratingRsa: false,
        isTestingSignOn: false,
        kiosbankPrivateKey: @json($kiosbankPrivateKey ?? ''),
        kiosbankPublicKey: @json($kiosbankPublicKey ?? ''),
        signOnModal: false,
        signOnResult: null,

        // Modal Deposit State
        depositModal: false,
        depositStep: 'input', // 'input', 'qris', 'success'
        depositAmount: 50000,
        isCreatingDeposit: false,
        isCheckingDeposit: false,
        depositData: null,
        depositError: null,
        depositStatusText: '',
        pollTimer: null,

        async refreshLiveStatus() {
            this.isRefreshing = true;
            try {
                const res = await fetch('{{ route("admin.settings.api_status") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.status === 'success') {
                    if (data.premku) {
                        this.premku = Object.assign({}, this.premku, data.premku);
                    }
                    if (data.preflix) {
                        this.preflix = Object.assign({}, this.preflix, data.preflix);
                    }
                    if (data.kiosbank) {
                        this.kiosbank = Object.assign({}, this.kiosbank, data.kiosbank);
                    }
                    this.lastChecked = data.timestamp || new Date().toLocaleTimeString('id-ID');
                }
            } catch (e) {
                console.error('Error refreshing API status:', e);
            } finally {
                this.isRefreshing = false;
            }
        },

        openDepositModal() {
            this.depositModal = true;
            this.depositStep = 'input';
            this.depositError = null;
        },

        closeDepositModal() {
            if (this.pollTimer) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
            this.depositModal = false;
            this.refreshLiveStatus();
        },

        async createDeposit() {
            this.isCreatingDeposit = true;
            this.depositError = null;
            try {
                const res = await fetch('{{ route("admin.settings.premku.deposit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ amount: this.depositAmount })
                });
                const json = await res.json();
                if (json.status === 'success' && json.data) {
                    this.depositData = json.data;
                    this.depositStep = 'qris';
                    this.depositStatusText = 'Menunggu Pembayaran';
                    this.startDepositPolling();
                } else {
                    this.depositError = json.message || 'Gagal membuat tagihan deposit.';
                }
            } catch (e) {
                this.depositError = 'Gagal menghubungi server: ' + e.message;
            } finally {
                this.isCreatingDeposit = false;
            }
        },

        startDepositPolling() {
            if (this.pollTimer) clearInterval(this.pollTimer);
            this.pollTimer = setInterval(() => {
                this.checkDepositStatus(false);
            }, 4000);
        },

        async checkDepositStatus(manual = false) {
            if (!this.depositData || !this.depositData.invoice) return;
            if (manual) this.isCheckingDeposit = true;

            try {
                const res = await fetch('{{ route("admin.settings.premku.check_deposit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ invoice: this.depositData.invoice })
                });
                const json = await res.json();
                const st = (json.data && json.data.status) ? json.data.status.toLowerCase() : '';
                
                if (st === 'success' || st === 'paid' || st === 'berhasil') {
                    if (this.pollTimer) clearInterval(this.pollTimer);
                    this.depositStep = 'success';
                    this.refreshLiveStatus();
                } else if (st === 'canceled' || st === 'cancelled' || st === 'expired') {
                    this.depositStatusText = 'Tagihan ' + st.toUpperCase();
                    if (this.pollTimer) clearInterval(this.pollTimer);
                } else {
                    this.depositStatusText = 'Menunggu Pembayaran';
                }
            } catch (e) {
                console.error(e);
            } finally {
                if (manual) this.isCheckingDeposit = false;
            }
        },

        checkDepositStatusManually() {
            this.checkDepositStatus(true);
        },

        async cancelDeposit() {
            if (!this.depositData || !this.depositData.invoice) {
                this.closeDepositModal();
                return;
            }
            try {
                await fetch('{{ route("admin.settings.premku.cancel_deposit") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ invoice: this.depositData.invoice })
                });
            } catch (e) {}
            this.closeDepositModal();
        },

        isCopied: false,
        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text.toString());
            this.isCopied = true;
            setTimeout(() => { this.isCopied = false; }, 2000);
        },

        async generateKiosbankRsa() {
            if (this.kiosbankPrivateKey && !confirm('Peringatan: Kunci RSA lama akan ditimpa dengan pasangan kunci 2048-bit baru. Jika Anda sudah mendaftarkan Public Key lama ke Kiosbank, Anda harus mendaftarkan Public Key baru lagi. Lanjutkan?')) {
                return;
            }
            this.isGeneratingRsa = true;
            try {
                const res = await fetch('{{ route("admin.settings.kiosbank.generate_rsa") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.kiosbankPrivateKey = data.private_key;
                    this.kiosbankPublicKey = data.public_key;
                    alert('Berhasil! Pasangan kunci RSA 2048-bit baru berhasil dibuat dan otomatis tersimpan ke sistem.');
                } else {
                    alert('Gagal generate RSA: ' + (data.message || 'Terjadi kesalahan'));
                }
            } catch (e) {
                alert('Gagal menghubungi server: ' + e.message);
            } finally {
                this.isGeneratingRsa = false;
            }
        },

        async testKiosbankSignOn() {
            this.isTestingSignOn = true;
            try {
                const res = await fetch('{{ route("admin.settings.kiosbank.test_signon") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                this.signOnResult = data;
                this.signOnModal = true;
            } catch (e) {
                alert('Gagal melakukan uji coba Sign-On: ' + e.message);
            } finally {
                this.isTestingSignOn = false;
            }
        }
    }
}
</script>
@endsection