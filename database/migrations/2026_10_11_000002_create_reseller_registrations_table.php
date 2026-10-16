<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseller_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('order_id_gateway')->nullable();
            $table->decimal('amount', 15, 2)->default(20000);
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->string('qris_url')->nullable();
            $table->string('payment_method')->default('qris'); // qris, balance
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_registrations');
    }
};

