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
        if (!Schema::hasTable('ppob_products')) {
            Schema::create('ppob_products', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // e.g. 500521, PLN20, 6001BR, DANA10
                $table->string('name'); // e.g. Telkomsel Prabayar 5.000
                $table->string('category')->default('pulsa'); // pulsa, data, pln, game, ewallet, tagihan
                $table->string('operator')->default('Telkomsel'); // Telkomsel, Indosat, XL, PLN, DANA, dll
                $table->string('prefix_id')->nullable(); // 21, 11, 31, 100302, AAO.AAAJ, dll
                $table->integer('base_price')->default(0); // Harga modal dari API Kiosbank
                $table->integer('margin')->default(1000); // Margin keuntungan toko
                $table->integer('price')->default(0); // Harga jual (base_price + margin)
                $table->integer('reseller_price')->nullable(); // Harga khusus reseller
                $table->string('status')->default('active'); // active, maintenance, inactive
                $table->string('provider')->default('kiosbank');
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kiosbank_deposits')) {
            Schema::create('kiosbank_deposits', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique(); // e.g. DEP-KB-20261016-1234
                $table->decimal('amount', 15, 2);
                $table->string('payment_method')->default('bca'); // bca, mandiri, bri, bni, qris, developer_mode
                $table->string('status')->default('pending'); // pending, success, canceled
                $table->string('bank_account_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->integer('unique_code')->default(0);
                $table->decimal('total_payment', 15, 2);
                $table->text('notes')->nullable();
                $table->string('proof_image')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kiosbank_deposits');
        Schema::dropIfExists('ppob_products');
    }
};

