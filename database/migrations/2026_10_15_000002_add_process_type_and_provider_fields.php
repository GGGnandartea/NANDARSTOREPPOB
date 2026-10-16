<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'process_type')) {
                $table->string('process_type')->default('auto')->after('type'); // 'auto' (otomatis dari stok) atau 'manual' (diproses admin)
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'provider')) {
                $table->string('provider')->nullable()->after('product_type'); // 'Preflix', 'Premku', 'Otomatis', 'Manual Admin'
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'process_type')) {
                $table->dropColumn('process_type');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'provider')) {
                $table->dropColumn('provider');
            }
        });
    }
};

