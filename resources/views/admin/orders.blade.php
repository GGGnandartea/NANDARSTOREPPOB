@extends('layouts.admin')
@section('title', 'Riwayat Pesanan')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{ 
    selectedOrder: null, 
    openModal: false, 
    statusAction: 'completed', 
    accountCredentials: '',
    viewAccountModal: false,
    viewAccountContent: ''
}">

    <!-- Alert Sukses -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Header & Quick Stat Filter Pills -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Riwayat Transaksi Pelanggan</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                    Semua Pesanan
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Pantau pembayaran, ubah status pesanan, dan input akun manual langsung dari sini.</p>
        </div>

        <!-- Filter Status Buttons -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar w-full md:w-auto shrink-0">
            <a href="{{ route('admin.orders') }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ !request('status') ? 'bg-orange-500 text-white shadow-md shadow-orange-500/20' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-50 border border-gray-200 dark:border-white/5' }}">
                Semua
            </a>
            <a href="{{ route('admin.orders', ['status' => 'completed']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') == 'completed' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-white dark:bg-gray-800 text-emerald-600 hover:bg-emerald-50 border border-emerald-200 dark:border-emerald-900/40' }}">
                ✓ Selesai
            </a>
            <a href="{{ route('admin.orders', ['status' => 'processing']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') == 'processing' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'bg-white dark:bg-gray-800 text-amber-600 hover:bg-amber-50 border border-amber-200 dark:border-amber-900/40' }}">
                ⏳ Diproses
            </a>
            <a href="{{ route('admin.orders', ['status' => 'canceled']) }}" class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all shrink-0 {{ request('status') == 'canceled' ? 'bg-red-500 text-white shadow-md shadow-red-500/20' : 'bg-white dark:bg-gray-800 text-red-600 hover:bg-red-50 border border-red-200 dark:border-red-900/40' }}">
                ✕ Dibatalkan
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar Card -->
    <div class="admin-card p-4 sm:p-5">
        <form action="{{ route('admin.orders') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
            <div class="relative w-full sm:max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari no. invoice, nama pembeli, atau produk..." 
                    class="w-full pl-11 pr-4 py-3 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs sm:text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500"
                >
                <svg class="w-4 h-4 absolute left-4 top-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="flex items-center gap-2.5 w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto px-5 py-3 rounded-2xl gradient-orange text-white text-xs font-black shadow-md shadow-orange-500/20 hover:opacity-90 transition cursor-pointer">
                    Cari Pesanan
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('admin.orders') }}" class="px-4 py-3 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 text-xs font-bold hover:bg-gray-200 transition cursor-pointer">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Pesanan -->
    <div class="admin-card overflow-hidden">
        <!-- Desktop Table -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left admin-table whitespace-nowrap">
                <thead>
                    <tr>
                        <th>Invoice & Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Layanan / Produk</th>
                        <th>Nominal</th>
                        <th>Status</th>
                        <th>Detail Akun</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <!-- Kolom 1: Invoice & Tanggal -->
                            <td>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-black text-gray-900 dark:text-white">#{{ $order->invoice_number }}</span>
                                    <a href="{{ url('/order/invoice/' . $order->invoice_number) }}" target="_blank" class="text-orange-500 hover:text-orange-600 text-xs" title="Cetak / Lihat Invoice">
                                        📄
                                    </a>
                                </div>
                                <p class="text-[10px] text-gray-400 font-medium mt-0.5">{{ $order->created_at ? $order->created_at->format('d M Y, H:i') : '-' }}</p>
                            </td>

                            <!-- Kolom 2: Pelanggan -->
                            <td>
                                <p class="font-bold text-xs text-gray-900 dark:text-white">{{ $order->user ? $order->user->name : 'Tamu' }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-gray-400 font-mono">{{ $order->email }}</span>
                                    @if($order->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->whatsapp) }}" target="_blank" class="text-[10px] text-emerald-500 font-bold hover:underline inline-flex items-center gap-0.5">
                                            <span>WA</span> ↗
                                        </a>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom 3: Layanan -->
                            <td>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="font-bold text-xs text-gray-900 dark:text-white max-w-[220px] truncate" title="{{ $order->product_name }}">
                                        {{ $order->product_name }}
                                    </p>
                                    @if(($order->quantity ?? 1) > 1)
                                        <span class="inline-block text-[10px] font-black px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                            {{ $order->quantity }}x pcs
                                        </span>
                                    @endif
                                </div>
                                @php
                                    $prov = $order->provider;
                                @endphp
                                @if($prov === 'Preflix')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">
                                        ⚡ Preflix API
                                    </span>
                                @elseif($prov === 'Premku')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-800/40">
                                        ⚡ Premku API
                                    </span>
                                @elseif($prov === 'Manual Admin')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                        ✋ Manual Admin
                                    </span>
                                @elseif($prov === 'Otomatis')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                        ⚡ Otomatis Lokal
                                    </span>
                                @elseif($order->product_type === 'api')
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-400 border border-sky-200 dark:border-sky-800/40">
                                        ⚡ Instan API
                                    </span>
                                @else
                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">
                                        📦 Produk Lokal
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom 4: Nominal -->
                            <td>
                                <span class="font-black text-xs text-gray-900 dark:text-white">
                                    Rp {{ number_format($order->total_amount ?? $order->price ?? 0, 0, ',', '.') }}
                                </span>
                                @if(($order->quantity ?? 1) > 1 && $order->price)
                                    <span class="block text-[9px] text-gray-400 font-medium">@ Rp {{ number_format($order->price, 0, ',', '.') }}</span>
                                @endif
                                @if($order->voucher_code)
                                    <span class="block text-[9px] font-bold text-orange-500">Kupon: {{ $order->voucher_code }}</span>
                                @endif
                            </td>

                            <!-- Kolom 5: Status -->
                            <td>
                                @php
                                    $st = strtolower($order->order_status ?? 'pending');
                                @endphp
                                @if(in_array($st, ['completed', 'selesai', 'success']))
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                        ✓ Selesai
                                    </span>
                                @elseif(in_array($st, ['processing', 'lunas']))
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50 animate-pulse">
                                        ⏳ Diproses
                                    </span>
                                @elseif($st === 'canceled')
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800/50">
                                        ✕ Batal
                                    </span>
                                @else
                                    <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-700">
                                        {{ ucfirst($st) }}
                                    </span>
                                @endif
                                @if(!empty($order->notes))
                                    <span class="block text-[9px] text-amber-600 dark:text-amber-400 font-medium max-w-[160px] truncate mt-1" title="{{ $order->notes }}">
                                        ⚠️ {{ $order->notes }}
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom 6: Detail Akun -->
                            <td>
                                @if($order->sn_or_account || $order->account_details)
                                    <button 
                                        type="button"
                                        @click="viewAccountContent = '{{ addslashes($order->sn_or_account ?? $order->account_details) }}'; viewAccountModal = true;"
                                        class="text-[10px] font-bold text-cyan-500 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 px-2.5 py-1 rounded-lg hover:underline cursor-pointer"
                                    >
                                        🔑 Lihat Akun
                                    </button>
                                @else
                                    <span class="text-[10px] text-gray-400 italic">Belum diisi</span>
                                @endif
                            </td>

                            <!-- Kolom 7: Aksi -->
                            @php
                                $isDone = in_array($st, ['completed', 'selesai', 'success']);
                                $isManualProduct = ($order->product_type === 'local' && (($order->product->process_type ?? 'auto') === 'manual' || $prov === 'Manual Admin'));
                            @endphp
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($isDone)
                                        @if($isManualProduct)
                                            <button 
                                                type="button"
                                                @click="selectedOrder = {{ json_encode(['id' => $order->id, 'invoice' => $order->invoice_number, 'customer' => $order->user ? $order->user->name : ($order->buyer_name ?: 'Tamu'), 'product' => $order->product_name, 'sn' => $order->sn_or_account ?? '']) }}; statusAction = 'completed'; accountCredentials = '{{ addslashes($order->sn_or_account ?? '') }}'; openModal = true;"
                                                class="px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-[10px] transition cursor-pointer"
                                                title="Edit Detail Kredensial Akun Manual"
                                            >
                                                ✏️ Edit Akun
                                            </button>
                                        @else
                                            <span class="text-xs font-bold text-gray-400 dark:text-gray-500 mr-2">-</span>
                                        @endif
                                    @else
                                        @if($order->product_type === 'api' && empty($order->sn_or_account))
                                            <form action="{{ route('admin.orders.retry_api', $order->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button 
                                                    type="submit"
                                                    class="px-2 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-[11px] transition cursor-pointer shadow-sm flex items-center gap-1"
                                                    title="Kirim / Cek Ulang ke Provider API (Premku/Preflix)"
                                                >
                                                    ⚡ Kirim API
                                                </button>
                                            </form>
                                        @endif

                                        <button 
                                            type="button"
                                            @click="selectedOrder = {{ json_encode(['id' => $order->id, 'invoice' => $order->invoice_number, 'customer' => $order->user ? $order->user->name : ($order->buyer_name ?: 'Tamu'), 'product' => $order->product_name, 'sn' => $order->sn_or_account ?? '']) }}; statusAction = 'completed'; accountCredentials = '{{ addslashes($order->sn_or_account ?? '') }}'; openModal = true;"
                                            class="px-2.5 py-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-[11px] transition cursor-pointer shadow-sm"
                                            title="Selesaikan & Kirim Akun"
                                        >
                                            Proses / Selesai
                                        </button>
                                        
                                        <form action="{{ route('admin.orders.update_status', $order->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="canceled">
                                            <button type="submit" class="p-1 rounded-lg text-gray-400 hover:text-red-500 transition cursor-pointer" title="Batalkan Pesanan">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                Tidak ada data pesanan yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards List -->
        <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
            @forelse($orders as $order)
                @php
                    $prov = $order->provider;
                    $st = strtolower($order->order_status ?? 'pending');
                    $isDone = in_array($st, ['completed', 'selesai', 'success']);
                    $isManualProduct = ($order->product_type === 'local' && (($order->product->process_type ?? 'auto') === 'manual' || $prov === 'Manual Admin'));
                @endphp
                <div class="p-4 space-y-3">
                    <!-- Top Row: Invoice + Date + Status -->
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="font-mono text-xs font-black text-gray-900 dark:text-white truncate">#{{ $order->invoice_number }}</span>
                            <a href="{{ url('/order/invoice/' . $order->invoice_number) }}" target="_blank" class="text-orange-500 hover:text-orange-600 text-xs shrink-0" title="Cetak / Lihat Invoice">
                                📄
                            </a>
                        </div>
                        <div class="shrink-0">
                            @if(in_array($st, ['completed', 'selesai', 'success']))
                                <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                    ✓ Selesai
                                </span>
                            @elseif(in_array($st, ['processing', 'lunas']))
                                <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-300 dark:border-amber-800/50 animate-pulse">
                                    ⏳ Diproses
                                </span>
                            @elseif($st === 'canceled')
                                <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300 dark:border-rose-800/50">
                                    ✕ Batal
                                </span>
                            @else
                                <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-700">
                                    {{ ucfirst($st) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Product & Provider Badge -->
                    <div>
                        <p class="font-black text-sm text-gray-900 dark:text-white leading-snug">
                            {{ $order->product_name }}
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                            @if($prov === 'Preflix')
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40">
                                    ⚡ Preflix API
                                </span>
                            @elseif($prov === 'Premku')
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 border border-purple-200 dark:border-purple-800/40">
                                    ⚡ Premku API
                                </span>
                            @elseif($prov === 'Manual Admin')
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40">
                                    ✋ Manual Admin
                                </span>
                            @elseif($prov === 'Otomatis')
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">
                                    ⚡ Otomatis Lokal
                                </span>
                            @elseif($order->product_type === 'api')
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-400 border border-sky-200 dark:border-sky-800/40">
                                    ⚡ Instan API
                                </span>
                            @else
                                <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">
                                    📦 Produk Lokal
                                </span>
                            @endif

                            <span class="text-[11px] font-black text-orange-500 ml-auto">
                                Rp {{ number_format($order->total_amount ?? $order->price ?? 0, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Customer & WA Info -->
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5 text-xs">
                        <div class="min-w-0 pr-2">
                            <p class="font-bold text-gray-900 dark:text-white truncate">
                                👤 {{ $order->user ? $order->user->name : ($order->buyer_name ?: 'Tamu') }}
                            </p>
                            <p class="text-[10px] text-gray-400 font-mono truncate">{{ $order->email }}</p>
                        </div>
                        <div class="shrink-0 flex items-center gap-1.5">
                            @if($order->whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->whatsapp) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-[10px] inline-flex items-center gap-1 shadow-sm">
                                    <span>Chat WA</span> ↗
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Detail Akun & Notes -->
                    @if($order->sn_or_account || $order->account_details || !empty($order->notes))
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            @if($order->sn_or_account || $order->account_details)
                                <button 
                                    type="button"
                                    @click="viewAccountContent = '{{ addslashes($order->sn_or_account ?? $order->account_details) }}'; viewAccountModal = true;"
                                    class="text-[11px] font-bold text-cyan-500 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-800/40 px-3 py-1.5 rounded-xl hover:underline cursor-pointer flex items-center gap-1"
                                >
                                    🔑 <span>Lihat Akun Pembeli</span>
                                </button>
                            @endif
                            @if(!empty($order->notes))
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium truncate max-w-full">
                                    ⚠️ {{ $order->notes }}
                                </span>
                            @endif
                        </div>
                    @endif

                    <!-- Action Buttons on Mobile -->
                    <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-white/5">
                        <span class="text-[10px] text-gray-400">
                            {{ $order->created_at ? $order->created_at->format('d M Y, H:i') : '-' }}
                        </span>

                        <div class="flex items-center gap-1.5">
                            @if($isDone)
                                @if($isManualProduct)
                                    <button 
                                        type="button"
                                        @click="selectedOrder = {{ json_encode(['id' => $order->id, 'invoice' => $order->invoice_number, 'customer' => $order->user ? $order->user->name : ($order->buyer_name ?: 'Tamu'), 'product' => $order->product_name, 'sn' => $order->sn_or_account ?? '']) }}; statusAction = 'completed'; accountCredentials = '{{ addslashes($order->sn_or_account ?? '') }}'; openModal = true;"
                                        class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-xs transition cursor-pointer"
                                    >
                                        ✏️ Edit Akun
                                    </button>
                                @endif
                            @else
                                @if($order->product_type === 'api' && empty($order->sn_or_account))
                                    <form action="{{ route('admin.orders.retry_api', $order->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button 
                                            type="submit"
                                            class="px-2.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition cursor-pointer shadow-sm flex items-center gap-1"
                                        >
                                            ⚡ Kirim API
                                        </button>
                                    </form>
                                @endif

                                <button 
                                    type="button"
                                    @click="selectedOrder = {{ json_encode(['id' => $order->id, 'invoice' => $order->invoice_number, 'customer' => $order->user ? $order->user->name : ($order->buyer_name ?: 'Tamu'), 'product' => $order->product_name, 'sn' => $order->sn_or_account ?? '']) }}; statusAction = 'completed'; accountCredentials = '{{ addslashes($order->sn_or_account ?? '') }}'; openModal = true;"
                                    class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs transition cursor-pointer shadow-sm"
                                >
                                    Proses / Selesai
                                </button>
                                
                                <form action="{{ route('admin.orders.update_status', $order->id) }}" method="POST" onsubmit="return confirm('Yakin ingin membatalkan pesanan ini?');" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="canceled">
                                    <button type="submit" class="p-1.5 rounded-xl text-gray-400 hover:text-red-500 bg-gray-100 dark:bg-gray-800 transition cursor-pointer" title="Batalkan Pesanan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-400 font-semibold text-xs">
                    Tidak ada data pesanan yang sesuai pencarian.
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-white/5">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL PROSES STATUS & INPUT AKUN -->
    <div x-show="openModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div @click.away="openModal = false" class="bg-white dark:bg-[#131b2e] rounded-3xl p-6 sm:p-8 max-w-lg w-full border border-gray-100 dark:border-white/10 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                <div>
                    <h3 class="text-lg font-black text-gray-900 dark:text-white">Proses Pesanan</h3>
                    <p class="text-xs text-gray-400" x-text="'Invoice: #' + (selectedOrder ? selectedOrder.invoice : '')"></p>
                </div>
                <button type="button" @click="openModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
            </div>

            <form :action="'{{ url('/admin/orders') }}/' + (selectedOrder ? selectedOrder.id : '') + '/status'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-black text-gray-500 uppercase tracking-wider block mb-1.5">Ubah Status</label>
                    <select name="status" x-model="statusAction" class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none">
                        <option value="completed">✓ Selesai (Completed)</option>
                        <option value="processing">⏳ Sedang Diproses (Processing)</option>
                        <option value="canceled">✕ Batalkan (Canceled)</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-black text-gray-500 uppercase tracking-wider block mb-1.5">
                        Detail Akun / Serial Number / Lisensi
                    </label>
                    <textarea 
                        name="sn_or_account" 
                        x-model="accountCredentials" 
                        rows="3" 
                        placeholder="Contoh: Email: user@gmail.com | Password: password123" 
                        class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-mono dark:text-cyan-400 outline-none"
                    ></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">
                        *Jika diisi dan status disetel 'Selesai', detail ini otomatis terkirim ke WhatsApp pembeli (jika WA pembeli sudah terverifikasi).
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-white/10">
                    <button type="button" @click="openModal = false" class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold cursor-pointer">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl gradient-orange text-white text-xs font-black shadow-md cursor-pointer">Simpan & Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL LIHAT DETAIL AKUN -->
    <div x-show="viewAccountModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div @click.away="viewAccountModal = false" class="bg-white dark:bg-[#131b2e] rounded-3xl p-6 max-w-md w-full border border-gray-100 dark:border-white/10 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-white/10">
                <h3 class="text-base font-black text-gray-900 dark:text-white">🔑 Kredensial Akun Pembeli</h3>
                <button type="button" @click="viewAccountModal = false" class="text-gray-400 hover:text-gray-200">✕</button>
            </div>
            <div class="bg-black/40 p-4 rounded-2xl border border-white/5 font-mono text-xs text-cyan-400 select-all whitespace-pre-wrap break-all" x-text="viewAccountContent"></div>
            <button type="button" @click="viewAccountModal = false" class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold cursor-pointer">Tutup</button>
        </div>
    </div>

</div>
@endsection