@extends('layouts.admin')

@section('title', 'Pengaturan Reseller VIP & Promo Pendaftaran')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-16">
    <!-- Header Page -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-black uppercase tracking-wider text-amber-500 bg-amber-100 dark:bg-amber-950/60 px-2.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-800/40">
                    Mitra &amp; VIP
                </span>
                <span class="text-xs text-gray-400">•</span>
                <span class="text-xs font-bold text-gray-400">Pengaturan Biaya &amp; Promo</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight flex items-center gap-2.5">
                👑 Pengaturan Reseller VIP &amp; Promo
            </h1>
            <p class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                Atur biaya aktivasi reseller atau aktifkan mode promo pendaftaran gratis agar user bisa langsung mendaftar secara otomatis tanpa bayar QRIS.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('admin.resellers.settings.toggle_free') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-2xl font-black text-xs transition shadow-sm flex items-center gap-2 cursor-pointer {{ $isFree ? 'bg-amber-500 hover:bg-amber-600 text-gray-950 shadow-amber-500/20' : 'bg-emerald-500 hover:bg-emerald-600 text-white shadow-emerald-500/20' }}">
                    @if($isFree)
                        <span>🔒 Kembalikan ke Mode Berbayar</span>
                    @else
                        <span>1-Klik Aktifkan Promo Gratis</span>
                    @endif
                </button>
            </form>
            <a href="{{ route('admin.resellers') }}" class="px-4 py-2.5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-white/10 text-xs font-bold text-gray-700 dark:text-gray-200 hover:text-orange-500 transition shadow-xs flex items-center gap-1.5">
                <span>Daftar Mitra</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-bold flex items-center gap-2.5 animate-fade-in shadow-xs">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- QUICK STATS CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Status Promo -->
        <div class="admin-card p-4 sm:p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Status Pendaftaran</span>
                <span class="w-2.5 h-2.5 rounded-full {{ $isFree ? 'bg-emerald-500 animate-ping' : 'bg-amber-500' }}"></span>
            </div>
            <div class="mt-3">
                <span class="text-xl sm:text-2xl font-black {{ $isFree ? 'text-emerald-500' : 'text-amber-500' }}">
                    {{ $isFree ? 'GRATIS (PROMO)' : 'BERBAYAR' }}
                </span>
                <p class="text-[11px] text-gray-400 mt-0.5">
                    {{ $isFree ? 'User daftar langsung aktif otomatis' : 'Biaya Rp ' . number_format($fee, 0, ',', '.') }}
                </p>
            </div>
        </div>

        <!-- 2. Total Reseller -->
        <div class="admin-card p-4 sm:p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Total Reseller</span>
                <span class="text-base">👑</span>
            </div>
            <div class="mt-3">
                <span class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
                    {{ number_format($totalResellers) }}
                </span>
                <p class="text-[11px] text-gray-400 mt-0.5">Akun mitra VIP terdaftar</p>
            </div>
        </div>

        <!-- 3. Reseller Aktif -->
        <div class="admin-card p-4 sm:p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Mitra Aktif</span>
                <span class="text-emerald-500 text-xs font-black">● Aktif</span>
            </div>
            <div class="mt-3">
                <span class="text-xl sm:text-2xl font-black text-emerald-500">
                    {{ number_format($activeResellers) }}
                </span>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ number_format($pendingResellers) }} akun belum aktivasi</p>
            </div>
        </div>

        <!-- 4. Pendapatan Pendaftaran -->
        <div class="admin-card p-4 sm:p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-wider">Pemasukan Aktivasi</span>
                <span class="text-orange-500 text-base">💰</span>
            </div>
            <div class="mt-3">
                <span class="text-lg sm:text-xl font-black text-orange-500">
                    Rp {{ number_format($totalPaidRevenue, 0, ',', '.') }}
                </span>
                <p class="text-[11px] text-gray-400 mt-0.5">Total penerimaan via QRIS</p>
            </div>
        </div>
    </div>

    <!-- FORM SETTINGS & LIVE PREVIEW -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- KOLOM KIRI: FORM PENGATURAN -->
        <div class="lg:col-span-7 admin-card p-6 sm:p-8 space-y-6">
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-white/5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/15 text-amber-500 flex items-center justify-center font-black text-lg border border-amber-500/30">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="font-black text-base text-gray-900 dark:text-white">Konfigurasi Pendaftaran &amp; Biaya</h3>
                        <p class="text-[11px] text-gray-400">Atur status promo, biaya nominal, dan pesan sambutan pendaftaran</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('admin.resellers.settings.save') }}" method="POST" class="space-y-5">
                @csrf

                <!-- TOGGLE PROMO GRATIS -->
                <div class="p-4 rounded-2xl border transition-all {{ $isFree ? 'bg-emerald-500/10 border-emerald-500/30' : 'bg-gray-50 dark:bg-gray-900/50 border-gray-200 dark:border-white/10' }}">
                    <label class="flex items-start gap-3.5 cursor-pointer select-none">
                        <input type="checkbox" name="reseller_registration_free" value="1" {{ $isFree ? 'checked' : '' }} class="mt-1 w-5 h-5 text-emerald-500 rounded-lg border-gray-300 focus:ring-emerald-500 focus:ring-offset-0 cursor-pointer">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-black text-sm text-gray-900 dark:text-white">Aktifkan Pendaftaran Reseller VIP Gratis (Mode Promo)</span>
                                @if($isFree)
                                    <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-emerald-500 text-white uppercase animate-pulse">AKTIF</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                Jika diaktifkan, pendaftar baru yang memilih role Reseller VIP akan langsung aktif otomatis tanpa perlu membayar QRIS biaya pendaftaran. User yang sudah ada juga dapat upgrade ke Reseller secara gratis dari menu profil mereka.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- NOMINAL BIAYA PENDAFTARAN STANDAR -->
                <div>
                    <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Biaya Pendaftaran Standar (Saat Promo Tidak Aktif)
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center font-black text-gray-400 text-sm pointer-events-none">Rp</span>
                        <input type="number" name="reseller_registration_fee" min="0" step="1000" value="{{ $fee }}" required
                               class="w-full pl-12 pr-4 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-sm font-black text-gray-900 dark:text-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition outline-none">
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5">
                        *Nominal ini akan otomatis dibuatkan tagihan QRIS jika mode promo gratis tidak dicentang (standar: Rp 20.000).
                    </p>
                </div>

                <!-- JUDUL PROMO -->
                <div>
                    <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Judul Promo Pendaftaran
                    </label>
                    <input type="text" name="reseller_promo_title" value="{{ $promoTitle }}" required
                           class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-sm font-bold text-gray-900 dark:text-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition outline-none">
                </div>

                <!-- PESAN DESKRIPSI PROMO -->
                <div>
                    <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Pesan Deskripsi Promo &amp; Keuntungan
                    </label>
                    <textarea name="reseller_promo_message" rows="3" required
                              class="w-full p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-semibold text-gray-900 dark:text-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition outline-none">{{ $promoMessage }}</textarea>
                    <p class="text-[11px] text-gray-400 mt-1">
                        Pesan ini akan ditampilkan di banner atas form pendaftaran dan modal upgrade profil pengguna.
                    </p>
                </div>

                <!-- BADGE PROMO TEXT -->
                <div>
                    <label class="block text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                        Teks Badge Promo
                    </label>
                    <input type="text" name="reseller_promo_badge" value="{{ $promoBadge }}" required
                           placeholder="Misal: PROMO TERBATAS atau GRATIS"
                           class="w-full px-4 py-3 rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-sm font-bold text-gray-900 dark:text-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition outline-none">
                </div>

                <!-- BUTTON SUBMIT -->
                <div class="pt-3">
                    <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-amber-500 via-yellow-500 to-amber-600 hover:brightness-105 text-gray-950 font-black text-sm shadow-xl shadow-amber-500/20 transition cursor-pointer flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Pengaturan Reseller VIP
                    </button>
                </div>
            </form>
        </div>

        <!-- KOLOM KANAN: LIVE PREVIEW & PANDUAN -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- LIVE PREVIEW BANNER -->
            <div class="admin-card p-6 space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-white/5">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">👁️</span>
                        <h4 class="font-black text-xs uppercase tracking-wider text-gray-900 dark:text-white">Tampilan di Mata User (Preview)</h4>
                    </div>
                    <span class="text-[10px] font-black text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded-full">
                        {{ $isFree ? 'Mode Gratis' : 'Mode Berbayar' }}
                    </span>
                </div>

                <!-- Tampilan Card Preview -->
                <div class="bg-gray-950 text-white rounded-2xl p-5 border border-amber-500/30 space-y-3.5 shadow-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👑</span>
                            <span class="font-black text-xs text-amber-400 uppercase tracking-wider">{{ $promoBadge }}</span>
                        </div>
                        <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full {{ $isFree ? 'bg-emerald-500 text-white animate-pulse' : 'bg-amber-500/20 text-amber-300' }}">
                            {{ $isFree ? 'Rp 0 (GRATIS)' : 'Rp ' . number_format($fee, 0, ',', '.') }}
                        </span>
                    </div>

                    <h5 class="font-black text-sm text-white leading-tight">
                        {{ $promoTitle }}
                    </h5>

                    <p class="text-xs text-gray-300 leading-relaxed font-medium">
                        {{ $promoMessage }}
                    </p>

                    <div class="pt-2 border-t border-white/10 flex items-center justify-between text-[11px]">
                        <span class="text-gray-400">Status Pembayaran:</span>
                        <span class="font-bold {{ $isFree ? 'text-emerald-400' : 'text-amber-400' }}">
                            {{ $isFree ? '✓ Otomatis Aktif (Tanpa Bayar)' : 'Wajib Bayar QRIS' }}
                        </span>
                    </div>
                </div>

                <p class="text-[11px] text-gray-400 leading-relaxed">
                    💡 Saat mode gratis aktif, tombol pada halaman pendaftaran baru dan tombol upgrade di halaman profil user akan langsung mengaktifkan status Reseller VIP secara instan tanpa tagihan QRIS.
                </p>
            </div>

            <!-- PANDUAN FITUR -->
            <div class="admin-card p-6 space-y-3">
                <h4 class="font-black text-xs uppercase tracking-wider text-gray-900 dark:text-white flex items-center gap-2">
                    <span>📌</span> Panduan &amp; Cara Kerja Promo
                </h4>
                <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-2 list-disc list-inside leading-relaxed font-medium">
                    <li><strong class="text-gray-800 dark:text-gray-200">Mode Gratis Aktif:</strong> Pendaftar baru memilih role Reseller VIP langsung aktif <code class="text-emerald-500">is_active = true</code> dan langsung bisa login.</li>
                    <li><strong class="text-gray-800 dark:text-gray-200">Mode Berbayar Aktif:</strong> Pendaftar diarahkan ke pop-up pembayaran QRIS sesuai nominal yang telah Anda tentukan.</li>
                    <li><strong class="text-gray-800 dark:text-gray-200">Upgrade Profil:</strong> Pengguna biasa yang sudah memiliki akun dapat mengklaim upgrade ke VIP dengan 1-klik di halaman profil mereka.</li>
                    <li><strong class="text-gray-800 dark:text-gray-200">Keuntungan Reseller:</strong> Reseller secara otomatis mendapatkan harga khusus reseller (kolom reseller_price) pada seluruh produk.</li>
                </ul>
            </div>
        </div>

    </div>
</div>
@endsection

