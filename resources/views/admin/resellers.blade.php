@extends('layouts.admin')

@php
    $activeSection = request('section', 'list');
@endphp

@section('title', $activeSection === 'create' ? 'Tambah Reseller' : 'Data Reseller')

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

    @if($activeSection === 'create')
        <!-- ======================================================== -->
        <!-- SECTION: TAMBAH RESELLER BARU                            -->
        <!-- ======================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Tambah Reseller Baru</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-purple text-white shadow-sm shadow-purple-500/20">
                        VIP Tier
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">Daftarkan akun mitra reseller VIP baru secara langsung dari panel admin.</p>
            </div>

            <a href="{{ route('admin.resellers', ['section' => 'list']) }}" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 hover:border-purple-500 transition flex items-center gap-2 self-start md:self-auto">
                <span>Lihat Data Reseller ({{ count($resellers) }})</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <div class="admin-card p-6 sm:p-8 max-w-2xl space-y-5">
            <div class="flex items-center gap-3 pb-4 border-b border-gray-100 dark:border-white/5">
                <div class="w-9 h-9 rounded-xl gradient-purple text-white flex items-center justify-center font-black text-xs shadow-sm">
                    VIP
                </div>
                <div>
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Formulir Pendaftaran Mitra Reseller</h3>
                    <p class="text-[11px] font-semibold text-gray-400">Akun yang dibuat langsung memiliki akses harga khusus Reseller VIP</p>
                </div>
            </div>

            <form action="{{ route('admin.resellers.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap Mitra</label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Alamat Email Login</label>
                    <input type="email" name="email" required placeholder="reseller@gmail.com" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1.5">Password Login</label>
                    <input type="text" name="password" required placeholder="Minimal 8 karakter" class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full gradient-purple text-white font-black py-3.5 rounded-xl shadow-lg shadow-purple-500/20 text-xs transition hover:opacity-90 cursor-pointer">
                        + Daftarkan Reseller Sekarang
                    </button>
                </div>
            </form>
        </div>

    @else
        <!-- ======================================================== -->
        <!-- SECTION: DATA RESELLER AKTIF                             -->
        <!-- ======================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Data Mitra Reseller</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-purple text-white shadow-sm shadow-purple-500/20">
                        {{ count($resellers) }} Mitra
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">Kelola akun mitra reseller, atur deposit saldo, dan pantau mutasi transaksi mereka.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <form action="{{ route('admin.resellers') }}" method="GET" class="w-full sm:w-72 relative">
                    <input type="hidden" name="section" value="list">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari nama atau email reseller..." 
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-purple-500 shadow-sm"
                    >
                    <svg class="w-4 h-4 absolute left-3.5 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </form>

                <a href="{{ route('admin.resellers', ['section' => 'create']) }}" class="px-4 py-2.5 rounded-xl gradient-purple text-white text-xs font-black shadow-md shadow-purple-500/20 hover:opacity-95 transition flex items-center justify-center gap-1.5 shrink-0">
                    <span>+ Tambah Reseller</span>
                </a>
            </div>
        </div>

        <!-- Tabel Daftar Reseller -->
        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                <h3 class="font-black text-sm text-gray-900 dark:text-white">Daftar Mitra Reseller Aktif</h3>
                <span class="text-xs font-bold text-gray-400">Total: {{ count($resellers) }} Reseller</span>
            </div>

            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Mitra &amp; Email</th>
                            <th>Saldo Reseller</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Manajemen &amp; Mutasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resellers as $r)
                            <tr x-data="{ showMutasi: false, showRiwayat: false }">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl gradient-purple text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                                            {{ strtoupper(substr($r->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-xs sm:text-sm text-gray-900 dark:text-white">{{ $r->name }}</p>
                                            <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ $r->email }}</p>
                                            @if($r->whatsapp)
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $r->whatsapp) }}" target="_blank" class="text-[10px] text-emerald-500 font-bold hover:underline inline-flex items-center gap-0.5 mt-0.5">
                                                    <span>WA: {{ $r->whatsapp }}</span> ↗
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="font-black text-sm text-purple-600 dark:text-purple-400 mb-1.5">
                                        Rp {{ number_format($r->balance, 0, ',', '.') }}
                                    </div>
                                    <form action="{{ route('admin.resellers.balance', $r->id) }}" method="POST" class="flex items-center gap-1.5">
                                        @csrf
                                        <input type="number" name="amount" placeholder="Nominal Rp" required min="1" class="w-24 px-2.5 py-1 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-lg text-xs font-bold dark:text-white outline-none">
                                        <button name="action" value="add" class="px-2 py-1 rounded-lg bg-emerald-500 text-white text-[10px] font-black hover:bg-emerald-600 transition cursor-pointer" title="Tambah Saldo">
                                            + Tambah
                                        </button>
                                        <button name="action" value="deduct" class="px-2 py-1 rounded-lg bg-rose-500 text-white text-[10px] font-black hover:bg-rose-600 transition cursor-pointer" title="Potong Saldo">
                                            - Potong
                                        </button>
                                    </form>
                                </td>

                                <td class="text-center">
                                    <form action="{{ route('admin.resellers.toggle', $r->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" title="Klik untuk mengubah status" class="px-3 py-1 rounded-full text-[10px] font-black transition cursor-pointer {{ $r->is_active ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300' }}">
                                            {{ $r->is_active ? '● AKTIF' : '● NONAKTIF' }}
                                        </button>
                                    </form>
                                </td>

                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button @click="showMutasi = true" class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/40 text-blue-600 dark:text-blue-400 text-xs font-bold hover:bg-blue-500 hover:text-white transition cursor-pointer">
                                            Mutasi Saldo
                                        </button>
                                        
                                        <button @click="showRiwayat = true" class="px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/40 text-purple-600 dark:text-purple-400 text-xs font-bold hover:bg-purple-500 hover:text-white transition cursor-pointer">
                                            Pesanan
                                        </button>
                                        
                                        <form action="{{ route('admin.resellers.delete', $r->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun reseller ini beserta seluruh saldonya?');" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-600 border border-red-200 dark:border-red-800/40 hover:bg-red-500 hover:text-white transition cursor-pointer" title="Hapus Reseller">
                                                ✕
                                            </button>
                                        </form>
                                    </div>

                                    <!-- MODAL MUTASI SALDO RESELLER -->
                                    <div x-show="showMutasi" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
                                        <div @click.away="showMutasi = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg border border-gray-100 dark:border-white/10 shadow-2xl p-6 text-left space-y-4" x-transition.scale>
                                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                                                <div>
                                                    <h3 class="font-black text-sm text-gray-900 dark:text-white">Mutasi Saldo: {{ $r->name }}</h3>
                                                    <p class="text-[10px] text-gray-400 font-mono">Saldo Saat Ini: Rp {{ number_format($r->balance, 0, ',', '.') }}</p>
                                                </div>
                                                <button @click="showMutasi = false" class="text-gray-400 hover:text-gray-200">✕</button>
                                            </div>
                                            <div class="max-h-72 overflow-y-auto custom-scrollbar space-y-2">
                                                @forelse($r->transactionLogs ?? [] as $log)
                                                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-black/30 border border-gray-100 dark:border-white/5 flex items-center justify-between text-xs">
                                                        <div>
                                                            <p class="font-bold text-gray-900 dark:text-white">{{ $log->description }}</p>
                                                            <p class="text-[10px] text-gray-400">{{ $log->created_at->format('d M Y, H:i') }}</p>
                                                        </div>
                                                        <span class="font-black {{ $log->type == 'debit' || $log->type == 'deduction' ? 'text-red-500' : 'text-emerald-500' }}">
                                                            {{ $log->type == 'debit' || $log->type == 'deduction' ? '-' : '+' }}Rp {{ number_format($log->amount, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                @empty
                                                    <p class="text-center py-8 text-xs text-gray-400">Belum ada riwayat mutasi.</p>
                                                @endforelse
                                            </div>
                                            <button type="button" @click="showMutasi = false" class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold">Tutup</button>
                                        </div>
                                    </div>

                                    <!-- MODAL RIWAYAT PESANAN RESELLER -->
                                    <div x-show="showRiwayat" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
                                        <div @click.away="showRiwayat = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg border border-gray-100 dark:border-white/10 shadow-2xl p-6 text-left space-y-4" x-transition.scale>
                                            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                                                <div>
                                                    <h3 class="font-black text-sm text-gray-900 dark:text-white">Riwayat Pembelian: {{ $r->name }}</h3>
                                                    <p class="text-[10px] text-gray-400">Daftar transaksi yang dibuat reseller ini</p>
                                                </div>
                                                <button @click="showRiwayat = false" class="text-gray-400 hover:text-gray-200">✕</button>
                                            </div>
                                            <div class="max-h-72 overflow-y-auto custom-scrollbar space-y-2">
                                                @forelse($r->orders ?? [] as $ord)
                                                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-black/30 border border-gray-100 dark:border-white/5 flex items-center justify-between text-xs">
                                                        <div>
                                                            <p class="font-bold text-gray-900 dark:text-white">{{ $ord->product_name }}</p>
                                                            <p class="text-[10px] text-gray-400 font-mono">#{{ $ord->invoice_number }} • {{ $ord->created_at->format('d M Y') }}</p>
                                                        </div>
                                                        <span class="font-black text-orange-500">
                                                            Rp {{ number_format($ord->total_amount ?? $ord->price, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                @empty
                                                    <p class="text-center py-8 text-xs text-gray-400">Belum pernah melakukan pemesanan.</p>
                                                @endforelse
                                            </div>
                                            <button type="button" @click="showRiwayat = false" class="w-full py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-bold">Tutup</button>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                    @if(request('search')) Pencarian tidak ditemukan. @else Belum ada reseller yang terdaftar. @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards List -->
            <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
                @forelse($resellers as $r)
                    <div class="p-4 space-y-3" x-data="{ showMutasi: false, showRiwayat: false }">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl gradient-purple text-white flex items-center justify-center font-black text-sm shadow-sm shrink-0">
                                    {{ strtoupper(substr($r->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-black text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $r->name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono truncate">{{ $r->email }}</p>
                                    @if($r->whatsapp)
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $r->whatsapp) }}" target="_blank" class="text-[10px] text-emerald-500 font-bold hover:underline inline-flex items-center gap-0.5 mt-0.5">
                                            <span>WA: {{ $r->whatsapp }}</span> ↗
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <form action="{{ route('admin.resellers.toggle', $r->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-0.5 rounded-full text-[9px] font-black transition cursor-pointer {{ $r->is_active ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-300' }}">
                                    {{ $r->is_active ? '● AKTIF' : '● NONAKTIF' }}
                                </button>
                            </form>
                        </div>

                        <div class="p-3 rounded-2xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-gray-400">Saldo Akun:</span>
                                <span class="font-black text-sm text-purple-600 dark:text-purple-400">Rp {{ number_format($r->balance, 0, ',', '.') }}</span>
                            </div>
                            <form action="{{ route('admin.resellers.balance', $r->id) }}" method="POST" class="flex items-center gap-1.5">
                                @csrf
                                <input type="number" name="amount" placeholder="Nominal Rp" required min="1" class="flex-1 px-3 py-1.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 rounded-xl text-xs font-bold dark:text-white outline-none">
                                <button name="action" value="add" class="px-2.5 py-1.5 rounded-xl bg-emerald-500 text-white text-[10px] font-black hover:bg-emerald-600 transition cursor-pointer shrink-0" title="Tambah Saldo">
                                    + Tambah
                                </button>
                                <button name="action" value="deduct" class="px-2.5 py-1.5 rounded-xl bg-rose-500 text-white text-[10px] font-black hover:bg-rose-600 transition cursor-pointer shrink-0" title="Potong Saldo">
                                    - Potong
                                </button>
                            </form>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-white/5">
                            <div class="flex items-center gap-1.5">
                                <button @click="showMutasi = true" class="px-3 py-1.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/40 text-blue-600 dark:text-blue-400 text-xs font-bold hover:bg-blue-500 hover:text-white transition cursor-pointer">
                                    Mutasi Saldo
                                </button>
                                <button @click="showRiwayat = true" class="px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 border border-purple-200 dark:border-purple-800/40 text-purple-600 dark:text-purple-400 text-xs font-bold hover:bg-purple-500 hover:text-white transition cursor-pointer">
                                    Pesanan
                                </button>
                            </div>
                            <form action="{{ route('admin.resellers.delete', $r->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun reseller ini beserta seluruh saldonya?');" class="inline">
                                @csrf
                                <button type="submit" class="p-1.5 rounded-xl bg-red-50 dark:bg-red-950/30 text-red-600 border border-red-200 dark:border-red-800/40 hover:bg-red-500 hover:text-white transition cursor-pointer" title="Hapus Reseller">
                                    ✕
                                </button>
                            </form>
                        </div>

                        <div x-show="showMutasi" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
                            <div @click.away="showMutasi = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg border border-gray-100 dark:border-white/10 shadow-2xl p-6 text-left space-y-4 max-h-[90vh] flex flex-col" x-transition.scale>
                                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10 shrink-0">
                                    <div>
                                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Mutasi Saldo: {{ $r->name }}</h3>
                                        <p class="text-[10px] text-gray-400 font-mono">Saldo Saat Ini: Rp {{ number_format($r->balance, 0, ',', '.') }}</p>
                                    </div>
                                    <button @click="showMutasi = false" class="text-gray-400 hover:text-gray-200">✕</button>
                                </div>
                                <div class="overflow-y-auto no-scrollbar space-y-2 flex-1">
                                    @forelse($r->transactionLogs ?? [] as $log)
                                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-black/30 border border-gray-100 dark:border-white/5 flex items-center justify-between text-xs">
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white">{{ $log->description }}</p>
                                                <p class="text-[10px] text-gray-400">{{ $log->created_at->format('d M Y, H:i') }}</p>
                                            </div>
                                            <span class="font-black {{ $log->type == 'debit' || $log->type == 'deduction' ? 'text-red-500' : 'text-emerald-500' }}">
                                                {{ $log->type == 'debit' || $log->type == 'deduction' ? '-' : '+' }}Rp {{ number_format($log->amount, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    @empty
                                        <p class="text-center py-6 text-gray-400 text-xs">Belum ada riwayat mutasi saldo.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div x-show="showRiwayat" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition.opacity>
                            <div @click.away="showRiwayat = false" class="bg-white dark:bg-[#131b2e] rounded-3xl w-full max-w-lg border border-gray-100 dark:border-white/10 shadow-2xl p-6 text-left space-y-4 max-h-[90vh] flex flex-col" x-transition.scale>
                                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10 shrink-0">
                                    <div>
                                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Pesanan Terakhir: {{ $r->name }}</h3>
                                        <p class="text-[10px] text-gray-400">Daftar transaksi akun reseller ini</p>
                                    </div>
                                    <button @click="showRiwayat = false" class="text-gray-400 hover:text-gray-200">✕</button>
                                </div>
                                <div class="overflow-y-auto no-scrollbar space-y-2 flex-1">
                                    @forelse($r->orders ?? [] as $ro)
                                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-black/30 border border-gray-100 dark:border-white/5 text-xs space-y-1">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono text-[10px] text-gray-400">#{{ $ro->invoice_number }}</span>
                                                <span class="font-black text-emerald-500">Rp {{ number_format($ro->total_amount, 0, ',', '.') }}</span>
                                            </div>
                                            <p class="font-bold text-gray-900 dark:text-white">{{ $ro->product_name }}</p>
                                        </div>
                                    @empty
                                        <p class="text-center py-6 text-gray-400 text-xs">Belum ada transaksi pesanan.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-gray-400 font-semibold text-xs">
                        Tidak ada reseller yang terdaftar.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

</div>
@endsection
