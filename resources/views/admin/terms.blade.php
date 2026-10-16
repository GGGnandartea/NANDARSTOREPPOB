@extends('layouts.admin')
@section('title', 'Syarat & Ketentuan')

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
                <span>Syarat & Ketentuan Layanan</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                    Kebijakan Toko
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Kebijakan garansi, pengembalian dana, dan aturan pembelian yang ditampilkan kepada pelanggan.</p>
        </div>
    </div>

    <!-- Card Editor -->
    <div class="admin-card p-6 sm:p-8 space-y-5">
        <form action="{{ route('admin.terms.save') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-2">Teks Kebijakan & Syarat Pembelian</label>
                <textarea 
                    name="terms" 
                    rows="14" 
                    placeholder="Tuliskan aturan garansi, pengiriman akun, dan syarat pemesanan di sini..." 
                    class="w-full p-5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-2xl text-xs sm:text-sm font-medium dark:text-white focus:ring-2 focus:ring-orange-500 outline-none resize-y leading-relaxed"
                >{{ $terms }}</textarea>
            </div>
            
            <div class="flex justify-end pt-2">
                <button type="submit" class="gradient-orange text-white font-black py-3.5 px-8 rounded-xl shadow-lg shadow-orange-500/25 transition-all hover:opacity-90 cursor-pointer text-xs flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection