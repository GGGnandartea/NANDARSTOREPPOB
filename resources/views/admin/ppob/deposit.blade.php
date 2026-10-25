@extends('layouts.admin')

@php
    $activeSection = request('section', 'topup');
@endphp

@section('title', $activeSection === 'alert' ? 'Alert Saldo PPOB' : 'Deposit PPOB')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- SUB-NAVIGASI MENU PPOB KIOSBANK -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-[#111827] p-2 rounded-2xl border border-gray-200 dark:border-white/10 shadow-sm">
        <nav class="flex flex-wrap items-center gap-1.5" aria-label="Menu PPOB">
            <a href="{{ route('admin.ppob.products') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition">
                <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>Katalog PPOB</span>
            </a>
            <a href="{{ route('admin.ppob.deposit', ['section' => 'topup']) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition {{ $activeSection !== 'alert' ? 'font-black bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/20' : 'font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Deposit PPOB</span>
            </a>
            <a href="{{ route('admin.ppob.deposit', ['section' => 'alert']) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm transition {{ $activeSection === 'alert' ? 'font-black bg-gradient-to-r from-rose-600 to-orange-600 text-white shadow-md shadow-rose-500/20' : 'font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5' }}">
                <svg class="w-4 h-4 {{ $activeSection === 'alert' ? 'text-white' : 'text-rose-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <span>Alert Saldo</span>
            </a>
            <a href="{{ route('admin.ppob.settings') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition">
                <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                <span>Akun Kiosbank</span>
            </a>
        </nav>

        <div class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-white/5 border border-gray-200 dark:border-white/10 flex items-center gap-2 text-xs font-bold text-gray-600 dark:text-gray-300">
            <span class="w-2 h-2 rounded-full {{ !empty($depositStatus['server_connected']) ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' }}"></span>
            <span>Mode: <strong class="text-cyan-600 dark:text-cyan-400 uppercase">{{ $kiosbankMode }}</strong></span>
        </div>
    </div>

    <!-- FLASH NOTIFICATIONS -->
    @if(session('success'))
        <div role="status" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-200 text-sm font-bold flex items-center gap-3 shadow-sm">
            <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div role="alert" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-sm font-bold flex items-center gap-3 shadow-sm">
            <span class="w-7 h-7 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('info'))
        <div role="status" class="p-4 rounded-2xl bg-cyan-50 dark:bg-cyan-950/50 border border-cyan-200 dark:border-cyan-800/60 text-cyan-800 dark:text-cyan-200 text-sm font-bold flex items-center gap-3 shadow-sm">
            <span class="w-7 h-7 rounded-xl bg-cyan-500 text-white flex items-center justify-center shrink-0">ℹ</span>
            <span>{{ session('info') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-200 text-sm">
            <ul class="list-disc pl-5 space-y-1 font-semibold">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($activeSection === 'alert')
        <!-- ======================================================== -->
        <!-- HALAMAN KHUSUS: ALERT SALDO DEPOSIT MENIPIS (TERPISAH)   -->
        <!-- ======================================================== -->
        @php
            $lbEnabled = $lowBalanceSettings['enabled'] ?? true;
            $lbThreshold = (int) ($lowBalanceSettings['threshold'] ?? 200000);
            $lbAdminWa = $lowBalanceSettings['admin_whatsapp'] ?? '082317458496';
            $lbLastAlert = $lowBalanceSettings['last_alert_at'] ?? null;
            $lbIsLow = !empty($lowBalanceSettings['is_low']);
        @endphp

        <!-- Ringkasan Saldo Saat Ini vs Batas Alert -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="admin-card p-5 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400 block">Saldo Deposit Saat Ini</span>
                    <span class="text-xl font-black {{ $lbIsLow ? 'text-rose-500' : 'text-emerald-500' }} mt-1 block">
                        Rp {{ number_format($depositStatus['saldo'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $lbIsLow ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' }}">
                    {{ $lbIsLow ? 'Menipis' : 'Aman' }}
                </span>
            </div>

            <div class="admin-card p-5 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400 block">Batas Minimum Alert</span>
                    <span class="text-xl font-black text-gray-900 dark:text-white mt-1 block">
                        Rp {{ number_format($lbThreshold, 0, ',', '.') }}
                    </span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300">
                    Threshold
                </span>
            </div>

            <div class="admin-card p-5 flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-gray-400 block">Nomor WA Penerima</span>
                    <span class="text-base font-mono font-black text-gray-900 dark:text-white mt-1 block">
                        {{ $lbAdminWa }}
                    </span>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $lbEnabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                    {{ $lbEnabled ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
        </div>

        <div class="admin-card p-6 sm:p-8 space-y-6 border-l-4 {{ $lbIsLow ? 'border-l-rose-500' : 'border-l-emerald-500' }}">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-black text-lg sm:text-xl text-gray-900 dark:text-white">
                            Notifikasi WhatsApp Otomatis Saat Saldo Deposit Menipis
                        </h1>
                        @if(!$lbEnabled)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                Nonaktif
                            </span>
                        @elseif($lbIsLow)
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800 animate-pulse">
                                Saldo Di Bawah Batas Minimum!
                            </span>
                        @else
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                ● Monitoring Aktif (Saldo Aman)
                            </span>
                        @endif
                    </div>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Sistem otomatis mengirim peringatan ke WhatsApp Admin setiap kali saldo deposit Kiosbank turun menyentuh atau berada di bawah batas minimum setelah transaksi PPOB maupun saat pengecekan berkala (Cron).
                    </p>
                </div>
                @if($lbLastAlert)
                    <span class="text-[11px] font-semibold text-gray-400 shrink-0">
                        Alert WA terakhir: <strong class="text-gray-600 dark:text-gray-300">{{ $lbLastAlert }}</strong>
                    </span>
                @endif
            </div>

            <form action="{{ route('admin.ppob.deposit.low_balance_alert') }}" method="POST" class="space-y-5" x-data="{ lbThreshold: {{ $lbThreshold }} }">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="lb-threshold" class="block text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Batas Minimum Peringatan Saldo (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-black text-gray-400 text-xs">Rp</span>
                            <input id="lb-threshold"
                                   type="number"
                                   name="threshold"
                                   min="10000"
                                   step="1000"
                                   x-model="lbThreshold"
                                   required
                                   class="w-full pl-10 pr-4 py-3 border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-black text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] font-bold text-gray-400">Preset:</span>
                            @foreach([100000 => '100rb', 200000 => '200rb', 500000 => '500rb', 1000000 => '1 Juta', 2000000 => '2 Juta'] as $tVal => $tLbl)
                                <button type="button"
                                        @click="lbThreshold = {{ $tVal }}"
                                        class="px-2.5 py-1 rounded-lg text-[10px] font-bold border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 hover:border-emerald-500 text-gray-600 dark:text-gray-300 cursor-pointer transition">
                                    {{ $tLbl }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label for="lb-admin-wa" class="block text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Nomor WhatsApp Admin Penerima Alert
                        </label>
                        <input id="lb-admin-wa"
                               type="text"
                               name="admin_whatsapp"
                               value="{{ $lbAdminWa }}"
                               required
                               placeholder="082317458496"
                               class="w-full px-3.5 py-3 border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono font-bold text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500">
                        <p class="text-[11px] text-gray-400 mt-1.5">Dilengkapi proteksi anti-spam (maksimal 1x alert tiap 4 jam).</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="enabled" value="1" {{ $lbEnabled ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500">
                        <span class="text-xs sm:text-sm font-extrabold text-gray-700 dark:text-gray-200">Aktifkan Peringatan WhatsApp Otomatis</span>
                    </label>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <button type="submit" name="action" value="test" class="px-4 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-white/10 dark:hover:bg-white/15 text-gray-800 dark:text-white font-bold text-xs transition cursor-pointer" title="Simpan & Kirim Pesan Uji Coba Alert ke WhatsApp Admin">
                            Kirim Tes WA
                        </button>
                        <button type="submit" name="action" value="save" class="px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-md shadow-emerald-500/20 transition cursor-pointer">
                            Simpan Pengaturan Alert
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <!-- ======================================================== -->
        <!-- HALAMAN KHUSUS: DEPOSIT & SALDO KIOSBANK (TERPISAH)      -->
        <!-- ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- KARTU UTAMA SALDO DEPOSIT (7 KOLOM) -->
            <div class="lg:col-span-7 rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-slate-900 via-[#0f172a] to-slate-900 text-white border border-white/10 shadow-xl relative overflow-hidden flex flex-col justify-between">
                <div class="absolute -right-12 -top-12 w-52 h-52 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -left-12 -bottom-12 w-52 h-52 rounded-full bg-cyan-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative z-10 space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <span class="text-[11px] font-extrabold uppercase tracking-widest text-cyan-400 block">Dompet Utama Mitra Biller</span>
                            <h1 class="text-xl sm:text-2xl font-black tracking-tight mt-0.5">Saldo Deposit Kiosbank</h1>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $isDev ? 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30' : 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30' }}">
                            <span class="w-2 h-2 rounded-full {{ $isDev ? 'bg-cyan-400' : 'bg-emerald-400' }} animate-pulse"></span>
                            {{ ucfirst($kiosbankMode) }}
                        </span>
                    </div>

                    @if($depositStatus['ok'])
                        <div class="pt-2">
                            <p class="text-3xl sm:text-5xl font-black text-emerald-400 tracking-tight">
                                Rp {{ number_format($depositStatus['saldo'] ?? 0, 0, ',', '.') }}
                            </p>
                            <p class="text-xs sm:text-sm text-slate-300 mt-2 leading-relaxed">
                                @if($isDev)
                                    Saldo aktif yang digunakan untuk memproses transaksi PPOB dan bertambah otomatis saat tiket top up dikonfirmasi.
                                @else
                                    Saldo deposit resmi Mitra di server Production Kiosbank (diperbarui via endpoint <code class="text-cyan-300">/Services/getCurrentDeposit</code>).
                                @endif
                            </p>
                        </div>

                        @if($isDev && !empty($depositStatus['server_connected']))
                            <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                                    <span>Respon Server Demo Kiosbank (<code class="text-cyan-300">getCurrentDeposit</code>):</span>
                                </div>
                                <span class="font-mono font-black text-cyan-300">
                                    Rp {{ number_format($depositStatus['server_saldo'] ?? 50000000, 0, ',', '.') }} (RC: {{ $depositStatus['rc'] ?? '00' }})
                                </span>
                            </div>
                        @endif
                    @else
                        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-sm">
                            <p class="font-bold">Saldo belum dapat dibaca dari server Kiosbank</p>
                            <p class="text-xs mt-1 text-amber-300/90">{{ $depositStatus['message'] ?? 'Koneksi belum tersedia.' }}</p>
                        </div>
                    @endif
                </div>

                <div class="relative z-10 pt-6 mt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <form action="{{ route('admin.ppob.deposit.check_live') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-500 hover:to-teal-500 text-white font-black text-xs sm:text-sm shadow-lg shadow-cyan-950/50 transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span>Cek Saldo Realtime</span>
                            </button>
                        </form>
                        <form action="{{ route('admin.orders.sync_ppob_pending') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-white font-bold text-xs sm:text-sm transition cursor-pointer" title="Cek status seluruh transaksi PPOB yang berstatus Pending ke /Services/Check-Status Kiosbank">
                                <span>Cek Status Transaksi Pending</span>
                                @if(($ppobStats['pending_ppob_orders'] ?? 0) > 0)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500 text-slate-950">
                                        {{ $ppobStats['pending_ppob_orders'] }}
                                    </span>
                                @endif
                            </button>
                        </form>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">
                        Pengecekan terakhir: {{ $depositStatus['checked_at'] ?? now()->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' }}
                    </span>
                </div>
            </div>

            <!-- KARTU TELEMETRI KONEKSI API KIOSBANK (5 KOLOM) -->
            <div class="lg:col-span-5 admin-card p-6 flex flex-col justify-between space-y-4">
                <div>
                    <div class="flex items-center justify-between gap-2 pb-3 border-b border-gray-100 dark:border-white/10">
                        <div>
                            <h2 class="text-base font-black text-gray-900 dark:text-white">Status Koneksi API Kiosbank</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Parameter autentikasi sesi yang terhubung ke Kiosbank</p>
                        </div>
                        @if(!empty($depositStatus['server_connected']))
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                ● ONLINE
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-300 dark:border-amber-800">
                                ● SANDBOX
                            </span>
                        @endif
                    </div>

                    <dl class="mt-4 space-y-2.5 text-xs">
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-white/5">
                            <dt class="text-gray-500 dark:text-gray-400 font-medium">Merchant ID &amp; Mitra</dt>
                            <dd class="font-mono font-bold text-gray-900 dark:text-white">{{ $depositStatus['merchant_id'] ?? '-' }} ({{ $depositStatus['mitra'] ?? 'DJI' }})</dd>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-white/5">
                            <dt class="text-gray-500 dark:text-gray-400 font-medium">Account ID / Counter</dt>
                            <dd class="font-mono font-bold text-gray-900 dark:text-white">{{ $depositStatus['account_id'] ?? '-' }} / #{{ $depositStatus['counter_id'] ?? '1' }}</dd>
                        </div>
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-white/5">
                            <dt class="text-gray-500 dark:text-gray-400 font-medium">Session ID Harian</dt>
                            <dd class="font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                @if(!empty($depositStatus['session_id']))
                                    {{ substr($depositStatus['session_id'], 0, 14) }}...
                                @else
                                    Belum Aktif
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <dt class="text-gray-500 dark:text-gray-400 font-medium">Endpoint Deposit</dt>
                            <dd class="font-mono text-[11px] text-gray-700 dark:text-gray-300 truncate max-w-[220px]" title="{{ $depositStatus['endpoint'] ?? '' }}">
                                /Services/getCurrentDeposit
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/5">
                        <span class="text-[10px] font-bold uppercase text-gray-400 block">Total Top Up Masuk</span>
                        <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 mt-0.5 block">
                            Rp {{ number_format($ppobStats['total_topup_success'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/5">
                        <span class="text-[10px] font-bold uppercase text-gray-400 block">Modal PPOB Terpakai</span>
                        <span class="text-sm font-black text-orange-500 mt-0.5 block">
                            Rp {{ number_format($ppobStats['total_ppob_modal_used'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @if($isDev)
            <!-- FORM PENGAJUAN TIKET DEPOSIT KIOSBANK (REALISTIC MITRA FLOW) -->
            <div class="admin-card p-6 sm:p-8 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="font-black text-lg sm:text-xl text-gray-900 dark:text-white">Buat Tiket Top Up Deposit Kiosbank</h2>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800">
                                Simulasi Development
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Simulasikan alur pengajuan tiket penambahan deposit Mitra Kiosbank. Setiap transaksi PPOB berhasil akan memotong saldo deposit aktif secara otomatis.
                        </p>
                    </div>
                </div>

                <form action="{{ route('admin.ppob.deposit.create') }}" method="POST" class="space-y-6" x-data="{ amount: {{ (int) old('amount', 500000) }}, channel: 'bca', autoApprove: true }">
                    @csrf
                    <input type="hidden" name="payment_method" value="developer_mode">

                    <!-- 1. PILIH CEPAT NOMINAL TOP UP -->
                    <div class="space-y-2.5">
                        <label class="block text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            1. Pilih Cepat Nominal Deposit
                        </label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                            @foreach([100000 => 'Rp 100.000', 250000 => 'Rp 250.000', 500000 => 'Rp 500.000', 1000000 => 'Rp 1.000.000', 2500000 => 'Rp 2.500.000', 5000000 => 'Rp 5.000.000'] as $presetVal => $presetLabel)
                                <button type="button"
                                        @click="amount = {{ $presetVal }}"
                                        :class="Number(amount) === {{ $presetVal }}
                                            ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 ring-2 ring-cyan-500/20'
                                            : 'border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-700 dark:text-gray-300 hover:border-cyan-500/40'"
                                        class="py-3 px-3 rounded-xl border font-black text-xs sm:text-sm transition text-center cursor-pointer">
                                    {{ $presetLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- 2. INPUT NOMINAL KUSTOM & KANAL BANK TUJUAN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="deposit-amount" class="block text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                2. Nominal Deposit (Rp)
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center font-black text-gray-400">Rp</span>
                                <input id="deposit-amount"
                                       type="number"
                                       name="amount"
                                       min="10000"
                                       step="1"
                                       x-model="amount"
                                       required
                                       class="w-full pl-11 pr-4 py-3 border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-black text-base focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5">Minimal pengajuan tiket deposit Rp 10.000.</p>
                        </div>

                        <div>
                            <label for="bank-channel" class="block text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2">
                                3. Kanal Bank Tiket Deposit Kiosbank
                            </label>
                            <select id="bank-channel"
                                    name="bank_channel"
                                    x-model="channel"
                                    class="w-full px-4 py-3 border border-gray-200 dark:border-white/10 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-bold text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 focus:border-cyan-500">
                                <option value="bca">Bank BCA — PT Dwimitra Jaya (Kiosbank Corporate)</option>
                                <option value="mandiri">Bank Mandiri — PT Dwimitra Jaya (Kiosbank Corporate)</option>
                                <option value="bri">Bank BRI — PT Dwimitra Jaya (Kiosbank Corporate)</option>
                                <option value="bni">Bank BNI — PT Dwimitra Jaya (Kiosbank Corporate)</option>
                                <option value="instant">Instant Sandbox Credit (Tanpa Kode Unik)</option>
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1.5">Menghasilkan tiket deposit dengan simulasi kode unik 3 digit seperti sistem tiket Kiosbank asli.</p>
                        </div>
                    </div>

                    <!-- 3. OPSI AUTO-APPROVE & SUBMIT -->
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200/80 dark:border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <label class="flex items-start sm:items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox"
                                   name="auto_approve"
                                   value="1"
                                   x-model="autoApprove"
                                   class="mt-0.5 sm:mt-0 w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white block">
                                    Langsung Cairkan ke Saldo Deposit Aktif
                                </span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400 block">
                                    Jika dicentang, saldo deposit langsung bertambah otomatis tanpa perlu klik konfirmasi manual di tabel bawah.
                                </span>
                            </div>
                        </label>

                        <button type="submit" class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs sm:text-sm shadow-lg shadow-emerald-500/20 shrink-0 transition cursor-pointer">
                            Buat Tiket Simulasi
                        </button>
                    </div>
                </form>
            </div>

            <!-- TABEL RIWAYAT TIKET DEPOSIT -->
            <div class="admin-card p-6 sm:p-8 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-black text-lg text-gray-900 dark:text-white">Riwayat Tiket Deposit Kiosbank</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Daftar seluruh pengajuan tiket penambahan modal deposit PPOB</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-full bg-gray-100 dark:bg-white/5 text-gray-600 dark:text-gray-300">
                        Total: {{ $deposits->total() }} Tiket
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left admin-table">
                        <thead>
                            <tr>
                                <th>No. Tiket / Waktu</th>
                                <th>Kanal Deposit</th>
                                <th>Nominal Deposit</th>
                                <th>Total Transfer (Kode Unik)</th>
                                <th>Status</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="dark:text-gray-200">
                            @forelse($deposits as $deposit)
                                <tr>
                                    <td>
                                        <div class="font-mono font-bold text-xs text-gray-900 dark:text-white">{{ $deposit->invoice_number }}</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $deposit->created_at ? $deposit->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB' : '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="font-bold text-xs text-gray-800 dark:text-gray-200">{{ $deposit->bank_account_name ?: 'Virtual Deposit Kiosbank' }}</div>
                                        @if($deposit->notes)
                                            <div class="text-[11px] text-gray-400 truncate max-w-[240px]">{{ $deposit->notes }}</div>
                                        @endif
                                    </td>
                                    <td class="font-black text-gray-900 dark:text-white">
                                        Rp {{ number_format($deposit->amount, 0, ',', '.') }}
                                    </td>
                                    <td>
                                        <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400">
                                            Rp {{ number_format($deposit->total_payment ?: $deposit->amount, 0, ',', '.') }}
                                        </span>
                                        @if(($deposit->unique_code ?? 0) > 0)
                                            <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded bg-cyan-50 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 font-bold">
                                                +{{ $deposit->unique_code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($deposit->status === 'success')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                ✓ Berhasil Masuk Saldo
                                            </span>
                                        @elseif($deposit->status === 'pending')
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                Menunggu Pencairan
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-black bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                {{ strtoupper($deposit->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @if($deposit->status === 'pending')
                                            <form action="{{ route('admin.ppob.deposit.simulate', $deposit->id) }}" method="POST" class="inline-block">
                                                @csrf
                                                <button type="submit" class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-sm transition cursor-pointer">
                                                    Simulasikan Berhasil
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">Selesai</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        Belum ada riwayat tiket deposit. Buat tiket di atas untuk menambah saldo deposit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $deposits->links() }}
            </div>
        @else
            <!-- PANDUAN RESMI DEPOSIT PRODUCTION KIOSBANK -->
            <div class="admin-card p-6 sm:p-8 space-y-6">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
                    <div>
                        <h2 class="font-black text-lg sm:text-xl text-gray-900 dark:text-white">Deposit Production</h2>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Prosedur resmi pengisian saldo deposit Mitra Kiosbank pada mode transaksi nyata (Production).
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                        Sinkronisasi Otomatis via API
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200/70 dark:border-white/10 space-y-2">
                        <span class="w-7 h-7 rounded-lg bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 font-black text-xs flex items-center justify-center">1</span>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Ambil Tiket Deposit Resmi</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            Buka aplikasi/portal Mitra Kiosbank atau hubungi Helpdesk Kiosbank dengan menyertakan Merchant ID <strong class="text-gray-800 dark:text-gray-200">{{ $depositStatus['merchant_id'] ?? '-' }}</strong> &amp; Account ID <strong class="text-gray-800 dark:text-gray-200">{{ $depositStatus['account_id'] ?? '-' }}</strong>.
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200/70 dark:border-white/10 space-y-2">
                        <span class="w-7 h-7 rounded-lg bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 font-black text-xs flex items-center justify-center">2</span>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Transfer Sesuai Nominal Tiket</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            Transfer ke rekening Bank Corporate Kiosbank sesuai angka unik tiket agar sistem Kiosbank memverifikasi mutasi Anda secara otomatis.
                        </p>
                    </div>
                    <div class="p-4 rounded-2xl bg-gray-50 dark:bg-white/5 border border-gray-200/70 dark:border-white/10 space-y-2">
                        <span class="w-7 h-7 rounded-lg bg-cyan-500/15 text-cyan-600 dark:text-cyan-400 font-black text-xs flex items-center justify-center">3</span>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Cek Saldo Realtime</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                            Setelah deposit masuk, klik tombol <strong>Cek Saldo Realtime</strong> di atas untuk membaca saldo terbaru langsung dari server Kiosbank (<code class="text-cyan-500">/Services/getCurrentDeposit</code>).
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
