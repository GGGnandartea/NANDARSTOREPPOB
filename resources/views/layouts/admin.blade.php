<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Panel') - NANDAR STORE 2.0</title>
    @php
        $siteFavicon = \App\Models\Setting::where('key', 'store_favicon')->value('value') ?: (\App\Models\Setting::where('key', 'store_logo')->value('value') ?: null);
    @endphp
    @if($siteFavicon)
        <link rel="icon" href="{{ $siteFavicon }}">
    @endif
    
    <!-- 100% Local CSS - Zero Cloud Latency -->
    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $appCss = $manifest['resources/css/app.css']['file'] ?? null;
    @endphp
    @if($appCss && file_exists(public_path('build/' . $appCss)))
        <link rel="stylesheet" href="{{ asset('build/' . $appCss) }}">
    @else
        @vite(['resources/css/app.css'])
    @endif
    <link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}">

    <!-- 100% Local Vendor Scripts - No Cloud CDN -->
    <script defer src="{{ asset('vendor/alpine.min.js') }}"></script>
    <script src="{{ asset('vendor/chart.umd.min.js') }}"></script>

    <script>
        if (localStorage.getItem('admin_theme') === 'dark' || (!('admin_theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="admin-body font-sans antialiased overflow-hidden" 
      x-data="{ 
          sidebarOpen: window.innerWidth >= 1024, 
          mobileMenuOpen: false 
      }" 
      @resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false">

    <!-- Mobile Drawer Backdrop Overlay -->
    <div 
        x-show="mobileMenuOpen" 
        @click="mobileMenuOpen = false" 
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="admin-backdrop fixed inset-0 bg-black/60 backdrop-blur-sm z-40"
        style="display: none;"
    ></div>

    <div class="flex h-screen w-full relative">
        
        <!-- SIDEBAR (DRAWER ON MOBILE, EXPANDABLE ON DESKTOP) -->
        <aside 
            class="admin-sidebar bg-white dark:bg-[#0f172a] border-r border-gray-200 dark:border-white/5 flex flex-col"
            :class="{
                'sidebar-expanded': sidebarOpen,
                'sidebar-collapsed': !sidebarOpen,
                'mobile-open': mobileMenuOpen,
                'mobile-closed': !mobileMenuOpen
            }"
        >
            
            <!-- Sidebar Header & Logo -->
            @php
                $adminStoreName = \App\Models\Setting::where('key', 'store_name')->value('value') ?: 'NandarStore';
                $adminStoreLogo = \App\Models\Setting::where('key', 'store_logo')->value('value') ?: null;
            @endphp
            <div class="h-16 flex items-center justify-between border-b border-gray-200 dark:border-white/5 px-4 shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 overflow-hidden group cursor-pointer" @click="if (window.innerWidth < 1024) mobileMenuOpen = false">
                    @if($adminStoreLogo)
                        <div class="w-9 h-9 min-w-[2.25rem] rounded-xl bg-white dark:bg-slate-800 border border-gray-200 dark:border-white/10 p-1 flex items-center justify-center shadow-md group-hover:scale-105 transition-transform overflow-hidden">
                            <img src="{{ $adminStoreLogo }}" alt="{{ $adminStoreName }}" class="w-full h-full object-contain rounded-lg">
                        </div>
                    @else
                        <div class="w-9 h-9 min-w-[2.25rem] rounded-xl gradient-orange text-white flex items-center justify-center font-black shadow-lg shadow-orange-500/25 group-hover:scale-105 transition-transform">
                            NS
                        </div>
                    @endif
                    <div :class="(sidebarOpen || window.innerWidth < 1024) ? 'flex' : 'hidden'" class="flex-col min-w-0">
                        <span class="text-base font-black tracking-tight text-gray-900 dark:text-white whitespace-nowrap flex items-center gap-1.5">
                            AdminPanel
                            <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-950 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">v2.0</span>
                        </span>
                        <span class="text-[10px] font-semibold text-gray-400 truncate uppercase">{{ $adminStoreName }}</span>
                    </div>
                </a>

                <!-- Mobile Close Button (✕) -->
                <button type="button" @click="mobileMenuOpen = false" class="admin-mobile-only p-2 text-gray-400 hover:text-gray-600 dark:hover:text-white rounded-xl cursor-pointer" title="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Menu Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 custom-scrollbar">
                
                <!-- Dashboard -->
                <a href="{{ route('admin.dashboard') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Dashboard Analisa</span>
                </a>

                <!-- Transaksi Section -->
                <div class="pt-3 pb-1">
                    <p :class="(sidebarOpen || window.innerWidth < 1024) ? 'block' : 'hidden'" class="px-3 text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Transaksi</p>
                    <div :class="(!sidebarOpen && window.innerWidth >= 1024) ? 'block' : 'hidden'" class="w-full h-px bg-gray-200 dark:bg-white/5 my-2"></div>
                </div>

                <a href="{{ route('admin.orders') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Riwayat Pesanan</span>
                </a>

                <a href="{{ route('admin.reports') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Grafik Laporan
                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">Excel</span>
                    </span>
                </a>

                <!-- Katalog Section -->
                <div class="pt-3 pb-1">
                    <p :class="(sidebarOpen || window.innerWidth < 1024) ? 'block' : 'hidden'" class="px-3 text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Katalog & Produk</p>
                    <div :class="(!sidebarOpen && window.innerWidth >= 1024) ? 'block' : 'hidden'" class="w-full h-px bg-gray-200 dark:bg-white/5 my-2"></div>
                </div>

                <a href="{{ route('admin.products.api') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.products.api') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Markup API</span>
                </a>

                <a href="{{ route('admin.ppob.products') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.ppob.products*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Produk PPOB
                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-cyan-100 dark:bg-cyan-950 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/40">Kiosbank</span>
                    </span>
                </a>

                <a href="{{ route('admin.products.local') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.products.local*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Produk Lokal Saya</span>
                </a>

                <a href="{{ route('admin.banners') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.banners*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Kelola Banner</span>
                </a>

                <!-- Manajemen Section -->
                <div class="pt-3 pb-1">
                    <p :class="(sidebarOpen || window.innerWidth < 1024) ? 'block' : 'hidden'" class="px-3 text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Manajemen</p>
                    <div :class="(!sidebarOpen && window.innerWidth >= 1024) ? 'block' : 'hidden'" class="w-full h-px bg-gray-200 dark:bg-white/5 my-2"></div>
                </div>

                <a href="{{ route('admin.resellers') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.resellers') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Daftar Reseller</span>
                </a>

                <a href="{{ route('admin.resellers.settings') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.resellers.settings*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Pengaturan VIP / Promo
                        @php
                            $isFreeVipSidebar = \App\Models\Setting::where('key', 'reseller_registration_free')->value('value') == '1';
                        @endphp
                        @if($isFreeVipSidebar)
                            <span class="text-[9px] font-black uppercase px-1.5 py-0.2 rounded-full bg-emerald-500 text-white animate-pulse">
                                GRATIS
                            </span>
                        @endif
                    </span>
                </a>

                <a href="{{ route('admin.vouchers') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.vouchers') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Voucher Diskon</span>
                </a>

                <a href="{{ route('admin.wa_verifications') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.wa_verifications') ? 'active-wa' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Verifikasi WhatsApp
                        @php
                            $pendingWaSidebar = \App\Models\User::whereNotNull('wa_verification_code')->where('is_whatsapp_verified', false)->count();
                        @endphp
                        @if($pendingWaSidebar > 0)
                            <span class="text-[10px] font-black px-1.5 py-0.2 rounded-full bg-emerald-500 text-white animate-pulse">
                                {{ $pendingWaSidebar }}
                            </span>
                        @endif
                    </span>
                </a>

                <!-- Sistem Section -->
                <div class="pt-3 pb-1">
                    <p :class="(sidebarOpen || window.innerWidth < 1024) ? 'block' : 'hidden'" class="px-3 text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Sistem</p>
                    <div :class="(!sidebarOpen && window.innerWidth >= 1024) ? 'block' : 'hidden'" class="w-full h-px bg-gray-200 dark:bg-white/5 my-2"></div>
                </div>

                <a href="{{ route('admin.branding') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.branding*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Branding
                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-orange-100 dark:bg-orange-950/80 text-orange-600 dark:text-orange-400 border border-orange-200 dark:border-orange-800/40">Logo & Web</span>
                    </span>
                </a>

                <a href="{{ route('admin.settings') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Pengaturan API</span>
                </a>

                <a href="{{ route('admin.ppob.deposit') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.ppob.deposit*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold flex items-center justify-between flex-1">
                        Top Up Kiosbank
                        <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40">Deposit</span>
                    </span>
                </a>
                
                <a href="{{ route('admin.terms') }}" 
                   @click="if (window.innerWidth < 1024) mobileMenuOpen = false"
                   class="sidebar-link {{ request()->routeIs('admin.terms') ? 'active' : '' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap font-bold">Syarat & Ketentuan</span>
                </a>
                
            </nav>
            
            <!-- Bottom Logout Action -->
            <div class="p-3 border-t border-gray-200 dark:border-white/5 bg-gray-50/50 dark:bg-black/20">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-colors font-bold text-sm cursor-pointer">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span :class="(sidebarOpen || window.innerWidth < 1024) ? 'inline' : 'hidden'" class="whitespace-nowrap">Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- KONTEN AREA KANAN -->
        <main class="flex-1 flex flex-col min-w-0 bg-transparent h-screen overflow-hidden">
            
            <!-- TOP NAVBAR -->
            <header class="h-16 bg-white/80 dark:bg-[#0f172a]/80 backdrop-blur-md border-b border-gray-200 dark:border-white/5 flex items-center justify-between px-3 sm:px-6 lg:px-8 shrink-0 z-10 shadow-sm">
                <div class="flex items-center gap-2.5 sm:gap-4 min-w-0">
                    <!-- Hamburger on Mobile -->
                    <button type="button" @click="mobileMenuOpen = !mobileMenuOpen" class="admin-mobile-only p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 focus:outline-none transition-colors cursor-pointer" title="Buka Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <!-- Hamburger on Desktop -->
                    <button type="button" @click="sidebarOpen = !sidebarOpen" class="admin-desktop-only p-2 rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5 focus:outline-none transition-colors cursor-pointer" title="Ciutkan Sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                    </button>
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-lg font-black text-gray-900 dark:text-white leading-tight truncate">@yield('title')</h2>
                        <p class="text-[10px] sm:text-[11px] font-semibold text-gray-400 hidden sm:block">Panel Kontrol &amp; Manajemen Toko</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                    <!-- Status Server Online (Desktop) -->
                    <div class="hidden md:flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Sistem Online
                    </div>

                    <!-- Theme Toggle -->
                    <button onclick="toggleAdminTheme()" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300 hover:ring-2 ring-orange-500/50 transition-all flex items-center justify-center cursor-pointer shadow-sm text-xs sm:text-sm">
                        <span id="admin-theme-icon">🌙</span>
                    </button>

                    <!-- Lihat Web Button (Hidden on Mobile) -->
                    <a href="{{ route('home') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-black text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/40 border border-orange-200 dark:border-orange-800/50 hover:bg-orange-100 transition-colors cursor-pointer">
                        <span>Lihat Web</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    </a>
                    
                    <!-- User Avatar -->
                    <div class="flex items-center gap-2 pl-1.5 sm:pl-2 border-l border-gray-200 dark:border-white/10">
                        <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-xs sm:text-sm shadow-md shadow-orange-500/20 cursor-pointer">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden xl:block">
                            <p class="text-xs font-black text-gray-800 dark:text-gray-200 leading-none">{{ Auth::user()->name ?? 'Admin' }}</p>
                            <p class="text-[10px] font-semibold text-gray-400 mt-0.5">Administrator</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- KONTEN SCROLLABLE -->
            <div class="flex-1 overflow-y-auto p-3 sm:p-6 lg:p-8 admin-content-scroll custom-scrollbar">
                @yield('content')
            </div>

        </main>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION BAR (KHUSUS LAYAR HP) -->
    <nav class="admin-bottom-nav fixed bottom-0 left-0 right-0 z-30 bg-white/95 dark:bg-[#0f172a]/95 backdrop-blur-xl border-t border-gray-200 dark:border-white/10 px-2 py-1.5 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] items-center justify-around">
        
        <!-- 1. Dashboard -->
        <a href="{{ route('admin.dashboard') }}" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.dashboard') ? 'text-orange-500 font-extrabold' : 'text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            <span class="text-[10px]">Dashboard</span>
        </a>

        <!-- 2. Pesanan -->
        <a href="{{ route('admin.orders') }}" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition-colors relative {{ request()->routeIs('admin.orders*') ? 'text-orange-500 font-extrabold' : 'text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <div class="relative">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                @php
                    $pendingOrdersBottom = \App\Models\Order::whereIn('order_status', ['pending', 'processing'])->count();
                @endphp
                @if($pendingOrdersBottom > 0)
                    <span class="absolute -top-1 -right-2 w-4 h-4 rounded-full bg-orange-500 text-white text-[9px] font-black flex items-center justify-center shadow-sm">
                        {{ $pendingOrdersBottom > 9 ? '9+' : $pendingOrdersBottom }}
                    </span>
                @endif
            </div>
            <span class="text-[10px]">Pesanan</span>
        </a>

        <!-- 3. Produk Lokal -->
        <a href="{{ route('admin.products.local') }}" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.products.local*') ? 'text-orange-500 font-extrabold' : 'text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <span class="text-[10px]">Katalog</span>
        </a>

        <!-- 4. Laporan -->
        <a href="{{ route('admin.reports') }}" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl transition-colors {{ request()->routeIs('admin.reports*') ? 'text-emerald-500 font-extrabold' : 'text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span class="text-[10px]">Laporan</span>
        </a>

        <!-- 5. Menu Drawer Trigger -->
        <button type="button" @click="mobileMenuOpen = true" class="flex flex-col items-center justify-center py-1 px-2.5 rounded-xl text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors cursor-pointer" title="Semua Menu">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            <span class="text-[10px]">Menu</span>
        </button>
    </nav>

    <script>
        const isDark = document.documentElement.classList.contains('dark');
        document.getElementById('admin-theme-icon').innerText = isDark ? '☀️' : '🌙';

        function toggleAdminTheme() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            const currentlyDark = html.classList.contains('dark');
            document.getElementById('admin-theme-icon').innerText = currentlyDark ? '☀️' : '🌙';
            localStorage.setItem('admin_theme', currentlyDark ? 'dark' : 'light');

            // Trigger custom event agar chart dapat menyesuaikan warna tema jika ada
            window.dispatchEvent(new CustomEvent('themeChanged', { detail: { isDark: currentlyDark } }));
        }

        // Global Elegant Toast Notification System (Zero Browser Alert)
        window.showAdminToast = function(message, type = 'success') {
            const container = document.getElementById('admin-toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-xs font-bold transition-all duration-300 transform translate-y-2 opacity-0 ${
                type === 'success' 
                    ? 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-600/20' 
                    : (type === 'error' ? 'bg-rose-600 text-white border-rose-500 shadow-rose-600/20' : 'bg-gray-900 text-white border-gray-700')
            }`;

            const icon = type === 'success' ? '✓' : (type === 'error' ? '✕' : 'ℹ');
            toast.innerHTML = `<span class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center text-[10px] font-black shrink-0">${icon}</span><span>${message}</span>`;
            container.appendChild(toast);

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        };

        // ==========================================================
        // BUTTERY SMOOTH INERTIAL SCROLL ENGINE FOR ADMIN PANEL
        // Memberikan sensasi scroll super halus & dinamis persis seperti
        // dashboard storefront utama (Lenis/macOS-like momentum easing)
        // ==========================================================
        (function() {
            function attachSmoothScroll(el) {
                if (!el || el._smoothWheelAttached) return;
                el._smoothWheelAttached = true;

                let targetY = el.scrollTop;
                let currentY = el.scrollTop;
                let isAnimating = false;
                let rafId = null;
                let idleTimer = null;

                // Sinkronisasi posisi jika pengguna scroll dengan drag scrollbar atau keyboard
                el.addEventListener('scroll', function() {
                    if (!isAnimating) {
                        targetY = el.scrollTop;
                        currentY = el.scrollTop;
                    }
                }, { passive: true });

                el.addEventListener('touchstart', function() {
                    if (rafId) {
                        cancelAnimationFrame(rafId);
                        rafId = null;
                    }
                    isAnimating = false;
                    el.classList.remove('is-wheeling');
                    targetY = el.scrollTop;
                    currentY = el.scrollTop;
                }, { passive: true });

                el.addEventListener('wheel', function(e) {
                    // Abaikan jika zoom (Ctrl + wheel) atau scroll horizontal murni
                    if (e.ctrlKey || e.altKey) return;
                    if (Math.abs(e.deltaY) < 1) return;

                    const maxScroll = el.scrollHeight - el.clientHeight;
                    if (maxScroll <= 0) return;

                    // Deteksi wheel fisik (discrete mouse notches) vs precision trackpad
                    const isDiscreteWheel = e.deltaMode !== 0 || Math.abs(e.deltaY) >= 20 || Number.isInteger(e.deltaY);

                    if (!isDiscreteWheel) {
                        // Trackpad sudah memiliki akselerasi hardware bawaan
                        targetY = el.scrollTop;
                        currentY = el.scrollTop;
                        return;
                    }

                    // Hentikan lompatan kaku bawaan browser
                    e.preventDefault();

                    // Nonaktifkan sementara transition CSS agar animasi frame lerp sinkron 60fps
                    el.classList.add('is-wheeling');

                    // Hitung langkah scroll dengan multiplier natural
                    const delta = e.deltaMode === 1 ? e.deltaY * 34 : e.deltaY;
                    targetY = Math.max(0, Math.min(targetY + delta, maxScroll));

                    if (!isAnimating) {
                        isAnimating = true;
                        currentY = el.scrollTop;
                        rafId = requestAnimationFrame(render);
                    }

                    clearTimeout(idleTimer);
                    idleTimer = setTimeout(() => {
                        targetY = el.scrollTop;
                        currentY = el.scrollTop;
                        isAnimating = false;
                        el.classList.remove('is-wheeling');
                    }, 350);
                }, { passive: false });

                function render() {
                    if (!isAnimating) return;

                    const diff = targetY - currentY;
                    
                    // Easing lerp 0.13: Luncuran mulus, responsif, tanpa lag/keterlambatan
                    currentY += diff * 0.13;

                    el.scrollTop = Math.round(currentY * 10) / 10;

                    if (Math.abs(diff) > 0.4) {
                        rafId = requestAnimationFrame(render);
                    } else {
                        el.scrollTop = targetY;
                        currentY = targetY;
                        isAnimating = false;
                        rafId = null;
                        el.classList.remove('is-wheeling');
                    }
                }
            }

            function initAllScrolls() {
                document.querySelectorAll('.admin-content-scroll, .admin-sidebar nav').forEach(attachSmoothScroll);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAllScrolls);
            } else {
                initAllScrolls();
            }
        })();
    </script>

    <!-- GLOBAL ADMIN TOAST CONTAINER -->
    <div id="admin-toast-container" class="fixed top-5 right-5 z-50 flex flex-col gap-2 pointer-events-none"></div>
</body>
</html>