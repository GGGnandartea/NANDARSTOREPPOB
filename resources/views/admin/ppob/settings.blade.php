@extends('layouts.admin')

@section('title', request('section') === 'cutoff' ? 'Jam Cut-Off & Maintenance PPOB' : 'Konfigurasi Kiosbank PPOB')

@section('content')
@php
    $activeSection = request('section', 'credentials');
    $cutoff = $cutoffStatus ?? (new \App\Services\KiosbankService())->getCutoffStatus();
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header & Navigasi Cepat -->
    <div class="admin-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">Konfigurasi &amp; Keamanan Kiosbank PPOB</h1>
                <span class="text-xs font-black px-2.5 py-1 rounded-full {{ $mode === 'production' ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-slate-950' }}">
                    Mode: {{ ucfirst($mode) }}
                </span>
                @if($cutoff['is_cutoff'] ?? false)
                    <span class="text-xs font-black px-2.5 py-1 rounded-full bg-rose-500 text-white animate-pulse">
                        ⏰ Cut-Off / Maintenance Aktif
                    </span>
                @else
                    <span class="text-xs font-black px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800/50">
                        ● Transaksi PPOB Normal ({{ $cutoff['current_time_wib'] ?? now('Asia/Jakarta')->format('H:i') }} WIB)
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                Kelola kredensial Webservice V2 Kiosbank serta pengaman Jam Cut-Off Harian ({{ $cutoff['cutoff_start'] ?? '23:50' }} - {{ $cutoff['cutoff_end'] ?? '00:10' }} WIB).
            </p>
            <div class="flex flex-wrap gap-3 pt-1 text-xs font-black text-cyan-600 dark:text-cyan-400">
                <a href="{{ route('admin.ppob.settings') }}" class="px-3 py-1.5 rounded-xl {{ $activeSection !== 'cutoff' ? 'bg-cyan-600 text-white' : 'bg-cyan-50 dark:bg-cyan-950/40 hover:underline' }}">
                    🔑 Kredensial Kiosbank
                </a>
                <a href="{{ route('admin.ppob.settings', ['section' => 'cutoff']) }}" class="px-3 py-1.5 rounded-xl {{ $activeSection === 'cutoff' ? 'bg-amber-500 text-slate-950' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 hover:underline' }}">
                    ⏰ Jam Cut-Off &amp; Maintenance ({{ $cutoff['cutoff_start'] ?? '23:50' }} - {{ $cutoff['cutoff_end'] ?? '00:10' }})
                </a>
                <a href="{{ route('admin.ppob.products') }}" class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:underline">
                    📱 Produk &amp; Margin
                </a>
                <a href="{{ route('admin.ppob.deposit') }}" class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:underline">
                    💰 Saldo Deposit
                </a>
                <a href="https://ppob.kiosbank.com:4040/api/webservicesV2/" target="_blank" rel="noopener noreferrer" class="px-3 py-1.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:underline">
                    📄 Dokumentasi Webservice V2 ↗
                </a>
            </div>
        </div>

        <form action="{{ route('admin.ppob.deposit.check_live') }}" method="POST" class="shrink-0">
            @csrf
            <button class="px-4 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-xs shadow-md shadow-cyan-600/20 cursor-pointer transition">
                🔄 Cek Saldo Mode Aktif
            </button>
        </form>
    </div>

    @if(session('success'))
        <div role="status" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/40 text-emerald-800 dark:text-emerald-200 font-bold text-sm flex items-center gap-3">
            <span class="w-7 h-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black shrink-0">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div role="alert" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-200 font-bold text-sm flex items-center gap-3">
            <span class="w-7 h-7 rounded-xl bg-rose-500 text-white flex items-center justify-center font-black shrink-0">✕</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div role="alert" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/40 text-rose-800 dark:text-rose-200 text-xs font-bold">
            <ul class="list-disc pl-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- KARTU PENGATURAN STATUS LAYANAN PPOB (ONLINE / OFFLINE / MAINTENANCE DLL) -->
    <!-- ========================================================================= -->
    @php
        $currentStatusMode = old('ppob_status_mode', $cutoff['status_mode'] ?? 'online');
        $currentStatusTitle = old('ppob_status_title', $cutoff['status_title'] ?? '');
        $currentStatusEta = old('ppob_status_eta', $cutoff['status_eta'] ?? '');
        $currentStatusMessage = old('ppob_cutoff_message', $cutoff['message'] ?? '');
    @endphp
    <div id="cutoff-section"
         x-data="{
            statusMode: @js($currentStatusMode),
            statusTitle: @js($currentStatusTitle),
            statusEta: @js($currentStatusEta),
            statusMessage: @js($currentStatusMessage),
            presets: {
                online: {
                    title: 'Layanan PPOB Aktif & Normal',
                    eta: 'Aktif 24 Jam (Kecuali Cut-Off Harian)',
                    message: 'Layanan PPOB sedang dalam jam rekonsiliasi / cut-off harian pusat (23:50 - 00:10 WIB). Transaksi akan kembali aktif otomatis setelah pukul 00:10 WIB.'
                },
                offline: {
                    title: 'Layanan PPOB Sedang Offline',
                    eta: 'Segera Kembali Aktif',
                    message: 'Mohon maaf, layanan transaksi PPOB (Pulsa, Paket Data, Token PLN & Tagihan) saat ini sedang dinonaktifkan sementara (Offline). Pembelian Produk Aplikasi Premium tetap buka dan diproses otomatis seperti biasa.'
                },
                maintenance: {
                    title: 'Layanan PPOB Sedang Maintenance',
                    eta: 'Estimasi ± 30 - 60 Menit',
                    message: 'Kami sedang melakukan pemeliharaan dan peningkatan sistem layanan PPOB agar transaksi lebih cepat & stabil. Mohon coba kembali beberapa saat lagi.'
                },
                gangguan: {
                    title: 'Gangguan Server Biller Pusat',
                    eta: 'Menunggu Pemulihan Server Pusat',
                    message: 'Saat ini jalur transaksi dari provider / biller pusat sedang mengalami kendala jaringan nasional. Demi keamanan saldo Anda, transaksi PPOB kami tahan sementara hingga jalur kembali stabil.'
                },
                restock: {
                    title: 'Sedang Restock Saldo Deposit Pusat',
                    eta: 'Estimasi ± 15 - 30 Menit',
                    message: 'Kami sedang melakukan penambahan / pengisian ulang saldo deposit pada sistem pusat PPOB. Layanan akan segera dibuka kembali dalam beberapa menit.'
                }
            },
            selectMode(mode) {
                this.statusMode = mode;
                if (this.presets[mode]) {
                    this.statusTitle = this.presets[mode].title;
                    this.statusEta = this.presets[mode].eta;
                    this.statusMessage = this.presets[mode].message;
                }
            }
         }"
         class="admin-card p-6 sm:p-7 border-t-4 {{ ($cutoff['is_cutoff'] ?? false) ? 'border-t-rose-500' : 'border-t-emerald-500' }} space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl {{ ($cutoff['is_cutoff'] ?? false) ? 'bg-rose-500 text-white' : 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30' }} flex items-center justify-center text-lg font-black shrink-0">
                    {{ ($cutoff['is_cutoff'] ?? false) ? 'OFF' : 'ON' }}
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">
                        <span>Kontrol Status Layanan PPOB (On / Off / Maintenance)</span>
                        @if(($cutoff['is_cutoff'] ?? false))
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-rose-500 text-white">
                                {{ $cutoff['status_badge'] ?? 'SEDANG OFF' }}
                            </span>
                        @else
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">
                                LAYANAN PPOB ONLINE
                            </span>
                        @endif
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Pilih status layanan PPOB saat ini. Jika disetel ke <strong>Offline / Maintenance / Gangguan / Restock</strong>, pelanggan yang mengklik menu PPOB di HP maupun Desktop akan melihat pop-up pemberitahuan profesional.
                    </p>
                </div>
            </div>
            <div class="text-left sm:text-right shrink-0">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 block">Waktu Server (WIB)</span>
                <span class="text-sm font-black font-mono text-gray-900 dark:text-white">{{ now('Asia/Jakarta')->format('H:i:s') }} WIB</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ppob.settings.cutoff') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="ppob_status_mode" :value="statusMode">

            <!-- PILIHAN MODE STATUS PPOB (5 KARTU INTERAKTIF) -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <label class="block text-xs font-black uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        1. Pilih Mode Status Layanan PPOB (Klik Kartu untuk Mengubah Otomatis)
                    </label>
                    <span class="text-[11px] font-bold text-gray-400">Teks judul &amp; pesan otomatis menyesuaikan pilihan, dan tetap bisa Anda edit bebas</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Mode 1: ONLINE -->
                    <button type="button"
                            @click="selectMode('online')"
                            :class="statusMode === 'online'
                                ? 'ring-2 ring-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 border-emerald-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/70 border-gray-200 dark:border-white/10 hover:border-emerald-400'"
                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between gap-2 cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-500 text-white">
                                <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                ONLINE
                            </span>
                            <span x-show="statusMode === 'online'" class="text-xs font-black text-emerald-600 dark:text-emerald-400">✓ Aktif</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">Normal / Aktif</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">Buka semua transaksi PPOB seperti biasa.</p>
                        </div>
                    </button>

                    <!-- Mode 2: OFFLINE -->
                    <button type="button"
                            @click="selectMode('offline')"
                            :class="statusMode === 'offline'
                                ? 'ring-2 ring-rose-500 bg-rose-50 dark:bg-rose-950/40 border-rose-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/70 border-gray-200 dark:border-white/10 hover:border-rose-400'"
                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between gap-2 cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-rose-500 text-white">
                                OFFLINE
                            </span>
                            <span x-show="statusMode === 'offline'" class="text-xs font-black text-rose-600 dark:text-rose-400">✓ Dipilih</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">Sedang Offline</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">Matikan sementara seluruh menu PPOB.</p>
                        </div>
                    </button>

                    <!-- Mode 3: MAINTENANCE -->
                    <button type="button"
                            @click="selectMode('maintenance')"
                            :class="statusMode === 'maintenance'
                                ? 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-950/40 border-amber-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/70 border-gray-200 dark:border-white/10 hover:border-amber-400'"
                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between gap-2 cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500 text-slate-950">
                                MAINTENANCE
                            </span>
                            <span x-show="statusMode === 'maintenance'" class="text-xs font-black text-amber-600 dark:text-amber-400">✓ Dipilih</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">Sedang Maintenance</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">Pemeliharaan rutin atau update sistem PPOB.</p>
                        </div>
                    </button>

                    <!-- Mode 4: GANGGUAN PUSAT -->
                    <button type="button"
                            @click="selectMode('gangguan')"
                            :class="statusMode === 'gangguan'
                                ? 'ring-2 ring-red-500 bg-red-50 dark:bg-red-950/40 border-red-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/70 border-gray-200 dark:border-white/10 hover:border-red-400'"
                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between gap-2 cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-red-600 text-white">
                                GANGGUAN
                            </span>
                            <span x-show="statusMode === 'gangguan'" class="text-xs font-black text-red-600 dark:text-red-400">✓ Dipilih</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">Gangguan Server Pusat</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">Biller / provider pusat sedang kendala nasional.</p>
                        </div>
                    </button>

                    <!-- Mode 5: RESTOCK DEPOSIT -->
                    <button type="button"
                            @click="selectMode('restock')"
                            :class="statusMode === 'restock'
                                ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950/40 border-blue-500/60'
                                : 'bg-gray-50 dark:bg-gray-900/70 border-gray-200 dark:border-white/10 hover:border-blue-400'"
                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between gap-2 cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-600 text-white">
                                RESTOCK
                            </span>
                            <span x-show="statusMode === 'restock'" class="text-xs font-black text-blue-600 dark:text-blue-400">✓ Dipilih</span>
                        </div>
                        <div>
                            <p class="text-xs font-black text-gray-900 dark:text-white">Restock Saldo Pusat</p>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">Sedang isi ulang saldo deposit provider PPOB.</p>
                        </div>
                    </button>
                </div>
            </div>

            <!-- DETAIL TAMPILAN POP-UP / INFORMASI KE USER -->
            <div class="p-4 sm:p-5 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Judul Status pada Pop-up / Halaman PPOB
                        </label>
                        <input type="text"
                               name="ppob_status_title"
                               x-model="statusTitle"
                               maxlength="120"
                               placeholder="Contoh: Layanan PPOB Sedang Maintenance"
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-black dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    <div>
                        <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                            Keterangan Estimasi Waktu / Status (ETA)
                        </label>
                        <input type="text"
                               name="ppob_status_eta"
                               x-model="statusEta"
                               maxlength="120"
                               placeholder="Contoh: Estimasi ± 30 Menit / Aktif kembali pukul 00:10 WIB"
                               class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                        Pesan Penjelasan ke Pelanggan (Tampil di Modal HP &amp; Desktop)
                    </label>
                    <textarea
                        name="ppob_cutoff_message"
                        x-model="statusMessage"
                        rows="3"
                        maxlength="600"
                        placeholder="Tuliskan alasan penutupan sementara layanan PPOB..."
                        class="w-full px-4 py-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500"
                    ></textarea>
                </div>
            </div>

            <!-- PENGATURAN JADWAL CUT-OFF HARIAN & AKSES KATALOG -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sakelar 1: Jadwal Cut-Off Harian Otomatis -->
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-white/10 space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="ppob_cutoff_enabled" value="0">
                        <input type="checkbox" name="ppob_cutoff_enabled" value="1" @checked($cutoff['cutoff_enabled'] ?? true) class="mt-1 w-4 h-4 rounded text-amber-500 focus:ring-amber-500">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white block">Aktifkan Jam Cut-Off Otomatis Harian</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Saat mode di atas <strong>ONLINE</strong>, sistem tetap otomatis menahan transaksi setiap malam pada jam rekonsiliasi berikut dan membukanya kembali otomatis.
                            </span>
                        </div>
                    </label>

                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200/70 dark:border-white/10">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Jam Mulai Cut-Off (WIB)</label>
                            <input type="time" name="ppob_cutoff_start" value="{{ old('ppob_cutoff_start', $cutoff['cutoff_start'] ?? '23:50') }}" required class="w-full px-3 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-black font-mono dark:text-white">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-wider text-gray-400 mb-1">Jam Selesai Cut-Off (WIB)</label>
                            <input type="time" name="ppob_cutoff_end" value="{{ old('ppob_cutoff_end', $cutoff['cutoff_end'] ?? '00:10') }}" required class="w-full px-3 py-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-black font-mono dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Sakelar 2: Opsi Akses Katalog Saat PPOB Off -->
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-white/10 space-y-3 flex flex-col justify-between">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="ppob_allow_catalog_browse" value="0">
                        <input type="checkbox" name="ppob_allow_catalog_browse" value="1" @checked($cutoff['allow_catalog_browse'] ?? true) class="mt-1 w-4 h-4 rounded text-cyan-600 focus:ring-cyan-500">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white block">Izinkan Lihat Daftar Harga Katalog Saat Off</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Jika dicentang, pelanggan yang melihat pop-up Offline/Maintenance memiliki tombol opsional <strong>"Lihat Katalog Harga (Mode Baca)"</strong> namun tombol Beli/Bayar tetap terkunci aman.
                            </span>
                        </div>
                    </label>

                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-black/30 border border-gray-200/60 dark:border-white/5 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                        Tip: Anda juga bisa menampilkan pengumuman geser di bagian atas website melalui menu <a href="{{ route('admin.settings', ['section' => 'announcement']) }}" class="text-orange-500 font-black underline">Pengumuman Atas</a>.
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-xl gradient-orange text-white font-black text-xs shadow-lg shadow-orange-500/20 hover:opacity-95 transition cursor-pointer flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan Status &amp; Pengaturan PPOB</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- FORMULIR KREDENSIAL AKUN KIOSBANK (DEVELOPMENT & PRODUCTION)              -->
    <!-- ========================================================================= -->
    <form method="POST" action="{{ route('admin.ppob.settings.update') }}" class="space-y-6">
        @csrf
        <div class="admin-card p-6 space-y-4">
            <label for="kiosbank-mode" class="block font-bold dark:text-white">Mode transaksi</label>
            <select id="kiosbank-mode" name="mode" class="w-full p-3 border rounded-xl bg-white dark:bg-gray-900 dark:text-white">
                <option value="development" @selected(old('mode', $mode) === 'development')>Development — Pengujian</option>
                <option value="production" @selected(old('mode', $mode) === 'production')>Production — Transaksi nyata</option>
            </select>
            <p class="text-sm text-amber-700 dark:text-amber-300">Production memakai saldo dan transaksi nyata. Pastikan IP server sudah didaftarkan ke Kiosbank. Simulasi hanya tersedia di Development.</p>
            <input type="hidden" name="enabled" value="0">
            <label class="flex items-center gap-2 font-bold dark:text-white"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $enabled))> Aktifkan layanan PPOB</label>
        </div>

        <div class="grid lg:grid-cols-2 gap-6">
            @foreach($profiles as $profileMode => $profile)
                <fieldset class="admin-card p-6 space-y-4 min-w-0">
                    <legend class="font-black text-lg dark:text-white px-2">{{ ucfirst($profileMode) }}</legend>
                    <p class="text-xs font-mono break-all text-cyan-600">https://{{ $profile['host'] }}:{{ $profile['port'] }}</p>
                    @foreach(['username' => 'User', 'password' => 'Password', 'mitra' => 'Mitra', 'merchant_id' => 'Merchant ID', 'account_id' => 'Account ID', 'counter_id' => 'Counter ID', 'merchant_name' => 'Nama Merchant'] as $field => $label)
                        <div>
                            <label for="{{ $profileMode }}-{{ $field }}" class="block text-sm font-bold mb-1 dark:text-gray-200">{{ $label }}</label>
                            <input id="{{ $profileMode }}-{{ $field }}" type="{{ $field === 'password' ? 'password' : 'text' }}" name="profiles[{{ $profileMode }}][{{ $field }}]"
                                value="{{ $field === 'password' ? '' : old('profiles.'.$profileMode.'.'.$field, $profile[$field]) }}"
                                autocomplete="{{ $field === 'password' ? 'new-password' : 'off' }}"
                                placeholder="{{ $field === 'password' && $profile['password_configured'] ? 'Tersimpan — kosongkan untuk mempertahankan' : $label }}"
                                @required($field !== 'password') class="w-full p-3 border rounded-xl bg-white dark:bg-gray-900 dark:text-white text-sm">
                        </div>
                    @endforeach
                    <details class="text-sm dark:text-gray-200">
                        <summary class="cursor-pointer font-bold">Kunci RSA (opsional, bila terdaftar di Kiosbank)</summary>
                        <label for="{{ $profileMode }}-private-key" class="block mt-3">Private key {{ $profile['private_key_configured'] ? '(tersimpan)' : '' }}</label>
                        <textarea id="{{ $profileMode }}-private-key" name="profiles[{{ $profileMode }}][private_key]" rows="3" placeholder="Kosongkan untuk mempertahankan kunci yang tersimpan" class="w-full p-2 border rounded bg-white dark:bg-gray-900 font-mono text-xs"></textarea>
                        <label for="{{ $profileMode }}-public-key" class="block mt-3">Public key</label>
                        <textarea id="{{ $profileMode }}-public-key" name="profiles[{{ $profileMode }}][public_key]" rows="3" class="w-full p-2 border rounded bg-white dark:bg-gray-900 font-mono text-xs">{{ old('profiles.'.$profileMode.'.public_key', $profile['public_key']) }}</textarea>
                    </details>
                </fieldset>
            @endforeach
        </div>
        <button type="submit" class="px-6 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-black cursor-pointer">Simpan Konfigurasi &amp; Mode</button>
    </form>
</div>
@endsection
