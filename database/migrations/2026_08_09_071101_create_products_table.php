<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price'); // Harga Jual ke pelanggan
            $table->string('type')->default('local'); // 'api' atau 'local'
            $table->integer('api_product_id')->nullable()->unique(); // ID asli dari Premku
            $table->integer('api_price')->nullable(); // Harga Modal dari API
            $table->string('image')->nullable();
            $table->integer('stock')->default(0);
            $table->string('status')->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};