@extends('layouts.admin')

@section('title', 'Konfigurasi Kiosbank PPOB')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="admin-card p-6 space-y-3">
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">Konfigurasi Kiosbank PPOB</h1>
        <p class="text-sm text-gray-500">Mode aktif: <strong>{{ ucfirst($mode) }}</strong>. Kredensial Development dan Production disimpan terpisah.</p>
        <div class="flex flex-wrap gap-4 text-sm font-bold text-cyan-600">
            <a href="{{ route('admin.ppob.products') }}">Produk &amp; Margin</a>
            <a href="{{ route('admin.ppob.deposit') }}">Saldo Deposit</a>
            <a href="https://ppob.kiosbank.com:4040/api/webservicesV2/" target="_blank" rel="noopener noreferrer">Dokumentasi Webservice V2</a>
        </div>
        <form action="{{ route('admin.ppob.deposit.check_live') }}" method="POST">
            @csrf
            <button class="px-4 py-2 rounded-xl bg-cyan-600 text-white font-bold text-sm">Cek Saldo Mode Aktif</button>
        </form>
    </div>

    @if(session('success'))
        <div role="status" class="p-4 rounded-xl bg-emerald-100 text-emerald-900">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div role="alert" class="p-4 rounded-xl bg-rose-100 text-rose-900">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="p-4 rounded-xl bg-rose-100 text-rose-900">
            <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

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
        <button type="submit" class="px-6 py-3 rounded-xl bg-cyan-600 hover:bg-cyan-700 text-white font-black">Simpan Konfigurasi &amp; Mode</button>
    </form>
</div>
@endsection
