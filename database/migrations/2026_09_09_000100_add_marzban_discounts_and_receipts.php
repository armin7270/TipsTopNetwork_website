<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * بسته امکانات جدید:
     * - پشتیبانی چند-پنلی (3x-ui | Marzban) در سرورها
     * - کدهای تخفیف
     * - ثبت IP ثبت‌نام (ضد تقلب رفرال)
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('panel_type', 20)->default('xui')->after('name'); // xui | marzban
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('discount_amount')->default(0);
            $table->string('sub_url', 500)->nullable()->after('xui_email'); // لینک اشتراک Marzban
        });

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->unsignedInteger('value'); // درصد یا مبلغ تومان
            $table->unsignedBigInteger('min_amount')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_codes');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn(['discount_amount', 'sub_url']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('panel_type');
        });
    }
};
