<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'buyer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('buyer_name')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'buyer_name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('buyer_name');
            });
        }
    }
};

