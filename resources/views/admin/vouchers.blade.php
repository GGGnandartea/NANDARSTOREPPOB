@extends('layouts.admin')
@section('title', 'Manajemen Voucher Diskon')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <!-- Header & Pencarian -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <span>Kupon & Voucher Diskon</span>
                <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                    Promo Toko
                </span>
            </h1>
            <p class="text-xs font-semibold text-gray-400 mt-1">Buat kode diskon spesial berupa nominal Rupiah atau persentase potongan harga.</p>
        </div>

        <form action="{{ route('admin.vouchers') }}" method="GET" class="w-full md:w-80 relative">
            <input 
                type="text" 
                name="search" 
                value="{{ request('search') }}" 
                placeholder="Cari kode kupon..." 
                class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs sm:text-sm font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 shadow-sm"
            >
            <svg class="w-4 h-4 absolute left-4 top-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Form Buat Voucher Baru (Kiri) -->
        <div class="lg:col-span-1 admin-card p-6 h-fit space-y-4">
            <div class="flex items-center gap-2.5 pb-3 border-b border-gray-100 dark:border-white/5">
                <div class="w-8 h-8 rounded-xl gradient-orange text-white flex items-center justify-center font-black text-xs shadow-sm">
                    🎟️
                </div>
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Buat Voucher Baru</h3>
                    <p class="text-[10px] text-gray-400">Atur kode promo & kuota</p>
                </div>
            </div>

            <form action="{{ route('admin.vouchers.store') }}" method="POST" class="space-y-3.5" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Kode Voucher</label>
                    <input type="text" name="code" placeholder="Misal: PROMONANDAR" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-mono font-black dark:text-white outline-none focus:ring-2 focus:ring-orange-500 uppercase tracking-wider">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Tipe Diskon</label>
                        <select name="type" class="w-full px-3 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer">
                            <option value="nominal">Nominal (Rp)</option>
                            <option value="percent">Persen (%)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Nilai Potongan</label>
                        <input type="number" name="value" required placeholder="Cth: 5000" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Batas Kuota Penggunaan</label>
                    <input type="number" name="max_uses" placeholder="Kosongkan jika tanpa batas" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Tanggal Kedaluwarsa</label>
                    <input type="date" name="expired_at" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer">
                </div>

                <button type="submit" x-bind:disabled="isSubmitting" class="w-full gradient-orange text-white py-3.5 rounded-xl font-black text-xs transition-all shadow-lg shadow-orange-500/25 hover:opacity-90 cursor-pointer disabled:opacity-50">
                    <span x-show="!isSubmitting">✓ Buat Voucher Sekarang</span>
                    <span x-show="isSubmitting" style="display: none;">Memproses...</span>
                </button>
            </form>
        </div>

        <!-- Tabel Daftar Voucher (Kanan) -->
        <div class="lg:col-span-2 admin-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                <h3 class="font-black text-sm text-gray-900 dark:text-white">Daftar Kode Promo Aktif</h3>
                <span class="text-xs font-bold text-gray-400">Total: {{ count($vouchers) }} Kupon</span>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Kupon & Diskon</th>
                            <th>Penggunaan / Kuota</th>
                            <th>Status & Masa Berlaku</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $v)
                        <tr>
                            <!-- Kolom 1: Kode Kupon & Potongan -->
                            <td>
                                <div class="inline-flex items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg bg-orange-50 dark:bg-orange-950/50 border border-orange-200 dark:border-orange-800/40 font-mono font-black text-xs text-orange-600 dark:text-orange-400 tracking-wider">
                                        {{ $v->code }}
                                    </span>
                                </div>
                                <p class="text-xs font-black text-gray-900 dark:text-white mt-1">
                                    Potongan: {{ $v->type == 'percent' ? $v->value . '%' : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                                </p>
                            </td>

                            <!-- Kolom 2: Penggunaan / Kuota -->
                            <td>
                                <div class="text-xs font-bold text-blue-600 dark:text-blue-400 mb-0.5">
                                    Dipakai: {{ $v->used ?? 0 }} / {{ $v->max_uses ?: '∞' }} kali
                                </div>
                                @if($v->max_uses)
                                    <div class="w-32 h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden mt-1">
                                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ min(100, (($v->used ?? 0) / $v->max_uses) * 100) }}%"></div>
                                    </div>
                                @endif
                            </td>

                            <!-- Kolom 3: Status & Masa Berlaku -->
                            <td>
                                <div>
                                    @if($v->is_active)
                                        <span class="bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50 px-2 py-0.5 rounded-full text-[10px] font-black">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700 px-2 py-0.5 rounded-full text-[10px] font-black">
                                            Nonaktif
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    {{ $v->expired_at ? 'Berlaku s/d ' . $v->expired_at->format('d M Y') : 'Tanpa batas waktu' }}
                                </p>
                            </td>

                            <!-- Kolom 4: Aksi -->
                            <td class="text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Toggle Aktif/Nonaktif -->
                                    <form action="{{ route('admin.vouchers.toggle', $v->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-bold transition cursor-pointer {{ $v->is_active ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 border border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-200 hover:bg-emerald-100' }}">
                                            {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>

                                    <!-- Hapus -->
                                    <form action="{{ route('admin.vouchers.delete', $v->id) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?');" class="inline">
                                        @csrf
                                        <button type="submit" class="p-1 rounded-lg text-gray-400 hover:text-red-500 transition cursor-pointer" title="Hapus Voucher">
                                            ✕
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                @if(request('search')) Pencarian tidak ditemukan. @else Belum ada voucher diskon yang dibuat. @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards List -->
            <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
                @forelse($vouchers as $v)
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-orange-50 dark:bg-orange-950/50 border border-orange-200 dark:border-orange-800/40 font-mono font-black text-xs text-orange-600 dark:text-orange-400 tracking-wider">
                                {{ $v->code }}
                            </span>
                            @if($v->is_active)
                                <span class="bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50 px-2 py-0.5 rounded-full text-[9px] font-black">
                                    Aktif
                                </span>
                            @else
                                <span class="bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-300 dark:border-gray-700 px-2 py-0.5 rounded-full text-[9px] font-black">
                                    Nonaktif
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-black text-gray-900 dark:text-white">
                                Diskon: {{ $v->type == 'percent' ? $v->value . '%' : 'Rp ' . number_format($v->value, 0, ',', '.') }}
                            </span>
                            <span class="text-[11px] font-bold text-blue-600 dark:text-blue-400">
                                Dipakai: {{ $v->used ?? 0 }} / {{ $v->max_uses ?: '∞' }} kali
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-white/5 text-[10px] text-gray-400">
                            <span>{{ $v->expired_at ? 'Berlaku s/d ' . $v->expired_at->format('d M Y') : 'Tanpa batas waktu' }}</span>
                            <div class="flex items-center gap-2">
                                <form action="{{ route('admin.vouchers.toggle', $v->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-[10px] font-bold transition cursor-pointer {{ $v->is_active ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 border border-amber-200' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 border border-emerald-200' }}">
                                        {{ $v->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.vouchers.delete', $v->id) }}" method="POST" onsubmit="return confirm('Hapus voucher ini?');" class="inline">
                                    @csrf
                                    <button type="submit" class="p-1 rounded-lg text-gray-400 hover:text-red-500 transition cursor-pointer" title="Hapus Voucher">
                                        ✕
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-400 font-semibold text-xs">
                        @if(request('search')) Pencarian tidak ditemukan. @else Belum ada voucher diskon yang dibuat. @endif
                    </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection