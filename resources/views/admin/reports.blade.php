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

    <!-- 4 Kartu Metrik Performa Bisnis -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        
        <!-- Kartu 1: Total Omset Lunas -->
        <div class="stat-card card-blue">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Omset (Lunas)</span>
                <div class="w-10 h-10 rounded-2xl gradient-blue text-white flex items-center justify-center shadow-md shadow-blue-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center text-[10px] font-black text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/40">
                    {{ $successOrdersCount }} Transaksi Lunas
                </span>
            </div>
        </div>

        <!-- Kartu 2: Volume Pesanan Masuk -->
        <div class="stat-card card-green">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Total Pesanan</span>
                <div class="w-10 h-10 rounded-2xl gradient-green text-white flex items-center justify-center shadow-md shadow-emerald-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $totalOrdersCount }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="inline-flex items-center text-[10px] font-black text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/40">
                    {{ $successRate }}% Tingkat Sukses
                </span>
            </div>
        </div>

        <!-- Kartu 3: Rata-rata Nilai Pesanan (AOV) -->
        <div class="stat-card card-purple">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Rata-rata Order (AOV)</span>
                <div class="w-10 h-10 rounded-2xl gradient-purple text-white flex items-center justify-center shadow-md shadow-purple-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                Rp {{ number_format($avgOrderValue, 0, ',', '.') }}
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[11px] font-medium text-gray-400">Per Pembelian Lunas</span>
            </div>
        </div>

        <!-- Kartu 4: Status Transaksi Tertunda & Batal -->
        <div class="stat-card card-orange">
            <div class="stat-ambient"></div>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Pending & Batal</span>
                <div class="w-10 h-10 rounded-2xl gradient-orange text-white flex items-center justify-center shadow-md shadow-orange-500/25">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
            <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                {{ $pendingOrdersCount }} <span class="text-xs font-normal text-gray-400">Pending /</span> {{ $failedOrdersCount }} <span class="text-xs font-normal text-rose-500">Batal</span>
            </h3>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-[11px] font-medium text-gray-400">Dalam periode ini</span>
            </div>
        </div>

    </div>

    <!-- Area Grafik Interaktif (Chart.js Lokal) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Grafik Utama: Tren Omset Penjualan (2 Kolom) -->
        <div class="lg:col-span-2 admin-card p-6 sm:p-7">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>📈 Tren Penjualan Harian</span>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                            Realtime
                        </span>
                    </h3>
                    <p class="text-xs text-gray-400 font-medium">Grafik omset harian yang berhasil dibayarkan pelanggan</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-bold text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                        <span class="dark:text-gray-300">Omset (Rp)</span>
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

        function renderChart() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.04)';

            const orangeGradient = ctx.createLinearGradient(0, 0, 0, 320);
            orangeGradient.addColorStop(0, 'rgba(249, 115, 22, 0.40)');
            orangeGradient.addColorStop(1, 'rgba(249, 115, 22, 0.00)');

            const labels = {!! json_encode($chartLabels) !!};
            const revenueData = {!! json_encode($chartRevenue) !!};

            if (chartInstance) {
                chartInstance.destroy();
            }

            chartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Omset Lunas (Rp)',
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
                                    return ' Omset: Rp ' + Number(context.parsed.y).toLocaleString('id-ID');
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

