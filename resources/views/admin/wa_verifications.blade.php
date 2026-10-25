@extends('layouts.admin')

@php
    $activeSection = request('section', 'verifications');
@endphp

@section('title', $activeSection === 'gateway' ? 'Gateway WA' : 'Verifikasi WA')

@section('content')
<div class="max-w-7xl mx-auto space-y-6" x-data="{
    botStatus: 'checking',
    botConnected: false,
    botPhone: '',
    qrCodeImg: '',
    testModalOpen: false,
    settingsModalOpen: false,
    editPhoneModalOpen: false,
    editUserId: '',
    editUserName: '',
    editUserPhone: '',
    editUserVerified: true,
    checkInterval: null,
    
    async fetchStatus() {
        try {
            const res = await fetch('{{ route('admin.wa_gateway.status') }}');
            if (res.ok) {
                const data = await res.json();
                this.botStatus = data.status;
                this.botConnected = data.connected;
                this.botPhone = data.phone || '';
                this.qrCodeImg = data.qr || '';
            } else {
                this.botStatus = 'offline';
                this.botConnected = false;
            }
        } catch (e) {
            this.botStatus = 'offline';
            this.botConnected = false;
        }
    },
    init() {
        @if($activeSection === 'gateway')
            this.fetchStatus();
            this.checkInterval = setInterval(() => {
                this.fetchStatus();
            }, 3000);
        @endif
    }
}">

    <!-- Alert Notifikasi -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</div>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    
    @if(session('error') || $errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</div>
            <div>{{ session('error') ?? $errors->first() }}</div>
        </div>
    @endif

    @if($activeSection === 'gateway')
        <!-- ======================================================== -->
        <!-- SECTION: GATEWAY WHATSAPP BOT MANDIRI & SCAN QR          -->
        <!-- ======================================================== -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Gateway WhatsApp Bot</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-green text-white shadow-sm shadow-emerald-500/20">
                        Node.js Service
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">
                    Kelola koneksi perangkat WhatsApp Bot, scan QR Code login, uji kirim pesan, dan konfigurasi URL endpoint bot.
                </p>
            </div>

            <a href="{{ route('admin.wa_verifications', ['section' => 'verifications']) }}" class="px-4 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-300 hover:border-emerald-500 transition flex items-center gap-2 self-start md:self-auto">
                <span>Data Verifikasi User ({{ count($pendingUsers) }} Pending)</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
        </div>

        <div class="admin-card p-6 relative overflow-hidden">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                
                <div class="space-y-2 flex-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-xl shadow-md"
                             :class="{
                                 'bg-emerald-500 text-white': botConnected,
                                 'bg-amber-500 text-white animate-pulse': botStatus === 'qr_ready' || botStatus === 'connecting',
                                 'bg-rose-500 text-white': botStatus === 'offline',
                                 'bg-gray-500 text-white': botStatus === 'checking'
                             }">
                            <span x-show="botConnected">🟢</span>
                            <span x-show="botStatus === 'qr_ready'">📱</span>
                            <span x-show="botStatus === 'connecting' || botStatus === 'checking'">⏳</span>
                            <span x-show="botStatus === 'offline'">🔴</span>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-black text-gray-900 dark:text-white tracking-tight">
                                    WhatsApp Bot Gateway Mandiri
                                </h2>
                                <template x-if="botConnected">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        ONLINE &amp; TERHUBUNG
                                    </span>
                                </template>
                                <template x-if="botStatus === 'qr_ready'">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400 border border-amber-300 dark:border-amber-800 animate-pulse">
                                        MENUNGGU SCAN QR
                                    </span>
                                </template>
                                <template x-if="botStatus === 'connecting'">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400">
                                        MENYAMBUNGKAN...
                                    </span>
                                </template>
                                <template x-if="botStatus === 'offline'">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400">
                                        SERVICE OFFLINE
                                    </span>
                                </template>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Otomatis memvalidasi kode verifikasi chat &amp; mengirim akun premium setelah pembayaran lunas.
                            </p>
                        </div>
                    </div>

                    <template x-if="botConnected">
                        <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/40 text-xs text-emerald-900 dark:text-emerald-300 space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold">Nomor WhatsApp Bot:</span>
                                <span class="font-mono font-black text-sm text-emerald-600 dark:text-emerald-400" x-text="'+' + botPhone"></span>
                            </div>
                            <p class="text-[11px] opacity-90">
                                ✓ Bot aktif standby. Saat pembeli mengirim pesan berformat kode <code class="bg-emerald-100 dark:bg-emerald-900 px-1 py-0.5 rounded font-mono font-bold">NS-XXXXX</code>, nomor mereka langsung diverifikasi otomatis tanpa perlu konfirmasi manual!
                            </p>
                        </div>
                    </template>

                    <template x-if="botStatus === 'offline'">
                        <div class="p-3.5 rounded-2xl bg-rose-50/60 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 text-xs text-rose-900 dark:text-rose-300 space-y-1">
                            <p class="font-bold">⚠️ Service Bot Belum Berjalan di Port 5001</p>
                            <p class="text-[11px] opacity-90">
                                Jalankan file <code class="bg-rose-100 dark:bg-rose-900/50 px-1.5 py-0.5 rounded font-mono font-bold">c:\xampp\htdocs\wa-gateway\start-wa-gateway.bat</code> di laptop Anda atau ketik <code class="font-mono font-bold">node server.js</code> di folder wa-gateway.
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Tombol Aksi Gateway -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0 w-full lg:w-auto">
                    <template x-if="botConnected">
                        <div class="flex items-center gap-2">
                            <button 
                                type="button" 
                                @click="testModalOpen = true"
                                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition cursor-pointer flex items-center gap-1.5 shadow-sm"
                            >
                                <span>Test Kirim Chat</span>
                            </button>
                            <form action="{{ route('admin.wa_gateway.logout') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memutus koneksi bot ini? Anda harus scan QR ulang.')">
                                @csrf
                                <button 
                                    type="submit" 
                                    class="px-4 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-950/40 text-gray-700 dark:text-gray-300 font-bold text-xs transition cursor-pointer"
                                >
                                    Ganti Nomor / Logout
                                </button>
                            </form>
                        </div>
                    </template>

                    <button 
                        type="button" 
                        @click="settingsModalOpen = true"
                        class="px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold transition cursor-pointer"
                        title="Pengaturan URL Gateway"
                    >
                        URL Gateway
                    </button>
                    <button 
                        type="button" 
                        @click="fetchStatus()"
                        class="px-3.5 py-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold transition cursor-pointer"
                        title="Refresh Status"
                    >
                        Refresh
                    </button>
                </div>

            </div>

            <!-- TAMPILAN SCAN QR CODE LANGSUNG DI HALAMAN INI JIKA BELUM LOGIN -->
            <template x-if="botStatus === 'qr_ready' && qrCodeImg">
                <div class="mt-6 pt-6 border-t border-gray-100 dark:border-white/5 flex flex-col md:flex-row items-center justify-center gap-8 bg-gray-50/50 dark:bg-black/20 p-6 rounded-2xl">
                    <div class="p-3 bg-white rounded-3xl shadow-xl border-4 border-emerald-500/30 flex items-center justify-center shrink-0 animate-in fade-in">
                        <img :src="qrCodeImg" alt="Scan QR Code WhatsApp" class="w-56 h-56 rounded-2xl select-none">
                    </div>

                    <div class="space-y-3 max-w-md text-left">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-lg bg-emerald-500 text-white font-black text-xs">LANGKAH SCAN</span>
                            <h4 class="font-black text-base text-gray-900 dark:text-white">Tautkan Nomor WhatsApp Anda</h4>
                        </div>
                        <ol class="list-decimal list-inside space-y-1.5 text-xs text-gray-600 dark:text-gray-300 leading-relaxed font-medium">
                            <li>Buka aplikasi <strong>WhatsApp</strong> di HP Anda.</li>
                            <li>Ketuk <strong>Menu (⋮)</strong> di Android atau <strong>Pengaturan</strong> di iPhone.</li>
                            <li>Pilih <strong>Perangkat Tertaut</strong>, lalu ketuk <strong>Tautkan Perangkat</strong>.</li>
                            <li>Arahkan kamera HP ke <strong>QR Code</strong> di sebelah kiri ini.</li>
                        </ol>
                        <p class="text-[11px] text-amber-600 dark:text-amber-400 font-bold flex items-center gap-1.5">
                            <span class="animate-spin inline-block">⏳</span> QR Code otomatis diperbarui secara berkala.
                        </p>
                    </div>
                </div>
            </template>
        </div>

        <!-- MODAL TEST KIRIM PESAN WHATSAPP -->
        <div 
            x-show="testModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
            @keydown.escape.window="testModalOpen = false"
        >
            <div 
                class="bg-white dark:bg-gray-900 rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 dark:border-white/10 space-y-4"
                @click.away="testModalOpen = false"
            >
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Test Kirim Pesan WhatsApp</h3>
                    <button @click="testModalOpen = false" class="p-1 text-gray-400 hover:text-gray-600 text-lg cursor-pointer">✕</button>
                </div>

                <form action="{{ route('admin.wa_gateway.test_send') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Nomor WhatsApp Tujuan</label>
                        <input 
                            type="text" 
                            name="target" 
                            placeholder="Contoh: 08123456789 atau 628123456789" 
                            required 
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">Isi Pesan Uji Coba</label>
                        <textarea 
                            name="message" 
                            rows="4" 
                            required 
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-medium dark:text-white outline-none focus:ring-2 focus:ring-emerald-500"
                        >Halo! Ini adalah pesan uji coba dari NandarStore WhatsApp Bot Gateway. Service bot berjalan normal.</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="testModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl gradient-green text-white text-xs font-black uppercase tracking-wider shadow-md hover:opacity-90 transition cursor-pointer">
                            Kirim Pesan Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL PENGATURAN URL GATEWAY -->
        <div 
            x-show="settingsModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
            @keydown.escape.window="settingsModalOpen = false"
        >
            <div 
                class="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-white/10 space-y-4"
                @click.away="settingsModalOpen = false"
            >
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <h3 class="font-black text-base text-gray-900 dark:text-white">Pengaturan URL Gateway</h3>
                    <button @click="settingsModalOpen = false" class="p-1 text-gray-400 hover:text-gray-600 text-lg cursor-pointer">✕</button>
                </div>

                <form action="{{ route('admin.wa_gateway.settings') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-wider mb-1">URL Service Bot Node.js</label>
                        <input 
                            type="url" 
                            name="wa_gateway_url" 
                            value="{{ $gatewayUrl }}" 
                            required 
                            class="w-full px-4 py-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-mono font-bold dark:text-white outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                        <p class="text-[10px] text-gray-400 mt-1">Default lokal: <code>http://127.0.0.1:5001</code></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="settingsModalOpen = false" class="px-4 py-2.5 rounded-xl text-xs font-bold text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                            Batal
                        </button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl gradient-green text-white text-xs font-black uppercase tracking-wider shadow-md hover:opacity-90 transition cursor-pointer">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    @else
        <!-- ======================================================== -->
        <!-- SECTION: VERIFIKASI WHATSAPP PENGGUNA                    -->
        <!-- ======================================================== -->
        <div class="admin-card p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Daftar Verifikasi WhatsApp</span>
                    <span class="text-xs font-black px-2.5 py-0.5 rounded-full gradient-green text-white shadow-sm shadow-emerald-500/20">
                        Auto / Manual
                    </span>
                </h1>
                <p class="text-xs font-semibold text-gray-400 mt-1">
                    Data verifikasi terkonfirmasi otomatis saat user mengirim chat ke bot, atau validasi manual lewat kode di bawah.
                </p>
            </div>

            <!-- Quick Code Validation Form -->
            <form action="{{ route('admin.wa_verifications.approve') }}" method="POST" class="w-full md:w-auto flex items-center gap-2">
                @csrf
                <input 
                    type="text" 
                    name="code" 
                    placeholder="Paste kode (NS-XXXXX)" 
                    required 
                    class="px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-mono font-black text-gray-800 dark:text-emerald-400 outline-none focus:ring-2 focus:ring-emerald-500 uppercase w-48 shadow-inner"
                >
                <button 
                    type="submit" 
                    class="px-4 py-2.5 gradient-green text-white font-black text-xs rounded-xl shadow-md transition hover:opacity-90 cursor-pointer shrink-0"
                >
                    ✓ Validasi Kode
                </button>
            </form>
        </div>

        <!-- DAFTAR PERMINTAAN VERIFIKASI PENDING -->
        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                    <h3 class="font-black text-sm text-gray-900 dark:text-white">
                        Permintaan Menunggu Validasi ({{ count($pendingUsers) }})
                    </h3>
                </div>
                <a href="{{ route('admin.wa_verifications', ['section' => 'gateway']) }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline">
                    Cek Status Gateway WA →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Pengguna &amp; Akun</th>
                            <th>Nomor WhatsApp</th>
                            <th>Kode Unik Chat</th>
                            <th>Waktu Request</th>
                            <th class="text-right">Aksi Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingUsers as $u)
                        <tr class="hover:bg-amber-50/20 dark:hover:bg-amber-950/10 transition">
                            <td>
                                <p class="font-black text-xs text-gray-900 dark:text-white">{{ $u->name }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $u->email }}</p>
                            </td>
                            <td>
                                <p class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{ $u->whatsapp }}</p>
                                <a 
                                    href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $u->whatsapp) }}" 
                                    target="_blank" 
                                    class="text-[10px] font-bold text-emerald-500 hover:underline inline-flex items-center gap-1 mt-0.5"
                                >
                                    <span>Buka Chat WhatsApp</span> ↗
                                </a>
                            </td>
                            <td>
                                <span class="px-3 py-1.5 bg-black/50 border border-emerald-500/40 text-emerald-400 font-mono font-black text-sm rounded-xl inline-block shadow-inner select-all">
                                    {{ $u->wa_verification_code }}
                                </span>
                            </td>
                            <td class="text-gray-400 text-xs font-medium">
                                {{ $u->updated_at ? $u->updated_at->format('d M Y, H:i') : '-' }} WIB
                            </td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button 
                                        type="button" 
                                        @click="editUserId = '{{ $u->id }}'; editUserName = '{{ addslashes($u->name) }}'; editUserPhone = '{{ $u->whatsapp }}'; editUserVerified = false; editPhoneModalOpen = true;"
                                        class="px-2.5 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-orange-100 hover:text-orange-600 dark:hover:bg-orange-950/40 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl transition cursor-pointer"
                                        title="Edit nomor WhatsApp"
                                    >
                                        Edit
                                    </button>
                                    <form action="{{ route('admin.wa_verifications.approve') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <button 
                                            type="submit" 
                                            class="px-3.5 py-1.5 gradient-green text-white font-black text-xs rounded-xl shadow-md transition hover:opacity-90 cursor-pointer"
                                            title="Validasi manual"
                                        >
                                            ✓ Setujui &amp; Validasi
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.wa_verifications.reject') }}" method="POST" class="inline" onsubmit="return confirm('Tolak permintaan verifikasi ini?')">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                        <button 
                                            type="submit" 
                                            class="px-3 py-1.5 bg-gray-100 dark:bg-gray-800 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-950/40 text-gray-600 dark:text-gray-300 font-bold text-xs rounded-xl transition cursor-pointer"
                                        >
                                            ✕ Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-400 font-semibold text-xs">
                                Tidak ada permintaan verifikasi yang menunggu. Semua akun sudah tervalidasi!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DAFTAR PENGGUNA TERVERIFIKASI -->
        <div class="admin-card overflow-hidden">
            <div class="p-5 border-b border-gray-100 dark:border-white/5 flex items-center justify-between bg-gray-50/50 dark:bg-black/20">
                <h3 class="font-black text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <span>✓</span> Pengguna Terverifikasi ({{ count($verifiedUsers) }})
                </h3>
                <span class="text-[11px] text-gray-400 font-semibold">Notifikasi &amp; pesanan otomatis aktif untuk nomor-nomor ini</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left admin-table whitespace-nowrap">
                    <thead>
                        <tr>
                            <th>Pengguna &amp; Akun</th>
                            <th>Nomor WhatsApp</th>
                            <th>Tanggal Terverifikasi</th>
                            <th>Status</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($verifiedUsers as $vu)
                        <tr>
                            <td>
                                <p class="font-black text-xs text-gray-900 dark:text-white">{{ $vu->name }}</p>
                                <p class="text-[10px] text-gray-400 font-mono">{{ $vu->email }}</p>
                            </td>
                            <td class="font-mono text-xs font-bold text-gray-900 dark:text-white">
                                {{ $vu->whatsapp }}
                            </td>
                            <td class="text-gray-400 text-xs font-medium">
                                {{ $vu->whatsapp_verified_at ? $vu->whatsapp_verified_at->format('d M Y, H:i') : '-' }} WIB
                            </td>
                            <td>
                                <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-black text-[10px] border border-emerald-300">
                                    ✓ TERVERIFIKASI
                                </span>
                            </td>
                            <td class="text-right">
                                <div class="inline-flex items-center gap-3">
                                    <button 
                                        type="button" 
                                        @click="editUserId = '{{ $vu->id }}'; editUserName = '{{ addslashes($vu->name) }}'; editUserPhone = '{{ $vu->whatsapp }}'; editUserVerified = true; editPhoneModalOpen = true;"
                                        class="text-xs text-orange-500 hover:text-orange-600 hover:underline font-bold cursor-pointer"
                                    >
                                        Edit Nomor
                                    </button>
                                    <form action="{{ route('admin.wa_verifications.reject') }}" method="POST" class="inline" onsubmit="return confirm('Cabut status verifikasi akun ini?')">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $vu->id }}">
                                        <button type="submit" class="text-xs text-red-500 hover:underline font-bold cursor-pointer">
                                            Cabut
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400 text-xs">Belum ada pengguna yang diverifikasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDIT NOMOR WHATSAPP OLEH ADMIN -->
        <div 
            x-show="editPhoneModalOpen" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
            @keydown.escape.window="editPhoneModalOpen = false"
        >
            <div 
                class="bg-white dark:bg-gray-900 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-white/10 space-y-4"
                @click.outside="editPhoneModalOpen = false"
            >
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div>
                        <h3 class="font-black text-sm text-gray-900 dark:text-white">Edit Nomor WhatsApp</h3>
                        <p class="text-[11px] text-gray-400">Akun: <span x-text="editUserName" class="font-bold text-gray-700 dark:text-gray-300"></span></p>
                    </div>
                    <button @click="editPhoneModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white cursor-pointer text-xl">✕</button>
                </div>

                <form action="{{ route('admin.wa_verifications.update_phone') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="user_id" :value="editUserId">

                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1.5">
                            Nomor WhatsApp Baru
                        </label>
                        <input 
                            type="text" 
                            name="whatsapp" 
                            x-model="editUserPhone" 
                            required 
                            placeholder="Contoh: 08123456789"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/30 text-xs font-mono font-bold dark:text-white focus:ring-2 focus:ring-orange-500 outline-none"
                        >
                        <p class="text-[10px] text-gray-400 mt-1">Gunakan nomor ponsel aktif diawali 08 atau 628.</p>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input 
                            type="checkbox" 
                            name="is_verified" 
                            id="chk_is_verified" 
                            value="1" 
                            x-model="editUserVerified"
                            class="w-4 h-4 rounded text-orange-500 focus:ring-orange-500"
                        >
                        <label for="chk_is_verified" class="text-xs font-bold text-gray-700 dark:text-gray-300 cursor-pointer">
                            Tandai Langsung Sebagai Terverifikasi (Verified)
                        </label>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button 
                            type="button" 
                            @click="editPhoneModalOpen = false" 
                            class="px-4 py-2 text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 rounded-xl cursor-pointer"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 gradient-orange text-white font-black text-xs rounded-xl shadow-md hover:opacity-90 cursor-pointer"
                        >
                            Simpan Nomor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection
