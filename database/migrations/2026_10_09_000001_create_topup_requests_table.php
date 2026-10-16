<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('topup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method')->default('qris');
            $table->string('status')->default('pending'); // pending, paid, failed, expired
            $table->string('qris_url')->nullable();
            $table->string('order_id_gateway')->nullable(); // ID dari QRIS gateway
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('topup_requests'); }
};

