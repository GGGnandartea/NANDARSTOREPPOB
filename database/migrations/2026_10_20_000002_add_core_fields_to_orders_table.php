<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom email/whatsapp/voucher/sn dibuat oleh migration runtime di
     * FrontController pada instalasi lama, sehingga database baru yang
     * dibuat murni dari migrasi tidak memilikinya. Migration ini
     * memformalkan kolom-kolom tersebut agar instalasi baru lengkap.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('orders', 'whatsapp')) {
                $table->string('whatsapp')->nullable();
            }
            if (!Schema::hasColumn('orders', 'voucher_code')) {
                $table->string('voucher_code')->nullable();
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->integer('discount_amount')->default(0);
            }
            if (!Schema::hasColumn('orders', 'sn_or_account')) {
                $table->string('sn_or_account')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Sengaja tidak dihapus karena kolom inti aplikasi.
    }
};
