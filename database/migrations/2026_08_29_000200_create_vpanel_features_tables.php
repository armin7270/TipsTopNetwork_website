<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ستون‌های جدید کاربران (کیف پول، معرفی، تلگرام، اکانت تست)
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('balance')->default(0)->after('status');
            $table->string('referral_code', 32)->nullable()->unique()->after('balance');
            $table->foreignId('referrer_id')->nullable()->after('referral_code');
            $table->string('telegram_chat_id', 32)->nullable()->index()->after('referrer_id');
            $table->string('telegram_username', 64)->nullable()->after('telegram_chat_id');
            $table->string('bot_state', 100)->nullable()->after('telegram_username');
            $table->unsignedInteger('trial_accounts_taken')->default(0)->after('bot_state');
        });

        // ستون‌های جدید سفارش‌ها (روش پرداخت، منبع خرید، تصویر رسید)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('source', 20)->default('web')->after('status');
            $table->string('payment_method', 20)->nullable()->after('source');
            $table->string('receipt_path')->nullable()->after('bank_reference');
        });

        // دفتر تراکنش‌ها (کیف پول)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30)->index(); // deposit, purchase, refund, withdrawal, referral_reward, admin_adjustment
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('pending')->index(); // pending, awaiting_verification, completed, failed
            $table->string('method', 20)->nullable(); // card, wallet, crypto, system
            $table->string('description')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // تیکت‌های پشتیبانی
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject', 191);
            $table->string('priority', 10)->default('medium'); // low, medium, high
            $table->string('status', 20)->default('open')->index(); // open, answered, closed
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_staff')->default(false);
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->timestamps();
        });

        // اکانت‌های تست
        Schema::create('user_trials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email', 191);
            $table->string('uuid', 64);
            $table->text('config')->nullable();
            $table->unsignedInteger('volume_mb');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // نوتیفیکیشن‌های درون‌برنامه‌ای
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->string('type', 50);
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('user_trials');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['source', 'payment_method', 'receipt_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'balance', 'referral_code', 'referrer_id', 'telegram_chat_id',
                'telegram_username', 'bot_state', 'trial_accounts_taken',
            ]);
        });
    }
};
