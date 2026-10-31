<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;
use App\Services\OrderFulfillmentService;
use App\Services\KiosbankService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Cron / CLI Command: Cek Status Ulang Otomatis (/Services/Check-Status)
 * untuk seluruh transaksi PPOB Kiosbank yang berstatus Pending / Processing.
 */
Artisan::command('ppob:sync-pending {--limit=30 : Jumlah maksimum transaksi yang diperiksa}', function () {
    $limit = max(1, (int) $this->option('limit'));

    $pendingOrders = Order::where('product_type', 'ppob')
        ->whereIn('payment_status', ['paid', 'success', 'settlement'])
        ->whereIn('order_status', ['processing', 'pending'])
        ->orderBy('id', 'asc')
        ->limit($limit)
        ->get();

    if ($pendingOrders->isEmpty()) {
        $this->info('Tidak ada transaksi PPOB pending yang perlu disinkronkan.');
        return 0;
    }

    $this->info("Memeriksa {$pendingOrders->count()} transaksi PPOB pending ke /Services/Check-Status Kiosbank...");

    $completed = 0;
    $failed = 0;
    $stillPending = 0;

    foreach ($pendingOrders as $order) {
        $res = OrderFulfillmentService::reconcilePendingPpobOrder($order);
        $order->refresh();

        $st = strtolower((string) $order->order_status);
        if (in_array($st, ['completed', 'success', 'lunas'], true)) {
            $completed++;
            $this->line(" ✓ [{$order->invoice_number}] -> BERHASIL ({$order->sn_or_account})");
        } elseif (in_array($st, ['failed', 'refunded'], true)) {
            $failed++;
            $this->warn(" ✕ [{$order->invoice_number}] -> GAGAL & AUTO-REFUND ({$res['message']})");
        } else {
            $stillPending++;
            $this->comment(" ⏳ [{$order->invoice_number}] -> MASIH PENDING ({$res['message']})");
        }
    }

    $this->info("Selesai: {$completed} Berhasil, {$failed} Gagal/Refund, {$stillPending} Masih Pending.");
    return 0;
})->purpose('Sinkronisasi otomatis status transaksi PPOB pending via /Services/Check-Status Kiosbank');

/**
 * Cron / CLI Command: Monitoring Saldo Deposit Provider & Kirim Notifikasi WhatsApp (Low Balance Alert)
 */
Artisan::command('ppob:check-low-balance {--force : Paksa kirim pesan WhatsApp meskipun di atas threshold}', function () {
    $force = (bool) $this->option('force');
    $service = new KiosbankService();
    $res = $service->checkAndNotifyLowBalance(null, $force);

    if ($res['sent'] ?? false) {
        $this->info("Alert WhatsApp Terkirim: {$res['message']}");
    } else {
        $this->comment("Status Saldo Deposit: " . ($res['message'] ?? 'Tidak ada pengiriman WA.'));
    }

    return 0;
})->purpose('Cek batas minimum saldo deposit Kiosbank dan kirim peringatan WhatsApp ke Admin');

/**
 * Cron / CLI Command: Auto-Expire / Auto-Cancel Pesanan QRIS, Top Up QRIS, dan Reseller QRIS
 * yang belum dibayar setelah melewati batas waktu 30 menit.
 */
Artisan::command('orders:expire-unpaid {--minutes=30 : Batas waktu kadaluarsa dalam menit}', function () {
    try {
        \App\Models\Setting::where('key', 'store_name')
            ->where('value', 'like', '%nandar%')
            ->update(['value' => 'PremiumKita']);
    } catch (\Throwable $e) {}

    $minutes = max(5, (int) $this->option('minutes'));
    $result = OrderFulfillmentService::expireUnpaidQrisRecords($minutes);

    if (($result['total'] ?? 0) === 0) {
        $this->info("Tidak ada tagihan QRIS unpaid yang melewati {$minutes} menit.");
        return 0;
    }

    $this->info("Auto-Expire selesai (>{$minutes} menit): {$result['orders']} Pesanan, {$result['topups']} Top Up Saldo, {$result['resellers']} Registrasi Reseller dibatalkan (expired).");
    return 0;
})->purpose('Batalkan otomatis tagihan QRIS (Pesanan, Top Up, Reseller) yang belum dibayar lebih dari 30 menit');

/**
 * CLI Command: Reset Seluruh Data Testing ke 0 untuk Persiapan Launching Publik
 */
Artisan::command('store:reset-launch {--keep-users : Hanya reset saldo user ke 0 tanpa menghapus user testing} {--keep-vouchers : Pertahankan kode voucher yang ada}', function () {
    $keepUsers = (bool) $this->option('keep-users');
    $keepVouchers = (bool) $this->option('keep-vouchers');

    $this->warn('Memulai proses Reset Data Testing ke 0 (Mode Siap Launching Publik)...');

    try {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $tablesToTruncate = [
            'orders'                 => 'Riwayat Pesanan & Pendapatan (orders)',
            'transaction_logs'       => 'Riwayat Mutasi Saldo & Testing (transaction_logs)',
            'topup_requests'         => 'Riwayat Deposit / Top Up Saldo (topup_requests)',
            'reseller_registrations' => 'Riwayat Pendaftaran Reseller (reseller_registrations)',
            'kiosbank_deposits'      => 'Riwayat Tiket Deposit Kiosbank (kiosbank_deposits)',
            'local_accounts'         => 'Stok Akun Produk Lokal (local_accounts)',
            'product_favorites'      => 'Produk Favorit User (product_favorites)',
        ];

        foreach ($tablesToTruncate as $table => $label) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                \Illuminate\Support\Facades\DB::table($table)->truncate();
                $this->line(" ✓ Dikosongkan: {$label}");
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('products')) {
            $deletedProducts = \App\Models\Product::whereIn('type', ['local', 'api'])->delete();
            $this->line(" ✓ Dihapus {$deletedProducts} Produk Lokal & Produk API (siap dipanggil ulang dari Admin)");
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('vouchers')) {
            if ($keepVouchers) {
                \App\Models\Voucher::query()->update(['used_count' => 0]);
                $this->line(' ✓ Kuota pemakaian Voucher direset ke 0');
            } else {
                \Illuminate\Support\Facades\DB::table('vouchers')->truncate();
                $this->line(' ✓ Dikosongkan: Daftar Voucher Testing (vouchers)');
            }
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
            if ($keepUsers) {
                \App\Models\User::where('role', '!=', 'admin')->update([
                    'balance' => 0,
                    'role'    => 'member',
                ]);
                $this->line(' ✓ Seluruh saldo User direset menjadi Rp 0');
            } else {
                $deletedUsers = \App\Models\User::where('role', '!=', 'admin')->delete();
                $this->line(" ✓ Dihapus {$deletedUsers} Akun User/Reseller Testing (Akun Admin tetap aman)");
            }
            \App\Models\User::where('role', 'admin')->update(['balance' => 0]);
            $this->line(' ✓ Saldo Akun Admin direset ke Rp 0');
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            \App\Models\Setting::updateOrCreate(['key' => 'provider_preflix_disabled'], ['value' => '0']);
            \App\Models\Setting::updateOrCreate(['key' => 'provider_premku_disabled'], ['value' => '0']);
        }

        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        \Illuminate\Support\Facades\Cache::forget('api_full_data');
        \Illuminate\Support\Facades\Cache::forget('admin_live_api_catalog_v3');

        $this->info('BERHASIL! Seluruh pendapatan, riwayat order, riwayat testing, produk lokal & produk API telah kembali ke 0.');
        return 0;
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
        $this->error('Gagal mereset database: ' . $e->getMessage());
        return 1;
    }
})->purpose('Kosongkan seluruh data testing (order, pendapatan, mutasi, produk lokal & API) kembali ke 0 untuk persiapan launching');

Schedule::command('orders:expire-unpaid')->everyMinute()->withoutOverlapping();
Schedule::command('ppob:sync-pending')->everyThreeMinutes()->withoutOverlapping();
Schedule::command('ppob:check-low-balance')->everyThirtyMinutes()->withoutOverlapping();
