@extends('layouts.admin')

@section('title', request('section') === 'cutoff' ? 'Jam Cut-Off & Maintenance PPOB' : (request('section') === 'uat' ? 'Pusat UAT & Syarat Launching Kiosbank' : 'Konfigurasi Kiosbank PPOB'))

@section('content')
@php
    $activeSection = request('section', 'credentials');
    $cutoff = $cutoffStatus ?? (new \App\Services\KiosbankService())->getCutoffStatus();
    $uatScenarios = $uatChecklist ?? (new \App\Services\KiosbankService())->getOfficialUatChecklist();
    $uatLogsData = $uatLogs ?? (new \App\Services\KiosbankService())->getUatLogs(80);
    $totalUatCount = count($uatScenarios);
    $completedUatCount = collect($uatScenarios)->filter(fn($s) => !empty($s['last_run']['ok']))->count();
    $isReadyForLaunch = $completedUatCount >= $totalUatCount && $totalUatCount > 0;
    $devProfile = $profiles['development'] ?? [];
    $latestSessionId = collect($uatLogsData)->pluck('session_id')->filter()->first()
        ?? \App\Models\Setting::where('key', 'kiosbank_session_id_development')->value('value');
    $signOnTodayCount = (int) (\App\Models\Setting::where('key', 'kiosbank_signon_count_' . now('Asia/Jakarta')->format('Y-m-d'))->value('value') ?? 1);
    $officialRequirements = [
        [
            'title' => '1. Autentikasi Sign-On & Reuse SessionID Harian (Maks 99x/Hari)',
            'detail' => 'SessionID berlaku hingga pukul 23:59 WIB di hari yang sama (disarankan Sign-On setelah 00:15 WIB). Sistem otomatis menyimpan SessionID di cache agar tidak terkena blokir RC 34.',
        ],
        [
            'title' => '2. Format ReferenceID Wajib 12 Digit Angka Unik (0-9)',
            'detail' => 'Setiap request SinglePayment dan Inquiry wajib memakai ReferenceID 12 digit numerik unik. Pada saat Payment, ReferenceID wajib sama persis dengan saat Inquiry.',
        ],
        [
            'title' => '3. Format Nominal Tagihan, Admin & Total 12 Digit Zero-Padded',
            'detail' => 'Seluruh parameter nominal pada Payment dan Check-Status dikirim dalam format 12 karakter rata kanan bertanda nol di depan (contoh: 000000027500) sesuai respon Inquiry.',
        ],
        [
            'title' => '4. Validasi Status Transaksi (BERHASIL vs SEDANG DIPROSES / RC 71)',
            'detail' => 'Selain mengecek rc == "00", sistem wajib memeriksa data.status ("BERHASIL" = Sukses Final, "SEDANG DIPROSES" atau RC 71 = Pending / Tahan Saldo hingga Callback atau Check-Status).',
        ],
        [
            'title' => '5. Rekonsiliasi /Services/Check-Status Identik + tglTransaksi',
            'detail' => 'Jika belum menerima Callback untuk transaksi Pending, sistem menembak /Services/Check-Status dengan parameter merchantID, productID, customerID, referenceID, tagihan, admin, total & tglTransaksi (YYYY-MM-DD) yang identik.',
        ],
        [
            'title' => '6. Endpoint Callback / Webhook Menerima POST JSON (HTTP 200 OK)',
            'detail' => 'URL Callback (' . url('/api/webhook/kiosbank') . ') siap menerima notifikasi asinkron dari server pusat Kiosbank untuk produk Pulsa/Paket Data/E-Money/PLN.',
        ],
        [
            'title' => '7. Jam Rekonsiliasi / Cut-Off Harian Server Pusat (23:50 - 00:10 WIB)',
            'detail' => 'Transaksi ditahan otomatis selama masa rekonsiliasi tengah malam server pusat Kiosbank untuk mencegah transaksi gantung lintas hari.',
        ],
    ];
@endphp
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Header & Navigasi Cepat -->
    <div class="admin-card p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">Konfigurasi &amp; UAT Kiosbank PPOB</h1>
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
                Kelola kredensial Webservice V2 Kiosbank, eksekusi UAT langsung ke Server Pusat Kiosbank, serta pengaman Jam Cut-Off Harian ({{ $cutoff['cutoff_start'] ?? '23:50' }} - {{ $cutoff['cutoff_end'] ?? '00:10' }} WIB).
            </p>
            <div class="flex flex-wrap gap-2.5 pt-1 text-xs font-black text-cyan-600 dark:text-cyan-400">
                <a href="{{ route('admin.ppob.settings') }}" class="px-3 py-1.5 rounded-xl {{ !in_array($activeSection, ['cutoff', 'uat']) ? 'bg-cyan-600 text-white' : 'bg-cyan-50 dark:bg-cyan-950/40 hover:underline' }}">
                    🔑 Kredensial Kiosbank
                </a>
                <a href="{{ route('admin.ppob.settings', ['section' => 'uat']) }}" class="px-3 py-1.5 rounded-xl {{ $activeSection === 'uat' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:underline' }}">
                    🧪 Pusat UAT &amp; Syarat Launching ({{ $completedUatCount }}/{{ $totalUatCount }})
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
    <!-- PUSAT EKSEKUSI UAT & SYARAT KETENTUAN LAUNCHING KIOSBANK (WEBSERVICE V2)  -->
    <!-- ========================================================================= -->
    <div id="uat-section" class="admin-card p-6 sm:p-7 border-t-4 border-t-emerald-500 space-y-6">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-gray-100 dark:border-white/10">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <span class="px-2.5 py-1 rounded-full bg-emerald-500 text-white text-[10px] font-black uppercase tracking-wider">
                        SERVER PUSAT KIOSBANK (UAT RESMI)
                    </span>
                    <span class="px-2.5 py-1 rounded-full bg-cyan-500/15 text-cyan-600 dark:text-cyan-300 border border-cyan-500/30 text-[10px] font-black font-mono">
                        https://{{ $devProfile['host'] ?? 'development.kiosbank.com' }}:{{ $devProfile['port'] ?? '4432' }}
                    </span>
                    <span class="px-2.5 py-1 rounded-full {{ $isReadyForLaunch ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-slate-950' }} text-[10px] font-black">
                        Progres Skenario UAT: {{ $completedUatCount }} / {{ $totalUatCount }} Selesai
                    </span>
                </div>
                <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">
                    Pusat Eksekusi UAT (User Acceptance Test) &amp; Syarat Launching Kiosbank
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                    Seluruh pengujian pada panel ini dikirim <strong>100% langsung (Live HTTP Digest Auth + SessionID Asli)</strong> ke server pusat Kiosbank Development (<code class="font-mono text-cyan-600 dark:text-cyan-400">https://development.kiosbank.com:4432</code>) menggunakan kredensial Merchant <strong>{{ $devProfile['merchant_id'] ?? 'DJI000799' }}</strong> sehingga tercatat resmi di log server pusat Kiosbank sebagai syarat pengajuan Kredensial Production.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <form method="POST" action="{{ route('admin.ppob.settings.uat_run') }}">
                    @csrf
                    <input type="hidden" name="scenario" value="all">
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-600/20 cursor-pointer transition flex items-center gap-2">
                        <span>🚀 Jalankan Semua 11 Skenario UAT ke Server Pusat</span>
                    </button>
                </form>

                <a href="{{ route('admin.ppob.settings.uat_export', ['format' => 'txt']) }}" class="px-3.5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-xs shadow-md shadow-cyan-600/20 transition flex items-center gap-1.5">
                    <span>📄 Download Berita Acara UAT (.TXT)</span>
                </a>

                <a href="{{ route('admin.ppob.settings.uat_export', ['format' => 'json']) }}" class="px-3 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-700 text-white font-black text-xs transition">
                    <span>{ } Export JSON</span>
                </a>
            </div>
        </div>

        <!-- RINGKASAN METRIK KONEKSI SERVER PUSAT -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">Identitas Mitra &amp; Merchant</span>
                <p class="text-sm font-black text-gray-900 dark:text-white mt-1 font-mono">
                    {{ $devProfile['mitra'] ?? 'DJI' }} / {{ $devProfile['merchant_id'] ?? 'DJI000799' }}
                </p>
                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block mt-0.5">
                    Account: {{ $devProfile['account_id'] ?? '085794205822' }} ({{ $devProfile['merchant_name'] ?? 'NANDARSTORE' }})
                </span>
            </div>

            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">SessionID Aktif (Server Pusat)</span>
                <p class="text-xs font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono break-all">
                    {{ !empty($latestSessionId) ? $latestSessionId : 'Belum Sign-On Hari Ini' }}
                </p>
                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block mt-0.5">
                    Batas Sign-On: <strong>Maks 99x / Hari</strong> (Berlaku s/d 23:59 WIB)
                </span>
            </div>

            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">URL Callback / Webhook Anda</span>
                <p class="text-xs font-black text-cyan-600 dark:text-cyan-400 mt-1 font-mono break-all">
                    {{ url('/api/webhook/kiosbank') }}
                </p>
                <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 block mt-0.5">
                    Metode: POST JSON (Response HTTP 200 OK)
                </span>
            </div>

            <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-900/70 border border-gray-200 dark:border-white/10">
                <span class="text-[10px] font-black uppercase tracking-wider text-gray-400 block">Total Hit Tercatat ke Pusat</span>
                <p class="text-lg font-black text-gray-900 dark:text-white mt-0.5">
                    {{ count($uatLogsData) }} <span class="text-xs font-bold text-gray-500">Request API</span>
                </p>
                <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 block">
                    ✓ Tersimpan dengan ReferenceID 12 Digit
                </span>
            </div>
        </div>

        <!-- 7 SYARAT & KETENTUAN RESMI UAT WEBSERVICE V2 KIOSBANK -->
        <div class="p-4 sm:p-5 rounded-2xl bg-cyan-50/60 dark:bg-cyan-950/25 border border-cyan-200/80 dark:border-cyan-800/40 space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-cyan-900 dark:text-cyan-300">
                    📋 7 Syarat &amp; Ketentuan Resmi UAT Webservice V2 Kiosbank (Sudah Terpenuhi di Sistem Anda)
                </h3>
                <span class="text-[11px] font-bold text-cyan-700 dark:text-cyan-400">Sesuai Dokumentasi Resmi Webservice V2 Kiosbank</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                @foreach($officialRequirements as $req)
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900/80 border border-cyan-200/60 dark:border-white/10 flex items-start gap-2.5">
                        <span class="w-5 h-5 rounded-full bg-emerald-500 text-white text-[11px] font-black flex items-center justify-center shrink-0 mt-0.5">✓</span>
                        <div class="min-w-0">
                            <p class="text-xs font-black text-gray-900 dark:text-white">{{ $req['title'] }}</p>
                            <p class="text-[11px] text-gray-600 dark:text-gray-400 mt-0.5 leading-snug">{{ $req['detail'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- TABEL 11 SKENARIO WAJIB UAT KIOSBANK -->
        <div class="space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-gray-900 dark:text-white">
                        Daftar 11 Skenario Pengujian UAT Resmi Kiosbank (SinglePayment, Inquiry, Payment &amp; Check-Status)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Klik tombol <strong>"Jalankan Semua 11 Skenario UAT ke Server Pusat"</strong> di atas atau tombol <strong>"Uji ke Pusat"</strong> pada masing-masing baris di bawah.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-white/10">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-900/90 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/10">
                            <th class="py-3 px-4">No &amp; Skenario UAT</th>
                            <th class="py-3 px-3">Endpoint &amp; Nopel Uji</th>
                            <th class="py-3 px-3">Ketentuan Pengujian</th>
                            <th class="py-3 px-3">Status UAT &amp; Ref ID (12 Digit)</th>
                            <th class="py-3 px-4 text-right">Aksi Uji Live</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-xs">
                        @foreach($uatScenarios as $key => $sc)
                            @php
                                $res = $sc['last_run'] ?? null;
                                $isPassed = !empty($res['ok']);
                            @endphp
                            <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.02] transition">
                                <td class="py-3.5 px-4 align-top">
                                    <span class="inline-block text-[10px] font-black uppercase px-2 py-0.5 rounded-md bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 mb-1">
                                        Skenario #{{ $sc['number'] ?? $loop->iteration }}
                                    </span>
                                    <p class="font-black text-gray-900 dark:text-white">{{ $sc['title'] }}</p>
                                </td>
                                <td class="py-3.5 px-3 align-top font-mono text-[11px]">
                                    <span class="font-black text-indigo-600 dark:text-indigo-400 block">{{ $sc['endpoint'] }}</span>
                                    <span class="text-gray-600 dark:text-gray-300 block mt-0.5">Produk: <strong>{{ $sc['product_id'] }}</strong></span>
                                    <span class="text-gray-600 dark:text-gray-300 block">Nopel: <strong>{{ $sc['nopel'] }}</strong></span>
                                </td>
                                <td class="py-3.5 px-3 align-top">
                                    <span class="text-[11px] font-medium text-gray-600 dark:text-gray-300 leading-snug block max-w-xs">{{ $sc['requirement'] }}</span>
                                </td>
                                <td class="py-3.5 px-3 align-top">
                                    @if($res)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full {{ $isPassed ? 'bg-emerald-500 text-white' : 'bg-amber-500 text-slate-950' }}">
                                            {{ $isPassed ? '✓ TERCATAT DI PUSAT (' . ($res['central_hits'] ?? 1) . ' HIT)' : '⚠ PERLU CEK' }}
                                        </span>
                                        <p class="font-mono text-[11px] font-bold text-gray-800 dark:text-gray-200 mt-1">
                                            RefID: {{ $res['reference_id'] ?? '-' }} | RC: {{ $res['app_rc'] ?? '-' }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 leading-snug">
                                            {{ $res['summary'] ?? '' }}
                                        </p>
                                        <span class="text-[10px] text-gray-400 block mt-0.5">{{ $res['executed_at'] ?? '' }}</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                            BELUM DIJALANKAN
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 align-top text-right">
                                    <form method="POST" action="{{ route('admin.ppob.settings.uat_run') }}">
                                        @csrf
                                        <input type="hidden" name="scenario" value="{{ $key }}">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-[11px] cursor-pointer transition whitespace-nowrap">
                                            ▶ Uji ke Pusat
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LOG BUKTI REQUEST & RESPONSE KE SERVER PUSAT KIOSBANK -->
        <div class="space-y-3 pt-2">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-gray-900 dark:text-white">
                        📡 Bukti Log Request &amp; Response Server Pusat Kiosbank ({{ count($uatLogsData) }} Log Terbaru)
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Setiap baris di bawah ini adalah bukti otentik komunikasi HTTP Digest antara server Anda dengan <code class="font-mono">https://development.kiosbank.com:4432</code> beserta <code class="font-mono">SessionID</code> dan <code class="font-mono">ReferenceID</code> 12 digit.
                    </p>
                </div>

                @if(count($uatLogsData) > 0)
                    <form method="POST" action="{{ route('admin.ppob.settings.uat_clear') }}" onsubmit="return confirm('Bersihkan riwayat log UAT lokal?');">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/30 text-[11px] font-black cursor-pointer transition">
                            🗑 Bersihkan Log
                        </button>
                    </form>
                @endif
            </div>

            @if(count($uatLogsData) === 0)
                <div class="p-8 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-dashed border-gray-300 dark:border-white/10 text-center space-y-2">
                    <p class="text-sm font-black text-gray-700 dark:text-gray-200">Belum Ada Log Transaksi UAT Hari Ini</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-lg mx-auto">
                        Klik tombol hijau <strong>"Jalankan Semua 11 Skenario UAT ke Server Pusat"</strong> di bagian atas kartu ini untuk mengirim seluruh skenario pengujian langsung ke server pusat Kiosbank.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-white/10 max-h-[520px] overflow-y-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-gray-100 dark:bg-gray-900 text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-white/10">
                                <th class="py-2.5 px-3">Waktu (WIB)</th>
                                <th class="py-2.5 px-3">Endpoint Pusat</th>
                                <th class="py-2.5 px-3">ReferenceID (12 Digit)</th>
                                <th class="py-2.5 px-3">Produk / Nopel</th>
                                <th class="py-2.5 px-3">HTTP &amp; RC Pusat</th>
                                <th class="py-2.5 px-3">Payload JSON (Request &amp; Response)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-xs font-mono">
                            @foreach($uatLogsData as $log)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-white/[0.02]">
                                    <td class="py-2.5 px-3 align-top whitespace-nowrap text-[11px] text-gray-600 dark:text-gray-300">
                                        {{ $log['timestamp'] ?? '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 align-top">
                                        <span class="font-black text-cyan-600 dark:text-cyan-400 block">{{ $log['endpoint'] ?? '-' }}</span>
                                        <span class="text-[10px] text-gray-400 break-all">{{ !empty($log['session_id']) ? 'SID: '.\Illuminate\Support\Str::limit($log['session_id'], 16) : '' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 align-top font-black text-gray-900 dark:text-white">
                                        {{ $log['reference_id'] ?: '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 align-top text-[11px]">
                                        <span class="block text-gray-800 dark:text-gray-200">ID: <strong>{{ $log['product_id'] ?: '-' }}</strong></span>
                                        <span class="block text-gray-500">Nopel: {{ $log['customer_id'] ?: '-' }}</span>
                                    </td>
                                    <td class="py-2.5 px-3 align-top">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-black {{ ($log['http_code'] ?? 0) === 200 ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' : 'bg-rose-500/15 text-rose-600' }}">
                                            HTTP {{ $log['http_code'] ?? 0 }} | RC: {{ $log['server_rc'] ?? '-' }}
                                        </span>
                                        <p class="font-sans text-[11px] text-gray-600 dark:text-gray-300 mt-1 max-w-xs">
                                            {{ $log['server_description'] ?? '' }}
                                        </p>
                                    </td>
                                    <td class="py-2.5 px-3 align-top font-sans">
                                        <details class="text-[11px]">
                                            <summary class="cursor-pointer font-bold text-cyan-600 dark:text-cyan-400 hover:underline">
                                                Lihat JSON Request &amp; Response
                                            </summary>
                                            <div class="mt-2 space-y-1.5 max-w-md">
                                                <div class="p-2 rounded bg-gray-900 text-emerald-300 font-mono text-[10px] overflow-x-auto">
                                                    <strong class="text-white block mb-0.5">REQUEST KE PUSAT:</strong>
                                                    {{ json_encode($log['request_payload'] ?? [], JSON_UNESCAPED_SLASHES) }}
                                                </div>
                                                <div class="p-2 rounded bg-gray-900 text-cyan-300 font-mono text-[10px] overflow-x-auto">
                                                    <strong class="text-white block mb-0.5">RESPONSE DARI PUSAT:</strong>
                                                    {{ json_encode($log['response_payload'] ?? [], JSON_UNESCAPED_SLASHES) }}
                                                </div>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

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
