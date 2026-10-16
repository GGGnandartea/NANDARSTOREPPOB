@extends('layouts.admin')
@section('title', 'Konfigurasi Payment Gateway')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <!-- Header Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Payment Gateway Tripay</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-blue text-white shadow-sm shadow-blue-500/20">
                    Kredensial PG
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Konfigurasi penerimaan pembayaran otomatis via QRIS, E-Wallet, dan Virtual Account.</p>
        </div>
    </div>

    <div class="admin-card overflow-hidden">
        <div class="p-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-black/20 flex items-center gap-3.5">
            <div class="w-10 h-10 rounded-xl gradient-blue text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                💳
            </div>
            <div>
                <h3 class="font-black text-base text-gray-900 dark:text-white">Tripay Merchant Gateway</h3>
                <p class="text-[11px] font-semibold text-gray-400">Atur Environment, Kode Merchant, dan Kunci API</p>
            </div>
        </div>
        
        <form action="{{ route('admin.settings.payment.save') }}" method="POST" class="p-6 sm:p-8 space-y-5">
            @csrf
            
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Environment Mode</label>
                <select name="tripay_env" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    <option value="sandbox" {{ $tripayEnv == 'sandbox' ? 'selected' : '' }}>Sandbox (Mode Percobaan / Testing)</option>
                    <option value="production" {{ $tripayEnv == 'production' ? 'selected' : '' }}>Production (Live / Siap Terima Pembayaran Nyata)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Kode Merchant</label>
                <input type="text" name="tripay_merchant" value="{{ $tripayMerchant }}" placeholder="Contoh: T12345" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">API Key</label>
                <input type="text" name="tripay_api_key" value="{{ $tripayApiKey }}" placeholder="DEV-..." class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Private Key</label>
                <input type="text" name="tripay_private_key" value="{{ $tripayPrivateKey }}" placeholder="Private key Tripay..." class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-white/5">
                <button type="submit" class="gradient-blue text-white font-black py-3.5 px-8 rounded-xl shadow-lg shadow-blue-500/25 transition-all hover:opacity-90 cursor-pointer text-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan Konfigurasi Payment</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection