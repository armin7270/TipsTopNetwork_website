<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:repair-db {--force : بازسازی اجباری حتی اگر دیتابیس کاربر داشته باشد}')]
#[Description('تشخیص دیتابیس ناقص (migrations ثبت شده ولی جداول اصلی غایب) و بازسازی کامل آن — برای ترمیم Volume های خراب Railway')]
class RepairDatabase extends Command
{
    /**
     * جداول اصلی که اپ برای بالا آمدن به آن‌ها نیاز دارد
     * (session/cache/queue روی دیتابیس هستند + جداول سیدر).
     */
    protected array $requiredTables = [
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'settings',
        'plans',
    ];

    public function handle(): int
    {
        // دیتابیس کاملاً خالی (بدون حتی جدول migrations) → حالت عادی است؛ migrate خودش همه‌چیز را می‌سازد
        if (! Schema::hasTable('migrations')) {
            return self::SUCCESS;
        }

        $missing = array_values(array_filter(
            $this->requiredTables,
            fn (string $table) => ! Schema::hasTable($table)
        ));

        if ($missing === []) {
            return self::SUCCESS;
        }

        $this->warn('⚠ دیتابیس ناقص است — migrations ثبت شده ولی این جداول غایب‌اند: '.implode(', ', $missing));

        // محافظ داده: اگر دیتابیس زنده کاربر دارد، بدون تایید صریح بازسازی نکن
        if (! $this->option('force') && Schema::hasTable('users') && DB::table('users')->count() > 0) {
            $this->error('دیتابیس کاربر دارد؛ بازسازی خودکار متوقف شد. برای بازسازی دستی: php artisan app:repair-db --force');

            return self::FAILURE;
        }

        $this->warn('🔧 بازسازی کامل دیتابیس (migrate:fresh + seed)...');

        $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);

        $this->info('✅ دیتابیس بازسازی شد.');

        return self::SUCCESS;
    }
}
