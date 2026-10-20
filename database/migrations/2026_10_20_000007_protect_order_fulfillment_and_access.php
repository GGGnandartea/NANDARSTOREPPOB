<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('fulfillment_started_at')->nullable();
            $table->text('guest_access_token')->nullable();
            $table->unsignedBigInteger('provider_amount')->nullable();
            $table->string('provider_product_code')->nullable();
            $table->string('provider_customer_id')->nullable();
        });

        // Previously paid orders may already have reached the provider, even if
        // no transaction ID was returned. Reconcile them instead of reordering.
        DB::table('orders')->whereIn('payment_status', ['paid', 'success', 'settlement'])
            ->update(['fulfillment_started_at' => now()]);

        DB::table('orders')->whereNull('user_id')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'guest_access_token' => Crypt::encryptString(Str::random(64)),
                ]);
            }
        });

        // Bill totals are historical provider amounts. Do not invent historical
        // prepaid costs from today's catalog or from the customer's sale price.
        DB::table('orders')->where('product_type', 'ppob')->where('bill_total', '>', 0)
            ->update(['provider_amount' => DB::raw('bill_total')]);
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn([
            'fulfillment_started_at', 'guest_access_token', 'provider_amount',
            'provider_product_code', 'provider_customer_id',
        ]));
    }
};
