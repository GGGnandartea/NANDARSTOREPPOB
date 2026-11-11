<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reseller_api_clients')) {
            Schema::create('reseller_api_clients', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('api_key', 80)->unique();
                $table->text('secret_key_encrypted');
                $table->string('secret_key_hint', 40);
                $table->string('webhook_url', 500)->nullable();
                $table->text('webhook_secret_encrypted')->nullable();
                $table->string('webhook_secret_hint', 40)->nullable();
                $table->text('ip_whitelist')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('admin_suspended')->default(false);
                $table->string('admin_suspend_reason', 255)->nullable();
                $table->integer('custom_rate_limit')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->string('last_used_ip', 64)->nullable();
                $table->unsignedBigInteger('total_requests')->default(0);
                $table->unsignedBigInteger('total_orders')->default(0);
                $table->timestamp('rotated_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reseller_api_logs')) {
            Schema::create('reseller_api_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('endpoint', 180);
                $table->string('method', 12);
                $table->string('ip_address', 64)->nullable();
                $table->unsignedSmallInteger('status_code');
                $table->string('ref_id', 100)->nullable()->index();
                $table->string('invoice_number', 100)->nullable()->index();
                $table->string('error_code', 60)->nullable();
                $table->unsignedInteger('response_time_ms')->default(0);
                $table->string('summary', 255)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'is_open_api')) {
                    $table->boolean('is_open_api')->default(false)->index();
                }
                if (!Schema::hasColumn('orders', 'api_client_ref_id')) {
                    $table->string('api_client_ref_id', 100)->nullable();
                }
                if (!Schema::hasColumn('orders', 'open_api_callback_url')) {
                    $table->string('open_api_callback_url', 500)->nullable();
                }
                if (!Schema::hasColumn('orders', 'open_api_webhook_sent_at')) {
                    $table->timestamp('open_api_webhook_sent_at')->nullable();
                }
                if (!Schema::hasColumn('orders', 'open_api_webhook_event')) {
                    $table->string('open_api_webhook_event', 40)->nullable();
                }
            });

            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unique(['user_id', 'api_client_ref_id'], 'orders_user_api_client_ref_unique');
                });
            } catch (\Throwable $e) {
                // Index may already exist
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropUnique('orders_user_api_client_ref_unique');
                });
            } catch (\Throwable $e) {}

            Schema::table('orders', function (Blueprint $table) {
                foreach (['is_open_api', 'api_client_ref_id', 'open_api_callback_url', 'open_api_webhook_sent_at', 'open_api_webhook_event'] as $col) {
                    if (Schema::hasColumn('orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('reseller_api_logs');
        Schema::dropIfExists('reseller_api_clients');
    }
};
