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
    $minutes = max(5, (int) $this->option('minutes'));
    $result = OrderFulfillmentService::expireUnpaidQrisRecords($minutes);

    if (($result['total'] ?? 0) === 0) {
        $this->info("Tidak ada tagihan QRIS unpaid yang melewati {$minutes} menit.");
        return 0;
    }

    $this->info("Auto-Expire selesai (>{$minutes} menit): {$result['orders']} Pesanan, {$result['topups']} Top Up Saldo, {$result['resellers']} Registrasi Reseller dibatalkan (expired).");
    return 0;
})->purpose('Batalkan otomatis tagihan QRIS (Pesanan, Top Up, Reseller) yang belum dibayar lebih dari 30 menit');

Schedule::command('orders:expire-unpaid')->everyMinute()->withoutOverlapping();
Schedule::command('ppob:sync-pending')->everyThreeMinutes()->withoutOverlapping();
Schedule::command('ppob:check-low-balance')->everyThirtyMinutes()->withoutOverlapping();
