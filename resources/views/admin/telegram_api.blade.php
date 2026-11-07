@extends('layouts.admin')

@section('title', 'API BOT Telegram - Store API')

@section('content')
<div class="max-w-7xl mx-auto space-y-6 pb-20">

    <!-- Header & Master Switch Status -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-500 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight">API BOT Telegram (QuantumVault Store API)</h2>
                        @if($isEnabled)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                AKTIF BISA DI-ORDER USER ({{ $tgActiveCount }}/{{ $tgTotalCount }} Produk)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/30">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                SEMENTARA MATI / NONAKTIF UNTUK USER (MODE PERSIAPAN)
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        Otomatisasi pembelian produk digital langsung dari saldo wallet bot Telegram (<code class="text-sky-500 font-bold">{{ $baseUrl }}</code>). Mendukung multi-varian, pembacaan <code class="text-sky-500 font-bold">deliverFields[]</code> dinamis, serta pemulihan anti-duplikat <code class="text-sky-500 font-bold">GET /orders/:orderId</code>.
                    </p>
                </div>
            </div>

            <!-- Master Switch Form (Nyalakan / Matikan untuk User) -->
            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                @if($isEnabled)
                    <form action="{{ route('admin.telegram.toggle') }}" method="POST" onsubmit="return confirm('Matikan API BOT Telegram untuk sementara? Semua produk dari Telegram akan disembunyikan dari etalase user.');">
                        @csrf
                        <input type="hidden" name="action" value="disable">
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-rose-600 hover:bg-rose-500 text-white shadow-sm transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            Matikan Sementara (Nonaktifkan Order User)
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.telegram.toggle') }}" method="POST" onsubmit="return confirm('Nyalakan API BOT Telegram sekarang? Produk akan tampil di etalase dan siap dibeli oleh user secara otomatis.');">
                        @csrf
                        <input type="hidden" name="action" value="enable">
                        <button type="submit" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                            Nyalakan untuk Bisa Di-Order User
                        </button>
                    </form>
                @endif

                <a href="{{ route('admin.products.api') }}" class="px-4 py-2.5 rounded-xl text-xs font-extrabold bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 text-gray-700 dark:text-gray-200 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                    Ke Menu Markup API
                </a>
            </div>
        </div>
    </div>

    <!-- Ringkasan Status Akun & Saldo Wallet Live (/me & /balance) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Status Koneksi API</p>
            @if(!empty($accountInfo))
                <div class="mt-2 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-base font-black text-emerald-500">TERHUBUNG</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Akun: <span class="font-bold text-gray-800 dark:text-gray-200">{{ '@' . ltrim((string) ($accountInfo['username'] ?? 'User'), '@') }}</span>
                </p>
            @elseif($apiKey !== '')
                <div class="mt-2 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span class="text-sm font-black text-rose-500">GAGAL TERHUBUNG</span>
                </div>
                <p class="text-[11px] text-rose-400 mt-1 truncate" title="{{ $apiError }}">{{ $apiError ?: 'Periksa API Key Anda' }}</p>
            @else
                <div class="mt-2 flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <span class="text-sm font-black text-amber-500">BELUM DIATUR</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Masukkan API Key (<code class="text-sky-500">qv_live_…</code>)</p>
            @endif
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Saldo Wallet Bot (GET /balance)</p>
            @php
                $rawBal = (float) ($balanceInfo['balance'] ?? 0);
                $rawCred = (float) ($balanceInfo['credit'] ?? 0);
                $balCurr = strtoupper((string) ($balanceInfo['currency'] ?? 'USD'));
                $estIdr = $balCurr === 'IDR' ? (int) $rawBal : (int) round($rawBal * $usdRate);
            @endphp
            <p class="text-xl font-black text-gray-900 dark:text-white mt-1.5">
                {{ $balCurr === 'USD' ? '$' . number_format($rawBal, 2) : $balCurr . ' ' . number_format($rawBal, 0, ',', '.') }}
            </p>
            <p class="text-xs text-emerald-500 font-bold mt-0.5">
                &approx; Rp {{ number_format($estIdr, 0, ',', '.') }}
                @if($rawCred > 0)
                    <span class="text-gray-400 font-normal">| Credit: ${{ number_format($rawCred, 2) }}</span>
                @endif
            </p>
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Katalog Produk &amp; Varian Live</p>
            <p class="text-xl font-black text-sky-500 mt-1.5">{{ count($liveCatalog) }} <span class="text-xs font-bold text-gray-400">Item / Varian</span></p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Tersimpan di DB: <span class="font-bold text-gray-800 dark:text-gray-200">{{ $dbProducts->count() }}</span> ({{ $tgActiveCount }} Aktif)
            </p>
        </div>

        <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-2xl p-4 shadow-sm">
            <p class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400">Total Transaksi di Bot (GET /orders)</p>
            <p class="text-xl font-black text-orange-500 mt-1.5">
                {{ (int) ($orderSummary['totalOrders'] ?? count($recentOrders)) }} <span class="text-xs font-bold text-gray-400">Order</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Total Spent: <span class="font-bold text-gray-800 dark:text-gray-200">${{ number_format((float) ($orderSummary['totalSpent'] ?? 0), 2) }}</span>
            </p>
        </div>
    </div>

    <!-- Form Pengaturan Kredensial, Kurs USD & Sinkronisasi -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white">Pengaturan Kredensial &amp; Standar Margin API BOT Telegram</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Buka bot Telegram Anda &rarr; ketuk <b>Get API</b> untuk mendapatkan API Key (<code class="text-sky-500">qv_live_…</code>). Setiap request dikirim menggunakan header <code class="text-sky-500">X-API-Key</code>.
                </p>
            </div>
        </div>

        <form action="{{ route('admin.telegram.save') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Personal API Key (<code class="text-sky-500">X-API-Key</code>)</label>
                    <div class="relative">
                        <input type="password" id="tgApiKeyInput" name="telegram_api_key" value="{{ $apiKey }}" placeholder="qv_live_xxxxxxxxxxxxxxxxxxxxxxxx"
                               class="w-full px-3.5 py-2.5 pr-16 rounded-xl text-xs font-mono font-bold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                        <button type="button" onclick="const el = document.getElementById('tgApiKeyInput'); el.type = el.type === 'password' ? 'text' : 'password'; this.textContent = el.type === 'password' ? 'Show' : 'Hide';"
                                class="absolute right-2 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            Show
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Base URL Endpoint</label>
                    <input type="url" name="telegram_api_base_url" value="{{ $baseUrl }}" placeholder="https://www.quantumvault.me/api/v1"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-bold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Kurs Konversi 1 USD ke IDR (Rp)</label>
                    <input type="number" name="telegram_usd_rate" value="{{ $usdRate }}" min="1000" step="100"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-gray-100 dark:border-white/5">
                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Untung Member Otomatis (Rp)</label>
                    <input type="number" name="auto_margin_member" value="{{ $autoMarginMember }}" min="0" step="500"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Untung Reseller Otomatis (Rp)</label>
                    <input type="number" name="auto_margin_reseller" value="{{ $autoMarginReseller }}" min="0" step="500"
                           class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Status Order untuk User</label>
                    <select name="is_enabled_for_users" class="w-full px-3.5 py-2.5 rounded-xl text-xs font-extrabold bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:border-sky-500 outline-none">
                        <option value="0" {{ !$isEnabled ? 'selected' : '' }}>MATI SEMENTARA (Simpan &amp; Jangan Tampilkan ke User Dulu)</option>
                        <option value="1" {{ $isEnabled ? 'selected' : '' }}>MENYALA / AKTIF (Tampilkan &amp; Bisa Di-Order User)</option>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 pt-2">
                <button type="submit" name="sync_now" value="0" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-gray-800 dark:bg-white/10 hover:bg-gray-700 text-white transition">
                    Simpan Konfigurasi
                </button>
                <button type="submit" name="sync_now" value="1" class="px-5 py-2.5 rounded-xl text-xs font-extrabold bg-sky-600 hover:bg-sky-500 text-white shadow-md shadow-sky-500/20 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    Simpan &amp; Tarik Katalog Produk Telegram Sekarang
                </button>
            </div>
        </form>
    </div>

    <!-- Tester Endpoint Interaktif Live (/me, /balance, /products, /products/:productKey, /orders, /orders/:orderId) -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white">Uji Coba Endpoint Live (Interactive API Tester)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Uji langsung seluruh endpoint <code class="text-sky-500">/me</code>, <code class="text-sky-500">/balance</code>, <code class="text-sky-500">/products</code>, <code class="text-sky-500">/products/:productKey</code>, <code class="text-sky-500">/orders</code>, dan <code class="text-sky-500">/orders/:orderId</code> (Read-Only, aman tanpa memotong saldo).</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Pilih Endpoint</label>
                <select id="testerEndpoint" class="w-full px-3 py-2 rounded-xl text-xs font-bold bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">
                    <option value="me">GET /me (Ringkasan Akun)</option>
                    <option value="balance">GET /balance (Cek Saldo Wallet)</option>
                    <option value="products">GET /products (Semua Katalog)</option>
                    <option value="product_detail">GET /products/:productKey (Detail &amp; deliverFields)</option>
                    <option value="orders">GET /orders (Riwayat Pembelian)</option>
                    <option value="order_detail">GET /orders/:orderId (Ambil Ulang Kredensial Order)</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-[10px] font-extrabold uppercase text-gray-400 mb-1">Parameter (<code class="text-sky-500">productKey</code> atau <code class="text-sky-500">orderId</code> jika diperlukan)</label>
                <input type="text" id="testerParam" placeholder="Contoh: outlook_mail atau QV-8F3A21C9"
                       class="w-full px-3 py-2 rounded-xl text-xs font-mono bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="runTelegramEndpointTest()" id="btnRunTester"
                        class="w-full px-4 py-2 rounded-xl text-xs font-extrabold bg-sky-600 hover:bg-sky-500 text-white transition">
                    Jalankan Request
                </button>
            </div>
        </div>

        <pre id="testerOutput" class="hidden mt-4 p-4 rounded-2xl bg-gray-950 text-emerald-400 text-[11px] font-mono overflow-x-auto max-h-80 border border-white/10"></pre>
    </div>

    <!-- Tabel Katalog Produk & Varian dari API BOT Telegram -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200/80 dark:border-white/5 rounded-3xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-gray-100 dark:border-white/5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white">Daftar Katalog Produk &amp; Varian (<code class="text-sky-500">GET /products</code>)</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Setiap produk / varian otomatis dikonversi ke Rupiah dan ditambahkan margin standar saat disinkronkan.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-gray-900/60 text-[10px] font-extrabold uppercase text-gray-400 border-b border-gray-100 dark:border-white/5">
                    <tr>
                        <th class="py-3 px-4">ID Katalog (<code class="text-sky-500">api_product_id</code>)</th>
                        <th class="py-3 px-4">Nama Produk &amp; Varian</th>
                        <th class="py-3 px-4">Harga API Asli</th>
                        <th class="py-3 px-4">Modal IDR</th>
                        <th class="py-3 px-4">Harga Jual Toko</th>
                        <th class="py-3 px-4">Stok Live</th>
                        <th class="py-3 px-4">Status di Database</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse($liveCatalog as $apiId => $item)
                        @php
                            $dbRow = $dbProducts->get($apiId);
                            $sellPrice = $dbRow ? (int) $dbRow->price : ((int) $item['price'] + $autoMarginMember);
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02]">
                            <td class="py-3 px-4 font-mono text-[11px] font-bold text-sky-500">{{ $apiId }}</td>
                            <td class="py-3 px-4">
                                <div class="font-extrabold text-gray-900 dark:text-white">{{ $dbRow ? $dbRow->name : $item['name'] }}</div>
                                @if(!empty($item['description']))
                                    <div class="text-[11px] text-gray-400 truncate max-w-md">{{ $item['description'] }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-gray-600 dark:text-gray-300">
                                {{ ($item['currency'] ?? 'USD') === 'USD' ? '$' . number_format((float) ($item['usd_price'] ?? 0), 2) : ($item['currency'] . ' ' . number_format((float) ($item['usd_price'] ?? 0), 0)) }}
                            </td>
                            <td class="py-3 px-4 font-extrabold text-gray-800 dark:text-gray-200">
                                Rp {{ number_format((int) $item['price'], 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 font-extrabold text-emerald-500">
                                Rp {{ number_format($sellPrice, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                @if(!empty($item['unlimited']))
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-sky-500/15 text-sky-400">UNLIMITED</span>
                                @elseif((int) $item['stock'] > 0)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-emerald-500/15 text-emerald-500">{{ $item['stock'] }} Tersedia</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-rose-500/15 text-rose-500">Habis (0)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($dbRow)
                                    @if($dbRow->status === 'available' && $isEnabled)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-emerald-500/15 text-emerald-500">AKTIF DI TOKO</span>
                                    @elseif($dbRow->status === 'available' && !$isEnabled)
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-amber-500/15 text-amber-500">TERSIMPAN (PROVIDER OFF)</span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-gray-500/15 text-gray-400">NONAKTIF</span>
                                    @endif
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black bg-gray-500/10 text-gray-400">BELUM DISINKRONKAN</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-gray-400 text-xs">
                                Belum ada katalog live yang ditampilkan. Masukkan API Key di atas lalu klik <b>Simpan &amp; Tarik Katalog Produk Telegram Sekarang</b>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function runTelegramEndpointTest() {
    const endpoint = document.getElementById('testerEndpoint').value;
    const param = document.getElementById('testerParam').value.trim();
    const out = document.getElementById('testerOutput');
    const btn = document.getElementById('btnRunTester');

    out.classList.remove('hidden');
    out.textContent = 'Menghubungi endpoint API BOT Telegram...';
    btn.disabled = true;

    try {
        const res = await fetch('{{ route("admin.telegram.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ endpoint, param })
        });
        const data = await res.json();
        out.textContent = JSON.stringify(data, null, 2);
    } catch (e) {
        out.textContent = 'Error: ' + e.message;
    } finally {
        btn.disabled = false;
    }
}
</script>
@endsection
