<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'api_trx_id')) {
                $table->string('api_trx_id')->nullable()->after('product_type');
            }
            if (!Schema::hasColumn('orders', 'notes')) {
                // Jangan memakai after('whatsapp'): kolom whatsapp pada instalasi
                // lama dibuat oleh migration runtime di FrontController.
                $table->text('notes')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'api_trx_id')) {
                $table->dropColumn('api_trx_id');
            }
            if (Schema::hasColumn('orders', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};

