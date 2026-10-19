<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * account_details dan sn_or_account berisi gabungan multi-akun untuk
     * pembelian qty > 1, sehingga melebihi batas varchar(255). Ubah ke text.
     */
    public function up(): void
    {
        if (Schema::hasColumn('orders', 'account_details')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `orders` MODIFY `account_details` TEXT NULL');
            } else {
                Schema::table('orders', function (Blueprint $table) {
                    $table->text('account_details')->nullable()->change();
                });
            }
        }
        if (Schema::hasColumn('orders', 'sn_or_account')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `orders` MODIFY `sn_or_account` TEXT NULL');
            } else {
                Schema::table('orders', function (Blueprint $table) {
                    $table->text('sn_or_account')->nullable()->change();
                });
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan (text adalah tipe yang benar).
    }
};
