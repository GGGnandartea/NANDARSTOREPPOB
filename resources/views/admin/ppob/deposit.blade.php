@extends('layouts.admin')

@section('title', 'Top Up Saldo Deposit Kiosbank')

@section('content')
<div class="max-w-6xl mx-auto space-y-6" x-data="kiosbankDepositHub()">

    <!-- Alert Notifikasi -->
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

    @if(session('info'))
        <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/40 text-blue-800 dark:text-blue-300 font-bold text-sm flex items-center gap-3 shadow-sm">
            <div class="w-8 h-8 rounded-xl bg-blue-500 text-white flex items-center justify-center font-black shrink-0">ℹ</div>
            <div>{{ session('info') }}</div>
        </div>
    @endif

    <!-- Breadcrumb & Back -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.ppob.products') }}" class="inline-flex items-center gap-2 text-xs font-bold text-gray-500 hover:text-cyan-500 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Kembali ke Katalog Produk PPOB</span>
        </a>

        @if($isDev)
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/30">
                ⚡ DEVELOPER MODE (SANDBOX ACTIVE)
            </span>
        @else
            <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                🟢 PRODUCTION MODE (LIVE DJI)
            </span>
        @endif
    </div>

    <!-- HERO CARD: SALDO REALTIME & CEK DEPOSIT -->
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 bg-gradient-to-br from-gray-900 via-[#0e1726] to-[#0b2434] text-white shadow-xl border border-cyan-500/20 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="absolute -top-20 -right-20 w-52 h-52 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="text-xs font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                    Host-to-Host (H2H) Deposit
                </span>
                @if($depositStatus['ok'])
                    <span class="text-[10px] font-bold text-emerald-400 flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Terhubung
                    </span>
                @endif
            </div>

            <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white">Saldo Deposit Kiosbank</h1>
            <p class="text-xs text-gray-400 mt-1 max-w-md font-medium">
                Saldo ini digunakan secara otomatis untuk memotong biaya setiap pembelian pulsa, token listrik PLN, game, dan e-wallet oleh pelanggan Anda.
            </p>

            <div class="mt-4 flex items-baseline gap-3">
                <span class="text-3xl sm:text-4xl font-black font-mono text-emerald-400">
                    Rp {{ number_format($depositStatus['saldo'] ?? 0, 0, ',', '.') }}
                </span>
                @if(!empty($depositStatus['is_sandbox']))
                    <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        Sandbox Saldo
                    </span>
                @endif
            </div>
        </div>

        <!-- Tombol Cek Saldo Realtime -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full md:w-auto">
            <form action="{{ route('admin.ppob.deposit.check_live') }}" method="POST">
                @csrf
                <button type="submit" class="w-full sm:w-auto py-3 px-5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-black text-xs transition border border-white/10 flex items-center justify-center gap-2 cursor-pointer shadow-md">
                    <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Cek Saldo Realtime (/getCurrentDeposit)</span>
                </button>
            </form>
        </div>
    </div>

    <!-- DUA KOLOM: FORM PENGAJUAN TOP UP & REKENING RESMI DJI -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- KOLOM KIRI: FORM TIKET TOP UP (7 KOLOM) -->
        <div class="lg:col-span-7 p-6 sm:p-7 rounded-3xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm space-y-5">
            <div>
                <h3 class="text-base font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Formulir Pengajuan Tiket Top Up Deposit</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Pilih nominal dan metode transfer untuk mengisi saldo deposit Kiosbank Anda.</p>
            </div>

            <form action="{{ route('admin.ppob.deposit.create') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Pilihan Cepat Nominal -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-2">Pilih Nominal Top Up Cepat</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                        <button type="button" @click="setAmount(100000)" :class="amount == 100000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 100.000
                        </button>
                        <button type="button" @click="setAmount(250000)" :class="amount == 250000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 250.000
                        </button>
                        <button type="button" @click="setAmount(500000)" :class="amount == 500000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 500.000
                        </button>
                        <button type="button" @click="setAmount(1000000)" :class="amount == 1000000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 1.000.000
                        </button>
                        <button type="button" @click="setAmount(2500000)" :class="amount == 2500000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 2.500.000
                        </button>
                        <button type="button" @click="setAmount(5000000)" :class="amount == 5000000 ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-black' : 'border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300'" class="p-3 rounded-xl border text-xs text-center transition cursor-pointer">
                            Rp 5.000.000
                        </button>
                    </div>
                </div>

                <!-- Input Nominal Bebas -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">
                        Atau Masukkan Nominal Sendiri (Rp) <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-2.5 font-bold text-xs text-gray-400">Rp</span>
                        <input type="number" 
                               name="amount" 
                               x-model="amount" 
                               required 
                               min="10000" 
                               step="1000"
                               placeholder="Cth: 750000"
                               class="w-full py-2.5 pl-10 pr-4 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-sm font-black text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <!-- Pilihan Rekening Pembayaran -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-2">Metode Transfer Rekening Resmi Kiosbank</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-white/10 hover:border-emerald-500 cursor-pointer transition">
                            <input type="radio" name="payment_method" value="bca" checked class="text-emerald-500">
                            <div class="flex-1 text-xs">
                                <span class="font-black text-gray-900 dark:text-white block">Bank BCA (PT DESIGN JAYA INDONESIA)</span>
                                <span class="text-[11px] text-gray-400 font-mono">No. Rek: 8410-888-999</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-white/10 hover:border-emerald-500 cursor-pointer transition">
                            <input type="radio" name="payment_method" value="mandiri" class="text-emerald-500">
                            <div class="flex-1 text-xs">
                                <span class="font-black text-gray-900 dark:text-white block">Bank Mandiri (PT DESIGN JAYA INDONESIA)</span>
                                <span class="text-[11px] text-gray-400 font-mono">No. Rek: 137-00-1122334-5</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-white/10 hover:border-emerald-500 cursor-pointer transition">
                            <input type="radio" name="payment_method" value="bri" class="text-emerald-500">
                            <div class="flex-1 text-xs">
                                <span class="font-black text-gray-900 dark:text-white block">Bank BRI (PT DESIGN JAYA INDONESIA)</span>
                                <span class="text-[11px] text-gray-400 font-mono">No. Rek: 0341-01-000888-30-1</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 dark:border-white/10 hover:border-emerald-500 cursor-pointer transition">
                            <input type="radio" name="payment_method" value="qris" class="text-emerald-500">
                            <div class="flex-1 text-xs">
                                <span class="font-black text-gray-900 dark:text-white block">QRIS Nasional DJI (Instan)</span>
                                <span class="text-[11px] text-gray-400">Scan via BCA Mobile, Livin, GoPay, OVO, DANA</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Catatan Opsional -->
                <div>
                    <label class="block text-xs font-black text-gray-700 dark:text-gray-300 mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text" name="notes" placeholder="Cth: Deposit rutin awal bulan" class="w-full py-2 px-3 rounded-xl bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-medium text-gray-900 dark:text-white focus:outline-none focus:border-emerald-500">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-6 rounded-2xl gradient-emerald text-white font-black text-xs shadow-lg shadow-emerald-500/20 hover:opacity-90 transition flex items-center justify-center gap-2 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Buat Tiket Top Up Saldo</span>
                </button>
            </form>
        </div>

        <!-- KOLOM KANAN: PANDUAN RESMI DEPOSIT DJI KIOSBANK (5 KOLOM) -->
        <div class="lg:col-span-5 space-y-5">
            
            <!-- Sandbox Simulation Notice -->
            @if($isDev)
                <div class="p-6 rounded-3xl bg-amber-500/10 border border-amber-500/30 text-amber-900 dark:text-amber-300 space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
                        <h4 class="font-black text-xs uppercase tracking-wide text-amber-800 dark:text-amber-200">Fitur Developer Mode Aktif</h4>
                    </div>
                    <p class="text-xs leading-relaxed">
                        Di Developer Mode, setelah Anda membuat tiket top up di sebelah kiri, Anda dapat langsung menekan tombol <strong>"⚡ Simulasi Sukses (Dev Mode)"</strong> pada tabel riwayat di bawah untuk menguji penambahan saldo tanpa harus transfer uang asli ke bank.
                    </p>
                </div>
            @endif

            <!-- Box Panduan Transfer -->
            <div class="p-6 rounded-3xl bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 shadow-sm space-y-4">
                <h4 class="font-black text-sm text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Petunjuk Deposit Resmi KIOSBANK</span>
                </h4>

                <ol class="space-y-3 text-xs text-gray-600 dark:text-gray-300 list-decimal list-inside font-medium leading-relaxed">
                    <li>Pilih nominal dan buat tiket top up deposit.</li>
                    <li>Sistem akan menyertakan <strong>3 digit kode unik</strong> (misal Rp 500.284).</li>
                    <li>Transfer persis hingga 3 digit terakhir ke rekening resmi <strong>PT DESIGN JAYA INDONESIA</strong>.</li>
                    <li>Saldo akan otomatis masuk ke akun Kiosbank Anda dalam waktu 1-5 menit setelah transfer diverifikasi.</li>
                    <li>Setelah transfer berhasil, tekan tombol <strong>"Cek Saldo Realtime"</strong> untuk memperbarui tampilan saldo.</li>
                </ol>

                <div class="pt-3 border-t border-gray-100 dark:border-white/5 text-[11px] text-gray-400">
                    <p>🕒 Jam Layanan Tiket Deposit: 06:00 - 22:00 WIB setiap hari.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL RIWAYAT TIKET TOP UP DEPOSIT -->
    <div class="bg-white dark:bg-[#111827] border border-gray-200 dark:border-white/5 rounded-3xl shadow-sm overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-white/5">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Riwayat Pengajuan Top Up Deposit Kiosbank</h3>
            <p class="text-xs text-gray-400">Daftar tiket top up saldo deposit Kiosbank yang telah dibuat.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 dark:bg-white/[0.02] border-b border-gray-100 dark:border-white/5 text-gray-400 uppercase tracking-wider font-extrabold text-[10px]">
                    <tr>
                        <th class="py-3.5 px-4">No Tiket</th>
                        <th class="py-3.5 px-4">Tanggal & Waktu</th>
                        <th class="py-3.5 px-4">Metode Rekening</th>
                        <th class="py-3.5 px-4 text-gray-500 font-bold">Nominal Pokok</th>
                        <th class="py-3.5 px-4 text-emerald-500 font-black">Total Transfer (+ Kode Unik)</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5 font-semibold text-gray-700 dark:text-gray-300">
                    @forelse($deposits as $d)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.01] transition-colors">
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                {{ $d->invoice_number }}
                            </td>
                            <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $d->created_at->format('d/m/Y H:i') }} WIB
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-gray-900 dark:text-white uppercase">{{ $d->payment_method }}</span>
                                <div class="text-[11px] text-gray-400">{{ $d->bank_account_number }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                Rp {{ number_format($d->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-black text-emerald-600 dark:text-emerald-400 text-xs">
                                Rp {{ number_format($d->total_payment, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($d->status === 'success')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800/40">
                                        ✓ Lunas
                                    </span>
                                @elseif($d->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-300 dark:border-amber-800/40">
                                        Menunggu Transfer
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-300 dark:border-rose-800/40">
                                        Batal
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($isDev && $d->status === 'pending')
                                    <form action="{{ route('admin.ppob.deposit.simulate', $d->id) }}" method="POST" onsubmit="return confirm('Simulasikan top up ini berhasil dan tambahkan ke saldo sandbox?');">
                                        @csrf
                                        <button type="submit" class="py-1 px-3 rounded-lg bg-amber-500 hover:bg-amber-600 text-black font-black text-[11px] transition shadow-sm cursor-pointer">
                                            ⚡ Simulasi Sukses (Dev Mode)
                                        </button>
                                    </form>
                                @else
                                    <span class="text-gray-400 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-gray-400">
                                Belum ada riwayat tiket top up deposit. Silakan buat tiket baru di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($deposits->hasPages())
            <div class="p-5 border-t border-gray-100 dark:border-white/5">
                {{ $deposits->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function kiosbankDepositHub() {
    return {
        amount: 500000,
        setAmount(val) {
            this.amount = val;
        }
    }
}
</script>
@endsection

