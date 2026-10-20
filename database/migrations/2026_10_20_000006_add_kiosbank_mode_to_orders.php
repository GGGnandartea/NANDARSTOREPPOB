<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('kiosbank_mode')->nullable();
        });

        $mode = DB::table('settings')->where('key', 'kiosbank_mode')->value('value');
        DB::table('orders')->where('product_type', 'ppob')->update([
            'kiosbank_mode' => in_array($mode, ['development', 'production'], true) ? $mode : 'development',
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('kiosbank_mode'));
    }
};
