<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // بررسی گواهی SSL هنگام اتصال به پنل (برای گواهی self-signed روی لوکال قابل خاموش‌کردن است)
            $table->boolean('ssl_verify')->default(true)->after('public_host');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('ssl_verify');
        });
    }
};
