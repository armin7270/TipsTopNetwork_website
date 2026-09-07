<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // null یعنی رمز هنوز عوض نشده و باید در اولین ورود عوض شود
            $table->timestamp('password_changed_at')->nullable()->after('remember_token');
            // نقش مدیریتی: super (مدیرکل) | finance (مالی) | support (پشتیبانی)
            $table->string('admin_role', 20)->default('super')->after('is_admin');
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->timestamp('last_check_at')->nullable();
            $table->boolean('last_check_ok')->nullable();
            $table->string('last_check_error', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['last_check_at', 'last_check_ok', 'last_check_error']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_changed_at', 'admin_role']);
        });
    }
};
