@extends('layouts.admin')

@section('title', 'Dashboard Analisa')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-xs sm:text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-xs sm:text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ session('error') }}</div>
        </div>
    @endif
    
    <!-- Welcome Banner with Ambient Gradient Glow -->
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 text-white shadow-xl shadow-orange-600/15">
        <div class="absolute -right-10 -bottom-10 w-64 h-64 rounded-full bg-white/10 blur-3xl pointer-events-none"></div>
        <div class="absolute right-20 -top-10 w-48 h-48 rounded-full bg-amber-300/20 blur-2xl pointer-events-none"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md text-xs font-black uppercase tracking-wider mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                    <span>Monitoring Real-Time Toko</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight leading-tight">
                    Selamat Datang, {{ Auth::user()->name ?? 'Administrator' }}!
                </h1>
                <p class="text-orange-100 text-xs sm:text-sm font-medium mt-1 max-w-xl leading-relaxed">
                    Ringkasan performa penjualan, aktivitas reseller, dan status verifikasi sistem PREMIUMKITA 2.0 hari ini.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('admin.reports') }}" class="px-4 py-2.5 rounded-xl bg-white text-gray-900 hover:bg-orange-50 font-black text-xs transition-all shadow-md flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Grafik Laporan & Excel</span>
                </a>
                <a href="{{ route('admin.products.local') }}" class="px-4 py-2.5 rounded-xl bg-black/25 hover:bg-black/40 text-white font-bold text-xs transition-colors backdrop-blur-md border border-white/20 flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4 text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Tambah Produk</span>
                </a>
                <a href="{{ route('admin.orders') }}" class="px-4 py-2.5 rounded-xl bg-black/25 hover:bg-black/40 text-white font-bold text-xs transition-colors backdrop-blur-md border border-white/20 flex items-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    <span>Cek Pesanan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Kartu Statistik dengan Gradasi Warna Elegan -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
        
        <!-- Kartu 1: Total Pendapatan (Gradasi Biru ke Cyan) -->
        <div class="stat-card card-blue p-3.5 sm:p-6">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <span class="text-[11px] sm:text-xs font-bold text-gray-500 dark:text-gray-400">Total Pendapatan</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl gradient-blue text-white flex items-center justify-center shadow-md shadow-blue-500/25 shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <h3 class="text-lg sm:text-2xl lg:text-3xl font-black text-gray-900 dark:text-white tracking-tight truncate">
                @if($totalRevenue >= 1000000000)
                    Rp {{ number_format($totalRevenue / 1000000000, 1, ',', '.') }}M
                @elseif($totalRevenue >= 1000000)
                    Rp {{ number_format($totalRevenue / 1000000, 1, ',', '.') }}Jt
                @else
                    Rp {{ number_format($totalRevenue, 0, ',', '.') }}
                @endif
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-2">
                <span class="inline-flex items-center text-[9px] sm:text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 sm:px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/40">
                    ✓ Lunas
                </span>
                <span class="text-[10px] sm:text-[11px] font-medium text-gray-400">Akumulasi</span>
            </div>
        </div>

        <!-- Kartu 2: Pesanan Baru (Gradasi Emerald ke Teal) -->
        <div class="stat-card card-green p-3.5 sm:p-6">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <span class="text-[11px] sm:text-xs font-bold text-gray-500 dark:text-gray-400">Pesanan Baru</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl gradient-green text-white flex items-center justify-center shadow-md shadow-emerald-500/25 shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            </div>
            <h3 class="text-lg sm:text-2xl lg:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $newOrders }}
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-2">
                <span class="inline-flex items-center text-[9px] sm:text-[10px] font-black text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 sm:px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/40">
                    Realtime
                </span>
                <span class="text-[10px] sm:text-[11px] font-medium text-gray-400">Hari ini</span>
            </div>
        </div>

        <!-- Kartu 3: Total Reseller VIP (Gradasi Violet ke Purple) -->
        <div class="stat-card card-purple p-3.5 sm:p-6">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <span class="text-[11px] sm:text-xs font-bold text-gray-500 dark:text-gray-400">Mitra Reseller</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl gradient-purple text-white flex items-center justify-center shadow-md shadow-purple-500/25 shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
            </div>
            <h3 class="text-lg sm:text-2xl lg:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $totalResellers }}
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-2">
                <span class="inline-flex items-center text-[9px] sm:text-[10px] font-black text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/40 px-1.5 sm:px-2 py-0.5 rounded-full border border-purple-200 dark:border-purple-800/40">
                    VIP Tier
                </span>
                <span class="text-[10px] sm:text-[11px] font-medium text-gray-400">Aktif</span>
            </div>
        </div>

        <!-- Kartu 4: Verifikasi WA Pending (Gradasi Oranye ke Amber) -->
        <div class="stat-card card-orange p-3.5 sm:p-6">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-2 sm:mb-3">
                <span class="text-[11px] sm:text-xs font-bold text-gray-500 dark:text-gray-400">Verifikasi WA</span>
                <div class="w-8 h-8 sm:w-11 sm:h-11 rounded-xl sm:rounded-2xl gradient-orange text-white flex items-center justify-center shadow-md shadow-orange-500/25 shrink-0">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
            </div>
            <h3 class="text-lg sm:text-2xl lg:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $pendingWaCount }} <span class="text-xs sm:text-sm font-semibold text-gray-400 font-normal">Antrean</span>
            </h3>
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mt-2">
                @if($pendingWaCount > 0)
                    <a href="{{ route('admin.wa_verifications') }}" class="inline-flex items-center text-[9px] sm:text-[10px] font-black text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/40 px-1.5 sm:px-2 py-0.5 rounded-full border border-orange-200 dark:border-orange-800/40 hover:underline">
                        Perlu Validasi →
                    </a>
                @else
                    <span class="inline-flex items-center text-[9px] sm:text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-1.5 sm:px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/40">
                        ✓ Tervalidasi
                    </span>
                @endif
                <span class="text-[10px] sm:text-[11px] font-medium text-gray-400">{{ $totalProducts }} Produk</span>
            </div>
        </div>
    </div>

    <!-- Area Grafik Pendapatan dengan Visual Gradients & Chart.js Lokal -->
    <div class="admin-card p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Grafik Penjualan & Performa (7 Hari Terakhir)</span>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">
                        Analitik
                    </span>
                </h3>
                <p class="text-xs font-semibold text-gray-400 mt-1">Perbandingan volume omset antara Produk API Instan vs Produk Lokal Manual</p>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 text-xs font-bold text-gray-500">
                    <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                    <span class="dark:text-gray-300">Produk Otomatis</span>
                    <span class="inline-block w-3 h-3 rounded-full bg-orange-500 ml-2"></span>
                    <span class="dark:text-gray-300">Produk Lokal</span>
                </div>
                <a href="{{ route('admin.reports') }}" class="px-3 py-1.5 rounded-xl bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/50 text-orange-600 dark:text-orange-400 font-bold text-xs hover:bg-orange-100 dark:hover:bg-orange-900/40 transition-colors flex items-center gap-1.5 cursor-pointer">
                    <span>Laporan Lengkap & Ekspor</span> →
                </a>
            </div>
        </div>
        
        <div class="relative h-80 w-full">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Grid Bawah: Pesanan Terbaru & Pintasan Sistem -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Tabel Pesanan Terbaru (2 Kolom) -->
        <div class="lg:col-span-2 admin-card p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">Pesanan Terbaru Masuk</h3>
                    <p class="text-xs font-semibold text-gray-400">Transaksi transaksi terakhir pelanggan</p>
                </div>
                <a href="{{ route('admin.orders') }}" class="text-xs font-black text-orange-500 hover:text-orange-600 hover:underline">
                    Lihat Semua →
                </a>
            </div>

            @if(count($recentOrders) > 0)
                <!-- Desktop Table -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left admin-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Produk</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentOrders as $order)
                                <tr>
                                    <td>
                                        <p class="font-mono text-xs font-bold text-gray-800 dark:text-gray-200">#{{ $order->invoice_number }}</p>
                                        <p class="text-[10px] text-gray-400">{{ $order->created_at ? $order->created_at->diffForHumans() : '-' }}</p>
                                    </td>
                                    <td>
                                        <p class="font-bold text-xs text-gray-900 dark:text-white truncate max-w-[180px]">{{ $order->product_name ?? 'Produk' }}</p>
                                        <span class="text-[10px] font-semibold text-gray-400 uppercase">{{ $order->product_type ?? 'local' }}</span>
                                    </td>
                                    <td>
                                        <span class="font-black text-xs text-gray-900 dark:text-white">
                                            Rp {{ number_format($order->total_amount ?? $order->price ?? 0, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $st = strtolower($order->payment_status ?? 'pending');
                                        @endphp
                                        @if(in_array($st, ['paid', 'success', 'settlement']))
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                                Sukses
                                            </span>
                                        @elseif($st === 'pending')
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50">
                                                Pending
                                            </span>
                                        @else
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700">
                                                {{ ucfirst($st) }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards List -->
                <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
                    @foreach($recentOrders as $order)
                        <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="font-mono text-[11px] font-black text-gray-900 dark:text-white">#{{ $order->invoice_number }}</span>
                                    <span class="text-[9px] text-gray-400 font-medium">{{ $order->created_at ? $order->created_at->diffForHumans() : '-' }}</span>
                                </div>
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $order->product_name ?? 'Produk' }}</p>
                                <span class="text-[11px] font-black text-orange-500">Rp {{ number_format($order->total_amount ?? $order->price ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <div class="shrink-0 text-right">
                                @php
                                    $st = strtolower($order->payment_status ?? 'pending');
                                @endphp
                                @if(in_array($st, ['paid', 'success', 'settlement']))
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                        Sukses
                                    </span>
                                @elseif($st === 'pending')
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50">
                                        Pending
                                    </span>
                                @else
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700">
                                        {{ ucfirst($st) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-400">
                    <p class="text-xs font-semibold">Belum ada pesanan terbaru.</p>
                </div>
            @endif
        </div>

        <!-- Kolom Kanan: Status Gateway & Pintasan Cepat (1 Kolom) -->
        <div class="space-y-6">
            <!-- Kartu Status Server Payment Gateway -->
            <div class="admin-card p-6">
                <h3 class="text-base font-black text-gray-900 dark:text-white mb-1">Status Integrasi Sistem</h3>
                <p class="text-xs font-semibold text-gray-400 mb-4">Layanan gateway & API pihak ketiga</p>

                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">Payment Gateway Node.js</p>
                                <p class="text-[10px] text-gray-400 font-mono">Port 4061 (QRIS Realtime)</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                            Aktif
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">Database MySQL</p>
                                <p class="text-[10px] text-gray-400 font-mono">XAMPP Server</p>
                            </div>
                        </div>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400">
                            Terkoneksi
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <div>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">API Provider (Premku/Preflix)</p>
                                <p class="text-[10px] text-gray-400">Katalog Produk Otomatis</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.settings') }}" class="text-[10px] font-black text-orange-500 hover:underline">
                            Atur →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Pintasan Cepat Panel -->
            <div class="admin-card p-6">
                <h3 class="text-base font-black text-gray-900 dark:text-white mb-3">Aksi Cepat</h3>
                <div class="grid grid-cols-2 gap-2.5">
                    <a href="{{ route('admin.vouchers') }}" class="p-3 rounded-2xl bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800/40 text-center hover:scale-[1.02] transition-transform">
                        <span class="text-xl">🎟️</span>
                        <p class="text-xs font-black text-orange-600 dark:text-orange-400 mt-1">Buat Voucher</p>
                    </a>
                    <a href="{{ route('admin.resellers') }}" class="p-3 rounded-2xl bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-800/40 text-center hover:scale-[1.02] transition-transform">
                        <span class="text-xl">👥</span>
                        <p class="text-xs font-black text-purple-600 dark:text-purple-400 mt-1">Daftar Reseller</p>
                    </a>
                    <a href="{{ route('admin.products.api') }}" class="p-3 rounded-2xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 text-center hover:scale-[1.02] transition-transform">
                        <span class="text-xl">📈</span>
                        <p class="text-xs font-black text-blue-600 dark:text-blue-400 mt-1">Markup API</p>
                    </a>
                    <a href="{{ route('admin.wa_verifications') }}" class="p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40 text-center hover:scale-[1.02] transition-transform">
                        <span class="text-xl">📱</span>
                        <p class="text-xs font-black text-emerald-600 dark:text-emerald-400 mt-1">Verifikasi WA</p>
                    </a>
                </div>

                <!-- Tombol Reset Data Testing ke 0 (Siap Launching Publik) -->
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-white/5">
                    <button type="button" onclick="document.getElementById('resetLaunchModal').classList.remove('hidden'); document.getElementById('resetLaunchModal').classList.add('flex');" class="w-full py-2.5 px-3 rounded-2xl bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 border border-rose-200 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 font-black text-xs transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        <span>Reset Semua Data ke 0 (Launching)</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Konfirmasi Reset Database ke 0 untuk Launching -->
    <div id="resetLaunchModal" class="fixed inset-0 z-[100] bg-black/70 hidden items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white dark:bg-[#111827] rounded-3xl w-full max-w-lg p-6 sm:p-7 border border-gray-200 dark:border-white/10 shadow-2xl space-y-4">
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-white/10">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-rose-500/15 border border-rose-500/30 text-rose-500 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">Reset Database ke 0 (Siap Launching)</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Bersihkan seluruh data testing sebelum dibuka ke publik</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('resetLaunchModal').classList.add('hidden'); document.getElementById('resetLaunchModal').classList.remove('flex');" class="text-gray-400 hover:text-gray-200 text-lg font-bold cursor-pointer">✕</button>
            </div>

            <form action="{{ route('admin.system.reset_launch') }}" method="POST" class="space-y-4">
                @csrf
                <div class="p-3.5 rounded-2xl bg-rose-50/70 dark:bg-rose-950/30 border border-rose-200/70 dark:border-rose-800/40 text-xs text-rose-800 dark:text-rose-200 space-y-1.5 leading-relaxed">
                    <p class="font-black">Tindakan ini akan mengosongkan kembali ke 0:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-[11px] font-medium">
                        <li>Total Pendapatan, Grafik Penjualan & Riwayat Order (<code class="font-bold">0</code>)</li>
                        <li>Riwayat Mutasi Saldo, Top Up, Registrasi Reseller & Tiket Deposit (<code class="font-bold">0</code>)</li>
                        <li>Seluruh Produk Lokal, Stok Akun Lokal & Produk API lama (siap dipanggil ulang dari menu Admin)</li>
                        <li>Akun Admin tetap aman (saldo Admin direset ke <code class="font-bold">Rp 0</code>)</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="delete_test_users" value="1" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500">
                        <span>Hapus semua akun User/Member testing (kecuali Admin)</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" name="delete_vouchers" value="1" checked class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500">
                        <span>Kosongkan juga daftar kode Voucher testing</span>
                    </label>
                </div>

                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                        Ketik <span class="text-rose-500 font-mono">RESET</span> untuk konfirmasi:
                    </label>
                    <input type="text" name="confirm_text" required placeholder="Ketik RESET" autocomplete="off" class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs sm:text-sm font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500">
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-white/10">
                    <button type="button" onclick="document.getElementById('resetLaunchModal').classList.add('hidden'); document.getElementById('resetLaunchModal').classList.remove('flex');" class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black text-xs shadow-lg shadow-rose-600/25 transition cursor-pointer">
                        Kosongkan ke 0 Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Inisialisasi Chart.js Lokal dengan Gradient Halus & Theme Awareness -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('revenueChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        let chartInstance = null;

        function renderChart() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';

            // Gradient Fills
            const blueGradient = ctx.createLinearGradient(0, 0, 0, 320);
            blueGradient.addColorStop(0, 'rgba(37, 99, 235, 0.35)');
            blueGradient.addColorStop(1, 'rgba(37, 99, 235, 0.00)');

            const orangeGradient = ctx.createLinearGradient(0, 0, 0, 320);
            orangeGradient.addColorStop(0, 'rgba(249, 115, 22, 0.35)');
            orangeGradient.addColorStop(1, 'rgba(249, 115, 22, 0.00)');

            const labels = {!! json_encode($days ?? ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']) !!};
            const apiData = {!! json_encode($apiDaily ?? [0, 0, 0, 0, 0, 0, 0]) !!};
            const localData = {!! json_encode($localDaily ?? [0, 0, 0, 0, 0, 0, 0]) !!};

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Produk API',
                            data: apiData,
                            borderColor: '#2563eb',
                            backgroundColor: blueGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.38,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Produk Lokal',
                            data: localData,
                            borderColor: '#f97316',
                            backgroundColor: orangeGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.38,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#f97316',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#f8fafc' : '#0f172a',
                            bodyColor: isDark ? '#cbd5e1' : '#334155',
                            borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return ' ' + context.dataset.label + ': Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: textColor,
                                font: { weight: '600', size: 11 },
                                callback: function(value) {
                                    if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                    if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'k';
                                    return 'Rp ' + value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: textColor,
                                font: { weight: '600', size: 11 }
                            }
                        }
                    }
                }
            });
        }

        renderChart();

        // Listener jika tombol dark mode ditekan
        window.addEventListener('themeChanged', function() {
            setTimeout(renderChart, 100);
        });
    });
</script>
@endsection