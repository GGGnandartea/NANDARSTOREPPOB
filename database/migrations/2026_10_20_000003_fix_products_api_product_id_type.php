<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode menyimpan string seperti PREFLIX-434 atau PREMKU-155 di
     * api_product_id, tetapi migration awal mendefinisikan kolom integer.
     * SQLite melonggarkan aturan tipe sehingga data tersimpan, sementara
     * MySQL menolaknya. Ubah menjadi string agar sesuai dengan kode.
     */
    public function up(): void
    {
        if (Schema::hasColumn('products', 'api_product_id')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE `products` MODIFY `api_product_id` VARCHAR(255) NULL');
            } else {
                // SQLite tidak mendukung MODIFY COLUMN; abaikan karena
                // SQLite menerima string pada kolom integer.
            }
        }
    }

    public function down(): void
    {
        // Sengaja tidak dikembalikan ke integer (string adalah tipe yang benar).
    }
};
