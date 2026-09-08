<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * حذف کامل گزینه «بررسی گواهی SSL» — اتصال به پنل 3x-ui همیشه بدون
     * اعتبارسنجی گواهی انجام می‌شود (اکثر پنل‌ها self-signed هستند).
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'ssl_verify')) {
                $table->dropColumn('ssl_verify');
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('ssl_verify')->default(true)->after('public_host');
        });
    }
};
