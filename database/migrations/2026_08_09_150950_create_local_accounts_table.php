<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('local_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('email');
            $table->string('password');
            $table->boolean('is_sold')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('local_accounts'); }
};