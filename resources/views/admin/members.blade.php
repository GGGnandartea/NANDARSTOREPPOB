@extends('layouts.admin')
@section('title', 'Kelola Member & Saldo Pengguna')

@section('content')
@php
    $activeSection = request('section', 'list');
    $currentRole = $roleFilter ?? request('role', 'all');
@endphp
<div class="max-w-7xl mx-auto space-y-6" x-data="{ 
    balanceModal: false,
    historyModal: false,
    orderModal: false,
    selectedUser: null,
    userLogs: [],
    userOrders: [],
    actionType: 'add',
    amountInput: '',
    descriptionInput: '',
    formatRp(val) {
        const n = Number(val) || 0;
        return 'Rp ' + n.toLocaleString('id-ID');
    },
    computedFinalBalance() {
        const cur = Number(this.selectedUser?.balance || 0);
        const amt = Number(this.amountInput || 0);
        if (!amt || amt <= 0) return cur;
        return this.actionType === 'add' ? cur + amt : Math.max(0, cur - amt);
    }
}">

    <!-- Alert Notifications -->
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
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-xs space-y-1 shadow-sm">
            @foreach($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    @if($activeSection === 'create')
        <!-- ===================================================== -->
        <!-- HALAMAN TAMBAH AKUN MEMBER / RESELLER BARU            -->
        <!-- ===================================================== -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-white/10">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Tambah Akun Pengguna Baru</span>
                    <span class="text-xs font-black px-2.5 py-1 rounded-full bg-orange-500 text-white">Member / Reseller</span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">Buat akun Member Biasa atau Reseller VIP secara langsung beserta saldo awal opsional.</p>
            </div>
            <a href="{{ route('admin.members', ['section' => 'list']) }}" class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-white/5 text-xs font-black text-gray-700 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-orange-950/30 hover:text-orange-600 transition inline-flex items-center gap-2 self-start sm:self-auto">
                <span>← Kembali ke Daftar Member</span>
            </a>
        </div>

        <div class="max-w-xl mx-auto pt-2">
            <div class="admin-card p-6 sm:p-8 border-t-4 border-t-orange-500">
                <h2 class="text-base font-black text-gray-900 dark:text-white mb-5 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl gradient-orange text-white flex items-center justify-center text-sm font-black shadow-md shadow-orange-500/20">+</span>
                    <span>Formulir Pendaftaran Member</span>
                </h2>

                <form action="{{ route('admin.members.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Alamat Email (Login)</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="budi@gmail.com" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Nomor WhatsApp (Opsional)</label>
                            <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" placeholder="081234567890" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold font-mono dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Level / Role Akun</label>
                            <select name="role" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                                <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>👤 Member Biasa (User)</option>
                                <option value="reseller" {{ old('role') === 'reseller' ? 'selected' : '' }}>👑 Mitra Reseller VIP</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Saldo Awal (Rp)</label>
                            <input type="number" name="initial_balance" value="{{ old('initial_balance', 0) }}" min="0" placeholder="0" class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-black font-mono dark:text-emerald-400 focus:ring-2 focus:ring-orange-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Password Login</label>
                        <input type="password" name="password" placeholder="Minimal 6 karakter" required class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none">
                    </div>

                    <button type="submit" class="w-full py-3.5 rounded-xl gradient-orange text-white font-black text-xs shadow-lg shadow-orange-500/25 hover:opacity-95 transition cursor-pointer mt-2">
                        Simpan & Aktifkan Akun Pengguna
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- ===================================================== -->
        <!-- HALAMAN UTAMA KELOLA SEMUA MEMBER & EDIT SALDO        -->
        <!-- ===================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-white/10">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Kelola Member & Saldo Pengguna</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-orange text-white shadow-sm shadow-orange-500/20">
                        {{ $members->count() }} Ditampilkan
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">Cek saldo seluruh member biasa & reseller, top up / potong saldo manual, lihat mutasi, dan upgrade ke Reseller VIP 1 klik.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.members', ['section' => 'create']) }}" class="px-4 py-2.5 rounded-xl gradient-orange text-white text-xs font-black shadow-md shadow-orange-500/20 hover:opacity-95 transition inline-flex items-center gap-1.5">
                    <span>+ Tambah Member Baru</span>
                </a>
            </div>
        </div>

        <!-- KPI Ringkasan Member & Saldo -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="admin-card p-4 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Total Pengguna</p>
                    <p class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($totalMembers ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Member & Reseller</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-blue-500/10 text-blue-500 flex items-center justify-center font-black text-base">👥</div>
            </div>

            <div class="admin-card p-4 flex items-center justify-between border-l-4 border-l-slate-500">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Member Biasa</p>
                    <p class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ number_format($regularMembersCount ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Role: User Reguler</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-slate-500/10 text-slate-500 flex items-center justify-center font-black text-base">👤</div>
            </div>

            <div class="admin-card p-4 flex items-center justify-between border-l-4 border-l-purple-500">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Reseller VIP</p>
                    <p class="text-xl sm:text-2xl font-black text-purple-600 dark:text-purple-400 mt-0.5">{{ number_format($resellerMembersCount ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Mitra Harga Spesial</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-purple-500/10 text-purple-500 flex items-center justify-center font-black text-base">👑</div>
            </div>

            <div class="admin-card p-4 flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400">Total Saldo Mengendap</p>
                    <p class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5">Rp {{ number_format($totalUserBalance ?? 0, 0, ',', '.') }}</p>
                    <p class="text-[10px] text-gray-400 font-semibold mt-0.5">Akumulasi Dompet User</p>
                </div>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-black text-base">💰</div>
            </div>
        </div>

        <!-- Filter Tab Role & Pencarian -->
        <div class="admin-card p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <!-- Filter Role Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 md:pb-0 no-scrollbar">
                <a href="{{ route('admin.members', array_merge(request()->except('role'), ['role' => 'all'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-black transition shrink-0 {{ $currentRole === 'all' ? 'gradient-orange text-white shadow-md shadow-orange-500/20' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    Semua ({{ $totalMembers ?? 0 }})
                </a>
                <a href="{{ route('admin.members', array_merge(request()->except('role'), ['role' => 'user'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-black transition shrink-0 {{ $currentRole === 'user' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    👤 Member Biasa ({{ $regularMembersCount ?? 0 }})
                </a>
                <a href="{{ route('admin.members', array_merge(request()->except('role'), ['role' => 'reseller'])) }}" 
                   class="px-3.5 py-2 rounded-xl text-xs font-black transition shrink-0 {{ $currentRole === 'reseller' ? 'bg-purple-600 text-white shadow-md shadow-purple-600/20' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200' }}">
                    👑 Reseller VIP ({{ $resellerMembersCount ?? 0 }})
                </a>
            </div>

            <!-- Form Pencarian -->
            <form action="{{ route('admin.members') }}" method="GET" class="flex items-center gap-2 w-full md:w-auto">
                @if($currentRole !== 'all')
                    <input type="hidden" name="role" value="{{ $currentRole }}">
                @endif
                <div class="relative flex-1 md:w-72">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari nama, email, atau nomor WA..." 
                        class="w-full pl-9 pr-4 py-2 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none"
                    >
                    <svg class="w-4 h-4 absolute left-3 top-2.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl gradient-orange text-white text-xs font-black shadow-sm cursor-pointer">
                    Cari
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.members', ['role' => $currentRole]) }}" class="px-3 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-500 hover:text-gray-800">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <!-- Tabel Daftar Member -->
        <div class="admin-card overflow-hidden">
            <!-- Desktop Table -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Profil Pengguna</th>
                            <th>WhatsApp</th>
                            <th>Level Akun</th>
                            <th>Saldo Dompet</th>
                            <th>Mutasi & Pesanan</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $m)
                            <tr>
                                <!-- Profil -->
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl {{ $m->role === 'reseller' ? 'bg-purple-500/15 text-purple-500 border border-purple-500/30' : 'bg-blue-500/15 text-blue-500 border border-blue-500/30' }} flex items-center justify-center font-black text-xs uppercase shrink-0">
                                            {{ substr($m->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-xs text-gray-900 dark:text-white">{{ $m->name }}</p>
                                            <p class="text-[11px] text-gray-400 font-mono">{{ $m->email }}</p>
                                            <p class="text-[9px] text-gray-400 mt-0.5">Daftar: {{ $m->created_at ? $m->created_at->format('d M Y') : '-' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- WhatsApp -->
                                <td>
                                    @if($m->whatsapp)
                                        <div class="flex flex-col gap-1">
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $m->whatsapp) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                                                <span>{{ $m->whatsapp }}</span>
                                                <span>↗</span>
                                            </a>
                                            @if($m->is_whatsapp_verified)
                                                <span class="inline-flex items-center gap-1 text-[9px] font-black text-emerald-600 dark:text-emerald-400">
                                                    ✓ Terverifikasi
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[9px] font-bold text-amber-500">
                                                    ⏳ Belum Verifikasi
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-[11px] text-gray-400 italic">Belum diisi</span>
                                    @endif
                                </td>

                                <!-- Level Akun & 1-Click Role Switch -->
                                <td>
                                    <div class="flex flex-col items-start gap-1.5">
                                        @if($m->role === 'reseller')
                                            <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-800/50">
                                                👑 Reseller VIP
                                            </span>
                                            <form action="{{ route('admin.members.role', $m->id) }}" method="POST" onsubmit="return confirm('Ubah akun {{ addslashes($m->name) }} kembali menjadi Member Biasa?');">
                                                @csrf
                                                <input type="hidden" name="role" value="user">
                                                <button type="submit" class="text-[10px] font-bold text-gray-400 hover:text-orange-500 underline cursor-pointer">
                                                    Ubah ke Member Biasa
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                                                👤 Member Biasa
                                            </span>
                                            <form action="{{ route('admin.members.role', $m->id) }}" method="POST" onsubmit="return confirm('Upgrade akun {{ addslashes($m->name) }} menjadi Mitra Reseller VIP sekarang?');">
                                                @csrf
                                                <input type="hidden" name="role" value="reseller">
                                                <button type="submit" class="px-2 py-0.5 rounded-md bg-purple-500/10 hover:bg-purple-500 text-purple-600 dark:text-purple-300 hover:text-white border border-purple-500/20 text-[10px] font-black transition cursor-pointer">
                                                    👑 Upgrade ke Reseller
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>

                                <!-- Saldo Dompet + Edit Saldo Button -->
                                <td>
                                    <div class="flex items-center gap-2.5">
                                        <div>
                                            <span class="font-black text-sm text-emerald-600 dark:text-emerald-400 block">
                                                Rp {{ number_format($m->balance ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <button 
                                            type="button"
                                            @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'role' => $m->role, 'balance' => (int) ($m->balance ?? 0)]) }}; actionType = 'add'; amountInput = ''; descriptionInput = ''; balanceModal = true;"
                                            class="px-3 py-1.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-black text-[11px] shadow-sm shadow-orange-500/20 transition cursor-pointer inline-flex items-center gap-1"
                                            title="Tambah atau Potong Saldo {{ $m->name }}"
                                        >
                                            <span>💰</span>
                                            <span>Edit Saldo</span>
                                        </button>
                                    </div>
                                </td>

                                <!-- Mutasi & Pesanan -->
                                <td>
                                    @php
                                        $logsData = $m->transactionLogs->map(function($l) {
                                            return [
                                                'type' => $l->type,
                                                'amount' => (int) $l->amount,
                                                'desc' => $l->description,
                                                'date' => $l->created_at ? $l->created_at->format('d M Y, H:i') : '-'
                                            ];
                                        })->values();

                                        $ordersData = $m->orders->map(function($o) {
                                            return [
                                                'invoice' => $o->invoice_number,
                                                'product' => $o->product_name,
                                                'price' => (int) ($o->total_amount ?? $o->price ?? 0),
                                                'status' => $o->order_status,
                                                'sn' => $o->sn_or_account ?? '-',
                                                'date' => $o->created_at ? $o->created_at->format('d M Y, H:i') : '-'
                                            ];
                                        })->values();
                                    @endphp
                                    <div class="flex items-center gap-1.5">
                                        <button 
                                            type="button"
                                            @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name]) }}; userLogs = {{ json_encode($logsData) }}; historyModal = true;"
                                            class="px-2.5 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/40 font-bold text-[11px] hover:bg-amber-500 hover:text-white transition cursor-pointer"
                                        >
                                            📜 Mutasi ({{ $m->transactionLogs->count() }})
                                        </button>

                                        <button 
                                            type="button"
                                            @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name]) }}; userOrders = {{ json_encode($ordersData) }}; orderModal = true;"
                                            class="px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/40 font-bold text-[11px] hover:bg-blue-500 hover:text-white transition cursor-pointer"
                                        >
                                            🛍️ Pesanan ({{ $m->orders->count() }})
                                        </button>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td>
                                    @if($m->is_active)
                                        <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/50">
                                            ● Aktif
                                        </span>
                                    @else
                                        <span class="text-[10px] font-black px-2.5 py-1 rounded-full bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-300 dark:border-rose-800/50">
                                            ○ Nonaktif
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <form action="{{ route('admin.members.toggle', $m->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 text-[11px] font-bold transition cursor-pointer">
                                                {{ $m->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.members.delete', $m->id) }}" method="POST" onsubmit="return confirm('Hapus permanen akun {{ addslashes($m->name) }}?');" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-rose-500 transition cursor-pointer" title="Hapus Pengguna">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                    Belum ada data pengguna yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Cards View -->
            <div class="block md:hidden divide-y divide-gray-100 dark:divide-white/5">
                @forelse($members as $m)
                    @php
                        $logsData = $m->transactionLogs->map(function($l) {
                            return [
                                'type' => $l->type,
                                'amount' => (int) $l->amount,
                                'desc' => $l->description,
                                'date' => $l->created_at ? $l->created_at->format('d M Y, H:i') : '-'
                            ];
                        })->values();

                        $ordersData = $m->orders->map(function($o) {
                            return [
                                'invoice' => $o->invoice_number,
                                'product' => $o->product_name,
                                'price' => (int) ($o->total_amount ?? $o->price ?? 0),
                                'status' => $o->order_status,
                                'sn' => $o->sn_or_account ?? '-',
                                'date' => $o->created_at ? $o->created_at->format('d M Y, H:i') : '-'
                            ];
                        })->values();
                    @endphp
                    <div class="p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="w-9 h-9 rounded-xl {{ $m->role === 'reseller' ? 'bg-purple-500/15 text-purple-500' : 'bg-blue-500/15 text-blue-500' }} flex items-center justify-center font-black text-xs uppercase shrink-0">
                                    {{ substr($m->name, 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $m->name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono truncate">{{ $m->email }}</p>
                                </div>
                            </div>
                            @if($m->role === 'reseller')
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 shrink-0">
                                    👑 Reseller VIP
                                </span>
                            @else
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 shrink-0">
                                    👤 Member Biasa
                                </span>
                            @endif
                        </div>

                        <div class="p-3 rounded-xl bg-gray-50 dark:bg-black/20 border border-gray-100 dark:border-white/5 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-gray-400 font-bold uppercase block">Saldo Dompet</span>
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($m->balance ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <button 
                                type="button"
                                @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name, 'email' => $m->email, 'role' => $m->role, 'balance' => (int) ($m->balance ?? 0)]) }}; actionType = 'add'; amountInput = ''; descriptionInput = ''; balanceModal = true;"
                                class="px-3 py-1.5 rounded-xl gradient-orange text-white font-black text-xs shadow-sm cursor-pointer"
                            >
                                💰 Edit Saldo
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-1.5">
                                <button 
                                    type="button"
                                    @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name]) }}; userLogs = {{ json_encode($logsData) }}; historyModal = true;"
                                    class="px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 font-bold text-[11px]"
                                >
                                    📜 Mutasi ({{ $m->transactionLogs->count() }})
                                </button>
                                <button 
                                    type="button"
                                    @click="selectedUser = {{ json_encode(['id' => $m->id, 'name' => $m->name]) }}; userOrders = {{ json_encode($ordersData) }}; orderModal = true;"
                                    class="px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-bold text-[11px]"
                                >
                                    🛍️ Pesanan ({{ $m->orders->count() }})
                                </button>
                            </div>

                            @if($m->role === 'user')
                                <form action="{{ route('admin.members.role', $m->id) }}" method="POST" onsubmit="return confirm('Upgrade {{ addslashes($m->name) }} ke Reseller VIP?');">
                                    @csrf
                                    <input type="hidden" name="role" value="reseller">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg bg-purple-600 text-white font-black text-[10px]">
                                        👑 Upgrade VIP
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center text-gray-400 text-xs font-bold">
                        Tidak ada data member ditemukan.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL 1: EDIT SALDO MEMBER (TOP UP / POTONG SALDO MANUAL + KETERANGAN)    -->
    <!-- ========================================================================= -->
    <div x-show="balanceModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-[#131b2e] rounded-3xl p-6 max-w-md w-full border border-gray-100 dark:border-white/10 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto overscroll-contain custom-scrollbar">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">💰 Kelola Saldo Pengguna</h3>
                    <p class="text-xs text-orange-500 font-bold" x-text="selectedUser ? (selectedUser.name + ' (' + selectedUser.email + ')') : ''"></p>
                </div>
                <button type="button" @click="balanceModal = false" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center cursor-pointer">✕</button>
            </div>

            <!-- Info Saldo Saat Ini & Proyeksi Saldo Akhir -->
            <div class="grid grid-cols-2 gap-2.5 p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/90 border border-gray-200 dark:border-white/10">
                <div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Saldo Saat Ini</span>
                    <span class="text-sm font-black text-gray-900 dark:text-white" x-text="formatRp(selectedUser ? selectedUser.balance : 0)"></span>
                </div>
                <div class="border-l border-gray-200 dark:border-white/10 pl-3">
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Estimasi Saldo Akhir</span>
                    <span class="text-sm font-black" :class="actionType === 'add' ? 'text-emerald-500' : 'text-rose-500'" x-text="formatRp(computedFinalBalance())"></span>
                </div>
            </div>

            <form :action="'{{ url('/admin/members') }}/' + (selectedUser ? selectedUser.id : '') + '/balance'" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Pilih Jenis Transaksi</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button 
                            type="button" 
                            @click="actionType = 'add'"
                            :class="actionType === 'add' ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20' : 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10'"
                            class="py-2.5 px-3 rounded-xl border text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <span>➕ Tambah Saldo</span>
                        </button>
                        <button 
                            type="button" 
                            @click="actionType = 'deduct'"
                            :class="actionType === 'deduct' ? 'bg-rose-600 text-white border-rose-600 shadow-md shadow-rose-600/20' : 'bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-white/10'"
                            class="py-2.5 px-3 rounded-xl border text-xs font-black transition cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <span>➖ Potong Saldo</span>
                        </button>
                    </div>
                    <input type="hidden" name="action" :value="actionType">
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Nominal (Rp)</label>
                    <input 
                        type="number" 
                        name="amount" 
                        x-model="amountInput"
                        placeholder="Masukkan nominal, misal: 25000" 
                        required 
                        min="1" 
                        class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-sm font-black font-mono dark:text-white outline-none focus:ring-2 focus:ring-orange-500"
                    >
                    <!-- Quick Nominal Pills -->
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <template x-for="preset in [10000, 20000, 50000, 100000, 250000]" :key="preset">
                            <button 
                                type="button" 
                                @click="amountInput = preset"
                                class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-orange-500 hover:text-white text-[10px] font-black text-gray-600 dark:text-gray-300 transition cursor-pointer"
                                x-text="formatRp(preset)"
                            ></button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-wider block mb-1.5">Keterangan Mutasi (Opsional)</label>
                    <input 
                        type="text" 
                        name="description" 
                        x-model="descriptionInput"
                        :placeholder="actionType === 'add' ? 'Contoh: Top Up Manual Transfer BCA / Bonus Member' : 'Contoh: Koreksi Saldo / Pembelian Manual'" 
                        class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-orange-500"
                    >
                    <p class="text-[10px] text-gray-400 mt-1">Tercatat di Riwayat Mutasi Member & dikirim otomatis ke WhatsApp member.</p>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-gray-100 dark:border-white/10">
                    <button type="button" @click="balanceModal = false" class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer">Batal</button>
                    <button 
                        type="submit" 
                        :class="actionType === 'add' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/20' : 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/20'"
                        class="px-5 py-2.5 rounded-xl text-white text-xs font-black shadow-md transition cursor-pointer"
                        x-text="actionType === 'add' ? '✓ Tambahkan Saldo Sekarang' : '✓ Potong Saldo Sekarang'"
                    ></button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: RIWAYAT MUTASI SALDO PENGGUNA                                    -->
    <!-- ========================================================================= -->
    <div x-show="historyModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-[#131b2e] rounded-3xl p-6 max-w-lg w-full border border-gray-100 dark:border-white/10 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">📜 Riwayat Mutasi Saldo</h3>
                    <p class="text-xs text-orange-500 font-bold" x-text="selectedUser ? selectedUser.name : ''"></p>
                </div>
                <button type="button" @click="historyModal = false" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center cursor-pointer">✕</button>
            </div>

            <div class="max-h-80 overflow-y-auto overscroll-contain space-y-2.5 pr-1 custom-scrollbar">
                <template x-if="userLogs.length === 0">
                    <p class="text-center py-8 text-xs text-gray-400 italic">Belum ada riwayat mutasi saldo untuk pengguna ini.</p>
                </template>

                <template x-for="(log, index) in userLogs" :key="index">
                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200/60 dark:border-white/5 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-gray-800 dark:text-gray-200 break-words" x-text="log.desc"></p>
                            <span class="text-[10px] text-gray-400 font-mono" x-text="log.date"></span>
                        </div>
                        <div class="text-right shrink-0">
                            <span 
                                class="text-xs font-black px-2.5 py-1 rounded-lg"
                                :class="['deposit', 'topup', 'refund', 'credit', 'bonus'].includes(String(log.type).toLowerCase()) ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400'"
                                x-text="(['deposit', 'topup', 'refund', 'credit', 'bonus'].includes(String(log.type).toLowerCase()) ? '+ ' : '- ') + formatRp(log.amount)"
                            ></span>
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-white/10 text-right">
                <button type="button" @click="historyModal = false" class="px-5 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 3: RIWAYAT PESANAN PENGGUNA                                         -->
    <!-- ========================================================================= -->
    <div x-show="orderModal" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white dark:bg-[#131b2e] rounded-3xl p-6 max-w-2xl w-full border border-gray-100 dark:border-white/10 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/10">
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">🛍️ Riwayat Pembelian Pengguna</h3>
                    <p class="text-xs text-blue-500 font-bold" x-text="selectedUser ? selectedUser.name : ''"></p>
                </div>
                <button type="button" @click="orderModal = false" class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-400 hover:text-gray-700 dark:hover:text-white flex items-center justify-center cursor-pointer">✕</button>
            </div>

            <div class="max-h-96 overflow-y-auto overscroll-contain space-y-2.5 pr-1 custom-scrollbar">
                <template x-if="userOrders.length === 0">
                    <p class="text-center py-8 text-xs text-gray-400 italic">Pengguna ini belum memiliki riwayat pesanan.</p>
                </template>

                <template x-for="(ord, idx) in userOrders" :key="idx">
                    <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200/60 dark:border-white/5 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-[11px] font-black text-orange-500" x-text="'#' + ord.invoice"></span>
                            <span class="text-[10px] text-gray-400" x-text="ord.date"></span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-gray-900 dark:text-white" x-text="ord.product"></p>
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 shrink-0" x-text="formatRp(ord.price)"></span>
                        </div>
                        <div class="p-2 rounded-lg bg-white dark:bg-black/40 border border-gray-100 dark:border-white/5 font-mono text-[10px] text-cyan-600 dark:text-cyan-400 break-all" x-text="'SN / Akun: ' + ord.sn"></div>
                    </div>
                </template>
            </div>

            <div class="pt-2 border-t border-gray-100 dark:border-white/10 text-right">
                <button type="button" @click="orderModal = false" class="px-5 py-2 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-bold text-gray-600 dark:text-gray-300 cursor-pointer">Tutup</button>
            </div>
        </div>
    </div>

</div>
@endsection

