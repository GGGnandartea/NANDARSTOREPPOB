<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('products')->where('type', 'local')
            ->where(fn ($query) => $query->whereNull('process_type')->orWhere('process_type', '!=', 'manual'))
            ->update(['stock' => DB::raw('(SELECT COUNT(*) FROM local_accounts WHERE local_accounts.product_id = products.id AND local_accounts.is_sold = 0)')]);
    }

    public function down(): void
    {
        // This repairs derived counts only. Never restore inaccurate inventory.
    }
};
