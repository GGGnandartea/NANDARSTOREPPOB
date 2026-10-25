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
    <!-- KARTU PENGATURAN JAM CUT-OFF OTOMATIS (23:50 - 00:10 WIB) & MAINTENANCE    -->
    <!-- ========================================================================= -->
    <div id="cutoff-section" class="admin-card p-6 sm:p-7 border-t-4 {{ ($cutoff['is_cutoff'] ?? false) ? 'border-t-rose-500' : 'border-t-amber-500' }} space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-white/10">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl {{ ($cutoff['is_cutoff'] ?? false) ? 'bg-rose-500 text-white' : 'bg-amber-500/15 text-amber-500 border border-amber-500/30' }} flex items-center justify-center text-xl font-black shrink-0">
                    ⏰
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-gray-900 dark:text-white flex items-center gap-2 flex-wrap">
                        <span>Pengaman Jam Cut-Off Harian &amp; Maintenance PPOB</span>
                        @if($cutoff['maintenance_enabled'] ?? false)
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-rose-500 text-white">Mode Maintenance Manual Aktif</span>
                        @elseif($cutoff['in_schedule_window'] ?? false)
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-950">Sedang Jam Cut-Off Harian</span>
                        @elseif($cutoff['cutoff_enabled'] ?? true)
                            <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400">Jadwal Otomatis Aktif</span>
                        @endif
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Mencegah saldo pelanggan terpotong atau transaksi nyangkut (Pending) saat server pusat Kiosbank / PLN melakukan rekonsiliasi tengah malam.
                    </p>
                </div>
            </div>
            <div class="text-left sm:text-right shrink-0">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 block">Waktu Server (WIB)</span>
                <span class="text-sm font-black font-mono text-gray-900 dark:text-white">{{ now('Asia/Jakarta')->format('H:i:s') }} WIB</span>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ppob.settings.cutoff') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sakelar 1: Jadwal Cut-Off Harian Otomatis -->
                <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-white/10 space-y-3">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="ppob_cutoff_enabled" value="0">
                        <input type="checkbox" name="ppob_cutoff_enabled" value="1" @checked($cutoff['cutoff_enabled'] ?? true) class="mt-1 w-4 h-4 rounded text-amber-500 focus:ring-amber-500">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white block">Aktifkan Jam Cut-Off Otomatis Harian</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Otomatis menahan sementara transaksi PPOB setiap malam pada rentang jam di bawah ini dan membuka kembali otomatis setelah selesai.
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

                <!-- Sakelar 2: Mode Maintenance Manual Instan -->
                <div class="p-4 rounded-2xl {{ ($cutoff['maintenance_enabled'] ?? false) ? 'bg-rose-50 dark:bg-rose-950/30 border-2 border-rose-500/40' : 'bg-gray-50 dark:bg-gray-900/80 border border-gray-200 dark:border-white/10' }} space-y-3 flex flex-col justify-between">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="ppob_maintenance_enabled" value="0">
                        <input type="checkbox" name="ppob_maintenance_enabled" value="1" @checked($cutoff['maintenance_enabled'] ?? false) class="mt-1 w-4 h-4 rounded text-rose-600 focus:ring-rose-500">
                        <div>
                            <span class="text-xs sm:text-sm font-black text-gray-900 dark:text-white block">Sakelar Maintenance Layanan PPOB (Manual)</span>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 block mt-0.5">
                                Nyalakan tombol ini jika pusat Kiosbank sedang mengalami gangguan mendadak di luar jam cut-off atau saat Anda sedang mengisi ulang deposit provider.
                            </span>
                        </div>
                    </label>

                    <div class="p-2.5 rounded-xl bg-white/80 dark:bg-black/30 border border-gray-200/60 dark:border-white/5 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                        💡 Saat aktif, halaman <code class="font-mono text-orange-500">/ppob</code> tetap bisa dibuka pelanggan untuk melihat katalog harga, namun tombol <strong>Beli / Bayar</strong> dikunci dengan pemberitahuan elegan.
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-black uppercase tracking-wider text-gray-400 mb-1.5">Pesan Pemberitahuan Cut-Off / Maintenance ke Pelanggan</label>
                <textarea 
                    name="ppob_cutoff_message" 
                    rows="2" 
                    placeholder="Layanan PPOB sedang dalam jam rekonsiliasi / cut-off harian pusat (23:50 - 00:10 WIB). Transaksi akan kembali aktif otomatis setelah pukul 00:10 WIB."
                    class="w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-white/10 text-xs font-bold dark:text-white outline-none focus:ring-2 focus:ring-amber-500"
                >{{ old('ppob_cutoff_message', $cutoff['message'] ?? '') }}</textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl gradient-orange text-white font-black text-xs shadow-lg shadow-orange-500/20 hover:opacity-95 transition cursor-pointer">
                    💾 Simpan Pengaturan Cut-Off &amp; Maintenance PPOB
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
