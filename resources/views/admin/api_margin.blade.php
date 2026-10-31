@extends('layouts.admin')

@section('title', 'Katalog Premium & Markup API')

@section('content')
<datalist id="kategoriList">
    <option value="Aplikasi Premium">
    <option value="Akun Streaming">
    <option value="E-Book">
    <option value="Novel">
    <option value="Rumus Excel">
    <option value="Template">
    <option value="Desain & Kreatif">
</datalist>

<div class="max-w-7xl mx-auto space-y-6">

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

    <!-- Header Banner dengan Alat Untung Massal -->
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 bg-gradient-to-r from-gray-900 via-slate-800 to-gray-900 text-white shadow-xl border border-white/10 flex flex-col xl:flex-row items-center justify-between gap-6">
        <div class="text-center xl:text-left w-full xl:w-auto">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs font-black uppercase tracking-wider mb-2">
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                <span>Sinkronisasi Otomatis API</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Katalog API & Penetapan Harga</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1 max-w-lg font-medium">
                Tentukan harga jual (markup margin), harga khusus reseller VIP, serta kategori produk dari Server Pusat.
            </p>
        </div>
        
        <!-- Box Untung Massal -->
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full xl:w-auto bg-black/40 p-2.5 rounded-2xl border border-white/10 backdrop-blur-md">
            <div class="px-4 py-1.5 w-full sm:w-auto">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Set Untung Massal</span>
                <div class="flex items-center gap-1.5">
                    <span class="text-orange-500 font-black text-sm">Rp</span>
                    <input type="number" id="mass_margin_value" placeholder="Cth: 5000" class="w-full sm:w-32 bg-transparent border-b border-gray-600 text-white focus:outline-none focus:border-orange-500 font-black text-base placeholder-gray-600">
                </div>
            </div>
            <button type="button" onclick="applyMassMargin()" class="w-full sm:w-auto gradient-orange text-white font-black py-3 px-6 rounded-xl transition-all shadow-md hover:opacity-90 flex items-center justify-center gap-2 cursor-pointer text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span>Terapkan Massal</span>
            </button>
        </div>
    </div>

    <!-- PANEL PANGGIL & SINKRONKAN PRODUK API LANGSUNG DARI SERVER PUSAT KE DATABASE -->
    <div class="admin-card p-5 sm:p-6 bg-white dark:bg-[#111827] border border-blue-200 dark:border-blue-500/20 rounded-3xl shadow-sm">
        <form action="{{ route('admin.products.api.sync_provider') }}" method="POST" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            @csrf
            <div class="space-y-1 max-w-xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-wider border border-blue-200 dark:border-blue-800/40">
                    <span>Import & Panggil Otomatis dari Server Pusat</span>
                </div>
                <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                    Panggil Produk API ke Database ({{ count($dbMarkups) }} Tersimpan di Database)
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Setelah database dikosongkan ke 0, gunakan tombol ini untuk langsung memanggil dan mengaktifkan seluruh katalog produk live dari <strong>Premku</strong> & <strong>Preflix</strong> ke etalase toko Anda dalam 1 klik.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 w-full lg:w-auto shrink-0 items-end">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Provider</label>
                    <select name="provider" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all">Semua (Premku & Preflix)</option>
                        <option value="premku">Hanya Premku</option>
                        <option value="preflix">Hanya Preflix</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Untung Member (Rp)</label>
                    <input type="number" name="default_margin" value="3000" min="0" class="w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="3000">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400 mb-1">Untung Reseller (Rp)</label>
                    <input type="number" name="reseller_margin" value="1500" min="0" class="w-full px-3 py-2.5 rounded-xl bg-amber-50/40 dark:bg-gray-900 border border-amber-200 dark:border-amber-800/40 text-xs font-black text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-amber-500" placeholder="1500">
                </div>
                <div>
                    <input type="hidden" name="auto_activate" value="1">
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-black text-xs shadow-lg shadow-blue-600/25 transition flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Panggil & Simpan API</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- MASTER SWITCH KONTROL SAKELAR PROVIDER API -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        
        <!-- KARTU PREFLIX -->
        <div class="admin-card p-5 sm:p-6 bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl relative overflow-hidden shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white flex items-center justify-center font-black shadow-md shadow-blue-500/20 text-sm">
                        PF
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Preflix Server</h3>
                        <p class="text-[11px] text-gray-400 font-medium">Provider Katalog Otomatis</p>
                    </div>
                </div>

                @if($preflixDisabled)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800/40">
                        🔴 SEMUA DIMATIKAN
                    </span>
                @elseif($preflixActiveCount === 0)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40">
                        ⚪ BELUM AKTIF (0 / {{ $preflixTotalCount }})
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                        🟢 AKTIF ({{ $preflixActiveCount }} / {{ $preflixTotalCount }})
                    </span>
                @endif
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                Kelola status seluruh produk dari Preflix secara serentak. Klik <strong>Aktifkan Semua</strong> untuk langsung memanggil & mengaktifkan seluruh katalog Preflix ke etalase toko.
            </p>

            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-white/5">
                <form action="{{ route('admin.products.api.toggle_provider') }}" method="POST" onsubmit="if(!confirm('Yakin ingin MEMATIKAN SEMUA produk Preflix dari etalase toko?')) return false; this.querySelector('button').disabled=true; this.querySelector('span').innerText='Mematikan...';" class="flex-1">
                    @csrf
                    <input type="hidden" name="provider" value="preflix">
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 font-black text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        <span>Matikan Semua Preflix</span>
                    </button>
                </form>

                <form action="{{ route('admin.products.api.toggle_provider') }}" method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('span').innerText='Mengaktifkan...';" class="flex-1">
                    @csrf
                    <input type="hidden" name="provider" value="preflix">
                    <input type="hidden" name="action" value="enable">
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 font-black text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Aktifkan Semua</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- KARTU PREMKU -->
        <div class="admin-card p-5 sm:p-6 bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl relative overflow-hidden shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-purple-500 to-indigo-600 text-white flex items-center justify-center font-black shadow-md shadow-purple-500/20 text-sm">
                        PK
                    </div>
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Premku Server</h3>
                        <p class="text-[11px] text-gray-400 font-medium">Provider Katalog Otomatis</p>
                    </div>
                </div>

                @if($premkuDisabled)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800/40">
                        🔴 SEMUA DIMATIKAN
                    </span>
                @elseif($premkuActiveCount === 0)
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40">
                        ⚪ BELUM AKTIF (0 / {{ $premkuTotalCount }})
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                        🟢 AKTIF ({{ $premkuActiveCount }} / {{ $premkuTotalCount }})
                    </span>
                @endif
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 leading-relaxed">
                Kelola status seluruh produk dari Premku secara serentak. Klik <strong>Aktifkan Semua</strong> untuk langsung memanggil & mengaktifkan seluruh katalog Premku ke etalase toko.
            </p>

            <div class="flex items-center gap-2 pt-2 border-t border-gray-100 dark:border-white/5">
                <form action="{{ route('admin.products.api.toggle_provider') }}" method="POST" onsubmit="if(!confirm('Yakin ingin MEMATIKAN SEMUA produk Premku dari etalase toko?')) return false; this.querySelector('button').disabled=true; this.querySelector('span').innerText='Mematikan...';" class="flex-1">
                    @csrf
                    <input type="hidden" name="provider" value="premku">
                    <input type="hidden" name="action" value="disable">
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 font-black text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                        <span>Matikan Semua Premku</span>
                    </button>
                </form>

                <form action="{{ route('admin.products.api.toggle_provider') }}" method="POST" onsubmit="this.querySelector('button').disabled=true; this.querySelector('span').innerText='Mengaktifkan...';" class="flex-1">
                    @csrf
                    <input type="hidden" name="provider" value="premku">
                    <input type="hidden" name="action" value="enable">
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 font-black text-xs transition cursor-pointer flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        <span>Aktifkan Semua</span>
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Form & Tabel Katalog API -->
    <form action="{{ route('admin.products.api.bulk_markup') }}" method="POST" id="bulkMarkupForm" onsubmit="return prepareBulkMarkupSubmit(this);" class="pb-24 space-y-4">
        @csrf
        <input type="hidden" name="products_json" id="products_json" value="">

        <div class="admin-card overflow-hidden">
            <!-- Search Filter Bar & Provider Tabs -->
            <div class="p-5 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-black/20 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="relative w-full md:max-w-md">
                    <input 
                        type="text" 
                        id="tableSearch" 
                        onkeyup="searchTable()" 
                        placeholder="Filter cepat nama produk di layar ini..." 
                        class="w-full pl-11 pr-4 py-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs sm:text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 shadow-sm"
                    >
                    <svg class="w-4 h-4 absolute left-4 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <!-- Provider Tabs -->
                <div class="flex flex-wrap items-center gap-2 self-start md:self-auto">
                    <button type="button" onclick="filterProvider('all')" id="btnFilter_all" class="prov-filter-btn px-3 py-1.5 rounded-xl text-xs font-black bg-orange-500 text-white shadow-sm cursor-pointer transition">
                        Semua ({{ count($apiProducts) }})
                    </button>
                    <button type="button" onclick="filterProvider('preflix')" id="btnFilter_preflix" class="prov-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer transition">
                        Preflix ({{ $preflixTotalCount }})
                    </button>
                    <button type="button" onclick="filterProvider('premku')" id="btnFilter_premku" class="prov-filter-btn px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer transition">
                        Premku ({{ $premkuTotalCount }})
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap" id="apiTable">
                    <thead>
                        <tr>
                            <th>Logo (256x256 px) & Produk</th>
                            <th>Provider & Modal</th>
                            <th>Harga Jual (Markup)</th>
                            <th class="text-amber-500">👑 Harga Reseller</th>
                            <th class="text-orange-500">Kategori Tipe</th>
                            <th class="text-center">Label Promo</th>
                            <th class="text-center">Aktifkan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apiProducts as $item)
                            @php
                                $isSaved = isset($dbMarkups[$item['api_id']]);
                                $isActive = $isSaved && $dbMarkups[$item['api_id']]->status == 'available';
                                $isPromo = ($isSaved && isset($dbMarkups[$item['api_id']]->is_promo)) ? $dbMarkups[$item['api_id']]->is_promo : 1;
                                $savedImage = ($isSaved && !empty($dbMarkups[$item['api_id']]->image)) ? $dbMarkups[$item['api_id']]->image : $item['image'];
                                $savedDesc = ($isSaved && !empty($dbMarkups[$item['api_id']]->description)) ? $dbMarkups[$item['api_id']]->description : $item['description'];
                                $savedCat = $isSaved ? $dbMarkups[$item['api_id']]->category : '';
                                $savedName = ($isSaved && !empty($dbMarkups[$item['api_id']]->name)) ? $dbMarkups[$item['api_id']]->name : $item['name'];
                                
                                $provColor = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700'; 
                                if (strtolower($item['provider']) == 'premku') {
                                    $provColor = 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-400 border-purple-200 dark:border-purple-800/40';
                                } elseif (strtolower($item['provider']) == 'preflix') {
                                    $provColor = 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800/40';
                                }
                            @endphp
                            <tr class="markup-row hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors group" data-api-id="{{ $item['api_id'] }}" data-original-price="{{ $item['original_price'] }}" data-provider="{{ strtolower($item['provider']) }}">
                                
                                <!-- Kolom 1: Logo & Nama -->
                                <td>
                                    <div class="flex items-start gap-3">
                                        <div class="flex flex-col items-center gap-0.5 shrink-0 mt-0.5" title="Upload Logo Produk (Ukuran Ideal: 256 x 256 px atau 500 x 500 px)">
                                            <div class="relative group/upload cursor-pointer w-11 h-11 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-white/10 flex items-center justify-center overflow-hidden hover:border-orange-500">
                                                <img id="preview_{{ $item['api_id'] }}" src="{{ $savedImage }}" class="{{ $savedImage ? '' : 'hidden' }} w-full h-full object-cover">
                                                <div id="upload_icon_{{ $item['api_id'] }}" class="{{ $savedImage ? 'hidden' : 'flex' }} flex-col items-center justify-center text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                </div>
                                                <input type="file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/png,image/jpeg,image/webp,image/gif,image/svg+xml" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="compressAndConvertToBase64(this, '{{ $item['api_id'] }}')">
                                            </div>
                                            <span class="text-[8px] font-mono font-bold text-gray-400">256x256</span>
                                        </div>
                                        
                                        <input type="hidden" id="img_{{ $item['api_id'] }}" class="row-image" name="products[{{ $item['api_id'] }}][image]" value="{{ $savedImage }}">
                                        <input type="hidden" class="row-original-price" name="products[{{ $item['api_id'] }}][original_price]" value="{{ $item['original_price'] }}">
                                        <input type="hidden" class="row-stock" name="products[{{ $item['api_id'] }}][stock]" value="{{ $item['stock'] ?? 0 }}">
                                        
                                        <div class="flex flex-col min-w-0 w-52 sm:w-64">
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <span class="text-[9px] font-black uppercase text-orange-500 tracking-wider">Nama Produk:</span>
                                                @if($savedName !== $item['name'])
                                                    <span class="text-[9px] px-1.5 py-0.2 rounded bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 font-bold" title="Nama produk ini telah di-kustom">Kustom</span>
                                                @endif
                                            </div>
                                            <input type="text" 
                                                   name="products[{{ $item['api_id'] }}][name]" 
                                                   value="{{ $savedName }}" 
                                                   class="row-name w-full px-2.5 py-1.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-orange-500 transition" 
                                                   placeholder="Kustom nama produk..."
                                                   title="{{ $savedName }}">
                                            @if($savedName !== $item['name'])
                                                <span class="text-[9px] text-gray-400 truncate mt-0.5" title="Nama Asli Provider: {{ $item['name'] }}">
                                                    Asli: {{ $item['name'] }}
                                                </span>
                                            @endif
                                            <input type="hidden" id="desc_input_{{ $item['api_id'] }}" class="row-description" name="products[{{ $item['api_id'] }}][description]" value="{{ $savedDesc }}">
                                            <button type="button" onclick="openDescModal('{{ $item['api_id'] }}', '{{ htmlspecialchars($savedName, ENT_QUOTES) }}')" class="text-[10px] text-orange-500 hover:underline mt-1 font-bold w-fit flex items-center gap-1 cursor-pointer">
                                                <span>✏️ Edit Deskripsi</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Kolom 2: Provider & Modal -->
                                <td>
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="{{ $provColor }} text-[9px] font-black px-2 py-0.5 rounded-full border">
                                            {{ $item['provider'] }}
                                        </span>
                                        <span class="font-mono text-xs font-bold text-gray-400">
                                            Rp {{ number_format($item['original_price'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Kolom 3: Harga Jual (Markup) -->
                                <td>
                                    <div class="relative w-28 sm:w-32">
                                        <span class="absolute left-2.5 top-2.5 text-gray-400 font-bold text-xs">Rp</span>
                                        <input type="number" name="products[{{ $item['api_id'] }}][markup_price]" value="{{ $isSaved ? $dbMarkups[$item['api_id']]->price : '' }}" class="markup-input row-markup-price w-full pl-8 pr-2.5 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="0">
                                    </div>
                                </td>

                                <!-- Kolom 4: Harga Reseller VIP -->
                                <td>
                                    <div class="relative w-28 sm:w-32">
                                        <span class="absolute left-2.5 top-2.5 text-amber-500 font-bold text-xs">Rp</span>
                                        <input type="number" name="products[{{ $item['api_id'] }}][reseller_price]" value="{{ $isSaved && isset($dbMarkups[$item['api_id']]->reseller_price) ? $dbMarkups[$item['api_id']]->reseller_price : '' }}" class="row-reseller-price w-full pl-8 pr-2.5 py-2 bg-amber-50/30 dark:bg-gray-900 border border-amber-200 dark:border-amber-800/40 rounded-xl text-xs font-black text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-amber-500" placeholder="Opsional">
                                    </div>
                                </td>

                                <!-- Kolom 5: Kategori Tipe -->
                                <td>
                                    <input list="kategoriList" type="text" name="products[{{ $item['api_id'] }}][category]" value="{{ $savedCat }}" class="row-category w-32 sm:w-36 px-3 py-2 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold text-gray-900 dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="Pilih / Ketik...">
                                </td>
                                
                                <!-- Kolom 6: Switch Promo Merah -->
                                <td class="text-center">
                                    <label class="relative inline-flex items-center cursor-pointer" title="Aktifkan/Matikan Label Promo">
                                        <input type="checkbox" name="products[{{ $item['api_id'] }}][is_promo]" value="1" class="row-is-promo sr-only peer" {{ $isPromo ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-rose-500 shadow-inner"></div>
                                    </label>
                                </td>
                                
                                <!-- Kolom 7: Switch Status Aktif -->
                                <td class="text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="products[{{ $item['api_id'] }}][is_active]" value="1" class="row-is-active sr-only peer status-checkbox" {{ $isActive ? 'checked' : '' }}>
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500 shadow-inner"></div>
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 font-semibold text-xs">Tidak ada data produk API dari server pusat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDIT DESKRIPSI -->
        <div id="descModal" class="fixed inset-0 z-[100] bg-black/60 hidden items-center justify-center p-4 backdrop-blur-sm transition-opacity">
            <div class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg p-6 sm:p-8 border border-gray-100 dark:border-white/10 shadow-2xl space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                    <h3 class="font-black text-base dark:text-white text-gray-900" id="descModalTitle">Edit Deskripsi Produk</h3>
                    <button type="button" onclick="closeDescModal()" class="text-gray-400 hover:text-gray-200">✕</button>
                </div>
                <p class="text-xs text-gray-400">Kosongkan jika ingin menggunakan deskripsi bawaan Server API.</p>
                <textarea id="descModalTextarea" rows="6" class="w-full p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs font-medium dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="Ketik deskripsi manual / catatan khusus untuk produk ini..."></textarea>
                <input type="hidden" id="descModalApiId">
                <div class="flex justify-end gap-2.5 pt-2 border-t border-gray-100 dark:border-white/10">
                    <button type="button" onclick="closeDescModal()" class="px-4 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl font-bold text-xs cursor-pointer">Batal</button>
                    <button type="button" onclick="saveDescModal()" class="px-5 py-2.5 gradient-orange text-white rounded-xl font-black text-xs shadow-md cursor-pointer">Terapkan Sementara</button>
                </div>
            </div>
        </div>

        <!-- Floating Bottom Action Bar -->
        <div class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 w-[90%] sm:w-auto">
            <div class="bg-gray-900/90 dark:bg-slate-900/90 backdrop-blur-xl border border-white/10 p-2.5 pl-6 rounded-full shadow-2xl flex items-center justify-between gap-6 sm:gap-10">
                <span class="text-xs font-black text-white whitespace-nowrap">Simpan semua penyesuaian harga?</span>
                <button type="submit" id="btnSubmitBulkMarkup" class="gradient-orange text-white font-black py-3 px-8 rounded-full shadow-lg shadow-orange-500/30 transition-all hover:scale-105 flex items-center gap-2 cursor-pointer text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span id="btnSubmitBulkMarkupText">Simpan Semua</span>
                </button>
            </div>
        </div>
    </form>

</div>

<script>
    function prepareBulkMarkupSubmit(form) {
        try {
            const payload = {};
            const rows = form.querySelectorAll('.markup-row');
            rows.forEach(row => {
                const apiId = row.getAttribute('data-api-id');
                if (!apiId) return;
                const imgEl = row.querySelector('.row-image');
                const origEl = row.querySelector('.row-original-price');
                const stockEl = row.querySelector('.row-stock');
                const nameEl = row.querySelector('.row-name');
                const descEl = row.querySelector('.row-description');
                const markupEl = row.querySelector('.row-markup-price');
                const resellerEl = row.querySelector('.row-reseller-price');
                const catEl = row.querySelector('.row-category');
                const promoEl = row.querySelector('.row-is-promo');
                const activeEl = row.querySelector('.row-is-active');

                payload[apiId] = {
                    image: imgEl ? imgEl.value : '',
                    original_price: origEl ? origEl.value : 0,
                    stock: stockEl ? stockEl.value : 0,
                    name: nameEl ? nameEl.value : '',
                    description: descEl ? descEl.value : '',
                    markup_price: markupEl ? markupEl.value : '',
                    reseller_price: resellerEl ? resellerEl.value : '',
                    category: catEl ? catEl.value : '',
                    is_promo: (promoEl && promoEl.checked) ? 1 : 0,
                    is_active: (activeEl && activeEl.checked) ? 1 : 0,
                };

                // Hapus atribut name dari input per baris agar PHP max_input_vars tidak terlampaui
                row.querySelectorAll('input[name]').forEach(inp => inp.removeAttribute('name'));
            });

            const jsonInput = document.getElementById('products_json');
            if (jsonInput) {
                jsonInput.value = JSON.stringify(payload);
            }

            const btn = document.getElementById('btnSubmitBulkMarkup');
            const btnText = document.getElementById('btnSubmitBulkMarkupText');
            if (btn) btn.disabled = true;
            if (btnText) btnText.innerText = 'Menyimpan...';
        } catch (err) {
            console.error('Gagal serialisasi form markup:', err);
        }
        return true;
    }

    function openDescModal(apiId, name) {
        document.getElementById('descModalTitle').innerText = 'Deskripsi: ' + name;
        document.getElementById('descModalApiId').value = apiId;
        document.getElementById('descModalTextarea').value = document.getElementById('desc_input_' + apiId).value;
        document.getElementById('descModal').classList.remove('hidden'); document.getElementById('descModal').classList.add('flex');
    }
    function closeDescModal() { document.getElementById('descModal').classList.add('hidden'); document.getElementById('descModal').classList.remove('flex'); }
    function saveDescModal() {
        const apiId = document.getElementById('descModalApiId').value;
        document.getElementById('desc_input_' + apiId).value = document.getElementById('descModalTextarea').value;
        closeDescModal();
    }
    function compressAndConvertToBase64(input, apiId) {
        if (window.validateAdminImageFile && !window.validateAdminImageFile(input)) return;
        const file = input.files[0]; if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas'); const MAX_SIZE = 256; 
                let width = img.width; let height = img.height;
                if (width > height && width > MAX_SIZE) { height *= MAX_SIZE / width; width = MAX_SIZE; } else if (height > MAX_SIZE) { width *= MAX_SIZE / height; height = MAX_SIZE; }
                canvas.width = width; canvas.height = height; const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, width, height);
                const base64String = canvas.toDataURL('image/webp', 0.8);
                document.getElementById('img_' + apiId).value = base64String; document.getElementById('preview_' + apiId).src = base64String; document.getElementById('preview_' + apiId).classList.remove('hidden'); document.getElementById('upload_icon_' + apiId).classList.add('hidden');
            }; img.src = e.target.result;
        }; reader.readAsDataURL(file);
    }
    function applyMassMargin() {
        const margin = parseInt(document.getElementById('mass_margin_value').value);
        if(!margin || margin <= 0) return (window.showAdminToast ? window.showAdminToast('Masukkan nominal keuntungan (Rp) yang valid!', 'error') : alert('Masukkan nominal keuntungan (Rp) yang valid!'));
        const rows = document.querySelectorAll('.markup-row'); let count = 0;
        rows.forEach(row => {
            if(row.style.display !== 'none') {
                const original = parseInt(row.getAttribute('data-original-price'));
                const input = row.querySelector('.markup-input');
                const checkbox = row.querySelector('.status-checkbox');
                input.value = original + margin; checkbox.checked = true; count++;
            }
        });
        if (window.showAdminToast) {
            window.showAdminToast(`Berhasil mengatur keuntungan +Rp ${margin.toLocaleString('id-ID')} ke ${count} produk! Pastikan klik Simpan Semua.`);
        }
    }
    let currentProviderFilter = 'all';

    function filterProvider(prov) {
        currentProviderFilter = prov;
        document.querySelectorAll('.prov-filter-btn').forEach(b => {
            b.classList.remove('bg-orange-500', 'text-white', 'font-black');
            b.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-300', 'font-bold');
        });
        const activeBtn = document.getElementById('btnFilter_' + prov);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-600', 'dark:text-gray-300', 'font-bold');
            activeBtn.classList.add('bg-orange-500', 'text-white', 'font-black');
        }
        searchTable();
    }

    function searchTable() {
        const input = document.getElementById('tableSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.markup-row');
        rows.forEach(row => {
            const nameInput = row.querySelector('.row-name') || row.querySelector('input[name*="[name]"]');
            const name = (nameInput ? nameInput.value : '').toLowerCase();
            const prov = (row.getAttribute('data-provider') || '').toLowerCase();
            const matchSearch = name.includes(input);
            const matchProv = (currentProviderFilter === 'all' || prov === currentProviderFilter);
            row.style.display = (matchSearch && matchProv) ? '' : 'none';
        });
    }
</script>
@endsection