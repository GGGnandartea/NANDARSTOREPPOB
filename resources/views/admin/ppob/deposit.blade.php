@extends('layouts.admin')

@section('title', 'Saldo Deposit Kiosbank')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <nav class="flex flex-wrap gap-4 text-sm font-bold text-cyan-600" aria-label="Menu PPOB">
        <a href="{{ route('admin.ppob.products') }}">Produk &amp; Margin</a>
        <a href="{{ route('admin.ppob.deposit') }}">Saldo Deposit</a>
        <a href="{{ route('admin.ppob.settings') }}">Konfigurasi &amp; Mode</a>
    </nav>
    @foreach(['success', 'error', 'info'] as $notice)
        @if(session($notice))
            <div role="status" class="admin-card p-4 text-sm dark:text-white">{{ session($notice) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div role="alert" class="p-4 rounded-xl bg-rose-100 text-rose-900">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <div class="rounded-3xl p-6 sm:p-8 bg-gray-900 text-white space-y-4">
        <div class="flex flex-wrap justify-between gap-3">
            <h1 class="text-2xl font-black">Saldo Deposit Kiosbank</h1>
            <span class="font-bold text-cyan-300">{{ ucfirst($kiosbankMode) }}</span>
        </div>
        @if($depositStatus['ok'])
            <p class="text-4xl font-black text-emerald-400">Rp {{ number_format($depositStatus['saldo'] ?? 0, 0, ',', '.') }}</p>
            <p class="text-sm text-gray-300">{{ !empty($depositStatus['is_sandbox']) ? 'Saldo virtual simulasi Development' : 'Saldo dari server Kiosbank' }}</p>
        @else
            <p class="text-amber-300">Saldo belum dapat dibaca: {{ $depositStatus['message'] ?? 'Koneksi belum tersedia.' }}</p>
        @endif
        <form action="{{ route('admin.ppob.deposit.check_live') }}" method="POST">
            @csrf
            <button class="px-5 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 font-bold">Cek Saldo Realtime</button>
        </form>
    </div>
    @if($isDev)
        <div class="admin-card p-6 space-y-4">
            <h2 class="font-black text-lg dark:text-white">Simulasi Top Up Development</h2>
            <p class="text-sm text-amber-700 dark:text-amber-300">Saldo ini virtual dan hanya berlaku untuk simulasi lokal saat koneksi Development tidak tersedia. Saldo server Development Kiosbank dikelola oleh Kiosbank. Tidak perlu transfer uang.</p>
            <form action="{{ route('admin.ppob.deposit.create') }}" method="POST" class="flex flex-wrap items-end gap-4">
                @csrf
                <input type="hidden" name="payment_method" value="developer_mode">
                <label for="deposit-amount" class="text-sm font-bold dark:text-white">Nominal virtual (Rp)
                    <input id="deposit-amount" type="number" name="amount" min="10000" step="1" value="{{ old('amount', 100000) }}" required class="block mt-2 p-3 border rounded-xl bg-white dark:bg-gray-900">
                </label>
                <button class="px-5 py-3 rounded-xl bg-cyan-600 text-white font-bold">Buat Tiket Simulasi</button>
            </form>
        </div>
        <div class="admin-card p-6 space-y-4">
            <h2 class="font-black text-lg dark:text-white">Riwayat Tiket Development</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left dark:text-gray-200">
                    <thead><tr class="border-b"><th class="p-3">Invoice</th><th class="p-3">Nominal</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead>
                    <tbody>
                        @forelse($deposits as $deposit)
                            <tr class="border-b border-gray-100 dark:border-white/10">
                                <td class="p-3 font-mono">{{ $deposit->invoice_number }}</td>
                                <td class="p-3">Rp {{ number_format($deposit->amount, 0, ',', '.') }}</td>
                                <td class="p-3">{{ $deposit->status }}</td>
                                <td class="p-3">
                                    @if($deposit->status === 'pending')
                                        <form action="{{ route('admin.ppob.deposit.simulate', $deposit->id) }}" method="POST">
                                            @csrf
                                            <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white font-bold">Simulasikan Berhasil</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-4 text-gray-500">Belum ada tiket simulasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $deposits->links() }}
        </div>
    @else
        <div class="admin-card p-6 space-y-3">
            <h2 class="font-black text-lg dark:text-white">Deposit Production</h2>
            <p class="text-sm text-gray-500">Gunakan kanal deposit resmi yang diberikan helpdesk Kiosbank. Setelah transfer dikonfirmasi Kiosbank, gunakan tombol Cek Saldo Realtime di atas.</p>
        </div>
    @endif
</div>
@endsection
