<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Kode Voucher (Misal: PROMO99)
            $table->enum('type', ['nominal', 'percent']); // Potongan harga tetap atau persen
            $table->decimal('value', 12, 2); // Jumlah potongan
            $table->integer('max_uses')->nullable(); // Kuota voucher (kosong = unlimited)
            $table->integer('used')->default(0); // Berapa kali sudah dipakai
            $table->date('expired_at')->nullable(); // Tanggal kedaluwarsa
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('vouchers');
    }
};