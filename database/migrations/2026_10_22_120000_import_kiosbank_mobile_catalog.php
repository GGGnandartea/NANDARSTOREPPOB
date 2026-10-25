<?php

use App\Services\KiosbankPulsaCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppob_products', function (Blueprint $table) {
            $table->string('price_synced_mode')->nullable();
            $table->timestamp('price_synced_at')->nullable();
        });

        foreach (KiosbankPulsaCatalog::products() as $product) {
            $metadata = KiosbankPulsaCatalog::metadata($product['code'], $product['name']);
            DB::table('ppob_products')->insertOrIgnore(array_merge($product, $metadata, [
                'base_price' => 0, 'margin' => 1000, 'price' => 0, 'status' => 'active',
                'provider' => 'kiosbank', 'created_at' => now(), 'updated_at' => now(),
            ]));
            // Preserve prices, margins, names and manual disabled states of existing rows.
            DB::table('ppob_products')->where('code', $product['code'])->where('provider', 'kiosbank')->update($metadata);
        }
    }

    public function down(): void
    {
        Schema::table('ppob_products', fn (Blueprint $table) => $table->dropColumn(['price_synced_mode', 'price_synced_at']));
        // Keep imported catalog rows, including any prices configured after deployment.
    }
};
