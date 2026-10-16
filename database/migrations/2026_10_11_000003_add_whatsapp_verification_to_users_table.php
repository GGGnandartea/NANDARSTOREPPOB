<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_whatsapp_verified')) {
                $table->boolean('is_whatsapp_verified')->default(false)->after('whatsapp');
            }
            if (!Schema::hasColumn('users', 'wa_verification_code')) {
                $table->string('wa_verification_code', 30)->nullable()->after('is_whatsapp_verified');
            }
            if (!Schema::hasColumn('users', 'whatsapp_verified_at')) {
                $table->timestamp('whatsapp_verified_at')->nullable()->after('wa_verification_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_whatsapp_verified')) {
                $table->dropColumn('is_whatsapp_verified');
            }
            if (Schema::hasColumn('users', 'wa_verification_code')) {
                $table->dropColumn('wa_verification_code');
            }
            if (Schema::hasColumn('users', 'whatsapp_verified_at')) {
                $table->dropColumn('whatsapp_verified_at');
            }
        });
    }
};

