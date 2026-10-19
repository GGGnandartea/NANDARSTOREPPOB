<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'bill_amount')) {
                $table->bigInteger('bill_amount')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('orders', 'bill_admin_fee')) {
                $table->bigInteger('bill_admin_fee')->nullable()->after('bill_amount');
            }
            if (!Schema::hasColumn('orders', 'bill_penalty')) {
                $table->bigInteger('bill_penalty')->nullable()->after('bill_admin_fee');
            }
            if (!Schema::hasColumn('orders', 'bill_total')) {
                $table->bigInteger('bill_total')->nullable()->after('bill_penalty');
            }
            if (!Schema::hasColumn('orders', 'bill_reference')) {
                $table->string('bill_reference')->nullable()->after('bill_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['bill_reference', 'bill_total', 'bill_penalty', 'bill_admin_fee', 'bill_amount'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
