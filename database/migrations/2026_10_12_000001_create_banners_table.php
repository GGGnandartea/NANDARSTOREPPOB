<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('badge')->nullable();
            $table->text('image')->nullable(); // Gambar Desktop (atau universal)
            $table->text('image_mobile')->nullable(); // Gambar khusus Mobile (opsional)
            $table->string('link_url')->nullable();
            $table->string('bg_gradient')->nullable()->default('bg-gradient-to-r from-orange-600 to-red-600');
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};

