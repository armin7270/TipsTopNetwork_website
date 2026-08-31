<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // سرورهای پنل 3x-ui
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_scheme', 10)->default('http');
            $table->string('api_host');
            $table->unsignedSmallInteger('api_port')->default(2053);
            $table->string('api_path', 191)->nullable();
            $table->string('username');
            $table->text('password');
            $table->string('public_host')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // اینباندهای هر سرور
        Schema::create('inbounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('xui_inbound_id');
            $table->string('protocol', 20);
            $table->unsignedSmallInteger('port');
            $table->unsignedSmallInteger('public_port')->nullable();
            $table->string('remark')->nullable();
            $table->longText('panel_data')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['server_id', 'xui_inbound_id']);
        });

        // پلن‌های فروش
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('price_toman')->default(0);
            $table->decimal('volume_gb', 10, 2)->default(0); // 0 = نامحدود
            $table->unsignedInteger('duration_days')->default(30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('inbound_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inbound_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // سفارش‌ها
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('renewal_of')->nullable();
            $table->string('plan_name');
            $table->decimal('volume_gb', 10, 2)->default(0);
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedBigInteger('price_toman')->default(0);
            $table->string('status', 30)->default('pending_payment')->index();
            $table->string('xui_email', 191)->nullable()->index();
            $table->string('xui_uuid', 64)->nullable();
            $table->string('bank_reference', 100)->nullable();
            $table->unsignedBigInteger('paid_amount')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('used_bytes')->default(0);
            $table->unsignedBigInteger('total_bytes')->default(0);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inbound_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inbound_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // تنظیمات سایت
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('inbound_order');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('inbound_plan');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('inbounds');
        Schema::dropIfExists('servers');
    }
};
