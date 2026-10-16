@extends('layouts.admin')

@section('title', 'Kelola Produk & Margin PPOB Kiosbank')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="ppobAdmin()">

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Mode Developer Notice -->
    @if($kiosbankMode === 'development')
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-300 font-medium text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500 text-black shadow-sm">
                    ⚡ DEVELOPER MODE (SANDBOX)
                </span>
                <span>Mode pengembangan aktif. Transaksi, inquiry, dan saldo berjalan secara sandbox (aman untuk testing).</span>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.settings') }}" class="font-bold underline hover:text-amber-600 dark:hover:text-amber-200">Ubah di Pengaturan API →</a>
            </div>
        </div>
    @endif

    <!-- Header Banner & Realtime Deposit Hub -->
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 bg-gradient-to-r from-gray-900 via-slate-800 to-gray-900 text-white shadow-xl border border-white/10 flex flex-col xl:flex-row items-center justify-between gap-6">
        <div class="text-center xl:text-left w-full xl:w-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 text-xs font-black uppercase tracking-wider mb-2 border border-cyan-500/30">
                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                <span>Kiosbank Open API Host-to-Host</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Katalog Produk PPOB & Penetapan Margin</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1 max-w-xl font-medium">
                Atur margin keuntungan tiap produk pulsa, token PLN, game, dan e-wallet. Tinjau harga modal asli dari API Kiosbank secara transparan.
            </p>
        </div>
        
        <!-- Live Deposit Widget Quick Access -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto bg-black/40 p-3 rounded-2xl border border-white/10 backdrop-blur-md">
            <div class="px-4 py-1.5 w-full sm:w-auto">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">Saldo Deposit Kiosbank</span>
                    @if($depositStatus['ok'])
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                    @endif
                </div>
                <div class="text-xl sm:text-2xl font-black text-emerald-400">
                    Rp {{ number_format($depositStatus['saldo'] ?? 0, 0, ',', '.') }}
                </div>
            </div>
            
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="{{ route('admin.ppob.deposit') }}" class="flex-1 sm:flex-none gradient-emerald text-white font-black py-2.5 px-4 rounded-xl text-xs transition shadow-md hover:opacity-90 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Top Up Saldo</span>
                </a>

                <form action="{{ route('admin.ppob.products.sync') }}" method="POST" onsubmit="return confirm('Sinkronkan status produk & katalog dari API Kiosbank sekarang?');">
                    @csrf
                    <button type="submit" class="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white transition text-xs font-bold" title="Sinkronkan dari API Kiosbank">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Produk PPOB</span>
            <div class="text-2xl font-black text-gray-900 dark:text-white">{{ $totalCount }}</div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Katalog Resmi Kiosbank</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm">
            <span class="text-[11px] font-bold text-emerald-500 uppercase tracking-wider block mb-1">Produk Aktif</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                <span>{{ $activeCount }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-bold">Siap Jual</span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Tampil di dashboard pembeli</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm">
            <span class="text-[11px] font-bold text-amber-500 uppercase tracking-wider block mb-1">Status Maintenance</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 flex items-center gap-2">
                <span>{{ $maintenanceCount }}</span>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 font-bold">Biller Gangguan</span>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Otomatis dari API DJI</p>
        </div>

        <div class="p-5 rounded-2xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm">
            <span class="text-[11px] font-bold text-cyan-500 uppercase tracking-wider block mb-1">Rata-rata Margin</span>
            <div class="text-2xl font-black text-cyan-600 dark:text-cyan-400">
                Rp {{ number_format($avgMargin, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">Keuntungan per transaksi</p>
        </div>
    </div>

    <!-- TOOL PENETAPAN MARGIN MASSAL (BULK UPDATE) -->
    <div class="p-6 rounded-3xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-4">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Penetapan Margin / Untung Massal</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Terapkan keuntungan toko secara serentak ke seluruh produk atau kategori tertentu dengan satu klik.</p>
            </div>
        </div>

        <form action="{{ route('admin.ppob.products.bulk_margin') }}" method="POST" onsubmit="return confirm('Terapkan margin massal ini ke produk terpilih?');" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            @csrf
            
            <!-- Target Kategori -->
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Target Kategori</label>
                <select name="target_scope" class="w-full py-2.5 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    <option value="all" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Semua Kategori (Semua Produk)</option>
                    <option value="pulsa" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Pulsa Prabayar</option>
                    <option value="pln" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Token PLN Listrik</option>
                    <option value="game" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Voucher Game</option>
                    <option value="ewallet" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Saldo E-Wallet</option>
                    <option value="streaming" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Aplikasi Streaming</option>
                    <option value="tagihan" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Hanya Tagihan & PDAM</option>
                </select>
            </div>

            <!-- Tipe Perhitungan -->
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Tipe Margin</label>
                <select name="calc_type" class="w-full py-2.5 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    <option value="fixed" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Nominal Tetap (Rp)</option>
                    <option value="percent" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Persentase (%)</option>
                </select>
            </div>

            <!-- Nilai Margin -->
            <div>
                <label class="block text-[11px] font-black text-gray-500 uppercase tracking-wider mb-1">Nilai Untung / Margin</label>
                <div class="relative">
                    <input type="number" name="mass_margin" placeholder="Cth: 1500" required min="0" class="w-full py-2.5 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-end">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl gradient-orange text-white font-black text-xs shadow-md hover:opacity-90 transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Terapkan Massal</span>
                </button>
            </div>
        </form>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="p-5 rounded-3xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('admin.ppob.products') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Search -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Cari Produk / SKU</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Nama, kode SKU, operator..." class="w-full py-2 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-semibold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
            </div>

            <!-- Kategori -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Kategori</label>
                <select name="category" onchange="this.form.submit()" class="w-full py-2 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-semibold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    <option value="all" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $category === $cat ? 'selected' : '' }}>{{ strtoupper($cat) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Operator -->
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Operator / Brand</label>
                <select name="operator" onchange="this.form.submit()" class="w-full py-2 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-semibold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    <option value="all" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">Semua Operator</option>
                    @foreach($operators as $op)
                        <option value="{{ $op }}" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $operator === $op ? 'selected' : '' }}>{{ $op }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-1">Status Produk</label>
                    <select name="status" onchange="this.form.submit()" class="w-full py-2 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-semibold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                        <option value="all" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $status === 'all' ? 'selected' : '' }}>Semua Status</option>
                        <option value="active" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $status === 'active' ? 'selected' : '' }}>🟢 Aktif</option>
                        <option value="maintenance" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $status === 'maintenance' ? 'selected' : '' }}>🟡 Maintenance</option>
                        <option value="inactive" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white" {{ $status === 'inactive' ? 'selected' : '' }}>🔴 Nonaktif</option>
                    </select>
                </div>
                <button type="submit" class="py-2 px-3.5 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-bold text-xs transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL DATA PRODUK PPOB LENGKAP -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white">Daftar Produk PPOB ({{ $products->total() }})</h3>
                <p class="text-xs text-gray-400">Harga Modal API adalah harga asli dari Kiosbank. Harga Jual = Modal + Margin.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-white/[0.02] border-b border-gray-100 dark:border-white/5 text-gray-400 uppercase tracking-wider font-extrabold text-[10px]">
                    <tr>
                        <th class="py-3.5 px-4">SKU / Kode</th>
                        <th class="py-3.5 px-4">Nama Produk & Operator</th>
                        <th class="py-3.5 px-4">Kategori</th>
                        <th class="py-3.5 px-4 text-rose-500 font-black">Harga Modal API (DJI)</th>
                        <th class="py-3.5 px-4 text-cyan-500 font-black">Margin Toko (Untung)</th>
                        <th class="py-3.5 px-4 text-emerald-500 font-black">Harga Jual Web</th>
                        <th class="py-3.5 px-4">Harga Reseller</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5 font-semibold text-gray-700 dark:text-gray-300">
                    @forelse($products as $p)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                            <!-- Kode SKU -->
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white text-xs">
                                <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300">
                                    {{ $p->code }}
                                </span>
                            </td>

                            <!-- Nama Produk & Operator -->
                            <td class="py-3.5 px-4">
                                <div class="font-black text-gray-900 dark:text-white text-xs">{{ $p->name }}</div>
                                <div class="text-[11px] text-gray-400 flex items-center gap-1.5 mt-0.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                                    <span>{{ $p->operator }}</span>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-white/10">
                                    {{ $p->category }}
                                </span>
                            </td>

                            <!-- Harga Modal Asli API Kiosbank (Base Price) -->
                            <td class="py-3.5 px-4 font-mono font-black text-rose-600 dark:text-rose-400 text-xs">
                                Rp {{ number_format($p->base_price, 0, ',', '.') }}
                            </td>

                            <!-- Margin / Untung -->
                            <td class="py-3.5 px-4 font-mono font-black text-cyan-600 dark:text-cyan-400 text-xs">
                                + Rp {{ number_format($p->margin, 0, ',', '.') }}
                            </td>

                            <!-- Harga Jual Pelanggan -->
                            <td class="py-3.5 px-4 font-mono font-black text-emerald-600 dark:text-emerald-400 text-xs">
                                Rp {{ number_format($p->price, 0, ',', '.') }}
                            </td>

                            <!-- Harga Reseller -->
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-500 dark:text-gray-400 text-xs">
                                @if($p->reseller_price)
                                    Rp {{ number_format($p->reseller_price, 0, ',', '.') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($p->status === 'active')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                                        🟢 Aktif
                                    </span>
                                @elseif($p->status === 'maintenance')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40">
                                        🟡 Gangguan
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800/40">
                                        🔴 Nonaktif
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                <button type="button" 
                                        @click="openEditModal({{ json_encode($p) }})"
                                        class="py-1.5 px-3 rounded-xl bg-gray-100 dark:bg-white/10 hover:bg-cyan-500 hover:text-white dark:hover:bg-cyan-500 text-gray-700 dark:text-gray-300 font-bold text-xs transition cursor-pointer">
                                    Edit Margin
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center mx-auto mb-3 text-gray-400 text-xl font-black">
                                    📦
                                </div>
                                <p class="text-xs font-semibold">Tidak ada produk PPOB yang sesuai dengan filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($products->hasPages())
            <div class="p-5 border-t border-gray-100 dark:border-white/5">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL EDIT MARGIN & PRODUK PPOB -->
    <div x-show="isEditModalOpen" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="isEditModalOpen = false" 
             class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/10 rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative space-y-5 animate-in fade-in zoom-in-95 duration-200">
            
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-cyan-500 block mb-0.5">Edit Produk PPOB</span>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white" x-text="editForm.name"></h3>
                    <p class="text-xs text-gray-400 font-mono mt-0.5" x-text="'Kode SKU: ' + editForm.code"></p>
                </div>
                <button type="button" @click="isEditModalOpen = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl">✕</button>
            </div>

            <!-- Box Modal API Asli -->
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/40 flex items-center justify-between">
                <div>
                    <span class="text-[10px] font-black uppercase text-rose-600 dark:text-rose-400 block tracking-wider">Harga Modal Asli API Kiosbank</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Diberikan oleh Biller Server Pusat DJI</span>
                </div>
                <div class="text-lg font-black font-mono text-rose-600 dark:text-rose-400" x-text="'Rp ' + Number(editForm.base_price).toLocaleString('id-ID')"></div>
            </div>

            <form :action="'{{ url('/admin/ppob/products') }}/' + editForm.id + '/update'" method="POST" class="space-y-4">
                @csrf
                
                <!-- Input Margin -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">
                        Margin / Keuntungan Toko (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 font-bold text-xs text-gray-400">Rp</span>
                        <input type="number" 
                               name="margin" 
                               x-model="editForm.margin" 
                               @input="calculateSellingPrice()"
                               required 
                               min="0"
                               class="w-full py-2.5 pl-10 pr-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <!-- Preview Harga Jual -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">
                        Harga Jual ke Pelanggan (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 font-bold text-xs text-gray-400">Rp</span>
                        <input type="number" 
                               name="price" 
                               x-model="editForm.price" 
                               required 
                               min="0"
                               class="w-full py-2.5 pl-10 pr-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-black text-emerald-600 dark:text-emerald-400 focus:outline-none focus:border-cyan-500">
                    </div>
                    <span class="text-[10px] text-gray-400 mt-1 block">Otomatis dihitung dari Modal (Rp <span x-text="Number(editForm.base_price).toLocaleString('id-ID')"></span>) + Margin.</span>
                </div>

                <!-- Harga Khusus Reseller -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">
                        Harga Khusus Reseller VIP (Rp)
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 font-bold text-xs text-gray-400">Rp</span>
                        <input type="number" 
                               name="reseller_price" 
                               x-model="editForm.reseller_price" 
                               min="0"
                               class="w-full py-2.5 pl-10 pr-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <!-- Status Produk -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">Status Ketersediaan</label>
                    <select name="status" x-model="editForm.status" class="w-full py-2.5 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-900 dark:text-white focus:outline-none focus:border-cyan-500">
                        <option value="active" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">🟢 Aktif (Tampil & Dapat Dipesan)</option>
                        <option value="maintenance" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">🟡 Gangguan / Maintenance</option>
                        <option value="inactive" class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white">🔴 Nonaktif (Sembunyikan dari Toko)</option>
                    </select>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-white/5">
                    <button type="button" @click="isEditModalOpen = false" class="py-2.5 px-4 rounded-xl bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-200 transition">
                        Batal
                    </button>
                    <button type="submit" class="py-2.5 px-5 rounded-xl gradient-cyan text-white font-black text-xs shadow-md hover:opacity-90 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ppobAdmin() {
    return {
        isEditModalOpen: false,
        editForm: {
            id: null,
            code: '',
            name: '',
            base_price: 0,
            margin: 0,
            price: 0,
            reseller_price: 0,
            status: 'active'
        },
        openEditModal(product) {
            this.editForm = {
                id: product.id,
                code: product.code,
                name: product.name,
                base_price: Number(product.base_price),
                margin: Number(product.margin),
                price: Number(product.price),
                reseller_price: product.reseller_price ? Number(product.reseller_price) : Number(product.price) - 300,
                status: product.status
            };
            this.isEditModalOpen = true;
        },
        calculateSellingPrice() {
            const base = Number(this.editForm.base_price) || 0;
            const margin = Number(this.editForm.margin) || 0;
            this.editForm.price = base + margin;
            this.editForm.reseller_price = Math.max(0, this.editForm.price - 300);
        }
    }
}
</script>
@endsection

