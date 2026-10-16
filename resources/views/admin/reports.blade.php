@extends('layouts.admin')

@section('title', 'Grafik & Laporan Penjualan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 sm:space-y-8" x-data="{ customRange: '{{ $period }}' === 'custom' }">
    
    <!-- Top Filter & Action Bar -->
    <div class="admin-card p-5 sm:p-6 bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            
            <!-- Title & Current Period Indicator -->
            <div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/40 text-[11px] font-black text-orange-600 dark:text-orange-400 uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                    <span>Periode Aktif: {{ $periodLabel }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                    Laporan Eksekutif Penjualan
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                    Monitoring data omset, volume pesanan, dan konversi toko dalam rentang waktu yang dipilih.
                </p>
            </div>

            <!-- Filter Controls & Export Button -->
            <div class="flex flex-wrap items-center gap-3">
                
                <!-- Periode Dropdown Form -->
                <form action="{{ route('admin.reports') }}" method="GET" class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <select 
                            name="period" 
                            id="periodSelect"
                            onchange="if(this.value === 'custom') { document.getElementById('customDateBox').classList.remove('hidden'); } else { this.form.submit(); }"
                            class="appearance-none bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 pr-9 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500/50 cursor-pointer shadow-sm"
                        >
                            <option value="7_days" {{ $period === '7_days' ? 'selected' : '' }}>📅 7 Hari Terakhir</option>
                            <option value="30_days" {{ $period === '30_days' ? 'selected' : '' }}>📅 30 Hari Terakhir (1 Bulan)</option>
                            <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>🗓️ Bulan Ini ({{ now()->translatedFormat('M Y') }})</option>
                            <option value="last_month" {{ $period === 'last_month' ? 'selected' : '' }}>🗓️ Bulan Lalu ({{ now()->subMonth()->translatedFormat('M Y') }})</option>
                            <option value="this_year" {{ $period === 'this_year' ? 'selected' : '' }}>📈 Tahun Ini ({{ now()->format('Y') }})</option>
                            <option value="all" {{ $period === 'all' ? 'selected' : '' }}>🌐 Semua Waktu</option>
                            <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>⚙️ Kustom Tanggal...</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Custom Date Input (muncul jika custom) -->
                    <div id="customDateBox" class="{{ $period === 'custom' ? 'flex' : 'hidden' }} items-center gap-2">
                        <input 
                            type="date" 
                            name="start_date" 
                            value="{{ $startDateInput ?? $startDate->format('Y-m-d') }}"
                            class="bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500/50"
                        />
                        <span class="text-xs text-gray-400 font-bold">s/d</span>
                        <input 
                            type="date" 
                            name="end_date" 
                            value="{{ $endDateInput ?? $endDate->format('Y-m-d') }}"
                            class="bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-xl px-3 py-2 text-xs font-bold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-orange-500/50"
                        />
                        <button type="submit" class="px-3 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-xl text-xs font-black shadow-sm transition-colors cursor-pointer">
                            Terapkan
                        </button>
                    </div>
                </form>

                <!-- Tombol Ekspor ke Excel (.csv) -->
                <a 
                    href="{{ route('admin.reports.export', request()->all()) }}" 
                    class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition-all shadow-md shadow-emerald-600/20 flex items-center gap-2 cursor-pointer group"
                    title="Unduh seluruh data transaksi dalam format Excel (.csv)"
                >
                    <svg class="w-4 h-4 text-emerald-200 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Ekspor ke Excel</span>
                </a>

            </div>

        </div>
    </div>

    <!-- 4 Kartu Metrik Performa Bisnis & Laba Bersih -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Kartu 1: Total Omset Lunas (Kotor) -->
        <div class="stat-card card-blue">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Omset (Kotor)</span>
                <div class="w-10 h-10 rounded-2xl gradient-blue text-white flex items-center justify-center shadow-md shadow-blue-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center text-[10px] font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/40">
                    {{ $successOrdersCount }} Pesanan Lunas
                </span>
            </div>
        </div>

        <!-- Kartu 2: Estimasi Beban Modal / HPP -->
        <div class="stat-card card-orange">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Estimasi Modal (HPP)</span>
                <div class="w-10 h-10 rounded-2xl gradient-orange text-white flex items-center justify-center shadow-md shadow-orange-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                Rp {{ number_format($totalCost, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Modal API & Provider</span>
            </div>
        </div>

        <!-- Kartu 3: KEUNTUNGAN BERSIH (NET PROFIT) -->
        <div class="stat-card relative overflow-hidden bg-gradient-to-br from-emerald-500/10 via-teal-500/5 to-emerald-500/15 border-2 border-emerald-500/40 dark:border-emerald-500/30 shadow-lg shadow-emerald-500/10">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-black text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Keuntungan Bersih</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center shadow-md shadow-emerald-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">
                Rp {{ number_format($totalProfit, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center text-[10px] font-black text-white bg-emerald-600 px-2.5 py-0.5 rounded-full shadow-sm">
                    Margin: {{ $profitMargin }}%
                </span>
                <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300">Laba Bersih</span>
            </div>
        </div>

        <!-- Kartu 4: Volume & Tingkat Keberhasilan -->
        <div class="stat-card card-purple">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Transaksi Masuk</span>
                <div class="w-10 h-10 rounded-2xl gradient-purple text-white flex items-center justify-center shadow-md shadow-purple-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $totalOrdersCount }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center text-[10px] font-black text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/40 px-2 py-0.5 rounded-full border border-purple-200 dark:border-purple-800/40">
                    {{ $successRate }}% Tingkat Sukses
                </span>
            </div>
        </div>

    </div>

    <!-- Area Grafik Interaktif (Chart.js Lokal) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Grafik Utama: Tren Omset & Keuntungan Bersih (2 Kolom) -->
        <div class="lg:col-span-2 admin-card p-6 sm:p-7">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>📈 Tren Penjualan &amp; Laba Bersih</span>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                            Realtime
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400 font-medium">Monitoring perbandingan omset kotor vs keuntungan bersih riil toko</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Toggle Periode vs 12 Bulan -->
                    <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200/80 dark:border-white/10 text-xs font-bold">
                        <button type="button" id="btnChartPeriod" class="px-2.5 py-1 rounded-lg transition-all cursor-pointer bg-orange-500 text-white shadow-sm font-black">
                            Periode Ini
                        </button>
                        <button type="button" id="btnChartMonthly" class="px-2.5 py-1 rounded-lg transition-all cursor-pointer text-gray-600 dark:text-gray-300 hover:text-orange-500 font-bold">
                            12 Bulan
                        </button>
                    </div>

                    <!-- Legend Indikator -->
                    <div class="flex items-center gap-3 text-[11px] font-black pl-2">
                        <div class="flex items-center gap-1.5 text-orange-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span>Omset</span>
                        </div>
                        <div class="flex items-center gap-1.5 text-emerald-500">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Laba Bersih</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="relative h-72 sm:h-80 w-full">
                <canvas id="reportChart"></canvas>
            </div>
        </div>

        <!-- Kolom Kanan: Top Produk Terlaris & Komposisi (1 Kolom) -->
        <div class="space-y-6">
            
            <!-- Card Top 5 Produk Terlaris -->
            <div class="admin-card p-6">
                <h3 class="text-base font-black text-gray-900 dark:text-white mb-1">👑 Top 5 Produk Terlaris</h3>
                <p class="text-xs text-gray-400 font-medium mb-4">Paling diminati berdasarkan jumlah pembelian lunas</p>

                @if(count($topProducts) > 0)
                    <div class="space-y-3">
                        @foreach($topProducts as $idx => $tp)
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-7 h-7 rounded-xl flex items-center justify-center font-black text-xs {{ $idx === 0 ? 'bg-amber-400 text-gray-950' : ($idx === 1 ? 'bg-gray-300 text-gray-900' : ($idx === 2 ? 'bg-amber-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-500')) }}">
                                        #{{ $idx + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[150px]" title="{{ $tp->product_name }}">
                                            {{ $tp->product_name }}
                                        </p>
                                        <p class="text-[10px] text-gray-400 font-medium">
                                            {{ $tp->sales_count }}x Terjual
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-black text-orange-500 whitespace-nowrap">
                                    Rp {{ number_format($tp->total_nominal, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-400">
                        <p class="text-xs font-semibold">Belum ada transaksi di periode ini.</p>
                    </div>
                @endif
            </div>

            <!-- Card Distribusi Kategori / Tipe -->
            <div class="admin-card p-6">
                <h3 class="text-base font-black text-gray-900 dark:text-white mb-1">📊 Komposisi Pemenuhan</h3>
                <p class="text-xs text-gray-400 font-medium mb-4">Perbandingan pesanan otomatis vs manual lokal</p>

                <div class="space-y-3">
                    <div class="p-3 rounded-2xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-800/30">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300">⚡ Sistem Otomatis</span>
                            <span class="text-xs font-black text-blue-700 dark:text-blue-300">{{ $apiCount }} Pesanan</span>
                        </div>
                        <p class="text-xs font-black text-gray-900 dark:text-white">Rp {{ number_format($apiRevenue, 0, ',', '.') }}</p>
                    </div>

                    <div class="p-3 rounded-2xl bg-orange-50/60 dark:bg-orange-950/20 border border-orange-200/60 dark:border-orange-800/30">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-orange-700 dark:text-orange-300">📦 Stok Manual Lokal</span>
                            <span class="text-xs font-black text-orange-700 dark:text-orange-300">{{ $localCount }} Pesanan</span>
                        </div>
                        <p class="text-xs font-black text-gray-900 dark:text-white">Rp {{ number_format($localRevenue, 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- TABEL REKAP KEUNTUNGAN BERSIH BULANAN -->
    <div class="admin-card overflow-hidden bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl shadow-sm">
        <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-black text-lg">
                    💰
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Laporan Keuntungan Bersih Bulanan</span>
                        <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                            12 Bulan Terakhir
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-0.5">
                        Rincian riil omset kotor, modal provider (HPP), dan laba bersih per bulan
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left admin-table">
                <thead>
                    <tr>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400">Bulan &amp; Tahun</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400 text-center">Pesanan Lunas</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400">Total Omset Kotor</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400">Modal Provider (HPP)</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400">Keuntungan Bersih</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400 text-center">Margin Laba</th>
                        <th class="py-3.5 px-4 text-xs font-black uppercase text-gray-500 dark:text-gray-400 text-right">Status Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse(array_reverse($monthlyReports) as $mr)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                            <td class="py-3.5 px-4 font-black text-xs text-gray-900 dark:text-white">
                                {{ $mr['label'] }}
                                @if($mr['year'] == now()->year && $mr['month'] == now()->month)
                                    <span class="ml-1.5 text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400">
                                        Bulan Berjalan
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 dark:bg-white/5 text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $mr['order_count'] }} Order
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-bold text-xs text-blue-600 dark:text-blue-400">
                                Rp {{ number_format($mr['revenue'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 font-bold text-xs text-gray-500 dark:text-gray-400">
                                Rp {{ number_format($mr['cost'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 font-black text-sm text-emerald-600 dark:text-emerald-400">
                                Rp {{ number_format($mr['profit'], 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center text-[11px] font-black {{ $mr['margin'] >= 25 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $mr['margin'] }}%
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                @if($mr['profit'] > 0)
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2.5 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                        <span>✓</span> Laba Sehat
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2.5 py-1 rounded-full bg-gray-100 dark:bg-white/5 text-gray-400 border border-gray-200 dark:border-white/10">
                                        Belum Ada
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400 text-xs font-bold">
                                Belum ada data transaksi bulanan yang terekam.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Rincian Data Transaksi Periode Ini -->
    <div class="admin-card overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">Rincian Transaksi ({{ $periodLabel }})</h3>
                <p class="text-xs text-gray-400 font-medium">Menampilkan seluruh data invoice pelanggan yang masuk di rentang ini</p>
            </div>
            
            <a 
                href="{{ route('admin.reports.export', request()->all()) }}" 
                class="px-3.5 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 text-emerald-600 dark:text-emerald-400 font-bold text-xs hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors flex items-center gap-1.5 cursor-pointer shrink-0"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Unduh Excel Tabel Ini</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left admin-table">
                <thead>
                    <tr>
                        <th>Invoice & Waktu</th>
                        <th>Pelanggan</th>
                        <th>Produk</th>
                        <th>Nominal</th>
                        <th>Status Bayar</th>
                        <th>Status Pesanan</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                        <tr>
                            <!-- Kolom Invoice -->
                            <td>
                                <p class="font-mono text-xs font-bold text-gray-900 dark:text-white">#{{ $o->invoice_number }}</p>
                                <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $o->created_at ? $o->created_at->format('d/m/Y, H:i') : '-' }} WIB</p>
                            </td>

                            <!-- Kolom Pelanggan -->
                            <td>
                                <p class="font-bold text-xs text-gray-900 dark:text-white">{{ $o->user ? $o->user->name : 'Tamu' }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-gray-400 font-mono">{{ $o->email }}</span>
                                    @if($o->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $o->whatsapp) }}" target="_blank" class="text-[10px] text-emerald-500 font-bold hover:underline inline-flex items-center gap-0.5">
                                            <span>WA</span> ↗
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Produk -->
                            <td>
                                <p class="font-bold text-xs text-gray-900 dark:text-white truncate max-w-[200px]" title="{{ $o->product_name }}">
                                    {{ $o->product_name }}
                                </p>
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full {{ $o->product_type === 'api' ? 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-400' }}">
                                    {{ $o->product_type === 'api' ? '⚡ Otomatis' : '📦 Manual Lokal' }}
                                </span>
                            </td>

                            <!-- Kolom Nominal -->
                            <td>
                                <span class="font-black text-xs text-gray-900 dark:text-white">
                                    Rp {{ number_format($o->total_amount ?? $o->price ?? 0, 0, ',', '.') }}
                                </span>
                                @if($o->voucher_code)
                                    <span class="block text-[9px] font-bold text-orange-500">Diskon: {{ $o->voucher_code }}</span>
                                @endif
                            </td>

                            <!-- Kolom Status Bayar -->
                            <td>
                                @php
                                    $pst = strtolower($o->payment_status ?? 'pending');
                                @endphp
                                @if(in_array($pst, ['paid', 'success', 'settlement']))
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                        ✓ Lunas
                                    </span>
                                @elseif($pst === 'pending')
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50">
                                        ⏳ Menunggu
                                    </span>
                                @else
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800/50">
                                        ✕ Batal
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom Status Pesanan -->
                            <td>
                                @php
                                    $ost = strtolower($o->order_status ?? 'pending');
                                @endphp
                                @if($ost === 'completed')
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                                        Selesai
                                    </span>
                                @elseif($ost === 'processing')
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/30">
                                        Diproses
                                    </span>
                                @else
                                    <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-500">
                                        {{ ucfirst($ost) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="text-right">
                                <a 
                                    href="{{ url('/order/invoice/' . $o->invoice_number) }}" 
                                    target="_blank"
                                    class="text-xs font-bold text-orange-500 hover:text-orange-600 hover:underline inline-flex items-center gap-1 cursor-pointer"
                                >
                                    <span>Detail</span> ↗
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                Tidak ada data transaksi yang ditemukan pada rentang periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-white/5">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

</div>

<!-- Inisialisasi Chart.js Lokal -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('reportChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        let chartInstance = null;
        let currentMode = 'period'; // 'period' atau 'monthly'

        const periodLabels = {!! json_encode($chartLabels) !!};
        const periodRevenue = {!! json_encode($chartRevenue) !!};
        const periodProfit = {!! json_encode($chartProfit) !!};

        const monthlyLabels = {!! json_encode($monthlyLabels) !!};
        const monthlyRevenue = {!! json_encode($monthlyRevenueData) !!};
        const monthlyProfit = {!! json_encode($monthlyProfitData) !!};

        const btnPeriod = document.getElementById('btnChartPeriod');
        const btnMonthly = document.getElementById('btnChartMonthly');

        function updateToggleButtons() {
            if (!btnPeriod || !btnMonthly) return;
            if (currentMode === 'period') {
                btnPeriod.className = 'px-2.5 py-1 rounded-lg transition-all cursor-pointer bg-orange-500 text-white shadow-sm font-black';
                btnMonthly.className = 'px-2.5 py-1 rounded-lg transition-all cursor-pointer text-gray-600 dark:text-gray-300 hover:text-orange-500 font-bold';
            } else {
                btnMonthly.className = 'px-2.5 py-1 rounded-lg transition-all cursor-pointer bg-emerald-500 text-white shadow-sm font-black';
                btnPeriod.className = 'px-2.5 py-1 rounded-lg transition-all cursor-pointer text-gray-600 dark:text-gray-300 hover:text-emerald-500 font-bold';
            }
        }

        if (btnPeriod) {
            btnPeriod.addEventListener('click', function() {
                currentMode = 'period';
                updateToggleButtons();
                renderChart();
            });
        }

        if (btnMonthly) {
            btnMonthly.addEventListener('click', function() {
                currentMode = 'monthly';
                updateToggleButtons();
                renderChart();
            });
        }

        function renderChart() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';

            const orangeGradient = ctx.createLinearGradient(0, 0, 0, 320);
            orangeGradient.addColorStop(0, 'rgba(249, 115, 22, 0.35)');
            orangeGradient.addColorStop(1, 'rgba(249, 115, 22, 0.00)');

            const emeraldGradient = ctx.createLinearGradient(0, 0, 0, 320);
            emeraldGradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
            emeraldGradient.addColorStop(1, 'rgba(16, 185, 129, 0.00)');

            const labels = currentMode === 'period' ? periodLabels : monthlyLabels;
            const revenueData = currentMode === 'period' ? periodRevenue : monthlyRevenue;
            const profitData = currentMode === 'period' ? periodProfit : monthlyProfit;

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Omset Kotor',
                            data: revenueData,
                            borderColor: '#f97316',
                            backgroundColor: orangeGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#f97316',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Keuntungan Bersih',
                            data: profitData,
                            borderColor: '#10b981',
                            backgroundColor: emeraldGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointHoverRadius: 7,
                            pointBackgroundColor: '#10b981',
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
                        legend: { display: false },
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

        window.addEventListener('themeChanged', function() {
            setTimeout(renderChart, 100);
        });
    });
</script>
@endsection

