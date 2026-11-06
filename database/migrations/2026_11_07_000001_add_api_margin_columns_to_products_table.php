<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'api_price')) {
                    $table->integer('api_price')->nullable()->after('api_product_id');
                }
                if (!Schema::hasColumn('products', 'api_margin')) {
                    $table->integer('api_margin')->nullable()->after('api_price');
                }
                if (!Schema::hasColumn('products', 'api_reseller_margin')) {
                    $table->integer('api_reseller_margin')->nullable()->after('api_margin');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'api_reseller_margin')) {
                    $table->dropColumn('api_reseller_margin');
                }
                if (Schema::hasColumn('products', 'api_margin')) {
                    $table->dropColumn('api_margin');
                }
            });
        }
    }
};

