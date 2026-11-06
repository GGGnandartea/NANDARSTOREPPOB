@extends('layouts.admin')

@section('title', 'SEO & Google Search Console')

@section('content')
@php
    $sitemapUrl = url('/sitemap.xml');
    $robotsUrl  = url('/robots.txt');
    $baseUrl    = !empty($seoCfg['seo_canonical_domain']) ? rtrim($seoCfg['seo_canonical_domain'], '/') : rtrim(url('/'), '/');
    $score      = (int) ($audit['score'] ?? 85);
    $scoreColor = $score >= 85 ? 'emerald' : ($score >= 60 ? 'amber' : 'rose');
    $isIndexed  = !str_contains(strtolower($seoCfg['seo_robots_index'] ?? 'index, follow'), 'noindex');
    $hasGsc     = !empty($seoCfg['seo_google_verification']) || !empty($seoCfg['seo_google_html_file']);
@endphp

<div class="space-y-6 max-w-6xl mx-auto pb-14" x-data="{ activeTab: 'utama', serpMode: 'mobile' }">
    <!-- HEADER PAGE -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
                <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-950/70 px-2.5 py-0.5 rounded-full border border-blue-200 dark:border-blue-800/50">
                    Search Engine Optimization
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Google Search Console &amp; Rich Snippets</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                Pusat Konfigurasi SEO &amp; Google Search Console
            </h1>
            <p class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                Atur tagline pencarian Google, meta description, kata kunci target, kode verifikasi Google Search Console, Schema.org JSON-LD, dan Sitemap XML otomatis.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $sitemapUrl }}" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-700 dark:text-gray-200 hover:border-blue-500 hover:text-blue-600 transition flex items-center gap-1.5 shadow-xs">
                <span>🗺️ Buka Sitemap.xml</span>
            </a>
            <a href="{{ $robotsUrl }}" target="_blank" class="px-3.5 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-black text-gray-700 dark:text-gray-200 hover:border-blue-500 hover:text-blue-600 transition flex items-center gap-1.5 shadow-xs">
                <span>🤖 Buka Robots.txt</span>
            </a>
            <button type="button" onclick="pingSitemapIndex()" id="btn-ping-sitemap" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black transition flex items-center gap-1.5 shadow-md shadow-blue-500/20 cursor-pointer">
                <span id="btn-ping-sitemap-label">🔄 Perbarui Cache Sitemap</span>
            </button>
        </div>
    </div>

    <!-- ALERT SUCCESS / ERROR -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2.5 animate-fade-in shadow-xs">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-xs flex items-center gap-3 shadow-sm">
            <div class="w-7 h-7 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <div id="ping-sitemap-alert" class="hidden p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-700 dark:text-blue-300 text-xs font-bold items-center justify-between gap-3">
        <span id="ping-sitemap-msg"></span>
        <button type="button" onclick="document.getElementById('ping-sitemap-alert').classList.add('hidden')" class="text-xs font-black underline cursor-pointer">Tutup</button>
    </div>

    <!-- RINGKASAN SKOR KESEHATAN SEO & LIVE GOOGLE SERP PREVIEW -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
        <!-- KIRI: SKOR KESEHATAN SEO & CHECKLIST AUDIT (5 KOLOM) -->
        <div class="lg:col-span-5 admin-card p-5 sm:p-6 flex flex-col justify-between space-y-4">
            <div>
                <div class="flex items-center justify-between gap-3 pb-3.5 border-b border-gray-100 dark:border-white/5">
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">Indeks Kualitas Pencarian</span>
                        <h2 class="text-base font-black text-gray-900 dark:text-white">Skor Kesehatan SEO Website</h2>
                    </div>
                    <div class="px-3.5 py-1.5 rounded-2xl font-black text-lg border {{ $score >= 85 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : ($score >= 60 ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30') }}">
                        {{ $score }}/100
                    </div>
                </div>

                <!-- Status Badges -->
                <div class="grid grid-cols-2 gap-2 my-3.5">
                    <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200/70 dark:border-white/10 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $isIndexed ? 'bg-emerald-500' : 'bg-rose-500' }} shrink-0"></span>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-gray-400 block leading-none">Status Robot</span>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-100 truncate block mt-0.5">{{ $isIndexed ? 'INDEX, FOLLOW' : 'NOINDEX (TERSEMBUNYI)' }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200/70 dark:border-white/10 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-gray-400 block leading-none">Peta Situs XML</span>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-100 truncate block mt-0.5">{{ $totalSitemapUrls }} URL Terindeks</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200/70 dark:border-white/10 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ ($seoCfg['seo_schema_enabled'] ?? '1') === '1' ? 'bg-emerald-500' : 'bg-gray-400' }} shrink-0"></span>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-gray-400 block leading-none">Schema JSON-LD</span>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-100 truncate block mt-0.5">{{ ($seoCfg['seo_schema_enabled'] ?? '1') === '1' ? 'Rich Snippet Aktif' : 'Nonaktif' }}</span>
                        </div>
                    </div>
                    <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200/70 dark:border-white/10 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $hasGsc ? 'bg-emerald-500' : 'bg-amber-500' }} shrink-0"></span>
                        <div class="min-w-0">
                            <span class="text-[10px] font-bold text-gray-400 block leading-none">Search Console</span>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-100 truncate block mt-0.5">{{ $hasGsc ? 'Terverifikasi' : 'Belum Diisi' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist Items -->
                <div class="space-y-2 max-h-[250px] overflow-y-auto pr-1">
                    @foreach($audit['checks'] as $chk)
                        <div class="p-2.5 rounded-xl border text-xs flex items-start gap-2.5 {{ $chk['passed'] ? 'bg-emerald-500/5 border-emerald-500/20 text-gray-800 dark:text-gray-200' : 'bg-amber-500/10 border-amber-500/30 text-amber-900 dark:text-amber-200' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-black shrink-0 mt-0.5 {{ $chk['passed'] ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-white' }}">
                                {{ $chk['passed'] ? '✓' : '!' }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-black text-[11px]">{{ $chk['label'] }}</span>
                                    <span class="text-[10px] font-extrabold opacity-75">+{{ $chk['points'] }} poin</span>
                                </div>
                                <p class="text-[11px] opacity-80 leading-snug mt-0.5">{{ $chk['detail'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Preset Generator 1-Klik -->
            <div class="pt-3 border-t border-gray-100 dark:border-white/5">
                <p class="text-[10px] font-black uppercase tracking-wider text-orange-500 mb-2">
                    ✨ Isi Otomatis Rekomendasi Tagline &amp; Meta SEO Terbaik:
                </p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                    <button type="button" onclick="applySeoPreset(1)" class="px-2.5 py-2 rounded-xl bg-orange-500/10 hover:bg-orange-500/20 border border-orange-500/30 text-orange-600 dark:text-orange-300 text-[11px] font-black transition cursor-pointer text-center">
                        1. Akun Premium + PPOB
                    </button>
                    <button type="button" onclick="applySeoPreset(2)" class="px-2.5 py-2 rounded-xl bg-blue-500/10 hover:bg-blue-500/20 border border-blue-500/30 text-blue-600 dark:text-blue-300 text-[11px] font-black transition cursor-pointer text-center">
                        2. Fokus PPOB &amp; Top Up
                    </button>
                    <button type="button" onclick="applySeoPreset(3)" class="px-2.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 text-emerald-600 dark:text-emerald-300 text-[11px] font-black transition cursor-pointer text-center">
                        3. Pusat Reseller VIP
                    </button>
                </div>
            </div>
        </div>

        <!-- KANAN: SIMULASI TAMPILAN DI GOOGLE SEARCH (SERP PREVIEW REAL-TIME) (7 KOLOM) -->
        <div class="lg:col-span-7 admin-card p-5 sm:p-6 flex flex-col justify-between space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-gray-100 dark:border-white/5">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black text-base shadow-md shadow-blue-500/20">
                        G
                    </div>
                    <div>
                        <h2 class="text-sm sm:text-base font-black text-gray-900 dark:text-white">Live Preview Hasil Pencarian Google (SERP)</h2>
                        <p class="text-[11px] font-medium text-gray-400">Simulasi tampilan nyata website Anda saat dicari di Google</p>
                    </div>
                </div>
                <div class="inline-flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1 border border-gray-200 dark:border-white/10">
                    <button type="button" @click="serpMode = 'mobile'" :class="serpMode === 'mobile' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-gray-500'" class="px-3 py-1 rounded-lg text-[11px] font-black transition cursor-pointer">
                        📱 HP (Mobile)
                    </button>
                    <button type="button" @click="serpMode = 'desktop'" :class="serpMode === 'desktop' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs' : 'text-gray-500'" class="px-3 py-1 rounded-lg text-[11px] font-black transition cursor-pointer">
                        💻 Desktop
                    </button>
                </div>
            </div>

            <!-- Kotak Simulasi Google -->
            <div class="bg-white dark:bg-[#1f2937] border border-gray-200 dark:border-gray-700 rounded-2xl p-4 sm:p-5 shadow-inner transition-all" :class="serpMode === 'mobile' ? 'max-w-md mx-auto w-full' : 'w-full'">
                <!-- Header Situs di Google -->
                <div class="flex items-center gap-2.5 mb-1.5">
                    <div class="w-7 h-7 rounded-full bg-orange-500/15 border border-orange-500/30 flex items-center justify-center overflow-hidden shrink-0">
                        @if(!empty($seoCfg['store_favicon']))
                            <img src="{{ $seoCfg['store_favicon'] }}" alt="Icon" class="w-5 h-5 object-contain">
                        @else
                            <span class="text-[11px] font-black text-orange-600">PK</span>
                        @endif
                    </div>
                    <div class="min-w-0 leading-tight">
                        <div id="serp-preview-sitename" class="text-xs font-bold text-gray-900 dark:text-gray-100 truncate">
                            {{ $seoCfg['store_name'] }}
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                            {{ $baseUrl }} › beranda
                        </div>
                    </div>
                </div>

                <!-- Judul Biru Google -->
                <h3 id="serp-preview-title" class="text-base sm:text-lg font-bold text-[#1a0dab] dark:text-[#8ab4f8] hover:underline cursor-pointer leading-snug mb-1">
                    {{ \App\Services\SeoService::resolveHomeTitle($seoCfg) }}
                </h3>

                <!-- Rich Snippet Rating Bintang & Harga -->
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-gray-600 dark:text-gray-300 mb-1.5">
                    <span class="text-amber-500 font-black tracking-tight">★★★★★</span>
                    <span>Rating <strong id="serp-preview-rating">{{ $seoCfg['seo_schema_rating_value'] ?? '4.9' }}</strong>/5.0</span>
                    <span>(<span id="serp-preview-votes">{{ number_format((int) ($seoCfg['seo_schema_rating_count'] ?? 1480), 0, ',', '.') }}</span> ulasan)</span>
                    <span>•</span>
                    <span id="serp-preview-price" class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $seoCfg['seo_schema_price_range'] ?? 'Rp 1.000 - Rp 500.000' }}</span>
                    <span>•</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold">Stok Tersedia</span>
                </div>

                <!-- Deskripsi Meta Google -->
                <p id="serp-preview-desc" class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-3">
                    {{ $seoCfg['store_description'] }}
                </p>

                <!-- Sub-Tagline Highlight -->
                <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700/70">
                    <p id="serp-preview-subtagline" class="text-[11px] font-bold text-orange-600 dark:text-orange-400 truncate">
                        {{ $seoCfg['seo_secondary_tagline'] }}
                    </p>
                </div>

                <!-- Simulasi Google Sitelinks -->
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 gap-2.5 text-left">
                    <div>
                        <span class="text-xs font-bold text-[#1a0dab] dark:text-[#8ab4f8] block">Katalog Akun Premium</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">Netflix, Spotify, Canva Pro, YouTube &amp; CapCut otomatis.</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#1a0dab] dark:text-[#8ab4f8] block">Layanan PPOB &amp; Pulsa</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">Pulsa murah, paket data, token PLN &amp; voucher game 24 jam.</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#1a0dab] dark:text-[#8ab4f8] block">Daftar Reseller VIP</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">Harga modal termurah seumur hidup untuk semua produk.</span>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#1a0dab] dark:text-[#8ab4f8] block">Pusat Bantuan &amp; Garansi</span>
                        <span class="text-[10px] text-gray-500 dark:text-gray-400 line-clamp-1">Panduan order QRIS otomatis &amp; klaim garansi resmi.</span>
                    </div>
                </div>
            </div>

            <!-- Copy Sitemap Quick Bar -->
            <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 block">URL Sitemap untuk Disubmit ke Google Search Console:</span>
                    <code id="sitemap-url-code" class="text-xs font-mono font-black text-gray-900 dark:text-white select-all break-all">{{ $sitemapUrl }}</code>
                </div>
                <button type="button" onclick="copyTextValue('{{ $sitemapUrl }}', 'URL Sitemap berhasil disalin!')" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-[11px] font-black shrink-0 cursor-pointer transition">
                    📋 Salin URL Sitemap
                </button>
            </div>
        </div>
    </div>

    <!-- NAVIGASI TAB PENGATURAN SEO -->
    <div class="flex overflow-x-auto gap-2 pb-1">
        <button type="button" @click="activeTab = 'utama'" :class="activeTab === 'utama' ? 'bg-orange-500 text-white shadow-md shadow-orange-500/25 border-orange-500' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-orange-500/50'" class="px-4 py-2.5 rounded-xl border text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-2">
            <span>🏷️ 1. Tagline, Judul &amp; Kata Kunci</span>
        </button>
        <button type="button" @click="activeTab = 'gsc'" :class="activeTab === 'gsc' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/25 border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-blue-500/50'" class="px-4 py-2.5 rounded-xl border text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-2">
            <span>🔍 2. Verifikasi Google Search Console &amp; Analytics</span>
        </button>
        <button type="button" @click="activeTab = 'template'" :class="activeTab === 'template' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/25 border-emerald-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-emerald-500/50'" class="px-4 py-2.5 rounded-xl border text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-2">
            <span>📄 3. Template SEO Produk, PPOB &amp; FAQ</span>
        </button>
        <button type="button" @click="activeTab = 'schema'" :class="activeTab === 'schema' ? 'bg-amber-500 text-gray-950 shadow-md shadow-amber-500/25 border-amber-500' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-amber-500/50'" class="px-4 py-2.5 rounded-xl border text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-2">
            <span>⭐ 4. Rich Snippets (Bintang Google) &amp; OG Banner</span>
        </button>
        <button type="button" @click="activeTab = 'sitemap'" :class="activeTab === 'sitemap' ? 'bg-purple-600 text-white shadow-md shadow-purple-500/25 border-purple-600' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10 hover:border-purple-500/50'" class="px-4 py-2.5 rounded-xl border text-xs font-black whitespace-nowrap transition cursor-pointer flex items-center gap-2">
            <span>🗺️ 5. Sitemap XML, Robots.txt &amp; Panduan GSC</span>
        </button>
    </div>

    <!-- FORM UTAMA KONFIGURASI SEO -->
    <form action="{{ route('admin.seo.save') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- ========================================================================= -->
        <!-- TAB 1: TAGLINE, JUDUL UTAMA & META KEYWORDS GOOGLE                         -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'utama'" class="admin-card p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                        1. Pengaturan Tagline, Judul Utama &amp; Kata Kunci Pencarian Google
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Atur bagaimana nama brand, tagline utama, dan deskripsi website Anda muncul di halaman pertama Google
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1 rounded-full bg-orange-500/15 text-orange-600 dark:text-orange-400 border border-orange-500/30 uppercase">
                    META TAG UTAMA
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-5">
                <!-- Nama Brand -->
                <div class="md:col-span-4">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Nama Toko / Brand Utama
                    </label>
                    <input type="text" name="store_name" id="seo_store_name" value="{{ $seoCfg['store_name'] }}" oninput="updateSerpPreview()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-black dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="PremiumKita">
                    <p class="text-[11px] text-gray-400 mt-1">Muncul di awal judul Google &amp; nama situs.</p>
                </div>

                <!-- Pemisah Judul -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Pemisah Judul
                    </label>
                    <select name="seo_title_separator" id="seo_title_separator" onchange="updateSerpPreview()" class="w-full px-3.5 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-black dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                        @foreach(['-' => '- (Strip)', '|' => '| (Garis Tegak)', '•' => '• (Bullet)', '—' => '— (Dash Panjang)', ':' => ': (Titik Dua)'] as $sepVal => $sepLabel)
                            <option value="{{ $sepVal }}" {{ ($seoCfg['seo_title_separator'] ?? '-') === $sepVal ? 'selected' : '' }}>{{ $sepLabel }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">Pemisah nama &amp; tagline.</p>
                </div>

                <!-- Tagline Utama Google -->
                <div class="md:col-span-6">
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                            Tagline Utama SEO (Slogan Pencarian Google)
                        </label>
                        <span id="tagline-char-count" class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400">0 / 70 karakter</span>
                    </div>
                    <input type="text" name="store_tagline" id="seo_store_tagline" value="{{ $seoCfg['store_tagline'] }}" oninput="updateSerpPreview()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="Platform Digital, Akun Premium & Layanan PPOB Otomatis 24 Jam">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Tagline ini otomatis digabung menjadi judul utama Google: <strong class="text-gray-700 dark:text-gray-200">Nama Toko - Tagline Utama</strong>.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Sub-Tagline / Highlight Penawaran -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Sub-Tagline / Highlight Keunggulan Toko
                    </label>
                    <input type="text" name="seo_secondary_tagline" id="seo_secondary_tagline" value="{{ $seoCfg['seo_secondary_tagline'] }}" oninput="updateSerpPreview()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="Proses Instan Detik Itu Juga • Bergaransi Resmi • Harga Reseller Termurah">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Ditampilkan pada struktur semantik crawler Googlebot untuk memperkuat relevansi kata kunci layanan.
                    </p>
                </div>

                <!-- Override Judul Beranda Kustom (Opsional) -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Judul Kustom Halaman Utama (Opsional)
                    </label>
                    <input type="text" name="seo_home_title" id="seo_home_title" value="{{ $seoCfg['seo_home_title'] }}" oninput="updateSerpPreview()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500" placeholder="Kosongkan agar otomatis memakai: {store_name} {separator} {tagline}">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Jika dikosongkan, sistem otomatis menyusun judul dari <strong>Nama Toko + Pemisah + Tagline Utama</strong>.
                    </p>
                </div>
            </div>

            <!-- Meta Description Utama -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Meta Description Utama (Deskripsi di Hasil Pencarian Google)
                    </label>
                    <span id="desc-char-count" class="text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400">0 / 160 karakter</span>
                </div>
                <textarea name="store_description" id="seo_store_description" rows="3" oninput="updateSerpPreview()" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs sm:text-sm font-semibold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 leading-relaxed" placeholder="Pusat belanja akun aplikasi premium murah bergaransi, isi pulsa, paket data, token listrik PLN, dan top up game otomatis 24 jam.">{{ $seoCfg['store_description'] }}</textarea>
                <p class="text-[11px] text-gray-400 mt-1">
                    Rekomendasi Google: <strong>110 – 160 karakter</strong>. Memuat kata kunci produk utama (Netflix, Spotify, Canva, Pulsa, Token PLN, Top Up Game) agar mudah terdeteksi saat calon pembeli mencari di Google.
                </p>
            </div>

            <!-- Meta Keywords + Quick Tag Adder -->
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Kata Kunci Target (Meta Keywords — Pisahkan dengan Koma)
                    </label>
                    <span class="text-[10px] font-bold text-gray-400">Klik tag di bawah untuk menambahkan kata kunci populer:</span>
                </div>
                <textarea name="seo_meta_keywords" id="seo_meta_keywords" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 leading-relaxed" placeholder="akun premium murah, netflix murah, spotify premium, canva pro, pulsa murah, token pln, ppob otomatis">{{ $seoCfg['seo_meta_keywords'] }}</textarea>

                <!-- Quick Keyword Chips -->
                <div class="flex flex-wrap items-center gap-1.5 mt-2.5">
                    @foreach([
                        'akun premium murah',
                        'jual netflix murah',
                        'spotify premium bergaransi',
                        'canva pro murah',
                        'youtube premium murah',
                        'capcut pro murah',
                        'chatgpt plus murah',
                        'pulsa murah otomatis',
                        'token listrik pln murah',
                        'top up game murah',
                        'diamond mlbb murah',
                        'top up free fire',
                        'top up e-wallet dana ovo gopay',
                        'agen ppob termurah',
                        'reseller aplikasi premium'
                    ] as $kwChip)
                        <button type="button" onclick="appendKeywordChip('{{ $kwChip }}')" class="px-2.5 py-1 rounded-lg bg-gray-100 hover:bg-orange-500 hover:text-white dark:bg-gray-800 dark:hover:bg-orange-500 text-gray-700 dark:text-gray-300 text-[11px] font-bold transition cursor-pointer border border-gray-200 dark:border-white/10">
                            + {{ $kwChip }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 2: GOOGLE SEARCH CONSOLE, ANALYTICS & WEBMASTER VERIFICATION           -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'gsc'" class="admin-card p-6 sm:p-8 space-y-6" style="display: none;">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                        2. Verifikasi Kepemilikan Google Search Console &amp; Tracking Analytics
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Hubungkan website Anda dengan Google Search Console agar bisa langsung memakai fitur "Inspeksi URL" &amp; "Minta Pengindeksan"
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1 rounded-full bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30 uppercase">
                    GOOGLE SEARCH CONSOLE
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Metode 1: Meta Tag HTML Google Search Console -->
                <div class="p-5 rounded-2xl bg-blue-500/5 border border-blue-500/20 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                            Metode 1: Tag HTML (Paling Direkomendasikan)
                        </span>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-blue-600 text-white">OTOMATIS</span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                        Di Google Search Console, pilih metode verifikasi <strong>"Tag HTML"</strong>. Anda bisa menempelkan <strong>seluruh baris tag `<meta name="google-site-verification" ...>`</strong> atau kode tokennya saja di bawah ini:
                    </p>
                    <input type="text" name="seo_google_verification" value="{{ $seoCfg['seo_google_verification'] }}" class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-blue-300 dark:border-blue-800 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500" placeholder='Contoh: <meta name="google-site-verification" content="AbCdEfGhIjKlMnOpQrStUvWxYz" />'>
                    @if(!empty($seoCfg['seo_google_verification']))
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">
                            ✓ Aktif di `&lt;head&gt;`: <code>&lt;meta name="google-site-verification" content="{{ $seoCfg['seo_google_verification'] }}"&gt;</code>
                        </div>
                    @endif
                </div>

                <!-- Metode 2: File HTML Google Search Console Tanpa Upload FTP -->
                <div class="p-5 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
                            Metode 2: File HTML Google (Tanpa Perlu Upload File!)
                        </span>
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-md bg-emerald-600 text-white">TANPA FTP</span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                        Jika Google meminta Anda mengupload file seperti <code>google1234567890abcdef.html</code>, cukup ketik nama filenya di bawah ini. Sistem akan otomatis membuatkan halamannya!
                    </p>
                    <input type="text" name="seo_google_html_file" value="{{ $seoCfg['seo_google_html_file'] }}" class="w-full px-4 py-3 bg-white dark:bg-gray-900 border border-emerald-300 dark:border-emerald-800 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: google1234567890abcdef.html">
                    @if(!empty($seoCfg['seo_google_html_file']))
                        <div class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 flex items-center justify-between">
                            <span>✓ File aktif: <code>{{ url('/' . $seoCfg['seo_google_html_file']) }}</code></span>
                            <a href="{{ url('/' . $seoCfg['seo_google_html_file']) }}" target="_blank" class="underline font-black">Tes Buka</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Google Analytics 4, GTM, Bing & Yandex -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-3 border-t border-gray-100 dark:border-white/5">
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Google Analytics 4 (GA4 ID)
                    </label>
                    <input type="text" name="seo_ga4_id" value="{{ $seoCfg['seo_ga4_id'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500" placeholder="G-XXXXXXXXXX">
                    <p class="text-[10px] text-gray-400 mt-1">Otomatis memasang script `gtag.js`.</p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Google Tag Manager (GTM ID)
                    </label>
                    <input type="text" name="seo_gtm_id" value="{{ $seoCfg['seo_gtm_id'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500" placeholder="GTM-XXXXXXX">
                    <p class="text-[10px] text-gray-400 mt-1">Kontainer Google Tag Manager.</p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Bing Webmaster (`msvalidate.01`)
                    </label>
                    <input type="text" name="seo_bing_verification" value="{{ $seoCfg['seo_bing_verification'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kode verifikasi Bing...">
                    <p class="text-[10px] text-gray-400 mt-1">Indeks pencarian Microsoft Bing &amp; Yahoo.</p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Yandex Verification
                    </label>
                    <input type="text" name="seo_yandex_verification" value="{{ $seoCfg['seo_yandex_verification'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kode verifikasi Yandex...">
                    <p class="text-[10px] text-gray-400 mt-1">Indeks mesin pencari Yandex.</p>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 3: TEMPLATE SEO OTOMATIS HALAMAN PRODUK, PPOB & FAQ                     -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'template'" class="admin-card p-6 sm:p-8 space-y-6" style="display: none;">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                        3. Template SEO Dinamis Halaman Produk, Layanan PPOB &amp; FAQ
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Setiap produk baru otomatis memiliki judul dan deskripsi SEO unik di Google menggunakan variabel dinamis
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 uppercase">
                    AUTO-SEO PRODUK
                </span>
            </div>

            <!-- Info Variabel Dinamis -->
            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10">
                <p class="text-xs font-black text-gray-800 dark:text-gray-200 mb-2">
                    Variabel Pintar yang Tersedia untuk Template Produk:
                </p>
                <div class="flex flex-wrap gap-2 text-[11px]">
                    <code class="px-2.5 py-1 rounded-lg bg-orange-500/10 text-orange-600 dark:text-orange-400 font-mono font-black">{product_name}</code>
                    <span class="text-gray-400 self-center">= Nama Produk</span>
                    <code class="px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono font-black">{price}</code>
                    <span class="text-gray-400 self-center">= Harga Produk (Rp ...)</span>
                    <code class="px-2.5 py-1 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 font-mono font-black">{category}</code>
                    <span class="text-gray-400 self-center">= Kategori</span>
                    <code class="px-2.5 py-1 rounded-lg bg-purple-500/10 text-purple-600 dark:text-purple-400 font-mono font-black">{store_name}</code>
                    <span class="text-gray-400 self-center">= Nama Toko</span>
                    <code class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono font-black">{tagline}</code>
                    <span class="text-gray-400 self-center">= Tagline Toko</span>
                </div>
            </div>

            <!-- Template Halaman Produk -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Template Judul SEO Halaman Detail Produk (`/product/{id}`)
                    </label>
                    <input type="text" name="seo_product_title_template" value="{{ $seoCfg['seo_product_title_template'] }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Contoh hasil: <em>Jual Netflix Premium 1 Bulan Murah Bergaransi (Rp 25.000) - PremiumKita</em>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Template Meta Description Halaman Detail Produk
                    </label>
                    <textarea name="seo_product_desc_template" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">{{ $seoCfg['seo_product_desc_template'] }}</textarea>
                </div>
            </div>

            <!-- SEO Halaman PPOB -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-gray-100 dark:border-white/5">
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Judul SEO Halaman PPOB &amp; Pulsa (`/ppob`)
                    </label>
                    <input type="text" name="seo_ppob_title" value="{{ $seoCfg['seo_ppob_title'] }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Meta Description Halaman PPOB &amp; Pulsa
                    </label>
                    <textarea name="seo_ppob_description" rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">{{ $seoCfg['seo_ppob_description'] }}</textarea>
                </div>
            </div>

            <!-- SEO Halaman FAQ & Syarat Ketentuan -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-gray-100 dark:border-white/5">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">
                            Judul SEO Halaman FAQ (`/faq`)
                        </label>
                        <input type="text" name="seo_faq_title" value="{{ $seoCfg['seo_faq_title'] }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">
                            Meta Description Halaman FAQ
                        </label>
                        <textarea name="seo_faq_description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">{{ $seoCfg['seo_faq_description'] }}</textarea>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">
                            Judul SEO Halaman Syarat &amp; Ketentuan (`/syarat-ketentuan`)
                        </label>
                        <input type="text" name="seo_terms_title" value="{{ $seoCfg['seo_terms_title'] }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">
                            Meta Description Halaman Syarat &amp; Ketentuan
                        </label>
                        <textarea name="seo_terms_description" rows="2" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500">{{ $seoCfg['seo_terms_description'] }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 4: RICH SNIPPETS SCHEMA.ORG (JSON-LD), BINTANG GOOGLE & OG BANNER      -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'schema'" class="admin-card p-6 sm:p-8 space-y-6" style="display: none;">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                        4. Rich Snippets Schema.org (JSON-LD), Rating Bintang Google &amp; OG Banner
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Memunculkan bintang ulasan, rentang harga, kotak pencarian Sitelinks, dan gambar thumbnail saat link dibagikan
                    </p>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="seo_schema_enabled" value="1" {{ ($seoCfg['seo_schema_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-orange-500 focus:ring-orange-500">
                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase">Aktifkan Schema JSON-LD</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Tipe Schema Bisnis
                    </label>
                    <select name="seo_schema_type" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                        @foreach(['OnlineStore' => 'OnlineStore (Toko Digital Online)', 'Store' => 'Store (Toko Ritel)', 'Organization' => 'Organization (Perusahaan)'] as $stKey => $stLabel)
                            <option value="{{ $stKey }}" {{ ($seoCfg['seo_schema_type'] ?? 'OnlineStore') === $stKey ? 'selected' : '' }}>{{ $stLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Rating Bintang Google (1.0 - 5.0)
                    </label>
                    <input type="text" name="seo_schema_rating_value" id="seo_schema_rating_value" value="{{ $seoCfg['seo_schema_rating_value'] }}" oninput="updateSerpPreview()" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black dark:text-white outline-none focus:ring-2 focus:ring-amber-500" placeholder="4.9">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Jumlah Ulasan Rich Snippet
                    </label>
                    <input type="number" name="seo_schema_rating_count" id="seo_schema_rating_count" value="{{ $seoCfg['seo_schema_rating_count'] }}" oninput="updateSerpPreview()" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black dark:text-white outline-none focus:ring-2 focus:ring-amber-500" placeholder="1480">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                        Rentang Harga Produk
                    </label>
                    <input type="text" name="seo_schema_price_range" id="seo_schema_price_range" value="{{ $seoCfg['seo_schema_price_range'] }}" oninput="updateSerpPreview()" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500" placeholder="Rp 1.000 - Rp 500.000">
                </div>
            </div>

            <!-- Link Sosial Media Resmi (Knowledge Graph Google) -->
            <div class="pt-4 border-t border-gray-100 dark:border-white/5">
                <h4 class="text-xs font-black text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-3">
                    Profil Sosial Media Resmi (`sameAs` Knowledge Graph Google)
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1">Instagram URL</label>
                        <input type="url" name="seo_social_instagram" value="{{ $seoCfg['seo_social_instagram'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none" placeholder="https://instagram.com/...">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1">TikTok URL</label>
                        <input type="url" name="seo_social_tiktok" value="{{ $seoCfg['seo_social_tiktok'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none" placeholder="https://tiktok.com/@...">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1">Telegram Channel / Grup</label>
                        <input type="url" name="seo_social_telegram" value="{{ $seoCfg['seo_social_telegram'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none" placeholder="https://t.me/...">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-500 mb-1">YouTube Channel</label>
                        <input type="url" name="seo_social_youtube" value="{{ $seoCfg['seo_social_youtube'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none" placeholder="https://youtube.com/@...">
                    </div>
                </div>
            </div>

            <!-- Open Graph Banner & Theme Color -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 pt-4 border-t border-gray-100 dark:border-white/5">
                <div class="md:col-span-8 space-y-2.5">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Gambar Banner Open Graph (Thumbnail Share WhatsApp, Facebook, Telegram &amp; Google Discover)
                    </label>
                    <input type="file" name="seo_og_image_file" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-orange-100 file:text-orange-700 hover:file:bg-orange-200 cursor-pointer">
                    <input type="text" name="seo_og_image_url" value="{{ $seoCfg['seo_og_image'] }}" class="w-full px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-semibold dark:text-white outline-none" placeholder="Atau masukkan URL gambar banner OG (1200 x 630 px)...">
                    @if(!empty($seoCfg['seo_og_image']))
                        <label class="inline-flex items-center gap-1.5 text-xs text-rose-500 font-bold cursor-pointer">
                            <input type="checkbox" name="remove_seo_og_image" value="1" class="rounded border-gray-300 text-rose-600">
                            <span>Hapus Banner OG Kustom (Gunakan Logo Toko)</span>
                        </label>
                    @endif
                </div>

                <div class="md:col-span-4 space-y-2.5">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Warna Tema Browser Mobile (`theme-color`)
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="color" value="{{ $seoCfg['seo_theme_color'] ?: '#f97316' }}" onchange="document.getElementById('seo_theme_color_input').value = this.value" class="w-12 h-11 rounded-xl border border-gray-200 cursor-pointer bg-transparent">
                        <input type="text" name="seo_theme_color" id="seo_theme_color_input" value="{{ $seoCfg['seo_theme_color'] ?: '#f97316' }}" class="flex-1 px-3.5 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none">
                    </div>
                    <p class="text-[11px] text-gray-400">Warna bar atas Chrome di HP Android.</p>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- TAB 5: SITEMAP XML, ROBOTS.TXT & PANDUAN GOOGLE SEARCH CONSOLE             -->
        <!-- ========================================================================= -->
        <div x-show="activeTab === 'sitemap'" class="admin-card p-6 sm:p-8 space-y-6" style="display: none;">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div>
                    <h3 class="text-base sm:text-lg font-black text-gray-900 dark:text-white">
                        5. Pengaturan Sitemap XML Otomatis, Robots.txt &amp; Panduan Indeks Cepat
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Kontrol bagaimana robot pencari merayapi seluruh halaman dan produk aktif di website Anda
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1 rounded-full bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30 uppercase">
                    CRAWLER &amp; SITEMAP
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Pengaturan Robot & Canonical Domain -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                            Kebijakan Perayapan Robot (`meta name="robots"`)
                        </label>
                        <select name="seo_robots_index" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-black dark:text-white outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="index, follow" {{ ($seoCfg['seo_robots_index'] ?? 'index, follow') === 'index, follow' ? 'selected' : '' }}>
                                ✅ index, follow (Izinkan Google Mengindeks Semua Halaman Publik — Direkomendasikan)
                            </option>
                            <option value="noindex, nofollow" {{ ($seoCfg['seo_robots_index'] ?? '') === 'noindex, nofollow' ? 'selected' : '' }}>
                                🚫 noindex, nofollow (Sembunyikan Website dari Mesin Pencari)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                            Domain Utama Kanonikal (Opsional)
                        </label>
                        <input type="url" name="seo_canonical_domain" value="{{ $seoCfg['seo_canonical_domain'] }}" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500" placeholder="Contoh: https://premiumkita.com (Kosongkan untuk otomatis)">
                        <p class="text-[11px] text-gray-400 mt-1">
                            Mencegah duplikasi indeks antara <code>http://</code>, <code>https://</code>, dan <code>www</code> di Google Search Console.
                        </p>
                    </div>

                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10 space-y-2.5">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="seo_sitemap_enabled" value="1" {{ ($seoCfg['seo_sitemap_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-purple-600">
                            <span class="text-xs font-black text-gray-800 dark:text-gray-200">Aktifkan Sitemap XML Otomatis (`/sitemap.xml`)</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="seo_sitemap_include_products" value="1" {{ ($seoCfg['seo_sitemap_include_products'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-purple-600">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Sertakan Seluruh Produk Aktif ({{ $activeProductCount }} produk) ke Sitemap</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" name="seo_sitemap_include_images" value="1" {{ ($seoCfg['seo_sitemap_include_images'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-purple-600">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Sertakan Tag Gambar Produk (`&lt;image:image&gt;`) agar Masuk Google Images</span>
                        </label>
                    </div>
                </div>

                <!-- Aturan Tambahan Robots.txt -->
                <div class="space-y-3">
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">
                        Aturan Kustom Tambahan `robots.txt` (Opsional)
                    </label>
                    <textarea name="seo_robots_custom" rows="5" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500" placeholder="# Contoh aturan tambahan&#10;Crawl-delay: 1">{{ $seoCfg['seo_robots_custom'] }}</textarea>

                    <div class="p-3.5 rounded-2xl bg-gray-900 text-gray-200 font-mono text-[11px] leading-relaxed overflow-x-auto">
                        <div class="text-[10px] font-black uppercase text-purple-400 mb-1">Preview /robots.txt Aktif Saat Ini:</div>
                        <pre class="whitespace-pre-wrap">{{ \App\Services\SeoService::generateRobotsTxt(request()->getSchemeAndHttpHost()) }}</pre>
                    </div>
                </div>
            </div>

            <!-- Panduan Langkah Demi Langkah Google Search Console -->
            <div class="p-5 rounded-2xl bg-gradient-to-br from-blue-500/10 via-indigo-500/5 to-purple-500/10 border border-blue-500/25 space-y-3">
                <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <span>🚀 Cara Agar Website Cepat Muncul di Halaman 1 Google (Google Search Console):</span>
                </h4>
                <ol class="list-decimal list-inside text-xs font-semibold text-gray-700 dark:text-gray-300 space-y-1.5 leading-relaxed">
                    <li>Buka <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 underline font-black">Google Search Console</a> lalu tambahkan properti domain/URL website Anda (<code>{{ $baseUrl }}</code>).</li>
                    <li>Pilih metode verifikasi <strong>Tag HTML</strong>, salin kodenya, lalu tempel pada tab <strong>"2. Verifikasi Google Search Console"</strong> di halaman ini dan klik <strong>Simpan</strong>.</li>
                    <li>Kembali ke Google Search Console, klik <strong>Verifikasi</strong>, lalu buka menu <strong>Sitemaps (Peta Situs)</strong> di sebelah kiri.</li>
                    <li>Masukkan <code>sitemap.xml</code> (atau <code>{{ $sitemapUrl }}</code>) lalu klik <strong>Submit / Kirim</strong>.</li>
                    <li>Gunakan kolom <strong>Inspeksi URL</strong> di bagian atas Google Search Console, ketik <code>{{ $baseUrl }}</code> lalu klik <strong>"Minta Pengindeksan" (Request Indexing)</strong> agar Googlebot langsung merayapi website Anda hari ini juga!</li>
                </ol>
            </div>
        </div>

        <!-- TOMBOL SIMPAN FLOATING / STICKY BOTTOM -->
        <div class="admin-card p-4 sm:p-5 flex flex-col sm:flex-row items-center justify-between gap-3 border-2 border-orange-500/30">
            <div class="text-xs font-bold text-gray-600 dark:text-gray-300">
                💡 Perubahan tagline, meta description, kode verifikasi Google, dan Sitemap XML akan <strong>langsung aktif detik itu juga</strong> setelah disimpan.
            </div>
            <button type="submit" class="w-full sm:w-auto px-7 py-3.5 rounded-xl gradient-orange text-white font-black text-sm shadow-lg shadow-orange-500/25 hover:brightness-110 active:scale-95 transition cursor-pointer flex items-center justify-center gap-2 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span>Simpan Semua Konfigurasi SEO &amp; Google</span>
            </button>
        </div>
    </form>
</div>

<script>
function updateSerpPreview() {
    var nameEl = document.getElementById('seo_store_name');
    var sepEl = document.getElementById('seo_title_separator');
    var tagEl = document.getElementById('seo_store_tagline');
    var subTagEl = document.getElementById('seo_secondary_tagline');
    var customHomeEl = document.getElementById('seo_home_title');
    var descEl = document.getElementById('seo_store_description');
    var ratingEl = document.getElementById('seo_schema_rating_value');
    var votesEl = document.getElementById('seo_schema_rating_count');
    var priceEl = document.getElementById('seo_schema_price_range');

    var storeName = (nameEl && nameEl.value.trim()) ? nameEl.value.trim() : 'PremiumKita';
    var sep = (sepEl && sepEl.value) ? sepEl.value : '-';
    var tagline = (tagEl && tagEl.value.trim()) ? tagEl.value.trim() : 'Platform Digital, Akun Premium & Layanan PPOB Otomatis 24 Jam';
    var customHome = (customHomeEl && customHomeEl.value.trim()) ? customHomeEl.value.trim() : '';
    var desc = (descEl && descEl.value.trim()) ? descEl.value.trim() : '';

    var finalTitle = customHome
        ? customHome.replace(/\{store_name\}/g, storeName).replace(/\{tagline\}/g, tagline).replace(/\{separator\}/g, sep)
        : (storeName + ' ' + sep + ' ' + tagline);

    var previewSitename = document.getElementById('serp-preview-sitename');
    var previewTitle = document.getElementById('serp-preview-title');
    var previewDesc = document.getElementById('serp-preview-desc');
    var previewSub = document.getElementById('serp-preview-subtagline');
    var previewRating = document.getElementById('serp-preview-rating');
    var previewVotes = document.getElementById('serp-preview-votes');
    var previewPrice = document.getElementById('serp-preview-price');

    if (previewSitename) previewSitename.textContent = storeName;
    if (previewTitle) previewTitle.textContent = finalTitle;
    if (previewDesc) previewDesc.textContent = desc;
    if (previewSub && subTagEl) previewSub.textContent = subTagEl.value;
    if (previewRating && ratingEl) previewRating.textContent = ratingEl.value || '4.9';
    if (previewVotes && votesEl) previewVotes.textContent = Number(votesEl.value || 1480).toLocaleString('id-ID');
    if (previewPrice && priceEl) previewPrice.textContent = priceEl.value || 'Rp 1.000 - Rp 500.000';

    // Update character counters
    var tagCountEl = document.getElementById('tagline-char-count');
    if (tagCountEl && tagEl) {
        var tLen = tagEl.value.length;
        tagCountEl.textContent = tLen + ' / 70 karakter';
        tagCountEl.className = 'text-[10px] font-extrabold ' + (tLen >= 15 && tLen <= 85 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500');
    }

    var descCountEl = document.getElementById('desc-char-count');
    if (descCountEl && descEl) {
        var dLen = descEl.value.length;
        descCountEl.textContent = dLen + ' / 160 karakter';
        descCountEl.className = 'text-[10px] font-extrabold ' + (dLen >= 90 && dLen <= 175 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-500');
    }
}

function appendKeywordChip(keyword) {
    var kwInput = document.getElementById('seo_meta_keywords');
    if (!kwInput) return;
    var current = kwInput.value.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    var exists = current.some(function(item) { return item.toLowerCase() === keyword.toLowerCase(); });
    if (!exists) {
        current.push(keyword);
        kwInput.value = current.join(', ');
    }
}

function applySeoPreset(presetId) {
    var tagEl = document.getElementById('seo_store_tagline');
    var subTagEl = document.getElementById('seo_secondary_tagline');
    var descEl = document.getElementById('seo_store_description');
    var kwEl = document.getElementById('seo_meta_keywords');

    if (presetId === 1) {
        if (tagEl) tagEl.value = 'Platform Digital, Akun Premium & Layanan PPOB Otomatis 24 Jam';
        if (subTagEl) subTagEl.value = 'Proses Instan Detik Itu Juga • Bergaransi Resmi • Harga Reseller Termurah';
        if (descEl) descEl.value = 'Pusat belanja akun aplikasi premium murah bergaransi (Netflix, Spotify, Canva Pro, YouTube Premium, CapCut Pro, ChatGPT Plus), isi pulsa murah semua operator, paket data, token listrik PLN, top up game & e-wallet otomatis 24 jam.';
        if (kwEl) kwEl.value = 'akun premium murah, jual netflix murah, spotify premium, canva pro murah, capcut pro, youtube premium, chatgpt plus murah, top up game murah, pulsa murah otomatis, token listrik pln, ppob termurah, reseller aplikasi premium, premiumkita';
    } else if (presetId === 2) {
        if (tagEl) tagEl.value = 'Pusat Pulsa Murah, Paket Data, Token PLN, Top Up Game & PPOB 24 Jam';
        if (subTagEl) subTagEl.value = 'Transaksi Detik Itu Juga • QRIS Otomatis • Harga Modal Agen Termurah';
        if (descEl) descEl.value = 'Agen pulsa murah semua operator, paket kuota internet, token listrik PLN prabayar, top up diamond MLBB & Free Fire, isi saldo DANA/OVO/GoPay/ShopeePay, serta cek tagihan PDAM & BPJS otomatis 24 jam.';
        if (kwEl) kwEl.value = 'pulsa murah otomatis, agen pulsa termurah, token listrik pln murah, paket data murah, top up game murah, diamond mlbb murah, top up free fire, saldo dana ovo gopay, bayar tagihan pdam bpjs, ppob otomatis 24 jam, premiumkita';
    } else if (presetId === 3) {
        if (tagEl) tagEl.value = 'Distributor Resmi Reseller Akun Premium & Produk Digital Bergaransi';
        if (subTagEl) subTagEl.value = 'Harga Modal VIP Termurah Seumur Hidup • Pengiriman Akun Otomatis 24 Jam';
        if (descEl) descEl.value = 'Gabung Reseller VIP aplikasi premium & produk digital termurah di Indonesia. Nikmati harga modal terbaik untuk Netflix, Canva Pro, Spotify, YouTube, CapCut, serta layanan PPOB dan top up otomatis 24 jam.';
        if (kwEl) kwEl.value = 'reseller akun premium, distributor aplikasi premium, supplier netflix murah, supplier canva pro, harga reseller produk digital, bisnis akun premium, agen produk digital otomatis, premiumkita';
    }

    updateSerpPreview();
}

function copyTextValue(text, message) {
    navigator.clipboard.writeText(text).then(function() {
        var alertBox = document.getElementById('ping-sitemap-alert');
        var alertMsg = document.getElementById('ping-sitemap-msg');
        if (alertBox && alertMsg) {
            alertMsg.textContent = '✓ ' + message;
            alertBox.classList.remove('hidden');
            alertBox.classList.add('flex');
        }
    });
}

function pingSitemapIndex() {
    var btn = document.getElementById('btn-ping-sitemap');
    var label = document.getElementById('btn-ping-sitemap-label');
    var alertBox = document.getElementById('ping-sitemap-alert');
    var alertMsg = document.getElementById('ping-sitemap-msg');

    if (btn) btn.disabled = true;
    if (label) label.textContent = '⏳ Memperbarui Sitemap...';

    fetch('{{ route('admin.seo.ping_sitemap') }}', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (alertBox && alertMsg) {
            alertMsg.textContent = '✓ ' + (data.message || 'Sitemap XML berhasil diperbarui!');
            alertBox.classList.remove('hidden');
            alertBox.classList.add('flex');
        }
    })
    .catch(function() {
        if (alertBox && alertMsg) {
            alertMsg.textContent = 'Gagal memperbarui cache sitemap.';
            alertBox.classList.remove('hidden');
            alertBox.classList.add('flex');
        }
    })
    .finally(function() {
        if (btn) btn.disabled = false;
        if (label) label.textContent = '🔄 Perbarui Cache Sitemap';
    });
}

document.addEventListener('DOMContentLoaded', function() {
    updateSerpPreview();
});
</script>
@endsection

